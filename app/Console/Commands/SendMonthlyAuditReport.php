<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendMonthlyAuditReport extends Command
{
    protected $signature = 'audit:send-monthly-report {--month=} {--year=}';

    protected $description = 'Generate and send monthly audit report to all admins';

    public function handle(): int
    {
        $month = $this->option('month') ?? now()->subMonth()->month;
        $year = $this->option('year') ?? now()->subMonth()->year;

        $this->info("Generating audit report for " . date('F Y', mktime(0, 0, 0, $month, 1, $year)));

        try {
            $report = AuditReportService::generateMonthlyReport($month, $year);

            $admins = User::whereHas('role', function ($q) {
                $q->where('code', 'admin');
            })
            ->where('status', 'active')
            ->get();

            if ($admins->isEmpty()) {
                $this->warn('No active admins found to send report to');
                return 1;
            }

            foreach ($admins as $admin) {
                try {
                    Mail::send('emails.monthly-audit-report', [
                        'admin' => $admin,
                        'report' => $report,
                    ], function ($message) use ($admin, $report) {
                        $message->to($admin->email)
                            ->subject("[IRMS Audit Report] {$report['period']}");
                    });

                    $this->info("Report sent to {$admin->email}");
                } catch (\Exception $e) {
                    $this->error("Failed to send report to {$admin->email}: " . $e->getMessage());
                }
            }

            $this->info('Monthly audit report sent successfully!');
            return 0;

        } catch (\Exception $e) {
            $this->error('Error generating audit report: ' . $e->getMessage());
            return 1;
        }
    }
}
