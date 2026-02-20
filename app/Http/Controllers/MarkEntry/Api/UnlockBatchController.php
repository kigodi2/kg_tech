<?php

namespace App\Http\Controllers\MarkEntry\Api;

use App\Http\Controllers\Controller;
use App\Models\MarkImportBatch;
use App\Services\MarkEntry\Submission\MarkSubmissionService;
use App\Services\MarkEntry\Audit\MarkEntryAuditService;
use Illuminate\Http\Request;

class UnlockBatchController extends Controller {

    private MarkSubmissionService $submissionService;
    private MarkEntryAuditService $auditService;

    public function __construct(
        MarkSubmissionService $submissionService,
        MarkEntryAuditService $auditService
    ) {
        $this->submissionService = $submissionService;
        $this->auditService = $auditService;
    }

    /**
     * POST /api/mark-entry/submission/unlock/{batchId}
     * Unlock a batch (admin only - allows resubmission)
     */
    public function unlock(Request $request, $batchId) {
        try {
            \Log::info('Unlock batch request', [
                'batchId' => $batchId,
                'user' => auth()->user() ? auth()->user()->id : null,
                'authenticated' => auth()->check(),
            ]);
            
            // Check authentication
            if (!auth()->check()) {
                \Log::warning('Unlock batch: User not authenticated');
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated. Please login first.'
                ], 401);
            }
            
            // Check authorization
            $user = auth()->user();
            $isAdmin = $user && ($user->isAdmin() || \Illuminate\Support\Facades\Gate::allows('mark-entry.admin'));
            
            \Log::info('Admin check', [
                'user_id' => $user->id,
                'is_admin' => $isAdmin,
                'role' => $user->role?->code,
            ]);
            
            if (!$isAdmin) {
                \Log::warning('Unlock batch: User not admin', ['user_id' => $user->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: Admin access required'
                ], 403);
            }
            
            // Find batch by ID
            $batch = MarkImportBatch::find($batchId);
            if (!$batch) {
                \Log::warning('Unlock batch: Batch not found', ['batchId' => $batchId]);
                return response()->json([
                    'success' => false,
                    'message' => "Batch with ID {$batchId} not found"
                ], 404);
            }
            
            \Log::info('Batch found', ['batch_id' => $batch->id, 'state' => $batch->lifecycle_state]);
            
            // Validate the reason field
            $validated = $request->validate([
                'reason' => 'required|string|min:10|max:1000'
            ]);

            // Store unlock reason in audit trail
            $this->auditService->logAction(
                $batch,
                'unlock_requested',
                auth()->user(),
                ['reason' => $validated['reason']]
            );

            // Unlock the batch
            $this->submissionService->unlockBatch($batch, auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'Batch unlocked successfully',
                'data' => [
                    'batch_id' => $batch->id,
                    'lifecycle_state' => $batch->fresh()->lifecycle_state,
                    'unlocked_at' => now(),
                    'unlocked_by' => auth()->user()->name,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Unlock batch validation error: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Unlock batch error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
