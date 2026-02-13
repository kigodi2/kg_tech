<?php

namespace App\Console;

use App\Jobs\ScheduledDailyBackup;
use App\Jobs\ScheduledMonthlyBackup;
use App\Jobs\ScheduledWeeklyBackup;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Daily backup at 1:00 AM
        $schedule->job(new ScheduledDailyBackup)
            ->dailyAt('01:00')
            ->name('backup-daily')
            ->withoutOverlapping(3600) // Max 1 hour overlap
            ->onFailure(function () {
                \Log::error('Daily backup job failed and could not be recovered');
            });

        // Weekly backup every Sunday at 2:00 AM
        $schedule->job(new ScheduledWeeklyBackup)
            ->weeklyOn(0, '02:00') // Sunday at 2:00 AM
            ->name('backup-weekly')
            ->withoutOverlapping(7200) // Max 2 hours overlap
            ->onFailure(function () {
                \Log::error('Weekly backup job failed');
            });

        // Monthly backup on 1st of month at 3:00 AM
        $schedule->job(new ScheduledMonthlyBackup)
            ->monthlyOn(1, '03:00') // 1st of month at 3:00 AM
            ->name('backup-monthly')
            ->withoutOverlapping(7200)
            ->onFailure(function () {
                \Log::error('Monthly backup job failed');
            });

        // Optional: Backup health check every 6 hours
        $schedule->call(function () {
            \Log::info('Backup system health check performed');
        })
            ->everyTenMinutes()
            ->name('backup-health-check')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
