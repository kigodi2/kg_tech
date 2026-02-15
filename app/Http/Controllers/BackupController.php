<?php

namespace App\Http\Controllers;

use App\Models\BackupLog;
use App\Models\SystemSetting;
use App\Services\SQLiteBackupService;
use App\Services\SQLiteRestoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * BackupController
 * 
 * RESTful API for backup and restore operations.
 * All actions require super admin authorization.
 */
class BackupController extends Controller
{
    protected SQLiteBackupService $backupService;
    protected SQLiteRestoreService $restoreService;

    public function __construct(
        SQLiteBackupService $backupService,
        SQLiteRestoreService $restoreService
    ) {
        $this->backupService = $backupService;
        $this->restoreService = $restoreService;
        $this->middleware('auth');
    }

    /**
     * GET /api/backups/status
     * Get backup system health status
     */
    public function status(): JsonResponse
    {
        $this->authorize('viewAny', 'App\Models\Backup');

        try {
            $lastBackup = BackupLog::backupOperations()
                ->successful()
                ->latest()
                ->first();

            $lastRestore = BackupLog::restoreOperations()
                ->latest()
                ->first();

            $failedBackups = BackupLog::backupOperations()
                ->failed()
                ->recent(7)
                ->count();

            return response()->json([
                'status' => 'healthy',
                'last_backup' => $lastBackup ? [
                    'operation' => $lastBackup->getOperationLabel(),
                    'created_at' => $lastBackup->created_at->toIso8601String(),
                    'user' => $lastBackup->user->name ?? 'System',
                    'backup_id' => $lastBackup->data['backup_id'] ?? null,
                ] : null,
                'last_restore' => $lastRestore ? [
                    'operation' => $lastRestore->getOperationLabel(),
                    'created_at' => $lastRestore->created_at->toIso8601String(),
                    'status' => $lastRestore->status,
                ] : null,
                'failed_backups_7d' => $failedBackups,
                'automated_backups_enabled' => SystemSetting::where('key', 'automated_backups_enabled')
                    ->value('value') === 'true',
            ]);
        } catch (\Exception $e) {
            Log::error("Backup status check failed: {$e->getMessage()}");
            return response()->json([
                'status' => 'error',
                'error' => 'Unable to retrieve backup status',
            ], 500);
        }
    }

    /**
     * POST /api/backups/create
     * Create a new backup immediately
     */
    public function create(Request $request): JsonResponse
    {
        $this->authorize('create', 'App\Models\Backup');

        try {
            $admin = Auth::user();
            if (!$admin->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Only administrators can create backups',
                ], 403);
            }

            $result = $this->backupService->createFullBackup(
                $admin,
                $request->input('notes')
            );

            return response()->json([
                'success' => true,
                'message' => 'Backup created successfully',
                'backup' => [
                    'id' => $result['backup_id'],
                    'size' => $result['size'],
                    'size_mb' => round($result['size'] / (1024 * 1024), 2),
                    'checksum' => $result['checksum'],
                    'encrypted' => $result['encrypted'],
                    'created_at' => $result['created_at'],
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error("Backup creation failed: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => 'Backup creation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/backups/logs
     * Get backup operation logs
     */
    public function logs(Request $request): JsonResponse
    {
        $this->authorize('viewAny', 'App\Models\Backup');

        try {
            $logs = BackupLog::query()
                ->with('user')
                ->when($request->input('operation'), fn($q) => 
                    $q->where('operation', $request->input('operation'))
                )
                ->when($request->input('status'), fn($q) => 
                    $q->where('status', $request->input('status'))
                )
                ->latest()
                ->paginate(50);

            return response()->json([
                'success' => true,
                'data' => $logs->map(fn($log) => [
                    'id' => $log->id,
                    'operation' => $log->getOperationLabel(),
                    'status' => $log->status,
                    'user' => $log->user->name ?? 'System',
                    'created_at' => $log->created_at->toIso8601String(),
                    'data' => $log->data,
                ]),
                'pagination' => [
                    'total' => $logs->total(),
                    'per_page' => $logs->perPage(),
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/backups/validate
     * Validate a backup file before restore
     */
    public function validateBackup(Request $request): JsonResponse
    {
        $this->authorize('restore', 'App\Models\Backup');

        $request->validate([
            'backup_path' => 'required|string',
        ]);

        try {
            $backupPath = $request->input('backup_path');
            $validation = $this->restoreService->validateBackup($backupPath);

            return response()->json([
                'success' => true,
                'valid' => $validation['valid'],
                'errors' => $validation['errors'] ?? [],
                'manifest' => $validation['manifest'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/backups/simulate-restore
     * Simulate restore to validate backup integrity
     */
    public function simulateRestore(Request $request): JsonResponse
    {
        $this->authorize('simulate', 'App\Models\Backup');

        $request->validate([
            'backup_path' => 'required|string',
        ]);

        try {
            $backupPath = $request->input('backup_path');
            $admin = Auth::user();

            $result = $this->restoreService->simulateRestore($backupPath, $admin);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] 
                    ? 'Restore simulation completed' 
                    : 'Restore simulation failed',
                'simulation' => [
                    'passed' => $result['success'],
                    'database' => $result['database'] ?? null,
                    'files' => $result['files'] ?? null,
                    'warnings' => $result['warnings'] ?? [],
                    'error' => $result['error'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Restore simulation failed: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => 'Simulation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/backups/restore
     * Perform actual restore from backup
     * 
     * WARNING: This will replace the entire database!
     */
    public function restore(Request $request): JsonResponse
    {
        $this->authorize('restore', 'App\Models\Backup');

        $request->validate([
            'backup_path' => 'required|string',
            'create_snapshot' => 'boolean',
            'confirm' => 'required|boolean',
        ]);

        $admin = Auth::user();

        // Require explicit confirmation due to destructive nature
        if (!$request->boolean('confirm')) {
            return response()->json([
                'success' => false,
                'error' => 'Restore requires explicit confirmation',
            ], 422);
        }

        try {
            $backupPath = $request->input('backup_path');
            $createSnapshot = $request->boolean('create_snapshot', true);

            Log::warning("Admin {$admin->name} initiated database restore from: {$backupPath}");

            $result = $this->restoreService->restore(
                $backupPath,
                $admin,
                $createSnapshot
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'restore' => [
                    'restored_at' => $result['restored_at'],
                    'quarantine_location' => $result['quarantine_location'],
                    'note' => 'Original database backed up in quarantine directory',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Database restore failed: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => 'Restore failed: ' . $e->getMessage(),
                'recovery_steps' => [
                    'Check quarantine directory at storage/backups/quarantine',
                    'Review application logs for detailed error information',
                    'Contact system administrator if automatic recovery was unsuccessful',
                ],
            ], 500);
        }
    }

    /**
     * GET /api/backups/health-metrics
     * Get backup system health metrics for dashboard
     */
    public function healthMetrics(): JsonResponse
    {
        $this->authorize('viewAny', 'App\Models\Backup');

        try {
            $now = now();
            
            $dailyStatus = BackupLog::backupOperations()
                ->where('created_at', '>=', $now->subDay())
                ->latest()
                ->first();

            $weeklyStatus = BackupLog::backupOperations()
                ->where('created_at', '>=', $now->subWeek())
                ->latest()
                ->first();

            $monthlyStatus = BackupLog::backupOperations()
                ->where('created_at', '>=', $now->subMonth())
                ->latest()
                ->first();

            $failureRate = BackupLog::backupOperations()
                ->where('created_at', '>=', $now->subDays(30))
                ->count() > 0 
                ? (BackupLog::backupOperations()
                    ->where('created_at', '>=', $now->subDays(30))
                    ->where('status', 'failed')
                    ->count() / BackupLog::backupOperations()
                    ->where('created_at', '>=', $now->subDays(30))
                    ->count()) * 100
                : 0;

            return response()->json([
                'success' => true,
                'metrics' => [
                    'daily_backup' => [
                        'status' => $dailyStatus?->status ?? 'pending',
                        'last_run' => $dailyStatus?->created_at->toIso8601String(),
                    ],
                    'weekly_backup' => [
                        'status' => $weeklyStatus?->status ?? 'pending',
                        'last_run' => $weeklyStatus?->created_at->toIso8601String(),
                    ],
                    'monthly_backup' => [
                        'status' => $monthlyStatus?->status ?? 'pending',
                        'last_run' => $monthlyStatus?->created_at->toIso8601String(),
                    ],
                    'failure_rate_30d' => round($failureRate, 2) . '%',
                    'total_backups' => BackupLog::backupOperations()->count(),
                    'successful_backups' => BackupLog::backupOperations()->successful()->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
