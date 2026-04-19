<?php

namespace App\Services\Results\Outliers;

use App\Models\CandidateResult;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\FinalGrade;
use App\Models\FinalOutlierResolution;
use App\Models\SubjectMarks;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class FinalOutliersService
{
    private array $columnCache = [];

    /*
     * Outliers Data Sources (FINAL STAGE ONLY)
     * - subject_marks (final): per-subject marks/grade/status
     * - final_grades (final): GPA/division/overall grade context
     * - candidate_results (final): candidate-level final result state/division
     * - result_processes (final process metadata, consumed at controller layer if needed)
     *
     * Safety:
     * - Strict read-only analytics service. No mutations are performed.
     */

    public function summary(User $user, array $filters): array
    {
        $candidateIds = $this->scopedCandidateIds($user, $filters);
        $subjectRows = $this->scopedSubjectMarks($candidateIds, $filters);

        $subjectStats = $this->buildSubjectStats($subjectRows);
        $candidateOutliers = $this->candidateOutliersCollection($candidateIds, $subjectRows, $subjectStats, $filters);
        $schoolOutliers = $this->schoolOutliersCollection($candidateIds, $subjectRows, $filters);

        $topSchool = $schoolOutliers->sortByDesc(fn ($r) => abs((float) ($r['z_score'] ?? 0)))->first();
        $topSubject = collect($subjectStats)->sortByDesc(fn ($r) => (float) ($r['std_dev'] ?? 0))->first();

        return [
            'flagged_candidates' => $candidateOutliers->count(),
            'flagged_schools' => $schoolOutliers->where('is_flagged', true)->count(),
            'top_outlier_school' => $topSchool,
            'top_outlier_subject' => $topSubject,
            'missing_withheld' => [
                'INC' => $subjectRows->where('subject_status', 'INC')->count(),
                'ABS' => $subjectRows->where('subject_status', 'ABS')->count(),
                'X' => $subjectRows->where('subject_status', 'X')->count(),
            ],
        ];
    }

    public function candidates(User $user, array $filters): LengthAwarePaginator
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 25)));

        $candidateIds = $this->scopedCandidateIds($user, $filters);
        $subjectRows = $this->scopedSubjectMarks($candidateIds, $filters);
        $subjectStats = $this->buildSubjectStats($subjectRows);

        $collection = $this->candidateOutliersCollection($candidateIds, $subjectRows, $subjectStats, $filters)
            ->sortByDesc(fn ($r) => abs((float) ($r['z_score'] ?? 0)))
            ->values();

        return $this->paginateCollection($collection, $page, $perPage);
    }

    public function schools(User $user, array $filters): LengthAwarePaginator
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 25)));

        $candidateIds = $this->scopedCandidateIds($user, $filters);
        $subjectRows = $this->scopedSubjectMarks($candidateIds, $filters);

        $collection = $this->schoolOutliersCollection($candidateIds, $subjectRows, $filters)
            ->sortByDesc(fn ($r) => abs((float) ($r['z_score'] ?? 0)))
            ->values();

        return $this->paginateCollection($collection, $page, $perPage);
    }

    public function subjects(User $user, array $filters): array
    {
        $candidateIds = $this->scopedCandidateIds($user, $filters);
        $subjectRows = $this->scopedSubjectMarks($candidateIds, $filters);
        if (empty($candidateIds)) {
            return [
                'subject_distribution' => [],
                'division_distribution' => [],
                'missing_withheld' => [
                    'INC' => 0,
                    'ABS' => 0,
                    'X' => 0,
                ],
            ];
        }

        $subjectStats = collect($this->buildSubjectStats($subjectRows))->values();
        $divisionDistribution = collect();
        if ($this->hasColumn('candidate_results', 'division')) {
            $divisionDistribution = CandidateResult::query()
                ->where('exam_type_id', $this->acseeExamTypeId())
                ->where('year', $this->resolveYear($filters))
                ->whereIn('candidate_id', $candidateIds)
                ->selectRaw('division, COUNT(*) as total')
                ->groupBy('division')
                ->orderBy('division')
                ->get();
        } elseif ($this->hasColumn('final_grades', 'division')) {
            $divisionDistribution = FinalGrade::query()
                ->where('exam_type_id', $this->acseeExamTypeId())
                ->where('year', $this->resolveYear($filters))
                ->whereIn('candidate_id', $candidateIds)
                ->selectRaw('division, COUNT(*) as total')
                ->groupBy('division')
                ->orderBy('division')
                ->get();
        }

        return [
            'subject_distribution' => $subjectStats,
            'division_distribution' => $divisionDistribution,
            'missing_withheld' => [
                'INC' => $subjectRows->where('subject_status', 'INC')->count(),
                'ABS' => $subjectRows->where('subject_status', 'ABS')->count(),
                'X' => $subjectRows->where('subject_status', 'X')->count(),
            ],
        ];
    }

    private function candidateOutliersCollection(array $candidateIds, Collection $subjectRows, array $subjectStats, array $filters): Collection
    {
        $threshold = (float) ($filters['z_threshold'] ?? 3.0);
        $statsBySubject = collect($subjectStats)->keyBy('subject_id');

        $rows = $subjectRows->map(function ($row) use ($statsBySubject, $threshold) {
            $stats = $statsBySubject->get($row->subject_id);
            $std = (float) ($stats['std_dev'] ?? 0);
            if ($std <= 0) {
                return null;
            }

            $z = ((float) $row->marks_obtained - (float) $stats['mean']) / $std;
            if (abs($z) < $threshold) {
                return null;
            }

            return [
                'candidate_id' => (int) $row->candidate_id,
                'index_number' => $row->index_number,
                'candidate_name' => $row->candidate_name,
                'school_id' => (int) $row->school_id,
                'school_name' => $row->school_name,
                'subject_id' => (int) $row->subject_id,
                'subject_name' => $row->subject_name,
                'mark' => (float) $row->marks_obtained,
                'subject_mean' => round((float) $stats['mean'], 2),
                'subject_std' => round($std, 2),
                'z_score' => round($z, 2),
                'flag' => $z > 0 ? 'HIGH_OUTLIER' : 'LOW_OUTLIER',
                'overall_grade' => $row->overall_grade,
                'division' => $row->division,
                'gpa' => $row->gpa,
                'competence_level' => $row->competence_level,
            ];
        })->filter()->values();

        if (!empty($filters['q'])) {
            $term = mb_strtolower(trim((string) $filters['q']));
            $rows = $rows->filter(function (array $row) use ($term) {
                $hay = mb_strtolower(implode(' ', [
                    $row['index_number'] ?? '',
                    $row['candidate_name'] ?? '',
                    $row['school_name'] ?? '',
                    $row['subject_name'] ?? '',
                ]));
                return str_contains($hay, $term);
            })->values();
        }

        return $this->filterResolvedCandidateRows($rows, $filters);
    }

    private function schoolOutliersCollection(array $candidateIds, Collection $subjectRows, array $filters): Collection
    {
        $schoolStats = $subjectRows
            ->groupBy('school_id')
            ->map(function (Collection $rows, $schoolId) {
                $marks = $rows->pluck('marks_obtained')->map(fn ($v) => (float) $v)->values();
                $mean = $marks->avg() ?? 0.0;
                $gradeA = $rows->where('grade', 'A')->count();
                $total = max(1, $rows->count());

                return [
                    'school_id' => (int) $schoolId,
                    'school_name' => (string) ($rows->first()->school_name ?? 'Unknown'),
                    'mean_mark' => (float) $mean,
                    'candidate_count' => $rows->pluck('candidate_id')->unique()->count(),
                    'total_subject_rows' => $rows->count(),
                    'a_grade_pct' => round(($gradeA / $total) * 100, 2),
                ];
            })
            ->values();

        $means = $schoolStats->pluck('mean_mark')->values();
        $overallMean = $means->avg() ?? 0.0;
        $variance = $means->count() > 0 ? $means->map(fn ($v) => pow($v - $overallMean, 2))->sum() / $means->count() : 0.0;
        $std = sqrt(max($variance, 0));

        $threshold = (float) ($filters['school_z_threshold'] ?? 2.0);
        $highAGradePctThreshold = (float) ($filters['high_a_threshold'] ?? 70.0);

        $rows = $schoolStats->map(function (array $row) use ($overallMean, $std, $threshold, $highAGradePctThreshold) {
            $z = $std > 0 ? (($row['mean_mark'] - $overallMean) / $std) : 0.0;
            $isFlagged = abs($z) >= $threshold || $row['a_grade_pct'] >= $highAGradePctThreshold;

            return [
                'school_id' => $row['school_id'],
                'school_name' => $row['school_name'],
                'candidate_count' => $row['candidate_count'],
                'mean_mark' => round($row['mean_mark'], 2),
                'z_score' => round($z, 2),
                'a_grade_pct' => $row['a_grade_pct'],
                'is_flagged' => $isFlagged,
                'flag_reason' => $row['a_grade_pct'] >= $highAGradePctThreshold
                    ? 'Unusual high A-grade distribution'
                    : (abs($z) >= $threshold ? 'Mean differs from scoped baseline' : null),
            ];
        });

        if (!empty($filters['q'])) {
            $term = mb_strtolower(trim((string) $filters['q']));
            $rows = $rows->filter(fn ($row) => str_contains(mb_strtolower($row['school_name']), $term))->values();
        }

        return $this->filterResolvedSchoolRows($rows->values(), $filters);
    }

    private function buildSubjectStats(Collection $subjectRows): array
    {
        return $subjectRows
            ->groupBy('subject_id')
            ->map(function (Collection $rows, $subjectId) {
                $marks = $rows->pluck('marks_obtained')->map(fn ($v) => (float) $v)->sort()->values();
                $count = $marks->count();
                $mean = $count > 0 ? $marks->avg() : 0.0;
                $variance = $count > 0 ? $marks->map(fn ($v) => pow($v - $mean, 2))->sum() / $count : 0.0;
                $std = sqrt(max($variance, 0));

                return [
                    'subject_id' => (int) $subjectId,
                    'subject_name' => (string) ($rows->first()->subject_name ?? 'Unknown'),
                    'mean' => round((float) $mean, 2),
                    'median' => round($this->percentile($marks, 50), 2),
                    'std_dev' => round((float) $std, 2),
                    'min' => round((float) ($marks->first() ?? 0), 2),
                    'max' => round((float) ($marks->last() ?? 0), 2),
                    'q1' => round($this->percentile($marks, 25), 2),
                    'q3' => round($this->percentile($marks, 75), 2),
                    'grade_counts' => $rows->groupBy('grade')->map->count(),
                    'total' => $count,
                ];
            })
            ->values()
            ->toArray();
    }

    private function scopedCandidateIds(User $user, array $filters): array
    {
        return $this->scopedCandidateResultsQuery($user, $filters)
            ->pluck('candidate_results.candidate_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function scopedSubjectMarks(array $candidateIds, array $filters): Collection
    {
        if (empty($candidateIds)) {
            return collect();
        }

        $select = [
            'subject_marks.candidate_id',
            'subject_marks.subject_id',
            'subject_marks.marks_obtained',
            'subject_marks.grade',
            'subject_marks.subject_status',
            'subjects.name as subject_name',
            'candidates.candidate_id as index_number',
            'candidates.full_name as candidate_name',
            'schools.id as school_id',
            'schools.name as school_name',
        ];

        if ($this->hasColumn('candidate_results', 'division')) {
            $select[] = 'candidate_results.division';
        } elseif ($this->hasColumn('final_grades', 'division')) {
            $select[] = 'final_grades.division';
        }

        if ($this->hasColumn('candidate_results', 'overall_grade')) {
            $select[] = 'candidate_results.overall_grade';
        } elseif ($this->hasColumn('final_grades', 'overall_grade')) {
            $select[] = 'final_grades.overall_grade';
        } elseif ($this->hasColumn('final_grades', 'grade')) {
            $select[] = 'final_grades.grade as overall_grade';
        }

        if ($this->hasColumn('candidate_results', 'gpa')) {
            $select[] = 'candidate_results.gpa';
        } elseif ($this->hasColumn('final_grades', 'gpa')) {
            $select[] = 'final_grades.gpa';
        }

        if ($this->hasColumn('candidate_results', 'competence_level')) {
            $select[] = 'candidate_results.competence_level';
        }

        return SubjectMarks::query()
            ->join('subjects', 'subjects.id', '=', 'subject_marks.subject_id')
            ->join('candidates', 'candidates.id', '=', 'subject_marks.candidate_id')
            ->join('schools', 'schools.id', '=', 'candidates.school_id')
            ->leftJoin('candidate_results', function ($join) use ($filters) {
                $join->on('candidate_results.candidate_id', '=', 'subject_marks.candidate_id')
                    ->where('candidate_results.exam_type_id', '=', $this->acseeExamTypeId())
                    ->where('candidate_results.year', '=', $this->resolveYear($filters));
            })
            ->leftJoin('final_grades', function ($join) use ($filters) {
                $join->on('final_grades.candidate_id', '=', 'subject_marks.candidate_id')
                    ->where('final_grades.exam_type_id', '=', $this->acseeExamTypeId())
                    ->where('final_grades.year', '=', $this->resolveYear($filters));
            })
            ->where('subject_marks.exam_type_id', $this->acseeExamTypeId())
            ->where('subject_marks.year', $this->resolveYear($filters))
            ->whereIn('subject_marks.candidate_id', $candidateIds)
            ->when(!empty($filters['subject_id']), fn ($q) => $q->where('subject_marks.subject_id', (int) $filters['subject_id']))
            ->select($select)
            ->whereNotNull('subject_marks.marks_obtained')
            ->get();
    }

    private function scopedCandidateResultsQuery(User $user, array $filters): Builder
    {
        $query = CandidateResult::query()
            ->join('candidates', 'candidates.id', '=', 'candidate_results.candidate_id')
            ->join('schools', 'schools.id', '=', 'candidates.school_id')
            ->where('candidate_results.exam_type_id', $this->acseeExamTypeId())
            ->where('candidate_results.year', $this->resolveYear($filters));

        $this->applyUserScope($query, $user);

        if (!empty($filters['region_id'])) {
            $query->where('schools.region_id', (int) $filters['region_id']);
        }
        if (!empty($filters['district_id'])) {
            $query->where('schools.district_id', (int) $filters['district_id']);
        }
        if (!empty($filters['council_id'])) {
            $query->where('schools.council_id', (int) $filters['council_id']);
        }
        if (!empty($filters['school_id'])) {
            $query->where('schools.id', (int) $filters['school_id']);
        }

        return $query;
    }

    private function resolveYear(array $filters): int
    {
        if (!empty($filters['year'])) {
            return (int) $filters['year'];
        }

        if (!empty($filters['exam_year_id'])) {
            $examYear = ExamYear::query()->find((int) $filters['exam_year_id']);
            if ($examYear) {
                return (int) $examYear->year_label;
            }
        }

        $active = ExamYear::query()->where('is_active', true)->first();
        return (int) ($active?->year_label ?? now()->year);
    }

    private function applyUserScope(Builder $query, User $user): void
    {
        $role = $user->role?->code;
        $scopeType = $user->scope?->scope_type;
        $scopeId = $user->scope?->scope_id;

        if (in_array($role, ['school_user', 'school_registrar'], true)) {
            $schoolId = $user->school_id ?? ($scopeType === 'school' ? $scopeId : null);
            if ($schoolId) {
                $query->where('schools.id', $schoolId);
            }
            return;
        }

        if (in_array($role, ['district_admin', 'district_supervisor', 'district_data_entry_officer'], true)
            && $scopeType === 'district' && $scopeId) {
            $query->where('schools.district_id', $scopeId);
            return;
        }

        if (in_array($role, ['regional_admin', 'regional_officer'], true)
            && $scopeType === 'region' && $scopeId) {
            $query->where('schools.region_id', $scopeId);
        }
    }

    private function acseeExamTypeId(): int
    {
        return (int) ExamType::query()->where('code', 'ACSEE')->value('id');
    }

    private function filterResolvedCandidateRows(Collection $rows, array $filters): Collection
    {
        if ($rows->isEmpty() || !$this->hasTable('final_outlier_resolutions')) {
            return $rows->values();
        }

        $keys = $rows->map(fn (array $r) => 'candidate:' . ((int) ($r['candidate_id'] ?? 0)) . ':' . ((int) ($r['subject_id'] ?? 0)))
            ->filter()
            ->values()
            ->all();
        if (empty($keys)) {
            return $rows->values();
        }

        $resolved = FinalOutlierResolution::query()
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('year', $this->resolveYear($filters))
            ->whereIn('resolution_key', $keys)
            ->pluck('resolution_key')
            ->map(fn ($k) => (string) $k)
            ->all();

        if (empty($resolved)) {
            return $rows->values();
        }

        $resolvedSet = array_flip($resolved);
        return $rows->filter(function (array $row) use ($resolvedSet) {
            $key = 'candidate:' . ((int) ($row['candidate_id'] ?? 0)) . ':' . ((int) ($row['subject_id'] ?? 0));
            return !isset($resolvedSet[$key]);
        })->values();
    }

    private function filterResolvedSchoolRows(Collection $rows, array $filters): Collection
    {
        if ($rows->isEmpty() || !$this->hasTable('final_outlier_resolutions')) {
            return $rows->values();
        }

        $keys = $rows->map(fn (array $r) => 'school:' . ((int) ($r['school_id'] ?? 0)))
            ->filter()
            ->values()
            ->all();
        if (empty($keys)) {
            return $rows->values();
        }

        $resolved = FinalOutlierResolution::query()
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('year', $this->resolveYear($filters))
            ->whereIn('resolution_key', $keys)
            ->pluck('resolution_key')
            ->map(fn ($k) => (string) $k)
            ->all();

        if (empty($resolved)) {
            return $rows->values();
        }

        $resolvedSet = array_flip($resolved);
        return $rows->filter(function (array $row) use ($resolvedSet) {
            $key = 'school:' . ((int) ($row['school_id'] ?? 0));
            return !isset($resolvedSet[$key]);
        })->values();
    }

    private function hasTable(string $table): bool
    {
        $key = 'table:' . $table;
        if (!array_key_exists($key, $this->columnCache)) {
            $this->columnCache[$key] = Schema::hasTable($table);
        }

        return (bool) $this->columnCache[$key];
    }

    private function percentile(Collection $sortedValues, float $percent): float
    {
        $count = $sortedValues->count();
        if ($count === 0) {
            return 0.0;
        }

        $rank = ($percent / 100) * ($count - 1);
        $low = (int) floor($rank);
        $high = (int) ceil($rank);
        if ($low === $high) {
            return (float) $sortedValues[$low];
        }

        $weight = $rank - $low;
        return ((1 - $weight) * (float) $sortedValues[$low]) + ($weight * (float) $sortedValues[$high]);
    }

    private function paginateCollection(Collection $collection, int $page, int $perPage): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (!array_key_exists($key, $this->columnCache)) {
            $this->columnCache[$key] = Schema::hasColumn($table, $column);
        }
        return $this->columnCache[$key];
    }
}
