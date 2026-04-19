<?php

namespace App\Http\Controllers\MarkEntry\Api;

use App\Http\Controllers\Controller;
use App\Models\CandidateSubjectSelection;
use App\Models\ExamYear;
use App\Models\MarkImportBatch;
use App\Models\RawMark;
use App\Models\Subject;
use App\Models\SubjectMarks;
use App\Services\MarkImport\MarkValidationService;
use App\Services\MarkEntry\Moderation\MarkModerationQueryService;
use App\Services\MarkEntry\Submission\MarkSubmissionService;
use App\Services\MarkEntry\Analytics\MarkAnalyticsService;
use App\Services\MarkEntry\Audit\MarkEntryAuditService;
use Illuminate\Http\Request;

class MarkLifecycleApiController extends Controller
{

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
    public function getPendingBatches(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $batches = $this->moderationQuery->getPendingReviews($perPage);

        return response()->json([
            'success' => true,
            'data' => $batches->items(),
            'pagination' => [
                'total' => $batches->total(),
                'per_page' => $batches->perPage(),
                'current_page' => $batches->currentPage(),
                'last_page' => $batches->lastPage(),
                'has_more' => $batches->hasMorePages(),
            ],
        ]);
    }

    /**
     * GET /api/mark-entry/moderation/batch/{batchId}
     * Get batch details with review history
     */
    public function getBatchModeration(Request $request, MarkImportBatch $batch)
    {
        $this->authorize('view', $batch);

        try {
            $batch->load(['school', 'subject', 'examType', 'region', 'district']);

            $errorCount = $batch->rawMarks()->where('has_errors', true)->count();
            $validCount = $batch->rawMarks()->where('has_errors', false)->count();

            return response()->json([
                'success' => true,
                'batch' => [
                    'id' => $batch->id,
                    'batch_code' => $batch->batch_code,
                    'exam_year' => $batch->exam_year,
                    'status' => $batch->status,
                    'lifecycle_state' => $batch->lifecycle_state,
                    'total_records' => $batch->total_records,
                    // Use live counters from raw_marks so the modal reflects latest row fixes.
                    'valid_records' => $validCount,
                    'error_records' => $errorCount,
                    'error_count' => $errorCount,
                    'valid_count' => $validCount,
                    'school' => [
                        'id' => $batch->school?->id,
                        'code' => $batch->school?->code,
                        'name' => $batch->school?->name,
                    ],
                    'subject' => [
                        'id' => $batch->subject?->id,
                        'code' => $batch->subject?->code,
                        'name' => $batch->subject?->name,
                    ],
                    'district' => [
                        'id' => $batch->district?->id,
                        'code' => $batch->district?->code,
                        'name' => $batch->district?->name,
                    ],
                    'region' => [
                        'id' => $batch->region?->id,
                        'name' => $batch->region?->name,
                    ],
                    'imported_by' => $batch->imported_by,
                    'imported_at' => $batch->imported_at?->format('Y-m-d H:i:s'),
                    'validated_at' => $batch->validated_at?->format('Y-m-d H:i:s'),
                    'locked_at' => $batch->locked_at?->format('Y-m-d H:i:s'),
                    'created_at' => $batch->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $batch->updated_at?->format('Y-m-d H:i:s'),
                ],
                'reviews' => $this->moderationQuery->getBatchReviews($batch)->map(fn($review) => [
                    'id' => $review->id,
                    'status' => $review->status,
                    'feedback' => $review->feedback,
                    'reviewed_at' => $review->reviewed_at?->format('Y-m-d H:i:s'),
                    'reviewer' => [
                        'id' => $review->reviewer?->id,
                        'name' => $review->reviewer?->name,
                    ],
                ])->values(),
                'permissions' => [
                    'can_approve' => auth()->check() && auth()->user()->can('moderate', $batch),
                    'can_reject' => auth()->check() && auth()->user()->can('moderate', $batch),
                    'can_lock' => auth()->check() && auth()->user()->can('lock', $batch),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching batch details', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load batch details'
            ], 500);
        }
    }

    /**
     * GET /api/mark-entry/moderation/search
     * Search pending batches
     */
    public function searchPending(Request $request)
    {
        $validated = $request->validate(['query' => 'required|string|min:2']);
        $results = $this->moderationQuery->searchPending($validated['query']);

        return response()->json(['data' => $results]);
    }

    /**
     * GET /api/mark-entry/moderation/stats
     * Get moderator statistics
     */
    public function getModeratorStats()
    {
        $stats = $this->moderationQuery->getModeratorStats(auth()->id());
        return response()->json(['data' => $stats]);
    }

    /**
     * GET /api/mark-entry/moderation/batch/{batchId}/raw-marks
     * Get raw marks for a batch (with error filtering)
     */
    public function getBatchRawMarks(Request $request, MarkImportBatch $batch)
    {
        try {
            $this->authorize('view', $batch);
            $batch->loadMissing('subject');

            $type = $request->input('type', 'all'); // 'all', 'errors', 'valid'
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);
            $canEditBatch = $this->isBatchEditableForRowUpdates($batch);

            $query = $batch->rawMarks();

            if ($type === 'errors') {
                $query->where('has_errors', true);
            } elseif ($type === 'valid') {
                $query->where('has_errors', false);
            }

            $marks = $query->paginate($perPage, ['*'], 'page', $page);
            $candidateSubjectContext = $this->buildCandidateSubjectContext($batch, $marks->getCollection());

            $formattedMarks = $marks->getCollection()
                ->map(fn($mark) => $this->formatRawMarkRow(
                    $mark,
                    $batch->subject,
                    $canEditBatch,
                    $candidateSubjectContext[(int) ($mark->candidate_id ?? 0)] ?? null
                ))
                ->values();

            return response()->json([
                'success' => true,
                'type' => $type,
                'data' => $formattedMarks,
                'pagination' => [
                    'total' => $marks->total(),
                    'per_page' => $marks->perPage(),
                    'current_page' => $marks->currentPage(),
                    'last_page' => $marks->lastPage(),
                    'has_more' => $marks->hasMorePages(),
                ],
                'counters' => $this->getBatchRowCounters($batch),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching batch raw marks', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load raw marks'
            ], 500);
        }
    }

    /**
     * PATCH /api/mark-entry/moderation/batch/{batchId}/rows/{rowId}/marks
     * Update multiple paper marks in one row and revalidate server-side.
     */
    public function updateBatchRowMarks(Request $request, MarkImportBatch $batch, RawMark $row)
    {
        $this->authorize('moderate', $batch);

        if ((int) $row->mark_import_batch_id !== (int) $batch->id) {
            return response()->json([
                'success' => false,
                'message' => 'Row does not belong to the selected batch.'
            ], 404);
        }

        if (!$this->isBatchEditableForRowUpdates($batch)) {
            return response()->json([
                'success' => false,
                'message' => 'This batch cannot be edited in its current status.'
            ], 403);
        }

        try {
            $validated = $request->validate([
                'marks' => 'required|array|min:1',
                'reason' => 'nullable|string|max:500',
            ]);

            $paperDefinitions = $this->getPaperDefinitions($batch->subject);
            if (empty($paperDefinitions)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paper definitions are missing for this subject.'
                ], 422);
            }

            $allowedCodes = collect($paperDefinitions)->pluck('code')->all();
            $incomingMarks = $validated['marks'];
            $unknownCodes = array_diff(array_keys($incomingMarks), $allowedCodes);

            if (!empty($unknownCodes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unknown paper code(s): ' . implode(', ', $unknownCodes),
                ], 422);
            }

            $oldByField = [];
            $newByField = [];
            foreach ($paperDefinitions as $paper) {
                if (!array_key_exists($paper['code'], $incomingMarks)) {
                    continue;
                }

                $normalized = $this->normalizeMarkInput($incomingMarks[$paper['code']]);
                $field = $paper['field'];
                $oldByField[$field] = $row->{$field};
                $newByField[$field] = $normalized;
                $row->{$field} = $normalized;
            }

            // Clear previous validation state before running server-side validation again.
            $row->has_errors = false;
            $row->error_messages = [];
            $row->has_warnings = false;
            $row->warning_messages = [];
            $row->subject_status = null;
            $row->status_reason = null;
            $row->save();

            $validator = app(MarkValidationService::class);
            $errors = $validator->validateRawMark($row, $batch);

            $row->refresh();
            if (!empty($errors)) {
                $row->has_errors = true;
                $row->error_messages = array_values($errors);
            } else {
                $row->has_errors = false;
                $row->error_messages = [];
            }
            $row->save();
            $row->refresh();

            $reason = trim((string) ($validated['reason'] ?? '')) ?: null;
            foreach ($newByField as $field => $newValue) {
                $oldValue = $oldByField[$field] ?? null;
                if ((string) $oldValue === (string) $newValue) {
                    continue;
                }
                $this->auditService->logChange(
                    $row,
                    auth()->user(),
                    'validation_fix',
                    $field,
                    $oldValue,
                    $newValue,
                    $reason,
                    $request->ip()
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Row marks updated and revalidated.',
                'row' => $this->formatRawMarkRow($row, $batch->subject, $this->isBatchEditableForRowUpdates($batch)),
                'counters' => $this->getBatchRowCounters($batch),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error updating row marks', [
                'batch_id' => $batch->id,
                'row_id' => $row->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update row marks.',
            ], 500);
        }
    }

    // ==================== SUBMISSION ENDPOINTS ====================

    /**
     * GET /api/mark-entry/submission/ready
     * Get batches ready for submission
     */
    public function getReadyForSubmission(Request $request)
    {
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
    public function getSubmitted(Request $request)
    {
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
    public function getSubmissionHistory(MarkImportBatch $batch)
    {
        return response()->json([
            'data' => $this->submissionService->getSubmissionHistory($batch),
        ]);
    }

    // ==================== ANALYTICS ENDPOINTS ====================

    /**
     * GET /api/mark-entry/analytics/overview
     * Get overall analytics
     */
    public function getAnalytics()
    {
        return response()->json([
            'data' => $this->analyticsService->getOverallAnalytics(),
        ]);
    }

    /**
     * GET /api/mark-entry/analytics/by-year
     * Analytics by exam year
     */
    public function getAnalyticsByYear()
    {
        return response()->json([
            'data' => $this->analyticsService->getByExamYear(),
        ]);
    }

    /**
     * GET /api/mark-entry/analytics/by-subject
     * Analytics by subject
     */
    public function getAnalyticsBySubject()
    {
        return response()->json([
            'data' => $this->analyticsService->getBySubject(),
        ]);
    }

    /**
     * GET /api/mark-entry/analytics/by-school
     * Analytics by school
     */
    public function getAnalyticsBySchool()
    {
        return response()->json([
            'data' => $this->analyticsService->getBySchool(),
        ]);
    }

    /**
     * GET /api/mark-entry/analytics/errors
     * Error rate statistics
     */
    public function getErrorStats()
    {
        return response()->json([
            'data' => $this->analyticsService->getErrorRateStats(),
        ]);
    }

    /**
     * GET /api/mark-entry/analytics/batch/{batchId}/timeline
     * Get batch processing timeline
     */
    public function getBatchTimeline(MarkImportBatch $batch)
    {
        return response()->json([
            'data' => $this->analyticsService->getBatchTimeline($batch),
        ]);
    }

    // ==================== AUDIT ENDPOINTS ====================

    /**
     * GET /api/mark-entry/audit/batch/{batchId}
     * Get audit trail for batch
     */
    public function getBatchAuditTrail(Request $request, MarkImportBatch $batch)
    {
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
    public function getUserActivity(Request $request, $userId)
    {
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
    public function getBatchActivitySummary(MarkImportBatch $batch)
    {
        return response()->json([
            'data' => $this->auditService->getBatchActivitySummary($batch),
        ]);
    }

    /**
     * GET /api/mark-entry/audit/batch/{batchId}/modifications
     * Get modification report for batch
     */
    public function getBatchModifications(MarkImportBatch $batch)
    {
        return response()->json([
            'data' => $this->auditService->getModificationReport($batch),
        ]);
    }

    // ==================== ACTION ENDPOINTS ====================

    /**
     * POST /api/mark-entry/moderation/batch/{batchId}/approve
     * Approve a batch (moderation action)
     */
    public function approveBatchAction(Request $request, MarkImportBatch $batch)
    {
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
    public function rejectBatchAction(Request $request, MarkImportBatch $batch)
    {
        $this->authorize('reject', $batch);

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
    public function lockBatchAction(Request $request, $batchId)
    {
        $batch = MarkImportBatch::find($batchId);
        if (!$batch) {
            return response()->json([
                'success' => false,
                'message' => "Batch with ID {$batchId} not found"
            ], 404);
        }

        try {
            $this->authorize('lock', $batch);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Not authorized to lock this batch. Batch must be in approved status and you must have moderator permissions.'
            ], 403);
        }

        try {
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
            \Log::error("Lock batch {$batchId} failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * POST /api/mark-entry/submission/lock-bulk
     * Lock specific batch IDs (visible rows)
     */
    public function lockBulkAction(Request $request)
    {
        $batchIds = $request->input('batch_ids', []);
        if (empty($batchIds) || !is_array($batchIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No batch IDs provided'
            ], 422);
        }

        if (count($batchIds) > 200) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum 200 batches can be locked at once'
            ], 422);
        }

        $user = auth()->user();
        $results = ['locked' => [], 'skipped' => [], 'failed' => []];

        $batches = MarkImportBatch::whereIn('id', $batchIds)
            ->with(['school', 'subject'])
            ->get();

        foreach ($batches as $batch) {
            $entry = [
                'batch_id' => $batch->id,
                'batch_code' => $batch->batch_code,
                'school' => $batch->school->name ?? 'N/A',
                'subject' => $batch->subject->name ?? 'N/A',
            ];

            if ($batch->lifecycle_state === 'submitted' || $batch->status === 'locked') {
                $entry['reason'] = 'Already locked';
                $results['skipped'][] = $entry;
                continue;
            }

            if ($batch->status !== 'approved' && $batch->lifecycle_state !== 'approved') {
                $entry['reason'] = "Status is '{$batch->status}', not approved";
                $results['skipped'][] = $entry;
                continue;
            }

            try {
                $this->authorize('lock', $batch);
            } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                $entry['reason'] = 'Not authorized';
                $results['failed'][] = $entry;
                continue;
            }

            try {
                $this->submissionService->lockBatch($batch, $user);
                $results['locked'][] = $entry;
            } catch (\Exception $e) {
                $entry['reason'] = $e->getMessage();
                $results['failed'][] = $entry;
            }
        }

        // Audit log for bulk operation
        \Log::info("Bulk lock operation by {$user->name}", [
            'user_id' => $user->id,
            'locked_count' => count($results['locked']),
            'skipped_count' => count($results['skipped']),
            'failed_count' => count($results['failed']),
        ]);

        return response()->json([
            'success' => true,
            'message' => sprintf(
                'Bulk lock complete: %d locked, %d skipped, %d failed',
                count($results['locked']),
                count($results['skipped']),
                count($results['failed'])
            ),
            'data' => $results,
        ]);
    }

    /**
     * POST /api/mark-entry/submission/lock-all
     * Lock all approved batches in scope (filtered by exam_year, region, district, etc.)
     */
    public function lockAllInScopeAction(Request $request)
    {
        $user = auth()->user();

        $query = MarkImportBatch::where(function ($q) {
                $q->where('status', 'approved')
                  ->orWhere('lifecycle_state', 'approved');
            })
            ->forUserScope($user)
            ->with(['school', 'subject']);

        // Optional scope filters
        if ($request->filled('exam_year')) {
            $query->where('exam_year', $request->input('exam_year'));
        }
        if ($request->filled('region_id')) {
            $query->where('region_id', $request->input('region_id'));
        }
        if ($request->filled('district_id')) {
            $query->where('district_id', $request->input('district_id'));
        }
        if ($request->filled('school_id')) {
            $query->where('school_id', $request->input('school_id'));
        }

        $batches = $query->limit(200)->get();

        if ($batches->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No approved batches found matching the current scope',
                'data' => ['locked' => [], 'skipped' => [], 'failed' => []],
            ]);
        }

        $results = ['locked' => [], 'skipped' => [], 'failed' => []];

        foreach ($batches as $batch) {
            $entry = [
                'batch_id' => $batch->id,
                'batch_code' => $batch->batch_code,
                'school' => $batch->school->name ?? 'N/A',
                'subject' => $batch->subject->name ?? 'N/A',
            ];

            try {
                $this->authorize('lock', $batch);
            } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                $entry['reason'] = 'Not authorized';
                $results['failed'][] = $entry;
                continue;
            }

            try {
                $this->submissionService->lockBatch($batch, $user);
                $results['locked'][] = $entry;
            } catch (\Exception $e) {
                $entry['reason'] = $e->getMessage();
                $results['failed'][] = $entry;
            }
        }

        // Audit log for bulk operation
        \Log::info("Lock-all-in-scope operation by {$user->name}", [
            'user_id' => $user->id,
            'scope' => $request->only(['exam_year', 'region_id', 'district_id', 'school_id']),
            'total_found' => $batches->count(),
            'locked_count' => count($results['locked']),
            'skipped_count' => count($results['skipped']),
            'failed_count' => count($results['failed']),
        ]);

        return response()->json([
            'success' => true,
            'message' => sprintf(
                'Lock all complete: %d locked, %d skipped, %d failed (of %d found)',
                count($results['locked']),
                count($results['skipped']),
                count($results['failed']),
                $batches->count()
            ),
            'data' => $results,
        ]);
    }

    /**
     * POST /api/mark-entry/submission/unlock/{batchId}
     * Unlock a batch (admin only - allows resubmission)
     */
    public function unlockBatchAction(Request $request, $batchId = null)
    {
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

    private function formatRawMarkRow(RawMark $mark, ?Subject $subject, bool $canEdit, ?array $candidateSubjectContext = null): array
    {
        $papers = $this->getPaperDefinitions($subject);
        $marks = [];
        foreach ($papers as $paper) {
            $marks[$paper['code']] = $mark->{$paper['field']};
        }

        $errors = array_values($mark->error_messages ?? []);
        $invalidFields = $this->inferInvalidFields($errors, $papers);

        return [
            'id' => $mark->id,
            'row_id' => $mark->id,
            'row_number' => $mark->row_number,
            'row_no' => $mark->row_number,
            'candidate_id' => $mark->candidate_id,
            'candidate_index_number' => $mark->candidate_index_number,
            'index_number' => $mark->candidate_index_number,
            'full_name' => $mark->full_name,
            'paper_1_marks' => $mark->paper_1_marks,
            'paper_2_marks' => $mark->paper_2_marks,
            'paper_3_marks' => $mark->paper_3_marks,
            'practical_marks' => $mark->practical_marks,
            'project_marks' => $mark->project_marks,
            'papers' => $papers,
            'marks' => $marks,
            'invalid_fields' => $invalidFields,
            'errors' => $errors,
            'error_messages' => $errors,
            'warnings' => array_values($mark->warning_messages ?? []),
            'has_errors' => (bool) $mark->has_errors,
            'has_warnings' => (bool) $mark->has_warnings,
            'subject_status' => $mark->subject_status,
            'status_reason' => $mark->status_reason,
            'registered_subjects' => $candidateSubjectContext['registered_subjects'] ?? [],
            'registered_subject_count' => $candidateSubjectContext['registered_subject_count'] ?? 0,
            'entered_subject_count' => $candidateSubjectContext['entered_subject_count'] ?? 0,
            'missing_subject_count' => $candidateSubjectContext['missing_subject_count'] ?? 0,
            'current_subject_registered' => $candidateSubjectContext['current_subject_registered'] ?? false,
            'can_edit' => $canEdit,
        ];
    }

    private function buildCandidateSubjectContext(MarkImportBatch $batch, $marks): array
    {
        $candidateIds = collect($marks)
            ->pluck('candidate_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($candidateIds->isEmpty() || !$batch->subject) {
            return [];
        }

        $examYearLabel = (string) $batch->exam_year;
        $examYearId = ExamYear::query()
            ->where('year_label', $examYearLabel)
            ->value('id');

        $selectionQuery = CandidateSubjectSelection::query()
            ->with('subject:id,code,name')
            ->whereIn('candidate_id', $candidateIds)
            ->where('exam_type_id', $batch->subject->exam_type_id)
            ->where('is_active', true)
            ->where(function ($query) use ($examYearId, $examYearLabel) {
                if ($examYearId) {
                    $query->where('exam_year_id', $examYearId)
                        ->orWhere('year', (int) $examYearLabel);
                    return;
                }

                $query->where('year', (int) $examYearLabel);
            })
            ->get();

        $enteredSubjectIdsByCandidate = SubjectMarks::query()
            ->whereIn('candidate_id', $candidateIds)
            ->where('exam_type_id', $batch->subject->exam_type_id)
            ->where('year', (int) $examYearLabel)
            ->whereNull('snapshot_id')
            ->get(['candidate_id', 'subject_id'])
            ->groupBy('candidate_id')
            ->map(fn($rows) => $rows->pluck('subject_id')->map(fn($id) => (int) $id)->unique()->values()->all());

        return $selectionQuery
            ->groupBy('candidate_id')
            ->map(function ($rows, $candidateId) use ($batch, $enteredSubjectIdsByCandidate) {
                $enteredSubjectIds = collect($enteredSubjectIdsByCandidate->get($candidateId, []))
                    ->map(fn($id) => (int) $id)
                    ->all();

                $registeredSubjects = $rows
                    ->filter(fn($selection) => $selection->subject)
                    ->sortBy(fn($selection) => $selection->subject->code)
                    ->values()
                    ->map(function ($selection) use ($enteredSubjectIds) {
                        $subjectId = (int) $selection->subject_id;

                        return [
                            'id' => $subjectId,
                            'code' => $selection->subject->code,
                            'name' => $selection->subject->name,
                            'entered' => in_array($subjectId, $enteredSubjectIds, true),
                        ];
                    })
                    ->values()
                    ->all();

                $enteredCount = collect($registeredSubjects)->where('entered', true)->count();

                return [
                    'registered_subjects' => $registeredSubjects,
                    'registered_subject_count' => count($registeredSubjects),
                    'entered_subject_count' => $enteredCount,
                    'missing_subject_count' => max(count($registeredSubjects) - $enteredCount, 0),
                    'current_subject_registered' => in_array((int) $batch->subject->id, array_column($registeredSubjects, 'id'), true),
                ];
            })
            ->all();
    }

    private function getPaperDefinitions(?Subject $subject): array
    {
        if (!$subject) {
            return [];
        }

        $papers = [];
        $writtenPapers = max(0, (int) ($subject->written_papers ?? 0));
        for ($i = 1; $i <= $writtenPapers; $i++) {
            $papers[] = [
                'code' => "P{$i}",
                'label' => "Paper {$i}",
                'field' => "paper_{$i}_marks",
                'min' => 0,
                'max' => 100,
                'max_mark' => 100,
                'required' => true,
                'sort_order' => $i,
            ];
        }

        if ((bool) ($subject->has_practical ?? false)) {
            $papers[] = [
                'code' => 'PR',
                'label' => 'Practical',
                'field' => 'practical_marks',
                'min' => 0,
                'max' => 50,
                'max_mark' => 50,
                'required' => true,
                'sort_order' => 90,
            ];
        }

        if ((bool) ($subject->has_project ?? false)) {
            $papers[] = [
                'code' => 'PJ',
                'label' => 'Project',
                'field' => 'project_marks',
                'min' => 0,
                'max' => 100,
                'max_mark' => 100,
                'required' => true,
                'sort_order' => 100,
            ];
        }

        return $papers;
    }

    private function inferInvalidFields(array $errors, array $papers): array
    {
        if (empty($errors)) {
            return [];
        }

        $invalid = [];
        foreach ($errors as $error) {
            $message = strtoupper((string) $error);
            if (preg_match_all('/PAPER[\s_]*(\d+)/i', (string) $error, $matches)) {
                foreach ($matches[1] as $number) {
                    $invalid[] = 'P' . (int) $number;
                }
            }
            if (str_contains($message, 'PRACTICAL')) {
                $invalid[] = 'PR';
            }
            if (str_contains($message, 'PROJECT')) {
                $invalid[] = 'PJ';
            }
        }

        $allowed = collect($papers)->pluck('code')->all();
        return array_values(array_unique(array_values(array_intersect($invalid, $allowed))));
    }

    private function normalizeMarkInput($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return $value;
    }

    private function isBatchEditableForRowUpdates(MarkImportBatch $batch): bool
    {
        // Pending-review batches can still be corrected; block only post-moderation/locking states.
        $blocked = ['approved', 'locked', 'processed'];
        $status = strtolower((string) $batch->status);
        $lifecycleState = strtolower((string) ($batch->lifecycle_state ?? ''));

        return !in_array($status, $blocked, true) && !in_array($lifecycleState, $blocked, true);
    }

    private function getBatchRowCounters(MarkImportBatch $batch): array
    {
        return [
            'error_rows_count' => $batch->rawMarks()->where('has_errors', true)->count(),
            'valid_rows_count' => $batch->rawMarks()->where('has_errors', false)->count(),
            'warnings_count' => $batch->rawMarks()->where('has_warnings', true)->count(),
        ];
    }
}
