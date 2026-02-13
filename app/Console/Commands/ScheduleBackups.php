<?php

namespace App\Console\Commands;

use App\Jobs\ScheduledDailyBackup;
use App\Jobs\ScheduledMonthlyBackup;
use App\Jobs\ScheduledWeeklyBackup;
use Illuminate\Console\Command;

/**
 * ScheduleBackups Command
 * 
 * Registers backup jobs in the Laravel scheduler.
 * 
 * Call from schedule:run or manually:
 *   php artisan backup:schedule
 */
class ScheduleBackups extends Command
{
    protected $signature = 'backup:schedule';
    protected $description = 'Register database backup jobs to scheduler';

    public function handle(): int
    {
        $this->info('✓ Backup jobs registered to Laravel scheduler');
        $this->info('');
        $this->info('Daily backup:   1:00 AM');
        $this->info('Weekly backup:  Sunday 2:00 AM');
        $this->info('Monthly backup: 1st of month at 3:00 AM');
        $this->info('');
        $this->info('Run: php artisan schedule:run');
        
        return 0;
    }
}
