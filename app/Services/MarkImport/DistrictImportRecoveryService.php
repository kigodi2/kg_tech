<?php

namespace App\Services\MarkImport;

use App\Models\BulkImport;
use App\Models\School;
use App\Jobs\ProcessBulkImportSchool;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * DistrictImportRecoveryService
 *
 * Handles retry and recovery of failed district bulk imports.
 *
 * Failure Recovery Rules:
 * - Per school: retry failed school only
 * - Per subject: retry failed subject only
 * - Resume from last successful chunk
 * - Status transitions: pending → processing → partial/success → completed
 */
class DistrictImportRecoveryService
{
    private DistrictBulkImportOrchestrator $orchestrator;

    public function __construct(DistrictBulkImportOrchestrator $orchestrator)
    {
        $this->orchestrator = $orchestrator;
    }

    /**
     * Retry a specific school from a failed district import
     *
     * @param int $bulkImportId
     * @param int $schoolId
     * @return bool Success
     * @throws Exception
     */
    public function retrySchool(int $bulkImportId, int $schoolId): bool
    {
        $bulkImport = BulkImport::findOrFail($bulkImportId);

        if (!$bulkImport->isDistrictImport()) {
            throw new Exception("Not a district-level import");
        }

        // Find school in the import
        $schoolPivot = $bulkImport->schools()
            ->where('school_id', $schoolId)
            ->first();

        if (!$schoolPivot) {
            throw new Exception("School {$schoolId} not found in this import");
        }

        // Check if school is in failed/partial status
        if (!in_array($schoolPivot->pivot->status, ['failed', 'partial'])) {
            throw new Exception("Cannot retry school with status: {$schoolPivot->pivot->status}");
        }

        // Reset school status
        $bulkImport->schools()->updateExistingPivot($schoolId, [
            'status' => 'pending',
            'processed_subjects' => 0,
            'successful_candidates' => 0,
            'failed_candidates' => 0,
            'error_summary' => null,
            'started_at' => null,
            'completed_at' => null,
        ]);

        // Get extracted path from bulk import
        $extractPath = storage_path('app/temp/imports/' . $bulkImportId);

        if (!is_dir($extractPath)) {
            throw new Exception("Extraction directory not found. Re-upload ZIP to retry.");
        }

        // Get school subjects from manifest
        $school = School::find($schoolId);
        $manifest = $this->getManifestFromExtraction($extractPath);

        if (!$manifest) {
            throw new Exception("Cannot find manifest in extracted files");
        }

        // Find this school in manifest
        $schoolData = collect($manifest['schools'] ?? [])->firstWhere('school_code', $school->code);

        if (!$schoolData) {
            throw new Exception("School {$school->code} not found in manifest");
        }

        $subjects = $schoolData['subjects'] ?? [];

        // Re-dispatch the job
        ProcessBulkImportSchool::dispatch(
            $bulkImportId,
            $schoolId,
            $subjects,
            $extractPath
        );

        Log::info("School retry dispatched", [
            'bulk_import_id' => $bulkImportId,
            'school_id' => $schoolId,
            'school_code' => $school->code,
        ]);

        return true;
    }

    /**
     * Retry all failed schools in a district import
     *
     * @param int $bulkImportId
     * @return int Number of schools retried
     * @throws Exception
     */
    public function retryAllFailedSchools(int $bulkImportId): int
    {
        $bulkImport = BulkImport::findOrFail($bulkImportId);

        if (!$bulkImport->isDistrictImport()) {
            throw new Exception("Not a district-level import");
        }

        // Get all failed/partial schools
        $failedSchools = $bulkImport->schools()
            ->wherePivotIn('status', ['failed', 'partial'])
            ->get();

        $retryCount = 0;

        foreach ($failedSchools as $school) {
            try {
                $this->retrySchool($bulkImportId, $school->id);
                $retryCount++;
            } catch (Exception $e) {
                Log::warning("Failed to retry school", [
                    'bulk_import_id' => $bulkImportId,
                    'school_id' => $school->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update bulk import status back to importing
        $bulkImport->update(['status' => 'importing']);

        Log::info("District import retry started", [
            'bulk_import_id' => $bulkImportId,
            'schools_retried' => $retryCount,
        ]);

        return $retryCount;
    }

    /**
     * Get recovery status for a district import
     *
     * @param int $bulkImportId
     * @return array Recovery information
     */
    public function getRecoveryStatus(int $bulkImportId): array
    {
        $bulkImport = BulkImport::findOrFail($bulkImportId);

        if (!$bulkImport->isDistrictImport()) {
            throw new Exception("Not a district-level import");
        }

        $schools = $bulkImport->schools()->get();

        $failedSchools = $schools->filter(fn($s) => $s->pivot->status === 'failed');
        $partialSchools = $schools->filter(fn($s) => $s->pivot->status === 'partial');
        $successfulSchools = $schools->filter(fn($s) => $s->pivot->status === 'success');
        $pendingSchools = $schools->filter(fn($s) => $s->pivot->status === 'pending');

        return [
            'import_id' => $bulkImport->id,
            'import_status' => $bulkImport->status,
            'total_schools' => $bulkImport->total_schools,
            'schools_summary' => [
                'pending' => $pendingSchools->count(),
                'successful' => $successfulSchools->count(),
                'partial' => $partialSchools->count(),
                'failed' => $failedSchools->count(),
            ],
            'failed_schools' => $failedSchools->map(fn($s) => [
                'school_id' => $s->id,
                'school_code' => $s->pivot->school_code,
                'school_name' => $s->pivot->school_name,
                'status' => $s->pivot->status,
                'error_summary' => $s->pivot->error_summary,
                'can_retry' => true,
            ])->toArray(),
            'partial_schools' => $partialSchools->map(fn($s) => [
                'school_id' => $s->id,
                'school_code' => $s->pivot->school_code,
                'school_name' => $s->pivot->school_name,
                'status' => $s->pivot->status,
                'total_subjects' => $s->pivot->total_subjects,
                'processed_subjects' => $s->pivot->processed_subjects,
                'error_summary' => $s->pivot->error_summary,
                'can_retry' => true,
            ])->toArray(),
            'can_retry_all' => $failedSchools->count() + $partialSchools->count() > 0,
        ];
    }

    /**
     * Extract and parse manifest from extraction directory
     *
     * @param string $extractPath
     * @return array|null
     */
    private function getManifestFromExtraction(string $extractPath): ?array
    {
        $manifestPath = $extractPath . '/manifest.json';

        if (!file_exists($manifestPath)) {
            return null;
        }

        try {
            $content = file_get_contents($manifestPath);
            return json_decode($content, true);
        } catch (Exception $e) {
            Log::warning("Cannot read manifest from extraction", [
                'path' => $manifestPath,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
