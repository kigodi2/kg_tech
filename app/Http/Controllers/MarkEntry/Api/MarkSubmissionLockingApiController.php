<?php

namespace App\Http\Controllers\MarkEntry\Api;

use App\Http\Controllers\Controller;
use App\Models\MarkImportBatch;
use App\Services\MarkEntry\MarkBatchStateMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarkSubmissionLockingApiController extends Controller
{
    private MarkBatchStateMachine $stateMachine;

    public function __construct(MarkBatchStateMachine $stateMachine)
    {
        $this->stateMachine = $stateMachine;
    }

    /**
     * POST /api/mark-entry/acsee/batches/{batch}/submit
     */
    public function submit(Request $request, MarkImportBatch $batch): JsonResponse
    {
        $this->authorize('submit', $batch);

        try {
            $result = $this->stateMachine->submit($batch, $request->user());

            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'VALIDATION_ERRORS_EXIST',
            ], 422);
        } catch (\LogicException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'INVALID_STATE',
            ], 409);
        } catch (\Exception $e) {
            \Log::error('Submit batch error', ['batch_id' => $batch->id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit batch',
                'code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    /**
     * POST /api/mark-entry/acsee/batches/{batch}/approve
     */
    public function approve(Request $request, MarkImportBatch $batch): JsonResponse
    {
        $this->authorize('approve', $batch);

        try {
            $validated = $request->validate([
                'feedback' => 'nullable|string|max:1000',
            ]);

            $result = $this->stateMachine->approve($batch, $request->user(), $validated['feedback'] ?? null);

            return response()->json($result);
        } catch (\LogicException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'INVALID_STATE',
            ], 409);
        } catch (\Exception $e) {
            \Log::error('Approve batch error', ['batch_id' => $batch->id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve batch',
                'code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    /**
     * POST /api/mark-entry/acsee/batches/{batch}/reject
     */
    public function reject(Request $request, MarkImportBatch $batch): JsonResponse
    {
        $this->authorize('reject', $batch);

        try {
            $validated = $request->validate([
                'reason' => 'required|string|min:10|max:1000',
            ]);

            $result = $this->stateMachine->reject($batch, $request->user(), $validated['reason']);

            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'VALIDATION_ERROR',
            ], 422);
        } catch (\LogicException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'INVALID_STATE',
            ], 409);
        } catch (\Exception $e) {
            \Log::error('Reject batch error', ['batch_id' => $batch->id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject batch',
                'code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    /**
     * POST /api/mark-entry/acsee/batches/{batch}/lock
     */
    public function lock(Request $request, MarkImportBatch $batch): JsonResponse
    {
        $this->authorize('lock', $batch);

        try {
            $result = $this->stateMachine->lock($batch, $request->user());

            return response()->json($result);
        } catch (\LogicException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'INVALID_STATE',
            ], 409);
        } catch (\Exception $e) {
            \Log::error('Lock batch error', ['batch_id' => $batch->id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to lock batch',
                'code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    /**
     * POST /api/mark-entry/acsee/batches/{batch}/unlock
     */
    public function unlock(Request $request, MarkImportBatch $batch): JsonResponse
    {
        $this->authorize('unlock', $batch);

        try {
            $validated = $request->validate([
                'reason' => 'required|string|min:10|max:1000',
            ]);

            $result = $this->stateMachine->unlock($batch, $request->user(), $validated['reason']);

            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'VALIDATION_ERROR',
            ], 422);
        } catch (\LogicException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'INVALID_STATE',
            ], 409);
        } catch (\Exception $e) {
            \Log::error('Unlock batch error', ['batch_id' => $batch->id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlock batch',
                'code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    /**
     * GET /api/mark-entry/acsee/submission/batches
     * List batches for submission page (validated/submitted by scope)
     */
    public function submissionBatches(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 20);

            $query = MarkImportBatch::query()
                ->forUserScope($user)
                ->with(['school', 'subject', 'examType', 'district', 'importedByUser'])
                ->orderBy('updated_at', 'desc');

            // Filter by status
            // Note: upload flow creates batches as 'draft'; they are effectively
            // validated when error_records=0, so we treat draft+0errors as ready.
            $status = $request->input('status', 'validated');
            if ($status === 'all') {
                $query->whereIn('status', ['draft', 'validated', 'submitted', 'rejected']);
            } elseif ($status === 'validated') {
                $query->where(function ($q) {
                    $q->where('status', 'validated')
                      ->orWhere(function ($q2) {
                          $q2->where('status', 'draft')->where('error_records', 0);
                      });
                });
            } else {
                $query->where('status', $status);
            }

            // Filter by exam year
            if ($request->filled('exam_year')) {
                $query->where('exam_year', $request->input('exam_year'));
            }

            // Filter by district
            if ($request->filled('district_id')) {
                $query->where('district_id', $request->input('district_id'));
            }

            // Filter by school
            if ($request->filled('school_id')) {
                $query->where('school_id', $request->input('school_id'));
            }

            // Filter by subject
            if ($request->filled('subject_id')) {
                $query->where('subject_id', $request->input('subject_id'));
            }

            $batches = $query->paginate($perPage);

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
        } catch (\Exception $e) {
            \Log::error('Submission batches error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load submission batches',
                'code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    /**
     * GET /api/mark-entry/acsee/locking/status
     * Lock status dashboard data (approved/locked + stats)
     */
    public function lockingStatus(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $baseQuery = MarkImportBatch::query()->forUserScope($user);

            if ($request->filled('exam_year')) {
                $baseQuery->where('exam_year', $request->input('exam_year'));
            }

            // Stats
            $submittedPending = (clone $baseQuery)->where('status', 'submitted')->count();
            $approvedReady = (clone $baseQuery)->where('status', 'approved')->count();
            $lockedToday = (clone $baseQuery)->where('status', 'locked')
                ->whereDate('locked_at', today())->count();
            $rejectedToday = (clone $baseQuery)->where('status', 'rejected')
                ->whereDate('updated_at', today())->count();

            // Approved batches ready to lock
            $approvedBatches = (clone $baseQuery)
                ->where('status', 'approved')
                ->with(['school', 'subject', 'examType', 'approvedByUser'])
                ->orderBy('approved_at', 'desc')
                ->get();

            // Recently locked batches
            $lockedBatches = (clone $baseQuery)
                ->where('status', 'locked')
                ->with(['school', 'subject', 'examType', 'lockedByUser'])
                ->orderBy('locked_at', 'desc')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'stats' => [
                    'submitted_pending' => $submittedPending,
                    'approved_ready' => $approvedReady,
                    'locked_today' => $lockedToday,
                    'rejected_today' => $rejectedToday,
                ],
                'approved_batches' => $approvedBatches,
                'locked_batches' => $lockedBatches,
            ]);
        } catch (\Exception $e) {
            \Log::error('Locking status error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load locking status',
                'code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    /**
     * GET /api/mark-entry/acsee/batches/{batch}/history
     * Lifecycle history for a batch
     */
    public function batchHistory(Request $request, MarkImportBatch $batch): JsonResponse
    {
        $this->authorize('view', $batch);

        try {
            $history = $batch->lifecycleStates()
                ->with('transitionedByUser')
                ->orderBy('transitioned_at', 'desc')
                ->get()
                ->map(fn($state) => [
                    'id' => $state->id,
                    'from' => $state->previous_state,
                    'to' => $state->current_state,
                    'reason' => $state->transition_reason,
                    'by' => $state->transitionedByUser?->name ?? 'System',
                    'at' => $state->transitioned_at?->format('Y-m-d H:i:s'),
                ]);

            return response()->json([
                'success' => true,
                'batch_id' => $batch->id,
                'batch_code' => $batch->batch_code,
                'current_status' => $batch->status,
                'history' => $history,
            ]);
        } catch (\Exception $e) {
            \Log::error('Batch history error', ['batch_id' => $batch->id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load batch history',
                'code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    /**
     * GET /api/mark-entry/acsee/history
     * Paginated lifecycle history across all batches
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 30);

            $query = \App\Models\MarkEntryLifecycleState::query()
                ->with(['batch.school', 'batch.subject', 'transitionedByUser'])
                ->orderBy('transitioned_at', 'desc');

            // Scope to user's access
            if (!$user->isAdmin()) {
                $query->whereHas('batch', function ($q) use ($user) {
                    $q->forUserScope($user);
                });
            }

            // Filter by exam year (via batch)
            if ($request->filled('exam_year')) {
                $query->whereHas('batch', function ($q) use ($request) {
                    $q->where('exam_year', $request->input('exam_year'));
                });
            }

            // Filter by school (via batch)
            if ($request->filled('school_id')) {
                $query->whereHas('batch', function ($q) use ($request) {
                    $q->where('school_id', $request->input('school_id'));
                });
            }

            // Filter by action / state
            if ($request->filled('action')) {
                $action = $request->input('action');
                if ($action === 'unlocked') {
                    $query->where('previous_state', 'locked');
                } else {
                    $query->where('current_state', $action);
                }
            }

            // Filter by date range
            if ($request->filled('from_date')) {
                $query->whereDate('transitioned_at', '>=', $request->input('from_date'));
            }
            if ($request->filled('to_date')) {
                $query->whereDate('transitioned_at', '<=', $request->input('to_date'));
            }

            $paginated = $query->paginate($perPage);

            $data = collect($paginated->items())->map(function ($state) {
                return [
                    'id' => $state->id,
                    'mark_import_batch_id' => $state->mark_import_batch_id,
                    'batch_code' => $state->batch?->batch_code,
                    'current_state' => $state->current_state,
                    'previous_state' => $state->previous_state,
                    'transition_reason' => $state->transition_reason,
                    'transitioned_by_user' => $state->transitionedByUser ? [
                        'id' => $state->transitionedByUser->id,
                        'name' => $state->transitionedByUser->name,
                    ] : null,
                    'transitioned_at' => $state->transitioned_at?->toIso8601String(),
                    'school_name' => $state->batch?->school?->name,
                    'subject_name' => $state->batch?->subject?->name,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'total' => $paginated->total(),
                    'per_page' => $paginated->perPage(),
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'has_more' => $paginated->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('History error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load history',
                'code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    /**
     * GET /api/mark-entry/acsee/admin/locked-batches
     * Admin unlock page data
     */
    public function lockedBatchesForAdmin(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $query = MarkImportBatch::query()
                ->where('status', 'locked')
                ->with(['school', 'subject', 'examType', 'district', 'lockedByUser'])
                ->orderBy('locked_at', 'desc');

            if ($request->filled('exam_year')) {
                $query->where('exam_year', $request->input('exam_year'));
            }

            if ($request->filled('district_id')) {
                $query->where('district_id', $request->input('district_id'));
            }

            if ($request->filled('school_id')) {
                $query->where('school_id', $request->input('school_id'));
            }

            $perPage = $request->input('per_page', 20);
            $batches = $query->paginate($perPage);

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
        } catch (\Exception $e) {
            \Log::error('Locked batches admin error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load locked batches',
                'code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }
}
