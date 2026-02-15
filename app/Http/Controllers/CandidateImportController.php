<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\School;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Services\Candidates\CandidateImportService;
use App\Jobs\ProcessCandidateBulkImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CandidateImportController extends Controller
{
    private CandidateImportService $importService;

    public function __construct(CandidateImportService $importService)
    {
        $this->importService = $importService;
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
            $request->validate([
                'file' => 'required|file|mimes:csv,txt',
                'exam_year' => 'nullable|string|regex:/^\d{4}$/',
                'exam_type' => 'nullable|in:PSLE,CSEE,ACSEE',
                'on_exists_mode' => 'nullable|in:skip,replace'
            ]);

            $file = $request->file('file');
            $examYear = $request->input('exam_year');
            $examType = $request->input('exam_type');
            $mode = $request->input('on_exists_mode', 'skip');

            // Parse and validate CSV
            $result = $this->importService->validateCSV($file, $examYear, $examType, $mode);

            return response()->json($result, 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'File validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
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
                'file' => 'required|file|mimes:csv,txt',
                'exam_year' => 'nullable|string|regex:/^\d{4}$/',
                'exam_type' => 'nullable|in:PSLE,CSEE,ACSEE',
                'on_exists_mode' => 'nullable|in:skip,replace'
            ]);

            $file = $request->file('file');
            $examYear = $request->input('exam_year');
            $examType = $request->input('exam_type');
            $mode = $request->input('on_exists_mode', 'skip');

            // Re-validate and commit
            $result = $this->importService->commitImport($file, $examYear, $examType, $mode);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'File validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
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
            $filename = 'candidates-import-template-' . date('Y-m-d') . '.csv';
            
            $headers = [
                'candidate_id',
                'full_name',
                'gender',    // M or F
                'school_code',
                'candidate_type', // SCHOOL or PRIVATE
                'combination', // For SCHOOL candidates: e.g., "PCM" or "HGE"
                'subjects', // For PRIVATE candidates: pipe-delimited codes e.g., "111|102|103|121"
                'exam_type', // Optional: PSLE, CSEE, ACSEE (default from UI)
                'exam_year'  // Optional: 4-digit year
            ];

            // Create CSV in memory
            $csv = fopen('php://memory', 'w');
            fputcsv($csv, $headers);
            
            // Add example rows (SCHOOL and PRIVATE)
            fputcsv($csv, [
                'S0001-0001',
                'John School',
                'M',
                'SCH001',
                'SCHOOL',
                'PCM', // For SCHOOL: combination code
                '', // Leave blank for SCHOOL
                'ACSEE',
                '2026'
            ]);
            
            fputcsv($csv, [
                'P0001-0001',
                'John Private',
                'M',
                'SCH001',
                'PRIVATE',
                '', // Leave blank for PRIVATE
                '111|102|103|121', // For PRIVATE: subject codes (GS + 3 principals)
                'ACSEE',
                '2026'
            ]);

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
