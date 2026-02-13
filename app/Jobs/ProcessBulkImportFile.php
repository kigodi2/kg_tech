<?php

namespace App\Jobs;

use App\Models\BulkImportFile;
use App\Models\CandidateExamRegistration;
use App\Models\SubjectMarks;
use App\Services\MarkImport\BulkImportOrchestrator;
use App\Services\Results\GradeCalculationService;
use App\Traits\BulkImportHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * ProcessBulkImportFile
 *
 * Processes a single CSV file from a bulk import ZIP.
 * 
 * Responsibilities:
 * - Read CSV file in chunks (no full load to memory)
 * - Validate each row
 * - Insert marks into database
 * - Track success/failure counts
 * - Log errors with row details
 * - Update bulk_import_file status
 * - Cleanup temporary files
 */
class ProcessBulkImportFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, BulkImportHelper;

    public BulkImportFile $importFile;
    public string $filePath;
    public int $maxAttempts = 3;
    public int $timeout = 300;

    public function __construct(BulkImportFile $importFile, string $filePath)
    {
        $this->importFile = $importFile;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job
     */
    public function handle(BulkImportOrchestrator $orchestrator, GradeCalculationService $gradeCalculationService): void
    {
        $this->importFile->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            $this->processFile();

            // After marks are inserted, calculate grades for all affected candidates
            $this->calculateGradesForImportedMarks($gradeCalculationService);

            // Mark as success
            $this->importFile->update(['status' => 'success']);

            Log::info("Bulk import file processed successfully: {$this->importFile->filename}");

        } catch (Exception $e) {
            $this->importFile->update([
                'status' => 'failed',
                'error_log' => $e->getMessage(),
            ]);

            Log::error("Bulk import file processing failed: {$this->importFile->filename}", [
                'error' => $e->getMessage(),
            ]);

            // Rethrow to trigger retry
            throw $e;
        } finally {
            // Mark as complete (success or failed)
            $orchestrator->markFileComplete($this->importFile->id);

            // Cleanup temp file
            @unlink($this->filePath);
        }
    }

    /**
     * Process the CSV file in chunks with batch inserts (OPTIMIZED)
     *
     * CSV format:
     * index_number,sex,papers,paper_1,paper_2,paper_3
     * 
     * Uses batch inserts for performance:
     * - Instead of 1 query per row
     * - Now ~500 rows per query
     * - Reduces from thousands of queries to ~20 queries for 10K rows
     */
    private function processFile(): void
    {
        if (!file_exists($this->filePath)) {
            throw new Exception("CSV file not found: {$this->filePath}");
        }

        $handle = fopen($this->filePath, 'r');

        if (!$handle) {
            throw new Exception("Cannot open CSV file: {$this->filePath}");
        }

        $this->optimizeForBulkImport();

        try {
            $this->startBenchmark('subject_marks');

            // Skip header
            fgetcsv($handle);

            $rowNumber = 1;
            $marksToInsert = [];
            $chunkSize = 500;
            $successCount = 0;
            $failureCount = 0;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                try {
                    // Validate and prepare row for batch insert
                    $markData = $this->prepareRowForInsert($row, $rowNumber);
                    $marksToInsert[] = $markData;
                    $successCount++;

                    // Insert when chunk is full
                    if (count($marksToInsert) >= $chunkSize) {
                        DB::table('subject_marks')->insert($marksToInsert);
                        $marksToInsert = [];
                        $this->garbageCollectEvery($rowNumber, 1000);
                    }

                } catch (Exception $e) {
                    $this->importFile->logError(
                        $rowNumber,
                        $row[0] ?? 'unknown',
                        $e->getMessage()
                    );
                    $failureCount++;
                }
            }

            // Insert remaining marks
            if (!empty($marksToInsert)) {
                DB::table('subject_marks')->insert($marksToInsert);
            }

            // Update final counts
            $this->importFile->update([
                'rows_total' => $rowNumber - 1,
                'rows_success' => $successCount,
                'rows_failed' => $failureCount,
                'completed_at' => now(),
            ]);

            // Log benchmark metrics
            $metrics = $this->endBenchmark('subject_marks');
            $this->logBenchmark(
                "Bulk Import File: {$this->importFile->filename} ({$successCount} marks)",
                $metrics
            );

        } finally {
            fclose($handle);
            $this->restoreFromBulkImport();
        }
    }

    /**
     * Calculate grades for all candidates with imported marks
     * After marks are bulk inserted, immediately calculate GPA, points, and division
     * 
     * Note: We calculate for ALL subjects of the candidate, not just the one imported
     */
    private function calculateGradesForImportedMarks(GradeCalculationService $gradeCalculationService): void
    {
        // Get the exam year to convert exam_year_id to year value
        $examYear = \App\Models\ExamYear::find($this->importFile->bulkImport->exam_year_id);
        if (!$examYear) {
            Log::error("Exam year not found for grade calculation: {$this->importFile->bulkImport->exam_year_id}");
            return;
        }

        // Get all unique candidates affected by this import
        // We fetch candidates that just had marks added
        $candidates = DB::table('subject_marks')
            ->where('subject_id', $this->importFile->subject_id)
            ->where('year', $examYear->year)
            ->where('exam_type_id', $this->importFile->bulkImport->exam_type_id)
            ->distinct()
            ->pluck('candidate_id');

        Log::info("Calculating grades for " . count($candidates) . " candidates after bulk import. Exam year: {$examYear->year}, Exam type: {$this->importFile->bulkImport->exam_type_id}");

        $successCount = 0;
        $failureCount = 0;

        // Calculate grades for each candidate
        // This will process ALL marks for the candidate in the exam year, not just the current subject
        foreach ($candidates as $candidateId) {
            if ($gradeCalculationService->calculateForCandidate(
                $candidateId,
                $this->importFile->bulkImport->exam_year_id,
                $this->importFile->bulkImport->exam_type_id
            )) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        Log::info("Grade calculation completed. Total: " . count($candidates) . ", Success: {$successCount}, Failed: {$failureCount}");
    }

    /**
     * Prepare a CSV row for batch insert (instead of processing directly)
     * 
     * Calculates the average of multiple papers (if applicable) and stores
     * the final mark as marks_obtained in the database.
     */
    private function prepareRowForInsert(array $row, int $rowNumber): array
    {
        // Validate row format
        if (count($row) < 3) {
            throw new Exception("Row has insufficient columns (expected 6)");
        }

        [$indexNumber, $sex, $papers, $paper1, $paper2, $paper3] = array_pad($row, 6, null);

        // Validate index number
        if (empty($indexNumber)) {
            throw new Exception("Index number is empty");
        }

        // Find candidate by index number
        $candidate = DB::table('candidates')
            ->where('candidate_id', trim($indexNumber))
            ->first();

        if (!$candidate) {
            throw new Exception("Candidate not found: {$indexNumber}");
        }

        // Find exam registration
        $registration = CandidateExamRegistration::where('candidate_id', $candidate->id)
            ->where('exam_year_id', $this->importFile->bulkImport->exam_year_id)
            ->first();

        if (!$registration) {
            throw new Exception("Candidate not registered for this exam year: {$indexNumber}");
        }

        // Get subject details to determine how many papers to expect
        $subject = \App\Models\Subject::find($this->importFile->subject_id);
        if (!$subject) {
            throw new Exception("Subject not found: {$this->importFile->subject_id}");
        }

        // Calculate marks_obtained: average of papers if multi-paper subject
        $marksObtained = $this->calculateFinalMarks($paper1, $paper2, $paper3, $subject);

        // Return prepared mark data
        return [
            'candidate_id' => $candidate->id,
            'subject_id' => $this->importFile->subject_id,
            'exam_type_id' => $registration->exam_type_id,
            'exam_year_id' => $this->importFile->bulkImport->exam_year_id,
            'paper_1' => !empty($paper1) ? (int)$paper1 : null,
            'paper_2' => !empty($paper2) ? (int)$paper2 : null,
            'paper_3' => !empty($paper3) ? (int)$paper3 : null,
            'marks_obtained' => $marksObtained,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Calculate final marks based on subject configuration
     * 
     * For multi-paper subjects (e.g., Chemistry with 3 papers):
     * - Average the papers together
     * - Result is stored as marks_obtained
     * 
     * For single-paper subjects:
     * - Use the single paper mark directly
     * 
     * @param mixed $paper1 First paper mark (or only mark for single-paper subjects)
     * @param mixed $paper2 Second paper mark (or practical)
     * @param mixed $paper3 Third paper mark (or project)
     * @param \App\Models\Subject $subject Subject configuration
     * @return float|null Final marks (average if multi-paper, or single mark)
     */
    private function calculateFinalMarks($paper1, $paper2, $paper3, $subject): ?float
    {
        // Collect non-null paper marks
        $paperMarks = [];
        
        if (!empty($paper1)) {
            $paperMarks[] = (float)$paper1;
        }
        if (!empty($paper2)) {
            $paperMarks[] = (float)$paper2;
        }
        if (!empty($paper3)) {
            $paperMarks[] = (float)$paper3;
        }

        // If no marks provided, return null
        if (empty($paperMarks)) {
            return null;
        }

        // Count total expected papers for this subject
        $totalPapers = ($subject->written_papers ?? 1) + 
                      ($subject->has_practical ? 1 : 0) + 
                      ($subject->has_project ? 1 : 0);

        // If subject has multiple papers, calculate average
        // If subject has single paper, use that mark directly
        if ($totalPapers > 1) {
            // Multi-paper subject: average the marks
            return round(array_sum($paperMarks) / count($paperMarks), 2);
        } else {
            // Single paper subject: use the mark as-is
            return $paperMarks[0] ?? null;
        }
    }

}
