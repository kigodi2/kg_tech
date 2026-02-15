<?php

namespace App\Http\Controllers;

use App\Services\AcseeAllocationTemplateService;
use App\Services\AcseeAllocationCSVImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ACSEE Allocation Controller
 * 
 * Handles:
 * - CSV template downloads (SCHOOL + PRIVATE)
 * - CSV allocation import (two-phase: validate + commit)
 */
class AcseeAllocationController extends Controller
{
    private AcseeAllocationTemplateService $templateService;
    private AcseeAllocationCSVImporter $importer;

    public function __construct(
        AcseeAllocationTemplateService $templateService,
        AcseeAllocationCSVImporter $importer
    ) {
        $this->templateService = $templateService;
        $this->importer = $importer;
    }

    /**
     * Download SCHOOL allocation template
     * 
     * GET /api/exam-types/acsee/templates/school-allocation.csv
     */
    public function getSchoolTemplate()
    {
        try {
            $content = $this->templateService->generateSchoolTemplate();
            $filename = 'ACSEE_SCHOOL_ALLOCATION_TEMPLATE_' . date('Y-m-d') . '.csv';

            return response($content, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
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
     * Download PRIVATE allocation template
     * 
     * GET /api/exam-types/acsee/templates/private-allocation.csv
     */
    public function getPrivateTemplate()
    {
        try {
            $content = $this->templateService->generatePrivateTemplate();
            $filename = 'ACSEE_PRIVATE_ALLOCATION_TEMPLATE_' . date('Y-m-d') . '.csv';

            return response($content, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
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
     * Phase 1: Validate CSV allocation import (dry-run)
     * 
     * POST /api/exam-types/acsee/allocate-from-csv/validate
     * 
     * Request:
     * {
     *   "file": <File>,
     *   "exam_year_id": 45,
     *   "candidate_type_filter": "ALL|SCHOOL|PRIVATE"
     * }
     */
    public function validateAllocationImport(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,txt',
                'exam_year_id' => 'required|integer|exists:exam_years,id',
                'candidate_type_filter' => 'nullable|in:ALL,SCHOOL,PRIVATE'
            ]);

            $file = $request->file('file');
            $examYearId = $request->input('exam_year_id');
            $candidateTypeFilter = $request->input('candidate_type_filter', 'ALL');

            $result = $this->importer->validateCSV($file, $examYearId, $candidateTypeFilter);

            return response()->json($result, $result['success'] ? 200 : 422);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('CSV validation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Phase 2: Commit CSV allocation import
     * 
     * POST /api/exam-types/acsee/allocate-from-csv/commit
     * 
     * Request:
     * {
     *   "file": <File>,
     *   "exam_year_id": 45,
     *   "candidate_type_filter": "ALL|SCHOOL|PRIVATE",
     *   "replace_allocations_default": false
     * }
     */
    public function commitAllocationImport(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,txt',
                'exam_year_id' => 'required|integer|exists:exam_years,id',
                'candidate_type_filter' => 'nullable|in:ALL,SCHOOL,PRIVATE',
                'replace_allocations_default' => 'nullable|boolean'
            ]);

            $file = $request->file('file');
            $examYearId = $request->input('exam_year_id');
            $candidateTypeFilter = $request->input('candidate_type_filter', 'ALL');
            $replaceAllocationsDefault = $request->boolean('replace_allocations_default', false);

            // Increase timeout for large imports
            set_time_limit(300); // 5 minutes

            $result = $this->importer->commitImport($file, $examYearId, $candidateTypeFilter, $replaceAllocationsDefault);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('CSV import error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import error: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Combined endpoint: Single request with phase parameter
     * 
     * POST /api/exam-types/acsee/allocate-from-csv
     * 
     * Request:
     * {
     *   "file": <File>,
     *   "exam_year_id": 45,
     *   "candidate_type_filter": "ALL|SCHOOL|PRIVATE",
     *   "phase": "validate|commit",
     *   "replace_allocations_default": false
     * }
     */
    public function importAllocations(Request $request)
    {
        $phase = $request->input('phase', 'validate');

        if ($phase === 'commit') {
            return $this->commitAllocationImport($request);
        }

        return $this->validateAllocationImport($request);
    }

    /**
     * Download error report from failed import
     * 
     * POST /api/exam-types/acsee/allocate-from-csv/download-errors
     */
    public function downloadErrorReport(Request $request)
    {
        try {
            $request->validate([
                'errors' => 'required|array',
            ]);

            $errors = $request->input('errors', []);
            $filename = 'allocation-import-errors-' . date('Y-m-d-His') . '.csv';

            $csv = fopen('php://memory', 'w');

            // CSV headers
            $headers = [
                'row_number',
                'index_number',
                'candidate_type',
                'combination_code',
                'subject_codes',
                'error_messages'
            ];
            fputcsv($csv, $headers);

            // Add error rows
            foreach ($errors as $error) {
                fputcsv($csv, [
                    $error['row_number'] ?? '',
                    $error['index_number'] ?? '',
                    $error['candidate_type'] ?? '',
                    $error['combination_code'] ?? '',
                    $error['subject_codes'] ?? '',
                    implode('; ', $error['error_messages'] ?? [])
                ]);
            }

            rewind($csv);
            $content = stream_get_contents($csv);
            fclose($csv);

            return response($content, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
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
