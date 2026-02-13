<?php

namespace App\Services\MarkImport;

use App\Models\BulkImport;
use App\Models\BulkImportFile;
use App\Models\District;
use App\Models\ExamYear;
use App\Models\School;
use App\Models\Subject;
use App\Jobs\ProcessBulkImportSchool;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use ZipArchive;
use Exception;

/**
 * DistrictBulkImportOrchestrator
 *
 * Orchestrates district-level bulk CSV imports from ZIP files.
 *
 * District Import Flow:
 * 1. Validate ZIP structure & manifest
 * 2. Confirm all schools belong to district
 * 3. Register bulk_import with scope=district
 * 4. Register bulk_import_schools for each school
 * 5. Dispatch one job per school (atomic processing)
 * 6. Track partial & complete failures per school
 * 7. Recover on per-school/per-subject basis
 */
class DistrictBulkImportOrchestrator
{
    private ZipSignerService $signerService;
    private DistrictManifestValidator $manifestValidator;
    private const TEMP_DIR = 'storage/app/temp/imports';

    public function __construct(
        ZipSignerService $signerService,
        DistrictManifestValidator $manifestValidator
    ) {
        $this->signerService = $signerService;
        $this->manifestValidator = $manifestValidator;
    }

    /**
     * Start a district-level bulk import from ZIP file
     *
     * @param string $zipPath Path to uploaded ZIP
     * @param int $districtId
     * @param int $examYearId
     * @return BulkImport
     * @throws Exception|ValidationException
     */
    public function startImport(string $zipPath, int $districtId, int $examYearId): BulkImport
    {
        $district = District::findOrFail($districtId);
        $examYear = ExamYear::findOrFail($examYearId);

        // Validate ZIP is readable
        if (!file_exists($zipPath) || !is_readable($zipPath)) {
            throw new Exception("ZIP file not readable: {$zipPath}");
        }

        // Compute ZIP hash for audit
        $zipHash = $this->signerService->hashFile($zipPath);

        // Extract and validate manifest
        $manifest = $this->extractAndValidateManifest($zipPath);

        // Validate manifest schema & scope
        $validation = $this->manifestValidator->validate($manifest, $district, $examYear);

        if (!$validation['valid']) {
            throw ValidationException::withMessages($validation['errors']);
        }

        // Validate all schools belong to district
        $this->validateSchoolOwnership($manifest, $district);

        // Create bulk import record
        $bulkImport = BulkImport::create([
            'district_id' => $districtId,
            'exam_year_id' => $examYearId,
            'scope_type' => 'district',
            'scope_id' => $districtId,
            'status' => 'validating',
            'total_files' => $this->countManifestFiles($manifest),
            'total_schools' => count($manifest['schools'] ?? []),
            'processed_files' => 0,
            'processed_schools' => 0,
            'zip_hash' => $zipHash,
            'manifest_hash' => $this->signerService->hashData(json_encode($manifest)),
            'signature' => $manifest['signature']['value'] ?? null,
            'created_by' => auth()->id(),
        ]);

        // Extract ZIP to temp directory
        $extractPath = $this->extractZipToTemp($zipPath, $bulkImport->id);

        // Register schools and dispatch jobs
        $this->registerSchoolsAndDispatchJobs($bulkImport, $manifest, $extractPath);

        // Update status to processing
        $bulkImport->update(['status' => 'importing']);

        $this->logImportStart($bulkImport, $district);

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

        // Verify signature if present
        if (isset($manifest['signature']['value'])) {
            if (!$this->signerService->verifyManifestSignature($manifest)) {
                throw new Exception("ZIP signature verification failed (file may be tampered)");
            }
        }

        return $manifest;
    }

    /**
     * Validate that all schools in manifest belong to the district
     *
     * @param array $manifest
     * @param District $district
     * @throws Exception
     */
    private function validateSchoolOwnership(array $manifest, District $district): void
    {
        $schools = $manifest['schools'] ?? [];

        foreach ($schools as $schoolData) {
            $schoolCode = $schoolData['school_code'] ?? null;

            if (!$schoolCode) {
                throw new Exception("School entry missing school_code");
            }

            $school = School::where('code', $schoolCode)
                ->where('district_id', $district->id)
                ->first();

            if (!$school) {
                throw new Exception("School {$schoolCode} not found in district {$district->code}");
            }
        }
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
     * Register schools and dispatch import jobs
     *
     * @param BulkImport $bulkImport
     * @param array $manifest
     * @param string $extractPath
     * @return void
     */
    private function registerSchoolsAndDispatchJobs(
        BulkImport $bulkImport,
        array $manifest,
        string $extractPath
    ): void {
        $schools = $manifest['schools'] ?? [];

        foreach ($schools as $schoolData) {
            $schoolCode = $schoolData['school_code'];
            $schoolName = $schoolData['school_name'];
            $subjects = $schoolData['subjects'] ?? [];

            // Find school
            $school = School::where('code', $schoolCode)->firstOrFail();

            // Register in bulk_import_schools pivot
            $bulkImport->schools()->attach($school->id, [
                'school_code' => $schoolCode,
                'school_name' => $schoolName,
                'status' => 'pending',
                'total_subjects' => count($subjects),
                'processed_subjects' => 0,
                'total_candidates' => $schoolData['total_candidates'] ?? 0,
                'successful_candidates' => 0,
                'failed_candidates' => 0,
            ]);

            // Dispatch job to process this school
            ProcessBulkImportSchool::dispatch(
                $bulkImport->id,
                $school->id,
                $subjects,
                $extractPath
            );
        }
    }

    /**
     * Count total CSV files in manifest
     *
     * @param array $manifest
     * @return int
     */
    private function countManifestFiles(array $manifest): int
    {
        $count = 0;
        $schools = $manifest['schools'] ?? [];

        foreach ($schools as $schoolData) {
            $subjects = $schoolData['subjects'] ?? [];
            $count += count($subjects);
        }

        return $count;
    }

    /**
     * Get import progress
     *
     * @param int $bulkImportId
     * @return array
     */
    public function getProgress(int $bulkImportId): array
    {
        $bulkImport = BulkImport::with('schools', 'district', 'examYear')
            ->findOrFail($bulkImportId);

        if (!$bulkImport->isDistrictImport()) {
            throw new Exception("Import {$bulkImportId} is not a district-level import");
        }

        $schoolsProgress = $bulkImport->schools()->get()->map(fn($school) => [
            'school_id' => $school->id,
            'school_code' => $school->pivot->school_code,
            'school_name' => $school->pivot->school_name,
            'status' => $school->pivot->status,
            'total_subjects' => $school->pivot->total_subjects,
            'processed_subjects' => $school->pivot->processed_subjects,
            'total_candidates' => $school->pivot->total_candidates,
            'successful_candidates' => $school->pivot->successful_candidates,
            'failed_candidates' => $school->pivot->failed_candidates,
            'started_at' => $school->pivot->started_at,
            'completed_at' => $school->pivot->completed_at,
        ]);

        return [
            'id' => $bulkImport->id,
            'district' => $bulkImport->district->name,
            'exam_year' => $bulkImport->examYear->year_label,
            'status' => $bulkImport->status,
            'progress_percentage' => $bulkImport->getProgressPercentage(),
            'total_schools' => $bulkImport->total_schools,
            'processed_schools' => $bulkImport->processed_schools,
            'total_files' => $bulkImport->total_files,
            'processed_files' => $bulkImport->processed_files,
            'schools' => $schoolsProgress,
            'summary' => $bulkImport->getSummary(),
        ];
    }

    /**
     * Mark school as completed
     *
     * @param int $bulkImportId
     * @param int $schoolId
     * @param string $status success|partial|failed
     * @param array $stats
     * @return void
     */
    public function markSchoolComplete(
        int $bulkImportId,
        int $schoolId,
        string $status,
        array $stats = []
    ): void {
        $bulkImport = BulkImport::findOrFail($bulkImportId);

        // Update school status
        $bulkImport->schools()->updateExistingPivot($schoolId, [
            'status' => $status,
            'processed_subjects' => $stats['processed_subjects'] ?? 0,
            'successful_candidates' => $stats['successful_candidates'] ?? 0,
            'failed_candidates' => $stats['failed_candidates'] ?? 0,
            'error_summary' => $stats['error_summary'] ?? null,
            'completed_at' => now(),
        ]);

        // Update import file counts
        $bulkImport->processed_schools = $bulkImport->schools()
            ->wherePivotIn('status', ['success', 'partial', 'failed'])
            ->count();

        $bulkImport->processed_files = $bulkImport->schools()
            ->wherePivotIn('status', ['success', 'partial', 'failed'])
            ->get()
            ->sum('pivot.processed_subjects');

        // Check if all schools are processed
        if ($bulkImport->processed_schools >= $bulkImport->total_schools) {
            $failedSchools = $bulkImport->schools()
                ->wherePivot('status', 'failed')
                ->count();
            $partialSchools = $bulkImport->schools()
                ->wherePivot('status', 'partial')
                ->count();

            if ($failedSchools > 0) {
                $bulkImport->status = 'failed';
            } elseif ($partialSchools > 0) {
                $bulkImport->status = 'partial';
            } else {
                $bulkImport->status = 'completed';
            }

            $bulkImport->completed_at = now();

            // Generate error summary
            $failedSchoolsList = $bulkImport->schools()
                ->wherePivotIn('status', ['failed', 'partial'])
                ->get()
                ->map(fn($s) => "{$s->pivot->school_code}: {$s->pivot->error_summary}")
                ->filter()
                ->join('; ');

            if ($failedSchoolsList) {
                $bulkImport->error_summary = $failedSchoolsList;
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
     * @param District $district
     * @return void
     */
    private function logImportStart(BulkImport $bulkImport, District $district): void
    {
        Log::channel('audit')->info('District Bulk Import Started', [
            'bulk_import_id' => $bulkImport->id,
            'district_id' => $district->id,
            'district_code' => $district->code,
            'exam_year_id' => $bulkImport->exam_year_id,
            'total_schools' => $bulkImport->total_schools,
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
}
