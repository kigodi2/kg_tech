<?php

namespace App\Http\Controllers\MarkEntry\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcseeRolePermission;
use App\Models\AcseeSettingsHistory;
use App\Models\MarkImportBatch;
use App\Models\Role;
use App\Models\SystemEventLog;
use App\Models\SystemSetting;
use App\Services\AcseeAdmin\AcseeSettingsService;
use App\Services\AcseeAdmin\CorrelationIdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MarkEntryAdminController extends Controller
{
    private AcseeSettingsService $settingsService;

    public function __construct(AcseeSettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    // ==================== CONFIGURATION ====================

    public function getSettings(Request $request): JsonResponse
    {
        if (Gate::denies('mark-entry.admin')) {
            return $this->forbidden('You do not have permission to view configuration.');
        }

        try {
            $this->settingsService->ensureDefaults();
            $grouped = $this->settingsService->getGroupedSettings();
            return response()->json(['ok' => true, 'settings' => $grouped]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to load settings');
        }
    }

    public function updateSetting(Request $request, string $key): JsonResponse
    {
        if (Gate::denies('mark-entry.admin')) {
            return $this->forbidden('You do not have permission to change configuration.');
        }

        $validated = $request->validate([
            'value' => 'required|string|max:1000',
        ]);

        try {
            $result = $this->settingsService->updateSetting($key, $validated['value'], auth()->id());

            if (!$result['ok']) {
                return response()->json([
                    'ok' => false,
                    'message' => $result['message'],
                    'correlation_id' => CorrelationIdService::get(),
                ], 422);
            }

            return response()->json(['ok' => true, 'message' => 'Setting updated successfully']);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to update setting');
        }
    }

    public function getSettingHistory(Request $request, string $key): JsonResponse
    {
        if (Gate::denies('mark-entry.admin')) {
            return $this->forbidden('You do not have permission to view setting history.');
        }

        try {
            $history = $this->settingsService->getHistory($key);
            return response()->json(['ok' => true, 'history' => $history]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to load setting history');
        }
    }

    public function restoreSetting(Request $request, int $historyId): JsonResponse
    {
        if (Gate::denies('mark-entry.admin')) {
            return $this->forbidden('You do not have permission to restore settings.');
        }

        try {
            $result = $this->settingsService->restoreFromHistory($historyId, auth()->id());

            if (!$result['ok']) {
                return response()->json([
                    'ok' => false,
                    'message' => $result['message'],
                    'correlation_id' => CorrelationIdService::get(),
                ], 422);
            }

            return response()->json(['ok' => true, 'message' => 'Setting restored successfully']);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to restore setting');
        }
    }

    // ==================== PERMISSIONS ====================

    public function getRoles(Request $request): JsonResponse
    {
        if (Gate::denies('mark-entry.admin')) {
            return $this->forbidden('You do not have permission to view roles.');
        }

        try {
            $roles = Role::all()->map(function ($role) {
                return [
                    'id' => $role->id,
                    'code' => $role->code,
                    'name' => $role->name,
                    'description' => $role->description,
                    'user_count' => $role->users()->count(),
                    'permissions' => AcseeRolePermission::forRole($role->id),
                ];
            });

            $definedPermissions = AcseeRolePermission::definedPermissions();

            return response()->json([
                'ok' => true,
                'roles' => $roles,
                'defined_permissions' => $definedPermissions,
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to load roles');
        }
    }

    public function updateRolePermissions(Request $request, Role $role): JsonResponse
    {
        if (Gate::denies('mark-entry.admin')) {
            return $this->forbidden('You do not have permission to manage permissions.');
        }

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|max:100',
        ]);

        try {
            $definedKeys = array_keys(AcseeRolePermission::definedPermissions());
            $grantedPermissions = $validated['permissions'];

            // Guardrail: prevent removing own admin permission
            if ($role->code === Role::CODE_ADMIN) {
                $adminProtected = ['acsee.admin.configuration', 'acsee.admin.permissions'];
                foreach ($adminProtected as $p) {
                    if (!in_array($p, $grantedPermissions)) {
                        return response()->json([
                            'ok' => false,
                            'message' => "Cannot remove '{$p}' from Admin role — this would cause lockout.",
                            'correlation_id' => CorrelationIdService::get(),
                            'hint' => 'At least one admin must retain configuration and permissions access.',
                        ], 422);
                    }
                }
            }

            foreach ($definedKeys as $permission) {
                $granted = in_array($permission, $grantedPermissions);

                AcseeRolePermission::updateOrCreate(
                    ['role_id' => $role->id, 'permission' => $permission],
                    [
                        'granted' => $granted,
                        'granted_by' => auth()->id(),
                        'granted_at' => now(),
                    ]
                );
            }

            SystemEventLog::record(
                SystemEventLog::CAT_ADMIN,
                'permissions_updated',
                SystemEventLog::STATUS_SUCCESS,
                "Permissions updated for role '{$role->name}'",
                ['role_id' => $role->id, 'role_code' => $role->code, 'permissions' => $grantedPermissions]
            );

            return response()->json(['ok' => true, 'message' => "Permissions updated for {$role->name}"]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to update permissions');
        }
    }

    // ==================== BATCH MANAGEMENT ====================

    public function getBatches(Request $request): JsonResponse
    {
        if (Gate::denies('mark-entry.admin')) {
            return $this->forbidden('You do not have permission to manage batches.');
        }

        try {
            $query = MarkImportBatch::with([
                'school:id,code,name',
                'subject:id,code,name',
                'district:id,code,name',
                'region:id,code,name',
                'importedByUser:id,name,first_name,last_name',
                'approvedByUser:id,name,first_name,last_name',
                'submittedByUser:id,name,first_name,last_name',
                'lockedByUser:id,name,first_name,last_name',
            ]);

            if ($request->filled('exam_year')) {
                $query->where('exam_year', $request->input('exam_year'));
            }
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }
            if ($request->filled('school_id')) {
                $query->where('school_id', $request->input('school_id'));
            }
            if ($request->filled('subject_id')) {
                $query->where('subject_id', $request->input('subject_id'));
            }
            if ($request->filled('district_id')) {
                $query->where('district_id', $request->input('district_id'));
            }
            if ($request->filled('region_id')) {
                $query->where('region_id', $request->input('region_id'));
            }
            if ($request->filled('date_from')) {
                $query->where('created_at', '>=', $request->input('date_from'));
            }
            if ($request->filled('date_to')) {
                $query->where('created_at', '<=', $request->input('date_to') . ' 23:59:59');
            }
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('batch_code', 'like', "%{$search}%")
                      ->orWhereHas('school', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                      ->orWhereHas('subject', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
                });
            }

            $batches = $query->orderByDesc('created_at')->paginate($request->input('per_page', 25));

            return response()->json(['ok' => true, 'batches' => $batches]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to load batches');
        }
    }

    public function getBatchDetail(Request $request, int $id): JsonResponse
    {
        if (Gate::denies('mark-entry.admin')) {
            return $this->forbidden('You do not have permission to view batch details.');
        }

        try {
            $batch = MarkImportBatch::with([
                'school:id,code,name',
                'subject:id,code,name',
                'district:id,code,name',
                'region:id,code,name',
                'importedByUser:id,name,first_name,last_name',
                'approvedByUser:id,name,first_name,last_name',
                'submittedByUser:id,name,first_name,last_name',
                'lockedByUser:id,name,first_name,last_name',
                'importRuns' => fn($q) => $q->orderByDesc('created_at')->limit(10),
                'moderationActions' => fn($q) => $q->orderByDesc('created_at')->limit(10),
                'lifecycleStates' => fn($q) => $q->orderByDesc('created_at')->limit(20),
            ])->find($id);

            if (!$batch) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Batch not found',
                    'correlation_id' => CorrelationIdService::get(),
                ], 404);
            }

            $visibility = $this->explainBatchVisibility($batch);

            return response()->json([
                'ok' => true,
                'batch' => $batch,
                'visibility' => $visibility,
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to load batch details');
        }
    }

    public function unlockBatch(Request $request, int $id): JsonResponse
    {
        if (Gate::denies('mark-entry.admin')) {
            return $this->forbidden('You do not have permission to unlock batches.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:1000',
        ]);

        try {
            $batch = MarkImportBatch::find($id);

            if (!$batch) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Batch not found',
                    'correlation_id' => CorrelationIdService::get(),
                ], 404);
            }

            if (!$batch->canBeUnlocked()) {
                return response()->json([
                    'ok' => false,
                    'message' => "Batch cannot be unlocked — current status is '{$batch->status}'",
                    'correlation_id' => CorrelationIdService::get(),
                    'hint' => 'Only LOCKED batches can be unlocked.',
                ], 422);
            }

            $previousStatus = $batch->status;

            $batch->update([
                'status' => MarkImportBatch::STATUS_APPROVED,
                'locked_by' => null,
                'locked_at' => null,
            ]);

            SystemEventLog::record(
                SystemEventLog::CAT_ADMIN,
                'batch_unlocked',
                SystemEventLog::STATUS_SUCCESS,
                "Batch {$batch->batch_code} unlocked by admin. Reason: {$validated['reason']}",
                [
                    'batch_id' => $batch->id,
                    'batch_code' => $batch->batch_code,
                    'previous_status' => $previousStatus,
                    'new_status' => MarkImportBatch::STATUS_APPROVED,
                    'reason' => $validated['reason'],
                ]
            );

            return response()->json([
                'ok' => true,
                'message' => "Batch {$batch->batch_code} has been unlocked",
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to unlock batch');
        }
    }

    public function recomputeBatchStats(Request $request, int $id): JsonResponse
    {
        if (Gate::denies('mark-entry.admin')) {
            return $this->forbidden('You do not have permission to recompute batch stats.');
        }

        try {
            $batch = MarkImportBatch::find($id);

            if (!$batch) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Batch not found',
                    'correlation_id' => CorrelationIdService::get(),
                ], 404);
            }

            $total = $batch->rawMarks()->count();
            $errors = $batch->rawMarks()->where('has_errors', true)->count();
            $valid = $total - $errors;

            $batch->update([
                'total_records' => $total,
                'valid_records' => $valid,
                'error_records' => $errors,
            ]);

            SystemEventLog::record(
                SystemEventLog::CAT_ADMIN,
                'batch_stats_recomputed',
                SystemEventLog::STATUS_SUCCESS,
                "Stats recomputed for batch {$batch->batch_code}: {$total} total, {$valid} valid, {$errors} errors",
                ['batch_id' => $batch->id, 'total' => $total, 'valid' => $valid, 'errors' => $errors]
            );

            return response()->json([
                'ok' => true,
                'message' => 'Stats recomputed',
                'stats' => ['total' => $total, 'valid' => $valid, 'errors' => $errors],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to recompute batch stats');
        }
    }

    // ==================== SYSTEM LOGS ====================

    public function getLogs(Request $request): JsonResponse
    {
        if (Gate::denies('mark-entry.admin')) {
            return $this->forbidden('You do not have permission to view system logs.');
        }

        try {
            $query = SystemEventLog::with('actor:id,name,first_name,last_name');

            if ($request->filled('category')) {
                $query->byCategory($request->input('category'));
            }
            if ($request->filled('status')) {
                $query->byStatus($request->input('status'));
            }
            if ($request->filled('correlation_id')) {
                $query->byCorrelation($request->input('correlation_id'));
            }
            if ($request->filled('actor_id')) {
                $query->byActor($request->input('actor_id'));
            }
            if ($request->filled('date_from') || $request->filled('date_to')) {
                $query->dateRange($request->input('date_from'), $request->input('date_to'));
            }
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('message', 'like', "%{$search}%")
                      ->orWhere('action', 'like', "%{$search}%")
                      ->orWhere('correlation_id', 'like', "%{$search}%");
                });
            }

            $logs = $query->orderByDesc('created_at')
                ->paginate($request->input('per_page', 50));

            return response()->json(['ok' => true, 'logs' => $logs]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'Failed to load system logs');
        }
    }

    public function exportLogsCsv(Request $request): StreamedResponse
    {
        if (Gate::denies('mark-entry.admin')) {
            abort(403, 'Unauthorized');
        }

        $query = SystemEventLog::with('actor:id,name,first_name,last_name');

        if ($request->filled('category')) {
            $query->byCategory($request->input('category'));
        }
        if ($request->filled('status')) {
            $query->byStatus($request->input('status'));
        }
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $query->dateRange($request->input('date_from'), $request->input('date_to'));
        }

        SystemEventLog::record(
            SystemEventLog::CAT_ADMIN,
            'logs_exported',
            SystemEventLog::STATUS_SUCCESS,
            'System event logs exported to CSV',
            ['filters' => $request->only(['category', 'status', 'date_from', 'date_to'])]
        );

        $filename = 'system_logs_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Date', 'Category', 'Action', 'Status', 'Actor', 'Message', 'Correlation ID', 'IP Address']);

            $query->orderByDesc('created_at')->chunk(500, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->created_at?->toIso8601String(),
                        $log->category,
                        $log->action,
                        $log->status,
                        $log->actor?->name ?? 'System',
                        $log->message,
                        $log->correlation_id,
                        $log->ip_address,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    // ==================== HELPERS ====================

    private function explainBatchVisibility(MarkImportBatch $batch): array
    {
        $reasons = [];

        if ($batch->isLocked()) {
            $reasons[] = ['visible' => true, 'reason' => 'Batch is LOCKED — included in reports'];
        } elseif ($batch->isApproved()) {
            $reasons[] = ['visible' => false, 'reason' => 'Batch is APPROVED but not LOCKED — not in reports until locked'];
        } elseif ($batch->isSubmitted()) {
            $reasons[] = ['visible' => false, 'reason' => 'Batch is SUBMITTED — awaiting approval'];
        } elseif ($batch->isRejected()) {
            $reasons[] = ['visible' => false, 'reason' => 'Batch was REJECTED — needs resubmission'];
        } else {
            $reasons[] = ['visible' => false, 'reason' => "Batch is in '{$batch->status}' state — not visible in reports"];
        }

        return $reasons;
    }

    private function forbidden(string $message): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'message' => $message,
            'correlation_id' => CorrelationIdService::get(),
            'hint' => 'Contact your system administrator for access.',
        ], 403);
    }

    private function serverError(\Throwable $e, string $userMessage): JsonResponse
    {
        $correlationId = CorrelationIdService::get();

        \Log::error("{$userMessage} [{$correlationId}]", [
            'correlation_id' => $correlationId,
            'exception' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        try {
            SystemEventLog::record(
                SystemEventLog::CAT_SYSTEM,
                'error',
                SystemEventLog::STATUS_FAILED,
                $userMessage . ': ' . Str::limit($e->getMessage(), 200),
                [
                    'exception_class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'stack_hash' => md5($e->getTraceAsString()),
                ],
                $correlationId
            );
        } catch (\Throwable $_) {
            // Don't let logging failures cascade
        }

        return response()->json([
            'ok' => false,
            'message' => $userMessage,
            'correlation_id' => $correlationId,
            'hint' => 'If this persists, contact support with the correlation ID.',
        ], 500);
    }
}
