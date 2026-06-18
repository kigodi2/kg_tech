<?php

namespace App\Filament\Admin\Widgets;

use App\Models\GovernanceAuditLog;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class SystemActivityPulseWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.system-activity-pulse';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public function getActivitySummary(): array
    {
        $lastDayLogs = GovernanceAuditLog::query()
            ->where('created_at', '>=', now()->subDay());

        $activityCount = (clone $lastDayLogs)->count();

        $activeUsers = (clone $lastDayLogs)
            ->get(['user_id', 'admin_id'])
            ->flatMap(function (GovernanceAuditLog $log): array {
                return array_filter([
                    $log->user_id ? 'user:' . $log->user_id : null,
                    $log->admin_id ? 'admin:' . $log->admin_id : null,
                ]);
            })
            ->unique()
            ->count();

        $topAction = (clone $lastDayLogs)
            ->selectRaw('action, COUNT(*) as aggregate')
            ->groupBy('action')
            ->orderByDesc('aggregate')
            ->first();

        $latestActivityAt = GovernanceAuditLog::query()
            ->latest('created_at')
            ->value('created_at');

        return [
            'events_last_24h' => $activityCount,
            'active_users_last_24h' => $activeUsers,
            'busiest_workflow' => $topAction ? $this->formatAction((string) $topAction->action) : 'No activity',
            'busiest_workflow_count' => $topAction ? (int) $topAction->aggregate : 0,
            'latest_activity_at' => $latestActivityAt ? now()->parse($latestActivityAt) : null,
            'today_logins' => GovernanceAuditLog::query()
                ->whereIn('action', [
                    GovernanceAuditLog::ACTION_LOGIN_SUCCESSFUL,
                    GovernanceAuditLog::ACTION_LOGIN_FAILED,
                ])
                ->whereDate('created_at', today())
                ->count(),
            'today_imports' => GovernanceAuditLog::query()
                ->whereIn('action', [
                    GovernanceAuditLog::ACTION_IMPORT_INITIATED,
                    GovernanceAuditLog::ACTION_IMPORT_COMPLETED,
                    GovernanceAuditLog::ACTION_IMPORT_FAILED,
                ])
                ->whereDate('created_at', today())
                ->count(),
        ];
    }

    public function getModuleBreakdown(): Collection
    {
        $moduleActions = [
            'Authentication' => [
                GovernanceAuditLog::ACTION_LOGIN_SUCCESSFUL,
                GovernanceAuditLog::ACTION_LOGIN_FAILED,
                GovernanceAuditLog::ACTION_PASSWORD_RESET,
                GovernanceAuditLog::ACTION_PASSWORD_CHANGED,
            ],
            'Imports' => [
                GovernanceAuditLog::ACTION_IMPORT_INITIATED,
                GovernanceAuditLog::ACTION_IMPORT_COMPLETED,
                GovernanceAuditLog::ACTION_IMPORT_FAILED,
            ],
            'User Management' => [
                GovernanceAuditLog::ACTION_USER_CREATED,
                GovernanceAuditLog::ACTION_USER_ROLE_ASSIGNED,
                GovernanceAuditLog::ACTION_USER_SCOPE_ASSIGNED,
                GovernanceAuditLog::ACTION_USER_SUSPENDED,
                GovernanceAuditLog::ACTION_USER_ACTIVATED,
            ],
            'Backups & Restore' => [
                GovernanceAuditLog::ACTION_BACKUP_CREATED,
                GovernanceAuditLog::ACTION_BACKUP_DOWNLOADED,
                GovernanceAuditLog::ACTION_BACKUP_DELETED,
                GovernanceAuditLog::ACTION_RESTORE_INITIATED,
                GovernanceAuditLog::ACTION_RESTORE_COMPLETED,
                GovernanceAuditLog::ACTION_RESTORE_FAILED,
            ],
        ];

        return collect($moduleActions)
            ->map(function (array $actions, string $label): array {
                $count = GovernanceAuditLog::query()
                    ->whereIn('action', $actions)
                    ->where('created_at', '>=', now()->subDay())
                    ->count();

                return [
                    'label' => $label,
                    'count' => $count,
                    'tone' => match ($label) {
                        'Authentication' => 'blue',
                        'Imports' => 'emerald',
                        'User Management' => 'amber',
                        'Backups & Restore' => 'rose',
                        default => 'gray',
                    },
                ];
            })
            ->sortByDesc('count')
            ->values();
    }

    public function getRecentActivities(): Collection
    {
        return GovernanceAuditLog::query()
            ->with(['user:id,name', 'admin:id,name'])
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(function (GovernanceAuditLog $log): array {
                $actor = $log->user?->name
                    ?? $log->admin?->name
                    ?? data_get($log->data, 'email')
                    ?? 'System';

                return [
                    'time' => now()->parse($log->created_at),
                    'actor' => $actor,
                    'action' => $this->formatAction((string) $log->action),
                    'summary' => $this->describeActivity($log),
                    'tone' => $this->actionTone((string) $log->action),
                ];
            });
    }

    private function describeActivity(GovernanceAuditLog $log): string
    {
        return match ($log->action) {
            GovernanceAuditLog::ACTION_LOGIN_SUCCESSFUL => 'Signed in successfully.',
            GovernanceAuditLog::ACTION_LOGIN_FAILED => 'Login failed: ' . ((string) data_get($log->data, 'reason', 'unknown')),
            GovernanceAuditLog::ACTION_IMPORT_INITIATED => 'Started an import workflow.',
            GovernanceAuditLog::ACTION_IMPORT_COMPLETED => ((int) data_get($log->data, 'records_imported', 0)) > 0
                ? number_format((int) data_get($log->data, 'records_imported')) . ' records imported.'
                : 'Import completed successfully.',
            GovernanceAuditLog::ACTION_IMPORT_FAILED => 'Import failed and needs follow-up.',
            GovernanceAuditLog::ACTION_USER_CREATED => 'Created a new user account.',
            GovernanceAuditLog::ACTION_USER_ROLE_ASSIGNED => 'Updated a user role assignment.',
            GovernanceAuditLog::ACTION_USER_SCOPE_ASSIGNED => 'Updated a user scope assignment.',
            GovernanceAuditLog::ACTION_USER_SUSPENDED => 'Suspended a user account.',
            GovernanceAuditLog::ACTION_USER_ACTIVATED => 'Re-activated a user account.',
            GovernanceAuditLog::ACTION_PASSWORD_RESET => 'Triggered a password reset.',
            GovernanceAuditLog::ACTION_PASSWORD_CHANGED => 'Changed a password.',
            GovernanceAuditLog::ACTION_BACKUP_CREATED => 'Created a system backup.',
            GovernanceAuditLog::ACTION_BACKUP_DOWNLOADED => 'Downloaded a backup archive.',
            GovernanceAuditLog::ACTION_BACKUP_DELETED => 'Deleted a backup archive.',
            GovernanceAuditLog::ACTION_RESTORE_INITIATED => 'Started a restore operation.',
            GovernanceAuditLog::ACTION_RESTORE_COMPLETED => 'Completed a restore operation.',
            GovernanceAuditLog::ACTION_RESTORE_FAILED => 'Restore failed and needs review.',
            default => 'Activity recorded in governance audit logs.',
        };
    }

    private function actionTone(string $action): string
    {
        return match ($action) {
            GovernanceAuditLog::ACTION_LOGIN_FAILED,
            GovernanceAuditLog::ACTION_IMPORT_FAILED,
            GovernanceAuditLog::ACTION_RESTORE_FAILED => 'danger',
            GovernanceAuditLog::ACTION_IMPORT_COMPLETED,
            GovernanceAuditLog::ACTION_LOGIN_SUCCESSFUL,
            GovernanceAuditLog::ACTION_RESTORE_COMPLETED,
            GovernanceAuditLog::ACTION_USER_ACTIVATED => 'success',
            GovernanceAuditLog::ACTION_IMPORT_INITIATED,
            GovernanceAuditLog::ACTION_USER_CREATED,
            GovernanceAuditLog::ACTION_USER_ROLE_ASSIGNED,
            GovernanceAuditLog::ACTION_USER_SCOPE_ASSIGNED,
            GovernanceAuditLog::ACTION_PASSWORD_RESET,
            GovernanceAuditLog::ACTION_PASSWORD_CHANGED => 'info',
            GovernanceAuditLog::ACTION_BACKUP_CREATED,
            GovernanceAuditLog::ACTION_BACKUP_DOWNLOADED,
            GovernanceAuditLog::ACTION_BACKUP_DELETED,
            GovernanceAuditLog::ACTION_RESTORE_INITIATED,
            GovernanceAuditLog::ACTION_USER_SUSPENDED => 'warning',
            default => 'gray',
        };
    }

    private function formatAction(string $action): string
    {
        return str($action)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
