<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\School;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Services\Candidates\CandidateImportService;
use App\Services\Candidates\CseeRegistrationPdfImportService;
use App\Support\PsleUserScope;
use App\Jobs\ProcessCandidateBulkImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CandidateImportController extends Controller
{
    private CandidateImportService $importService;

    public function __construct(CandidateImportService $importService)
    {
        $this->importService = $importService;
    }

    public function validateCseeRegistrationPdf(Request $request, CseeRegistrationPdfImportService $service)
    {
        try {
            $request->validate([
                'files' => 'required|array|min:1',
                'files.*' => 'required|file|mimes:pdf',
                'exam_year' => 'nullable|string|regex:/^\d{4}$/',
            ]);

            return response()->json(
                $service->validatePdfBatch($request->file('files', []), $request->input('exam_year')),
                200
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'File validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('CSEE registration PDF validation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage(),
                'errors' => [],
            ], 400);
        }
    }

    public function commitCseeRegistrationPdf(Request $request, CseeRegistrationPdfImportService $service)
    {
        try {
            if ($request->has('parsed_payloads')) {
                $validated = $request->validate([
                    'parsed_payloads' => 'required|array|min:1',
                    'parsed_payloads.*.exam_year' => 'nullable|string|regex:/^\d{4}$/',
                    'parsed_payloads.*.school_code' => 'required|string',
                    'parsed_payloads.*.school_name' => 'required|string',
                    'parsed_payloads.*.rows' => 'required|array|min:1',
                    'parsed_payloads.*.rows.*.candidate_id' => 'required|string',
                    'parsed_payloads.*.rows.*.gender' => 'required|string',
                    'parsed_payloads.*.rows.*.full_name' => 'required|string',
                    'parsed_payloads.*.rows.*.school_code' => 'nullable|string',
                    'parsed_payloads.*.rows.*.subject_codes' => 'required|array|min:1',
                    'parsed_payloads.*.rows.*.subject_codes.*' => 'required|string',
                    'parsed_payloads.*.source_file_name' => 'nullable|string',
                    'exam_year' => 'nullable|string|regex:/^\d{4}$/',
                ]);

                return response()->json(
                    $service->commitParsedPayloadBatch($validated['parsed_payloads'], $request->input('exam_year')),
                    200
                );
            }

            $request->validate([
                'files' => 'required|array|min:1',
                'files.*' => 'required|file|mimes:pdf',
                'exam_year' => 'nullable|string|regex:/^\d{4}$/',
            ]);

            return response()->json(
                $service->commitPdfBatch($request->file('files', []), $request->input('exam_year')),
                200
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'File validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('CSEE registration PDF import error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import error: ' . $e->getMessage(),
                'errors' => [],
            ], 400);
        }
    }

    /**
     * Validate CSV file and preview results (Phase 1: Dry-run)
     * 
     * Returns validation results without committing changes
     * Supports on_exists_mode: 'skip' (default) or 'replace'
     */
    public function validateImport(Request $request)
    {
        try {
            set_time_limit(300);

            $request->validate([
                'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:51200',
                'exam_year' => 'nullable|string|regex:/^\d{4}$/',
                'exam_type' => 'nullable|in:PSLE,CSEE,ACSEE',
                'on_exists_mode' => 'nullable|in:skip,replace,stop'
            ]);

            $file = $request->file('file');
            $examYear = $request->input('exam_year');
            $examType = $request->input('exam_type');
            $mode = $request->input('on_exists_mode', 'skip');

            Log::info('PSLE pupil import started', [
                'user_id' => auth()->id(),
                'exam_year' => $examYear,
                'file_name' => $file->getClientOriginalName(),
                'phase' => 'validation',
            ]);

            // Parse and validate CSV
            $result = $this->importService->validateCSV($file, $examYear, $examType, $mode);
            if (strtoupper((string) $examType) === 'PSLE') {
                $result = $this->applyPsleImportScope($request, $result);
            }

            if (strtoupper((string) $examType) === 'PSLE') {
                Log::info('PSLE pupil import validation summary', [
                    'total_rows' => $result['summary']['total_rows'] ?? $result['total_rows'] ?? 0,
                    'valid_rows' => $result['summary']['valid_rows'] ?? 0,
                    'duplicates' => $result['summary']['duplicates_in_file'] ?? 0,
                    'invalid_rows' => $result['summary']['invalid_rows'] ?? $result['error_count'] ?? 0,
                ]);
            }

            return response()->json($result, 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'File validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Candidate import validation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage(),
                'errors' => []
            ], 400);
        }
    }

    /**
     * Commit the import (Phase 2: Actual write)
     * 
     * Uses validated data from Phase 1
     * Supports on_exists_mode: 'skip' (default) or 'replace'
     */
    public function commitImport(Request $request)
    {
        try {
            // Increase execution time for large imports
            set_time_limit(300); // 5 minutes for large batches
            
            $request->validate([
                'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:51200',
                'exam_year' => 'nullable|string|regex:/^\d{4}$/',
                'exam_type' => 'nullable|in:PSLE,CSEE,ACSEE',
                'on_exists_mode' => 'nullable|in:skip,replace,stop'
            ]);

            $file = $request->file('file');
            $examYear = $request->input('exam_year');
            $examType = $request->input('exam_type');
            $mode = $request->input('on_exists_mode', 'skip');

            if (strtoupper((string) $examType) === 'PSLE') {
                $validation = $this->applyPsleImportScope(
                    $request,
                    $this->importService->validateCSV($file, $examYear, $examType, $mode)
                );

                if (!($validation['can_import'] ?? false)) {
                    return response()->json($validation, 422);
                }
            }

            // Re-validate and commit
            $result = $this->importService->commitImport($file, $examYear, $examType, $mode);

            if ($result['success'] ?? false) {
                \App\Services\PsleCacheService::incrementVersion();
            }

            if (strtoupper((string) $examType) === 'PSLE') {
                Log::info('PSLE pupil import committed', [
                    'imported' => $result['imported_count'] ?? 0,
                    'skipped' => $result['skipped_count'] ?? 0,
                    'failed' => count($result['errors'] ?? []),
                    'updated' => $result['updated_count'] ?? 0,
                ]);
            }

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'File validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Candidate import commit error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import error: ' . $e->getMessage(),
                'errors' => []
            ], 400);
        }
    }

    /**
     * Download import template
     */
    public function downloadTemplate(Request $request)
    {
        try {
            $examType = strtoupper((string) $request->input('exam_type', 'ACSEE'));
            $filename = 'candidates-import-template-' . date('Y-m-d') . '.csv';

            // Create CSV in memory
            $csv = fopen('php://memory', 'w');

            if ($examType === 'CSEE') {
                $filename = 'csee-candidates-import-template-' . date('Y-m-d') . '.csv';
                fputcsv($csv, [
                    'candidate_id',
                    'full_name',
                    'gender',
                    'exam_type',
                    'exam_year',
                ]);

                fputcsv($csv, [
                    'S51910001',
                    'JANE DOE',
                    'F',
                    'CSEE',
                    '2026',
                ]);
            } elseif ($examType === 'PSLE') {
                $filename = 'psle-pupil-import-template-' . date('Y-m-d') . '.csv';
                fputcsv($csv, [
                    'candidate_number',
                    'PReM_No',
                    'pupil_name',
                    'sex',
                    'school_code',
                ]);

                fputcsv($csv, [
                    'PS0404006-0001',
                    '20201520092',
                    'ASHERI JOSHUA CHAULA',
                    'M',
                    'PS0404006',
                ]);
            } else {
                fputcsv($csv, [
                    'candidate_id',
                    'prem_no',
                    'full_name',
                    'gender',
                    'school_code',
                    'candidate_type',
                    'combination',
                    'subjects',
                    'exam_type',
                    'exam_year'
                ]);

                fputcsv($csv, [
                    'S0001-0001',
                    'PREM-001',
                    'John School',
                    'M',
                    'SCH001',
                    'SCHOOL',
                    'PCM',
                    '',
                    'ACSEE',
                    '2026'
                ]);

                fputcsv($csv, [
                    'P0001-0001',
                    'PREM-002',
                    'John Private',
                    'M',
                    'SCH001',
                    'PRIVATE',
                    '',
                    '111|102|103|121',
                    'ACSEE',
                    '2026'
                ]);
            }

            rewind($csv);
            $content = stream_get_contents($csv);
            fclose($csv);

            return response($content, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
                'X-Content-Type-Options' => 'nosniff',
            ]);

        } catch (\Exception $e) {
            Log::error('Template download error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate template'
            ], 500);
        }
    }

    private function applyPsleImportScope(Request $request, array $result): array
    {
        $user = $request->user();
        if (!$user) {
            return [
                'success' => false,
                'message' => 'You are not authorized to import PSLE pupils.',
                'rows' => $result['rows'] ?? [],
                'summary' => $result['summary'] ?? [],
                'can_import' => false,
            ];
        }

        if (PsleUserScope::hasGlobalAccess($user)) {
            return $result;
        }

        $schoolsQuery = School::query();
        PsleUserScope::applyToSchools($schoolsQuery, $user);
        $allowedSchoolCodes = $schoolsQuery
            ->pluck('code')
            ->map(fn ($code) => strtoupper(trim((string) $code)))
            ->filter()
            ->values()
            ->all();

        if (empty($allowedSchoolCodes)) {
            return [
                'success' => false,
                'message' => 'You do not have an assigned PSLE school scope for this import.',
                'rows' => $result['rows'] ?? [],
                'summary' => $result['summary'] ?? [],
                'can_import' => false,
            ];
        }

        $allowed = array_flip($allowedSchoolCodes);
        $rows = $result['rows'] ?? [];
        $errors = $result['errors'] ?? [];
        $outOfScope = 0;

        foreach ($rows as &$row) {
            $schoolCode = strtoupper(trim((string) ($row['school_code'] ?? '')));
            if ($schoolCode !== '' && isset($allowed[$schoolCode])) {
                continue;
            }

            $outOfScope++;
            $row['status'] = 'ERROR';
            $row['message'] = 'School is outside your assigned PSLE scope.';
            $row['messages'] = ['School is outside your assigned PSLE scope.'];
            $errors[] = [
                'row_number' => $row['row_number'] ?? null,
                'candidate_id' => $row['candidate_id'] ?? $row['candidate_number'] ?? '',
                'prem_no' => $row['prem_no'] ?? '',
                'full_name' => $row['full_name'] ?? $row['pupil_name'] ?? '',
                'gender' => $row['sex'] ?? '',
                'school_code' => $row['school_code'] ?? '',
                'error_messages' => ['School is outside your assigned PSLE scope.'],
                'primary_error' => 'School is outside your assigned PSLE scope.',
            ];
        }
        unset($row);

        if ($outOfScope === 0) {
            return $result;
        }

        $summary = $result['summary'] ?? [];
        $summary['invalid_rows'] = ($summary['invalid_rows'] ?? 0) + $outOfScope;
        $summary['valid_rows'] = max(0, ($summary['valid_rows'] ?? 0) - $outOfScope);
        $summary['out_of_scope_rows'] = $outOfScope;

        $result['rows'] = $rows;
        $result['errors'] = array_slice($errors, 0, 100);
        $result['summary'] = $summary;
        $result['success'] = false;
        $result['can_import'] = false;
        $result['error_count'] = ($result['error_count'] ?? 0) + $outOfScope;
        $result['total_errors'] = ($result['total_errors'] ?? 0) + $outOfScope;
        $result['message'] = $outOfScope . ' row(s) are outside your assigned PSLE school scope.';

        return $result;
    }

    /**
     * Async bulk import - dispatch to queue for large files
     * 
     * Returns immediately and processes in background
     * Supports on_exists_mode: 'skip' (default) or 'replace'
     */
    public function asyncBulkImport(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,txt|max:52428800', // 50MB max
                'exam_year' => 'nullable|string|regex:/^\d{4}$/',
                'exam_type' => 'nullable|in:PSLE,CSEE,ACSEE',
                'on_exists_mode' => 'nullable|in:skip,replace'
            ]);

            $file = $request->file('file');
            $examYear = $request->input('exam_year', '2026');
            $examType = $request->input('exam_type', 'ACSEE');
            $mode = $request->input('on_exists_mode', 'skip');

            // Store file temporarily
            $tempPath = Storage::disk('local')->putFile('imports', $file);
            $fullPath = Storage::disk('local')->path($tempPath);

            // Dispatch async job
            ProcessCandidateBulkImport::dispatch(
                $fullPath,
                $examYear,
                $examType,
                $mode
            )->onQueue('default');

            return response()->json([
                'success' => true,
                'message' => 'Import job dispatched. Processing in background...',
                'file_path' => $tempPath,
                'import_id' => uniqid('import_')
            ], 202); // 202 Accepted

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'File validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Async bulk import error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start import: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Download error report from failed import
     */
    public function downloadErrorReport(Request $request)
    {
        try {
            $request->validate([
                'errors' => 'required|array',
            ]);

            $errors = $request->input('errors', []);
            $filename = 'candidate-import-errors-' . date('Y-m-d-His') . '.csv';

            $csv = fopen('php://memory', 'w');
            
            // CSV headers
            $headers = [
                'row_number',
                'candidate_id',
                'prem_no',
                'full_name',
                'gender',
                'school_code',
                'combination',
                'exam_type',
                'error_messages'
            ];
            fputcsv($csv, $headers);

            // Add error rows
            foreach ($errors as $error) {
                fputcsv($csv, [
                    $error['row_number'] ?? '',
                    $error['candidate_id'] ?? '',
                    $error['prem_no'] ?? '',
                    $error['full_name'] ?? '',
                    $error['gender'] ?? '',
                    $error['school_code'] ?? '',
                    $error['combination'] ?? '',
                    $error['exam_type'] ?? '',
                    implode('; ', $error['error_messages'] ?? [])
                ]);
            }

            rewind($csv);
            $content = stream_get_contents($csv);
            fclose($csv);

            return response($content, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
                'X-Content-Type-Options' => 'nosniff',
            ]);

        } catch (\Exception $e) {
            Log::error('Error report download error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate error report'
            ], 500);
        }
    }
}
