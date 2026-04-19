<?php

namespace App\Services\MarkEntry;

use App\Models\MarkImportBatch;
use App\Models\MarkEntryLifecycleState;
use App\Models\MarkBatchApproval;
use App\Models\MarkModerationAction;
use App\Models\MarkModerationReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MarkBatchStateMachine
{
    private const TRANSITIONS = [
        'submit'  => ['from' => 'validated', 'to' => 'submitted'],
        'approve' => ['from' => 'submitted', 'to' => 'approved'],
        'reject'  => ['from' => 'submitted', 'to' => 'rejected'],
        'lock'    => ['from' => 'approved',  'to' => 'locked'],
        'unlock'  => ['from' => 'locked',    'to' => 'submitted'],
    ];

    public function submit(MarkImportBatch $batch, User $user): array
    {
        // Accept both 'validated' and 'draft' (draft with 0 errors is effectively validated)
        if (!in_array($batch->status, ['validated', 'draft'])) {
            throw new \LogicException(
                "Cannot submit: batch status is '{$batch->status}', expected 'validated' or 'draft'."
            );
        }

        if ($batch->hasErrors()) {
            throw new \InvalidArgumentException(
                'Cannot submit batch with validation errors. Fix all errors first.'
            );
        }

        return DB::transaction(function () use ($batch, $user) {
            $oldStatus = $batch->status;

            $batch->update([
                'status' => MarkImportBatch::STATUS_SUBMITTED,
                'lifecycle_state' => 'submitted',
                'submitted_by' => $user->id,
                'submitted_at' => now(),
                'requires_resubmission' => false,
                'rejection_reason' => null,
            ]);

            $this->logTransition($batch, $oldStatus, 'submitted', $user, 'Submitted for review');

            Log::info("Batch {$batch->id} submitted by {$user->name}");

            return $this->result($batch, $oldStatus, 'submitted', 'Batch submitted for review');
        });
    }

    public function approve(MarkImportBatch $batch, User $user, ?string $feedback = null): array
    {
        $this->guardTransition($batch, 'approve');

        return DB::transaction(function () use ($batch, $user, $feedback) {
            $oldStatus = $batch->status;

            $batch->update([
                'status' => MarkImportBatch::STATUS_APPROVED,
                'lifecycle_state' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $review = MarkModerationReview::create([
                'mark_import_batch_id' => $batch->id,
                'reviewer_id' => $user->id,
                'review_type' => 'approval',
                'status' => 'approved',
                'feedback' => $feedback,
                'reviewed_at' => now(),
            ]);

            $batch->update(['latest_review_id' => $review->id]);

            MarkModerationAction::create([
                'action' => MarkModerationAction::ACTION_APPROVE,
                'scope' => 'single_subject',
                'actor_id' => $user->id,
                'mark_import_batch_id' => $batch->id,
                'exam_year_id' => $batch->exam_year,
                'school_id' => $batch->school_id,
                'subject_id' => $batch->subject_id,
                'district_id' => $batch->district_id,
                'affected_rows' => $batch->total_records ?? 0,
                'reason' => $feedback,
                'correlation_id' => (string) Str::uuid(),
            ]);

            $this->logTransition($batch, $oldStatus, 'approved', $user, 'Approved' . ($feedback ? ": {$feedback}" : ''));

            Log::info("Batch {$batch->id} approved by {$user->name}");

            return $this->result($batch, $oldStatus, 'approved', 'Batch approved successfully');
        });
    }

    public function reject(MarkImportBatch $batch, User $user, string $reason): array
    {
        $this->guardTransition($batch, 'reject');

        if (strlen(trim($reason)) < 10) {
            throw new \InvalidArgumentException('Rejection reason must be at least 10 characters.');
        }

        return DB::transaction(function () use ($batch, $user, $reason) {
            $oldStatus = $batch->status;

            $batch->update([
                'status' => MarkImportBatch::STATUS_REJECTED,
                'lifecycle_state' => 'rejected',
                'rejection_reason' => $reason,
                'requires_resubmission' => true,
            ]);

            $review = MarkModerationReview::create([
                'mark_import_batch_id' => $batch->id,
                'reviewer_id' => $user->id,
                'review_type' => 'rejection',
                'status' => 'rejected',
                'feedback' => $reason,
                'reviewed_at' => now(),
            ]);

            $batch->update(['latest_review_id' => $review->id]);

            MarkModerationAction::create([
                'action' => MarkModerationAction::ACTION_REJECT,
                'scope' => 'single_subject',
                'actor_id' => $user->id,
                'mark_import_batch_id' => $batch->id,
                'exam_year_id' => $batch->exam_year,
                'school_id' => $batch->school_id,
                'subject_id' => $batch->subject_id,
                'district_id' => $batch->district_id,
                'affected_rows' => $batch->total_records ?? 0,
                'reason' => $reason,
                'correlation_id' => (string) Str::uuid(),
            ]);

            $this->logTransition($batch, $oldStatus, 'rejected', $user, "Rejected: {$reason}");

            Log::warning("Batch {$batch->id} rejected by {$user->name}: {$reason}");

            return $this->result($batch, $oldStatus, 'rejected', 'Batch rejected');
        });
    }

    public function lock(MarkImportBatch $batch, User $user): array
    {
        $this->guardTransition($batch, 'lock');

        return DB::transaction(function () use ($batch, $user) {
            $oldStatus = $batch->status;

            // Run promotion: raw_marks → subject_marks
            $promotionService = app(MarkPromotionService::class);
            $promotionResult = $promotionService->promote($batch);

            $batch->update([
                'status' => MarkImportBatch::STATUS_LOCKED,
                'lifecycle_state' => 'locked',
                'locked_by' => $user->id,
                'locked_at' => now(),
                'promoted_count' => $promotionResult['promoted'],
            ]);

            // Lock individual raw marks
            $batch->rawMarks()->update([
                'is_locked' => true,
                'locked_at' => now(),
                'locked_by' => $user->id,
            ]);

            MarkBatchApproval::updateOrCreate(
                [
                    'mark_import_batch_id' => $batch->id,
                    'approval_level' => 'submission',
                ],
                [
                    'approved_by' => $user->id,
                    'approval_type' => 'lock',
                    'status' => 'locked',
                    'approved_at' => now(),
                    'approval_notes' => "Locked. Promoted {$promotionResult['promoted']} marks to subject_marks.",
                ]
            );

            $this->logTransition($batch, $oldStatus, 'locked', $user,
                "Locked and promoted {$promotionResult['promoted']} marks to subject_marks"
            );

            Log::info("Batch {$batch->id} locked by {$user->name}. Promoted {$promotionResult['promoted']} marks.");

            return array_merge(
                $this->result($batch, $oldStatus, 'locked', 'Batch locked and marks promoted to final'),
                ['promotion' => $promotionResult]
            );
        });
    }

    public function unlock(MarkImportBatch $batch, User $user, string $reason): array
    {
        $this->guardTransition($batch, 'unlock');

        if (strlen(trim($reason)) < 10) {
            throw new \InvalidArgumentException('Unlock reason must be at least 10 characters.');
        }

        return DB::transaction(function () use ($batch, $user, $reason) {
            $oldStatus = $batch->status;

            $batch->update([
                'status' => MarkImportBatch::STATUS_SUBMITTED,
                'lifecycle_state' => 'submitted',
                'locked_by' => null,
                'locked_at' => null,
                'promoted_count' => null,
            ]);

            // Unlock individual raw marks
            $batch->rawMarks()->where('is_locked', true)->update([
                'is_locked' => false,
                'locked_at' => null,
                'locked_by' => null,
            ]);

            MarkBatchApproval::updateOrCreate(
                [
                    'mark_import_batch_id' => $batch->id,
                    'approval_level' => 'submission',
                ],
                [
                    'approved_by' => $user->id,
                    'approval_type' => 'unlock',
                    'status' => 'unlocked',
                    'approved_at' => now(),
                    'approval_notes' => "Unlocked by admin. Reason: {$reason}",
                ]
            );

            $this->logTransition($batch, $oldStatus, 'submitted', $user, "Admin unlock: {$reason}");

            Log::warning("Batch {$batch->id} unlocked by admin {$user->name}: {$reason}");

            return $this->result($batch, $oldStatus, 'submitted', 'Batch unlocked. Must be reviewed again before locking.');
        });
    }

    // ==================== HELPERS ====================

    private function guardTransition(MarkImportBatch $batch, string $action): void
    {
        $transition = self::TRANSITIONS[$action] ?? null;

        if (!$transition) {
            throw new \InvalidArgumentException("Unknown transition action: {$action}");
        }

        if ($batch->status !== $transition['from']) {
            throw new \LogicException(
                "Cannot {$action}: batch status is '{$batch->status}', expected '{$transition['from']}'."
            );
        }
    }

    private function logTransition(
        MarkImportBatch $batch,
        string $fromState,
        string $toState,
        User $user,
        string $reason
    ): MarkEntryLifecycleState {
        return MarkEntryLifecycleState::create([
            'mark_import_batch_id' => $batch->id,
            'current_state' => $toState,
            'previous_state' => $fromState,
            'transitioned_by' => $user->id,
            'transitioned_at' => now(),
            'transition_reason' => $reason,
        ]);
    }

    private function result(MarkImportBatch $batch, string $oldStatus, string $newStatus, string $message): array
    {
        return [
            'success' => true,
            'message' => $message,
            'batch_id' => $batch->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'batch_code' => $batch->batch_code,
        ];
    }

    public function canTransition(MarkImportBatch $batch, string $action): bool
    {
        $transition = self::TRANSITIONS[$action] ?? null;
        if (!$transition) return false;
        return $batch->status === $transition['from'];
    }

    public function getAvailableActions(MarkImportBatch $batch): array
    {
        $actions = [];
        foreach (self::TRANSITIONS as $action => $transition) {
            if ($batch->status === $transition['from']) {
                $actions[] = $action;
            }
        }
        return $actions;
    }
}
