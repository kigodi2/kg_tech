<?php

namespace App\Http\Controllers;

use App\Models\BulkImport;
use App\Models\District;
use App\Services\MarkImport\BulkImportOrchestrator;
use App\Services\MarkImport\DistrictBulkImportOrchestrator;
use App\Services\MarkImport\DistrictImportRecoveryService;
use App\Services\MarkImport\ZipPreviewService;
use App\Services\MarkImport\SimpleBulkImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * BulkImportController - REBUILT FOR RELIABILITY
 */
class BulkImportController extends Controller
{
    private BulkImportOrchestrator $orchestrator;
    private DistrictBulkImportOrchestrator $districtOrchestrator;
    private DistrictImportRecoveryService $recoveryService;
    private ZipPreviewService $previewService;

    public function __construct(
        BulkImportOrchestrator $orchestrator,
        DistrictBulkImportOrchestrator $districtOrchestrator,
        DistrictImportRecoveryService $recoveryService,
        ZipPreviewService $previewService
    ) {
        $this->orchestrator = $orchestrator;
        $this->districtOrchestrator = $districtOrchestrator;
        $this->recoveryService = $recoveryService;
        $this->previewService = $previewService;
    }

    /**
     * Preview ZIP file - SIMPLIFIED & BULLETPROOF
     */
    public function preview(Request $request)
    {
        Log::info('Preview endpoint called', [
            'method' => $request->method(),
            'has_file' => $request->hasFile('zip_file'),
        ]);

        // Step 1: Check file exists
        if (!$request->hasFile('zip_file')) {
            Log::warning('No file provided to preview endpoint');
            return response()->json([
                'success' => false,
                'errors' => ['No file uploaded'],
            ], 422);
        }

        $zipFile = $request->file('zip_file');
        Log::info('File received', [
            'name' => $zipFile->getClientOriginalName(),
            'size' => $zipFile->getSize(),
            'extension' => $zipFile->getClientOriginalExtension(),
        ]);

        // Step 2: Validate extension
        $extension = strtolower($zipFile->getClientOriginalExtension());
        if ($extension !== 'zip') {
            Log::warning('Invalid file extension: ' . $extension);
            return response()->json([
                'success' => false,
                'errors' => ['File must be a ZIP archive (*.zip)'],
            ], 422);
        }

        // Step 3: Store file
        try {
            $zipPath = $zipFile->store('temp', 'local');
            // Use the storage disk directly
            $storage = \Storage::disk('local');
            $fullPath = $storage->path($zipPath);
            Log::info('File stored successfully', [
                'relative_path' => $zipPath,
                'full_path' => $fullPath,
                'exists' => file_exists($fullPath),
            ]);
        } catch (\Exception $e) {
            Log::error('File storage error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'errors' => ['Failed to store file: ' . $e->getMessage()],
            ], 500);
        }

        // Step 4: Validate ZIP structure
        try {
            Log::info('Starting validation', ['path' => $fullPath, 'exists' => file_exists($fullPath)]);
            $validation = $this->previewService->validate($fullPath);
            Log::info('ZIP validation result', [
                'valid' => $validation['valid'],
                'errors' => $validation['errors'] ?? [],
            ]);

            if (!$validation['valid']) {
                $errorMessages = $validation['errors'] ?? ['Invalid ZIP structure'];
                Log::warning('ZIP validation failed', ['errors' => $errorMessages]);
                // Don't delete - keep for debugging
                // @unlink($fullPath);
                return response()->json([
                    'success' => false,
                    'errors' => $errorMessages,
                ], 422);
            }
        } catch (\Exception $e) {
            Log::error('ZIP validation error: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
            ]);
            // Don't delete - keep for debugging
            // @unlink($fullPath);
            return response()->json([
                'success' => false,
                'errors' => ['ZIP validation failed: ' . $e->getMessage()],
            ], 422);
        }

        // Step 5: Generate preview
        try {
            $preview = $this->previewService->preview($fullPath);
            Log::info('Preview generated successfully');
        } catch (\Exception $e) {
            Log::error('Preview generation error: ' . $e->getMessage());
            @unlink($fullPath);
            return response()->json([
                'success' => false,
                'errors' => ['Failed to generate preview: ' . $e->getMessage()],
            ], 500);
        }

        // Step 6: Store in session
        session()->put('bulk_import_temp_zip', $fullPath);
        Log::info('ZIP path stored in session');

        // Step 7: Return success
        return response()->json([
            'success' => true,
            'preview' => $preview,
        ], 200);
    }

    /**
     * Start import - SIMPLE SYNCHRONOUS
     */
    public function startImport(Request $request)
    {
        Log::info('Start import endpoint called');

        // Validate input
        try {
            $validated = $request->validate([
                'school_id' => 'required|integer|exists:schools,id',
                'exam_year_id' => 'required|integer|exists:exam_years,id',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }

        // Get ZIP from session
        $zipPath = session()->get('bulk_import_temp_zip');
        if (!$zipPath || !file_exists($zipPath)) {
            session()->forget('bulk_import_temp_zip');
            return response()->json([
                'success' => false,
                'errors' => ['ZIP file not found. Please upload again.'],
            ], 422);
        }

        try {
            // Create bulk import record
            $bulkImport = \App\Models\BulkImport::create([
                'school_id' => $validated['school_id'],
                'exam_year_id' => $validated['exam_year_id'],
                'status' => 'processing',
                'total_files' => 0,
                'created_by' => auth()->id(),
            ]);

            // Process import synchronously
            $importer = new SimpleBulkImporter();
            $result = $importer->importZip($bulkImport, $zipPath);

            // Clear session
            session()->forget('bulk_import_temp_zip');

            return response()->json([
                'success' => true,
                'bulk_import_id' => $bulkImport->id,
                'message' => 'Import completed successfully',
                'result' => $result,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Import error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'errors' => ['Import failed: ' . $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Start district import
     */
    public function startDistrictImport(Request $request)
    {
        Log::info('Start district import endpoint called');

        // Validate
        try {
            $validated = $request->validate([
                'district_id' => 'required|integer|exists:districts,id',
                'exam_year_id' => 'required|integer|exists:exam_years,id',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed', $e->errors());
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        }

        // Check authorization
        try {
            $this->authorize('uploadDistrictCsv', \App\Models\BulkImport::class, $validated['district_id']);
        } catch (\Exception $e) {
            Log::warning('Authorization failed');
            return response()->json([
                'success' => false,
                'errors' => ['You do not have permission'],
            ], 403);
        }

        // Get ZIP from session
        $zipPath = session()->get('bulk_import_temp_zip');
        if (!$zipPath || !file_exists($zipPath)) {
            Log::warning('ZIP file not in session or missing');
            session()->forget('bulk_import_temp_zip');
            return response()->json([
                'success' => false,
                'errors' => ['ZIP file not found. Please upload again.'],
            ], 422);
        }

        // Start import
        try {
            $bulkImport = $this->districtOrchestrator->startImport(
                $zipPath,
                $validated['district_id'],
                $validated['exam_year_id']
            );
        } catch (\Exception $e) {
            Log::error('District import error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'errors' => ['Failed to start import: ' . $e->getMessage()],
            ], 500);
        }

        session()->forget('bulk_import_temp_zip');

        return response()->json([
            'success' => true,
            'bulk_import_id' => $bulkImport->id,
            'message' => 'District import started successfully',
        ], 200);
    }

    /**
     * Get progress
     */
    public function getProgress(int $id)
    {
        try {
            $bulkImport = BulkImport::findOrFail($id);
            $this->authorize('view', $bulkImport);

            $progress = $bulkImport->isDistrictImport()
                ? $this->districtOrchestrator->getProgress($id)
                : $this->orchestrator->getProgress($id);

            return response()->json([
                'success' => true,
                'progress' => $progress,
            ]);
        } catch (\Exception $e) {
            Log::error('Get progress error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get details
     */
    public function getDetails(int $id)
    {
        try {
            $bulkImport = BulkImport::findOrFail($id);
            $this->authorize('view', $bulkImport);

            return response()->json([
                'success' => true,
                'data' => $bulkImport,
            ]);
        } catch (\Exception $e) {
            Log::error('Get details error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
