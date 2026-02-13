<?php

namespace App\Jobs;

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\SQLiteBackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ScheduledMonthlyBackup Job
 * 
 * Monthly immutable archive backup.
 * Stored separately for long-term retention.
 */
class ScheduledMonthlyBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->queue = 'backups';
        $this->tries = 3;
        $this->timeout = 7200;
        $this->backoff = [600, 1200, 1800];
    }

    public function handle(SQLiteBackupService $backupService): void
    {
        try {
            $admin = User::where('is_admin', true)
                ->orderBy('id')
                ->first();

            if (!$admin) {
                Log::warning('No admin user found for scheduled backup');
                return;
            }

            Log::info('Starting scheduled monthly immutable backup...');

            // Create backup
            $result = $backupService->createFullBackup(
                $admin,
                'Automated monthly immutable archive - ' . now()->format('Y-m')
            );

            // Archive to separate location with read-only permissions
            $archivePath = storage_path('backups/archives/monthly');
            if (!is_dir($archivePath)) {
                mkdir($archivePath, 0750, true);
            }

            $archivedFile = $archivePath . '/' . basename($result['path']);
            if (file_exists($result['path'])) {
                copy($result['path'], $archivedFile);
                // Set immutable flag
                chmod($archivedFile, 0440); // Read-only
            }

            // Update metadata
            SystemSetting::updateOrCreate(
                ['key' => 'last_monthly_backup_timestamp'],
                ['value' => now()->toIso8601String()]
            );

            SystemSetting::updateOrCreate(
                ['key' => 'last_monthly_backup_status'],
                ['value' => 'success']
            );

            Log::info('Monthly immutable backup completed', [
                'backup_id' => $result['backup_id'],
                'archived_at' => $archivedFile,
            ]);
        } catch (\Exception $e) {
            Log::error('Monthly backup failed: ' . $e->getMessage());

            SystemSetting::updateOrCreate(
                ['key' => 'last_monthly_backup_status'],
                ['value' => 'failed']
            );

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Monthly backup job failed: ' . $exception->getMessage());
    }
}
