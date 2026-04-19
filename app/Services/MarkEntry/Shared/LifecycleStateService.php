<?php

namespace App\Services\MarkEntry\Shared;

use App\Models\MarkImportBatch;
use App\Models\MarkEntryLifecycleState;
use Illuminate\Support\Facades\DB;

class LifecycleStateService {
    
    private const VALID_TRANSITIONS = [
        'draft' => ['validating', 'validated', 'rejected'],
        'validating' => ['validated', 'validation_failed'],
        'validated' => ['submitted', 'awaiting_moderation', 'draft', 'rejected'],
        'validation_failed' => ['draft'],
        'awaiting_moderation' => ['approved', 'rejected'],
        'submitted' => ['approved', 'rejected', 'archived'],
        'approved' => ['locked', 'submitted', 'draft'],
        'rejected' => ['draft', 'validated'],
        'locked' => ['submitted', 'processed'],
        'processing' => ['processed'],
        'processed' => ['submitted', 'archived'],
        'archived' => [],
    ];

    /**
     * Transition batch to new state
     */
    public function transition(
        MarkImportBatch $batch,
        string $newState,
        $user = null,
        ?string $reason = null
    ): MarkEntryLifecycleState {
        
        return DB::transaction(function () use ($batch, $newState, $user, $reason) {
            $currentState = $this->getCurrentState($batch);
            
            if (!$this->isValidTransition($currentState, $newState)) {
                throw new \Exception(
                    "Cannot transition from '{$currentState}' to '{$newState}'"
                );
            }

            $lifecycle = MarkEntryLifecycleState::create([
                'mark_import_batch_id' => $batch->id,
                'current_state' => $newState,
                'previous_state' => $currentState,
                'transitioned_by' => $user->id ?? null,
                'transitioned_at' => now(),
                'transition_reason' => $reason ?? $this->getDefaultReason($newState),
            ]);

            $batch->update(['lifecycle_state' => $newState]);

            \Log::info("Batch {$batch->id} transitioned: {$currentState} → {$newState}");

            return $lifecycle;
        });
    }

    private function isValidTransition(string $from, string $to): bool {
        $allowed = self::VALID_TRANSITIONS[$from] ?? [];
        return in_array($to, $allowed);
    }

    private function getDefaultReason(string $state): string {
        return match ($state) {
            'validating' => 'Validation in progress',
            'validated' => 'All validation rules passed',
            'awaiting_moderation' => 'Waiting for moderator review',
            'approved' => 'Approved by moderator',
            'rejected' => 'Rejected, awaiting resubmission',
            'submitted' => 'Submitted to exam authority',
            default => ucwords(str_replace('_', ' ', $state)),
        };
    }

    public function getCurrentState(MarkImportBatch $batch): string {
        if (!empty($batch->lifecycle_state)) {
            return (string) $batch->lifecycle_state;
        }

        // Legacy fallback: infer lifecycle from persisted batch status.
        $status = (string) ($batch->status ?? '');
        return match ($status) {
            'submitted' => 'submitted',
            'approved' => 'approved',
            'rejected' => 'rejected',
            'locked' => 'locked',
            'validated' => 'validated',
            default => 'draft',
        };
    }

    public function canTransition(MarkImportBatch $batch, string $targetState): bool {
        $current = $this->getCurrentState($batch);
        return $this->isValidTransition($current, $targetState);
    }

    public function getAvailableTransitions(MarkImportBatch $batch): array {
        $current = $this->getCurrentState($batch);
        return self::VALID_TRANSITIONS[$current] ?? [];
    }
}
