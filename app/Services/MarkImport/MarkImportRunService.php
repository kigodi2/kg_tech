<?php

namespace App\Services\MarkImport;

use App\Models\MarkImportRun;
use App\Models\MarkImportRunError;
use App\Models\MarkImportRunRow;
use App\Models\MarkImportBatch;
use App\Models\Candidate;
use App\Models\Subject;
use App\Models\School;
use App\Models\ExamType;
use App\Models\ExamYear;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarkImportRunService
{
    private AcseeMarkTemplateService $templateService;

    public function __construct(AcseeMarkTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Start a new import run for any upload type.
     */
    public function startRun(
        int $userId,
        int $examYearId,
        ?int $schoolId,
        ?int $subjectId,
        string $scopeType,
        string $fileName,
        ?int $fileSize = null,
        ?int $regionId = null,
        ?int $districtId = null
    ): MarkImportRun {
        return MarkImportRun::create([
            'correlation_id' => (string) Str::uuid(),
            'user_id' => $userId,
            'exam_year_id' => $examYearId,
            'school_id' => $schoolId ?? 0,
            'subject_id' => $subjectId ?? 0,
            'scope' => $scopeType === 'single_subject' ? 'school' : ($scopeType === 'school_zip' ? 'school' : 'district'),
            'scope_type' => $scopeType,
            'file_name' => $fileName,
            'original_file_name' => $fileName,
            'file_size' => $fileSize,
            'status' => MarkImportRun::STATUS_PROCESSING,
            'started_at' => now(),
            'region_id' => $regionId,
            'district_id' => $districtId,
        ]);
    }

    /**
     * Validate a single CSV file and populate structured errors and preview rows.
     * Returns ['valid' => bool, 'errors' => [...], 'preview' => [...], 'totals' => [...]]
     */
    public function validateSingleCsv(
        MarkImportRun $run,
        UploadedFile $file,
        int $examYearValue,
        int $schoolId,
        int $subjectId
    ): array {
        $errors = [];
        $previewRows = [];
        $validCount = 0;
        $warningCount = 0;
        $blockingCount = 0;

        // --- FILE-LEVEL VALIDATION ---
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'txt'])) {
            $this->addRunError($run, 0, null, null, null, null, 'INVALID_FILE_TYPE', 'error',
                "Invalid file type '.{$ext}'. Only CSV and TXT files are accepted.", $ext);
            $run->fail("File type validation failed: .{$ext}");
            return $this->buildResult($run, 0, 0, 1, 0);
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            $this->addRunError($run, 0, null, null, null, null, 'FILE_TOO_LARGE', 'error',
                'File exceeds 5MB limit (' . round($file->getSize() / 1024 / 1024, 1) . 'MB).', (string) $file->getSize());
            $run->fail("File too large");
            return $this->buildResult($run, 0, 0, 1, 0);
        }

        // Read and parse CSV
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            $this->addRunError($run, 0, null, null, null, null, 'FILE_UNREADABLE', 'error',
                'Cannot open file for reading.', null);
            $run->fail("File unreadable");
            return $this->buildResult($run, 0, 0, 1, 0);
        }

        // Get subject
        $subject = Subject::find($subjectId);
        if (!$subject) {
            fclose($handle);
            $this->addRunError($run, 0, null, $subjectId, null, null, 'INVALID_SUBJECT', 'error',
                "Subject ID {$subjectId} not found.", (string) $subjectId);
            $run->fail("Subject not found");
            return $this->buildResult($run, 0, 0, 1, 0);
        }

        // Get expected headers
        $paperStructure = [
            'written_papers' => $subject->written_papers ?? 2,
            'has_practical' => (bool) ($subject->has_practical ?? false),
            'has_project' => (bool) ($subject->has_project ?? false),
        ];
        $expectedHeaders = $this->getExpectedHeaders($paperStructure);

        // Read header row
        $headerRow = fgetcsv($handle);
        if (!$headerRow) {
            fclose($handle);
            $this->addRunError($run, 0, null, $subjectId, null, null, 'EMPTY_FILE', 'error',
                'CSV file is empty or has no header row.', null);
            $run->fail("Empty CSV file");
            return $this->buildResult($run, 0, 0, 1, 0);
        }

        $actualHeaders = array_map(fn($h) => strtolower(trim($h)), $headerRow);

        // Header validation
        if (count($actualHeaders) !== count($expectedHeaders)) {
            $this->addRunError($run, 0, null, $subjectId, null, null, 'HEADER_MISMATCH', 'error',
                'Expected ' . count($expectedHeaders) . ' columns (' . implode(', ', $expectedHeaders) . ') but found ' . count($actualHeaders) . '.',
                implode(',', $actualHeaders));
            fclose($handle);
            $run->fail("Header column count mismatch");
            return $this->buildResult($run, 0, 0, 1, 0);
        }

        // Check for unknown/reordered headers
        foreach ($expectedHeaders as $i => $expected) {
            if (!isset($actualHeaders[$i]) || $actualHeaders[$i] !== $expected) {
                $this->addRunError($run, 0, null, $subjectId, null, $expected, 'HEADER_MISMATCH', 'error',
                    "Column " . ($i + 1) . " should be '{$expected}' but found '" . ($actualHeaders[$i] ?? 'MISSING') . "'.",
                    $actualHeaders[$i] ?? null);
                // Don't immediately fail — collect all header errors
                $blockingCount++;
            }
        }

        if ($blockingCount > 0) {
            fclose($handle);
            $run->fail("Header structure invalid");
            return $this->buildResult($run, 0, 0, $blockingCount, 0);
        }

        // --- DUPLICATE FILE CHECK (checksum) ---
        $fileChecksum = hash_file('sha256', $file->getRealPath());
        $run->update(['checksum' => $fileChecksum]);

        $existingRun = MarkImportRun::where('checksum', $fileChecksum)
            ->where('exam_year_id', $run->exam_year_id)
            ->where('school_id', $schoolId)
            ->where('subject_id', $subjectId)
            ->where('id', '!=', $run->id)
            ->where('status', 'completed')
            ->first();

        if ($existingRun) {
            $this->addRunError($run, 0, null, $subjectId, null, null, 'DUPLICATE_UPLOAD', 'warning',
                "This exact file was already uploaded on {$existingRun->completed_at?->format('Y-m-d H:i')} (Run #{$existingRun->id}). Re-uploading may create duplicate batches.",
                $fileChecksum);
            $warningCount++;
        }

        // --- ROW-LEVEL + FIELD-LEVEL VALIDATION ---
        // Pre-load candidate map for this school
        $acsee = ExamType::where('code', 'ACSEE')->first();
        $examYear = ExamYear::find($run->exam_year_id);
        $examYearLabel = $examYear ? (int) $examYear->year_label : $examYearValue;

        $registeredCandidates = [];
        if ($acsee && $examYear) {
            $registeredCandidates = DB::table('candidates')
                ->join('candidate_exam_registrations', function ($j) use ($acsee, $examYear) {
                    $j->on('candidates.id', '=', 'candidate_exam_registrations.candidate_id')
                      ->where('candidate_exam_registrations.exam_type_id', $acsee->id)
                      ->where('candidate_exam_registrations.exam_year_id', $examYear->id);
                })
                ->where('candidates.school_id', $schoolId)
                ->pluck('candidates.id', 'candidates.candidate_id')
                ->toArray();
        }

        // Check for locked conflicts
        $lockedBatch = MarkImportBatch::where('school_id', $schoolId)
            ->where('subject_id', $subjectId)
            ->where('exam_year', $examYearLabel)
            ->whereIn('status', [MarkImportBatch::STATUS_LOCKED, MarkImportBatch::STATUS_APPROVED])
            ->first();

        if ($lockedBatch) {
            $this->addRunError($run, 0, null, $subjectId, null, null, 'LOCKED_CONFLICT', 'error',
                "Marks for this school/subject/year are already " . strtoupper($lockedBatch->status) . " in batch {$lockedBatch->batch_code}. Cannot overwrite locked/approved marks.",
                $lockedBatch->batch_code);
            fclose($handle);
            $run->fail("Locked/approved conflict");
            return $this->buildResult($run, 0, 0, 1, $warningCount);
        }

        $rowNumber = 0;
        $seenIndexNumbers = [];
        $rowsToInsert = [];
        $errorsToInsert = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Skip empty rows
            if (empty(array_filter($row, fn($v) => trim($v ?? '') !== ''))) {
                continue;
            }

            $indexNumber = trim($row[0] ?? '');
            $rowValid = true;
            $rowWarnings = 0;

            // FIELD: index_number - required
            if (empty($indexNumber)) {
                $errorsToInsert[] = [
                    'run_id' => $run->id, 'row_number' => $rowNumber, 'index_number' => null,
                    'subject_id' => $subjectId, 'paper' => null, 'column_name' => 'index_number',
                    'error_code' => 'MISSING_REQUIRED', 'message' => 'Index number is missing.',
                    'raw_value' => null, 'severity' => 'error', 'created_at' => now(), 'updated_at' => now(),
                ];
                $rowValid = false;
            }

            // FIELD: index_number format (e.g., S0101/0001)
            if (!empty($indexNumber) && !preg_match('/^[A-Z0-9]{1,2}\d{3,4}\/\d{4}$/i', $indexNumber)) {
                $errorsToInsert[] = [
                    'run_id' => $run->id, 'row_number' => $rowNumber, 'index_number' => $indexNumber,
                    'subject_id' => $subjectId, 'paper' => null, 'column_name' => 'index_number',
                    'error_code' => 'INVALID_INDEX', 'message' => "Index number format invalid: '{$indexNumber}'. Expected format like S0101/0001.",
                    'raw_value' => $indexNumber, 'severity' => 'error', 'created_at' => now(), 'updated_at' => now(),
                ];
                $rowValid = false;
            }

            // Duplicate within file
            if (!empty($indexNumber) && isset($seenIndexNumbers[$indexNumber])) {
                $errorsToInsert[] = [
                    'run_id' => $run->id, 'row_number' => $rowNumber, 'index_number' => $indexNumber,
                    'subject_id' => $subjectId, 'paper' => null, 'column_name' => 'index_number',
                    'error_code' => 'DUPLICATE', 'message' => "Duplicate index number in file. First appeared on row {$seenIndexNumbers[$indexNumber]}.",
                    'raw_value' => $indexNumber, 'severity' => 'error', 'created_at' => now(), 'updated_at' => now(),
                ];
                $rowValid = false;
            }
            if (!empty($indexNumber)) {
                $seenIndexNumbers[$indexNumber] = $rowNumber;
            }

            // Candidate registered check
            $candidateId = null;
            if (!empty($indexNumber) && $rowValid) {
                $candidateId = $registeredCandidates[$indexNumber] ?? null;
                if ($candidateId === null) {
                    $errorsToInsert[] = [
                        'run_id' => $run->id, 'row_number' => $rowNumber, 'index_number' => $indexNumber,
                        'subject_id' => $subjectId, 'paper' => null, 'column_name' => 'index_number',
                        'error_code' => 'NOT_REGISTERED',
                        'message' => "Candidate '{$indexNumber}' is not registered for ACSEE " . ($examYear->year_label ?? $examYearValue) . " at this school.",
                        'raw_value' => $indexNumber, 'severity' => 'error', 'created_at' => now(), 'updated_at' => now(),
                    ];
                    $rowValid = false;
                }
            }

            // Mark columns validation — NECTA-aligned 3-state paper detection
            $colIdx = 2; // after index_number and sex
            $paper1 = null; $paper2 = null; $paper3 = null; $practical = null; $project = null;
            $requiredComponents = [];
            $filledComponents = [];
            $missingComponents = [];
            $nonMissingErrors = []; // range/format errors for filled papers

            for ($p = 1; $p <= ($paperStructure['written_papers'] ?? 2); $p++) {
                $val = trim($row[$colIdx] ?? '');
                $paperName = "paper_p{$p}";
                $requiredComponents[] = $paperName;

                if ($val === '' || $val === null) {
                    $missingComponents[] = $paperName;
                    // Value stays null — do NOT generate MISSING_REQUIRED yet
                } else {
                    $result = $this->validateMarkField($val, $paperName, $rowNumber, $indexNumber, $subjectId, $run->id);
                    if ($result['error'] && $result['error']['error_code'] !== 'MISSING_REQUIRED') {
                        $nonMissingErrors[] = $result['error'];
                    }
                    $filledComponents[] = $paperName;
                    if ($p === 1) $paper1 = $result['value'] ?? null;
                    elseif ($p === 2) $paper2 = $result['value'] ?? null;
                    elseif ($p === 3) $paper3 = $result['value'] ?? null;
                }
                $colIdx++;
            }

            if ($paperStructure['has_practical']) {
                $val = trim($row[$colIdx] ?? '');
                $requiredComponents[] = 'practical';
                if ($val === '' || $val === null) {
                    $missingComponents[] = 'practical';
                } else {
                    $result = $this->validateMarkField($val, 'practical', $rowNumber, $indexNumber, $subjectId, $run->id, 50);
                    if ($result['error'] && $result['error']['error_code'] !== 'MISSING_REQUIRED') {
                        $nonMissingErrors[] = $result['error'];
                    }
                    $filledComponents[] = 'practical';
                    $practical = $result['value'] ?? null;
                }
                $colIdx++;
            }

            if ($paperStructure['has_project']) {
                $val = trim($row[$colIdx] ?? '');
                $requiredComponents[] = 'project';
                if ($val === '' || $val === null) {
                    $missingComponents[] = 'project';
                } else {
                    $result = $this->validateMarkField($val, 'project', $rowNumber, $indexNumber, $subjectId, $run->id);
                    if ($result['error'] && $result['error']['error_code'] !== 'MISSING_REQUIRED') {
                        $nonMissingErrors[] = $result['error'];
                    }
                    $filledComponents[] = 'project';
                    $project = $result['value'] ?? null;
                }
            }

            // 3-state paper presence detection
            if (count($missingComponents) === count($requiredComponents) && count($requiredComponents) > 0) {
                // STATE A: ALL_PAPERS_MISSING → warning (subject absent 'X')
                $errorsToInsert[] = [
                    'run_id' => $run->id, 'row_number' => $rowNumber, 'index_number' => $indexNumber,
                    'subject_id' => $subjectId, 'paper' => null, 'column_name' => null,
                    'error_code' => 'SUBJECT_ABSENT_X',
                    'message' => "All required papers missing (" . implode(', ', $missingComponents) . "). Candidate marked as 'X' (did not appear).",
                    'raw_value' => null, 'severity' => 'warning',
                    'is_actionable' => false, 'is_resolved' => false,
                    'created_at' => now(), 'updated_at' => now(),
                ];
                $rowWarnings++;
            } elseif (!empty($missingComponents) && !empty($filledComponents)) {
                // STATE B: PARTIAL_PAPERS_MISSING → actionable warning (INC candidate, non-blocking for moderation)
                $errorsToInsert[] = [
                    'run_id' => $run->id, 'row_number' => $rowNumber, 'index_number' => $indexNumber,
                    'subject_id' => $subjectId, 'paper' => implode(',', $missingComponents), 'column_name' => null,
                    'error_code' => 'MISSING_REQUIRED_PAPER_MARK',
                    'message' => "Incomplete: Missing required paper(s): " . implode(', ', $missingComponents)
                        . ". Has marks for: " . implode(', ', $filledComponents)
                        . ". Choose 'Accept as INC' or 'Reject' in moderation.",
                    'raw_value' => implode(',', $filledComponents), 'severity' => 'warning',
                    'is_actionable' => true, 'is_resolved' => false,
                    'created_at' => now(), 'updated_at' => now(),
                ];
                $rowWarnings++;
            }
            // STATE C: NO_MISSING → continue with range/format errors from filled papers

            // Add any non-missing errors (range, format) for filled papers
            foreach ($nonMissingErrors as $nme) {
                $errorsToInsert[] = $nme;
                if ($nme['severity'] === 'error') $rowValid = false;
                else $rowWarnings++;
            }

            // Suspicious values warning
            $allMarks = array_filter([$paper1, $paper2, $paper3, $practical, $project], fn($v) => $v !== null);
            if (count($allMarks) > 1 && count(array_unique($allMarks)) === 1 && ($allMarks[0] == 0 || $allMarks[0] == 100)) {
                $errorsToInsert[] = [
                    'run_id' => $run->id, 'row_number' => $rowNumber, 'index_number' => $indexNumber,
                    'subject_id' => $subjectId, 'paper' => null, 'column_name' => null,
                    'error_code' => 'SUSPICIOUS_VALUES', 'message' => "All paper marks are identical ({$allMarks[0]}). Please verify this is correct.",
                    'raw_value' => implode(',', $allMarks), 'severity' => 'warning', 'created_at' => now(), 'updated_at' => now(),
                ];
                $rowWarnings++;
            }

            // Build preview row
            $total = null;
            if (!empty($allMarks)) {
                $total = round(array_sum($allMarks) / count($allMarks), 2);
            }

            $rowsToInsert[] = [
                'run_id' => $run->id, 'row_number' => $rowNumber, 'source_file' => null,
                'index_number' => $indexNumber, 'candidate_id' => $candidateId,
                'school_id' => $schoolId, 'subject_id' => $subjectId,
                'paper_1' => $paper1, 'paper_2' => $paper2, 'paper_3' => $paper3,
                'practical' => $practical, 'project' => $project, 'total' => $total,
                'is_valid' => $rowValid, 'status' => 'pending', 'created_at' => now(),
            ];

            if ($rowValid) $validCount++;
            else $blockingCount++;
            $warningCount += $rowWarnings;
        }

        fclose($handle);

        // Bulk insert rows and errors
        if (!empty($rowsToInsert)) {
            foreach (array_chunk($rowsToInsert, 500) as $chunk) {
                DB::table('mark_import_run_rows')->insert($chunk);
            }
        }
        if (!empty($errorsToInsert)) {
            foreach (array_chunk($errorsToInsert, 500) as $chunk) {
                DB::table('mark_import_run_errors')->insert($chunk);
            }
        }

        // Update run totals
        $totalRows = $rowNumber;
        $status = $blockingCount > 0 ? ($validCount > 0 ? 'partial' : 'failed') : 'completed';
        // Note: MarkImportRun only has 'completed' and 'failed' for now — use completed for partial too
        if ($status === 'partial') $status = 'completed';

        $run->update([
            'total_rows' => $totalRows,
            'success_rows' => $validCount,
            'error_rows' => $blockingCount,
            'warning_rows' => $warningCount,
            'status' => $status,
            'completed_at' => now(),
            'summary' => "{$validCount}/{$totalRows} rows valid, {$blockingCount} errors, {$warningCount} warnings",
        ]);

        return $this->buildResult($run, $totalRows, $validCount, $blockingCount, $warningCount);
    }

    /**
     * Validate a single mark field.
     * Returns ['value' => float|null, 'error' => array|null]
     */
    private function validateMarkField(string $val, string $columnName, int $rowNumber, ?string $indexNumber, int $subjectId, int $runId, int $maxMark = 100): array
    {
        if ($val === '' || $val === null) {
            return [
                'value' => null,
                'error' => [
                    'run_id' => $runId, 'row_number' => $rowNumber, 'index_number' => $indexNumber,
                    'subject_id' => $subjectId, 'paper' => $columnName, 'column_name' => $columnName,
                    'error_code' => 'MISSING_REQUIRED', 'message' => "Mark value for '{$columnName}' is empty.",
                    'raw_value' => null, 'severity' => 'error', 'created_at' => now(), 'updated_at' => now(),
                ],
            ];
        }

        if (!is_numeric($val)) {
            return [
                'value' => null,
                'error' => [
                    'run_id' => $runId, 'row_number' => $rowNumber, 'index_number' => $indexNumber,
                    'subject_id' => $subjectId, 'paper' => $columnName, 'column_name' => $columnName,
                    'error_code' => 'NON_NUMERIC', 'message' => "'{$columnName}' must be a number (got: '{$val}').",
                    'raw_value' => $val, 'severity' => 'error', 'created_at' => now(), 'updated_at' => now(),
                ],
            ];
        }

        $numVal = (float) $val;

        if ($numVal < 0 || $numVal > $maxMark) {
            return [
                'value' => $numVal,
                'error' => [
                    'run_id' => $runId, 'row_number' => $rowNumber, 'index_number' => $indexNumber,
                    'subject_id' => $subjectId, 'paper' => $columnName, 'column_name' => $columnName,
                    'error_code' => 'OUT_OF_RANGE', 'message' => "'{$columnName}' must be 0–{$maxMark} (got: {$numVal}).",
                    'raw_value' => $val, 'severity' => 'error', 'created_at' => now(), 'updated_at' => now(),
                ],
            ];
        }

        return ['value' => $numVal, 'error' => null];
    }

    /**
     * Add a single error to a run.
     */
    public function addRunError(
        MarkImportRun $run,
        int $rowNumber,
        ?string $indexNumber,
        ?int $subjectId,
        ?string $paper,
        ?string $columnName,
        string $errorCode,
        string $severity,
        string $message,
        ?string $rawValue,
        ?string $sourceFile = null
    ): MarkImportRunError {
        return MarkImportRunError::create([
            'run_id' => $run->id,
            'row_number' => $rowNumber,
            'source_file' => $sourceFile,
            'index_number' => $indexNumber,
            'subject_id' => $subjectId,
            'paper' => $paper,
            'column_name' => $columnName,
            'error_code' => $errorCode,
            'severity' => $severity,
            'message' => $message,
            'raw_value' => $rawValue,
        ]);
    }

    /**
     * Build a structured result array from a run.
     */
    public function buildResult(MarkImportRun $run, int $totalRows, int $validRows, int $errorRows, int $warningRows): array
    {
        $errors = $run->errors()
            ->orderBy('row_number')
            ->limit(50)
            ->get()
            ->map(fn($e) => [
                'row' => $e->row_number,
                'index_number' => $e->index_number,
                'field' => $e->column_name ?? $e->paper,
                'code' => $e->error_code,
                'severity' => $e->severity,
                'message' => $e->message,
                'raw_value' => $e->raw_value,
            ])
            ->toArray();

        $preview = MarkImportRunRow::where('run_id', $run->id)
            ->orderBy('row_number')
            ->limit(20)
            ->get()
            ->map(fn($r) => [
                'row' => $r->row_number,
                'index_number' => $r->index_number,
                'has_errors' => !$r->is_valid,
                'messages' => $run->errors()
                    ->where('row_number', $r->row_number)
                    ->pluck('message')
                    ->toArray(),
            ])
            ->toArray();

        $canCommit = $validRows > 0 && $run->status !== MarkImportRun::STATUS_FAILED;

        return [
            'success' => true,
            'run_id' => $run->id,
            'correlation_id' => $run->correlation_id,
            'can_commit' => $canCommit,
            'status' => $errorRows > 0 ? ($validRows > 0 ? 'partial' : 'failed') : 'completed',
            'totals' => [
                'total_rows' => $totalRows,
                'valid_rows' => $validRows,
                'invalid_rows' => $errorRows,
                'warnings' => $warningRows,
            ],
            'errors' => $errors,
            'preview' => $preview,
        ];
    }

    /**
     * Generate CSV content for error report download.
     */
    public function generateErrorCsv(MarkImportRun $run): string
    {
        $csv = "Row,Index Number,Subject,Field,Error Code,Severity,Message,Raw Value\n";

        $errors = $run->errors()
            ->with('subject')
            ->orderBy('row_number')
            ->get();

        foreach ($errors as $e) {
            $subjectName = $e->subject ? $e->subject->code : ($e->subject_id ?? '');
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $e->row_number,
                str_replace('"', '""', $e->index_number ?? ''),
                str_replace('"', '""', $subjectName),
                str_replace('"', '""', $e->column_name ?? $e->paper ?? ''),
                $e->error_code,
                $e->severity,
                str_replace('"', '""', $e->message),
                str_replace('"', '""', $e->raw_value ?? '')
            );
        }

        return $csv;
    }

    /**
     * Get expected CSV headers based on subject paper structure.
     */
    private function getExpectedHeaders(array $paperStructure): array
    {
        $headers = ['index_number', 'sex'];
        for ($i = 1; $i <= ($paperStructure['written_papers'] ?? 2); $i++) {
            $headers[] = "paper_p{$i}";
        }
        if ($paperStructure['has_practical']) {
            $headers[] = 'practical';
        }
        if ($paperStructure['has_project']) {
            $headers[] = 'project';
        }
        return $headers;
    }
}
