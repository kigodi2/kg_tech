<?php

namespace App\Jobs;

use App\Models\BulkImport;
use App\Models\BulkImportFile;
use App\Models\School;
use App\Models\Subject;
use App\Services\MarkImport\DistrictBulkImportOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessBulkImportSchool
 *
 * Processes one school from a district bulk import.
 * - Atomic school-level processing
 * - Failure isolation (school failure doesn't affect others)
 * - Per-subject chunked imports
 * - Row-level locking
 */
class ProcessBulkImportSchool implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout per school
    public $tries = 3;

    protected int $bulkImportId;
    protected int $schoolId;
    protected array $subjects;
    protected string $extractPath;

    public function __construct(
        int $bulkImportId,
        int $schoolId,
        array $subjects,
        string $extractPath
    ) {
        $this->bulkImportId = $bulkImportId;
        $this->schoolId = $schoolId;
        $this->subjects = $subjects;
        $this->extractPath = $extractPath;
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        $bulkImport = BulkImport::findOrFail($this->bulkImportId);
        $school = School::findOrFail($this->schoolId);
        $orchestrator = app(DistrictBulkImportOrchestrator::class);

        try {
            Log::info("Starting school import", [
                'bulk_import_id' => $this->bulkImportId,
                'school_id' => $this->schoolId,
                'school_code' => $school->code,
                'total_subjects' => count($this->subjects),
            ]);

            // Mark school as processing
            $bulkImport->schools()->updateExistingPivot($this->schoolId, [
                'status' => 'processing',
                'started_at' => now(),
            ]);

            $processedSubjects = 0;
            $successfulCandidates = 0;
            $failedCandidates = 0;
            $subjectErrors = [];
            $hasPartialFailure = false;

            // Process each subject for this school
            foreach ($this->subjects as $subjectData) {
                $subjectCode = $subjectData['code'] ?? null;

                if (!$subjectCode) {
                    Log::warning("Subject missing code", ['school_id' => $this->schoolId]);
                    $subjectErrors[] = "Subject missing code";
                    $hasPartialFailure = true;
                    continue;
                }

                try {
                    $result = $this->processSubject($subjectCode, $subjectData);

                    if ($result['success']) {
                        $successfulCandidates += $result['successful'] ?? 0;
                        $processedSubjects++;
                    } else {
                        $failedCandidates += $result['failed'] ?? 0;
                        $subjectErrors[] = "{$subjectCode}: {$result['error']}";
                        $hasPartialFailure = true;
                    }
                } catch (\Exception $e) {
                    Log::error("Subject processing failed", [
                        'subject_code' => $subjectCode,
                        'error' => $e->getMessage(),
                    ]);
                    $subjectErrors[] = "{$subjectCode}: {$e->getMessage()}";
                    $hasPartialFailure = true;
                }
            }

            // Determine final status
            $finalStatus = 'success';
            if ($hasPartialFailure) {
                $finalStatus = $processedSubjects > 0 ? 'partial' : 'failed';
            }

            // Mark school as complete
            $orchestrator->markSchoolComplete(
                $this->bulkImportId,
                $this->schoolId,
                $finalStatus,
                [
                    'processed_subjects' => $processedSubjects,
                    'successful_candidates' => $successfulCandidates,
                    'failed_candidates' => $failedCandidates,
                    'error_summary' => implode('; ', $subjectErrors),
                ]
            );

            Log::info("School import completed", [
                'bulk_import_id' => $this->bulkImportId,
                'school_id' => $this->schoolId,
                'status' => $finalStatus,
                'processed_subjects' => $processedSubjects,
            ]);

        } catch (\Exception $e) {
            Log::error("School import failed critically", [
                'bulk_import_id' => $this->bulkImportId,
                'school_id' => $this->schoolId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mark as failed
            app(DistrictBulkImportOrchestrator::class)->markSchoolComplete(
                $this->bulkImportId,
                $this->schoolId,
                'failed',
                ['error_summary' => $e->getMessage()]
            );

            throw $e;
        }
    }

    /**
     * Process a single subject for the school
     *
     * @param string $subjectCode
     * @param array $subjectData
     * @return array {success: bool, successful?: int, failed?: int, error?: string}
     */
    protected function processSubject(string $subjectCode, array $subjectData): array
    {
        // Find CSV file for this subject in extracted ZIP
        $csvPath = $this->findSubjectCsvFile($subjectCode);

        if (!$csvPath) {
            return [
                'success' => false,
                'error' => "CSV file not found for subject {$subjectCode}",
                'failed' => $subjectData['candidates'] ?? 0,
            ];
        }

        // Dispatch ProcessBulkImportFile job for this subject
        $school = School::find($this->schoolId);
        $subject = Subject::where('code', $subjectCode)->first();

        $importFile = BulkImportFile::create([
            'bulk_import_id' => $this->bulkImportId,
            'subject_id' => $subject?->id,
            'subject_code' => $subjectCode,
            'filename' => basename($csvPath),
            'status' => 'pending',
            'file_hash' => hash_file('sha256', $csvPath),
        ]);

        // Process the file
        try {
            ProcessBulkImportFile::dispatchSync($importFile, $csvPath);

            return [
                'success' => $importFile->status === 'success',
                'successful' => $importFile->rows_success ?? 0,
                'failed' => $importFile->rows_failed ?? 0,
                'error' => $importFile->status === 'failed' ? 'File processing failed' : null,
            ];

        } catch (\Exception $e) {
            $importFile->update(['status' => 'failed']);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'failed' => $subjectData['candidates'] ?? 0,
            ];
        }
    }

    /**
     * Find CSV file for subject in extracted directory
     *
     * @param string $subjectCode
     * @return string|null
     */
    protected function findSubjectCsvFile(string $subjectCode): ?string
    {
        $school = School::find($this->schoolId);
        $schoolCode = $school->code;

        // Expected pattern: SCHOOL_CODE/*.csv matching subject
        $schoolDir = $this->extractPath . '/' . $schoolCode . '*';
        $schoolDirs = glob($schoolDir, GLOB_ONLYDIR);

        if (empty($schoolDirs)) {
            return null;
        }

        $schoolPath = $schoolDirs[0];

        // Look for CSV file with subject code
        $csvPattern = $schoolPath . '/' . $subjectCode . '*.csv';
        $csvFiles = glob($csvPattern);

        return $csvFiles[0] ?? null;
    }
}
