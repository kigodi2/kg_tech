<?php

namespace App\Services\MarkEntry\Outliers;

use App\Models\MarkImportBatch;
use App\Models\MarkOutlierResolution;
use App\Models\MarkImportRunError;
use App\Models\RawMark;
use App\Models\User;
use App\Models\Candidate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EntryOutliersService
{
    /*
     * Outliers Data Sources (ENTRY STAGE ONLY)
     * - mark_import_batches: batch metadata + lifecycle status/scope
     * - raw_marks: entry/moderation-stage marks for QA anomaly checks
     * - mark_import_run_errors: import validation issues (including missing required papers)
     *
     * Safety:
     * - Read-only service. No updates/inserts/deletes are performed.
     */

    public function summary(User $user, array $filters): array
    {
        $batchIds = $this->scopedBatchIds($user, $filters);

        $errorsQuery = MarkImportRunError::query()
            ->join('mark_import_runs', 'mark_import_runs.id', '=', 'mark_import_run_errors.run_id')
            ->whereIn('mark_import_runs.mark_import_batch_id', $batchIds);

        $paperLevelIssues = (clone $errorsQuery)->whereNotNull('mark_import_run_errors.paper')->count();
        $candidatesFlagged = (clone $errorsQuery)
            ->select('mark_import_run_errors.index_number')
            ->whereNotNull('mark_import_run_errors.index_number')
            ->distinct()
            ->count('mark_import_run_errors.index_number');

        $rangeViolations = (clone $errorsQuery)
            ->whereIn('mark_import_run_errors.error_code', ['OUT_OF_RANGE', 'NON_NUMERIC', 'INVALID_FORMAT'])
            ->count();

        $synthetic = $this->syntheticIssues($batchIds, $filters);

        return [
            'batches_flagged' => $synthetic->pluck('batch_id')->merge($errorsQuery->pluck('mark_import_runs.mark_import_batch_id'))->filter()->unique()->count(),
            'candidates_flagged' => $candidatesFlagged + $synthetic->pluck('candidate_index_number')->filter()->unique()->count(),
            'paper_level_issues' => $paperLevelIssues + $synthetic->where('issue_type', 'MISSING_REQUIRED_PAPER_MARK')->count(),
            'max_min_violations' => $rangeViolations + $synthetic->where('issue_type', 'OUT_OF_RANGE')->count(),
        ];
    }

    public function list(User $user, array $filters): LengthAwarePaginator
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(50, (int) ($filters['per_page'] ?? 50)));

        $batchIds = $this->scopedBatchIds($user, $filters);

        $errorRows = MarkImportRunError::query()
            ->join('mark_import_runs', 'mark_import_runs.id', '=', 'mark_import_run_errors.run_id')
            ->join('mark_import_batches', 'mark_import_batches.id', '=', 'mark_import_runs.mark_import_batch_id')
            ->leftJoin('schools', 'schools.id', '=', 'mark_import_batches.school_id')
            ->leftJoin('subjects', 'subjects.id', '=', 'mark_import_batches.subject_id')
            ->whereIn('mark_import_runs.mark_import_batch_id', $batchIds)
            ->when(!empty($filters['q']), function ($q) use ($filters) {
                $term = trim((string) $filters['q']);
                $q->where(function ($sub) use ($term) {
                    $sub->where('mark_import_batches.batch_code', 'like', "%{$term}%")
                        ->orWhere('schools.name', 'like', "%{$term}%")
                        ->orWhere('subjects.name', 'like', "%{$term}%")
                        ->orWhere('mark_import_run_errors.index_number', 'like', "%{$term}%")
                        ->orWhere('mark_import_run_errors.message', 'like', "%{$term}%");
                });
            })
            ->select([
                'mark_import_run_errors.id',
                'mark_import_runs.mark_import_batch_id as batch_id',
                'mark_import_batches.batch_code',
                'mark_import_batches.status as batch_status',
                'mark_import_batches.lifecycle_state as batch_lifecycle_state',
                'schools.name as school_name',
                'subjects.name as subject_name',
                'mark_import_run_errors.index_number as candidate_index_number',
                'mark_import_run_errors.error_code',
                'mark_import_run_errors.is_actionable',
                'mark_import_run_errors.is_resolved',
                'mark_import_run_errors.resolution_action',
                'mark_import_run_errors.message',
                'mark_import_run_errors.paper',
                'mark_import_run_errors.severity',
                'mark_import_run_errors.created_at',
            ])
            ->orderByDesc('mark_import_run_errors.created_at')
            ->limit(2000)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => 'run-error-' . $row->id,
                    'source' => 'import_error',
                    'batch_id' => (int) $row->batch_id,
                    'batch_code' => $row->batch_code,
                    'batch_status' => $row->batch_status,
                    'school_name' => $row->school_name,
                    'subject_name' => $row->subject_name,
                    'candidate_index_number' => $row->candidate_index_number,
                    'issue_type' => $row->error_code,
                    'severity' => $row->severity,
                    'paper' => $row->paper,
                    'is_actionable' => (bool) $row->is_actionable,
                    'is_resolved' => (bool) $row->is_resolved,
                    'resolution_action' => $row->resolution_action,
                    'message' => $row->message,
                    'review_action' => $this->reviewActionForIssue(
                        $row->error_code,
                        $row->batch_status,
                        $row->batch_lifecycle_state,
                        $row->message
                    ),
                    'detected_at' => Carbon::parse($row->created_at)->toDateTimeString(),
                    'detected_at_ts' => Carbon::parse($row->created_at)->timestamp,
                ];
            });

        $all = $errorRows->concat($this->syntheticIssues($batchIds, $filters))
            ->sortByDesc('detected_at_ts')
            ->values();

        $tab = strtolower((string) ($filters['tab'] ?? ''));
        if ($tab === 'missing') {
            $all = $all->filter(fn (array $row) => str_contains((string) ($row['issue_type'] ?? ''), 'MISSING_REQUIRED_PAPER_MARK'))
                ->values();
        } elseif ($tab === 'swings') {
            $all = $all->filter(fn (array $row) => str_contains((string) ($row['issue_type'] ?? ''), 'SUSPICIOUS_SPIKE'))
                ->values();
        } elseif ($tab === 'integrity') {
            $all = $all->filter(fn (array $row) => !in_array((string) ($row['issue_type'] ?? ''), ['MISSING_REQUIRED_PAPER_MARK', 'SUSPICIOUS_SPIKE'], true))
                ->values();
        }

        $total = $all->count();
        $slice = $all->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    public function candidateDetails(User $user, int $candidateId, array $filters): array
    {
        $batchIds = $this->scopedBatchIds($user, $filters);
        $candidateIndex = Candidate::query()->where('id', $candidateId)->value('candidate_id');

        $rawRows = RawMark::query()
            ->join('mark_import_batches', 'mark_import_batches.id', '=', 'raw_marks.mark_import_batch_id')
            ->where('raw_marks.candidate_id', $candidateId)
            ->whereIn('raw_marks.mark_import_batch_id', $batchIds)
            ->select([
                'raw_marks.id',
                'raw_marks.mark_import_batch_id as batch_id',
                'raw_marks.candidate_index_number',
                'raw_marks.paper_1_marks',
                'raw_marks.paper_2_marks',
                'raw_marks.paper_3_marks',
                'raw_marks.practical_marks',
                'raw_marks.project_marks',
                'raw_marks.subject_status',
                'raw_marks.status_reason',
                'mark_import_batches.batch_code',
                'mark_import_batches.status as batch_status',
            ])
            ->orderByDesc('raw_marks.id')
            ->get();

        $issues = $this->list($user, array_merge($filters, ['per_page' => 2000]))
            ->getCollection()
            ->filter(fn (array $row) => (string) ($row['candidate_id'] ?? '') === (string) $candidateId
                || ((string) ($row['candidate_index_number'] ?? '') !== '' && (string) ($row['candidate_index_number'] ?? '') === (string) $candidateIndex))
            ->values();

        return [
            'candidate_id' => $candidateId,
            'rows' => $rawRows,
            'issues' => $issues,
        ];
    }

    public function batchDetails(User $user, int $batchId, array $filters): array
    {
        $batchIds = $this->scopedBatchIds($user, $filters);
        if (!in_array($batchId, $batchIds, true)) {
            return [
                'batch_id' => $batchId,
                'issues' => collect(),
            ];
        }

        $issues = $this->list($user, array_merge($filters, ['per_page' => 2000]))
            ->getCollection()
            ->where('batch_id', $batchId)
            ->values();

        return [
            'batch_id' => $batchId,
            'issues' => $issues,
        ];
    }

    public function tabStatusSummary(User $user, array $filters): array
    {
        $all = $this->list($user, array_merge($filters, [
            'page' => 1,
            'per_page' => 2000,
        ]))->getCollection();

        return [
            'integrity' => $this->tabCounters(
                $all->filter(fn (array $row) => !in_array((string) ($row['issue_type'] ?? ''), ['MISSING_REQUIRED_PAPER_MARK', 'SUSPICIOUS_SPIKE'], true))
            ),
            'missing' => $this->tabCounters(
                $all->filter(fn (array $row) => str_contains((string) ($row['issue_type'] ?? ''), 'MISSING_REQUIRED_PAPER_MARK'))
            ),
            'swings' => $this->tabCounters(
                $all->filter(fn (array $row) => str_contains((string) ($row['issue_type'] ?? ''), 'SUSPICIOUS_SPIKE'))
            ),
        ];
    }

    private function scopedBatchIds(User $user, array $filters): array
    {
        return MarkImportBatch::query()
            ->forUserScope($user)
            ->when(!empty($filters['exam_year_id']), function ($q) use ($filters) {
                $yearLabel = \App\Models\ExamYear::query()->where('id', (int) $filters['exam_year_id'])->value('year_label');
                if ($yearLabel) {
                    $q->where('exam_year', (int) $yearLabel);
                }
            })
            ->when(!empty($filters['status']) && $filters['status'] !== 'all', fn ($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['school_id']), fn ($q) => $q->where('school_id', (int) $filters['school_id']))
            ->when(!empty($filters['subject_id']), fn ($q) => $q->where('subject_id', (int) $filters['subject_id']))
            ->when(!empty($filters['q']), function ($q) use ($filters) {
                $term = trim((string) $filters['q']);
                $q->where(function ($sub) use ($term) {
                    $sub->where('batch_code', 'like', "%{$term}%")
                        ->orWhereHas('school', fn ($s) => $s->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('subject', fn ($s) => $s->where('name', 'like', "%{$term}%"));
                });
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function syntheticIssues(array $batchIds, array $filters): Collection
    {
        if (empty($batchIds)) {
            return collect();
        }

        $threshold = (float) (config('mark_entry.outliers.z_threshold') ?? env('MARK_ENTRY_OUTLIER_Z_THRESHOLD', 3.0));

        $rows = RawMark::query()
            ->join('mark_import_batches', 'mark_import_batches.id', '=', 'raw_marks.mark_import_batch_id')
            ->leftJoin('schools', 'schools.id', '=', 'mark_import_batches.school_id')
            ->leftJoin('subjects', 'subjects.id', '=', 'mark_import_batches.subject_id')
            ->whereIn('raw_marks.mark_import_batch_id', $batchIds)
            ->select([
                'raw_marks.id',
                'raw_marks.mark_import_batch_id as batch_id',
                'raw_marks.candidate_id',
                'raw_marks.candidate_index_number',
                'raw_marks.paper_1_marks',
                'raw_marks.paper_2_marks',
                'raw_marks.paper_3_marks',
                'raw_marks.practical_marks',
                'raw_marks.project_marks',
                'raw_marks.has_errors',
                'raw_marks.subject_status',
                'raw_marks.status_reason',
                'raw_marks.created_at',
                'mark_import_batches.batch_code',
                'mark_import_batches.status as batch_status',
                'mark_import_batches.lifecycle_state as batch_lifecycle_state',
                'schools.name as school_name',
                'subjects.name as subject_name',
                'subjects.has_practical as subject_has_practical',
            ])
            ->get();

        $rawMarkIds = $rows->pluck('id')->filter()->values();
        $spikeResolvedMap = collect();
        if ($rawMarkIds->isNotEmpty()) {
            $spikeResolvedMap = MarkOutlierResolution::query()
                ->where('issue_type', 'SUSPICIOUS_SPIKE')
                ->whereIn('raw_mark_id', $rawMarkIds->all())
                ->pluck('resolved_at', 'raw_mark_id');
        }

        $issues = collect();

        foreach ($rows as $row) {
            $paperValues = [
                'paper_1' => $row->paper_1_marks,
                'paper_2' => $row->paper_2_marks,
                'paper_3' => $row->paper_3_marks,
                'practical' => $row->practical_marks,
                'project' => $row->project_marks,
            ];
            foreach ($paperValues as $paper => $value) {
                if ($value === null) {
                    continue;
                }
                $val = (float) $value;
                $max = $this->paperMaxMarkForOutlier($paper, (bool) ($row->subject_has_practical ?? false));
                if ($val < 0 || $val > $max) {
                    $issues->push([
                        'id' => 'range-' . $row->id . '-' . $paper,
                        'source' => 'raw_marks',
                        'batch_id' => (int) $row->batch_id,
                        'batch_code' => $row->batch_code,
                        'batch_status' => $row->batch_status,
                        'school_name' => $row->school_name,
                        'subject_name' => $row->subject_name,
                        'candidate_id' => $row->candidate_id,
                        'candidate_index_number' => $row->candidate_index_number,
                        'issue_type' => 'OUT_OF_RANGE',
                        'severity' => 'error',
                        'paper' => $paper,
                        'is_actionable' => false,
                        'is_resolved' => false,
                        'resolution_action' => null,
                        'message' => "{$paper} mark {$val} is outside 0-{$max}.",
                        'review_action' => 'Require correction',
                        'detected_at' => Carbon::parse($row->created_at)->toDateTimeString(),
                        'detected_at_ts' => Carbon::parse($row->created_at)->timestamp,
                    ]);
                }
            }

            if (strtoupper((string) ($row->subject_status ?? '')) === 'INC' || str_contains(strtoupper((string) ($row->status_reason ?? '')), 'MISSING')) {
                $issues->push([
                    'id' => 'inc-' . $row->id,
                    'source' => 'raw_marks',
                    'batch_id' => (int) $row->batch_id,
                    'batch_code' => $row->batch_code,
                    'batch_status' => $row->batch_status,
                    'school_name' => $row->school_name,
                    'subject_name' => $row->subject_name,
                    'candidate_id' => $row->candidate_id,
                    'candidate_index_number' => $row->candidate_index_number,
                    'issue_type' => 'MISSING_REQUIRED_PAPER_MARK',
                    'severity' => 'warning',
                    'paper' => null,
                    'is_actionable' => (bool) $row->has_errors && strtoupper((string) ($row->subject_status ?? '')) !== 'INC',
                    'is_resolved' => strtoupper((string) ($row->subject_status ?? '')) === 'INC' || !(bool) $row->has_errors,
                    'resolution_action' => strtoupper((string) ($row->subject_status ?? '')) === 'INC' ? MarkImportRunError::RESOLUTION_ACCEPT_INC : null,
                    'message' => $row->status_reason ?: 'Missing required paper mark detected in entry stage.',
                    'review_action' => $this->reviewActionForIssue(
                        'MISSING_REQUIRED_PAPER_MARK',
                        $row->batch_status,
                        $row->batch_lifecycle_state,
                        $row->status_reason,
                        $row->subject_status,
                        $row->status_reason
                    ),
                    'detected_at' => Carbon::parse($row->created_at)->toDateTimeString(),
                    'detected_at_ts' => Carbon::parse($row->created_at)->timestamp,
                ]);
            }
        }

        $dupGroups = $rows->groupBy(fn ($row) => $row->batch_id . '|' . $row->candidate_index_number)
            ->filter(fn (Collection $group) => $group->count() > 1);
        foreach ($dupGroups as $group) {
            $first = $group->first();
            $issues->push([
                'id' => 'dup-' . $first->batch_id . '-' . md5((string) $first->candidate_index_number),
                'source' => 'raw_marks',
                'batch_id' => (int) $first->batch_id,
                'batch_code' => $first->batch_code,
                'batch_status' => $first->batch_status,
                'school_name' => $first->school_name,
                'subject_name' => $first->subject_name,
                'candidate_id' => $first->candidate_id,
                'candidate_index_number' => $first->candidate_index_number,
                'issue_type' => 'DUPLICATE_ENTRY',
                'severity' => 'warning',
                'paper' => null,
                'is_actionable' => false,
                'is_resolved' => false,
                'resolution_action' => null,
                'message' => 'Duplicate candidate entry rows detected in the same batch.',
                'review_action' => 'Verify latest upload / audit trail',
                'detected_at' => Carbon::parse($first->created_at)->toDateTimeString(),
                'detected_at_ts' => Carbon::parse($first->created_at)->timestamp,
            ]);
        }

        $byBatch = $rows->groupBy('batch_id');
        foreach ($byBatch as $batchRows) {
            $totals = $batchRows->map(function ($row) {
                return (float) ($row->paper_1_marks ?? 0)
                    + (float) ($row->paper_2_marks ?? 0)
                    + (float) ($row->paper_3_marks ?? 0)
                    + (float) ($row->practical_marks ?? 0)
                    + (float) ($row->project_marks ?? 0);
            })->values();

            $mean = $totals->avg();
            $variance = $totals->count() > 0
                ? $totals->map(fn ($v) => pow($v - $mean, 2))->sum() / max($totals->count(), 1)
                : 0.0;
            $std = sqrt(max($variance, 0));
            if ($std <= 0) {
                continue;
            }

            foreach ($batchRows->values() as $index => $row) {
                $score = (float) $totals[$index];
                $z = ($score - $mean) / $std;
                if (abs($z) >= $threshold) {
                    $spikeResolved = $spikeResolvedMap->has((int) $row->id);
                    $issues->push([
                        'id' => 'z-' . $row->id,
                        'source' => 'raw_marks',
                        'batch_id' => (int) $row->batch_id,
                        'batch_code' => $row->batch_code,
                        'batch_status' => $row->batch_status,
                        'school_name' => $row->school_name,
                        'subject_name' => $row->subject_name,
                        'candidate_id' => $row->candidate_id,
                        'candidate_index_number' => $row->candidate_index_number,
                        'issue_type' => 'SUSPICIOUS_SPIKE',
                        'severity' => 'warning',
                        'paper' => null,
                        'is_actionable' => !$spikeResolved,
                        'is_resolved' => $spikeResolved,
                        'resolution_action' => $spikeResolved ? 'APPROVE_SPIKE' : null,
                        'message' => 'Extreme score swing detected in batch (z=' . round($z, 2) . ').',
                        'review_action' => $spikeResolved ? 'Approved' : 'Approve or manual verification',
                        'detected_at' => Carbon::parse($row->created_at)->toDateTimeString(),
                        'detected_at_ts' => Carbon::parse($row->created_at)->timestamp,
                    ]);
                }
            }
        }

        if (!empty($filters['q'])) {
            $term = mb_strtolower(trim((string) $filters['q']));
            $issues = $issues->filter(function (array $row) use ($term) {
                $hay = mb_strtolower(implode(' ', [
                    $row['batch_code'] ?? '',
                    $row['school_name'] ?? '',
                    $row['subject_name'] ?? '',
                    $row['candidate_index_number'] ?? '',
                    $row['message'] ?? '',
                ]));
                return str_contains($hay, $term);
            })->values();
        }

        return $issues;
    }

    private function reviewActionForIssue(
        string $errorCode,
        ?string $batchStatus = null,
        ?string $lifecycleState = null,
        ?string $message = null,
        ?string $subjectStatus = null,
        ?string $statusReason = null
    ): string
    {
        if ($errorCode === 'MISSING_REQUIRED_PAPER_MARK') {
            $status = strtolower((string) $batchStatus);
            $lifecycle = strtolower((string) $lifecycleState);
            $isApprovedLike = in_array($status, ['approved', 'locked', 'processed'], true)
                || in_array($lifecycle, ['approved', 'locked', 'processed'], true);
            if ($isApprovedLike) {
                $subjectStatusUpper = strtoupper((string) $subjectStatus);
                $combined = strtoupper(trim(((string) $message) . ' ' . ((string) $statusReason)));
                if ($subjectStatusUpper === 'X' || str_contains($combined, "MARKED AS 'X'") || preg_match('/\bX\b/', $combined)) {
                    return 'X';
                }
                if ($subjectStatusUpper === 'INC' || str_contains($combined, 'INC')) {
                    return 'INC';
                }
            }
            return 'Treat as INC / Require correction';
        }
        if (in_array($errorCode, ['OUT_OF_RANGE', 'NON_NUMERIC', 'INVALID_FORMAT'], true)) {
            return 'Require correction';
        }

        return 'Manual review';
    }

    private function paperMaxMarkForOutlier(string $paperCode, bool $hasPractical): float
    {
        if ($paperCode === 'practical') {
            return 50.0;
        }

        // ACSEE practical can be captured as paper_3 in some flows.
        if ($paperCode === 'paper_3' && $hasPractical) {
            return 50.0;
        }

        return 100.0;
    }

    private function tabCounters(Collection $rows): array
    {
        $actionable = $rows->filter(fn (array $row) => (bool) ($row['is_actionable'] ?? false) && !(bool) ($row['is_resolved'] ?? false))->count();
        $resolved = $rows->filter(fn (array $row) => (bool) ($row['is_resolved'] ?? false))->count();
        $total = $rows->count();

        return [
            'total' => $total,
            'actionable' => $actionable,
            'resolved' => $resolved,
            'manual' => max(0, $total - $actionable - $resolved),
        ];
    }
}
