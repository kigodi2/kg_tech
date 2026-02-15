<?php

namespace App\Services\Candidates;

use App\Models\Candidate;
use App\Models\School;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Models\CandidateExamRegistration;
use App\Models\CandidateSubjectSelection;
use App\Models\Subject;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CandidateImportService
{
    /**
     * Validate CSV file and return preview (Phase 1: Dry-run)
     * 
     * Returns:
     * - success (bool)
     * - total_rows (int)
     * - valid_count (int)
     * - invalid_count (int)
     * - errors (array) - details per failed row
     * - summary (array) - counts by error type
     */
    public function validateCSV(UploadedFile $file, ?string $examYear = null, ?string $examType = null): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);
        
        if (!$header) {
            fclose($handle);
            return [
                'success' => false,
                'message' => 'CSV file is empty',
                'total_rows' => 0,
                'valid_count' => 0,
                'invalid_count' => 0,
                'errors' => [],
                'summary' => []
            ];
        }

        // Normalize headers
        $header = array_map('strtolower', $header);
        $header = array_map('trim', $header);

        $rowNumber = 0;
        $validCount = 0;
        $errorRows = [];
        $errorSummary = [];
        $seenCandidates = []; // Track duplicates within file

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Map row to columns
            $record = $this->mapRowToRecord($row, $header);
            $rowErrors = [];

            // Validate each field
            $this->validateCandidateId($record['candidate_id'] ?? null, $rowErrors, $seenCandidates, $rowNumber);
            $this->validateFullName($record['full_name'] ?? null, $rowErrors);
            $this->validateGender($record['gender'] ?? null, $rowErrors);
            $this->validateSchoolCode($record['school_code'] ?? null, $rowErrors);

            // If exam_type is ACSEE, validate combination
            $finalExamType = $record['exam_type'] ?? $examType ?? 'ACSEE';
            if (strtoupper($finalExamType) === 'ACSEE') {
                $this->validateCombination($record['combination'] ?? null, $rowErrors);
            }

            // Validate exam year if provided
            if ($examYear || $record['exam_year'] ?? null) {
                $finalExamYear = $record['exam_year'] ?? $examYear;
                $this->validateExamYear($finalExamYear, $rowErrors);
            }

            // Check for duplicates in DB
            if ($record['candidate_id']) {
                $existing = Candidate::where('candidate_id', $record['candidate_id'])->first();
                if ($existing) {
                    $rowErrors[] = 'Candidate ID already exists in database';
                    $errorSummary['duplicate_in_db'] = ($errorSummary['duplicate_in_db'] ?? 0) + 1;
                }
            }

            // Collect results
            if (empty($rowErrors)) {
                $validCount++;
                $seenCandidates[$record['candidate_id']] = true;
            } else {
                $errorRows[] = [
                    'row_number' => $rowNumber,
                    'candidate_id' => $record['candidate_id'] ?? '',
                    'full_name' => $record['full_name'] ?? '',
                    'gender' => $record['gender'] ?? '',
                    'school_code' => $record['school_code'] ?? '',
                    'combination' => $record['combination'] ?? '',
                    'exam_type' => $record['exam_type'] ?? $examType ?? 'ACSEE',
                    'error_messages' => $rowErrors,
                    'primary_error' => reset($rowErrors) ?: 'Unknown error'
                ];

                // Track error types
                foreach ($rowErrors as $err) {
                    $key = strtolower(str_replace(' ', '_', substr($err, 0, 30)));
                    $errorSummary[$key] = ($errorSummary[$key] ?? 0) + 1;
                }
            }
        }

        fclose($handle);

        return [
            'success' => count($errorRows) === 0,
            'message' => count($errorRows) === 0 ? 'All rows valid' : count($errorRows) . ' row(s) have errors',
            'total_rows' => $rowNumber,
            'valid_count' => $validCount,
            'invalid_count' => count($errorRows),
            'errors' => array_slice($errorRows, 0, 100), // Limit to first 100 for display
            'total_errors' => count($errorRows),
            'summary' => $errorSummary,
            'can_import' => $validCount > 0
        ];
    }

    /**
     * Re-validate and commit the import (Phase 2)
     * 
     * Returns:
     * - success (bool)
     * - message (string)
     * - imported_count (int)
     * - skipped_count (int)
     * - updated_count (int)
     * - errors (array)
     */
    public function commitImport(
        UploadedFile $file,
        ?string $examYear = null,
        ?string $examType = null,
        string $mode = 'skip'
    ): array {
        try {
            DB::beginTransaction();

            $handle = fopen($file->getRealPath(), 'r');
            $header = fgetcsv($handle);
            
            if (!$header) {
                fclose($handle);
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'CSV file is empty',
                    'imported_count' => 0,
                    'skipped_count' => 0,
                    'updated_count' => 0,
                    'errors' => []
                ];
            }

            $header = array_map('strtolower', $header);
            $header = array_map('trim', $header);

            // Preload lookup tables to avoid N+1 queries
            $schools = School::all()->keyBy('code');
            $acseeType = ExamType::where('code', 'ACSEE')->first();
            $resolvedExamYear = $this->resolveExamYear($examYear);
            
            // Preload existing candidate IDs for batch checking
            $existingCandidateIds = Candidate::pluck('id', 'candidate_id');

            $rowNumber = 0;
            $importedCount = 0;
            $skippedCount = 0;
            $updatedCount = 0;
            $errors = [];
            $chunk = []; // Batch records
            $chunkSize = 100; // Process in batches of 100

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    $record = $this->mapRowToRecord($row, $header);
                    
                    // Re-validate
                    $rowErrors = [];
                    $this->validateCandidateId($record['candidate_id'] ?? null, $rowErrors, [], $rowNumber);
                    $this->validateFullName($record['full_name'] ?? null, $rowErrors);
                    $this->validateGender($record['gender'] ?? null, $rowErrors);
                    $this->validateSchoolCode($record['school_code'] ?? null, $rowErrors);

                    $finalExamType = $record['exam_type'] ?? $examType ?? 'ACSEE';
                    if (strtoupper($finalExamType) === 'ACSEE') {
                        $this->validateCombination($record['combination'] ?? null, $rowErrors);
                    }

                    if (!empty($rowErrors)) {
                        $errors[] = [
                            'row_number' => $rowNumber,
                            'candidate_id' => $record['candidate_id'] ?? '',
                            'error_messages' => $rowErrors
                        ];
                        continue;
                    }

                    // Check if candidate exists using preloaded list
                    $candidateExists = isset($existingCandidateIds[$record['candidate_id']]);

                    if ($candidateExists) {
                        if ($mode === 'skip') {
                            $skippedCount++;
                            continue;
                        } elseif ($mode === 'replace') {
                            $existingCandidate = Candidate::where('candidate_id', $record['candidate_id'])->first();
                            $this->updateCandidate($existingCandidate, $record, $examYear, $examType);
                            $updatedCount++;
                            continue;
                        }
                    }

                    // Collect for batch processing
                    $chunk[] = [
                        'record' => $record,
                        'examYear' => $resolvedExamYear,
                        'examType' => $finalExamType,
                        'schools' => $schools,
                        'acseeType' => $acseeType
                    ];

                    // Process chunk when it reaches batch size
                    if (count($chunk) >= $chunkSize) {
                        $importedCount += $this->processBatch($chunk);
                        $chunk = [];
                    }

                } catch (\Exception $e) {
                    Log::warning("Row $rowNumber import error: " . $e->getMessage());
                    $errors[] = [
                        'row_number' => $rowNumber,
                        'candidate_id' => $record['candidate_id'] ?? 'unknown',
                        'error_messages' => [$e->getMessage()]
                    ];
                }
            }

            // Process remaining chunk
            if (!empty($chunk)) {
                $importedCount += $this->processBatch($chunk);
            }

            fclose($handle);

            DB::commit();

            return [
                'success' => true,
                'message' => "Imported $importedCount candidates" . ($updatedCount > 0 ? ", updated $updatedCount" : '') . ($skippedCount > 0 ? ", skipped $skippedCount" : ''),
                'imported_count' => $importedCount,
                'skipped_count' => $skippedCount,
                'updated_count' => $updatedCount,
                'errors' => array_slice($errors, 0, 100)
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Candidate import commit error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'imported_count' => 0,
                'skipped_count' => 0,
                'updated_count' => 0,
                'errors' => []
            ];
        }
    }

    /**
     * Map CSV row to candidate record using headers
     */
    private function mapRowToRecord(array $row, array $headers): array
    {
        $record = [];
        foreach ($headers as $index => $header) {
            $record[$header] = trim($row[$index] ?? '');
        }
        return $record;
    }

    /**
     * Validate candidate_id
     */
    private function validateCandidateId(?string $candidateId, array &$errors, array $seen = [], int $rowNumber = 0): void
    {
        if (empty($candidateId)) {
            $errors[] = 'candidate_id is required';
            return;
        }

        if (strlen($candidateId) > 50) {
            $errors[] = 'candidate_id must be 50 characters or less';
        }

        if (isset($seen[$candidateId])) {
            $errors[] = 'candidate_id is duplicated within this file';
        }
    }

    /**
     * Validate full_name
     */
    private function validateFullName(?string $fullName, array &$errors): void
    {
        if (empty($fullName)) {
            $errors[] = 'full_name is required';
            return;
        }

        if (strlen($fullName) > 255) {
            $errors[] = 'full_name must be 255 characters or less';
        }
    }

    /**
     * Validate gender (M or F)
     */
    private function validateGender(?string $gender, array &$errors): void
    {
        if (empty($gender)) {
            $errors[] = 'gender is required (M or F)';
            return;
        }

        $genderUpper = strtoupper($gender[0] ?? '');
        if (!in_array($genderUpper, ['M', 'F'])) {
            $errors[] = 'gender must be M or F';
        }
    }

    /**
     * Validate school_code exists
     */
    private function validateSchoolCode(?string $schoolCode, array &$errors): void
    {
        if (empty($schoolCode)) {
            $errors[] = 'school_code is required';
            return;
        }

        $school = School::where('code', $schoolCode)->first();
        if (!$school) {
            $errors[] = "school_code not found: $schoolCode";
        }
    }

    /**
     * Validate combination for ACSEE
     */
    private function validateCombination(?string $combination, array &$errors): void
    {
        if (empty($combination)) {
            $errors[] = 'combination is required for ACSEE candidates';
            return;
        }

        $acsee = ExamType::where('code', 'ACSEE')->first();
        if (!$acsee) {
            $errors[] = 'ACSEE exam type not configured';
            return;
        }

        $combinationValue = trim($combination);

        // First try: Check if it's a registered combination code (e.g., HGL, HGK, HKL)
        // Case-insensitive comparison to handle codes like PMCs
        $combinationExists = DB::table('combinations')
            ->where('exam_type_id', $acsee->id)
            ->whereRaw('LOWER(code) = LOWER(?)', [$combinationValue])
            ->exists();

        if ($combinationExists) {
            return; // Valid combination code found
        }

        // Second try: Check if it's comma-separated subjects (e.g., "History,Geography,Literature")
        $parts = array_map('trim', explode(',', $combinationValue));
        $parts = array_filter($parts);

        if (count($parts) > 1) {
            // Treat as comma-separated subjects
            $foundSubjects = Subject::where('exam_type_id', $acsee->id)
                ->where(function ($q) use ($parts) {
                    foreach ($parts as $part) {
                        $q->orWhere('code', strtoupper($part))
                          ->orWhere('name', 'like', "%$part%");
                    }
                })
                ->count();

            if ($foundSubjects !== count($parts)) {
                $errors[] = "combination has invalid subjects: $combinationValue";
            }
        } else {
            // Single value that's not a registered combination
            $errors[] = "combination code not found: $combinationValue";
        }
    }

    /**
     * Validate exam year
     */
    private function validateExamYear(?string $yearStr, array &$errors): void
    {
        if (empty($yearStr)) {
            return; // Optional
        }

        if (!preg_match('/^\d{4}$/', $yearStr)) {
            $errors[] = 'exam_year must be a 4-digit year';
            return;
        }

        $year = ExamYear::where('year_label', $yearStr)->first();
        if (!$year) {
            $errors[] = "exam_year not found: $yearStr";
        }
    }

    /**
     * Create a new candidate
     */
    private function createCandidate(array $record, ?string $examYear = null, ?string $examType = null): Candidate
    {
        $school = School::where('code', $record['school_code'])->firstOrFail();

        $candidate = Candidate::create([
            'school_id' => $school->id,
            'candidate_id' => $record['candidate_id'],
            'full_name' => $record['full_name'],
            'gender' => strtoupper($record['gender'][0]),
            'exam_type' => $record['exam_type'] ?? $examType ?? 'ACSEE',
            'combination' => $record['combination'] ?? null,
            'status' => 'registered',
            'is_active' => true,
        ]);

        // Register for ACSEE if needed
        if ((strtoupper($record['exam_type'] ?? $examType ?? 'ACSEE') === 'ACSEE') && $record['combination']) {
            $this->registerForACSEE($candidate, $record['combination'], $examYear);
        }

        return $candidate;
    }

    /**
     * Update an existing candidate
     */
    private function updateCandidate(Candidate $candidate, array $record, ?string $examYear = null, ?string $examType = null): void
    {
        $school = School::where('code', $record['school_code'])->firstOrFail();

        $candidate->update([
            'school_id' => $school->id,
            'full_name' => $record['full_name'],
            'gender' => strtoupper($record['gender'][0]),
            'exam_type' => $record['exam_type'] ?? $examType ?? $candidate->exam_type,
            'combination' => $record['combination'] ?? $candidate->combination,
        ]);

        // Re-register for ACSEE if combination changed
        if ((strtoupper($record['exam_type'] ?? $examType ?? 'ACSEE') === 'ACSEE') && $record['combination']) {
            $this->registerForACSEE($candidate, $record['combination'], $examYear);
        }
    }

    /**
     * Register candidate for ACSEE
     */
    private function registerForACSEE(Candidate $candidate, string $combination, ?string $examYearStr = null): void
    {
        $examType = ExamType::where('code', 'ACSEE')->first();
        if (!$examType) {
            throw new \Exception('ACSEE exam type not found');
        }

        // Resolve exam year
        $examYear = null;
        if ($examYearStr) {
            $examYear = ExamYear::where('year_label', $examYearStr)->first();
        }
        if (!$examYear) {
            $examYear = ExamYear::active()->first();
        }
        if (!$examYear) {
            throw new \Exception('No active exam year found');
        }

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

        // Register subjects from combination
        $parts = array_map('trim', explode(',', $combination));
        $subjects = Subject::where('exam_type_id', $examType->id)
            ->where(function ($q) use ($parts) {
                foreach ($parts as $part) {
                    $q->orWhere('code', strtoupper($part))
                      ->orWhere('name', 'like', "%$part%");
                }
            })
            ->get();

        foreach ($subjects as $subject) {
            $existingSubject = CandidateSubjectSelection::where('candidate_id', $candidate->id)
                ->where('subject_id', $subject->id)
                ->where('exam_type_id', $examType->id)
                ->where('exam_year_id', $examYear->id)
                ->first();

            if (!$existingSubject) {
                CandidateSubjectSelection::create([
                    'candidate_id' => $candidate->id,
                    'exam_type_id' => $examType->id,
                    'exam_year_id' => $examYear->id,
                    'subject_id' => $subject->id,
                    'year' => (int)$examYear->year_label,
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * Resolve exam year to ExamYear model
     */
    private function resolveExamYear(?string $yearStr): ?ExamYear
    {
        if ($yearStr) {
            return ExamYear::where('year_label', $yearStr)->first();
        }
        return ExamYear::active()->first();
    }

    /**
     * Process a batch of candidate records
     */
    private function processBatch(array $batch): int
    {
        $count = 0;

        foreach ($batch as $item) {
            try {
                $record = $item['record'];
                $schools = $item['schools'];
                $acseeType = $item['acseeType'];
                $examYear = $item['examYear'];
                $examType = $item['examType'];

                // Get school from preloaded list
                $school = $schools[$record['school_code']] ?? null;
                if (!$school) {
                    Log::warning("School not found: {$record['school_code']}");
                    continue;
                }

                // Create candidate
                $candidate = Candidate::create([
                    'school_id' => $school->id,
                    'candidate_id' => $record['candidate_id'],
                    'full_name' => $record['full_name'],
                    'gender' => strtoupper($record['gender'][0] ?? 'M'),
                    'exam_type' => $examType,
                    'combination' => $record['combination'] ?? null,
                    'status' => 'registered',
                    'is_active' => true,
                ]);

                // Register for ACSEE if needed
                if (strtoupper($examType) === 'ACSEE' && $record['combination'] && $acseeType && $examYear) {
                    $this->registerForACSEEBatch($candidate, $record['combination'], $acseeType, $examYear);
                }

                $count++;

            } catch (\Exception $e) {
                Log::error("Batch process error: " . $e->getMessage(), [
                    'record' => $record['candidate_id'] ?? 'unknown'
                ]);
            }
        }

        return $count;
    }

    /**
     * Register candidate for ACSEE (batch version - with preloaded exam type and year)
     */
    private function registerForACSEEBatch(Candidate $candidate, string $combination, ExamType $examType, ExamYear $examYear): void
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

        // Register subjects from combination - but do this less frequently to avoid timeout
        // Batch collect subjects instead of registering one by one
        $parts = array_map('trim', explode(',', $combination));
        $subjects = Subject::where('exam_type_id', $examType->id)
            ->where(function ($q) use ($parts) {
                foreach ($parts as $part) {
                    $q->orWhere('code', strtoupper($part))
                      ->orWhere('name', 'like', "%$part%");
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
}
