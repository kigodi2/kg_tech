<?php

namespace App\Services\MarkEntry\Moderation;

use App\Models\MarkImportBatch;
use App\Models\MarkModerationReview;
use App\Services\MarkEntry\Shared\LifecycleStateService;
use Illuminate\Support\Facades\DB;

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
                throw new \Exception('No active moderation review found');
            }

            $review->update([
                'status' => 'approved',
                'feedback' => $feedback,
                'reviewed_at' => now(),
                'reviewer_id' => $approver->id,
            ]);

            $this->lifecycleService->transition(
                $batch,
                'approved',
                $approver,
                "Approved by " . $approver->name
            );

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
                throw new \Exception('No active moderation review found');
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
                'requires_resubmission' => true,
                'rejection_reason' => $reason,
            ]);

            \Log::warning("Batch {$batch->id} rejected by {$rejector->name}");

            return $review;
        });
    }
}
