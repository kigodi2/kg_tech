<?php

namespace App\Services\MarkImport;

use App\Models\MarkImportBatch;
use App\Models\RawMark;
use Illuminate\Database\Eloquent\Collection;
use Exception;

/**
 * Mark Row Locking Service
 * 
 * Manages row-level locking after mark processing.
 * Once locked:
 * - Rows cannot be updated or deleted
 * - Only authorized roles can unlock
 * - All lock/unlock actions are logged for audit
 */
class MarkRowLockingService
{
    /**
     * Lock all processed rows in a batch
     * 
     * Called after successful validation and processing.
     * Prevents accidental or malicious modification of marks.
     */
    public function lockBatchRows(MarkImportBatch $batch, int $userId): array
    {
        $rawMarks = $batch->rawMarks()->unlocked()->get();

        $lockedCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($rawMarks as $rawMark) {
            try {
                $rawMark->lock($userId);
                $lockedCount++;
            } catch (Exception $e) {
                $failedCount++;
                $errors[] = [
                    'row_number' => $rawMark->row_number,
                    'index_number' => $rawMark->candidate_index_number,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Log batch lock action
        \Log::info("Batch {$batch->batch_code} rows locked", [
            'batch_id' => $batch->id,
            'locked_count' => $lockedCount,
            'failed_count' => $failedCount,
            'locked_by' => $userId,
        ]);

        return [
            'success' => $failedCount === 0,
            'locked_count' => $lockedCount,
            'failed_count' => $failedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Lock specific rows in a batch
     */
    public function lockSpecificRows(array $rowIds, int $userId): array
    {
        $rawMarks = RawMark::whereIn('id', $rowIds)
            ->unlocked()
            ->get();

        $lockedCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($rawMarks as $rawMark) {
            try {
                $rawMark->lock($userId);
                $lockedCount++;
            } catch (Exception $e) {
                $failedCount++;
                $errors[] = [
                    'row_id' => $rawMark->id,
                    'index_number' => $rawMark->candidate_index_number,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => $failedCount === 0,
            'locked_count' => $lockedCount,
            'failed_count' => $failedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Unlock all rows in a batch (restricted action)
     * 
     * Only authorized roles should call this.
     * All unlocks are logged for audit trail.
     */
    public function unlockBatchRows(MarkImportBatch $batch, int $userId, ?string $reason = null): array
    {
        $rawMarks = $batch->rawMarks()->locked()->get();

        $unlockedCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($rawMarks as $rawMark) {
            try {
                $rawMark->unlock($userId);
                $unlockedCount++;
            } catch (Exception $e) {
                $failedCount++;
                $errors[] = [
                    'row_number' => $rawMark->row_number,
                    'index_number' => $rawMark->candidate_index_number,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Log batch unlock action with reason
        \Log::warning("Batch {$batch->batch_code} rows unlocked", [
            'batch_id' => $batch->id,
            'unlocked_count' => $unlockedCount,
            'failed_count' => $failedCount,
            'unlocked_by' => $userId,
            'reason' => $reason ?? 'No reason provided',
        ]);

        return [
            'success' => $failedCount === 0,
            'unlocked_count' => $unlockedCount,
            'failed_count' => $failedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Unlock specific row (restricted action)
     */
    public function unlockSpecificRow(int $rowId, int $userId, ?string $reason = null): array
    {
        $rawMark = RawMark::findOrFail($rowId);

        try {
            $rawMark->unlock($userId);

            \Log::warning("RawMark row {$rowId} unlocked", [
                'index_number' => $rawMark->candidate_index_number,
                'batch_id' => $rawMark->mark_import_batch_id,
                'unlocked_by' => $userId,
                'reason' => $reason ?? 'No reason provided',
            ]);

            return [
                'success' => true,
                'message' => 'Row unlocked successfully',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get locked rows count in batch
     */
    public function getLockedRowsCount(MarkImportBatch $batch): int
    {
        return $batch->rawMarks()->locked()->count();
    }

    /**
     * Get unlocked rows count in batch
     */
    public function getUnlockedRowsCount(MarkImportBatch $batch): int
    {
        return $batch->rawMarks()->unlocked()->count();
    }

    /**
     * Check if row is locked
     */
    public function isRowLocked(int $rowId): bool
    {
        return RawMark::findOrFail($rowId)->is_locked;
    }

    /**
     * Prevent updates to locked rows
     * 
     * Call this in update operations to ensure locked rows cannot be modified.
     */
    public function preventLockedRowUpdate(RawMark $rawMark): void
    {
        if ($rawMark->is_locked) {
            throw new Exception("These marks are submitted and locked for processing.");
        }
    }

    /**
     * Prevent deletion of locked rows
     */
    public function preventLockedRowDelete(RawMark $rawMark): void
    {
        if ($rawMark->is_locked) {
            throw new Exception("These marks are submitted and locked for processing.");
        }
    }

    /**
     * Get locking status report for batch
     */
    public function getBatchLockingStatus(MarkImportBatch $batch): array
    {
        $lockedCount = $this->getLockedRowsCount($batch);
        $unlockedCount = $this->getUnlockedRowsCount($batch);
        $totalCount = $batch->total_records;

        $lockPercentage = $totalCount > 0 ? round(($lockedCount / $totalCount) * 100, 2) : 0;

        return [
            'batch_id' => $batch->id,
            'batch_code' => $batch->batch_code,
            'total_rows' => $totalCount,
            'locked_rows' => $lockedCount,
            'unlocked_rows' => $unlockedCount,
            'lock_percentage' => $lockPercentage,
            'all_locked' => $lockedCount === $totalCount && $totalCount > 0,
            'fully_unlocked' => $unlockedCount === $totalCount && $totalCount > 0,
        ];
    }

    /**
     * Get audit log of lock/unlock actions
     * 
     * Note: Depends on Laravel's native logging to storage/logs
     */
    public function getAuditLog(MarkImportBatch $batch, int $limit = 50): array
    {
        $logFile = storage_path('logs/laravel.log');

        if (!file_exists($logFile)) {
            return [];
        }

        $logs = [];
        $lines = file($logFile);
        $reversedLines = array_reverse($lines);

        foreach ($reversedLines as $line) {
            if (str_contains($line, $batch->batch_code) || str_contains($line, "batch_id\": {$batch->id}")) {
                if (str_contains($line, 'unlocked') || str_contains($line, 'locked')) {
                    $logs[] = trim($line);
                    if (count($logs) >= $limit) {
                        break;
                    }
                }
            }
        }

        return array_reverse($logs);
    }
}
