<?php

namespace App\Services\MarkImport;

use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use SplFileObject;

/**
 * BulkCsvExportService
 *
 * Generates bulk CSV exports per subject for a school + exam year,
 * bundles them into a ZIP with manifest & checksums.
 *
 * Design principles:
 * - Chunk queries for large datasets (1000+ candidates)
 * - Stream CSV rows (no full dataset in memory)
 * - Generate CSVs one subject at a time
 * - Write ZIP entries incrementally
 * - Compute checksums during CSV generation
 * - Enforce exam_year isolation
 * - Include manifest for audit & integrity verification
 */
class BulkCsvExportService
{
    const CHUNK_SIZE = 500;
    const TEMP_DIR = 'storage/app/temp/csv-exports';

    /**
     * Generate ZIP bundle with per-subject CSVs and manifest
     *
     * @param int $schoolId
     * @param int $examYearId
     * @return array Contains ['zip_path', 'filename', 'manifest']
     * @throws \Exception if validation fails
     */
    public function generateBulkExport(int $schoolId, int $examYearId): array
    {
        // Validate inputs
        $school = School::findOrFail($schoolId);
        $examYear = ExamYear::findOrFail($examYearId);

        // Ensure year is not locked
        $this->validateYearNotLocked($examYearId);

        // Get subjects with registered candidates
        $subjects = $this->getSubjectsWithCandidates($schoolId, $examYearId);

        Log::info('BulkCsvExportService: subjects found', [
            'school_id' => $schoolId,
            'exam_year_id' => $examYearId,
            'subject_count' => $subjects->count(),
            'subjects' => $subjects->pluck('code')->toArray(),
        ]);

        if ($subjects->isEmpty()) {
            throw new \Exception(
                "No subjects with registered candidates found for {$examYear->year_label} at {$school->name}"
            );
        }

        // Ensure temp directory exists
        $this->ensureTempDirectory();

        // Create ZIP file
        $zipPath = $this->generateZipPath($school, $examYear);
        $zipArchive = new ZipArchive();

        if ($zipArchive->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Failed to create ZIP file: {$zipPath}");
        }

        $manifest = [
            'exam_year' => (string)$examYear->year_label,
            'school_code' => $school->code,
            'school_name' => $school->name,
            'scope' => [
                'type' => 'school',
            ],
            'files' => [],
            // Additional metadata for audit trail
            '_metadata' => [
                'system' => 'IRMS',
                'exam_type' => 'ACSEE',
                'generated_at' => now()->toIso8601String(),
                'generated_by' => auth()->id() ?? 'system',
            ],
        ];

        $totalCandidates = 0;
        $csvFilesToCleanup = [];

        // Generate CSV per subject and add to ZIP
        foreach ($subjects as $subject) {
            try {
                $csvPath = $this->generateSubjectCsv($school, $subject, $examYear, $examYearId);
                $csvFilename = $this->getCsvFilename($subject, $examYear, $school);

                Log::info('Generated CSV', [
                    'subject_code' => $subject->code,
                    'csv_path' => $csvPath,
                    'csv_filename' => $csvFilename,
                    'file_exists' => file_exists($csvPath),
                    'file_size' => file_exists($csvPath) ? filesize($csvPath) : 0,
                ]);

                // Add CSV to ZIP
                if (!file_exists($csvPath)) {
                    throw new \Exception("CSV file not found before adding to ZIP: {$csvPath}");
                }
                if (!$zipArchive->addFile($csvPath, $csvFilename)) {
                    throw new \Exception("Failed to add {$csvFilename} to ZIP archive");
                }

                // Keep track of CSV files for cleanup after ZIP is closed
                $csvFilesToCleanup[] = $csvPath;

            } catch (\Exception $e) {
                Log::error('Error generating CSV for subject', [
                    'subject_code' => $subject->code,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }

            // Compute checksum
            $checksum = $this->computeChecksum($csvPath);

            // Count candidates for this subject (for audit)
            $subjectCandidateCount = $this->countCandidatesForSubject($schoolId, $examYearId, $subject->id);
            $totalCandidates += $subjectCandidateCount;

            // Add to manifest
            $manifest['files'][] = [
                'filename' => $csvFilename,
                'subject_code' => $subject->code,
                'subject_name' => $subject->name,
                'checksum' => 'sha256:' . $checksum,
                'candidate_count' => $subjectCandidateCount,
            ];
        }

        // Add manifest.json to ZIP
        $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!$zipArchive->addFromString('manifest.json', $manifestJson)) {
            throw new \Exception("Failed to add manifest.json to ZIP archive");
        }
        Log::info('Added manifest.json to ZIP', ['manifest' => $manifest]);

        if (!$zipArchive->close()) {
            throw new \Exception("Failed to close ZIP archive. Check file permissions and disk space.");
        }

        // Clean up temporary CSV files after ZIP is successfully closed
        foreach ($csvFilesToCleanup as $csvPath) {
            @unlink($csvPath);
        }

        // Log audit trail
        $this->logExport(
            user_id: auth()->id() ?? 0,
            school_id: $schoolId,
            exam_year_id: $examYearId,
            num_subjects: count($manifest['files']),
            num_candidates: $totalCandidates,
            zip_filename: basename($zipPath)
        );

        return [
            'zip_path' => $zipPath,
            'filename' => basename($zipPath),
            'manifest' => $manifest,
        ];
    }

    /**
     * Generate CSV for a specific subject
     *
     * Uses chunked queries and streaming to handle large candidate lists efficiently.
     *
     * @param School $school
     * @param Subject $subject
     * @param ExamYear $examYear
     * @param int $examYearId
     * @return string Path to temporary CSV file
     */
    private function generateSubjectCsv(
        School $school,
        Subject $subject,
        ExamYear $examYear,
        int $examYearId
    ): string {
        $csvPath = storage_path(
            'app/temp/' . uniqid('csv_') . '.csv'
        );

        $csv = fopen($csvPath, 'w');

        // Generate headers dynamically based on subject configuration
        $headers = ['index_number', 'sex'];
        
        $papersCount = $subject->written_papers ?? 2;
        for ($i = 1; $i <= $papersCount; $i++) {
            $headers[] = "paper_p{$i}";
        }
        
        if ($subject->has_practical) {
            $headers[] = 'practical';
        }
        
        if ($subject->has_project) {
            $headers[] = 'project';
        }

        // Write header
        fputcsv($csv, $headers);

        // Get ACSEE exam type
        $acsee = ExamType::where('code', 'ACSEE')->firstOrFail();

        // Fetch candidates in chunks
        $query = CandidateExamRegistration::query()
            ->where('exam_year_id', $examYearId)
            ->where('exam_type_id', $acsee->id)
            ->whereHas('candidate', function ($q) use ($school) {
                $q->where('school_id', $school->id);
            })
            ->whereHas('candidate.combination.subjects', function ($q) use ($subject) {
                $q->where('subject_id', $subject->id);
            })
            ->with('candidate:id,candidate_id,gender')
            ->orderBy('candidate_id');

        // Process in chunks to avoid memory issues
        $query->chunk(self::CHUNK_SIZE, function ($registrations) use (
            $csv,
            $papersCount,
            $subject
        ) {
            foreach ($registrations as $registration) {
                $row = [
                    $registration->candidate->candidate_id,
                    $registration->candidate->gender,
                ];

                // Add empty cells for papers
                for ($i = 1; $i <= $papersCount; $i++) {
                    $row[] = '';
                }

                // Add practical cell if applicable
                if ($subject->has_practical) {
                    $row[] = '';
                }

                // Add project cell if applicable
                if ($subject->has_project) {
                    $row[] = '';
                }

                fputcsv($csv, $row);
            }
        });

        fclose($csv);

        return $csvPath;
    }

    /**
     * Get subjects with registered candidates for school + year
     *
     * Reuses SubjectFilterService logic for consistency.
     *
     * @param int $schoolId
     * @param int $examYearId
     * @return \Illuminate\Support\Collection
     */
    public function getSubjectsWithCandidates(int $schoolId, int $examYearId): \Illuminate\Support\Collection
    {
        $acsee = ExamType::where('code', 'ACSEE')->firstOrFail();

        return Subject::query()
            ->distinct()
            ->select('subjects.id', 'subjects.code', 'subjects.name', 'subjects.written_papers')
            ->join('combination_subject', 'subjects.id', '=', 'combination_subject.subject_id')
            ->join('combinations', 'combination_subject.combination_id', '=', 'combinations.id')
            ->join('candidates', 'combinations.code', '=', 'candidates.combination')
            ->where('candidates.school_id', '=', $schoolId)
            ->where('candidates.exam_type', '=', $acsee->code)
            ->where('subjects.exam_type_id', '=', $acsee->id)
            ->where('subjects.is_active', '=', true)
            ->orderBy('subjects.code')
            ->get();
    }

    /**
     * Count candidates for a specific subject
     *
     * @param int $schoolId
     * @param int $examYearId
     * @param int $subjectId
     * @return int
     */
    private function countCandidatesForSubject(int $schoolId, int $examYearId, int $subjectId): int
    {
        $acsee = ExamType::where('code', 'ACSEE')->firstOrFail();

        return CandidateExamRegistration::query()
            ->where('exam_year_id', $examYearId)
            ->where('exam_type_id', $acsee->id)
            ->whereHas('candidate', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })
            ->whereHas('candidate.combination.subjects', function ($q) use ($subjectId) {
                $q->where('subject_id', $subjectId);
            })
            ->count();
    }

    /**
     * Validate exam year is not locked
     *
     * @param int $examYearId
     * @throws \Exception
     */
    private function validateYearNotLocked(int $examYearId): void
    {
        $examYear = ExamYear::findOrFail($examYearId);

        if ($examYear->is_locked) {
            throw new \Exception("Exam year {$examYear->year_label} is locked. Bulk export is disabled.");
        }
    }

    /**
     * Compute SHA-256 checksum for a file
     *
     * @param string $filePath
     * @return string
     */
    private function computeChecksum(string $filePath): string
    {
        return hash_file('sha256', $filePath);
    }

    /**
     * Generate ZIP file path
     *
     * @param School $school
     * @param ExamYear $examYear
     * @return string
     */
    private function generateZipPath(School $school, ExamYear $examYear): string
    {
        $filename = sprintf(
            '%s_ACSEE_%s_MarkTemplate.zip',
            str_replace(' ', '_', $school->name),
            $examYear->year_label
        );

        return storage_path('app/temp/' . $filename);
    }

    /**
     * Generate CSV filename for subject
     *
     * @param Subject $subject
     * @param ExamYear $examYear
     * @param School $school
     * @return string
     */
    private function getCsvFilename(Subject $subject, ExamYear $examYear, School $school): string
    {
        // Match scoresheet naming pattern: {subject_code}_{subject_name}.csv
        return sprintf(
            '%s_%s.csv',
            $subject->code,
            $subject->name
        );
    }

    /**
     * Ensure temporary directory exists
     *
     * @return void
     */
    private function ensureTempDirectory(): void
    {
        $tempDir = storage_path('app/temp');

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
    }

    /**
     * Log bulk export action for audit trail
     *
     * @param int $user_id
     * @param int $school_id
     * @param int $exam_year_id
     * @param int $num_subjects
     * @param int $num_candidates
     * @param string $zip_filename
     * @return void
     */
    private function logExport(
        int $user_id,
        int $school_id,
        int $exam_year_id,
        int $num_subjects,
        int $num_candidates,
        string $zip_filename
    ): void {
        Log::info('Bulk CSV Export', [
            'action' => 'bulk_csv_export',
            'user_id' => $user_id,
            'role' => auth()->user()->role ?? 'unknown',
            'school_id' => $school_id,
            'exam_year_id' => $exam_year_id,
            'num_subjects' => $num_subjects,
            'num_candidates' => $num_candidates,
            'zip_filename' => $zip_filename,
            'timestamp' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
        ]);
    }
}
