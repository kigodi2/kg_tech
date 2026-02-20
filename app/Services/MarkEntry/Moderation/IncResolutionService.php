<?php

namespace App\Services\MarkEntry\Moderation;

use App\Models\MarkImportBatch;
use App\Models\MarkImportRunError;
use App\Models\SubjectExamStatus;
use App\Models\MarkModerationAction;
use App\Models\MarkRejection;
use App\Models\RawMark;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IncResolutionService
{
    /**
     * Accept a MISSING_REQUIRED_PAPER_MARK issue as INC.
     *
     * - Marks the error as resolved (ACCEPT_INC)
     * - Creates a SubjectExamStatus record with status=INC
     * - Updates the raw_mark subject_status to INC
     * - Logs a moderation action
     * - Does NOT touch existing numeric marks
     *
     * @throws \Exception if issue not found, already resolved, or not actionable
     */
    public function acceptAsInc(int $issueId, User $actor, ?string $note = null): array
    {
        $correlationId = (string) Str::uuid();

        return DB::transaction(function () use ($issueId, $actor, $note, $correlationId) {
            $issue = MarkImportRunError::findOrFail($issueId);

            $this->guardActionable($issue);

            // Resolve the error
            $issue->update([
                'is_resolved' => true,
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
                'resolution_action' => MarkImportRunError::RESOLUTION_ACCEPT_INC,
                'resolution_note' => $note,
                'resolution_correlation_id' => $correlationId,
            ]);

            // Find the associated raw mark to get candidate/batch context
            $run = $issue->run;
            $batch = $run ? $run->batch : null;

            // Find matching raw mark row
            $rawMark = null;
            if ($run && $issue->index_number) {
                $rawMark = RawMark::where('mark_import_batch_id', $run->mark_import_batch_id)
                    ->where('candidate_index_number', $issue->index_number)
                    ->where('subject_id', $issue->subject_id)
                    ->first();
            }

            // Update raw mark subject_status to INC (additive — does NOT clear paper marks)
            if ($rawMark) {
                $rawMark->update([
                    'subject_status' => 'INC',
                    'status_reason' => 'Accepted as INC by moderator: ' . ($note ?? 'Missing required papers'),
                    'has_errors' => false, // Unblock this row
                ]);
            }

            // Create SubjectExamStatus record
            $examYear = $batch ? $batch->exam_year : null;
            $examTypeId = $batch ? $batch->exam_type_id : null;
            $candidateId = $rawMark ? $rawMark->candidate_id : null;

            $statusRecord = null;
            if ($candidateId && $issue->subject_id && $examYear && $examTypeId) {
                $statusRecord = SubjectExamStatus::updateOrCreate(
                    [
                        'candidate_id' => $candidateId,
                        'subject_id' => $issue->subject_id,
                        'exam_year' => $examYear,
                        'exam_type_id' => $examTypeId,
                    ],
                    [
                        'status' => SubjectExamStatus::STATUS_INC,
                        'source' => SubjectExamStatus::SOURCE_MODERATION,
                        'batch_id' => $batch?->id,
                        'decided_by' => $actor->id,
                        'decided_at' => now(),
                        'note' => $note ?? 'Missing papers: ' . $issue->paper,
                        'run_error_id' => $issue->id,
                        'correlation_id' => $correlationId,
                    ]
                );
            }

            // Recalculate batch error_records count (excluding resolved actionable)
            if ($batch) {
                $this->recalculateBatchErrors($batch);
            }

            // Log moderation action
            MarkModerationAction::create([
                'action' => 'ACCEPT_INC',
                'scope' => 'candidate',
                'actor_id' => $actor->id,
                'mark_import_batch_id' => $batch?->id,
                'run_id' => $run?->id,
                'exam_year_id' => null,
                'school_id' => $batch?->school_id,
                'subject_id' => $issue->subject_id,
                'candidate_id' => $candidateId,
                'affected_rows' => 1,
                'reason' => 'Accepted as INC: ' . ($note ?? $issue->message),
                'correlation_id' => $correlationId,
            ]);

            Log::info("INC accepted for issue {$issueId}", [
                'correlation_id' => $correlationId,
                'actor' => $actor->id,
                'candidate_id' => $candidateId,
                'subject_id' => $issue->subject_id,
                'batch_id' => $batch?->id,
            ]);

            return [
                'ok' => true,
                'message' => 'Issue accepted as INC. Subject will be excluded from grading.',
                'correlation_id' => $correlationId,
                'issue_id' => $issueId,
                'status_record_id' => $statusRecord?->id,
            ];
        });
    }

    /**
     * Reject a MISSING_REQUIRED_PAPER_MARK issue.
     *
     * - Marks the error as resolved (REJECT)
     * - Creates a rejection record
     * - Does NOT clear the blocking error (batch stays blocked for this row)
     */
    public function reject(int $issueId, User $actor, ?string $note = null): array
    {
        $correlationId = (string) Str::uuid();

        return DB::transaction(function () use ($issueId, $actor, $note, $correlationId) {
            $issue = MarkImportRunError::findOrFail($issueId);

            $this->guardActionable($issue);

            // Resolve the error as REJECT — the row remains invalid
            $issue->update([
                'is_resolved' => true,
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
                'resolution_action' => MarkImportRunError::RESOLUTION_REJECT,
                'resolution_note' => $note ?? 'Incomplete papers rejected — requires re-upload with complete data.',
                'resolution_correlation_id' => $correlationId,
            ]);

            $run = $issue->run;
            $batch = $run ? $run->batch : null;

            $rawMark = null;
            if ($run && $issue->index_number) {
                $rawMark = RawMark::where('mark_import_batch_id', $run->mark_import_batch_id)
                    ->where('candidate_index_number', $issue->index_number)
                    ->where('subject_id', $issue->subject_id)
                    ->first();
            }

            $candidateId = $rawMark?->candidate_id;

            // Create rejection record
            MarkRejection::create([
                'run_id' => $run?->id,
                'mark_import_batch_id' => $batch?->id,
                'candidate_id' => $candidateId,
                'row_number' => $issue->row_number,
                'reason_code' => 'INCOMPLETE_PAPERS_REJECTED',
                'note' => $note ?? 'Missing required papers rejected for correction.',
                'rejected_by' => $actor->id,
                'scope' => 'candidate',
                'correlation_id' => $correlationId,
            ]);

            // Log moderation action
            MarkModerationAction::create([
                'action' => MarkModerationAction::ACTION_REJECT,
                'scope' => 'candidate',
                'actor_id' => $actor->id,
                'mark_import_batch_id' => $batch?->id,
                'run_id' => $run?->id,
                'subject_id' => $issue->subject_id,
                'candidate_id' => $candidateId,
                'affected_rows' => 1,
                'reason' => 'Incomplete papers rejected: ' . ($note ?? $issue->message),
                'correlation_id' => $correlationId,
            ]);

            Log::warning("INC rejected for issue {$issueId}", [
                'correlation_id' => $correlationId,
                'actor' => $actor->id,
                'candidate_id' => $candidateId,
                'subject_id' => $issue->subject_id,
                'batch_id' => $batch?->id,
            ]);

            return [
                'ok' => true,
                'message' => 'Issue rejected. Batch remains blocked until data is corrected and re-uploaded.',
                'correlation_id' => $correlationId,
                'issue_id' => $issueId,
            ];
        });
    }

    /**
     * Accept a missing-paper issue as INC when the error originates from raw_marks (no import run).
     */
    public function acceptAsIncFromRawMark(int $rawMarkId, User $actor, ?string $note = null): array
    {
        $correlationId = (string) Str::uuid();

        return DB::transaction(function () use ($rawMarkId, $actor, $note, $correlationId) {
            $rawMark = RawMark::findOrFail($rawMarkId);

            if (!$rawMark->has_errors) {
                throw new \LogicException("Raw mark #{$rawMarkId} has no errors.");
            }

            $batch = $rawMark->batch ?? MarkImportBatch::find($rawMark->mark_import_batch_id);

            $rawMark->update([
                'subject_status' => 'INC',
                'status_reason' => 'Accepted as INC by moderator: ' . ($note ?? 'Missing required papers'),
                'has_errors' => false,
            ]);

            // Create SubjectExamStatus record
            $examYear = $batch ? $batch->exam_year : null;
            $examTypeId = $batch ? $batch->exam_type_id : null;
            $candidateId = $rawMark->candidate_id;

            $statusRecord = null;
            if ($candidateId && $rawMark->subject_id && $examYear && $examTypeId) {
                $statusRecord = SubjectExamStatus::updateOrCreate(
                    [
                        'candidate_id' => $candidateId,
                        'subject_id' => $rawMark->subject_id,
                        'exam_year' => $examYear,
                        'exam_type_id' => $examTypeId,
                    ],
                    [
                        'status' => SubjectExamStatus::STATUS_INC,
                        'source' => SubjectExamStatus::SOURCE_MODERATION,
                        'batch_id' => $batch?->id,
                        'decided_by' => $actor->id,
                        'decided_at' => now(),
                        'note' => $note ?? 'Missing papers accepted as INC',
                        'correlation_id' => $correlationId,
                    ]
                );
            }

            if ($batch) {
                $this->recalculateBatchErrors($batch);
            }

            MarkModerationAction::create([
                'action' => 'ACCEPT_INC',
                'scope' => 'candidate',
                'actor_id' => $actor->id,
                'mark_import_batch_id' => $batch?->id,
                'exam_year_id' => $examYear,
                'school_id' => $batch?->school_id,
                'subject_id' => $rawMark->subject_id,
                'candidate_id' => $candidateId,
                'affected_rows' => 1,
                'reason' => 'Accepted as INC: ' . ($note ?? 'Missing required papers'),
                'correlation_id' => $correlationId,
            ]);

            Log::info("INC accepted from raw_mark #{$rawMarkId}", [
                'correlation_id' => $correlationId,
                'actor' => $actor->id,
                'candidate_id' => $candidateId,
                'batch_id' => $batch?->id,
            ]);

            return [
                'ok' => true,
                'message' => 'Issue accepted as INC. Subject will be excluded from grading.',
                'correlation_id' => $correlationId,
                'issue_id' => $rawMarkId,
                'status_record_id' => $statusRecord?->id,
            ];
        });
    }

    /**
     * Reject a missing-paper issue when the error originates from raw_marks (no import run).
     */
    public function rejectFromRawMark(int $rawMarkId, User $actor, ?string $note = null): array
    {
        $correlationId = (string) Str::uuid();

        return DB::transaction(function () use ($rawMarkId, $actor, $note, $correlationId) {
            $rawMark = RawMark::findOrFail($rawMarkId);

            if (!$rawMark->has_errors) {
                throw new \LogicException("Raw mark #{$rawMarkId} has no errors.");
            }

            $batch = $rawMark->batch ?? MarkImportBatch::find($rawMark->mark_import_batch_id);

            MarkRejection::create([
                'mark_import_batch_id' => $batch?->id,
                'candidate_id' => $rawMark->candidate_id,
                'row_number' => $rawMark->row_number,
                'reason_code' => 'INCOMPLETE_PAPERS_REJECTED',
                'note' => $note ?? 'Missing required papers rejected for correction.',
                'rejected_by' => $actor->id,
                'scope' => 'candidate',
                'correlation_id' => $correlationId,
            ]);

            MarkModerationAction::create([
                'action' => MarkModerationAction::ACTION_REJECT,
                'scope' => 'candidate',
                'actor_id' => $actor->id,
                'mark_import_batch_id' => $batch?->id,
                'subject_id' => $rawMark->subject_id,
                'candidate_id' => $rawMark->candidate_id,
                'affected_rows' => 1,
                'reason' => 'Incomplete papers rejected: ' . ($note ?? 'Missing required papers'),
                'correlation_id' => $correlationId,
            ]);

            Log::warning("INC rejected from raw_mark #{$rawMarkId}", [
                'correlation_id' => $correlationId,
                'actor' => $actor->id,
                'candidate_id' => $rawMark->candidate_id,
                'batch_id' => $batch?->id,
            ]);

            return [
                'ok' => true,
                'message' => 'Issue rejected. Batch remains blocked until data is corrected and re-uploaded.',
                'correlation_id' => $correlationId,
                'issue_id' => $rawMarkId,
            ];
        });
    }

    /**
     * Guard: ensure the issue is actionable and not yet resolved.
     */
    private function guardActionable(MarkImportRunError $issue): void
    {
        if (!$issue->is_actionable) {
            throw new \LogicException("Issue #{$issue->id} is not actionable (error_code: {$issue->error_code}).");
        }

        if ($issue->is_resolved) {
            throw new \LogicException(
                "Issue #{$issue->id} is already resolved as '{$issue->resolution_action}' " .
                "by user #{$issue->resolved_by} at {$issue->resolved_at}."
            );
        }

        if ($issue->error_code !== MarkImportRunError::CODE_MISSING_REQUIRED_PAPER_MARK) {
            throw new \LogicException(
                "Issue #{$issue->id} has error_code '{$issue->error_code}', expected 'MISSING_REQUIRED_PAPER_MARK'."
            );
        }
    }

    /**
     * Recalculate batch error_records count, excluding resolved actionable errors.
     */
    private function recalculateBatchErrors($batch): void
    {
        $unresolved = RawMark::where('mark_import_batch_id', $batch->id)
            ->where('has_errors', true)
            ->count();

        $batch->update(['error_records' => $unresolved]);
    }
}
