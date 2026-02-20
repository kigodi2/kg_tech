<?php

namespace App\Services\MarkEntry\Submission;

use App\Models\MarkImportBatch;
use App\Models\MarkBatchApproval;
use App\Services\MarkEntry\Shared\LifecycleStateService;
use Illuminate\Support\Facades\DB;

class MarkSubmissionService {

    private LifecycleStateService $lifecycleService;

    public function __construct(LifecycleStateService $lifecycleService) {
        $this->lifecycleService = $lifecycleService;
    }

    /**
     * Lock a batch (prevent further modifications)
     */
    public function lockBatch(MarkImportBatch $batch, $user): MarkBatchApproval {
        return DB::transaction(function () use ($batch, $user) {
            
            if ($batch->lifecycle_state !== 'approved') {
                throw new \Exception('Only approved batches can be locked');
            }

            $approval = MarkBatchApproval::create([
                'mark_import_batch_id' => $batch->id,
                'approved_by' => $user->id,
                'approval_type' => 'submission',
                'status' => 'locked',
                'approved_at' => now(),
                'approval_notes' => 'Batch locked for submission',
            ]);

            $this->lifecycleService->transition(
                $batch,
                'submitted',
                $user,
                'Locked and submitted to exam authority'
            );

            \Log::info("Batch {$batch->id} locked and submitted by {$user->name}");

            return $approval;
        });
    }

    /**
     * Unlock a batch (admin only - allows resubmission)
     */
    public function unlockBatch(MarkImportBatch $batch, $user): bool {
        return DB::transaction(function () use ($batch, $user) {

            if (!in_array($batch->lifecycle_state, ['submitted', 'archived'])) {
                throw new \Exception('Can only unlock submitted or archived batches');
            }

            // Reset to 'draft' to allow resubmission (valid transition: submitted -> archived is not useful for unlock)
            // Instead, we reset the batch state to allow re-entry into the workflow
            $batch->update([
                'lifecycle_state' => 'draft',
                'locked_at' => null,
                'locked_by' => null,
                'status' => 'draft'
            ]);

            \Log::warning("Batch {$batch->id} unlocked by admin {$user->name}");

            return true;
        });
    }

    /**
     * Get submission-ready batches
     */
    public function getSubmissionReadyBatches(int $perPage = 20) {
        return MarkImportBatch::select('mark_import_batches.*')
            ->where('lifecycle_state', 'approved')
            ->with(['school', 'subject', 'examType', 'latestReview.reviewer'])
            ->addSelect(['candidate_count' => \App\Models\RawMark::selectRaw('COUNT(DISTINCT candidate_index_number)')
                ->whereColumn('mark_import_batch_id', 'mark_import_batches.id')
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get submitted batches
     */
    public function getSubmittedBatches(int $perPage = 20) {
        return MarkImportBatch::select('mark_import_batches.*')
            ->where('lifecycle_state', 'submitted')
            ->with(['school', 'subject', 'examType'])
            ->addSelect(['candidate_count' => \App\Models\RawMark::selectRaw('COUNT(DISTINCT candidate_index_number)')
                ->whereColumn('mark_import_batch_id', 'mark_import_batches.id')
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get submission history for batch
     */
    public function getSubmissionHistory(MarkImportBatch $batch) {
        return $batch->approvals()
            ->with('approvedByUser')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
