<?php

namespace App\Jobs;

use App\Models\Candidate;
use App\Models\School;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Models\CandidateExamRegistration;
use App\Models\CandidateSubjectSelection;
use App\Models\Subject;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Exception;

/**
 * ProcessCandidateBulkImport
 *
 * Asynchronously processes bulk candidate import from CSV file.
 * 
 * Responsibilities:
 * - Read CSV file in chunks (memory-efficient)
 * - Validate each candidate record
 * - Batch insert candidates (100 per batch)
 * - Register candidates for ACSEE if needed
 * - Track success/failure counts
 * - Log errors with row details
 * - Handle timeout prevention with proper chunking
 */
class ProcessCandidateBulkImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $filePath;
    public ?string $examYear;
    public ?string $examType;
    public string $mode;
    public int $maxAttempts = 3;
    public int $timeout = 300; // 5 minutes

    public function __construct(string $filePath, ?string $examYear = null, ?string $examType = null, string $mode = 'skip')
    {
        $this->filePath = $filePath;
        $this->examYear = $examYear;
        $this->examType = $examType;
        $this->mode = $mode;
    }

    /**
     * Execute the bulk import job
     */
    public function handle(): void
    {
        try {
            Log::info("Starting candidate bulk import", [
                'file' => $this->filePath,
                'examYear' => $this->examYear,
                'examType' => $this->examType,
            ]);

            $this->processCandidateImport();

            Log::info("Candidate bulk import completed successfully", [
                'file' => $this->filePath
            ]);
        } catch (Exception $e) {
            Log::error("Candidate bulk import failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        } finally {
            // Cleanup temp file
            @unlink($this->filePath);
        }
    }

    /**
     * Process candidate CSV in optimized batches
     */
    private function processCandidateImport(): void
    {
        if (!file_exists($this->filePath)) {
            throw new Exception("CSV file not found: {$this->filePath}");
        }

        $handle = fopen($this->filePath, 'r');
        if (!$handle) {
            throw new Exception("Cannot open CSV file: {$this->filePath}");
        }

        try {
            // Preload lookups to avoid N+1 queries
            $schools = School::all()->keyBy('code');
            $acseeType = ExamType::where('code', 'ACSEE')->first();
            $resolvedExamYear = $this->resolveExamYear($this->examYear);
            $existingCandidateIds = Candidate::pluck('id', 'candidate_id');

            // Read header
            $header = fgetcsv($handle);
            if (!$header) {
                throw new Exception("CSV file is empty");
            }

            $header = array_map('strtolower', $header);
            $header = array_map('trim', $header);

            $rowNumber = 0;
            $successCount = 0;
            $skipCount = 0;
            $updateCount = 0;
            $errorCount = 0;
            $batch = [];
            $chunkSize = 100;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    $record = $this->mapRowToRecord($row, $header);

                    // Validate record
                    $errors = [];
                    $this->validateRecord($record, $errors);

                    if (!empty($errors)) {
                        Log::warning("Row $rowNumber validation errors: " . implode('; ', $errors));
                        $errorCount++;
                        continue;
                    }

                    // Check for duplicates
                    $candidateExists = isset($existingCandidateIds[$record['candidate_id']]);

                    if ($candidateExists) {
                        if ($this->mode === 'skip') {
                            $skipCount++;
                            continue;
                        } elseif ($this->mode === 'replace') {
                            // Handle replacement
                            $candidate = Candidate::where('candidate_id', $record['candidate_id'])->first();
                            $this->updateCandidate($candidate, $record, $this->examYear, $this->examType);
                            $updateCount++;
                            continue;
                        }
                    }

                    // Collect for batch
                    $batch[] = [
                        'record' => $record,
                        'examYear' => $resolvedExamYear,
                        'examType' => $this->examType ?? 'ACSEE',
                        'schools' => $schools,
                        'acseeType' => $acseeType,
                    ];

                    // Process batch when it reaches size
                    if (count($batch) >= $chunkSize) {
                        $successCount += $this->processBatch($batch);
                        $batch = [];
                        gc_collect_cycles(); // Prevent memory bloat
                    }
                } catch (Exception $e) {
                    Log::warning("Row $rowNumber error: " . $e->getMessage());
                    $errorCount++;
                }
            }

            // Process remaining batch
            if (!empty($batch)) {
                $successCount += $this->processBatch($batch);
            }

            fclose($handle);

            Log::info("Bulk import summary", [
                'total_rows' => $rowNumber - 1,
                'imported' => $successCount,
                'skipped' => $skipCount,
                'updated' => $updateCount,
                'errors' => $errorCount,
            ]);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    }

    /**
     * Map CSV row to record array
     */
    private function mapRowToRecord(array $row, array $header): array
    {
        $record = [];
        foreach ($header as $index => $columnName) {
            $record[$columnName] = $row[$index] ?? null;
        }
        return $record;
    }

    /**
     * Validate a candidate record
     */
    private function validateRecord(array $record, array &$errors): void
    {
        if (empty($record['candidate_id'])) {
            $errors[] = 'candidate_id is required';
        }

        if (empty($record['full_name'])) {
            $errors[] = 'full_name is required';
        }

        if (empty($record['gender'])) {
            $errors[] = 'gender is required';
        } elseif (!in_array(strtoupper($record['gender'][0]), ['M', 'F'])) {
            $errors[] = 'gender must be M or F';
        }

        if (empty($record['school_code'])) {
            $errors[] = 'school_code is required';
        }

        // For ACSEE, validate combination
        $examType = $record['exam_type'] ?? $this->examType ?? 'ACSEE';
        if (strtoupper($examType) === 'ACSEE') {
            if (empty($record['combination'])) {
                $errors[] = 'combination is required for ACSEE';
            }
        }
    }

    /**
     * Process a batch of candidate records
     */
    private function processBatch(array $batch): int
    {
        $count = 0;

        DB::beginTransaction();
        try {
            foreach ($batch as $item) {
                $record = $item['record'];
                $schools = $item['schools'];
                $acseeType = $item['acseeType'];
                $examYear = $item['examYear'];
                $examType = $item['examType'];

                $school = $schools[$record['school_code']] ?? null;
                if (!$school) {
                    Log::warning("School not found: {$record['school_code']}");
                    continue;
                }

                // Normalize combination and resolve ID (strict exact match)
                $comboCode = isset($record['combination']) ? strtoupper(trim($record['combination'])) : null;
                $combo = null;
                if ($comboCode) {
                    $combo = \App\Models\Combination::where('code', $comboCode)->first();
                }

                // Create candidate with both code and FK
                $candidate = Candidate::create([
                    'school_id' => $school->id,
                    'candidate_id' => $record['candidate_id'],
                    'full_name' => $record['full_name'],
                    'gender' => strtoupper($record['gender'][0] ?? 'M'),
                    'exam_type' => $examType,
                    'combination' => $comboCode,
                    'combination_id' => $combo?->id ?? null,
                    'status' => 'registered',
                    'is_active' => true,
                ]);

                // Integrity guard after create
                if ($comboCode && ($candidate->combination !== $comboCode || $candidate->combination_id !== ($combo?->id ?? null))) {
                    throw new Exception("Combination mismatch for {$candidate->candidate_id}: CSV='{$comboCode}' Saved='{$candidate->combination}'");
                }

                // Register for ACSEE if needed (use normalized code)
                if (strtoupper($examType) === 'ACSEE' && $comboCode && $acseeType && $examYear) {
                    $this->registerForACSEE($candidate, $comboCode, $acseeType, $examYear);
                }

                $count++;
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Batch processing error: " . $e->getMessage());
            throw $e;
        }

        return $count;
    }

    /**
     * Register candidate for ACSEE with preloaded data
     */
    private function registerForACSEE(Candidate $candidate, string $combination, ExamType $examType, ExamYear $examYear): void
    {
        // Check if already registered
        $existing = CandidateExamRegistration::where('candidate_id', $candidate->id)
            ->where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->first();

        if ($existing) {
            return;
        }

        // Create registration
        CandidateExamRegistration::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $examType->id,
            'exam_year_id' => $examYear->id,
            'year' => (int)$examYear->year_label,
            'registration_number' => 'REG-' . uniqid(),
            'is_active' => true,
            'is_verified' => false,
        ]);

        // Register subjects in batch
        $parts = array_map('trim', explode(',', $combination));
        $subjects = Subject::where('exam_type_id', $examType->id)
            ->where(function ($q) use ($parts) {
                foreach ($parts as $part) {
                    $q->orWhere('code', 'LIKE', $part)
                        ->orWhereRaw('LOWER(code) = LOWER(?)', [$part]);
                }
            })
            ->get();

        // Batch insert subject selections
        $subjectSelections = $subjects->map(function ($subject) use ($candidate, $examType, $examYear) {
            return [
                'candidate_id' => $candidate->id,
                'exam_type_id' => $examType->id,
                'exam_year_id' => $examYear->id,
                'subject_id' => $subject->id,
                'year' => (int)$examYear->year_label,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        if (!empty($subjectSelections)) {
            CandidateSubjectSelection::insert($subjectSelections);
        }
    }

    /**
     * Resolve exam year
     */
    private function resolveExamYear(?string $yearStr): ?ExamYear
    {
        if ($yearStr) {
            return ExamYear::where('year_label', $yearStr)->first();
        }
        return ExamYear::active()->first();
    }

    /**
     * Update existing candidate
     */
    private function updateCandidate(Candidate $candidate, array $record, ?string $examYear = null, ?string $examType = null): void
    {
        $school = School::where('code', $record['school_code'])->first();
        if (!$school) {
            return;
        }

        $updateData = [
            'school_id' => $school->id,
            'full_name' => $record['full_name'],
            'gender' => strtoupper($record['gender'][0] ?? 'M'),
            'exam_type' => $examType ?? $candidate->exam_type,
        ];
        if (!empty($record['combination'])) {
            $comboCode = strtoupper(trim($record['combination']));
            $combo = \App\Models\Combination::where('code', $comboCode)->first();
            $updateData['combination'] = $comboCode;
            $updateData['combination_id'] = $combo?->id ?? null;
        }

        $candidate->update($updateData);

        // Re-register for ACSEE if needed
        if (strtoupper($examType ?? 'ACSEE') === 'ACSEE' && $record['combination']) {
            $acseeType = ExamType::where('code', 'ACSEE')->first();
            $resolvedYear = $this->resolveExamYear($examYear);
            if ($acseeType && $resolvedYear) {
                $this->registerForACSEE($candidate, $record['combination'], $acseeType, $resolvedYear);
            }
        }
    }
}
