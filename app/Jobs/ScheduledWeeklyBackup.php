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
 * ScheduledWeeklyBackup Job
 * 
 * Full weekly SQLite backup with verification.
 */
class ScheduledWeeklyBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->queue = 'backups';
        $this->tries = 3;
        $this->timeout = 7200; // 2 hours timeout
        $this->backoff = [600, 1200, 1800]; // 10, 20, 30 minutes
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

            Log::info('Starting scheduled weekly backup...');

            // Create backup
            $result = $backupService->createFullBackup(
                $admin,
                'Automated weekly full backup'
            );

            // Update metadata
            SystemSetting::updateOrCreate(
                ['key' => 'last_weekly_backup_timestamp'],
                ['value' => now()->toIso8601String()]
            );

            SystemSetting::updateOrCreate(
                ['key' => 'last_weekly_backup_status'],
                ['value' => 'success']
            );

            Log::info('Weekly backup completed successfully', [
                'backup_id' => $result['backup_id'],
                'size' => $result['size'],
            ]);
        } catch (\Exception $e) {
            Log::error('Weekly backup failed: ' . $e->getMessage());

            SystemSetting::updateOrCreate(
                ['key' => 'last_weekly_backup_status'],
                ['value' => 'failed']
            );

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Weekly backup job failed after retries: ' . $exception->getMessage());
    }
}
