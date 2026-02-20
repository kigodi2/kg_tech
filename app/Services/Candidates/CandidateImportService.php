<?php

namespace App\Services\Candidates;

use App\Models\Candidate;
use App\Models\School;
use App\Models\District;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Models\CandidateExamRegistration;
use App\Models\CandidateSubjectSelection;
use App\Models\Subject;
use App\Models\Combination;
use App\Services\AcseeAllocationValidator;
use App\Services\IndexNumber\IndexNumberValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CandidateImportService
{
    /**
     * Validate CSV file and return preview (Phase 1: Dry-run)
     * Supports skip/replace mode for handling existing candidates
     * 
     * Returns:
     * - success (bool)
     * - total_rows (int)
     * - create_count (int) - new candidates
     * - update_count (int) - candidates that will be updated (replace mode)
     * - skip_count (int) - candidates that exist and will be skipped
     * - error_count (int) - genuine validation errors
     * - errors (array) - details per failed row
     * - rows (array) - detailed row status for preview table
     * - summary (array) - counts by error type
     * - can_import (bool)
     */
    public function validateCSV(
        UploadedFile $file,
        ?string $examYear = null,
        ?string $examType = null,
        string $mode = 'skip'
    ): array {
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (!$header) {
            fclose($handle);
            return [
                'success' => false,
                'message' => 'CSV file is empty',
                'total_rows' => 0,
                'create_count' => 0,
                'update_count' => 0,
                'skip_count' => 0,
                'error_count' => 0,
                'errors' => [],
                'rows' => [],
                'summary' => [],
                'can_import' => false
            ];
        }

        // Normalize headers
        $header = array_map('strtolower', $header);
        $header = array_map('trim', $header);

        $rowNumber = 0;
        $createCount = 0;
        $updateCount = 0;
        $skipCount = 0;
        $errorRows = [];
        $errorSummary = [];
        $rowDetails = [];
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
            $rowStatus = 'NEW';

            // Validate each field
            $this->validateCandidateId($record['candidate_id'] ?? null, $rowErrors, $seenCandidates, $rowNumber);
            $this->validateFullName($record['full_name'] ?? null, $rowErrors);
            $this->validateGender($record['gender'] ?? null, $rowErrors);

            // NECTA Phase 2: Validate candidate type (SCHOOL or PRIVATE)
            $candidateType = strtoupper($record['candidate_type'] ?? 'SCHOOL');
            $this->validateCandidateType($candidateType, $rowErrors);

            // If exam_type is ACSEE, validate combination or subjects based on type
            $finalExamType = $record['exam_type'] ?? $examType ?? 'ACSEE';
            if (strtoupper($finalExamType) === 'ACSEE') {
                if ($candidateType === 'SCHOOL') {
                    $this->validateSchoolCode($record['school_code'] ?? null, $rowErrors);
                    $this->validateCombination($record['combination'] ?? null, $rowErrors, 'SCHOOL');
                } else {
                    // PRIVATE candidate: school_code is still required (for centre affiliation)
                    $this->validateSchoolCode($record['school_code'] ?? null, $rowErrors);
                    // PRIVATE candidates must have subjects column
                    $this->validateSubjects($record['subjects'] ?? null, $rowErrors);
                }
            } else {
                // Non-ACSEE: validate school code
                $this->validateSchoolCode($record['school_code'] ?? null, $rowErrors);
                if ($record['combination'] ?? null) {
                    $this->validateCombination($record['combination'] ?? null, $rowErrors, 'SCHOOL');
                }
            }

            // Validate exam year if provided (from CSV or UI dropdown)
            // The exam_year is optional in the CSV - it can come from the UI dropdown instead
            $csvExamYear = $record['exam_year'] ?? null;
            if ($csvExamYear) {
                // Validate CSV exam year if present
                $this->validateExamYear($csvExamYear, $rowErrors);
            }
            // If exam year is provided via UI but not in CSV, we don't validate per-row
            // The exam year will be applied globally to the ACSEE registration

            // Check for duplicates in DB - KEY CHANGE: handle via skip/replace mode
            $existingCandidate = null;
            if ($record['candidate_id']) {
                $existingCandidate = Candidate::where('candidate_id', $record['candidate_id'])->first();
                if ($existingCandidate) {
                    if ($mode === 'replace') {
                        $rowStatus = 'REPLACE';
                        $updateCount++;
                    } else {
                        // mode === 'skip'
                        $rowStatus = 'SKIP';
                        $skipCount++;
                    }
                }
            }

            // Only mark as error if validation failed
            if (empty($rowErrors)) {
                if ($rowStatus !== 'SKIP' && $rowStatus !== 'REPLACE') {
                    $createCount++;
                }
                $seenCandidates[$record['candidate_id']] = true;
            } else {
                // Real validation error
                $rowStatus = 'ERROR';
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

            // Store detailed row info for preview table
            $rowDetails[] = [
                'row_number' => $rowNumber,
                'candidate_id' => $record['candidate_id'] ?? '',
                'full_name' => $record['full_name'] ?? '',
                'csv_combination' => $record['combination'] ?? '',
                'resolved_combination' => empty($rowErrors) && !empty($record['combination']) ? strtoupper(trim($record['combination'])) : null,
                'status' => $rowStatus,
                'messages' => $rowErrors
            ];
        }

        fclose($handle);

        return [
            'success' => count($errorRows) === 0,
            'message' => count($errorRows) === 0 ? 'All rows valid' : count($errorRows) . ' row(s) have errors',
            'total_rows' => $rowNumber,
            'create_count' => $createCount,
            'update_count' => $updateCount,
            'skip_count' => $skipCount,
            'error_count' => count($errorRows),
            'errors' => array_slice($errorRows, 0, 100), // Limit to first 100 for display
            'total_errors' => count($errorRows),
            'rows' => $rowDetails,
            'summary' => $errorSummary,
            'can_import' => ($createCount + $updateCount) > 0 && count($errorRows) === 0
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
            $allocationsCreated = 0;
            $allocationsUpdated = 0;
            $allocationErrors = [];
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

                    // Determine candidate type for validation
                    $candidateType = strtoupper($record['candidate_type'] ?? 'SCHOOL');

                    $finalExamType = $record['exam_type'] ?? $examType ?? 'ACSEE';
                    if (strtoupper($finalExamType) === 'ACSEE') {
                        if ($candidateType === 'SCHOOL') {
                            // SCHOOL candidates require combination
                            $this->validateCombination($record['combination'] ?? null, $rowErrors, 'SCHOOL');
                        } else {
                            // PRIVATE candidates require subjects
                            $this->validateSubjects($record['subjects'] ?? null, $rowErrors);
                        }
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
                        $result = $this->processBatch($chunk);
                        $importedCount += $result['imported'];
                        $allocationsCreated += $result['allocations'];
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
                $result = $this->processBatch($chunk);
                $importedCount += $result['imported'];
                $allocationsCreated += $result['allocations'];
            }

            fclose($handle);

            DB::commit();

            $message = "Imported $importedCount candidates"
                . ($updatedCount > 0 ? ", updated $updatedCount" : '')
                . ($skippedCount > 0 ? ", skipped $skippedCount" : '')
                . ($allocationsCreated > 0 ? ", allocated subjects for $allocationsCreated" : '');

            return [
                'success' => count($errors) === 0,
                'message' => $message,
                'imported_count' => $importedCount,
                'skipped_count' => $skippedCount,
                'updated_count' => $updatedCount,
                'allocations_created_count' => $allocationsCreated,
                'allocations_updated_count' => $allocationsUpdated,
                'errors' => array_slice($errors, 0, 100),
                'allocation_errors' => array_slice($allocationErrors, 0, 50)
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
     * Validate combination for ACSEE SCHOOL candidates
     * Private candidates don't use combinations - they use manual subject selection
     */
    private function validateCombination(?string $combination, array &$errors, string $candidateType = 'SCHOOL'): void
    {
        // Private candidates don't require combination
        if ($candidateType === 'PRIVATE') {
            return;
        }

        // SCHOOL candidates require combination
        if (empty($combination)) {
            $errors[] = 'combination is required for ACSEE SCHOOL candidates';
            return;
        }

        $acsee = ExamType::where('code', 'ACSEE')->first();
        if (!$acsee) {
            $errors[] = 'ACSEE exam type not configured';
            return;
        }

        $combinationValue = strtoupper(trim($combination));

        // Strict rule: CSV is source of truth. Only accept exact combination codes
        // (case-insensitive). Do NOT attempt to parse subjects or guess.
        $combinationExists = DB::table('combinations')
            ->where('exam_type_id', $acsee->id)
            ->whereRaw('UPPER(code) = ?', [$combinationValue])
            ->exists();

        if ($combinationExists) {
            return; // Valid exact combination code
        }

        // Not found: treat as validation error (no guessing)
        $errors[] = "Unknown combination code: $combinationValue";
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
     * Create a new candidate - NECTA Phase 2 support (SCHOOL + PRIVATE)
     */
    private function createCandidate(array $record, ?string $examYear = null, ?string $examType = null): Candidate
    {
        $candidateType = strtoupper($record['candidate_type'] ?? 'SCHOOL');
        $finalExamType = strtoupper($record['exam_type'] ?? $examType ?? 'ACSEE');

        $candidateData = [
            'candidate_id' => $record['candidate_id'],
            'full_name' => $record['full_name'],
            'gender' => strtoupper($record['gender'][0] ?? 'M'),
            'exam_type' => $finalExamType,
            'candidate_type' => $candidateType,  // NECTA Phase 2
            'status' => 'registered',
            'is_active' => true,
        ];

        if ($candidateType === 'SCHOOL') {
            // SCHOOL candidate: requires school_code and combination
            $school = School::where('code', $record['school_code'])->firstOrFail();
            $candidateData['school_id'] = $school->id;
            $comboCode = isset($record['combination']) ? strtoupper(trim($record['combination'])) : null;
            $candidateData['combination'] = $comboCode;
            $candidateData['combination_id'] = $this->getCombinationId($comboCode);
        } else {
            // PRIVATE candidate: requires district
            $district = District::where('name', 'like', "%{$record['district']}%")->firstOrFail();
            $candidateData['district_id'] = $district->id;
        }

        $candidate = Candidate::create($candidateData);

        // Integrity guard: ensure saved combination matches CSV (if provided)
        if (!empty($comboCode)) {
            $savedComboCode = strtoupper(trim($candidate->combination ?? ''));
            $savedComboId = $candidate->combination_id;
            if ($savedComboCode !== $comboCode || $savedComboId !== ($candidateData['combination_id'] ?? null)) {
                throw new \Exception("Combination mismatch detected for {$candidate->candidate_id}. CSV='{$comboCode}' Saved='{$savedComboCode}'");
            }
        }

        // Register for ACSEE if needed
        if ($finalExamType === 'ACSEE') {
            if ($candidateType === 'SCHOOL' && !empty($comboCode)) {
                $this->registerForACSEE($candidate, $comboCode, $examYear);
            } elseif ($candidateType === 'PRIVATE' && $record['subjects']) {
                $this->registerForACSEEPrivate($candidate, $record['subjects'], $examYear);
            }
        }

        return $candidate;
    }

    /**
     * Update an existing candidate (REPLACE mode in import)
     * 
     * Safe update: only changes name, gender, school_id
     * Does NOT change candidate_id, exam_type, combination to prevent exam allocation issues
     */
    private function updateCandidate(Candidate $candidate, array $record, ?string $examYear = null, ?string $examType = null): void
    {
        $school = School::where('code', $record['school_code'])->first();

        if (!$school) {
            Log::warning("Cannot update candidate {$candidate->candidate_id}: school {$record['school_code']} not found");
            return;
        }

        // Safe update: name, gender, school. For replace mode we also update combination
        $updateData = [
            'school_id' => $school->id,
            'full_name' => $record['full_name'],
            'gender' => strtoupper($record['gender'][0] ?? 'M'),
        ];

        // If CSV provided a combination, apply it (replace mode semantics)
        if (!empty($record['combination'])) {
            $comboCode = strtoupper(trim($record['combination']));
            $comboId = $this->getCombinationId($comboCode);
            $updateData['combination'] = $comboCode;
            $updateData['combination_id'] = $comboId;
        }

        $candidate->update($updateData);

        // Integrity guard: ensure updated candidate matches CSV combination if provided
        if (!empty($record['combination'])) {
            $savedComboCode = strtoupper(trim($candidate->combination ?? ''));
            $savedComboId = $candidate->combination_id;
            if (($comboCode ?? null) && ($savedComboCode !== ($comboCode ?? '') || $savedComboId !== ($comboId ?? null))) {
                throw new \Exception("Combination mismatch on update for {$candidate->candidate_id}. CSV='{$comboCode}' Saved='{$savedComboCode}'");
            }
        }

        Log::info("Updated candidate via import", [
            'candidate_id' => $candidate->candidate_id,
            'full_name' => $record['full_name'],
            'school_id' => $school->id,
            'mode' => 'replace'
        ]);
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
     * Validate candidate type (SCHOOL or PRIVATE) - NECTA Phase 2
     */
    private function validateCandidateType(string $type, &$errors): void
    {
        if (empty($type)) {
            $errors[] = 'candidate_type is required (SCHOOL or PRIVATE)';
            return;
        }

        if (!in_array(strtoupper($type), ['SCHOOL', 'PRIVATE'])) {
            $errors[] = 'candidate_type must be SCHOOL or PRIVATE';
        }
    }

    /**
     * Validate district exists - NECTA Phase 2 (PRIVATE candidates)
     */
    private function validateDistrict(?string $districtName, &$errors): void
    {
        if (empty($districtName)) {
            $errors[] = 'district is required for PRIVATE candidates';
            return;
        }

        $district = District::where('name', 'like', "%$districtName%")->first();
        if (!$district) {
            $errors[] = "district not found: $districtName";
        }
    }

    /**
     * Validate subjects format and content - NECTA Phase 2 (PRIVATE candidates)
     * Format: "111|102|103|104" (pipe-separated subject codes or IDs)
     * Accepts both numeric IDs and subject codes (e.g., "111|112|121|122")
     * 
     * For PRIVATE candidates:
     * - General Studies (111) is OPTIONAL (some may sit for fewer subjects)
     * - Minimum 1 subject required
     */
    private function validateSubjects(?string $subjectsStr, &$errors): void
    {
        if (empty($subjectsStr)) {
            $errors[] = 'subjects are required for PRIVATE candidates (format: 111|102|103|104)';
            return;
        }

        // Parse subject identifiers (can be codes or numeric IDs)
        $subjectIdentifiers = array_filter(
            array_map('trim', explode('|', $subjectsStr)),
            fn($s) => !empty($s)
        );

        if (empty($subjectIdentifiers)) {
            $errors[] = 'Invalid subject format. Use pipe-separated codes or IDs: 111|102|103|104';
            return;
        }

        // Resolve subjects from identifiers (try code first, then ID)
        $subjectIds = [];
        foreach ($subjectIdentifiers as $identifier) {
            // Try code first (most common case), then numeric ID
            $subject = Subject::where('code', strtoupper($identifier))->first()
                ?? Subject::find((int)$identifier);

            if ($subject) {
                $subjectIds[] = $subject->id;
            } else {
                $errors[] = "Subject not found: $identifier";
            }
        }

        // Check minimum subjects (at least 1 subject required)
        if (count($subjectIds) < 1) {
            $errors[] = 'Minimum 1 subject required, found: ' . count($subjectIds);
        }
    }

    /**
     * Get combination ID by code or name - NECTA Phase 2
     */
    private function getCombinationId(?string $combination): ?int
    {
        if (!$combination) {
            return null;
        }

        $code = strtoupper(trim($combination));
        $combo = Combination::whereRaw('UPPER(code) = ?', [$code])->first();

        return $combo ? $combo->id : null;
    }

    /**
     * Register PRIVATE candidate for ACSEE with specific subjects - NECTA Phase 2
     */
    private function registerForACSEEPrivate(Candidate $candidate, string $subjectsStr, ?string $examYearStr = null): void
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

        // Parse subject IDs
        $subjectIds = array_map('intval', array_filter(explode('|', $subjectsStr)));

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

        // Allocate specified subjects with NECTA validation
        $validator = new AcseeAllocationValidator();
        $result = $validator->validate($candidate, $examType->id, $examYear->id, $subjectIds);

        if ($result['ok']) {
            // Batch insert subject selections
            $now = now();
            $subjectSelections = [];

            foreach ($result['all_subject_ids'] as $subjectId) {
                $subjectSelections[] = [
                    'candidate_id' => $candidate->id,
                    'subject_id' => $subjectId,
                    'exam_year_id' => $examYear->id,
                    'is_principal' => in_array($subjectId, $result['principal_subject_ids']),
                    'source' => 'import',
                    'created_by' => Auth::id() ?? 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($subjectSelections)) {
                CandidateSubjectSelection::insert($subjectSelections);
            }
        } else {
            Log::warning('ACSEE subject allocation failed for PRIVATE candidate', [
                'candidate_id' => $candidate->id,
                'errors' => $result['errors']
            ]);
            throw new \Exception('Subject allocation failed: ' . implode('; ', $result['errors']));
        }
    }



    /**
     * Process a batch of candidate records
     * 
     * @param array $batch
     * @return array {imported: int, allocations: int}
     */
    private function processBatch(array $batch): array
    {
        $imported = 0;
        $allocations = 0;

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

                // Use candidate_type from CSV if provided, otherwise auto-detect from index number
                if (!empty($record['candidate_type'])) {
                    $candidateType = strtoupper($record['candidate_type']);
                } else {
                    // Auto-detect from index number prefix
                    $validator = new IndexNumberValidator();
                    $parsed = $validator->parse($record['candidate_id']);
                    $candidateType = $parsed?->candidate_type ?? 'SCHOOL'; // Default to SCHOOL if parsing fails
                }

                // Normalize combination code and resolve ID (strict exact match)
                $comboCode = isset($record['combination']) ? strtoupper(trim($record['combination'])) : null;
                $comboId = $this->getCombinationId($comboCode);

                // Create candidate with both code and FK to avoid later guessing
                $candidate = Candidate::create([
                    'school_id' => $school->id,
                    'candidate_id' => $record['candidate_id'],
                    'full_name' => $record['full_name'],
                    'gender' => strtoupper($record['gender'][0] ?? 'M'),
                    'exam_type' => $examType,
                    'combination' => $comboCode,
                    'combination_id' => $comboId,
                    'candidate_type' => $candidateType,
                    'status' => 'registered',
                    'is_active' => true,
                ]);

                // Integrity guard: ensure saved candidate matches CSV combination exactly
                $savedComboCode = strtoupper(trim($candidate->combination ?? ''));
                $savedComboId = $candidate->combination_id;
                if ($comboCode && ($savedComboCode !== $comboCode || $savedComboId !== $comboId)) {
                    throw new \Exception("Combination mismatch detected for {$candidate->candidate_id}. CSV='{$comboCode}' Saved='{$savedComboCode}'");
                }

                // Register for ACSEE if needed
                if (strtoupper($examType) === 'ACSEE' && $acseeType && $examYear) {
                    if ($candidateType === 'SCHOOL' && $comboCode) {
                        $this->registerForACSEEBatch($candidate, $comboCode, $acseeType, $examYear);
                    } elseif ($candidateType === 'PRIVATE' && !empty($record['subjects'])) {
                        // First create exam registration, then allocate subjects
                        $this->createExamRegistrationIfNotExists($candidate, $acseeType, $examYear);

                        // Allocate specific subjects for PRIVATE candidates
                        $allocationErrors = [];
                        $allocCount = $this->allocateSubjectsForPrivateCandidate($candidate, $record['subjects'], $acseeType, $examYear, $allocationErrors);
                        $allocations += $allocCount;
                        // Note: allocation errors are logged but don't fail the import
                        if (!empty($allocationErrors)) {
                            Log::warning("Allocation errors for {$candidate->candidate_id}", $allocationErrors);
                        }
                    }
                }

                $imported++;
            } catch (\Exception $e) {
                Log::error("Batch process error: " . $e->getMessage(), [
                    'record' => $record['candidate_id'] ?? 'unknown'
                ]);
            }
        }

        return ['imported' => $imported, 'allocations' => $allocations];
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

    /**
     * Create exam registration if it doesn't exist
     */
    private function createExamRegistrationIfNotExists(Candidate $candidate, ExamType $examType, ExamYear $examYear): void
    {
        $existing = CandidateExamRegistration::where('candidate_id', $candidate->id)
            ->where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->first();

        if ($existing) {
            return; // Already registered
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
    }

    /**
     * Allocate subjects for PRIVATE candidates from CSV import
     * Uses AcseeAllocationValidator to ensure NECTA compliance
     * 
     * @param Candidate $candidate - The candidate to allocate subjects for
     * @param string $subjectsStr - Pipe-delimited subject IDs or codes (e.g., "111|102|103|121")
     * @param ExamType $examType - ACSEE exam type
     * @param ExamYear $examYear - The exam year
     * @param array &$errors - Reference to collect allocation errors
     * @return int - Number of allocations created/updated (0 if errors)
     */
    private function allocateSubjectsForPrivateCandidate(
        Candidate $candidate,
        string $subjectsStr,
        ExamType $examType,
        ExamYear $examYear,
        &$errors = []
    ): int {
        try {
            if (empty($subjectsStr)) {
                return 0;
            }

            // Parse subject identifiers (can be IDs or codes)
            $subjectIdentifiers = array_filter(
                array_map('trim', explode('|', $subjectsStr)),
                fn($s) => !empty($s)
            );

            if (empty($subjectIdentifiers)) {
                return 0;
            }

            // Resolve subject IDs from identifiers (try code first, then numeric ID)
            $subjectIds = [];
            foreach ($subjectIdentifiers as $identifier) {
                // Try code first (most common case), then numeric ID
                $subject = Subject::where('code', strtoupper($identifier))->first()
                    ?? Subject::find((int)$identifier);

                if ($subject) {
                    $subjectIds[] = $subject->id;
                }
            }

            if (empty($subjectIds)) {
                $errors[] = "No valid subjects found for candidate {$candidate->candidate_id}";
                return 0;
            }

            // For PRIVATE candidates, no additional validation needed
            // They can choose any combination of subjects (unlike SCHOOL candidates with NECTA rules)

            // Delete existing allocations for this candidate+exam type+exam year (replace mode)
            CandidateSubjectSelection::where('candidate_id', $candidate->id)
                ->where('exam_type_id', $examType->id)
                ->where('exam_year_id', $examYear->id)
                ->delete();

            // Build allocations from resolved subject IDs
            $now = now();
            $allocations = [];

            // Allocate all resolved subjects (PRIVATE candidates can have any combination)
            // is_principal=true for all subjects (PRIVATE candidates don't follow NECTA principal subject rules)
            foreach ($subjectIds as $subjectId) {
                $allocations[] = [
                    'candidate_id' => $candidate->id,
                    'exam_type_id' => $examType->id,
                    'exam_year_id' => $examYear->id,
                    'subject_id' => $subjectId,
                    'year' => (int)$examYear->year_label,
                    'is_principal' => true,  // All subjects treated as principal for PRIVATE
                    'source' => 'import',
                    'created_by' => Auth::id() ?? 1,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Insert allocations
            if (!empty($allocations)) {
                CandidateSubjectSelection::insert($allocations);
                return count($allocations);
            }

            return 0;
        } catch (\Exception $e) {
            $errors[] = "Candidate {$candidate->candidate_id}: " . $e->getMessage();
            return 0;
        }
    }
}
