<?php

namespace App\Services\MarkImport;

use App\Models\BulkImport;
use App\Models\BulkImportFile;
use App\Models\ExamYear;
use App\Models\School;
use App\Models\Subject;
use App\Jobs\ProcessBulkImportFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use ZipArchive;
use Exception;

/**
 * BulkImportOrchestrator
 *
 * Orchestrates the import of bulk CSV ZIP files.
 * 
 * Flow:
 * 1. Accept ZIP upload
 * 2. Validate ZIP signature (if present)
 * 3. Extract manifest.json
 * 4. Register bulk_import record
 * 5. Register bulk_import_files for each CSV
 * 6. Dispatch jobs per subject
 * 7. Track progress and failures
 */
class BulkImportOrchestrator
{
    private ZipSignerService $signerService;
    private const TEMP_DIR = 'storage/app/temp/imports';

    public function __construct(ZipSignerService $signerService)
    {
        $this->signerService = $signerService;
    }

    /**
     * Start a bulk import from ZIP file
     *
     * @param string $zipPath Path to uploaded ZIP
     * @param int $schoolId
     * @param int $examYearId
     * @return BulkImport
     * @throws Exception
     */
    public function startImport(string $zipPath, int $schoolId, int $examYearId): BulkImport
    {
        $school = School::findOrFail($schoolId);
        $examYear = ExamYear::findOrFail($examYearId);

        // Validate ZIP is readable
        if (!file_exists($zipPath) || !is_readable($zipPath)) {
            throw new Exception("ZIP file not readable: {$zipPath}");
        }

        // Compute ZIP hash for audit
        $zipHash = $this->signerService->hashFile($zipPath);

        // Extract and validate manifest
        $manifest = $this->extractAndValidateManifest($zipPath);

        // Create bulk import record
        $bulkImport = BulkImport::create([
            'school_id' => $schoolId,
            'exam_year_id' => $examYearId,
            'status' => 'pending',
            'total_files' => count($manifest['files'] ?? []),
            'zip_hash' => $zipHash,
            'manifest_hash' => $this->signerService->hashData(json_encode($manifest)),
            'signature' => $manifest['signature']['value'] ?? null,
            'created_by' => auth()->id(),
        ]);

        // Mark as processing immediately (file registration happens in background)
        $bulkImport->status = 'processing';
        $bulkImport->save();

        // Start background processing via command (don't wait for it)
        $bulkImportId = $bulkImport->id;
        $command = "php artisan import:start-bulk-processing {$bulkImportId} '$zipPath' > /dev/null 2>&1 &";
        exec($command);

        $this->logImportStart($bulkImport);

        // Return immediately - processing happens in background
        return $bulkImport;
    }

    /**
     * Extract and validate manifest.json from ZIP
     *
     * @param string $zipPath
     * @return array
     * @throws Exception
     */
    private function extractAndValidateManifest(string $zipPath): array
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new Exception("Cannot open ZIP file: {$zipPath}");
        }

        // Check manifest.json exists
        if (($index = $zip->locateName('manifest.json')) === false) {
            throw new Exception("manifest.json not found in ZIP");
        }

        $manifestContent = $zip->getFromIndex($index);
        $zip->close();

        $manifest = json_decode($manifestContent, true);

        if (!$manifest) {
            throw new Exception("Invalid manifest.json (invalid JSON)");
        }

        // Validate manifest structure
        if (!isset($manifest['exam_year']) || !isset($manifest['school_code'])) {
            throw new Exception("manifest.json missing required fields");
        }

        // Verify signature if present
        if (isset($manifest['signature']['value'])) {
            if (!$this->signerService->verifyManifestSignature($manifest)) {
                throw new Exception("ZIP signature verification failed (file may be tampered)");
            }
        }

        return $manifest;
    }

    /**
     * Extract ZIP contents to temporary directory
     *
     * @param string $zipPath
     * @param int $bulkImportId
     * @return string Path to extracted contents
     */
    private function extractZipToTemp(string $zipPath, int $bulkImportId): string
    {
        $this->ensureTempDirectory();

        $extractPath = storage_path('app/temp/imports/' . $bulkImportId);

        if (!is_dir($extractPath)) {
            mkdir($extractPath, 0755, true);
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new Exception("Cannot extract ZIP: {$zipPath}");
        }

        $zip->extractTo($extractPath);
        $zip->close();

        return $extractPath;
    }

    /**
     * Register files in database and dispatch import jobs
     *
     * @param BulkImport $bulkImport
     * @param array $manifest
     * @param string $extractPath
     * @return void
     */
    private function registerFilesAndDispatchJobs(
        BulkImport $bulkImport,
        array $manifest,
        string $extractPath
    ): void {
        $files = $manifest['files'] ?? [];

        foreach ($files as $filename => $fileInfo) {
            // Skip manifest.json
            if ($filename === 'manifest.json') {
                continue;
            }

            $filePath = $extractPath . '/' . $filename;

            if (!file_exists($filePath)) {
                Log::warning("File referenced in manifest not found: {$filename}");
                continue;
            }

            // Extract subject code from filename (e.g., PHY_2026_S0325.csv → PHY)
            $subjectCode = explode('_', $filename)[0];

            // Find subject by code
            $subject = Subject::where('code', $subjectCode)->first();

            // Create bulk_import_file record
            $importFile = BulkImportFile::create([
                'bulk_import_id' => $bulkImport->id,
                'subject_id' => $subject?->id,
                'subject_code' => $subjectCode,
                'filename' => $filename,
                'status' => 'pending',
                'file_hash' => $this->signerService->hashFile($filePath),
            ]);

            // Dispatch asynchronous processing via command
            $command = "php artisan import:process-bulk-file {$importFile->id} '$filePath' > /dev/null 2>&1 &";
            exec($command);
        }
    }

    /**
     * Get import progress
     *
     * @param int $bulkImportId
     * @return array
     */
    public function getProgress(int $bulkImportId): array
    {
        $bulkImport = BulkImport::findOrFail($bulkImportId);
        
        return [
            'id' => $bulkImport->id,
            'status' => $bulkImport->status,
            'progress_percentage' => $bulkImport->getProgressPercentage(),
            'total_files' => $bulkImport->total_files,
            'processed_files' => $bulkImport->processed_files,
            'files' => $bulkImport->files->map(fn($file) => [
                'subject_code' => $file->subject_code,
                'filename' => $file->filename,
                'status' => $file->status,
                'rows_total' => $file->rows_total,
                'rows_success' => $file->rows_success,
                'rows_failed' => $file->rows_failed,
                'success_rate' => round($file->getSuccessRate(), 2),
            ]),
            'summary' => $bulkImport->getSummary(),
        ];
    }

    /**
     * Mark import file as completed and update parent import status
     *
     * @param int $importFileId
     * @return void
     */
    public function markFileComplete(int $importFileId): void
    {
        $importFile = BulkImportFile::findOrFail($importFileId);
        $importFile->completed_at = now();
        $importFile->save();

        $bulkImport = $importFile->bulkImport;
        $bulkImport->processed_files = $bulkImport->files()->whereIn('status', ['success', 'failed'])->count();

        // Check if all files are processed
        if ($bulkImport->processed_files >= $bulkImport->total_files) {
            $bulkImport->status = 'completed';
            $bulkImport->completed_at = now();

            // Generate error summary
            $failedFiles = $bulkImport->files()->where('status', 'failed')->get();
            if ($failedFiles->isNotEmpty()) {
                $bulkImport->error_summary = $failedFiles->map(fn($f) => 
                    "{$f->subject_code}: {$f->rows_failed} failed rows"
                )->join('; ');
            }
        }

        $bulkImport->save();
    }

    /**
     * Ensure temp directory exists
     *
     * @return void
     */
    private function ensureTempDirectory(): void
    {
        $tempDir = storage_path('app/temp/imports');

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
    }

    /**
     * Log import start for audit
     *
     * @param BulkImport $bulkImport
     * @return void
     */
    private function logImportStart(BulkImport $bulkImport): void
    {
        Log::channel('audit')->info('Bulk Import Started', [
            'bulk_import_id' => $bulkImport->id,
            'school_id' => $bulkImport->school_id,
            'exam_year_id' => $bulkImport->exam_year_id,
            'total_files' => $bulkImport->total_files,
            'zip_hash' => $bulkImport->zip_hash,
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Cleanup temporary files for completed import
     *
     * @param int $bulkImportId
     * @return void
     */
    public function cleanup(int $bulkImportId): void
    {
        $extractPath = storage_path('app/temp/imports/' . $bulkImportId);

        if (is_dir($extractPath)) {
            $this->removeDirectory($extractPath);
        }
    }

    /**
     * Recursively remove directory
     *
     * @param string $path
     * @return void
     */
    private function removeDirectory(string $path): void
    {
        $files = array_diff(scandir($path), ['.', '..']);

        foreach ($files as $file) {
            $filePath = $path . '/' . $file;

            if (is_dir($filePath)) {
                $this->removeDirectory($filePath);
            } else {
                @unlink($filePath);
            }
        }

        @rmdir($path);
    }

    /**
     * Process a CSV file directly (synchronously, no queue)
     *
     * @param BulkImportFile $importFile
     * @param string $filePath
     * @return void
     * @throws Exception
     */
    private function processFileDirectly(BulkImportFile $importFile, string $filePath): void
    {
        Log::info("Processing file directly: {$importFile->filename}");
        
        if (!file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }

        $importFile->status = 'processing';
        $importFile->save();

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception("Cannot open file: {$filePath}");
        }

        $rowNumber = 0;
        $successCount = 0;
        $failureCount = 0;
        $headers = null;

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                // First row is header
                if ($rowNumber === 1) {
                    $headers = $row;
                    continue;
                }

                try {
                    if (empty($row[0])) {
                        continue; // Skip empty rows
                    }

                    $candidateId = trim($row[0]);
                    $mark = isset($row[2]) ? trim($row[2]) : null;

                    if ($mark === '' || $mark === null) {
                        $failureCount++;
                        continue;
                    }

                    $mark = (float)$mark;

                    // Find candidate
                    $candidate = \App\Models\Candidate::where('candidate_id', $candidateId)->first();
                    if (!$candidate) {
                        $failureCount++;
                        continue;
                    }

                    // Save or update mark
                    \App\Models\SubjectMarks::updateOrCreate(
                        [
                            'candidate_id' => $candidate->id,
                            'subject_id' => $importFile->subject_id,
                            'exam_year_id' => $importFile->bulkImport->exam_year_id,
                        ],
                        [
                            'mark_p1' => $mark,
                            'marked_by' => auth()->id(),
                            'marked_at' => now(),
                        ]
                    );

                    $successCount++;
                } catch (Exception $e) {
                    Log::warning("Error processing row {$rowNumber}: " . $e->getMessage());
                    $failureCount++;
                }
            }
        } finally {
            fclose($handle);
        }

        // Update file status
        $importFile->status = 'completed';
        $importFile->rows_total = $rowNumber - 1; // Exclude header
        $importFile->rows_success = $successCount;
        $importFile->rows_failed = $failureCount;
        $importFile->completed_at = now();
        $importFile->save();

        Log::info("File completed: {$importFile->filename}, Success: {$successCount}, Failed: {$failureCount}");
    }
}
