<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Subject;
use App\Models\Combination;
use App\Models\ExamYear;
use App\Models\CandidateSubjectSelection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * ACSEE Allocation CSV Importer
 * 
 * Two-phase CSV import for bulk subject allocation:
 * - Phase 1: Validation (non-destructive, dry-run)
 * - Phase 2: Commit (actual database changes)
 * 
 * Supports both SCHOOL (combination-driven) and PRIVATE (subject-driven) candidates
 */
class AcseeAllocationCSVImporter
{
    private AcseeAllocationValidator $validator;

    public function __construct(AcseeAllocationValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Phase 1: Validate CSV file (non-destructive)
     * 
     * @param UploadedFile $file
     * @param int $examYearId
     * @param string $candidateTypeFilter ALL|SCHOOL|PRIVATE
     * @return array {success, message, total_rows, valid_count, invalid_count, errors[], summary}
     */
    public function validateCSV(UploadedFile $file, int $examYearId, string $candidateTypeFilter = 'ALL'): array
    {
        try {
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

            // Normalize and validate headers
            $header = array_map('strtolower', $header);
            $header = array_map('trim', $header);
            
            // exam_year is now optional - can come from UI dropdown
            $requiredColumns = ['index_number'];
            $headerValidation = $this->validateHeaders($header, $requiredColumns);
            
            if (!$headerValidation['valid']) {
                fclose($handle);
                return [
                    'success' => false,
                    'message' => $headerValidation['message'],
                    'total_rows' => 0,
                    'valid_count' => 0,
                    'invalid_count' => 0,
                    'errors' => [$headerValidation],
                    'summary' => []
                ];
            }

            // Get exam year to verify it exists
            $examYear = ExamYear::find($examYearId);
            if (!$examYear) {
                fclose($handle);
                return [
                    'success' => false,
                    'message' => 'Exam year not found',
                    'total_rows' => 0,
                    'valid_count' => 0,
                    'invalid_count' => 0,
                    'errors' => [],
                    'summary' => []
                ];
            }

            $rowNumber = 0;
            $validCount = 0;
            $errorRows = [];
            $errorSummary = [];
            $seenIndexNumbers = [];

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Map row to columns
                $record = $this->mapRowToRecord($row, $header);
                $rowErrors = [];

                // Extract and normalize data
                $examYearFromCSV = $record['exam_year'] ?? null;
                $indexNumber = $record['index_number'] ?? null;
                $combinationCode = $record['combination_code'] ?? null;
                $subjectCodes = $record['subject_codes'] ?? null;
                $replaceAllocations = strtoupper($record['replace_allocations'] ?? 'NO') === 'YES';

                // Validate exam_year matches parameter
                if ($examYearFromCSV && $examYearFromCSV != $examYear->year) {
                    $rowErrors[] = "Exam year mismatch: CSV has {$examYearFromCSV}, expected {$examYear->year}";
                    $errorSummary['exam_year_mismatch'] = ($errorSummary['exam_year_mismatch'] ?? 0) + 1;
                }

                // Validate index_number
                if (!$indexNumber) {
                    $rowErrors[] = "Missing index_number";
                    $errorSummary['missing_index_number'] = ($errorSummary['missing_index_number'] ?? 0) + 1;
                } else {
                    // Check for duplicates within file
                    if (isset($seenIndexNumbers[$indexNumber])) {
                        $rowErrors[] = "Duplicate index_number in file: {$indexNumber}";
                        $errorSummary['duplicate_in_file'] = ($errorSummary['duplicate_in_file'] ?? 0) + 1;
                    }
                    $seenIndexNumbers[$indexNumber] = true;

                    // Find candidate by index_number + exam_year
                    $candidate = $this->matchCandidate($indexNumber, $examYearId);
                    
                    if (!$candidate) {
                        $rowErrors[] = "Candidate not found: {$indexNumber} (for exam year {$examYear->year})";
                        $errorSummary['candidate_not_found'] = ($errorSummary['candidate_not_found'] ?? 0) + 1;
                    } else {
                        // Validate candidate type matches filter and CSV mode detection
                        $detectedType = $this->detectCandidateType($indexNumber, $combinationCode, $subjectCodes);
                        
                        if ($candidateTypeFilter !== 'ALL' && $detectedType !== $candidateTypeFilter) {
                            $rowErrors[] = "Candidate type mismatch: candidate is {$candidate->candidate_type}, detected {$detectedType}";
                            $errorSummary['candidate_type_mismatch'] = ($errorSummary['candidate_type_mismatch'] ?? 0) + 1;
                        }

                        // Validate based on candidate type
                        if ($detectedType === 'SCHOOL') {
                            if (!$combinationCode) {
                                $rowErrors[] = "Missing combination_code for SCHOOL candidate";
                                $errorSummary['missing_combination_code'] = ($errorSummary['missing_combination_code'] ?? 0) + 1;
                            } else {
                                // Validate combination exists
                                $combination = Combination::where('code', strtoupper($combinationCode))->first();
                                if (!$combination) {
                                    $rowErrors[] = "Combination not found: {$combinationCode}";
                                    $errorSummary['combination_not_found'] = ($errorSummary['combination_not_found'] ?? 0) + 1;
                                } else {
                                    // Validate subjects from combination
                                    $subjectIds = $combination->subjects()->pluck('subjects.id')->toArray();
                                    $validationResult = $this->validator->validate(
                                        $candidate,
                                        $candidate->examRegistrations()->first()?->exam_type_id ?? 1,
                                        $examYearId,
                                        $subjectIds
                                    );
                                    
                                    if (!$validationResult['ok']) {
                                        $rowErrors = array_merge($rowErrors, $validationResult['errors']);
                                        foreach ($validationResult['warnings'] as $warning) {
                                            $errorSummary['validation_warning'] = ($errorSummary['validation_warning'] ?? 0) + 1;
                                        }
                                    }
                                }
                            }
                        } else {
                            // PRIVATE candidate
                            if (!$subjectCodes) {
                                $rowErrors[] = "Missing subject_codes for PRIVATE candidate";
                                $errorSummary['missing_subject_codes'] = ($errorSummary['missing_subject_codes'] ?? 0) + 1;
                            } else {
                                // Parse and validate subject codes
                                $subjectCodesArray = array_filter(array_map('trim', explode('|', $subjectCodes)));
                                
                                if (empty($subjectCodesArray)) {
                                    $rowErrors[] = "No valid subject codes provided";
                                    $errorSummary['no_subject_codes'] = ($errorSummary['no_subject_codes'] ?? 0) + 1;
                                } else {
                                    // Resolve codes to IDs
                                    $subjectIds = [];
                                    foreach ($subjectCodesArray as $code) {
                                        $subject = Subject::where('code', $code)->first();
                                        if (!$subject) {
                                            $rowErrors[] = "Subject code not found: {$code}";
                                            $errorSummary['invalid_subject_code'] = ($errorSummary['invalid_subject_code'] ?? 0) + 1;
                                        } else {
                                            $subjectIds[] = $subject->id;
                                        }
                                    }

                                    // Validate if we have valid subject IDs
                                    if (!empty($subjectIds)) {
                                        $validationResult = $this->validator->validate(
                                            $candidate,
                                            $candidate->examRegistrations()->first()?->exam_type_id ?? 1,
                                            $examYearId,
                                            $subjectIds
                                        );
                                        
                                        if (!$validationResult['ok']) {
                                            $rowErrors = array_merge($rowErrors, $validationResult['errors']);
                                            foreach ($validationResult['warnings'] as $warning) {
                                                $errorSummary['validation_warning'] = ($errorSummary['validation_warning'] ?? 0) + 1;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // Collect results
                if (empty($rowErrors)) {
                    $validCount++;
                } else {
                    $errorRows[] = [
                        'row_number' => $rowNumber,
                        'index_number' => $indexNumber ?? '',
                        'candidate_type' => $detectedType ?? 'UNKNOWN',
                        'combination_code' => $combinationCode ?? '',
                        'subject_codes' => $subjectCodes ?? '',
                        'error_messages' => $rowErrors,
                        'primary_error' => reset($rowErrors) ?: 'Unknown error'
                    ];

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
                'errors' => $errorRows,
                'summary' => $errorSummary
            ];

        } catch (\Exception $e) {
            Log::error('CSV validation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'CSV validation failed: ' . $e->getMessage(),
                'total_rows' => 0,
                'valid_count' => 0,
                'invalid_count' => 0,
                'errors' => [],
                'summary' => []
            ];
        }
    }

    /**
     * Phase 2: Commit CSV import (actual database changes)
     * 
     * @param UploadedFile $file
     * @param int $examYearId
     * @param string $candidateTypeFilter
     * @param bool $replaceAllocationsDefault
     * @return array {success, message, total_rows, success_count, skipped_count, failed_count, errors[], affected_candidates[]}
     */
    public function commitImport(UploadedFile $file, int $examYearId, string $candidateTypeFilter = 'ALL', bool $replaceAllocationsDefault = false): array
    {
        try {
            // Re-validate first (same as Phase 1)
            $validationResult = $this->validateCSV($file, $examYearId, $candidateTypeFilter);
            
            if (!$validationResult['success']) {
                return [
                    'success' => false,
                    'message' => 'CSV validation failed - no changes made',
                    'total_rows' => $validationResult['total_rows'],
                    'success_count' => 0,
                    'skipped_count' => 0,
                    'failed_count' => count($validationResult['errors']),
                    'errors' => $validationResult['errors'],
                    'affected_candidates' => []
                ];
            }

            $handle = fopen($file->getRealPath(), 'r');
            $header = fgetcsv($handle);
            $header = array_map('strtolower', $header);
            $header = array_map('trim', $header);

            $examYear = ExamYear::find($examYearId);
            $rowNumber = 0;
            $successCount = 0;
            $failedCount = 0;
            $errorRows = [];
            $affectedCandidates = [];

            DB::beginTransaction();

            try {
                while (($row = fgetcsv($handle)) !== false) {
                    $rowNumber++;

                    if (empty(array_filter($row))) {
                        continue;
                    }

                    $record = $this->mapRowToRecord($row, $header);
                    $indexNumber = $record['index_number'] ?? null;
                    $combinationCode = $record['combination_code'] ?? null;
                    $subjectCodes = $record['subject_codes'] ?? null;
                    $replaceAllocations = strtoupper($record['replace_allocations'] ?? ($replaceAllocationsDefault ? 'YES' : 'NO')) === 'YES';

                    $rowErrors = [];

                    // Find candidate
                    $candidate = $this->matchCandidate($indexNumber, $examYearId);
                    
                    if (!$candidate) {
                        $errorRows[] = [
                            'row_number' => $rowNumber,
                            'index_number' => $indexNumber ?? '',
                            'error_messages' => ['Candidate not found']
                        ];
                        $failedCount++;
                        continue;
                    }

                    // Determine import type
                    $importType = $combinationCode ? 'SCHOOL' : 'PRIVATE';
                    
                    try {
                        if ($importType === 'SCHOOL') {
                            $this->allocateSubjectsForSchool($candidate, $combinationCode, $examYearId, $replaceAllocations);
                        } else {
                            $this->allocateSubjectsForPrivate($candidate, $subjectCodes, $examYearId, $replaceAllocations);
                        }

                        $successCount++;
                        
                        // Track affected candidates
                        $found = false;
                        foreach ($affectedCandidates as &$aff) {
                            if ($aff['id'] === $candidate->id) {
                                $aff['allocation_count']++;
                                $found = true;
                                break;
                            }
                        }
                        
                        if (!$found) {
                            $affectedCandidates[] = [
                                'id' => $candidate->id,
                                'index_number' => $candidate->candidate_id,
                                'full_name' => $candidate->full_name,
                                'allocation_count' => 1
                            ];
                        }

                    } catch (\Exception $e) {
                        $errorRows[] = [
                            'row_number' => $rowNumber,
                            'index_number' => $indexNumber ?? '',
                            'error_messages' => [$e->getMessage()]
                        ];
                        $failedCount++;
                    }
                }

                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                fclose($handle);

                Log::error('CSV commit error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return [
                    'success' => false,
                    'message' => 'Import failed: ' . $e->getMessage(),
                    'total_rows' => $rowNumber,
                    'success_count' => 0,
                    'skipped_count' => 0,
                    'failed_count' => $rowNumber,
                    'errors' => [['row_number' => 'all', 'error_messages' => [$e->getMessage()]]],
                    'affected_candidates' => []
                ];
            }

            fclose($handle);

            return [
                'success' => $failedCount === 0,
                'message' => "Import complete: {$successCount} successful, {$failedCount} failed",
                'total_rows' => $rowNumber,
                'success_count' => $successCount,
                'skipped_count' => 0,
                'failed_count' => $failedCount,
                'errors' => $errorRows,
                'affected_candidates' => $affectedCandidates
            ];

        } catch (\Exception $e) {
            Log::error('CSV import error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'CSV import failed: ' . $e->getMessage(),
                'total_rows' => 0,
                'success_count' => 0,
                'skipped_count' => 0,
                'failed_count' => 0,
                'errors' => [],
                'affected_candidates' => []
            ];
        }
    }

    /**
     * Helper: Map CSV row to record dict using headers
     * 
     * @param array $row
     * @param array $header
     * @return array
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
     * Helper: Validate CSV headers
     * 
     * @param array $header
     * @param array $required
     * @return array {valid, message}
     */
    private function validateHeaders(array $header, array $required): array
    {
        foreach ($required as $col) {
            if (!in_array($col, $header)) {
                return [
                    'valid' => false,
                    'message' => "Missing required column: {$col}"
                ];
            }
        }
        
        // Check for either combination_code or subject_codes
        if (!in_array('combination_code', $header) && !in_array('subject_codes', $header)) {
            return [
                'valid' => false,
                'message' => 'Must include either combination_code (SCHOOL) or subject_codes (PRIVATE)'
            ];
        }

        return ['valid' => true, 'message' => 'Headers valid'];
    }

    /**
     * Helper: Find candidate by index_number + exam_year
     * 
     * @param string $indexNumber
     * @param int $examYearId
     * @return Candidate|null
     */
    private function matchCandidate(string $indexNumber, int $examYearId): ?Candidate
    {
        return Candidate::where('candidate_id', $indexNumber)
            ->whereHas('examRegistrations', function($q) use ($examYearId) {
                $q->where('exam_year_id', $examYearId);
            })
            ->first();
    }

    /**
     * Helper: Detect candidate type from index prefix or CSV columns
     * 
     * @param string $indexNumber
     * @param string|null $combinationCode
     * @param string|null $subjectCodes
     * @return string SCHOOL|PRIVATE
     */
    private function detectCandidateType(string $indexNumber, ?string $combinationCode, ?string $subjectCodes): string
    {
        // Detect from index prefix
        if (str_starts_with(strtoupper($indexNumber), 'S')) {
            return 'SCHOOL';
        }
        if (str_starts_with(strtoupper($indexNumber), 'P')) {
            return 'PRIVATE';
        }

        // Detect from CSV columns
        if ($combinationCode && !$subjectCodes) {
            return 'SCHOOL';
        }
        if ($subjectCodes && !$combinationCode) {
            return 'PRIVATE';
        }

        // Default based on what's present
        return $combinationCode ? 'SCHOOL' : 'PRIVATE';
    }

    /**
     * Helper: Allocate subjects for SCHOOL candidate
     * 
     * @param Candidate $candidate
     * @param string $combinationCode
     * @param int $examYearId
     * @param bool $replaceAllocations
     * @return void
     * @throws \Exception
     */
    private function allocateSubjectsForSchool(Candidate $candidate, string $combinationCode, int $examYearId, bool $replaceAllocations = false): void
    {
        // Find combination
        $combination = Combination::where('code', strtoupper($combinationCode))->first();
        
        if (!$combination) {
            throw new \Exception("Combination {$combinationCode} not found");
        }

        // Get subjects from combination
        $subjectIds = $combination->subjects()->pluck('subjects.id')->toArray();
        
        // Get exam type from candidate
        $examRegistration = $candidate->examRegistrations()->first();
        if (!$examRegistration) {
            throw new \Exception("Candidate has no exam registration");
        }

        // Validate
        $validationResult = $this->validator->validate(
            $candidate,
            $examRegistration->exam_type_id,
            $examYearId,
            $subjectIds
        );

        if (!$validationResult['ok']) {
            throw new \Exception('Validation failed: ' . implode(', ', $validationResult['errors']));
        }

        // Apply allocation
        $this->applyAllocation(
            $candidate,
            $examRegistration->exam_type_id,
            $examYearId,
            $validationResult['all_subject_ids'],
            $validationResult['principal_subject_ids'],
            'csv_import',
            $replaceAllocations
        );
    }

    /**
     * Helper: Allocate subjects for PRIVATE candidate
     * 
     * @param Candidate $candidate
     * @param string $subjectCodes
     * @param int $examYearId
     * @param bool $replaceAllocations
     * @return void
     * @throws \Exception
     */
    private function allocateSubjectsForPrivate(Candidate $candidate, string $subjectCodes, int $examYearId, bool $replaceAllocations = false): void
    {
        // Parse subject codes
        $codes = array_filter(array_map('trim', explode('|', $subjectCodes)));
        
        if (empty($codes)) {
            throw new \Exception('No subject codes provided');
        }

        // Resolve codes to IDs
        $subjectIds = [];
        foreach ($codes as $code) {
            $subject = Subject::where('code', $code)->first();
            if (!$subject) {
                throw new \Exception("Subject code {$code} not found");
            }
            $subjectIds[] = $subject->id;
        }

        // Get exam type from candidate
        $examRegistration = $candidate->examRegistrations()->first();
        if (!$examRegistration) {
            throw new \Exception("Candidate has no exam registration");
        }

        // Validate
        $validationResult = $this->validator->validate(
            $candidate,
            $examRegistration->exam_type_id,
            $examYearId,
            $subjectIds
        );

        if (!$validationResult['ok']) {
            throw new \Exception('Validation failed: ' . implode(', ', $validationResult['errors']));
        }

        // Apply allocation
        $this->applyAllocation(
            $candidate,
            $examRegistration->exam_type_id,
            $examYearId,
            $validationResult['all_subject_ids'],
            $validationResult['principal_subject_ids'],
            'csv_import',
            $replaceAllocations
        );
    }

    /**
     * Helper: Apply allocation to database
     * 
     * Uses updateOrCreate() for idempotency
     * 
     * @param Candidate $candidate
     * @param int $examTypeId
     * @param int $examYearId
     * @param array $allSubjectIds
     * @param array $principalSubjectIds
     * @param string $source
     * @param bool $replaceExisting
     * @return void
     */
    private function applyAllocation(
        Candidate $candidate,
        int $examTypeId,
        int $examYearId,
        array $allSubjectIds,
        array $principalSubjectIds,
        string $source,
        bool $replaceExisting = false
    ): void
    {
        if ($replaceExisting) {
            CandidateSubjectSelection::where('candidate_id', $candidate->id)
                ->where('exam_year_id', $examYearId)
                ->delete();
        }

        $examYear = ExamYear::find($examYearId);
        $year = $examYear->year ?? date('Y');

        foreach ($allSubjectIds as $subjectId) {
            $isPrincipal = in_array($subjectId, $principalSubjectIds);
            
            CandidateSubjectSelection::updateOrCreate(
                [
                    'candidate_id' => $candidate->id,
                    'exam_type_id' => $examTypeId,
                    'exam_year_id' => $examYearId,
                    'subject_id' => $subjectId,
                ],
                [
                    'year' => $year,
                    'is_principal' => $isPrincipal,
                    'source' => $source,
                    'created_by' => Auth::id(),
                    'is_active' => true,
                ]
            );
        }
    }
}
