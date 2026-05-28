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
use App\Services\Candidates\CseeCandidateSubjectService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Throwable;

class CandidateImportService
{
    private array $schoolReferenceCache = [];

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
        [$header, $rows] = $this->readImportRows($file);

        if (!$header) {
            return [
                'success' => false,
                'message' => 'Import file is empty',
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
        $header = array_map(fn ($value) => trim(strtolower((string) $value), " \t\n\r\0\x0B\xEF\xBB\xBF"), $header);

        $missingHeaders = $this->missingRequiredHeaders($header, strtoupper((string) ($examType ?? '')));
        if (!empty($missingHeaders)) {
            return [
                'success' => false,
                'message' => 'Missing required column(s): ' . implode(', ', $missingHeaders),
                'total_rows' => 0,
                'create_count' => 0,
                'update_count' => 0,
                'skip_count' => 0,
                'error_count' => count($missingHeaders),
                'warning_count' => 0,
                'errors' => array_map(fn ($header) => [
                    'row_number' => 1,
                    'candidate_id' => '',
                    'full_name' => '',
                    'primary_error' => "Missing required column: {$header}",
                    'error_messages' => ["Missing required column: {$header}"],
                ], $missingHeaders),
                'warnings' => [],
                'rows' => [],
                'summary' => [
                    'total_rows' => 0,
                    'valid_rows' => 0,
                    'duplicates_in_file' => 0,
                    'already_existing' => 0,
                    'invalid_rows' => count($missingHeaders),
                    'missing_candidate_number' => in_array('candidate_number', $missingHeaders, true) ? 1 : 0,
                    'missing_prem_no' => in_array('PReM_No', $missingHeaders, true) || in_array('prem_no', $missingHeaders, true) ? 1 : 0,
                    'missing_pupil_name' => in_array('pupil_name', $missingHeaders, true) ? 1 : 0,
                    'invalid_sex' => in_array('sex', $missingHeaders, true) ? 1 : 0,
                    'missing_school_centre' => in_array('school_code', $missingHeaders, true) ? 1 : 0,
                    'unknown_school_code' => 0,
                    'invalid_council' => 0,
                ],
                'can_import' => false,
            ];
        }

        $rowNumber = 0;
        $createCount = 0;
        $updateCount = 0;
        $skipCount = 0;
        $errorRows = [];
        $warningRows = [];
        $errorSummary = [];
        $rowDetails = [];
        $seenCandidates = []; // Track duplicates within file
        $candidateIdsInFile = $this->candidateIdsFromRows($rows, $header, $examType);
        $existingCandidateIds = empty($candidateIdsInFile)
            ? collect()
            : Candidate::whereIn('candidate_id', $candidateIdsInFile)->pluck('id', 'candidate_id');
        $seenPsleSignals = [
            'prem_school' => [],
            'name_gender_school' => [],
        ];
        foreach ($rows as $row) {
            $rowNumber++;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Map row to columns
            $record = $this->mapRowToRecord($row, $header);
            $record = $this->normalizeRecord($record, $examType);
            $rowErrors = [];
            $rowWarnings = [];
            $rowStatus = 'NEW';

            // Validate each field
            $this->validateCandidateId($record['candidate_id'] ?? null, $rowErrors, $seenCandidates, $rowNumber);
            if (!empty($record['candidate_id'])) {
                $seenCandidates[$record['candidate_id']] = true;
            }
            if ($this->isPsleImport($record, $examType)) {
                $this->validatePsleCandidateNumber($record, $rowErrors);
                $this->validatePremNo($record['prem_no'] ?? null, $rowErrors);
            }
            $this->validateFullName($record['full_name'] ?? null, $rowErrors);
            $this->validateGender($record['gender'] ?? null, $rowErrors);

            // NECTA Phase 2: Validate candidate type (SCHOOL or PRIVATE)
            $candidateType = strtoupper($record['candidate_type'] ?? 'SCHOOL');
            $this->validateCandidateType($candidateType, $rowErrors);

            // If exam_type is ACSEE, validate combination or subjects based on type
            $finalExamType = $record['exam_type'] ?? $examType ?? 'ACSEE';
            if (strtoupper($finalExamType) === 'ACSEE') {
                if ($candidateType === 'SCHOOL') {
                    $this->validateSchoolReference($record, $rowErrors);
                    $this->validateCombination($record['combination'] ?? null, $rowErrors, 'SCHOOL');
                } else {
                    // PRIVATE candidate: school_code is still required (for centre affiliation)
                    $this->validateSchoolReference($record, $rowErrors);
                    // PRIVATE candidates must have subjects column
                    $this->validateSubjects($record['subjects'] ?? null, $rowErrors);
                }
            } else {
                // Non-ACSEE: validate school code
                $this->validateSchoolReference($record, $rowErrors);
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
                $existingCandidate = $existingCandidateIds[$record['candidate_id']] ?? null;
                if ($existingCandidate !== null) {
                    if ($mode === 'replace') {
                        $rowStatus = 'REPLACE';
                        $updateCount++;
                    } elseif ($mode === 'stop') {
                        $rowStatus = 'ERROR';
                        $rowErrors[] = 'candidate_id already exists; stop-on-duplicates mode prevents import';
                    } else {
                        // mode === 'skip'
                        $rowStatus = 'SKIP';
                        $skipCount++;
                    }
                }
            }

            if ($this->isPsleImport($record, $examType)) {
                $this->validatePsleWarnings($record, $rowWarnings, $seenPsleSignals);
            }

            // Only mark as error if validation failed
            if (empty($rowErrors)) {
                if (!empty($rowWarnings) && $rowStatus === 'NEW') {
                    $rowStatus = 'WARNING';
                    $warningRows[] = [
                        'row_number' => $rowNumber,
                        'candidate_id' => $record['candidate_id'] ?? '',
                        'prem_no' => $record['prem_no'] ?? '',
                        'full_name' => $record['full_name'] ?? '',
                        'gender' => $record['gender'] ?? '',
                        'school_code' => $record['school_code'] ?? '',
                        'warning_messages' => $rowWarnings,
                        'primary_warning' => reset($rowWarnings) ?: 'Potential duplicate detected',
                    ];
                }

                if (!in_array($rowStatus, ['SKIP', 'REPLACE', 'REGISTER'], true)) {
                    $createCount++;
                }
            } else {
                // Real validation error
                if ($rowStatus === 'REPLACE') {
                    $updateCount = max(0, $updateCount - 1);
                } elseif ($rowStatus === 'SKIP') {
                    $skipCount = max(0, $skipCount - 1);
                }
                $rowStatus = 'ERROR';
                $errorRows[] = [
                    'row_number' => $rowNumber,
                    'candidate_id' => $record['candidate_id'] ?? '',
                    'prem_no' => $record['prem_no'] ?? '',
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
            $schoolContext = $this->schoolPreviewContext($record);
            $rowDetails[] = [
                'row_number' => $rowNumber,
                'candidate_id' => $record['candidate_id'] ?? '',
                'candidate_number' => $record['candidate_id'] ?? '',
                'prem_no' => $record['prem_no'] ?? '',
                'full_name' => $record['full_name'] ?? '',
                'pupil_name' => $record['full_name'] ?? '',
                'sex' => strtoupper(substr((string) ($record['gender'] ?? ''), 0, 1)),
                'school_code' => $record['school_code'] ?? '',
                'school' => $schoolContext['school_name'],
                'school_name' => $schoolContext['school_name'],
                'council' => $schoolContext['council_name'],
                'region' => $schoolContext['region_name'],
                'csv_combination' => $record['combination'] ?? '',
                'resolved_combination' => empty($rowErrors) && !empty($record['combination']) ? strtoupper(trim($record['combination'])) : null,
                'status' => $rowStatus,
                'message' => $this->previewMessage($rowStatus, $rowErrors, $rowWarnings),
                'messages' => $rowStatus === 'ERROR' ? $rowErrors : $rowWarnings
            ];
        }

        $summary = $this->buildValidationSummary($rowDetails, $errorRows, $warningRows, $createCount, $updateCount, $skipCount);

        return [
            'success' => count($errorRows) === 0,
            'message' => count($errorRows) === 0 ? 'All rows valid' : count($errorRows) . ' row(s) have errors',
            'total_rows' => $rowNumber,
            'create_count' => $createCount,
            'update_count' => $updateCount,
            'skip_count' => $skipCount,
            'error_count' => count($errorRows),
            'warning_count' => count($warningRows),
            'errors' => array_slice($errorRows, 0, 100), // Limit to first 100 for display
            'warnings' => array_slice($warningRows, 0, 100),
            'total_errors' => count($errorRows),
            'total_warnings' => count($warningRows),
            'rows' => $rowDetails,
            'summary' => $summary + $errorSummary,
            'can_import' => ($createCount + $updateCount + $skipCount) > 0 && !($mode === 'stop' && count($errorRows) > 0)
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
            $this->prepareSqliteForBulkWrite();
            $validation = $this->validateCSV($file, $examYear, $examType, $mode);
            if (!($validation['can_import'] ?? false)) {
                return [
                    'success' => false,
                    'message' => $validation['message'] ?? 'No valid rows are available for import.',
                    'imported_count' => 0,
                    'skipped_count' => $validation['skip_count'] ?? 0,
                    'updated_count' => 0,
                    'errors' => $validation['errors'] ?? [],
                    'summary' => $validation['summary'] ?? [],
                ];
            }

            DB::beginTransaction();
            [$header, $rows] = $this->readImportRows($file);

            if (!$header) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Import file is empty',
                    'imported_count' => 0,
                    'skipped_count' => 0,
                    'updated_count' => 0,
                    'errors' => []
                ];
            }

            $header = array_map(fn ($value) => trim(strtolower((string) $value), " \t\n\r\0\x0B\xEF\xBB\xBF"), $header);

            // Preload lookup tables to avoid N+1 queries
            $schoolCodesInFile = $this->schoolCodesFromRows($rows, $header, $examType);
            $schools = empty($schoolCodesInFile)
                ? collect()
                : School::whereIn('code', $schoolCodesInFile)
                    ->get()
                    ->mapWithKeys(function (School $school) {
                        return [strtoupper(trim((string) $school->code)) => $school];
                    });
            $acseeType = ExamType::where('code', 'ACSEE')->first();
            $resolvedExamYear = $this->resolveExamYear($examYear);

            // Preload only uploaded candidate IDs for batch checking.
            $candidateIdsInFile = $this->candidateIdsFromRows($rows, $header, $examType);
            $existingCandidateIds = empty($candidateIdsInFile)
                ? collect()
                : Candidate::whereIn('candidate_id', $candidateIdsInFile)->pluck('id', 'candidate_id');

            $rowNumber = 0;
            $importedCount = 0;
            $skippedCount = 0;
            $updatedCount = 0;
            $allocationsCreated = 0;
            $allocationsUpdated = 0;
            $allocationErrors = [];
            $errors = [];
            $warnings = [];
            $seenPsleSignals = [
                'prem_school' => [],
                'name_gender_school' => [],
            ];
            $chunk = []; // Batch records
            $chunkSize = 100; // Process in batches of 100

            foreach ($rows as $row) {
                $rowNumber++;

                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    $record = $this->mapRowToRecord($row, $header);
                    $record = $this->normalizeRecord($record, $examType);
                    $record['row_number'] = $rowNumber;

                    // Re-validate
                    $rowErrors = [];
                    $rowWarnings = [];
                    $this->validateCandidateId($record['candidate_id'] ?? null, $rowErrors, [], $rowNumber);
                    if ($this->isPsleImport($record, $examType)) {
                        $this->validatePsleCandidateNumber($record, $rowErrors);
                        $this->validatePremNo($record['prem_no'] ?? null, $rowErrors);
                    }
                    $this->validateFullName($record['full_name'] ?? null, $rowErrors);
                    $this->validateGender($record['gender'] ?? null, $rowErrors);
                    $this->validateSchoolReference($record, $rowErrors);

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

                    if ($this->isPsleImport($record, $examType)) {
                        $this->validatePsleWarnings($record, $rowWarnings, $seenPsleSignals);
                    }

                    if (!empty($rowErrors)) {
                        $errors[] = [
                            'row_number' => $rowNumber,
                            'candidate_id' => $record['candidate_id'] ?? '',
                            'prem_no' => $record['prem_no'] ?? '',
                            'error_messages' => $rowErrors
                        ];
                        continue;
                    }

                    if (!empty($rowWarnings)) {
                        $warnings[] = [
                            'row_number' => $rowNumber,
                            'candidate_id' => $record['candidate_id'] ?? '',
                            'prem_no' => $record['prem_no'] ?? '',
                            'warning_messages' => $rowWarnings,
                        ];
                    }

                    // Check if candidate exists using preloaded list
                    $candidateExists = isset($existingCandidateIds[$record['candidate_id']]);

                    if ($candidateExists) {
                        if ($mode === 'skip') {
                            $existingCandidate = Candidate::where('candidate_id', $record['candidate_id'])->first();
                            $schoolCode = strtoupper(trim((string) ($record['school_code'] ?? '')));
                            $school = $schools[$schoolCode] ?? null;
                            
                            if ($existingCandidate && $school) {
                                // Safeguard: check school mismatch
                                if ($existingCandidate->school_id !== $school->id) {
                                    $hasMarks = $existingCandidate->marks()->exists() || $existingCandidate->rawMarks()->exists();
                                    $candidatePrefix = strtoupper(trim(strtok($existingCandidate->candidate_id, '-')));
                                    
                                    if ($candidatePrefix === $school->code && !$hasMarks) {
                                        $existingCandidate->update(['school_id' => $school->id]);
                                        Log::info("Safe candidate school link repaired during skip-mode import", [
                                            'candidate_id' => $existingCandidate->candidate_id,
                                            'old_school_id' => $existingCandidate->getOriginal('school_id'),
                                            'new_school_id' => $school->id
                                        ]);
                                    } else {
                                        $reason = $hasMarks 
                                            ? "Candidate has marks under old school" 
                                            : "Candidate prefix {$candidatePrefix} does not match school code {$school->code}";
                                        $errors[] = [
                                            'row_number' => $rowNumber,
                                            'candidate_id' => $record['candidate_id'],
                                            'prem_no' => $record['prem_no'] ?? '',
                                            'error_messages' => ["Candidate already exists under a different school. Repair blocked: {$reason}"]
                                        ];
                                        continue;
                                    }
                                }
                                
                                // Ensure exam registration exists
                                if (in_array(strtoupper($finalExamType), ['PSLE', 'CSEE'], true) && $resolvedExamYear) {
                                    $standardType = ExamType::where('code', strtoupper($finalExamType))->first();
                                    if ($standardType) {
                                        $this->createExamRegistrationIfNotExists($existingCandidate, $standardType, $resolvedExamYear);
                                        if (strtoupper($finalExamType) === 'CSEE') {
                                            app(CseeCandidateSubjectService::class)->ensureCoreSubjects($existingCandidate, $resolvedExamYear);
                                        }
                                    }
                                }
                            }
                            $skippedCount++;
                            continue;
                        } elseif ($mode === 'replace') {
                            $existingCandidate = Candidate::where('candidate_id', $record['candidate_id'])->first();
                            $this->updateCandidate($existingCandidate, $record, $examYear, $examType);
                            $updatedCount++;
                            $importedCount++;
                            continue;
                        } elseif ($mode === 'stop') {
                            throw new \Exception('Import stopped because existing candidate numbers were detected.');
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
                        $errors = array_merge($errors, $result['errors'] ?? []);
                        $chunk = [];
                    }
                } catch (\Exception $e) {
                    Log::warning("Row $rowNumber import error: " . $e->getMessage());
                    $errors[] = [
                        'row_number' => $rowNumber,
                        'candidate_id' => $record['candidate_id'] ?? 'unknown',
                        'prem_no' => $record['prem_no'] ?? '',
                        'error_messages' => [$e->getMessage()]
                    ];
                }
            }

            // Process remaining chunk
            if (!empty($chunk)) {
                $result = $this->processBatch($chunk);
                $importedCount += $result['imported'];
                $allocationsCreated += $result['allocations'];
                $errors = array_merge($errors, $result['errors'] ?? []);
            }

            DB::commit();

            $message = "Imported $importedCount candidates"
                . ($updatedCount > 0 ? ", updated $updatedCount" : '')
                . ($skippedCount > 0 ? ", skipped $skippedCount" : '')
                . ($allocationsCreated > 0 ? ", allocated subjects for $allocationsCreated" : '');

            if (!empty($errors)) {
                $firstError = $errors[0]['error_messages'][0] ?? 'Unknown row error';
                $message .= ". First error: {$firstError}";
            }

            return [
                'success' => ($importedCount + $updatedCount) > 0 || count($errors) === 0,
                'message' => $message,
                'imported_count' => $importedCount,
                'skipped_count' => $skippedCount,
                'updated_count' => $updatedCount,
                'allocations_created_count' => $allocationsCreated,
                'allocations_updated_count' => $allocationsUpdated,
                'warning_count' => count($warnings),
                'warnings' => array_slice($warnings, 0, 100),
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
    private function readImportRows(UploadedFile $file): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $headers = [];
        $rows = [];

        if (in_array($extension, ['csv', 'txt'], true)) {
            $handle = fopen($file->getRealPath(), 'r');
            if (!$handle) {
                throw new \RuntimeException('Unable to read uploaded file.');
            }

            $headers = fgetcsv($handle) ?: [];
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = $row;
            }
            fclose($handle);

            return [$headers, $rows];
        }

        if ($extension === 'xls') {
            throw new \RuntimeException('Legacy .xls files cannot be read by this server. Please save the file as .xlsx or .csv and try again.');
        }

        if ($extension !== 'xlsx') {
            throw new \RuntimeException('Unsupported file format. Upload .xlsx, .xls, or .csv.');
        }

        if (!class_exists(ReaderEntityFactory::class)) {
            throw new \RuntimeException('Excel reader is not installed. Upload a CSV file or install the spreadsheet reader dependency.');
        }

        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open($file->getRealPath());

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $index => $row) {
                $values = array_map(fn ($cell) => trim((string) $cell->getValue()), $row->getCells());
                if ($index === 1) {
                    $headers = $values;
                    continue;
                }
                $rows[] = $values;
            }
            break;
        }

        $reader->close();

        return [$headers, $rows];
    }

    private function mapRowToRecord(array $row, array $headers): array
    {
        $record = [];
        foreach ($headers as $index => $header) {
            $record[$header] = trim($row[$index] ?? '');
        }

        if (empty($record['candidate_id']) && !empty($record['candidate_number'])) {
            $record['candidate_id'] = $record['candidate_number'];
        }

        if (empty($record['full_name']) && !empty($record['pupil_name'])) {
            $record['full_name'] = $record['pupil_name'];
        }

        if (empty($record['gender']) && !empty($record['sex'])) {
            $record['gender'] = $record['sex'];
        }

        if (empty($record['prem_no']) && !empty($record['prem no'])) {
            $record['prem_no'] = $record['prem no'];
        }

        if (empty($record['prem_no']) && !empty($record['premno'])) {
            $record['prem_no'] = $record['premno'];
        }

        if (empty($record['prem_no']) && !empty($record['prem_number'])) {
            $record['prem_no'] = $record['prem_number'];
        }

        if (empty($record['prem_no']) && !empty($record['national_id_or_upi'])) {
            $record['prem_no'] = $record['national_id_or_upi'];
        }

        if (empty($record['school_code']) && !empty($record['centre_number'])) {
            $record['school_code'] = $record['centre_number'];
        }

        if (empty($record['school_name']) && !empty($record['school/centre'])) {
            $record['school_name'] = $record['school/centre'];
        }

        return $record;
    }

    private function candidateIdsFromRows(array $rows, array $headers, ?string $examType = null): array
    {
        $ids = [];

        foreach ($rows as $row) {
            if (empty(array_filter($row))) {
                continue;
            }

            $record = $this->normalizeRecord($this->mapRowToRecord($row, $headers), $examType);
            if (!empty($record['candidate_id'])) {
                $ids[] = $record['candidate_id'];
            }
        }

        return array_values(array_unique($ids));
    }

    private function schoolCodesFromRows(array $rows, array $headers, ?string $examType = null): array
    {
        $codes = [];

        foreach ($rows as $row) {
            if (empty(array_filter($row))) {
                continue;
            }

            $record = $this->normalizeRecord($this->mapRowToRecord($row, $headers), $examType);
            if (!empty($record['school_code'])) {
                $codes[] = strtoupper((string) $record['school_code']);
            }
        }

        return array_values(array_unique($codes));
    }

    private function normalizeRecord(array $record, ?string $examType = null): array
    {
        $finalExamType = strtoupper(trim((string) ($record['exam_type'] ?? $examType ?? '')));
        $candidateId = trim((string) ($record['candidate_id'] ?? ''));

        if ($finalExamType === 'CSEE') {
            $record['candidate_type'] = 'SCHOOL';

            if ($candidateId !== '') {
                $record['school_code'] = strtoupper(substr($candidateId, 0, 5));
            }
        }

        if (!empty($record['school_code'])) {
            $record['school_code'] = strtoupper(trim((string) $record['school_code']));
        }

        if (empty($record['school_code']) && !empty($record['school_name'])) {
            $school = $this->findSchoolByReference((string) $record['school_name']);
            if ($school) {
                $record['school_code'] = strtoupper((string) $school->code);
            }
        }

        if (!empty($record['gender'])) {
            $record['gender'] = strtoupper(substr(trim((string) $record['gender']), 0, 1));
        }

        if (!empty($record['candidate_id'])) {
            $record['candidate_id'] = strtoupper(trim((string) $record['candidate_id']));
        }

        if (!empty($record['full_name'])) {
            $record['full_name'] = strtoupper(trim(preg_replace('/\s+/', ' ', (string) $record['full_name'])));
        }

        if (!empty($record['prem_no'])) {
            $record['prem_no'] = trim((string) $record['prem_no']);
        }

        if ($finalExamType !== '' && empty($record['exam_type'])) {
            $record['exam_type'] = $finalExamType;
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

    private function validatePsleCandidateNumber(array $record, array &$errors): void
    {
        $candidateNumber = strtoupper(trim((string) ($record['candidate_id'] ?? '')));
        if ($candidateNumber === '') {
            return;
        }

        if (!preg_match('/^PS\d{7}-\d{4}$/', $candidateNumber)) {
            $errors[] = 'Invalid Candidate Number format. Expected PS0404006-0001';
            return;
        }

        $schoolCode = strtoupper(trim((string) ($record['school_code'] ?? '')));
        if ($schoolCode !== '') {
            $candidatePrefix = strtok($candidateNumber, '-');
            if ($candidatePrefix !== $schoolCode) {
                $errors[] = "candidate_number prefix {$candidatePrefix} does not match school_code {$schoolCode}";
            }
        }
    }

    private function validatePremNo(?string $premNo, array &$errors): void
    {
        if (trim((string) $premNo) === '') {
            $errors[] = 'PReM_No is required';
        }
    }

    private function validateSchoolReference(array $record, array &$errors): void
    {
        $reference = $record['school_code'] ?? null;
        if (empty($reference)) {
            $errors[] = 'school_code is required';
            return;
        }

        if (!$this->findSchoolByReference((string) $reference)) {
            $errors[] = "Unknown School Code: {$reference}";
        }
    }

    private function findSchoolByReference(string $reference): ?School
    {
        $value = strtoupper(trim($reference));
        if ($value === '') {
            return null;
        }

        if (array_key_exists($value, $this->schoolReferenceCache)) {
            return $this->schoolReferenceCache[$value];
        }

        $school = School::with(['council.region', 'district.region', 'region'])
            ->whereRaw('UPPER(code) = ?', [$value])
            ->orWhereRaw('UPPER(registration_number) = ?', [$value])
            ->orWhereRaw('UPPER(name) = ?', [$value])
            ->first();

        $this->schoolReferenceCache[$value] = $school;

        return $school;
    }

    private function schoolPreviewContext(array $record): array
    {
        $school = $this->findSchoolByReference((string) ($record['school_code'] ?? ''));

        return [
            'school_name' => $school?->name ?? '',
            'council_name' => $school?->council?->name ?? $school?->district?->name ?? '',
            'region_name' => $school?->region?->name ?? $school?->council?->region?->name ?? $school?->district?->region?->name ?? '',
        ];
    }

    private function missingRequiredHeaders(array $header, string $examType): array
    {
        if ($examType !== 'PSLE') {
            return [];
        }

        $hasAny = fn (array $names): bool => count(array_intersect($names, $header)) > 0;
        $missing = [];

        if (!$hasAny(['candidate_number', 'candidate_id'])) {
            $missing[] = 'candidate_number';
        }
        if (!$hasAny(['prem_no', 'prem no', 'premno', 'prem_number'])) {
            $missing[] = 'PReM_No';
        }
        if (!$hasAny(['pupil_name', 'full_name'])) {
            $missing[] = 'pupil_name';
        }
        if (!$hasAny(['sex', 'gender'])) {
            $missing[] = 'sex';
        }
        if (!$hasAny(['school_code'])) {
            $missing[] = 'school_code';
        }

        return $missing;
    }

    private function previewMessage(string $status, array $errors, array $warnings): string
    {
        if (!empty($errors)) {
            return reset($errors) ?: 'Invalid row';
        }

        if ($status === 'SKIP') {
            return 'Already exists; will be skipped';
        }

        if ($status === 'REPLACE') {
            return 'Already exists; will be updated';
        }

        if (!empty($warnings)) {
            return reset($warnings) ?: 'Warning detected';
        }

        return 'Ready for import';
    }

    private function buildValidationSummary(array $rows, array $errors, array $warnings, int $createCount, int $updateCount, int $skipCount): array
    {
        $countErrorsLike = function (string $needle) use ($errors): int {
            return collect($errors)->filter(function ($row) use ($needle) {
                return str_contains(strtolower(implode(' ', $row['error_messages'] ?? [])), $needle);
            })->count();
        };

        return [
            'total_rows' => count($rows),
            'valid_rows' => $createCount + $updateCount,
            'duplicates_in_file' => $countErrorsLike('duplicated within this file'),
            'already_existing' => $skipCount + $updateCount,
            'invalid_rows' => count($errors),
            'warnings' => count($warnings),
            'missing_candidate_number' => $countErrorsLike('candidate_id is required'),
            'missing_prem_no' => $countErrorsLike('prem_no is required'),
            'missing_pupil_name' => $countErrorsLike('full_name is required'),
            'invalid_sex' => $countErrorsLike('gender must be') + $countErrorsLike('gender is required'),
            'missing_school_centre' => $countErrorsLike('school_code is required'),
            'unknown_school_code' => $countErrorsLike('unknown school code'),
            'invalid_council' => 0,
            'new_rows' => $createCount,
            'update_rows' => $updateCount,
            'skip_rows' => $skipCount,
        ];
    }

    private function isPsleImport(array $record, ?string $examType): bool
    {
        $finalExamType = strtoupper(trim((string) ($record['exam_type'] ?? $examType ?? '')));

        return $finalExamType === 'PSLE';
    }

    private function validatePsleWarnings(array $record, array &$warnings, array &$seenSignals): void
    {
        $schoolCode = strtoupper(trim((string) ($record['school_code'] ?? '')));
        if ($schoolCode === '') {
            return;
        }

        $school = School::where('code', $schoolCode)->first();
        if (!$school) {
            return;
        }

        $candidateId = strtoupper(trim((string) ($record['candidate_id'] ?? '')));
        $premNo = strtoupper(trim((string) ($record['prem_no'] ?? '')));
        $fullName = trim((string) ($record['full_name'] ?? ''));
        $gender = strtoupper(substr(trim((string) ($record['gender'] ?? '')), 0, 1));

        if ($candidateId !== '' && str_contains($candidateId, '-')) {
            $candidatePrefix = strtoupper(trim((string) strtok($candidateId, '-')));
            if ($candidatePrefix !== '' && $candidatePrefix !== $schoolCode) {
                $warnings[] = "candidate_number prefix {$candidatePrefix} does not match school_code {$schoolCode}";
            }
        }

        if ($premNo !== '') {
            $premKey = $schoolCode . '|' . $premNo;
            if (isset($seenSignals['prem_school'][$premKey])) {
                $warnings[] = "duplicate PReM_No detected in this file for school {$schoolCode}";
            } else {
                $seenSignals['prem_school'][$premKey] = true;
            }

            // Database-level PReM uniqueness is not enforced in the current schema; avoid row-by-row lookups here.
        }

        if ($fullName !== '' && $gender !== '') {
            $identityKey = $schoolCode . '|' . strtoupper($fullName) . '|' . $gender;
            if (isset($seenSignals['name_gender_school'][$identityKey])) {
                $warnings[] = "possible duplicate pupil in this file by name, sex, and school";
            } else {
                $seenSignals['name_gender_school'][$identityKey] = true;
            }

            // Existing identity checks are intentionally omitted from the preview to keep large CSV validation responsive.
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
            'prem_no' => $record['prem_no'] ?? null,
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
        } elseif ($finalExamType === 'CSEE') {
            $resolvedExamYear = $this->resolveExamYear($examYear);
            $cseeType = ExamType::where('code', 'CSEE')->first();
            if ($resolvedExamYear && $cseeType) {
                $this->createExamRegistrationIfNotExists($candidate, $cseeType, $resolvedExamYear);
                app(CseeCandidateSubjectService::class)->ensureCoreSubjects($candidate, $resolvedExamYear);
            }
        } elseif ($finalExamType === 'PSLE') {
            $resolvedExamYear = $this->resolveExamYear($examYear);
            $psleType = ExamType::where('code', 'PSLE')->first();
            if ($resolvedExamYear && $psleType) {
                $this->createExamRegistrationIfNotExists($candidate, $psleType, $resolvedExamYear);
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

        // Safeguard: Check if school is changing and candidate already has marks
        if ($candidate->school_id !== $school->id) {
            $hasMarks = $candidate->marks()->exists() || $candidate->rawMarks()->exists();
            if ($hasMarks) {
                throw new \Exception("Cannot update candidate school link for {$candidate->candidate_id} because the candidate has existing marks under their current school.");
            }
        }

        // Safe update: name, gender, school. For replace mode we also update combination
        $updateData = [
            'school_id' => $school->id,
            'full_name' => $record['full_name'],
            'prem_no' => $record['prem_no'] ?? null,
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

        $candidateType = strtoupper($record['candidate_type'] ?? $candidate->candidate_type ?? 'SCHOOL');
        $finalExamType = strtoupper($record['exam_type'] ?? $examType ?? 'ACSEE');
        if ($finalExamType === 'ACSEE') {
            if ($candidateType === 'PRIVATE' && !empty($record['subjects'])) {
                $resolvedExamYear = $this->resolveExamYear($examYear);
                $acseeType = ExamType::where('code', 'ACSEE')->first();
                if ($resolvedExamYear && $acseeType) {
                    $allocationErrors = [];
                    $this->allocateSubjectsForPrivateCandidate($candidate, $record['subjects'], $acseeType, $resolvedExamYear, $allocationErrors);
                }
            }
        }

        if (in_array($finalExamType, ['PSLE', 'CSEE'], true)) {
            $resolvedExamYear = $this->resolveExamYear($examYear);
            $targetType = ExamType::where('code', $finalExamType)->first();
            if ($resolvedExamYear && $targetType) {
                $this->createExamRegistrationIfNotExists($candidate, $targetType, $resolvedExamYear);
                if ($finalExamType === 'CSEE') {
                    app(CseeCandidateSubjectService::class)->ensureCoreSubjects($candidate, $resolvedExamYear);
                }
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
        $errors = [];

        foreach ($batch as $item) {
            try {
                $record = $item['record'];
                $schools = $item['schools'];
                $acseeType = $item['acseeType'];
                $examYear = $item['examYear'];
                $examType = $item['examType'];
                $schoolCode = strtoupper(trim((string) ($record['school_code'] ?? '')));
                $school = $schools[$schoolCode] ?? null;
                if (!$school) {
                    Log::warning("School not found: {$schoolCode}");
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
                $candidate = $this->retryIfDatabaseLocked(fn () => Candidate::create([
                    'school_id' => $school->id,
                    'candidate_id' => $record['candidate_id'],
                    'prem_no' => $record['prem_no'] ?? null,
                    'full_name' => $record['full_name'],
                    'gender' => strtoupper($record['gender'][0] ?? 'M'),
                    'exam_type' => $examType,
                    'combination' => $comboCode,
                    'combination_id' => $comboId,
                    'candidate_type' => $candidateType,
                    'status' => 'registered',
                    'is_active' => true,
                ]));

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
                } elseif (in_array(strtoupper($examType), ['PSLE', 'CSEE'], true) && $examYear) {
                    $standardType = ExamType::where('code', strtoupper($examType))->first();
                    if ($standardType) {
                        $this->createExamRegistrationIfNotExists($candidate, $standardType, $examYear);
                        if (strtoupper($examType) === 'CSEE') {
                            app(CseeCandidateSubjectService::class)->ensureCoreSubjects($candidate, $examYear);
                        }
                    }
                }

                if (strtoupper($examType) === 'PSLE') {
                    Log::info('PSLE pupil import saved candidate', [
                        'candidate_number' => $candidate->candidate_id,
                        'pupil_name' => $candidate->full_name,
                        'school_id' => $candidate->school_id,
                        'school_code' => $school->code,
                        'exam_year_id' => $examYear?->id,
                        'exam_type' => $candidate->exam_type,
                        'status' => $candidate->status,
                    ]);
                }

                $imported++;
            } catch (Throwable $e) {
                Log::error("Batch process error: " . $e->getMessage(), [
                    'record' => $record['candidate_id'] ?? 'unknown'
                ]);
                $errors[] = [
                    'row_number' => $record['row_number'] ?? null,
                    'candidate_id' => $record['candidate_id'] ?? 'unknown',
                    'prem_no' => $record['prem_no'] ?? '',
                    'error_messages' => [$e->getMessage()],
                ];
            }
        }

        return ['imported' => $imported, 'allocations' => $allocations, 'errors' => $errors];
    }

    private function prepareSqliteForBulkWrite(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        try {
            DB::statement('PRAGMA busy_timeout = 30000');
        } catch (Throwable $e) {
            Log::warning('Unable to set SQLite busy timeout for candidate import', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function retryIfDatabaseLocked(callable $callback, int $attempts = 6)
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return $callback();
            } catch (Throwable $e) {
                $lastException = $e;
                $message = strtolower($e->getMessage());

                if (strpos($message, 'database is locked') === false || $attempt === $attempts) {
                    throw $e;
                }

                usleep(250000 * $attempt);
            }
        }

        throw $lastException;
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

        $legacyByYear = CandidateExamRegistration::where('candidate_id', $candidate->id)
            ->where('exam_type_id', $examType->id)
            ->where('year', (int) $examYear->year_label)
            ->first();

        if ($legacyByYear) {
            $legacyByYear->exam_year_id = $examYear->id;
            $legacyByYear->year = (int) $examYear->year_label;
            $legacyByYear->status = $legacyByYear->status ?: 'PENDING';
            $legacyByYear->save();
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

            // Run NECTA validation to match test expectations
            $validator = new \App\Services\AcseeAllocationValidator();
            $result = $validator->validate($candidate, $examType->id, $examYear->id, $subjectIds);

            if (!$result['ok']) {
                $errors = array_merge($errors, $result['errors']);
                return 0;
            }

            // Delete existing allocations for this candidate+exam type+exam year (replace mode)
            CandidateSubjectSelection::where('candidate_id', $candidate->id)
                ->where('exam_type_id', $examType->id)
                ->where('exam_year_id', $examYear->id)
                ->delete();

            // Build allocations from resolved subject IDs
            $now = now();
            $allocations = [];

            foreach ($result['all_subject_ids'] as $subjectId) {
                $allocations[] = [
                    'candidate_id' => $candidate->id,
                    'exam_type_id' => $examType->id,
                    'exam_year_id' => $examYear->id,
                    'subject_id' => $subjectId,
                    'year' => (int)$examYear->year_label,
                    'is_principal' => in_array($subjectId, $result['principal_subject_ids']),
                    'source' => 'import',
                    'created_by' => \Illuminate\Support\Facades\Auth::id() ?? 1,
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
