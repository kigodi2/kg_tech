<?php

namespace App\Services\MarkEntry\Moderation;

use App\Models\MarkImportBatch;
use App\Models\MarkImportRun;
use App\Models\MarkImportRunError;
use App\Models\MarkModerationAction;
use App\Models\MarkModerationReview;
use App\Models\MarkRejection;
use App\Models\RawMark;
use App\Services\MarkImport\MarkValidationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class ModerationDashboardService
{
    public function __construct(
        private MarkValidationService $markValidationService
    ) {
    }

    /**
     * Get dashboard statistics for review dashboard
     */
    public function getDashboardStats(?int $examYear = null): array
    {
        $pendingQuery = MarkImportBatch::query();
        $this->applyPendingModerationScope($pendingQuery);

        if ($examYear) {
            $pendingQuery->where('exam_year', $examYear);
        }

        $totalPending = $pendingQuery->count();

        // Errors to Review: count from MarkImportRunError + raw_marks with has_errors
        $runErrors = MarkImportRunError::where('severity', 'error')
            ->whereHas('run', function ($q) use ($examYear) {
                $q->whereHas('batch', function ($bq) use ($examYear) {
                    $this->applyPendingModerationScope($bq);
                    if ($examYear) {
                        $bq->where('exam_year', $examYear);
                    }
                });
            })
            ->count();

        // Also count raw_marks with errors in pending batches (fallback path)
        $rawMarkErrors = RawMark::where('has_errors', true)
            ->whereHas('batch', function ($bq) use ($examYear) {
                $this->applyPendingModerationScope($bq);
                if ($examYear) {
                    $bq->where('exam_year', $examYear);
                }
            })
            ->count();

        $errorsToReview = $runErrors + $rawMarkErrors;

        $today = Carbon::today();

        // Count from MarkModerationReview (all approve/reject paths create these)
        $approvedToday = MarkModerationReview::where('status', 'approved')
            ->whereDate('reviewed_at', $today)
            ->when($examYear, fn($q) => $q->whereHas('batch', fn($bq) => $bq->where('exam_year', $examYear)))
            ->count();

        $rejectedToday = MarkModerationReview::where('status', 'rejected')
            ->whereDate('reviewed_at', $today)
            ->when($examYear, fn($q) => $q->whereHas('batch', fn($bq) => $bq->where('exam_year', $examYear)))
            ->count();

        return [
            'total_pending' => $totalPending,
            'errors_to_review' => $errorsToReview,
            'approved_today' => $approvedToday,
            'rejected_today' => $rejectedToday,
        ];
    }

    /**
     * Get filtered pending review queue with related data
     */
    public function getPendingQueue(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = MarkImportBatch::query()
            ->with(['school', 'subject', 'region', 'district', 'importedByUser'])
            ->withCount(['rawMarks as pending_marks_count' => function ($q) {
                $q->where('has_errors', false);
            }])
            ->withCount(['rawMarks as error_marks_count' => function ($q) {
                $q->where('has_errors', true);
            }])
            ->withCount(['rawMarks as warning_marks_count' => function ($q) {
                $q->where('has_warnings', true)->where('has_errors', false);
            }])
            ->withCount(['rawMarks as absent_marks_count' => function ($q) {
                $q->whereNotNull('subject_status');
            }])
            ->addSelect(['candidate_count' => RawMark::selectRaw('COUNT(DISTINCT candidate_index_number)')
                ->whereColumn('mark_import_batch_id', 'mark_import_batches.id')
            ]);

        $this->applyPendingModerationScope($query);

        if (!empty($filters['exam_year'])) {
            $query->where('exam_year', $filters['exam_year']);
        }
        if (!empty($filters['region_id'])) {
            $query->where('region_id', $filters['region_id']);
        }
        if (!empty($filters['district_id'])) {
            $query->where('district_id', $filters['district_id']);
        }
        if (!empty($filters['school_id'])) {
            $query->where('school_id', $filters['school_id']);
        }
        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }
        if (!empty($filters['status']) && $filters['status'] === 'errors') {
            $query->where('error_records', '>', 0);
        }

        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);
        $collection = $paginator->getCollection();

        if ($collection->isEmpty()) {
            return $paginator;
        }

        $batchIds = $collection->pluck('id')->map(fn ($v) => (int) $v)->all();
        $this->reconcileStaleCandidateNotFoundErrors($batchIds);

        // Resolve latest import run per batch, then count unresolved blocking errors on that run.
        $latestRuns = MarkImportRun::query()
            ->select('id', 'mark_import_batch_id')
            ->whereIn('mark_import_batch_id', $batchIds)
            ->orderByDesc('id')
            ->get()
            ->unique('mark_import_batch_id')
            ->values();

        $runIdByBatch = $latestRuns
            ->mapWithKeys(fn ($run) => [(int) $run->mark_import_batch_id => (int) $run->id]);

        $runIds = $latestRuns->pluck('id')->map(fn ($v) => (int) $v)->all();

        $unresolvedByRun = empty($runIds)
            ? collect()
            : MarkImportRunError::query()
                ->selectRaw('run_id, COUNT(*) as cnt')
                ->whereIn('run_id', $runIds)
                ->where('severity', 'error')
                ->where(function ($q) {
                    $q->where('is_actionable', false)
                        ->orWhere(function ($q2) {
                            $q2->where('is_actionable', true)->where('is_resolved', false);
                        });
                })
                ->groupBy('run_id')
                ->pluck('cnt', 'run_id');

        // Fallback for legacy batches with no linked runs.
        $legacyRawErrors = RawMark::query()
            ->selectRaw('mark_import_batch_id, COUNT(*) as cnt')
            ->whereIn('mark_import_batch_id', $batchIds)
            ->where('has_errors', true)
            ->groupBy('mark_import_batch_id')
            ->pluck('cnt', 'mark_import_batch_id');

        $collection->transform(function ($batch) use ($runIdByBatch, $unresolvedByRun, $legacyRawErrors) {
            $batchId = (int) $batch->id;
            $latestRunId = $runIdByBatch->get($batchId);

            if ($latestRunId) {
                $batch->error_marks_count = (int) ($unresolvedByRun->get((int) $latestRunId) ?? 0);
            } else {
                $batch->error_marks_count = (int) ($legacyRawErrors->get($batchId) ?? 0);
            }

            return $batch;
        });

        $paginator->setCollection($collection);
        return $paginator;
    }

    /**
     * Scope batches that are truly pending moderation.
     * Prevent historical terminal batches (locked/approved/processed/rejected/archived/superseded)
     * from leaking into approval-stage queue due to stale lifecycle_state values.
     */
    private function applyPendingModerationScope($query): void
    {
        $query->where(function ($q) {
            $q->whereIn('status', ['validated', 'submitted'])
              ->orWhere(function ($legacy) {
                  $legacy->whereNull('status')
                         ->where(function ($lq) {
                             $lq->whereIn('lifecycle_state', ['awaiting_moderation', 'validated', 'submitted'])
                                ->orWhereNull('lifecycle_state');
                         });
              });
        });

        $query->whereNotIn('status', ['approved', 'locked', 'processed', 'rejected', 'archived', 'superseded']);
    }

    /**
     * Get import run errors for a specific run (filterable)
     */
    public function getRunErrors(int $runId, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = MarkImportRunError::where('run_id', $runId);

        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }
        if (!empty($filters['error_code'])) {
            $query->where('error_code', $filters['error_code']);
        }

        return $query->orderBy('row_number', 'asc')->paginate($perPage);
    }

    /**
     * Get errors for a batch (via its import runs)
     */
    public function getBatchErrors(int $batchId, int $perPage = 50): LengthAwarePaginator
    {
        $latestRunId = MarkImportRun::query()
            ->where('mark_import_batch_id', $batchId)
            ->latest('id')
            ->value('id');

        if (!$latestRunId) {
            return MarkImportRunError::query()->whereRaw('1 = 0')->paginate($perPage);
        }

        return MarkImportRunError::query()
            ->where('run_id', $latestRunId)
            ->with(['run:id,file_name,created_at', 'subject:id,code,name'])
            ->orderByRaw("CASE WHEN is_resolved = 0 THEN 0 ELSE 1 END")
            ->orderBy('row_number', 'asc')
            ->paginate($perPage);
    }

    /**
     * Export errors as CSV-ready array for a run
     */
    public function exportRunErrorsCsv(int $runId): array
    {
        $errors = MarkImportRunError::where('run_id', $runId)
            ->with('subject:id,code,name')
            ->orderBy('row_number', 'asc')
            ->get();

        $rows = [['Row', 'Index Number', 'Subject', 'Paper', 'Column', 'Error Code', 'Message', 'Raw Value', 'Severity']];

        foreach ($errors as $error) {
            $rows[] = [
                $error->row_number,
                $error->index_number ?? '',
                $error->subject?->code ?? '',
                $error->paper ?? '',
                $error->column_name ?? '',
                $error->error_code,
                $error->message,
                $error->raw_value ?? '',
                $error->severity,
            ];
        }

        return $rows;
    }

    /**
     * Get errors and warnings from raw_marks for a batch (fallback when no import runs linked).
     * Transforms RawMark fields to match MarkImportRunError shape expected by the UI.
     */
    public function getBatchRawMarkErrors(int $batchId, int $perPage = 50): LengthAwarePaginator
    {
        $this->reconcileStaleCandidateNotFoundErrors([$batchId]);

        $paginator = RawMark::where('mark_import_batch_id', $batchId)
            ->where(function ($q) {
                $q->where('has_errors', true)->orWhere('has_warnings', true);
            })
            ->with('subject:id,code,name')
            ->orderByRaw("CASE WHEN has_errors = 1 THEN 0 ELSE 1 END")
            ->orderBy('row_number', 'asc')
            ->paginate($perPage);

        // Transform items to match MarkImportRunError field names the modal expects
        $paginator->getCollection()->transform(function ($mark) {
            $isError = (bool) $mark->has_errors;
            $messages = $isError
                ? (is_array($mark->error_messages) ? $mark->error_messages : [])
                : (is_array($mark->warning_messages) ? $mark->warning_messages : []);
            $messageStr = implode('; ', $messages);

            // Re-evaluate practical marks range: fix legacy "0 and 100" errors to use correct 0-50 range
            if ($isError && preg_match('/Practical marks must be between 0 and 100/i', $messageStr)) {
                $practicalVal = $mark->practical_marks;
                if ($practicalVal !== null && is_numeric($practicalVal) && $practicalVal >= 0 && $practicalVal <= 50) {
                    // Practical mark is actually valid under the correct 0-50 range — clear this error
                    $remainingMessages = array_filter(
                        is_array($mark->error_messages) ? $mark->error_messages : [],
                        fn($m) => !preg_match('/Practical marks must be between/i', $m)
                    );
                    if (empty($remainingMessages)) {
                        $mark->update(['has_errors' => false, 'error_messages' => []]);
                        $isError = false;
                        $messages = is_array($mark->warning_messages) ? $mark->warning_messages : [];
                        $messageStr = implode('; ', $messages);
                    } else {
                        $mark->update(['error_messages' => array_values($remainingMessages)]);
                        $messages = $remainingMessages;
                        $messageStr = implode('; ', $messages);
                    }
                } elseif ($practicalVal !== null && is_numeric($practicalVal) && $practicalVal > 50) {
                    // Still invalid but update message to reflect correct range
                    $messageStr = preg_replace(
                        '/Practical marks must be between 0 and 100/',
                        "Practical marks must be between 0 and 50 (got: {$practicalVal})",
                        $messageStr
                    );
                }
            }

            // Count how many "missing paper" messages there are
            $missingPaperCount = preg_match_all('/(?:Paper \d+|Practical|Project) marks (?:are )?missing/i', $messageStr);

            // Determine required components from subject config
            $subject = $mark->subject;
            $requiredCount = 0;
            if ($subject) {
                $requiredCount = (int) ($subject->written_papers ?? 1);
                if ($subject->has_practical) $requiredCount++;
                if ($subject->has_project) $requiredCount++;
            }

            // ALL papers missing → Absent (X), not INC
            $isAllMissing = $missingPaperCount > 0 && $requiredCount > 0 && $missingPaperCount >= $requiredCount;
            // Only SOME papers missing → INC (actionable)
            $isPartialMissing = $missingPaperCount > 0 && !$isAllMissing;

            if ($isAllMissing) {
                $errorCode = 'SUBJECT_ABSENT_X';
                $severity = 'warning';
                $messageStr = "All required papers missing — candidate marked as 'X' (did not appear).";
                $isActionable = false;
            } elseif ($isPartialMissing) {
                $errorCode = 'MISSING_REQUIRED_PAPER_MARK';
                $severity = 'warning';
                $isActionable = true;
            } else {
                $errorCode = $isError ? 'VALIDATION_ERROR' : ($mark->subject_status === 'X' ? 'SUBJECT_ABSENT_X' : 'WARNING');
                $severity = $isError ? 'error' : 'warning';
                $isActionable = false;
            }

            // Extract paper number from message if possible
            $paper = null;
            if ($isPartialMissing && preg_match('/Paper (\d+)/i', $messageStr, $m)) {
                $paper = (int) $m[1];
            }

            return (object) [
                'id' => $mark->id,
                'row_number' => $mark->row_number,
                'index_number' => $mark->candidate_index_number,
                'paper' => $paper,
                'column_name' => null,
                'error_code' => $errorCode,
                'message' => $messageStr,
                'raw_value' => $mark->subject_status ?: ($isAllMissing ? 'X' : null),
                'severity' => $severity,
                'subject' => $subject,
                'is_actionable' => $isActionable,
                'is_resolved' => $isAllMissing,
                'resolution_action' => $isAllMissing ? 'AUTO_ABSENT' : null,
            ];
        });

        return $paginator;
    }

    /**
     * Export raw_marks errors as CSV-ready array for a batch (fallback)
     */
    public function exportBatchRawMarkErrorsCsv(int $batchId): array
    {
        $marks = RawMark::where('mark_import_batch_id', $batchId)
            ->where('has_errors', true)
            ->with('subject:id,code,name')
            ->orderBy('row_number', 'asc')
            ->get();

        $rows = [['Row', 'Index Number', 'Subject', 'Error Messages']];

        foreach ($marks as $mark) {
            $messages = is_array($mark->error_messages) ? implode('; ', $mark->error_messages) : ($mark->error_messages ?? '');
            $rows[] = [
                $mark->row_number,
                $mark->candidate_index_number ?? '',
                $mark->subject?->code ?? '',
                $messages,
            ];
        }

        return $rows;
    }

    /**
     * Check if a batch has linked import runs
     */
    public function batchHasLinkedRuns(int $batchId): bool
    {
        return MarkImportRun::where('mark_import_batch_id', $batchId)->exists();
    }

    /**
     * Reconcile stale "candidate not found" errors when candidate registration happens after initial validation.
     * This only updates raw_marks validation state; candidate registration data is not modified.
     */
    private function reconcileStaleCandidateNotFoundErrors(array $batchIds): void
    {
        $batchIds = array_values(array_unique(array_filter(array_map('intval', $batchIds))));
        if (empty($batchIds)) {
            return;
        }

        $marks = RawMark::query()
            ->whereIn('mark_import_batch_id', $batchIds)
            ->where('has_errors', true)
            ->whereNull('candidate_id')
            ->whereNotNull('candidate_index_number')
            ->where('error_messages', 'like', '%Candidate with index number%not found%')
            ->with(['batch:id,school_id,exam_year,subject_id'])
            ->get();

        if ($marks->isEmpty()) {
            return;
        }

        foreach ($marks as $mark) {
            $batch = $mark->batch;
            if (!$batch) {
                continue;
            }

            $candidateId = DB::table('candidates')
                ->where('candidate_id', $mark->candidate_index_number)
                ->where('school_id', (int) $batch->school_id)
                ->value('id');

            if (!$candidateId) {
                continue;
            }

            $mark->candidate_id = (int) $candidateId;
            $mark->save();
            $mark->refresh();
            $mark->unsetRelation('candidate');

            $errors = $this->markValidationService->validateRawMark($mark, $batch);
            $mark->update([
                'has_errors' => !empty($errors),
                'error_messages' => array_values($errors),
            ]);
        }
    }
}
