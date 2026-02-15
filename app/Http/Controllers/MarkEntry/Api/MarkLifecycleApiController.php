<?php

namespace App\Http\Controllers\MarkEntry\Api;

use App\Http\Controllers\Controller;
use App\Models\MarkImportBatch;
use App\Services\MarkEntry\Moderation\MarkModerationQueryService;
use App\Services\MarkEntry\Submission\MarkSubmissionService;
use App\Services\MarkEntry\Analytics\MarkAnalyticsService;
use App\Services\MarkEntry\Audit\MarkEntryAuditService;
use Illuminate\Http\Request;

class MarkLifecycleApiController extends Controller {

    private MarkModerationQueryService $moderationQuery;
    private MarkSubmissionService $submissionService;
    private MarkAnalyticsService $analyticsService;
    private MarkEntryAuditService $auditService;

    public function __construct(
        MarkModerationQueryService $moderationQuery,
        MarkSubmissionService $submissionService,
        MarkAnalyticsService $analyticsService,
        MarkEntryAuditService $auditService
    ) {
        $this->moderationQuery = $moderationQuery;
        $this->submissionService = $submissionService;
        $this->analyticsService = $analyticsService;
        $this->auditService = $auditService;
    }

    // ==================== MODERATION ENDPOINTS ====================

    /**
     * GET /api/mark-entry/moderation/pending
     * Get pending batches for moderation
     */
    public function getPendingBatches(Request $request) {
        $perPage = $request->input('per_page', 20);
        $batches = $this->moderationQuery->getPendingReviews($perPage);

        return response()->json([
            'data' => $batches->items(),
            'pagination' => [
                'total' => $batches->total(),
                'per_page' => $batches->perPage(),
                'current_page' => $batches->currentPage(),
                'last_page' => $batches->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/mark-entry/moderation/batch/{batchId}
     * Get batch details with review history
     */
    public function getBatchModeration(MarkImportBatch $batch) {
        return response()->json([
            'batch' => $batch->load(['school', 'subject', 'examType']),
            'reviews' => $this->moderationQuery->getBatchReviews($batch),
            'lifecycle_history' => json_decode($batch->lifecycle_history ?? '[]', true),
        ]);
    }

    /**
     * GET /api/mark-entry/moderation/search
     * Search pending batches
     */
    public function searchPending(Request $request) {
        $validated = $request->validate(['query' => 'required|string|min:2']);
        $results = $this->moderationQuery->searchPending($validated['query']);

        return response()->json(['data' => $results]);
    }

    /**
     * GET /api/mark-entry/moderation/stats
     * Get moderator statistics
     */
    public function getModeratorStats() {
        $stats = $this->moderationQuery->getModeratorStats(auth()->id());
        return response()->json(['data' => $stats]);
    }

    // ==================== SUBMISSION ENDPOINTS ====================

    /**
     * GET /api/mark-entry/submission/ready
     * Get batches ready for submission
     */
    public function getReadyForSubmission(Request $request) {
        $perPage = $request->input('per_page', 20);
        $batches = $this->submissionService->getSubmissionReadyBatches($perPage);

        return response()->json([
            'data' => $batches->items(),
            'pagination' => [
                'total' => $batches->total(),
                'per_page' => $batches->perPage(),
                'current_page' => $batches->currentPage(),
                'last_page' => $batches->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/mark-entry/submission/submitted
     * Get submitted batches
     */
    public function getSubmitted(Request $request) {
        $perPage = $request->input('per_page', 20);
        $batches = $this->submissionService->getSubmittedBatches($perPage);

        return response()->json([
            'data' => $batches->items(),
            'pagination' => [
                'total' => $batches->total(),
                'per_page' => $batches->perPage(),
                'current_page' => $batches->currentPage(),
                'last_page' => $batches->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/mark-entry/submission/history/{batchId}
     * Get submission history for a batch
     */
    public function getSubmissionHistory(MarkImportBatch $batch) {
        return response()->json([
            'data' => $this->submissionService->getSubmissionHistory($batch),
        ]);
    }

    // ==================== ANALYTICS ENDPOINTS ====================

    /**
     * GET /api/mark-entry/analytics/overview
     * Get overall analytics
     */
    public function getAnalytics() {
        return response()->json([
            'data' => $this->analyticsService->getOverallAnalytics(),
        ]);
    }

    /**
     * GET /api/mark-entry/analytics/by-year
     * Analytics by exam year
     */
    public function getAnalyticsByYear() {
        return response()->json([
            'data' => $this->analyticsService->getByExamYear(),
        ]);
    }

    /**
     * GET /api/mark-entry/analytics/by-subject
     * Analytics by subject
     */
    public function getAnalyticsBySubject() {
        return response()->json([
            'data' => $this->analyticsService->getBySubject(),
        ]);
    }

    /**
     * GET /api/mark-entry/analytics/by-school
     * Analytics by school
     */
    public function getAnalyticsBySchool() {
        return response()->json([
            'data' => $this->analyticsService->getBySchool(),
        ]);
    }

    /**
     * GET /api/mark-entry/analytics/errors
     * Error rate statistics
     */
    public function getErrorStats() {
        return response()->json([
            'data' => $this->analyticsService->getErrorRateStats(),
        ]);
    }

    /**
     * GET /api/mark-entry/analytics/batch/{batchId}/timeline
     * Get batch processing timeline
     */
    public function getBatchTimeline(MarkImportBatch $batch) {
        return response()->json([
            'data' => $this->analyticsService->getBatchTimeline($batch),
        ]);
    }

    // ==================== AUDIT ENDPOINTS ====================

    /**
     * GET /api/mark-entry/audit/batch/{batchId}
     * Get audit trail for batch
     */
    public function getBatchAuditTrail(Request $request, MarkImportBatch $batch) {
        $perPage = $request->input('per_page', 50);
        $auditTrail = $this->auditService->getBatchAuditTrail($batch, $perPage);

        return response()->json([
            'data' => $auditTrail->items(),
            'pagination' => [
                'total' => $auditTrail->total(),
                'per_page' => $auditTrail->perPage(),
                'current_page' => $auditTrail->currentPage(),
                'last_page' => $auditTrail->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/mark-entry/audit/user/{userId}
     * Get user activity
     */
    public function getUserActivity(Request $request, $userId) {
        $perPage = $request->input('per_page', 50);
        $activity = $this->auditService->getUserActivity($userId, $perPage);

        return response()->json([
            'data' => $activity->items(),
            'pagination' => [
                'total' => $activity->total(),
                'per_page' => $activity->perPage(),
                'current_page' => $activity->currentPage(),
                'last_page' => $activity->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/mark-entry/audit/batch/{batchId}/summary
     * Get batch activity summary
     */
    public function getBatchActivitySummary(MarkImportBatch $batch) {
        return response()->json([
            'data' => $this->auditService->getBatchActivitySummary($batch),
        ]);
    }

    /**
     * GET /api/mark-entry/audit/batch/{batchId}/modifications
     * Get modification report for batch
     */
    public function getBatchModifications(MarkImportBatch $batch) {
        return response()->json([
            'data' => $this->auditService->getModificationReport($batch),
        ]);
    }

    // ==================== ACTION ENDPOINTS ====================

    /**
     * POST /api/mark-entry/moderation/batch/{batchId}/approve
     * Approve a batch (moderation action)
     */
    public function approveBatchAction(Request $request, MarkImportBatch $batch) {
        $this->authorize('moderate', $batch);
        
        try {
            $validated = $request->validate([
                'feedback' => 'nullable|string|max:1000'
            ]);

            $moderationService = app(\App\Services\MarkEntry\Moderation\MarkModerationService::class);
            $review = $moderationService->approveBatch($batch, auth()->user(), $validated['feedback'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Batch approved successfully',
                'data' => [
                    'batch_id' => $batch->id,
                    'lifecycle_state' => $batch->fresh()->lifecycle_state,
                    'approved_at' => $review->reviewed_at,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * POST /api/mark-entry/moderation/batch/{batchId}/reject
     * Reject a batch (moderation action)
     */
    public function rejectBatchAction(Request $request, MarkImportBatch $batch) {
        $this->authorize('moderate', $batch);
        
        try {
            $validated = $request->validate([
                'reason' => 'required|string|min:10|max:1000'
            ]);

            $moderationService = app(\App\Services\MarkEntry\Moderation\MarkModerationService::class);
            $review = $moderationService->rejectBatch($batch, auth()->user(), $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Batch rejected successfully',
                'data' => [
                    'batch_id' => $batch->id,
                    'lifecycle_state' => $batch->fresh()->lifecycle_state,
                    'rejected_at' => $review->reviewed_at,
                    'rejection_reason' => $batch->fresh()->rejection_reason,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * POST /api/mark-entry/submission/lock/{batchId}
     * Lock a batch for submission (prevents further modifications)
     */
    public function lockBatchAction(Request $request, MarkImportBatch $batch) {
        $this->authorize('lock', $batch);
        
        try {
            $moderationService = app(\App\Services\MarkEntry\Moderation\MarkModerationService::class);
            $approval = $this->submissionService->lockBatch($batch, auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'Batch locked successfully',
                'data' => [
                    'batch_id' => $batch->id,
                    'lifecycle_state' => $batch->fresh()->lifecycle_state,
                    'locked_at' => $approval->approved_at,
                    'locked_by' => auth()->user()->name,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * POST /api/mark-entry/submission/unlock/{batchId}
     * Unlock a batch (admin only - allows resubmission)
     */
    public function unlockBatchAction(Request $request, $batchId = null) {
        // Handle both route parameter and query string
        $batchId = $batchId ?? $request->input('batch_id') ?? $request->input('batchId');
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
            $isAdmin = $user && ($user->hasRole('admin') || $user->hasPermissionTo('mark-entry.admin'));
            
            \Log::info('Admin check', [
                'user_id' => $user->id,
                'is_admin' => $isAdmin,
                'roles' => $user->roles->pluck('name')->toArray(),
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
        } catch (\Exception $e) {
            \Log::error('Unlock batch error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
