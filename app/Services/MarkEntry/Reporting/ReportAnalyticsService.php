<?php

namespace App\Services\MarkEntry\Reporting;

use App\Models\ExamType;
use App\Models\MarkImportBatch;
use App\Models\RawMark;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportAnalyticsService
{
    /**
     * Resolve exam_year_id (PK) to year_label (e.g. 2026) for batch queries.
     */
    private function resolveYearValue(int $examYearId): int
    {
        $examYear = \App\Models\ExamYear::findOrFail($examYearId);
        return (int) $examYear->year_label;
    }

    /**
     * Get completion rates: how many schools/subjects have marks imported
     */
    public function getCompletionRates(int $examYearId, ?int $districtId = null): array
    {
        $yearValue = $this->resolveYearValue($examYearId);
        $batchQuery = MarkImportBatch::where('exam_year', $yearValue);
        if ($districtId) {
            $batchQuery->where('district_id', $districtId);
        }

        $totalBatches = (clone $batchQuery)->count();
        $approvedBatches = (clone $batchQuery)->whereIn('status', [
            MarkImportBatch::STATUS_APPROVED,
            MarkImportBatch::STATUS_LOCKED,
            MarkImportBatch::STATUS_PROCESSED,
        ])->count();

        $schoolsWithMarks = (clone $batchQuery)->distinct('school_id')->count('school_id');
        $subjectsWithMarks = (clone $batchQuery)->distinct('subject_id')->count('subject_id');

        $totalMarks = RawMark::whereHas('batch', function ($q) use ($yearValue, $districtId) {
            $q->where('exam_year', $yearValue);
            if ($districtId) $q->where('district_id', $districtId);
        })->count();

        $errorFreeMarks = RawMark::whereHas('batch', function ($q) use ($yearValue, $districtId) {
            $q->where('exam_year', $yearValue);
            if ($districtId) $q->where('district_id', $districtId);
        })->where('has_errors', false)->count();

        return [
            'total_batches' => $totalBatches,
            'approved_batches' => $approvedBatches,
            'completion_rate' => $totalBatches > 0 ? round(($approvedBatches / $totalBatches) * 100, 1) : 0,
            'schools_with_marks' => $schoolsWithMarks,
            'subjects_with_marks' => $subjectsWithMarks,
            'total_marks' => $totalMarks,
            'error_free_marks' => $errorFreeMarks,
            'data_quality_rate' => $totalMarks > 0 ? round(($errorFreeMarks / $totalMarks) * 100, 1) : 0,
        ];
    }

    /**
     * Get missing marks heatmap data: schools × subjects
     */
    public function getMissingMarksHeatmap(int $examYearId, ?int $districtId = null): array
    {
        $yearValue = $this->resolveYearValue($examYearId);
        $query = MarkImportBatch::where('exam_year', $yearValue)
            ->with(['school:id,code,name', 'subject:id,code,name']);

        if ($districtId) {
            $query->where('district_id', $districtId);
        }

        $batches = $query->get();

        $schools = $batches->pluck('school')->unique('id')->sortBy('code')->values();
        $subjects = $batches->pluck('subject')->unique('id')->sortBy('code')->values();

        $matrix = [];
        foreach ($schools as $school) {
            $row = ['school_code' => $school->code, 'school_name' => $school->name, 'subjects' => []];
            foreach ($subjects as $subject) {
                $batch = $batches->first(fn($b) => $b->school_id === $school->id && $b->subject_id === $subject->id);
                $row['subjects'][$subject->code] = $batch ? [
                    'status' => $batch->status,
                    'total_records' => $batch->total_records,
                    'error_records' => $batch->error_records,
                    'has_data' => true,
                ] : ['has_data' => false];
            }
            $matrix[] = $row;
        }

        return [
            'subjects' => $subjects->map(fn($s) => ['code' => $s->code, 'name' => $s->name])->values()->toArray(),
            'schools' => $matrix,
        ];
    }

    /**
     * Get mark distribution bins (0-9, 10-19, ..., 90-100) for a subject
     */
    public function getMarkDistribution(int $examYearId, int $subjectId): array
    {
        $yearValue = $this->resolveYearValue($examYearId);
        $marks = RawMark::whereHas('batch', function ($q) use ($yearValue, $subjectId) {
            $q->where('exam_year', $yearValue)
              ->where('subject_id', $subjectId)
              ->whereIn('status', [
                  MarkImportBatch::STATUS_APPROVED,
                  MarkImportBatch::STATUS_LOCKED,
                  MarkImportBatch::STATUS_PROCESSED,
              ]);
        })
        ->where('has_errors', false)
        ->get();

        // Calculate total marks per candidate
        $totals = $marks->map(function ($mark) {
            return (float)($mark->paper_1_marks ?? 0)
                 + (float)($mark->paper_2_marks ?? 0)
                 + (float)($mark->paper_3_marks ?? 0)
                 + (float)($mark->practical_marks ?? 0)
                 + (float)($mark->project_marks ?? 0);
        });

        $bins = [];
        $ranges = ['0-9', '10-19', '20-29', '30-39', '40-49', '50-59', '60-69', '70-79', '80-89', '90-100'];
        foreach ($ranges as $i => $range) {
            $low = $i * 10;
            $high = $i === 9 ? 100 : ($low + 9);
            $bins[] = [
                'range' => $range,
                'count' => $totals->filter(fn($t) => $t >= $low && $t <= $high)->count(),
            ];
        }

        return [
            'subject_id' => $subjectId,
            'total_candidates' => $totals->count(),
            'average' => $totals->count() > 0 ? round($totals->average(), 1) : 0,
            'min' => $totals->count() > 0 ? $totals->min() : 0,
            'max' => $totals->count() > 0 ? $totals->max() : 0,
            'bins' => $bins,
        ];
    }

    /**
     * Get top anomalies: candidates with high variance between papers
     */
    public function getAnomalies(int $examYearId, ?int $subjectId = null, int $limit = 20): array
    {
        $yearValue = $this->resolveYearValue($examYearId);
        $query = RawMark::whereHas('batch', function ($q) use ($yearValue, $subjectId) {
            $q->where('exam_year', $yearValue)
              ->whereIn('status', [
                  MarkImportBatch::STATUS_APPROVED,
                  MarkImportBatch::STATUS_LOCKED,
                  MarkImportBatch::STATUS_PROCESSED,
              ]);
            if ($subjectId) $q->where('subject_id', $subjectId);
        })
        ->where('has_errors', false)
        ->whereNotNull('paper_1_marks')
        ->whereNotNull('paper_2_marks')
        ->with(['candidate:id,candidate_id,full_name', 'batch:id,subject_id', 'batch.subject:id,code,name'])
        ->get();

        // Calculate variance for each candidate
        $anomalies = $query->map(function ($mark) {
            $papers = array_filter([
                (float)($mark->paper_1_marks ?? 0),
                (float)($mark->paper_2_marks ?? 0),
                (float)($mark->paper_3_marks ?? 0),
                (float)($mark->practical_marks ?? 0),
                (float)($mark->project_marks ?? 0),
            ], fn($v) => $v > 0);

            if (count($papers) < 2) return null;

            $mean = array_sum($papers) / count($papers);
            $variance = array_sum(array_map(fn($p) => pow($p - $mean, 2), $papers)) / count($papers);
            $stddev = sqrt($variance);

            return [
                'candidate_index' => $mark->candidate_index_number ?? $mark->candidate?->candidate_id ?? '—',
                'candidate_name' => $mark->full_name ?? $mark->candidate?->full_name ?? '—',
                'subject' => $mark->batch?->subject?->code ?? '—',
                'papers' => $papers,
                'mean' => round($mean, 1),
                'std_dev' => round($stddev, 1),
                'variance' => round($variance, 1),
            ];
        })
        ->filter()
        ->sortByDesc('std_dev')
        ->take($limit)
        ->values();

        return $anomalies->toArray();
    }

    /**
     * Get summary report data
     */
    public function getSummaryReport(int $examYearId, ?int $districtId = null): array
    {
        $completion = $this->getCompletionRates($examYearId, $districtId);
        $yearValue = $this->resolveYearValue($examYearId);

        // Schools with highest missing marks
        $schoolsQuery = MarkImportBatch::where('exam_year', $yearValue)
            ->select('school_id', DB::raw('SUM(error_records) as total_errors'), DB::raw('SUM(total_records) as total_records'), DB::raw('COUNT(*) as batch_count'))
            ->groupBy('school_id')
            ->with('school:id,code,name')
            ->orderByDesc('total_errors')
            ->limit(10);

        if ($districtId) {
            $schoolsQuery->where('district_id', $districtId);
        }

        $worstSchools = $schoolsQuery->get()->map(fn($b) => [
            'school_code' => $b->school?->code,
            'school_name' => $b->school?->name,
            'total_errors' => $b->total_errors,
            'total_records' => $b->total_records,
            'batch_count' => $b->batch_count,
            'error_rate' => $b->total_records > 0 ? round(($b->total_errors / $b->total_records) * 100, 1) : 0,
        ]);

        // Locking/submission status snapshot
        $statusSnapshot = MarkImportBatch::where('exam_year', $yearValue)
            ->when($districtId, fn($q) => $q->where('district_id', $districtId))
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'completion' => $completion,
            'worst_schools' => $worstSchools,
            'status_snapshot' => $statusSnapshot,
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
