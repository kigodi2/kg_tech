<?php

namespace App\Services\MarkEntry\Reporting;

use App\Models\District;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\MarkImportBatch;
use App\Models\RawMark;
use App\Models\Region;
use App\Models\School;
use App\Models\Subject;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class ReportScoresheetPdfService
{
    /**
     * Resolve exam_year_id (PK) to year_label (e.g. 2026) for batch queries.
     */
    private function resolveYearValue(int $examYearId): int
    {
        $examYear = ExamYear::findOrFail($examYearId);
        return (int) $examYear->year_label;
    }

    /**
     * Get allowed statuses based on mode.
     */
    private function allowedStatuses(string $mode): array
    {
        if ($mode === 'all') {
            return [
                MarkImportBatch::STATUS_DRAFT,
                MarkImportBatch::STATUS_VALIDATED,
                MarkImportBatch::STATUS_SUBMITTED,
                MarkImportBatch::STATUS_APPROVED,
                MarkImportBatch::STATUS_LOCKED,
                MarkImportBatch::STATUS_PROCESSED,
            ];
        }

        return [
            MarkImportBatch::STATUS_APPROVED,
            MarkImportBatch::STATUS_LOCKED,
            MarkImportBatch::STATUS_PROCESSED,
        ];
    }

    /**
     * Generate filled scoresheet data for PDF rendering
     */
    public function generateFilledScoresheet(int $examYearId, int $schoolId, int $subjectId, string $mode = 'approved'): array
    {
        $examYear = ExamYear::findOrFail($examYearId);
        $school = School::findOrFail($schoolId);
        $subject = Subject::findOrFail($subjectId);
        $yearValue = (int) $examYear->year_label;
        $statuses = $this->allowedStatuses($mode);

        // Get raw marks for this school/subject/year from batches matching the mode
        $marks = RawMark::query()
            ->whereHas('batch', function ($q) use ($yearValue, $schoolId, $subjectId, $statuses) {
                $q->where('exam_year', $yearValue)
                  ->where('school_id', $schoolId)
                  ->where('subject_id', $subjectId)
                  ->whereIn('status', $statuses);
            })
            ->where('has_errors', false)
            ->with('candidate:id,candidate_id,full_name,gender,combination')
            ->orderBy('candidate_index_number')
            ->get();

        $paperStructure = [
            'written_papers' => $subject->written_papers ?? 2,
            'has_practical' => (bool)($subject->has_practical ?? false),
            'has_project' => (bool)($subject->has_project ?? false),
        ];

        // Calculate total for each candidate
        $candidateRows = $marks->map(function ($mark) use ($paperStructure) {
            $total = 0;
            $papers = [];

            for ($i = 1; $i <= $paperStructure['written_papers']; $i++) {
                $field = "paper_{$i}_marks";
                $val = $mark->$field;
                $papers["P{$i}"] = $val;
                $total += (float)($val ?? 0);
            }

            if ($paperStructure['has_practical']) {
                $papers['PRAC'] = $mark->practical_marks;
                $total += (float)($mark->practical_marks ?? 0);
            }

            if ($paperStructure['has_project']) {
                $papers['PROJ'] = $mark->project_marks;
                $total += (float)($mark->project_marks ?? 0);
            }

            return [
                'index_number' => $mark->candidate_index_number ?? $mark->candidate?->candidate_id ?? '—',
                'full_name' => $mark->full_name ?? $mark->candidate?->full_name ?? '—',
                'gender' => strtoupper(substr($mark->candidate?->gender ?? 'U', 0, 1)),
                'combination' => $mark->candidate?->combination ?? '—',
                'papers' => $papers,
                'total' => $total,
            ];
        });

        return [
            'exam_year' => $examYear,
            'school' => $school,
            'subject' => $subject,
            'candidates' => $candidateRows,
            'paper_structure' => $paperStructure,
            'total_candidates' => $candidateRows->count(),
            'timestamp' => now(),
            'mode' => $mode,
        ];
    }

    const TEMP_DIR = 'storage/app/temp/report-scoresheets';

    /**
     * Generate a ZIP of filled scoresheet PDFs for all subjects at a school
     */
    public function generateSchoolZip(int $examYearId, int $schoolId, string $mode = 'approved'): array
    {
        $examYear = ExamYear::findOrFail($examYearId);
        $school = School::findOrFail($schoolId);
        $subjects = $this->getSubjectsWithMarks($schoolId, $examYearId, $mode);

        if ($subjects->isEmpty()) {
            throw new \Exception("No subjects with marks found for {$school->name} in {$examYear->year_label}");
        }

        $this->ensureTempDirectory();
        $zipFilename = sprintf('%s_%s_scoresheets.zip', $school->code, $examYear->year_label);
        $zipPath = base_path(self::TEMP_DIR . '/' . $zipFilename);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Failed to create ZIP file');
        }

        $viewName = $mode === 'all' ? 'mark-entry.pdf.filled-scoresheet-draft' : 'mark-entry.pdf.filled-scoresheet';

        $pdfFiles = [];
        foreach ($subjects as $subject) {
            $data = $this->generateFilledScoresheet($examYearId, $schoolId, $subject->id, $mode);
            if ($data['total_candidates'] === 0) continue;

            $pdfName = sprintf('%s_%s_%s.pdf', $school->code, $subject->code, $examYear->year_label);
            $pdfPath = base_path(self::TEMP_DIR . '/' . $pdfName);

            $pdf = Pdf::loadView($viewName, $data)
                ->setPaper('a4', 'landscape')
                ->setOption('enable-local-file-access', true);
            file_put_contents($pdfPath, $pdf->output());

            $zip->addFile($pdfPath, $pdfName);
            $pdfFiles[] = $pdfPath;
        }

        $zip->close();
        foreach ($pdfFiles as $f) { @unlink($f); }

        return ['file_path' => $zipPath, 'filename' => $zipFilename, 'total_subjects' => $subjects->count()];
    }

    /**
     * Generate a ZIP of filled scoresheet PDFs for all schools in a district
     */
    public function generateDistrictZip(int $examYearId, int $districtId, string $mode = 'approved'): array
    {
        $examYear = ExamYear::findOrFail($examYearId);
        $district = District::findOrFail($districtId);
        $schools = $this->getSchoolsWithMarks($districtId, $examYearId, $mode);

        if ($schools->isEmpty()) {
            throw new \Exception("No schools with marks found in {$district->name} for {$examYear->year_label}");
        }

        $this->ensureTempDirectory();
        $zipFilename = sprintf('district_%s_%s_scoresheets.zip', $district->code, $examYear->year_label);
        $zipPath = base_path(self::TEMP_DIR . '/' . $zipFilename);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Failed to create ZIP file');
        }

        $viewName = $mode === 'all' ? 'mark-entry.pdf.filled-scoresheet-draft' : 'mark-entry.pdf.filled-scoresheet';

        $pdfFiles = [];
        foreach ($schools as $school) {
            $subjects = $this->getSubjectsWithMarks($school->id, $examYearId, $mode);
            foreach ($subjects as $subject) {
                try {
                    $data = $this->generateFilledScoresheet($examYearId, $school->id, $subject->id, $mode);
                    if ($data['total_candidates'] === 0) continue;
                } catch (\Exception $e) { continue; }

                $pdfName = sprintf('%s/%s_%s_%s.pdf', $school->code, $school->code, $subject->code, $examYear->year_label);
                $pdfPath = base_path(self::TEMP_DIR . '/' . str_replace('/', '_', $pdfName));

                $pdf = Pdf::loadView($viewName, $data)
                    ->setPaper('a4', 'landscape')
                    ->setOption('enable-local-file-access', true);
                file_put_contents($pdfPath, $pdf->output());

                $zip->addFile($pdfPath, $pdfName);
                $pdfFiles[] = $pdfPath;
            }
        }

        $zip->close();
        foreach ($pdfFiles as $f) { @unlink($f); }

        return ['file_path' => $zipPath, 'filename' => $zipFilename, 'total_schools' => $schools->count()];
    }

    /**
     * Generate a ZIP of filled scoresheet PDFs for all districts in a region
     */
    public function generateRegionZip(int $examYearId, int $regionId, string $mode = 'approved'): array
    {
        $examYear = ExamYear::findOrFail($examYearId);
        $region = Region::findOrFail($regionId);

        $districts = District::where('region_id', $regionId)->get();
        $allSchools = collect();

        foreach ($districts as $district) {
            $schools = $this->getSchoolsWithMarks($district->id, $examYearId, $mode);
            foreach ($schools as $school) {
                $allSchools->push(['school' => $school, 'district' => $district]);
            }
        }

        if ($allSchools->isEmpty()) {
            throw new \Exception("No schools with marks found in {$region->name} for {$examYear->year_label}");
        }

        $this->ensureTempDirectory();
        $zipFilename = sprintf('region_%s_%s_scoresheets.zip', $region->code, $examYear->year_label);
        $zipPath = base_path(self::TEMP_DIR . '/' . $zipFilename);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Failed to create ZIP file');
        }

        $viewName = $mode === 'all' ? 'mark-entry.pdf.filled-scoresheet-draft' : 'mark-entry.pdf.filled-scoresheet';

        $pdfFiles = [];
        foreach ($allSchools as $entry) {
            $school = $entry['school'];
            $district = $entry['district'];
            $subjects = $this->getSubjectsWithMarks($school->id, $examYearId, $mode);

            foreach ($subjects as $subject) {
                try {
                    $data = $this->generateFilledScoresheet($examYearId, $school->id, $subject->id, $mode);
                    if ($data['total_candidates'] === 0) continue;
                } catch (\Exception $e) { continue; }

                $pdfName = sprintf('%s/%s/%s_%s_%s.pdf', $district->code, $school->code, $school->code, $subject->code, $examYear->year_label);
                $pdfPath = base_path(self::TEMP_DIR . '/' . str_replace('/', '_', $pdfName));

                $pdf = Pdf::loadView($viewName, $data)
                    ->setPaper('a4', 'landscape')
                    ->setOption('enable-local-file-access', true);
                file_put_contents($pdfPath, $pdf->output());

                $zip->addFile($pdfPath, $pdfName);
                $pdfFiles[] = $pdfPath;
            }
        }

        $zip->close();
        foreach ($pdfFiles as $f) { @unlink($f); }

        return ['file_path' => $zipPath, 'filename' => $zipFilename, 'total_schools' => $allSchools->count()];
    }

    private function ensureTempDirectory(): void
    {
        $dir = base_path(self::TEMP_DIR);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Get all subjects with imported marks for a school
     */
    public function getSubjectsWithMarks(int $schoolId, int $examYearId, string $mode = 'approved'): Collection
    {
        $yearValue = $this->resolveYearValue($examYearId);
        $statuses = $this->allowedStatuses($mode);

        return Subject::query()
            ->select(
                'subjects.id', 'subjects.code', 'subjects.name',
                DB::raw('MAX(mark_import_batches.status) as batch_status'),
                DB::raw('SUM(mark_import_batches.total_records) as total_records')
            )
            ->join('mark_import_batches', 'subjects.id', '=', 'mark_import_batches.subject_id')
            ->where('mark_import_batches.school_id', $schoolId)
            ->where('mark_import_batches.exam_year', $yearValue)
            ->whereIn('mark_import_batches.status', $statuses)
            ->groupBy('subjects.id', 'subjects.code', 'subjects.name')
            ->orderBy('subjects.code')
            ->get();
    }

    /**
     * Get schools with imported marks in a district
     */
    public function getSchoolsWithMarks(int $districtId, int $examYearId, string $mode = 'approved'): Collection
    {
        $yearValue = $this->resolveYearValue($examYearId);
        $statuses = $this->allowedStatuses($mode);

        return School::query()
            ->select(
                'schools.id', 'schools.code', 'schools.name',
                DB::raw('MAX(mark_import_batches.status) as batch_status'),
                DB::raw('SUM(mark_import_batches.total_records) as total_records')
            )
            ->join('mark_import_batches', 'schools.id', '=', 'mark_import_batches.school_id')
            ->where('mark_import_batches.district_id', $districtId)
            ->where('mark_import_batches.exam_year', $yearValue)
            ->whereIn('mark_import_batches.status', $statuses)
            ->groupBy('schools.id', 'schools.code', 'schools.name')
            ->orderBy('schools.code')
            ->get();
    }
}
