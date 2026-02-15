<?php

namespace App\Http\Controllers;

use App\Services\Schools\SchoolImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SchoolImportController extends Controller
{
    private SchoolImportService $importService;

    public function __construct(SchoolImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Validate CSV file and preview results (Phase 1: Dry-run)
     * 
     * Returns validation results without committing changes
     */
    public function validateImport(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
            ]);

            $file = $request->file('file');

            // Parse and validate CSV
            $result = $this->importService->validateCSV($file);

            return response()->json($result, 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'File validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('School import validation error', [
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
     */
    public function commit(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
            ]);

            $file = $request->file('file');

            // Re-validate and commit
            $result = $this->importService->commitImport($file);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'File validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('School import commit error', [
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
            $filename = 'schools-import-template-' . date('Y-m-d') . '.csv';
            
            $headers = [
                'Code',
                'Name',
                'Region ID',
                'District ID',
                'Ownership',
            ];

            // Create CSV in memory
            $csv = fopen('php://memory', 'w');
            fputcsv($csv, $headers);
            
            // Add example rows
            fputcsv($csv, [
                'SCH001',
                'Arusha Primary School',
                '1',
                '5',
                'GOVERNMENT',
            ]);

            fputcsv($csv, [
                'S0203',
                'IRINGA GIRLS SECONDARY SCHOOL',
                'IR07',
                'IR0701',
                'GOVERNMENT',
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
     * Download error report from failed import
     */
    public function downloadErrors(Request $request)
    {
        try {
            $request->validate([
                'errors' => 'required|array',
            ]);

            $errors = $request->input('errors', []);
            $filename = 'schools-import-errors-' . date('Y-m-d-His') . '.csv';

            $csv = fopen('php://memory', 'w');
            
            // CSV headers
            $headers = [
                'row_number',
                'code',
                'name',
                'region_id',
                'district_id',
                'ownership',
                'error_messages'
            ];
            fputcsv($csv, $headers);

            // Add error rows
            foreach ($errors as $error) {
                $normalizedRow = $error['normalized_row'] ?? [];
                fputcsv($csv, [
                    $error['row_number'] ?? '',
                    $normalizedRow['code'] ?? '',
                    $normalizedRow['name'] ?? '',
                    $normalizedRow['region_id'] ?? '',
                    $normalizedRow['district_id'] ?? '',
                    $normalizedRow['ownership'] ?? '',
                    implode('; ', $this->flattenErrors($error['errors'] ?? []))
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

    /**
     * Flatten nested error array to string
     */
    private function flattenErrors(array $errors): array
    {
        $flat = [];
        foreach ($errors as $field => $messages) {
            if (is_array($messages)) {
                $flat = array_merge($flat, $messages);
            } else {
                $flat[] = $messages;
            }
        }
        return $flat;
    }
}
