<?php

namespace App\Services;

use App\Models\GovernanceAuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AuditReportService
{
    /**
     * Generate monthly audit report
     */
    public static function generateMonthlyReport(int $month = null, int $year = null): array
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $logs = GovernanceAuditLog::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        return [
            'period' => $startDate->format('F Y'),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'summary' => static::generateSummary($logs),
            'statistics' => static::generateStatistics($logs),
            'user_activity' => static::generateUserActivity($logs),
            'security_events' => static::generateSecurityEvents($logs),
            'imports' => static::generateImportReport($logs),
            'logins' => static::generateLoginReport($logs),
        ];
    }

    /**
     * Generate summary statistics
     */
    private static function generateSummary(Collection $logs): array
    {
        return [
            'total_events' => $logs->count(),
            'unique_users' => $logs->where('user_id', '!=', null)->pluck('user_id')->unique()->count(),
            'unique_admins' => $logs->where('admin_id', '!=', null)->pluck('admin_id')->unique()->count(),
            'action_types' => $logs->pluck('action')->unique()->count(),
        ];
    }

    /**
     * Generate detailed statistics
     */
    private static function generateStatistics(Collection $logs): array
    {
        $actions = $logs->groupBy('action')->map(fn($group) => $group->count())->toArray();

        return [
            'actions_by_type' => $actions,
            'logins_successful' => $logs->where('action', 'login_successful')->count(),
            'logins_failed' => $logs->where('action', 'login_failed')->count(),
            'imports_initiated' => $logs->where('action', 'import_initiated')->count(),
            'imports_completed' => $logs->where('action', 'import_completed')->count(),
            'imports_failed' => $logs->where('action', 'import_failed')->count(),
            'users_created' => $logs->where('action', 'user_created')->count(),
            'users_suspended' => $logs->where('action', 'user_suspended')->count(),
            'password_resets' => $logs->where('action', 'password_reset')->count(),
        ];
    }

    /**
     * User activity summary
     */
    private static function generateUserActivity(Collection $logs): array
    {
        return $logs
            ->where('user_id', '!=', null)
            ->groupBy('user_id')
            ->map(function (Collection $userLogs) {
                $user = $userLogs->first()->user;
                return [
                    'user_id' => $user->id ?? null,
                    'user_name' => $user->name ?? 'Unknown',
                    'email' => $user->email ?? 'unknown@example.com',
                    'events' => $userLogs->count(),
                    'logins' => $userLogs->where('action', 'like', 'login%')->count(),
                    'imports' => $userLogs->where('action', 'like', 'import%')->count(),
                ];
            })
            ->sortByDesc('events')
            ->values()
            ->toArray();
    }

    /**
     * Security events (failures and suspicious activity)
     */
    private static function generateSecurityEvents(Collection $logs): array
    {
        $events = [];

        // Failed logins
        $failedLogins = $logs->where('action', 'login_failed');
        if ($failedLogins->count() > 0) {
            $events[] = [
                'type' => 'Failed Logins',
                'count' => $failedLogins->count(),
                'details' => $failedLogins
                    ->groupBy('data->email')
                    ->map(fn($group) => [
                        'email' => $group->first()['data']['email'] ?? 'unknown',
                        'attempts' => $group->count(),
                    ])
                    ->values()
                    ->toArray(),
            ];
        }

        // Unauthorized scope attempts
        $unauthorizedAttempts = $logs->where('action', 'import_failed')
            ->filter(fn($log) => ($log->data['reason'] ?? null) === 'unauthorized_scope');
        
        if ($unauthorizedAttempts->count() > 0) {
            $events[] = [
                'type' => 'Unauthorized Scope Attempts',
                'count' => $unauthorizedAttempts->count(),
                'details' => 'Users attempted to access data outside their scope',
            ];
        }

        // Account suspensions
        $suspensions = $logs->where('action', 'user_suspended');
        if ($suspensions->count() > 0) {
            $events[] = [
                'type' => 'Account Suspensions',
                'count' => $suspensions->count(),
                'details' => $suspensions
                    ->pluck('user.name')
                    ->unique()
                    ->implode(', '),
            ];
        }

        // Failed imports
        $failedImports = $logs->where('action', 'import_failed');
        if ($failedImports->count() > 0) {
            $events[] = [
                'type' => 'Failed Imports',
                'count' => $failedImports->count(),
                'details' => 'Check import logs for error details',
            ];
        }

        return $events;
    }

    /**
     * Import statistics
     */
    private static function generateImportReport(Collection $logs): array
    {
        $completed = $logs->where('action', 'import_completed');
        $failed = $logs->where('action', 'import_failed');
        $total = $completed->count() + $failed->count();

        $totalRecords = $completed->sum(fn($log) => $log->data['records_imported'] ?? 0);

        return [
            'total_imports' => $total,
            'successful_imports' => $completed->count(),
            'failed_imports' => $failed->count(),
            'success_rate' => $total > 0 ? round(($completed->count() / $total) * 100) : 0,
            'total_records_imported' => $totalRecords,
            'average_records_per_import' => $completed->count() > 0 ? round($totalRecords / $completed->count()) : 0,
        ];
    }

    /**
     * Login statistics
     */
    private static function generateLoginReport(Collection $logs): array
    {
        $successful = $logs->where('action', 'login_successful');
        $failed = $logs->where('action', 'login_failed');
        $total = $successful->count() + $failed->count();

        return [
            'total_login_attempts' => $total,
            'successful_logins' => $successful->count(),
            'failed_logins' => $failed->count(),
            'success_rate' => $total > 0 ? round(($successful->count() / $total) * 100) : 0,
            'unique_users_logged_in' => $successful->pluck('user_id')->unique()->count(),
            'peak_hour' => static::findPeakHour($successful),
        ];
    }

    /**
     * Find the peak hour for logins
     */
    private static function findPeakHour(Collection $logins): string
    {
        if ($logins->isEmpty()) {
            return 'N/A';
        }

        $hourCounts = $logins
            ->groupBy(fn($log) => $log->created_at->format('H:00-H:59'))
            ->map(fn($group) => $group->count());

        $peakHour = $hourCounts->keys()[array_key_first($hourCounts->toArray())];
        return $peakHour ?? 'N/A';
    }

    /**
     * Export report as PDF
     */
    public static function exportReportAsPdf(array $report): \Barryvdh\DomPDF\PDF
    {
        $pdf = \PDF::loadView('reports.audit-report', compact('report'));
        return $pdf;
    }

    /**
     * Export report as CSV
     */
    public static function exportReportAsCsv(array $report): string
    {
        $csv = "IRMS Audit Report\n";
        $csv .= "Period: " . $report['period'] . "\n";
        $csv .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n\n";

        $csv .= "SUMMARY\n";
        foreach ($report['summary'] as $key => $value) {
            $csv .= ucfirst(str_replace('_', ' ', $key)) . "," . $value . "\n";
        }
        $csv .= "\n";

        $csv .= "STATISTICS\n";
        foreach ($report['statistics'] as $key => $value) {
            if (!is_array($value)) {
                $csv .= ucfirst(str_replace('_', ' ', $key)) . "," . $value . "\n";
            }
        }

        return $csv;
    }
}
