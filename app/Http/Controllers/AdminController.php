<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Candidate;
use App\Models\School;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\GovernanceAuditLog;
use App\Models\RawMark;
use App\Models\MarkImportBatch;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $stats = [
            'users_count' => User::count(),
            'candidates_count' => Candidate::count(),
            'schools_count' => School::count(),
            'exam_types_count' => ExamType::count(),
            'exam_years_count' => ExamYear::count(),
            'total_marks_count' => RawMark::count(),
            'import_batches_count' => MarkImportBatch::count(),
        ];

        $activityRange = (string) $request->query('activity_range', '24h');

        if (!in_array($activityRange, ['today', '24h', '7d'], true)) {
            $activityRange = '24h';
        }

        $rangeStart = match ($activityRange) {
            'today' => now()->startOfDay(),
            '7d' => now()->subDays(7),
            default => now()->subDay(),
        };

        $rangeLabel = match ($activityRange) {
            'today' => 'Today',
            '7d' => 'Last 7 Days',
            default => 'Last 24 Hours',
        };

        $activityLogs = GovernanceAuditLog::query()
            ->where('created_at', '>=', $rangeStart);

        $activitySummary = [
            'events' => (clone $activityLogs)->count(),
            'active_users' => (clone $activityLogs)
                ->get(['user_id', 'admin_id'])
                ->flatMap(function (GovernanceAuditLog $log): array {
                    return array_filter([
                        $log->user_id ? 'user:' . $log->user_id : null,
                        $log->admin_id ? 'admin:' . $log->admin_id : null,
                    ]);
                })
                ->unique()
                ->count(),
            'logins' => GovernanceAuditLog::query()
                ->whereIn('action', [
                    GovernanceAuditLog::ACTION_LOGIN_SUCCESSFUL,
                    GovernanceAuditLog::ACTION_LOGIN_FAILED,
                ])
                ->where('created_at', '>=', $rangeStart)
                ->count(),
            'imports' => GovernanceAuditLog::query()
                ->whereIn('action', [
                    GovernanceAuditLog::ACTION_IMPORT_INITIATED,
                    GovernanceAuditLog::ACTION_IMPORT_COMPLETED,
                    GovernanceAuditLog::ACTION_IMPORT_FAILED,
                ])
                ->where('created_at', '>=', $rangeStart)
                ->count(),
            'latest_activity_at' => GovernanceAuditLog::query()->latest('created_at')->value('created_at'),
            'range' => $activityRange,
            'range_label' => $rangeLabel,
        ];

        $topAction = (clone $activityLogs)
            ->selectRaw('action, COUNT(*) as aggregate')
            ->groupBy('action')
            ->orderByDesc('aggregate')
            ->first();

        $activitySummary['busiest_workflow'] = $topAction ? $this->formatAction((string) $topAction->action) : 'No activity';
        $activitySummary['busiest_workflow_count'] = $topAction ? (int) $topAction->aggregate : 0;

        $activityModules = collect([
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
        ])->map(function (array $actions, string $label) use ($rangeStart): array {
            return [
                'label' => $label,
                'count' => GovernanceAuditLog::query()
                    ->whereIn('action', $actions)
                    ->where('created_at', '>=', $rangeStart)
                    ->count(),
            ];
        })->sortByDesc('count')->values();

        $recentActivities = GovernanceAuditLog::query()
            ->with(['user:id,name', 'admin:id,name'])
            ->where('created_at', '>=', $rangeStart)
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(function (GovernanceAuditLog $log): array {
                return [
                    'time' => $log->created_at,
                    'actor' => $log->user?->name
                        ?? $log->admin?->name
                        ?? data_get($log->data, 'email')
                        ?? 'System',
                    'action' => $this->formatAction((string) $log->action),
                    'summary' => $this->describeActivity($log),
                    'tone' => $this->actionTone((string) $log->action),
                ];
            });

        return view('admin.dashboard', compact('stats', 'activitySummary', 'activityModules', 'recentActivities'));
    }

    private function formatAction(string $action): string
    {
        return str($action)->replace('_', ' ')->title()->toString();
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
            GovernanceAuditLog::ACTION_CANDIDATE_RECLAIMED => 'Reclaimed candidate ' . (string) data_get($log->data, 'candidate_id') . ' from ' . (string) data_get($log->data, 'old_school_name', 'another school') . '.',
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
            GovernanceAuditLog::ACTION_CANDIDATE_RECLAIMED,
            GovernanceAuditLog::ACTION_USER_SUSPENDED => 'warning',
            default => 'gray',
        };
    }
}
