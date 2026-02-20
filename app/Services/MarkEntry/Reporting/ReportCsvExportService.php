<?php

namespace App\Services\MarkEntry\Reporting;

use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\MarkImportBatch;
use App\Models\RawMark;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class ReportCsvExportService
{
    const TEMP_DIR = 'storage/app/temp/report-exports';

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
     * Export marks for a single school + subject as CSV
     */
    public function exportSchoolSubjectCsv(int $schoolId, int $subjectId, int $examYearId, string $mode = 'approved'): array
    {
        $school = School::findOrFail($schoolId);
        $subject = Subject::findOrFail($subjectId);
        $examYear = ExamYear::findOrFail($examYearId);

        $marks = $this->getMarksForSubject($schoolId, $subjectId, $examYearId, $mode);

        if ($marks->isEmpty()) {
            throw new \Exception("No marks found for {$subject->code} at {$school->name} in {$examYear->year_label}");
        }

        $this->ensureTempDirectory();

        $filename = sprintf('%s_%s_%s_marks.csv', $school->code, $subject->code, $examYear->year_label);
        $filePath = base_path(self::TEMP_DIR . '/' . $filename);

        $this->writeCsvFile($filePath, $marks, $subject, $school, $examYear);

        return [
            'file_path' => $filePath,
            'filename' => $filename,
            'total_records' => $marks->count(),
        ];
    }

    /**
     * Export all subjects for a school as ZIP
     */
    public function exportSchoolZip(int $schoolId, int $examYearId, string $mode = 'approved'): array
    {
        $school = School::findOrFail($schoolId);
        $examYear = ExamYear::findOrFail($examYearId);

        $subjects = $this->getSubjectsWithMarks($schoolId, $examYearId, $mode);

        if ($subjects->isEmpty()) {
            throw new \Exception("No subjects with marks found for {$school->name} in {$examYear->year_label}");
        }

        $this->ensureTempDirectory();

        $zipFilename = sprintf('%s_%s_all_marks.zip', $school->code, $examYear->year_label);
        $zipPath = base_path(self::TEMP_DIR . '/' . $zipFilename);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Failed to create ZIP file');
        }

        $totalRecords = 0;
        $csvFiles = [];

        foreach ($subjects as $subject) {
            $marks = $this->getMarksForSubject($schoolId, $subject->id, $examYearId, $mode);
            if ($marks->isEmpty()) continue;

            $csvFilename = sprintf('%s_%s_%s_marks.csv', $school->code, $subject->code, $examYear->year_label);
            $csvPath = base_path(self::TEMP_DIR . '/' . $csvFilename);

            $this->writeCsvFile($csvPath, $marks, $subject, $school, $examYear);
            $zip->addFile($csvPath, $csvFilename);
            $csvFiles[] = $csvPath;
            $totalRecords += $marks->count();
        }

        $zip->close();

        // Cleanup temp CSVs
        foreach ($csvFiles as $f) {
            @unlink($f);
        }

        return [
            'file_path' => $zipPath,
            'filename' => $zipFilename,
            'total_subjects' => $subjects->count(),
            'total_records' => $totalRecords,
        ];
    }

    /**
     * Export all schools in a district as ZIP
     */
    public function exportDistrictZip(int $districtId, int $examYearId, string $mode = 'approved'): array
    {
        $examYear = ExamYear::findOrFail($examYearId);
        $yearValue = $this->resolveYearValue($examYearId);
        $statuses = $this->allowedStatuses($mode);

        $schools = School::where('district_id', $districtId)
            ->whereHas('markImportBatches', function ($q) use ($yearValue, $statuses) {
                $q->where('exam_year', $yearValue)
                  ->whereIn('status', $statuses);
            })
            ->orderBy('code')
            ->get();

        if ($schools->isEmpty()) {
            throw new \Exception("No schools with marks found in this district for {$examYear->year_label}");
        }

        $this->ensureTempDirectory();

        $zipFilename = sprintf('district_%s_%s_marks.zip', $districtId, $examYear->year_label);
        $zipPath = base_path(self::TEMP_DIR . '/' . $zipFilename);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Failed to create ZIP file');
        }

        $totalRecords = 0;
        $csvFiles = [];

        foreach ($schools as $school) {
            $subjects = $this->getSubjectsWithMarks($school->id, $examYearId, $mode);
            foreach ($subjects as $subject) {
                $marks = $this->getMarksForSubject($school->id, $subject->id, $examYearId, $mode);
                if ($marks->isEmpty()) continue;

                $csvFilename = sprintf('%s/%s_%s_%s_marks.csv', $school->code, $school->code, $subject->code, $examYear->year_label);
                $csvPath = base_path(self::TEMP_DIR . '/' . str_replace('/', '_', $csvFilename));

                $this->writeCsvFile($csvPath, $marks, $subject, $school, $examYear);
                $zip->addFile($csvPath, $csvFilename);
                $csvFiles[] = $csvPath;
                $totalRecords += $marks->count();
            }
        }

        $zip->close();

        foreach ($csvFiles as $f) {
            @unlink($f);
        }

        return [
            'file_path' => $zipPath,
            'filename' => $zipFilename,
            'total_schools' => $schools->count(),
            'total_records' => $totalRecords,
        ];
    }

    /**
     * Get marks for a specific subject at a school
     */
    private function getMarksForSubject(int $schoolId, int $subjectId, int $examYearId, string $mode = 'approved'): Collection
    {
        $yearValue = $this->resolveYearValue($examYearId);
        $statuses = $this->allowedStatuses($mode);

        return RawMark::query()
            ->whereHas('batch', function ($q) use ($schoolId, $subjectId, $yearValue, $statuses) {
                $q->where('school_id', $schoolId)
                  ->where('subject_id', $subjectId)
                  ->where('exam_year', $yearValue)
                  ->whereIn('status', $statuses);
            })
            ->where('has_errors', false)
            ->with('candidate:id,candidate_id,full_name,gender,combination')
            ->orderBy('candidate_index_number')
            ->get();
    }

    /**
     * Get subjects with marks for a school
     */
    private function getSubjectsWithMarks(int $schoolId, int $examYearId, string $mode = 'approved'): Collection
    {
        $yearValue = $this->resolveYearValue($examYearId);
        $statuses = $this->allowedStatuses($mode);

        return Subject::query()
            ->select('subjects.id', 'subjects.code', 'subjects.name',
                     'subjects.written_papers', 'subjects.has_practical', 'subjects.has_project')
            ->join('mark_import_batches', 'subjects.id', '=', 'mark_import_batches.subject_id')
            ->where('mark_import_batches.school_id', $schoolId)
            ->where('mark_import_batches.exam_year', $yearValue)
            ->whereIn('mark_import_batches.status', $statuses)
            ->distinct()
            ->orderBy('subjects.code')
            ->get();
    }

    /**
     * Write marks data to a CSV file
     */
    private function writeCsvFile(string $filePath, Collection $marks, Subject $subject, School $school, ExamYear $examYear): void
    {
        $handle = fopen($filePath, 'w');

        // Build headers
        $headers = ['exam_year', 'centre_no', 'school_name', 'candidate_index_no', 'candidate_name', 'gender', 'combination', 'subject_code'];

        $writtenPapers = $subject->written_papers ?? 2;
        for ($i = 1; $i <= $writtenPapers; $i++) {
            $headers[] = "paper_{$i}";
        }
        if ($subject->has_practical) $headers[] = 'practical';
        if ($subject->has_project) $headers[] = 'project';
        $headers[] = 'total';
        $headers[] = 'remarks';

        fputcsv($handle, $headers);

        foreach ($marks as $mark) {
            $row = [
                $examYear->year_label,
                $school->code,
                $school->name,
                $mark->candidate_index_number ?? $mark->candidate?->candidate_id ?? '',
                $mark->full_name ?? $mark->candidate?->full_name ?? '',
                strtoupper(substr($mark->candidate?->gender ?? '', 0, 1)),
                $mark->candidate?->combination ?? '',
                $subject->code,
            ];

            $total = 0;
            for ($i = 1; $i <= $writtenPapers; $i++) {
                $val = $mark->{"paper_{$i}_marks"};
                $row[] = $val ?? '';
                $total += (float)($val ?? 0);
            }
            if ($subject->has_practical) {
                $row[] = $mark->practical_marks ?? '';
                $total += (float)($mark->practical_marks ?? 0);
            }
            if ($subject->has_project) {
                $row[] = $mark->project_marks ?? '';
                $total += (float)($mark->project_marks ?? 0);
            }
            $row[] = $total;
            $row[] = '';

            fputcsv($handle, $row);
        }

        fclose($handle);
    }

    private function ensureTempDirectory(): void
    {
        $dir = base_path(self::TEMP_DIR);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
