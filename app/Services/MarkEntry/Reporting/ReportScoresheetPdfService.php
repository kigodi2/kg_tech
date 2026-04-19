<?php

namespace App\Services\MarkEntry\Reporting;

use App\Models\District;
use App\Models\Candidate;
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
    private const BULK_EXPORT_TIMEOUT_SECONDS = 600;
    private const BULK_EXPORT_MEMORY_LIMIT = '1024M';

    /**
     * Configure runtime limits for heavy PDF ZIP generation.
     */
    private function configureBulkExportRuntime(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(self::BULK_EXPORT_TIMEOUT_SECONDS);
        }

        @ini_set('max_execution_time', (string) self::BULK_EXPORT_TIMEOUT_SECONDS);
        @ini_set('memory_limit', self::BULK_EXPORT_MEMORY_LIMIT);
    }

    /**
     * Resolve candidate gender from linked candidate first, then raw CSV row.
     */
    private function resolveGender(?string $candidateGender, mixed $rawData): string
    {
        $gender = $candidateGender;

        if (($gender === null || trim($gender) === '') && is_array($rawData)) {
            // CSV template is [index_number, sex, paper_1, ...]
            $gender = $rawData[1] ?? null;
        }

        $normalized = strtoupper(substr(trim((string) $gender), 0, 1));

        return in_array($normalized, ['M', 'F'], true) ? $normalized : 'U';
    }

    /**
     * Resolve candidate combination with sensible fallbacks.
     */
    private function resolveCombination(?string $candidateCombination, mixed $fallbackCandidate): string
    {
        $combination = trim((string) ($candidateCombination ?? ''));

        if ($combination === '' && $fallbackCandidate) {
            $combination = trim((string) ($fallbackCandidate->resolved_combination ?? ''));
        }

        return $combination !== '' ? $combination : '—';
    }

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
            ->with('candidate:id,candidate_id,full_name,gender,combination,combination_id')
            ->orderBy('candidate_index_number')
            ->get();

        // Fallback map by index number for rows whose candidate_id is null or partially populated.
        $indexNumbers = $marks->pluck('candidate_index_number')
            ->filter(fn ($index) => !empty($index))
            ->unique()
            ->values();

        $candidateFallbackMap = collect();
        if ($indexNumbers->isNotEmpty()) {
            $candidateFallbackMap = Candidate::query()
                ->leftJoin('combinations', 'combinations.id', '=', 'candidates.combination_id')
                ->where('candidates.school_id', $schoolId)
                ->whereIn('candidates.candidate_id', $indexNumbers)
                ->select(
                    'candidates.candidate_id',
                    'candidates.gender',
                    DB::raw("COALESCE(NULLIF(candidates.combination, ''), combinations.code) as resolved_combination")
                )
                ->get()
                ->keyBy('candidate_id');
        }

        $paperStructure = [
            'written_papers' => $subject->written_papers ?? 2,
            'has_practical' => (bool)($subject->has_practical ?? false),
            'has_project' => (bool)($subject->has_project ?? false),
        ];

        // Calculate total for each candidate
        $candidateRows = $marks->map(function ($mark) use ($paperStructure, $candidateFallbackMap) {
            $total = 0;
            $papers = [];
            $paperCount = 0;
            $fallbackCandidate = $candidateFallbackMap->get($mark->candidate_index_number);

            for ($i = 1; $i <= $paperStructure['written_papers']; $i++) {
                $field = "paper_{$i}_marks";
                $val = $mark->$field;
                $papers["P{$i}"] = $val;
                $total += (float)($val ?? 0);
                if ($val !== null && $val !== '' && is_numeric($val)) {
                    $paperCount++;
                }
            }

            if ($paperStructure['has_practical']) {
                $papers['PRAC'] = $mark->practical_marks;
                $total += (float)($mark->practical_marks ?? 0);
                if ($mark->practical_marks !== null && $mark->practical_marks !== '' && is_numeric($mark->practical_marks)) {
                    $paperCount++;
                }
            }

            if ($paperStructure['has_project']) {
                $papers['PROJ'] = $mark->project_marks;
                $total += (float)($mark->project_marks ?? 0);
                if ($mark->project_marks !== null && $mark->project_marks !== '' && is_numeric($mark->project_marks)) {
                    $paperCount++;
                }
            }

            $markValue = $paperCount > 0 ? round($total / $paperCount, 2) : null;

            return [
                'index_number' => $mark->candidate_index_number ?? $mark->candidate?->candidate_id ?? '—',
                'full_name' => $mark->full_name ?? $mark->candidate?->full_name ?? '—',
                'gender' => $this->resolveGender($mark->candidate?->gender ?? $fallbackCandidate?->gender, $mark->raw_data),
                'combination' => $this->resolveCombination($mark->candidate?->combination, $fallbackCandidate),
                'papers' => $papers,
                'total' => $total,
                'mark' => $markValue,
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
        $this->configureBulkExportRuntime();

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
                ->setPaper('a4', 'portrait')
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
        $this->configureBulkExportRuntime();

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
                    ->setPaper('a4', 'portrait')
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
        $this->configureBulkExportRuntime();

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
                    ->setPaper('a4', 'portrait')
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
