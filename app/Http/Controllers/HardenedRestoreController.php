<?php

namespace App\Http\Controllers;

use App\Models\RestoreAuditLog;
use App\Policies\HardenedRestorePolicy;
use App\Services\HardenedRestoreService;
use App\Services\SQLiteBackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * HardenedRestoreController
 * 
 * REST API for production-grade, audit-compliant restore operations.
 * All restore operations are logged and auditable.
 * 
 * Endpoints:
 * - POST /api/restore/validate       - Validate backup before restore
 * - POST /api/restore/confirm        - Get confirmation page data
 * - POST /api/restore/execute        - Execute restore (DESTRUCTIVE)
 * - GET  /api/restore/audit-logs     - View restore audit trail
 * - POST /api/restore/audit-export   - Export audit logs for examination authority
 */
class HardenedRestoreController extends Controller
{
    protected HardenedRestoreService $restoreService;
    protected HardenedRestorePolicy $policy;

    public function __construct(
        HardenedRestoreService $restoreService,
        HardenedRestorePolicy $policy
    ) {
        $this->restoreService = $restoreService;
        $this->policy = $policy;
        $this->middleware('auth');
    }

    /**
     * GET /api/restore/legal-text
     * Get the legal acknowledgment text
     */
    public function getLegalText(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'legal_text' => $this->getLegalAcknowledgmentText(),
            'required_fields' => [
                'legal_acknowledged' => 'boolean (checkbox)',
                'confirmation_text' => 'string ("RESTORE")',
                'restore_reason' => 'string (minimum 10 characters)',
            ],
        ]);
    }

    /**
     * POST /api/restore/validate
     * Validate backup file before proceeding
     */
    public function validateBackup(Request $request): JsonResponse
    {
        $this->authorize('restoreFullSystem', $this->policy);

        $request->validate([
            'backup_path' => 'required|string',
        ]);

        try {
            $backupPath = $request->input('backup_path');
            
            $validation = $this->restoreService->validateRestorePreconditions($backupPath);

            return response()->json([
                'success' => $validation['valid'],
                'valid' => $validation['valid'],
                'errors' => $validation['errors'],
                'warnings' => $validation['warnings'] ?? [],
            ]);

        } catch (\Exception $e) {
            Log::error("Backup validation failed: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => 'Validation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/restore/confirm
     * Get confirmation page with legal text and audit info
     * 
     * This endpoint prepares the UI with all information the operator needs
     */
    public function getConfirmationPage(Request $request): JsonResponse
    {
        $this->authorize('restoreFullSystem', $this->policy);

        $request->validate([
            'backup_id' => 'required|string',
            'backup_filename' => 'required|string',
            'backup_hash' => 'required|string',
        ]);

        try {
            $user = Auth::user();

            return response()->json([
                'success' => true,
                'operator' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role?->name,
                    'id' => $user->id,
                ],
                'backup_info' => [
                    'backup_id' => $request->input('backup_id'),
                    'filename' => $request->input('backup_filename'),
                    'hash' => $request->input('backup_hash'),
                ],
                'legal_acknowledgment' => [
                    'title' => 'EXAMINATION DATA GOVERNANCE NOTICE',
                    'text' => $this->getLegalAcknowledgmentText(),
                    'required_checkbox' => 'I understand and accept full responsibility for this restore operation.',
                    'confirmation_required' => 'Type "RESTORE" in the confirmation field',
                ],
                'audit_notice' => 'This operation will be logged and audited. All actions are recorded with timestamp, operator ID, and backup information.',
                'required_fields' => [
                    'legal_acknowledged' => 'boolean',
                    'confirmation_text' => 'string',
                    'restore_reason' => 'string',
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
     * POST /api/restore/execute
     * DESTRUCTIVE OPERATION: Execute the restore
     * 
     * This is the point of no return. All preconditions must be met.
     */
    public function executeRestore(Request $request): JsonResponse
    {
        $this->authorize('restoreFullSystem', $this->policy);

        $request->validate([
            'backup_path' => 'required|string',
            'backup_id' => 'required|string',
            'backup_filename' => 'required|string',
            'backup_hash' => 'required|string',
            'legal_acknowledged' => 'required|boolean|in:1,true',
            'confirmation_text' => 'required|string|in:RESTORE',
            'restore_reason' => 'required|string|min:10',
        ]);

        $user = Auth::user();
        $backupPath = $request->input('backup_path');

        Log::warning("RESTORE REQUEST INITIATED", [
            'user' => $user->name,
            'backup_id' => $request->input('backup_id'),
            'ip' => $request->ip(),
        ]);

        try {
            // ═══════════════════════════════════════════════════════════
            // Step 1: Validate legal acknowledgment
            // ═══════════════════════════════════════════════════════════
            $legalValidation = $this->restoreService->validateLegalAcknowledgment(
                $request->only([
                    'legal_acknowledged',
                    'confirmation_text',
                    'restore_reason',
                ])
            );

            if (!$legalValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Legal acknowledgment validation failed',
                    'errors' => $legalValidation['errors'],
                ], 422);
            }

            // ═══════════════════════════════════════════════════════════
            // Step 2: Validate backup preconditions
            // ═══════════════════════════════════════════════════════════
            $validation = $this->restoreService->validateRestorePreconditions($backupPath);
            
            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Backup validation failed',
                    'errors' => $validation['errors'],
                ], 422);
            }

            // ═══════════════════════════════════════════════════════════
            // Step 3: Prepare audit data
            // ═══════════════════════════════════════════════════════════
            $auditData = [
                'backup_id' => $request->input('backup_id'),
                'backup_filename' => $request->input('backup_filename'),
                'backup_hash' => $request->input('backup_hash'),
                'scope_type' => 'full',
                'restore_reason' => $request->input('restore_reason'),
                'legal_acknowledgment_text' => $this->getLegalAcknowledgmentText(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            // ═══════════════════════════════════════════════════════════
            // Step 4: EXECUTE RESTORE (POINT OF NO RETURN)
            // ═══════════════════════════════════════════════════════════
            $result = $this->restoreService->executeRestore(
                $backupPath,
                $user,
                $auditData,
                true // Create snapshot
            );

            Log::critical("✅ RESTORE COMPLETED SUCCESSFULLY", [
                'user' => $user->name,
                'backup_id' => $request->input('backup_id'),
                'audit_log_id' => $result['audit_log_id'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Database restore completed successfully and verified',
                'restore' => [
                    'audit_log_id' => $result['audit_log_id'],
                    'restored_at' => $result['restored_at'],
                    'quarantine_location' => $result['quarantine_location'],
                    'notice' => 'Original database backed up in quarantine location. The system is now online.',
                ],
            ]);

        } catch (\Exception $e) {
            Log::error("RESTORE FAILED: " . $e->getMessage(), [
                'user' => $user->name,
                'backup_id' => $request->input('backup_id'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'recovery_instructions' => [
                    'Check storage/backups/quarantine for your current database',
                    'Review application logs in storage/logs for detailed error information',
                    'Contact the system administrator for assistance',
                ],
            ], 500);
        }
    }

    /**
     * GET /api/restore/audit-logs
     * View audit trail of restore operations
     * 
     * Returns only records the user has permission to view:
     * - Super Admins: all restores
     * - Regional Admins: restores in their region
     * - District Admins: restores in their district
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $this->authorize('viewRestoreAuditLogs', $this->policy);

        try {
            $user = Auth::user();
            $query = RestoreAuditLog::with(['user', 'region', 'district'])
                ->latest('created_at');

            // Filter based on user's scope
            if ($user->isAdmin()) {
                // Super admin: see all
            } elseif ($this->isRegionalAdmin($user)) {
                // Regional admin: see only regional restores
                $query->where('scope_type', 'region')
                    ->where('region_id', $user->getScopeId());
            } elseif ($this->isDistrictAdmin($user)) {
                // District admin: see only district restores
                $query->where('scope_type', 'district')
                    ->where('district_id', $user->getScopeId());
            }

            $logs = $query->paginate(50);

            return response()->json([
                'success' => true,
                'data' => $logs->map(fn($log) => [
                    'id' => $log->id,
                    'operator' => $log->user?->name,
                    'operator_role' => $log->user?->role?->name,
                    'backup_id' => $log->backup_id,
                    'backup_filename' => $log->backup_filename,
                    'scope' => $log->getScopeLabel(),
                    'restore_reason' => $log->restore_reason,
                    'status' => $log->status,
                    'status_badge' => $log->getStatusBadge(),
                    'initiated_at' => $log->initiated_at?->toIso8601String(),
                    'executed_at' => $log->executed_at?->toIso8601String(),
                    'completed_at' => $log->completed_at?->toIso8601String(),
                    'legal_acknowledged' => $log->legal_acknowledged,
                    'ip_address' => $log->ip_address,
                    'error' => $log->error_message,
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
     * POST /api/restore/audit-export
     * Export audit logs for examination authority
     * 
     * Returns CSV/PDF suitable for examination governance records
     */
    public function auditExport(Request $request): JsonResponse
    {
        $this->authorize('downloadRestoreAuditReport', $this->policy);

        $request->validate([
            'format' => 'required|in:json,csv',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
        ]);

        try {
            $user = Auth::user();
            $query = RestoreAuditLog::with(['user', 'region', 'district'])
                ->latest('created_at');

            // Apply date filters
            if ($request->filled('from_date')) {
                $query->where('created_at', '>=', $request->input('from_date'));
            }
            if ($request->filled('to_date')) {
                $query->where('created_at', '<=', $request->input('to_date'));
            }

            // Filter based on scope
            if ($user->isAdmin()) {
                // See all
            } elseif ($this->isRegionalAdmin($user)) {
                $query->where('region_id', $user->getScopeId());
            } elseif ($this->isDistrictAdmin($user)) {
                $query->where('district_id', $user->getScopeId());
            }

            $logs = $query->get();
            $format = $request->input('format', 'json');

            if ($format === 'json') {
                return response()->json([
                    'success' => true,
                    'export_date' => now()->toIso8601String(),
                    'exported_by' => $user->name,
                    'record_count' => $logs->count(),
                    'records' => $logs->map(fn($log) => $log->toAuditExport()),
                ]);
            }

            // CSV format
            $csv = $this->generateAuditCsv($logs);
            
            return response()->json([
                'success' => true,
                'csv_data' => $csv,
                'filename' => 'restore-audit-' . now()->format('Y-m-d-His') . '.csv',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate CSV from audit logs
     */
    protected function generateAuditCsv($logs): string
    {
        $output = fopen('php://memory', 'r+');

        // CSV header
        fputcsv($output, [
            'Audit ID',
            'Timestamp',
            'Operator',
            'Operator Role',
            'Scope',
            'Backup ID',
            'Backup Filename',
            'Backup Hash',
            'Restore Reason',
            'Status',
            'Error Message',
            'IP Address',
            'Legal Acknowledged',
            'Duration (seconds)',
        ]);

        // CSV rows
        foreach ($logs as $log) {
            $export = $log->toAuditExport();
            fputcsv($output, [
                $export['audit_id'],
                $export['timestamp'],
                $export['operator'],
                $export['operator_role'],
                $export['scope'],
                $export['backup_restored'],
                $log->backup_filename,
                $export['backup_hash'],
                $export['restore_reason'],
                $export['status'],
                $export['error'] ?? '',
                $export['ip_address'],
                $export['legal_acknowledged'],
                $export['duration_seconds'] ?? '',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Get legal acknowledgment text
     */
    protected function getLegalAcknowledgmentText(): string
    {
        return <<<'TEXT'
This operation will REPLACE the ENTIRE examination database.
All current results, registrations, and marks will be LOST.
This action is irreversible and must be authorized
according to examination data governance regulations.

By proceeding, you confirm:
1. You have authority to perform this operation
2. You have verified this restore is necessary
3. You accept full responsibility for consequences
4. All affected stakeholders have been notified
5. This operation complies with examination regulations

This restore operation will be:
• Logged with complete audit trail
• Recorded with your name, role, and timestamp
• Validated against backup integrity checksums
• Protected with automatic rollback on failure

CONFIRMATION REQUIRED:
You must type "RESTORE" in the confirmation field
and check the acknowledgment box to proceed.
TEXT;
    }

    /**
     * Check if user is regional admin
     */
    protected function isRegionalAdmin($user): bool
    {
        return $user->isRegionalOfficer() 
            && $user->getScopeType() === 'region';
    }

    /**
     * Check if user is district admin
     */
    protected function isDistrictAdmin($user): bool
    {
        return ($user->isDistrictSupervisor() || $user->isDistrictDataEntryOfficer())
            && $user->getScopeType() === 'district';
    }
}
