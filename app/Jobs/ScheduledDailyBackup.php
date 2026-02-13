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
 * ScheduledDailyBackup Job
 * 
 * Daily incremental SQLite backup job.
 * Queued for non-blocking execution.
 */
class ScheduledDailyBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->queue = 'backups';
        $this->tries = 3;
        $this->timeout = 3600; // 1 hour timeout
        $this->backoff = [300, 600, 1200]; // Exponential backoff: 5, 10, 20 minutes
    }

    public function handle(SQLiteBackupService $backupService): void
    {
        try {
            // Get system admin for backup attribution
            $admin = User::where('is_admin', true)
                ->orderBy('id')
                ->first();

            if (!$admin) {
                Log::warning('No admin user found for scheduled backup');
                return;
            }

            // Check if last backup was successful (avoid duplicate backups)
            $lastBackupTime = SystemSetting::where('key', 'last_backup_timestamp')
                ->value('value');

            if ($lastBackupTime) {
                $lastTime = strtotime($lastBackupTime);
                if (time() - $lastTime < 82800) { // 23 hours
                    Log::info('Skipping scheduled backup - recent backup exists');
                    return;
                }
            }

            Log::info('Starting scheduled daily backup...');

            // Create backup
            $result = $backupService->createFullBackup(
                $admin,
                'Automated daily backup'
            );

            // Update last backup timestamp
            SystemSetting::updateOrCreate(
                ['key' => 'last_backup_timestamp'],
                ['value' => now()->toIso8601String()]
            );

            // Update backup status
            SystemSetting::updateOrCreate(
                ['key' => 'last_backup_status'],
                ['value' => 'success']
            );

            Log::info('Daily backup completed successfully', [
                'backup_id' => $result['backup_id'],
                'size' => $result['size'],
            ]);
        } catch (\Exception $e) {
            Log::error('Daily backup failed: ' . $e->getMessage());

            // Update failure status
            SystemSetting::updateOrCreate(
                ['key' => 'last_backup_status'],
                ['value' => 'failed']
            );

            // Re-throw to trigger retry
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Daily backup job failed after retries: ' . $exception->getMessage());

        // Send alert to admins
        $admins = User::where('is_admin', true)->get();
        foreach ($admins as $admin) {
            // You can implement email/notification here
        }
    }
}
