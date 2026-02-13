<?php

namespace App\Services\MarkEntry\Shared;

use App\Models\MarkImportBatch;
use App\Models\MarkEntryLifecycleState;
use Illuminate\Support\Facades\DB;

class LifecycleStateService {
    
    private const VALID_TRANSITIONS = [
        'draft' => ['validating', 'rejected'],
        'validating' => ['validated', 'validation_failed'],
        'validated' => ['awaiting_moderation', 'draft'],
        'validation_failed' => ['draft'],
        'awaiting_moderation' => ['approved', 'rejected'],
        'approved' => ['submitted', 'draft'],
        'rejected' => ['draft'],
        'processing' => ['processed'],
        'processed' => ['submitted'],
        'submitted' => ['archived'],
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
            $currentState = $batch->lifecycle_state ?? 'draft';
            
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
        return $batch->lifecycle_state ?? 'draft';
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
