<?php

namespace App\Services\MarkEntry\Moderation;

use App\Models\MarkImportBatch;
use App\Models\MarkImportRun;
use App\Models\MarkImportRunError;
use App\Models\MarkModerationAction;
use App\Models\MarkRejection;
use App\Models\MarkModerationReview;
use App\Services\MarkEntry\Shared\LifecycleStateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModerationActionService
{
    private LifecycleStateService $lifecycleService;

    public function __construct(LifecycleStateService $lifecycleService)
    {
        $this->lifecycleService = $lifecycleService;
    }

    /**
     * Approve marks by scope.
     * Aborts if blocking errors exist.
     *
     * @param string $scope  single_subject|school|district
     * @param array  $params [exam_year_id, school_id?, subject_id?, district_id?, batch_id?]
     * @param \App\Models\User $actor
     * @param string|null $feedback
     * @return array  ['approved_count' => int, 'correlation_id' => string, 'batches' => array]
     * @throws \Exception if blocking errors exist
     */
    public function approve(string $scope, array $params, $actor, ?string $feedback = null): array
    {
        $correlationId = (string) Str::uuid();

        return DB::transaction(function () use ($scope, $params, $actor, $feedback, $correlationId) {
            $batches = $this->resolveBatches($scope, $params);

            if ($batches->isEmpty()) {
                throw new \Exception('No batches found matching the specified scope.');
            }

            // Check for blocking errors
            $blockingErrors = $this->getBlockingErrorsForBatches($batches->pluck('id')->toArray());
            if ($blockingErrors > 0) {
                throw new \Exception(
                    "Cannot approve: {$blockingErrors} blocking error(s) exist. " .
                    "Resolve all errors before approving."
                );
            }

            $approvedCount = 0;
            $batchIds = [];

            foreach ($batches as $batch) {
                if (!in_array($batch->lifecycle_state, ['awaiting_moderation', 'submitted', 'draft', null]) &&
                    !in_array($batch->status, ['submitted', 'validated'])) {
                    continue;
                }

                // Create or update moderation review
                $review = MarkModerationReview::where('mark_import_batch_id', $batch->id)
                    ->latest('id')->first();

                if ($review) {
                    $review->update([
                        'status' => 'approved',
                        'feedback' => $feedback,
                        'reviewed_at' => now(),
                        'reviewer_id' => $actor->id,
                    ]);
                } else {
                    $review = MarkModerationReview::create([
                        'mark_import_batch_id' => $batch->id,
                        'reviewer_id' => $actor->id,
                        'review_type' => 'admin',
                        'status' => 'approved',
                        'feedback' => $feedback,
                        'reviewed_at' => now(),
                    ]);
                }

                // Transition lifecycle state
                $this->lifecycleService->transition(
                    $batch,
                    'approved',
                    $actor,
                    "Approved ({$scope}) by {$actor->name}"
                );

                $batch->update([
                    'status' => 'approved',
                    'approved_by' => $actor->id,
                    'approved_at' => now(),
                    'latest_review_id' => $review->id,
                ]);

                $approvedCount += $batch->total_records ?? 0;
                $batchIds[] = $batch->id;
            }

            // Log the moderation action
            MarkModerationAction::create([
                'action' => MarkModerationAction::ACTION_APPROVE,
                'scope' => $scope,
                'actor_id' => $actor->id,
                'mark_import_batch_id' => count($batchIds) === 1 ? $batchIds[0] : null,
                'exam_year_id' => $params['exam_year_id'] ?? null,
                'school_id' => $params['school_id'] ?? null,
                'subject_id' => $params['subject_id'] ?? null,
                'district_id' => $params['district_id'] ?? null,
                'affected_rows' => $approvedCount,
                'reason' => $feedback,
                'correlation_id' => $correlationId,
            ]);

            \Log::info("Moderation APPROVE", [
                'correlation_id' => $correlationId,
                'scope' => $scope,
                'actor' => $actor->id,
                'batches' => $batchIds,
                'affected_rows' => $approvedCount,
            ]);

            return [
                'approved_count' => $approvedCount,
                'batch_count' => count($batchIds),
                'correlation_id' => $correlationId,
                'batches' => $batchIds,
            ];
        });
    }

    /**
     * Reject marks by scope.
     *
     * @param string $scope  candidate|subject_batch|run|batch
     * @param array  $params [batch_id?, run_id?, candidate_id?, row_number?, exam_year_id?, school_id?, subject_id?]
     * @param \App\Models\User $actor
     * @param string $reasonCode  from MarkRejection::REASON_* constants
     * @param string|null $note
     * @return array  ['rejected_count' => int, 'correlation_id' => string]
     */
    public function reject(string $scope, array $params, $actor, string $reasonCode, ?string $note = null): array
    {
        $correlationId = (string) Str::uuid();

        return DB::transaction(function () use ($scope, $params, $actor, $reasonCode, $note, $correlationId) {
            $batchIds = [];
            $rejectedCount = 0;

            if ($scope === 'candidate' && !empty($params['batch_id'])) {
                // Reject a single candidate row
                $batch = MarkImportBatch::findOrFail($params['batch_id']);
                $batchIds[] = $batch->id;
                $rejectedCount = 1;

            } elseif ($scope === 'batch' || $scope === 'subject_batch') {
                $batches = $this->resolveBatches($scope === 'subject_batch' ? 'single_subject' : 'school', $params);
                foreach ($batches as $batch) {
                    $batchIds[] = $batch->id;
                    $rejectedCount += $batch->total_records ?? 0;
                }

            } elseif ($scope === 'run' && !empty($params['run_id'])) {
                $run = MarkImportRun::findOrFail($params['run_id']);
                if ($run->mark_import_batch_id) {
                    $batchIds[] = $run->mark_import_batch_id;
                }
                $rejectedCount = $run->total_rows ?? 0;
            }

            // Create rejection record
            MarkRejection::create([
                'run_id' => $params['run_id'] ?? null,
                'mark_import_batch_id' => count($batchIds) === 1 ? $batchIds[0] : null,
                'candidate_id' => $params['candidate_id'] ?? null,
                'row_number' => $params['row_number'] ?? null,
                'reason_code' => $reasonCode,
                'note' => $note,
                'rejected_by' => $actor->id,
                'scope' => $scope,
                'correlation_id' => $correlationId,
            ]);

            // Update batch status for non-candidate rejections
            if ($scope !== 'candidate') {
                foreach ($batchIds as $batchId) {
                    $batch = MarkImportBatch::find($batchId);
                    if (!$batch) continue;

                    $review = MarkModerationReview::where('mark_import_batch_id', $batch->id)
                        ->latest('id')->first();

                    if ($review) {
                        $review->update([
                            'status' => 'rejected',
                            'feedback' => $note ?? $reasonCode,
                            'reviewed_at' => now(),
                            'reviewer_id' => $actor->id,
                        ]);
                    }

                    $this->lifecycleService->transition(
                        $batch,
                        'rejected',
                        $actor,
                        "Rejected ({$scope}): {$reasonCode}"
                    );

                    $batch->update([
                        'status' => 'rejected',
                        'rejection_reason' => $note ?? $reasonCode,
                        'requires_resubmission' => true,
                    ]);
                }
            }

            // Log the moderation action
            MarkModerationAction::create([
                'action' => MarkModerationAction::ACTION_REJECT,
                'scope' => $scope,
                'actor_id' => $actor->id,
                'mark_import_batch_id' => count($batchIds) === 1 ? $batchIds[0] : null,
                'run_id' => $params['run_id'] ?? null,
                'exam_year_id' => $params['exam_year_id'] ?? null,
                'school_id' => $params['school_id'] ?? null,
                'subject_id' => $params['subject_id'] ?? null,
                'district_id' => $params['district_id'] ?? null,
                'candidate_id' => $params['candidate_id'] ?? null,
                'affected_rows' => $rejectedCount,
                'reason' => $note ?? $reasonCode,
                'correlation_id' => $correlationId,
            ]);

            \Log::warning("Moderation REJECT", [
                'correlation_id' => $correlationId,
                'scope' => $scope,
                'actor' => $actor->id,
                'reason_code' => $reasonCode,
                'batches' => $batchIds,
                'affected_rows' => $rejectedCount,
            ]);

            return [
                'rejected_count' => $rejectedCount,
                'batch_count' => count($batchIds),
                'correlation_id' => $correlationId,
                'batches' => $batchIds,
            ];
        });
    }

    /**
     * Resolve batches based on scope and params
     */
    private function resolveBatches(string $scope, array $params)
    {
        $query = MarkImportBatch::query();

        if (!empty($params['batch_id'])) {
            return $query->where('id', $params['batch_id'])->get();
        }

        if (!empty($params['exam_year_id'])) {
            $query->where('exam_year', $params['exam_year_id']);
        }

        switch ($scope) {
            case 'single_subject':
                if (!empty($params['school_id'])) $query->where('school_id', $params['school_id']);
                if (!empty($params['subject_id'])) $query->where('subject_id', $params['subject_id']);
                break;
            case 'school':
                if (!empty($params['school_id'])) $query->where('school_id', $params['school_id']);
                break;
            case 'district':
                if (!empty($params['district_id'])) $query->where('district_id', $params['district_id']);
                break;
        }

        // Only get batches that are in a reviewable state
        $query->where(function ($q) {
            $q->whereIn('lifecycle_state', ['awaiting_moderation', 'validated', 'submitted'])
              ->orWhere(function ($q2) {
                  $q2->whereIn('status', ['submitted', 'validated'])
                     ->where(function ($q3) {
                         $q3->whereNull('lifecycle_state')
                            ->orWhere('lifecycle_state', 'draft');
                     });
              });
        });

        return $query->get();
    }

    /**
     * Count blocking errors for given batch IDs.
     * Resolved actionable errors (e.g. INC accepted) are NOT counted as blocking.
     */
    private function getBlockingErrorsForBatches(array $batchIds): int
    {
        return MarkImportRunError::where('severity', 'error')
            ->where(function ($q) {
                // Non-actionable errors always block
                $q->where(function ($q2) {
                    $q2->where('is_actionable', false)->orWhereNull('is_actionable');
                })
                // Actionable errors only block if unresolved
                ->orWhere(function ($q2) {
                    $q2->where('is_actionable', true)->where('is_resolved', false);
                });
            })
            ->whereHas('run', function ($q) use ($batchIds) {
                $q->whereIn('mark_import_batch_id', $batchIds);
            })
            ->count();
    }
}
