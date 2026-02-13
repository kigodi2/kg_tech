<?php

namespace App\Services;

use App\Models\GovernanceAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SecurityAlertService
{
    /**
     * Check for suspicious activities and send alerts to admins
     */
    public static function checkAndAlert(): void
    {
        $alerts = [];

        // Alert 1: Multiple failed login attempts
        $failedLogins = GovernanceAuditLog::query()
            ->where('action', 'login_failed')
            ->where('created_at', '>=', now()->subHour())
            ->groupBy('data->email')
            ->havingRaw('count(*) >= 5')
            ->pluck('data->email');

        if ($failedLogins->isNotEmpty()) {
            $alerts[] = [
                'type' => 'MULTIPLE_FAILED_LOGINS',
                'severity' => 'HIGH',
                'description' => 'Multiple failed login attempts detected',
                'details' => "Email addresses with 5+ failed attempts in last hour: " . $failedLogins->join(', '),
            ];
        }

        // Alert 2: Unauthorized scope access attempts
        $scopeFailures = GovernanceAuditLog::query()
            ->where('action', 'import_failed')
            ->where('data->reason', 'unauthorized_scope')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($scopeFailures >= 3) {
            $alerts[] = [
                'type' => 'UNAUTHORIZED_SCOPE_ATTEMPTS',
                'severity' => 'MEDIUM',
                'description' => 'Multiple unauthorized scope access attempts',
                'details' => "$scopeFailures unauthorized scope access attempts in last hour",
            ];
        }

        // Alert 3: Unusual import activity
        $successfulImports = GovernanceAuditLog::query()
            ->where('action', 'import_completed')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        $failedImports = GovernanceAuditLog::query()
            ->where('action', 'import_failed')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        $failureRate = $successfulImports + $failedImports > 0
            ? round(($failedImports / ($successfulImports + $failedImports)) * 100)
            : 0;

        if ($failureRate >= 30) {
            $alerts[] = [
                'type' => 'HIGH_IMPORT_FAILURE_RATE',
                'severity' => 'HIGH',
                'description' => 'High import failure rate detected',
                'details' => "$failureRate% of imports failed in last hour ($failedImports failures, $successfulImports successes)",
            ];
        }

        // Alert 4: Recently suspended users
        $recentlySuspended = GovernanceAuditLog::query()
            ->where('action', 'user_suspended')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($recentlySuspended >= 3) {
            $alerts[] = [
                'type' => 'MULTIPLE_SUSPENSIONS',
                'severity' => 'MEDIUM',
                'description' => 'Multiple user suspensions',
                'details' => "$recentlySuspended users suspended in last 24 hours",
            ];
        }

        // Send alerts
        if (!empty($alerts)) {
            static::sendAlertsToAdmins($alerts);
        }

        return;
    }

    /**
     * Send alerts to all admin users
     */
    private static function sendAlertsToAdmins(array $alerts): void
    {
        $admins = User::whereHas('role', function ($q) {
            $q->where('code', 'admin');
        })
        ->where('status', 'active')
        ->get();

        foreach ($admins as $admin) {
            try {
                Mail::send('emails.security-alert', [
                    'admin' => $admin,
                    'alerts' => $alerts,
                    'timestamp' => now(),
                ], function ($message) use ($admin) {
                    $message->to($admin->email)
                        ->subject('[IRMS Security Alert] Suspicious Activity Detected');
                });
            } catch (\Exception $e) {
                Log::warning("Failed to send security alert to {$admin->email}: " . $e->getMessage());
            }
        }
    }

    /**
     * Check for a specific suspicious activity
     */
    public static function logFailedLogin(string $email, string $reason): void
    {
        $failedAttempts = GovernanceAuditLog::query()
            ->where('action', 'login_failed')
            ->where('data->email', $email)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        // Alert if 5+ failed attempts
        if ($failedAttempts >= 5) {
            static::sendAlertsToAdmins([
                [
                    'type' => 'BRUTE_FORCE_ATTEMPT',
                    'severity' => 'CRITICAL',
                    'description' => 'Possible brute force attack',
                    'details' => "Email: $email - $failedAttempts failed login attempts in the last hour",
                ]
            ]);
        }
    }
}
