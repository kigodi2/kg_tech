<?php

namespace App\Services\MarkImport;

use App\Models\MarkImportBatch;
use App\Models\RawMark;
use App\Models\Subject;
use App\Models\Candidate;
use App\Traits\BulkImportHelper;
use Illuminate\Support\Str;
use Illuminate\Support\LazyCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Exception;

class MarkImportService
{
    use BulkImportHelper;

    private MarkValidationService $validationService;
    private CsvIntegrityService $integrityService;
    private MarkRowLockingService $lockingService;

    public function __construct(
        MarkValidationService $validationService,
        CsvIntegrityService $integrityService,
        MarkRowLockingService $lockingService
    ) {
        $this->validationService = $validationService;
        $this->integrityService = $integrityService;
        $this->lockingService = $lockingService;
    }

    /**
     * Create a new mark import batch
     * 
     * IMPORTANT: Combination is NOT an input parameter.
     * Combination is derived from candidate registration during validation.
     */
    public function createBatch(
        int $examYear,
        int $schoolId,
        int $subjectId,
        string $importedBy
    ): MarkImportBatch {
        $subject = Subject::findOrFail($subjectId);
        $school = \App\Models\School::findOrFail($schoolId);

        $batch = MarkImportBatch::create([
            'batch_code' => $this->generateBatchCode($schoolId, $subjectId, $examYear),
            'exam_year' => $examYear,
            'school_id' => $schoolId,
            'region_id' => $school->region_id,
            'district_id' => $school->district_id,
            'subject_id' => $subjectId,
            'exam_type_id' => $subject->exam_type_id,
            'status' => MarkImportBatch::STATUS_DRAFT,
            'imported_by' => $importedBy,
            'imported_at' => now(),
        ]);

        return $batch;
    }

    /**
     * Process CSV upload and create raw marks (OPTIMIZED with LazyCollection + Batch Insert)
     * 
     * Includes integrity verification before processing.
     * Uses LazyCollection to stream file and batch inserts for performance.
     */
    public function processCSVUpload(
        MarkImportBatch $batch,
        UploadedFile $file,
        int $examYear,
        int $schoolId,
        int $subjectId
    ): array {
        $errors = [];
        $successCount = 0;

        try {
            // Verify CSV integrity first
            $integrityResult = $this->integrityService->verifyUploadedCSV(
                $batch,
                $file,
                $examYear,
                $schoolId,
                $subjectId
            );

            if (!$integrityResult['valid']) {
                return [
                    'success' => false,
                    'error' => $integrityResult['error'],
                ];
            }

            $this->startBenchmark('raw_marks');
            $subject = $batch->subject;
            
            // Get paper structure for this subject
            $paperStructure = $this->getPaperStructure($subject);

            // Clear any previous raw marks for this batch
            $batch->rawMarks()->delete();

            // Use LazyCollection to stream CSV file (memory efficient)
            $filePath = $file->getRealPath();
            $rowNumber = 0;
            $rawMarksToInsert = [];
            $candidateIds = [];
            $batchSize = 1000;

            LazyCollection::make(function () use ($filePath) {
                $handle = fopen($filePath, 'r');
                fgetcsv($handle); // Skip header
                
                while (($line = fgetcsv($handle)) !== false) {
                    yield $line;
                }
                fclose($handle);
            })
            ->each(function ($row) use (
                $batch,
                $paperStructure,
                &$rawMarksToInsert,
                &$candidateIds,
                &$rowNumber,
                &$successCount,
                &$errors,
                $batchSize
            ) {
                $rowNumber++;

                try {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        return;
                    }

                    $indexNumber = trim($row[0] ?? '');
                    
                    if (empty($indexNumber)) {
                        return;
                    }

                    // Extract marks based on paper structure
                    $marks = $this->extractMarks($row, $paperStructure);

                    // Prepare raw mark record for batch insert
                    $rawMarksToInsert[] = [
                        'mark_import_batch_id' => $batch->id,
                        'subject_id' => $batch->subject_id,
                        'row_number' => $rowNumber,
                        'candidate_index_number' => $indexNumber,
                        'full_name' => trim($row[1] ?? ''),
                        'paper_1_marks' => $marks['paper_1'] ?? null,
                        'paper_2_marks' => $marks['paper_2'] ?? null,
                        'paper_3_marks' => $marks['paper_3'] ?? null,
                        'practical_marks' => $marks['practical'] ?? null,
                        'project_marks' => $marks['project'] ?? null,
                        'raw_data' => json_encode($row),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $candidateIds[$indexNumber] = true;
                    $successCount++;

                    // Insert in batches of 1000
                    if (count($rawMarksToInsert) >= $batchSize) {
                        DB::table('raw_marks')->insert($rawMarksToInsert);
                        $rawMarksToInsert = [];
                        $this->garbageCollectEvery($rowNumber, 1000);
                    }
                } catch (Exception $e) {
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                }
            });

            // Insert remaining records
            if (!empty($rawMarksToInsert)) {
                DB::table('raw_marks')->insert($rawMarksToInsert);
            }

            // Update candidate IDs for all raw marks (batch update)
            if (!empty($candidateIds)) {
                $this->linkCandidatesToRawMarks($batch, array_keys($candidateIds));
            }

            $batch->update([
                'total_records' => $successCount,
            ]);

            // Log benchmark
            $metrics = $this->endBenchmark('raw_marks');
            $this->logBenchmark("Process CSV Upload ({$successCount} records)", $metrics);

            return [
                'success' => true,
                'imported_records' => $successCount,
                'errors' => $errors,
                'metrics' => $metrics,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'errors' => $errors,
            ];
        }
    }

    /**
     * Link candidates to raw marks (batch update)
     */
    private function linkCandidatesToRawMarks(MarkImportBatch $batch, array $indexNumbers): void
    {
        // Get all candidate IDs for the index numbers
        $candidateMap = Candidate::whereIn('candidate_id', $indexNumbers)
            ->pluck('id', 'candidate_id')
            ->toArray();

        // Update raw marks with candidate IDs (batch update)
        foreach ($candidateMap as $indexNumber => $candidateId) {
            DB::table('raw_marks')
                ->where('mark_import_batch_id', $batch->id)
                ->where('candidate_index_number', $indexNumber)
                ->update(['candidate_id' => $candidateId]);
        }
    }

    /**
     * Validate all raw marks in a batch
     */
    public function validateBatch(MarkImportBatch $batch): array
    {
        $rawMarks = $batch->rawMarks()->get();
        $validRecords = 0;
        $errorRecords = 0;
        $allErrors = [];

        foreach ($rawMarks as $rawMark) {
            $errors = $this->validationService->validateRawMark($rawMark, $batch);

            if (!empty($errors)) {
                $rawMark->update([
                    'error_messages' => $errors,
                    'has_errors' => true,
                ]);
                $errorRecords++;
                $allErrors = array_merge($allErrors, $errors);
            } else {
                $rawMark->clearErrors();
                $validRecords++;
            }
        }

        $batch->update([
            'valid_records' => $validRecords,
            'error_records' => $errorRecords,
        ]);

        return [
            'valid' => $validRecords,
            'invalid' => $errorRecords,
            'total' => $batch->total_records,
            'errors' => $allErrors,
        ];
    }

    /**
     * Generate batch code
     */
    private function generateBatchCode(int $schoolId, int $subjectId, int $examYear): string
    {
        $timestamp = now()->format('YmdHis'); // Include seconds for uniqueness
        $randomSuffix = Str::random(6); // Add random suffix for extra uniqueness
        return "BATCH-{$schoolId}-{$subjectId}-{$examYear}-{$timestamp}-{$randomSuffix}";
    }

    /**
     * Parse CSV file
     */
    private function parseCSV(UploadedFile $file): array
    {
        $rows = [];
        $handle = fopen($file->getRealPath(), 'r');

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Get paper structure for subject
     */
    private function getPaperStructure(Subject $subject): array
    {
        return [
            'written_papers' => $subject->written_papers ?? 2,
            'has_practical' => $subject->has_practical ?? false,
            'has_project' => $subject->has_project ?? false,
        ];
    }

    /**
     * Extract marks from CSV row based on paper structure
     */
    private function extractMarks(array $row, array $paperStructure): array
    {
        $marks = [];
        $columnIndex = 2; // Start after index number and name

        // Extract written paper marks
        for ($i = 1; $i <= ($paperStructure['written_papers'] ?? 2); $i++) {
            $marks["paper_$i"] = !empty($row[$columnIndex]) ? (float) $row[$columnIndex] : null;
            $columnIndex++;
        }

        // Extract practical marks
        if ($paperStructure['has_practical']) {
            $marks['practical'] = !empty($row[$columnIndex]) ? (float) $row[$columnIndex] : null;
            $columnIndex++;
        }

        // Extract project marks
        if ($paperStructure['has_project']) {
            $marks['project'] = !empty($row[$columnIndex]) ? (float) $row[$columnIndex] : null;
        }

        return $marks;
    }
}
