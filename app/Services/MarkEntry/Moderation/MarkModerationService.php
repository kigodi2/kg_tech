<?php

namespace App\Services\MarkEntry\Moderation;

use App\Models\MarkImportBatch;
use App\Models\MarkModerationAction;
use App\Models\MarkModerationReview;
use App\Services\MarkEntry\Shared\LifecycleStateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarkModerationService {

    private LifecycleStateService $lifecycleService;

    public function __construct(LifecycleStateService $lifecycleService) {
        $this->lifecycleService = $lifecycleService;
    }

    /**
     * Create moderation review
     */
    public function createReview(
        MarkImportBatch $batch,
        $reviewer,
        string $reviewType
    ): MarkModerationReview {
        
        return DB::transaction(function () use ($batch, $reviewer, $reviewType) {
            
            if ($batch->lifecycle_state === 'validated') {
                $this->lifecycleService->transition(
                    $batch,
                    'awaiting_moderation',
                    auth()->user(),
                    'Sent to moderator for review'
                );
            }

            return MarkModerationReview::create([
                'mark_import_batch_id' => $batch->id,
                'reviewer_id' => $reviewer->id,
                'review_type' => $reviewType,
                'status' => 'pending',
            ]);
        });
    }

    /**
     * Approve batch
     */
    public function approveBatch(
        MarkImportBatch $batch,
        $approver,
        ?string $feedback = null
    ): MarkModerationReview {
        
        return DB::transaction(function () use ($batch, $approver, $feedback) {
            
            $review = MarkModerationReview::where('mark_import_batch_id', $batch->id)
                ->latest('id')->first();
            
            if (!$review) {
                $review = MarkModerationReview::create([
                    'mark_import_batch_id' => $batch->id,
                    'reviewer_id' => $approver->id,
                    'review_type' => 'approval',
                    'status' => 'pending',
                ]);
            }

            $review->update([
                'status' => 'approved',
                'feedback' => $feedback,
                'reviewed_at' => now(),
                'reviewer_id' => $approver->id,
            ]);

            $currentState = $this->lifecycleService->getCurrentState($batch);
            if ($currentState === 'validated') {
                $this->lifecycleService->transition(
                    $batch,
                    'awaiting_moderation',
                    $approver,
                    'Sent to moderation before approval'
                );
                $batch->refresh();
            }

            $this->lifecycleService->transition(
                $batch,
                'approved',
                $approver,
                "Approved by " . $approver->name
            );

            $batch->update(['status' => 'approved']);

            MarkModerationAction::create([
                'action' => MarkModerationAction::ACTION_APPROVE,
                'scope' => 'single_subject',
                'actor_id' => $approver->id,
                'mark_import_batch_id' => $batch->id,
                'exam_year_id' => $batch->exam_year,
                'school_id' => $batch->school_id,
                'subject_id' => $batch->subject_id,
                'district_id' => $batch->district_id,
                'affected_rows' => $batch->total_records ?? 0,
                'reason' => $feedback,
                'correlation_id' => (string) Str::uuid(),
            ]);

            \Log::info("Batch {$batch->id} approved by {$approver->name}");

            return $review;
        });
    }

    /**
     * Reject batch with feedback
     */
    public function rejectBatch(
        MarkImportBatch $batch,
        $rejector,
        string $reason
    ): MarkModerationReview {
        
        return DB::transaction(function () use ($batch, $rejector, $reason) {
            
            $review = MarkModerationReview::where('mark_import_batch_id', $batch->id)
                ->latest('id')->first();
            
            if (!$review) {
                $review = MarkModerationReview::create([
                    'mark_import_batch_id' => $batch->id,
                    'reviewer_id' => $rejector->id,
                    'review_type' => 'rejection',
                    'status' => 'pending',
                ]);
            }

            $review->update([
                'status' => 'rejected',
                'feedback' => $reason,
                'reviewed_at' => now(),
                'reviewer_id' => $rejector->id,
            ]);

            $this->lifecycleService->transition(
                $batch,
                'rejected',
                $rejector,
                "Rejected: {$reason}"
            );

            $batch->update([
                'status' => 'rejected',
                'requires_resubmission' => true,
                'rejection_reason' => $reason,
            ]);

            MarkModerationAction::create([
                'action' => MarkModerationAction::ACTION_REJECT,
                'scope' => 'single_subject',
                'actor_id' => $rejector->id,
                'mark_import_batch_id' => $batch->id,
                'exam_year_id' => $batch->exam_year,
                'school_id' => $batch->school_id,
                'subject_id' => $batch->subject_id,
                'district_id' => $batch->district_id,
                'affected_rows' => $batch->total_records ?? 0,
                'reason' => $reason,
                'correlation_id' => (string) Str::uuid(),
            ]);

            \Log::warning("Batch {$batch->id} rejected by {$rejector->name}");

            return $review;
        });
    }
}
