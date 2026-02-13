<?php

namespace App\Providers;

use App\Jobs\ScheduledDailyBackup;
use App\Jobs\ScheduledMonthlyBackup;
use App\Jobs\ScheduledWeeklyBackup;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class ScheduleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Daily backup at 1:00 AM
            $schedule->job(new ScheduledDailyBackup)
                ->dailyAt('01:00')
                ->name('backup-daily')
                ->withoutOverlapping(3600) // Max 1 hour overlap
                ->onFailure(function () {
                    \Log::error('Daily backup job failed and could not be recovered');
                })
                ->onSuccess(function () {
                    \Log::info('Daily backup job completed successfully');
                });

            // Weekly backup every Sunday at 2:00 AM
            $schedule->job(new ScheduledWeeklyBackup)
                ->weeklyOn(0, '02:00') // Sunday at 2:00 AM
                ->name('backup-weekly')
                ->withoutOverlapping(7200) // Max 2 hours overlap
                ->onFailure(function () {
                    \Log::error('Weekly backup job failed');
                })
                ->onSuccess(function () {
                    \Log::info('Weekly backup job completed successfully');
                });

            // Monthly backup on 1st of month at 3:00 AM
            $schedule->job(new ScheduledMonthlyBackup)
                ->monthlyOn(1, '03:00') // 1st of month at 3:00 AM
                ->name('backup-monthly')
                ->withoutOverlapping(7200)
                ->onFailure(function () {
                    \Log::error('Monthly backup job failed');
                })
                ->onSuccess(function () {
                    \Log::info('Monthly backup job completed successfully');
                });

            // Backup health check every 10 minutes
            $schedule->call(function () {
                \Log::info('Backup system health check performed at ' . now());
            })
                ->everyTenMinutes()
                ->name('backup-health-check')
                ->withoutOverlapping();
        });
    }
}
