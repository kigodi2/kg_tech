<?php

namespace App\Http\Controllers\Api\Results;

use App\Http\Controllers\Controller;
use App\Http\Requests\Results\GradingPreviewImpactRequest;
use App\Http\Requests\Results\SaveGradingConfigRequest;
use App\Http\Requests\Results\UpsertGradingConfigRequest;
use App\Http\Requests\Results\ValidateGradingSetupRequest;
use App\Models\Candidate;
use App\Models\CandidateResult;
use App\Models\CandidateSubjectSelection;
use App\Models\District;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\FinalGrade;
use App\Models\GovernanceAuditLog;
use App\Models\GradingProfile;
use App\Models\MarkImportBatch;
use App\Models\RawMark;
use App\Models\Region;
use App\Models\ReportExport;
use App\Models\ResultProcess;
use App\Models\ResultSnapshot;
use App\Models\ResultStatistic;
use App\Models\School;
use App\Models\Subject;
use App\Models\SubjectMarks;
use App\Models\SubjectPaperWeight;
use App\Models\User;
use App\Services\MarkEntry\MarkPromotionService;
use App\Services\Results\AcseeDistrictSchoolFpdfService;
use App\Services\Results\AcseeLiveMarkSetService;
use App\Services\Results\AcseeResultsFpdfService;
use App\Services\Results\NectaGradingService;
use App\Services\Results\ResultsExportService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AcseeLifecycleApiController extends Controller
{
    /**
     * NectaGradingService Contract Snapshot (DO NOT REMOVE)
     * - Public methods used by Results processing/preview:
     *   - calculateGrade(float $marks): string
     *   - getGradePoints(string $grade): int
     *   - calculateDivision(float $totalPoints): ?array
     *   - getGpaCompetence(float $gpa): array
     *   - getGradeBoundaries(): array
     *   - getGradePointsMapping(): array
     *   - getDivisionBoundaries(): array
     *   - getGpaCompetenceBoundaries(): array
     *   - getDefaultGpaSettings(): array
     *   - getAcseeRulesNotes(): array
     *   - resolveProfileConfig(?GradingProfile $profile): array
     *   - calculateGradeWithRules(float $marks, array $gradingRules): string
     *   - getGradePointsWithMapping(string $grade, array $gradePoints): float
     *   - calculateDivisionWithRules(float $totalPoints, array $divisionRules): array
     *   - getCompetenceByBasis(float $value, string $basis, array $competenceRules): array
     * - Config sections depended on by Results preview/management:
     *   - grading rules (boundaries, grade labels, points, principal/subsidiary flags)
     *   - GPA settings and grade-point mapping
     *   - division boundaries/rules
     *   - competence mapping rules
     * - Defaults:
     *   - If profile config is missing/partial, service defaults are surfaced from NectaGradingService constants.
     *
     * Discovery notes (from existing IRMS implementation):
     * - Grading configuration is stored in `grading_profiles` + `grading_rules`.
     * - Active grading scale is chosen by (`exam_type_id`, `exam_year_id`, `is_active = true`).
     * - Grade application logic is in service layer (`NectaGradingService` / related processing services),
     *   not on /results/acsee page load.
     * - Versioning/locking exists in `grading_profiles` (`version`, `is_locked`, `locked_at`, `locked_by_id`).
     * - Governance auditing exists via `governance_audit_logs` and is used for config mutations.
     * - Lifecycle page routing follows Mark Entry pattern (`?view=...`, pushState/popstate, single active view).
     */
    private array $columnCache = [];
    private array $paperWeightCache = [];

    public function __construct(
        private readonly NectaGradingService $gradingService,
        protected AcseeDistrictSchoolFpdfService $districtSchoolFpdfService,
        protected AcseeLiveMarkSetService $liveMarkSetService,
        protected ResultsExportService $resultsExportService,
        protected AcseeResultsFpdfService $acseeResultsFpdfService
    )
    {
    }

    public function summary(Request $request): JsonResponse
    {
        $this->extendExecutionWindow();
        $this->authorize('viewResults', CandidateResult::class);

        $cacheKey = $this->resultsCacheKey('summary', $request);
        $data = Cache::remember($cacheKey, now()->addSeconds(30), function () use ($request) {
            $query = $this->scopedCandidateResults($request);
            $base = (clone $query);

            $totalCandidates = (clone $base)->count();
            $publishedQuery = (clone $base);
            $this->applyPublishedFilter($publishedQuery);
            $publishedCandidates = $publishedQuery->count();
            $lockedCandidates = $this->hasColumn('candidate_results', 'is_locked')
                ? (clone $base)->where('is_locked', true)->count()
                : 0;

            $schoolCount = (clone $base)
                ->join('candidates', 'candidate_results.candidate_id', '=', 'candidates.id')
                ->distinct('candidates.school_id')
                ->count('candidates.school_id');

            // Mark Entry ready queue (submitted from entry lifecycle and promoted/ready for processing)
            $readyBatches = $this->scopedReadyBatches($request);
            $readyBatchCount = (clone $readyBatches)->count();
            $readySchoolCount = (clone $readyBatches)->whereNotNull('school_id')->distinct()->count('school_id');
            $readySubjectCount = (clone $readyBatches)->whereNotNull('subject_id')->distinct()->count('subject_id');
            $readyPairKeys = $this->liveMarkSetService->readyBatchPairKeys($readyBatches);
            $liveReadyMarks = $this->liveMarkSetService->currentLiveSubjectMarksCollection(
                new Request([
                    'exam_year_id' => $this->resolveExamYearId($request),
                    'region_id' => $request->input('region_id'),
                    'district_id' => $request->input('district_id'),
                    'school_id' => $request->input('school_id'),
                ]),
                $this->acseeExamTypeId(),
                $this->resolveYear($request),
                fn ($query, $scopeRequest, $candidateAlias, $schoolAlias) => $this->applyScopeFiltersToCandidateJoinQuery($query, $scopeRequest, $candidateAlias, $schoolAlias),
                false,
                null,
                $readyPairKeys
            );
            $readySubjectMarksRows = $liveReadyMarks->count();
            $readyCandidates = $liveReadyMarks->pluck('candidate_id')->unique()->count();

            $gradeColumn = $this->hasColumn('final_grades', 'overall_grade') ? 'overall_grade' : 'grade';
            $gradeDistribution = FinalGrade::query()
                ->where('exam_type_id', $this->acseeExamTypeId())
                ->where('year', $this->resolveYear($request))
                ->when($this->hasColumn('final_grades', 'snapshot_id'), function (Builder $q) use ($request) {
                    $activeSnapshot = $this->activeSnapshot($this->resolveExamYearId($request));
                    if ($activeSnapshot) {
                        $q->where('snapshot_id', $activeSnapshot->id);
                    }
                })
                ->when($this->hasColumn('final_grades', 'is_published'), fn (Builder $q) => $q->where('is_published', true))
                ->when($this->hasColumn('final_grades', 'candidate_id'), function (Builder $q) use ($base) {
                    $q->whereIn('candidate_id', (clone $base)->select('candidate_results.candidate_id'));
                })
                ->selectRaw("{$gradeColumn} as grade, COUNT(*) as total")
                ->groupBy($gradeColumn)
                ->orderBy($gradeColumn)
                ->get();

            return [
                'total_candidates' => $totalCandidates,
                'published_candidates' => $publishedCandidates,
                'locked_candidates' => $lockedCandidates,
                'schools_with_results' => $schoolCount,
                'ready_queue' => [
                    'exam_year' => $this->resolveYear($request),
                    'candidates' => $readyCandidates,
                    'subject_marks_rows' => $readySubjectMarksRows,
                    'schools' => $readySchoolCount,
                    'subjects' => $readySubjectCount,
                    'batches' => $readyBatchCount,
                ],
                'latest_processes' => ResultProcess::query()
                    ->where('exam_type_id', $this->acseeExamTypeId())
                    ->where('exam_year_id', $this->resolveExamYearId($request))
                    ->latest('created_at')
                    ->limit(5)
                    ->get(['id', 'type', 'status', 'processed_count', 'error_count', 'created_at']),
                'active_snapshot' => $this->activeSnapshot($this->resolveExamYearId($request)),
                'grade_distribution' => $gradeDistribution,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function reviewDashboard(Request $request): JsonResponse
    {
        $this->authorize('viewResults', CandidateResult::class);

        $query = $this->scopedCandidateResults($request);

        $divisionData = $this->hasColumn('candidate_results', 'division')
            ? (clone $query)
                ->selectRaw('division, COUNT(*) as total')
                ->groupBy('division')
                ->orderBy('division')
                ->get()
            : collect();

        $candidateIds = (clone $query)->pluck('candidate_results.candidate_id');

        $marksQuery = SubjectMarks::query()
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('year', $this->resolveYear($request))
            ->whereIn('candidate_id', $candidateIds);

        $outliers = (clone $marksQuery)
            ->where(function ($q) {
                $q->where('percentage', '<', 20)
                    ->orWhere('percentage', '>', 95);
            })
            ->count();

        $missingOrPartial = (clone $marksQuery)
            ->where(function ($q) {
                $q->whereNull('marks_obtained');
                if ($this->hasColumn('subject_marks', 'subject_status')) {
                    $q->orWhereIn('subject_status', ['ABS', 'INC']);
                }
            })
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'division_distribution' => $divisionData,
                'outliers_count' => $outliers,
                'missing_or_partial_count' => $missingOrPartial,
            ],
        ]);
    }

    public function reportsOverview(Request $request): JsonResponse
    {
        $this->extendExecutionWindow();
        $this->authorize('viewResults', CandidateResult::class);

        $examYearId = $request->integer('exam_year_id') ?: $this->resolveExamYearId($request);
        $mode = strtolower((string) $request->input('mode', 'published'));
        $scope = $this->resolveScopeFromRequest($request, true);

        $query = $this->scopedCandidateResults(new Request([
            'exam_year_id' => $examYearId,
            'mode' => $mode,
            'region_id' => $scope['scope_type'] === 'region' ? $scope['scope_id'] : null,
            'district_id' => $scope['scope_type'] === 'district' ? $scope['scope_id'] : null,
            'school_id' => $scope['scope_type'] === 'school' ? $scope['scope_id'] : null,
        ]));

        if ($mode === 'published') {
            $published = clone $query;
            $this->applyPublishedFilter($published);
            $query = $published;
        }

        $totalCandidates = (clone $query)->count();
        $schoolCount = (clone $query)
            ->join('candidates', 'candidate_results.candidate_id', '=', 'candidates.id')
            ->distinct('candidates.school_id')
            ->count('candidates.school_id');
        $divisionCounts = $this->hasColumn('candidate_results', 'division')
            ? (clone $query)->selectRaw('division, COUNT(*) as total')->groupBy('division')->pluck('total', 'division')->toArray()
            : [];
        $meanGpa = $this->hasColumn('candidate_results', 'gpa') ? round((float) ((clone $query)->avg('gpa') ?? 0), 2) : null;
        $meanAggt = $this->hasColumn('candidate_results', 'grade_points') ? round((float) ((clone $query)->avg('grade_points') ?? 0), 2) : null;

        $topSchoolsSelect = 'schools.id, schools.code, schools.name, districts.name as district_name, regions.name as region_name, COUNT(*) as candidates';
        $topSchoolsSelect .= $this->hasColumn('candidate_results', 'gpa')
            ? ', AVG(candidate_results.gpa) as mean_gpa'
            : ', NULL as mean_gpa';
        $topSchoolsSelect .= $this->hasColumn('candidate_results', 'grade_points')
            ? ', AVG(candidate_results.grade_points) as mean_aggt'
            : ', NULL as mean_aggt';

        $topSchools = CandidateResult::query()
            ->where('candidate_results.exam_type_id', $this->acseeExamTypeId())
            ->where('candidate_results.year', $this->resolveYear(new Request(['exam_year_id' => $examYearId])))
            ->join('candidates', 'candidate_results.candidate_id', '=', 'candidates.id')
            ->join('schools', 'candidates.school_id', '=', 'schools.id')
            ->leftJoin('districts', 'schools.district_id', '=', 'districts.id')
            ->leftJoin('regions', 'schools.region_id', '=', 'regions.id')
            ->when($mode === 'published', function ($q) {
                if ($this->hasColumn('candidate_results', 'is_published')) {
                    $q->where('candidate_results.is_published', true);
                } elseif ($this->hasColumn('candidate_results', 'status')) {
                    $q->where('candidate_results.status', 'RELEASED');
                }
            })
            ->when($this->hasColumn('candidate_results', 'snapshot_id'), function ($q) use ($examYearId, $mode, $scope) {
                if ($mode === 'published') {
                    $snapshot = $this->activeSnapshot($examYearId, $scope['scope_type'], $scope['scope_id']);
                    if ($snapshot) {
                        $q->where('candidate_results.snapshot_id', $snapshot->id);
                    }
                }
            })
            ->when($scope['scope_type'] !== 'national', function ($q) use ($scope) {
                if ($scope['scope_type'] === 'region') {
                    $q->where('schools.region_id', $scope['scope_id']);
                } elseif ($scope['scope_type'] === 'district') {
                    $q->where('schools.district_id', $scope['scope_id']);
                } elseif ($scope['scope_type'] === 'school') {
                    $q->where('schools.id', $scope['scope_id']);
                }
            })
            ->selectRaw($topSchoolsSelect)
            ->groupBy('schools.id', 'schools.code', 'schools.name', 'districts.name', 'regions.name')
            ->orderByDesc('candidates')
            ->limit(10)
            ->get();

        $recentExports = ReportExport::query()
            ->with('user:id,name')
            ->when($examYearId, fn ($q) => $q->where('exam_year_id', $examYearId))
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (ReportExport $exp) => [
                'id' => $exp->id,
                'time' => $exp->created_at?->toDateTimeString(),
                'actor' => $exp->user?->name ?? 'System',
                'scope' => $exp->scope,
                'export_type' => $exp->export_type,
                'action' => $exp->action,
                'status' => $exp->status,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'mode' => $mode,
                'scope' => $scope,
                'summary' => [
                    'candidates' => $totalCandidates,
                    'schools' => $schoolCount,
                    'mean_gpa' => $meanGpa,
                    'mean_aggt' => $meanAggt,
                    'division_counts' => $divisionCounts,
                ],
                'top_schools' => $topSchools,
                'recent_exports' => $recentExports,
            ],
        ]);
    }

    public function exportsHistory(Request $request): JsonResponse
    {
        $this->authorize('viewResults', CandidateResult::class);
        $examYearId = $request->integer('exam_year_id') ?: $this->resolveExamYearId($request);
        $items = ReportExport::query()
            ->with('user:id,name')
            ->when($examYearId, fn ($q) => $q->where('exam_year_id', $examYearId))
            ->latest('id')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function exportsReadiness(Request $request): JsonResponse
    {
        $this->authorize('viewResults', CandidateResult::class);

        $validated = $request->validate([
            'exam_year_id' => ['required', 'integer', 'exists:exam_years,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
        ]);

        if (empty($validated['district_id'])) {
            return response()->json([
                'success' => true,
                'data' => [
                    'ready' => true,
                    'issues' => [],
                    'summary' => [
                        'district_id' => null,
                        'blocked_schools' => 0,
                    ],
                    'message' => 'Select a district to preview export readiness.',
                ],
            ]);
        }

        $examYear = ExamYear::query()->findOrFail((int) $validated['exam_year_id']);
        $issues = $this->districtExportReadinessIssues(
            (int) $validated['district_id'],
            $this->acseeExamTypeId(),
            (int) $examYear->year_label
        );

        return response()->json([
            'success' => true,
            'data' => [
                'ready' => empty($issues),
                'issues' => $issues,
                'summary' => [
                    'district_id' => (int) $validated['district_id'],
                    'blocked_schools' => count($issues),
                ],
                'message' => empty($issues)
                    ? 'All schools in the selected district are ready for district ZIP export.'
                    : $this->formatDistrictExportReadinessMessage($issues),
            ],
        ]);
    }

    public function exportDownload(Request $request): Response|JsonResponse
    {
        $this->extendExecutionWindow();
        @set_time_limit(0);
        $this->authorize('exportResults', CandidateResult::class);
        $validated = $request->validate([
            'exam_year_id' => ['required', 'integer', 'exists:exam_years,id'],
            'format' => ['required', 'in:csv,pdf'],
            'report_type' => ['required', 'in:candidate_results,school_summary,district_school_results'],
            'mode' => ['nullable', 'in:published,draft'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'school_id' => ['nullable', 'integer', 'exists:schools,id'],
        ]);

        if (
            $validated['report_type'] === 'district_school_results'
            && $validated['format'] !== 'pdf'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'District-wise centre results export is available as PDF only.',
            ], 422);
        }

        if (
            $validated['report_type'] === 'district_school_results'
            && empty($validated['district_id'])
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a specific district before downloading district-wise ZIP.',
            ], 422);
        }

        $examYear = ExamYear::query()->findOrFail((int) $validated['exam_year_id']);
        $mode = strtolower((string) ($validated['mode'] ?? 'draft'));
        $scope = $this->resolveScopeFromRequest($request, true);

        $scopedRequest = new Request([
            'exam_year_id' => $examYear->id,
            'mode' => $mode,
            'region_id' => $validated['region_id'] ?? ($scope['scope_type'] === 'region' ? $scope['scope_id'] : null),
            'district_id' => $validated['district_id'] ?? ($scope['scope_type'] === 'district' ? $scope['scope_id'] : null),
            'school_id' => $validated['school_id'] ?? ($scope['scope_type'] === 'school' ? $scope['scope_id'] : null),
        ]);
        $query = $this->scopedCandidateResults(
            $scopedRequest,
            null,
            $validated['report_type'] === 'district_school_results' && $mode === 'draft'
        );

        if ($mode === 'published') {
            $published = clone $query;
            $this->applyPublishedFilter($published);
            $query = $published;
        }

        $examTypeId = $this->acseeExamTypeId();
        $yearValue = (int) $examYear->year_label;

        $rowsQuery = (clone $query)
            ->with([
                'candidate:id,school_id,candidate_id,full_name,gender,combination',
                'candidate.subjectSelections' => fn ($q) => $q
                    ->select(['id', 'candidate_id', 'subject_id', 'exam_type_id', 'exam_year_id', 'year', 'is_active', 'is_principal'])
                    ->where('exam_type_id', $examTypeId)
                    ->where(function ($sq) use ($examYear, $yearValue) {
                        $sq->where('exam_year_id', $examYear->id)
                           ->orWhere('year', $yearValue);
                    })
                    ->where('is_active', true)
                    ->where('is_principal', true)
                    ->with('subject:id,code,name'),
                'candidate.school:id,name,code,district_id,region_id',
                'candidate.school.district:id,name',
                'candidate.school.region:id,name',
                'subjectMarks' => fn ($q) => $q
                    ->select(['id', 'candidate_id', 'subject_id', 'exam_type_id', 'year', 'marks_obtained', 'paper_1', 'paper_2', 'paper_3', 'grade', 'subject_status'])
                    ->where('exam_type_id', $examTypeId)
                    ->where('year', $yearValue),
                'subjectMarks.subject:id,code,name,written_papers,has_practical',
            ]);

        if ($validated['report_type'] !== 'district_school_results') {
            $rowsQuery->limit(50000);
        }

        $rows = $rowsQuery->get();

        if ($rows->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No result rows available for selected filters.',
            ], 422);
        }

        // Candidate-level totals/grades live in candidate_results; AGGT/GPA/division are on final_grades in this schema.
        $candidateIds = $rows->pluck('candidate_id')->filter()->unique()->values();
        $finalByCandidate = $this->finalGradesForExport($scopedRequest, $candidateIds, $examTypeId, $yearValue, $mode)
            ->get(['candidate_id', 'grade', 'final_percentage', 'gpa', 'division', 'grading_breakdown'])
            ->keyBy('candidate_id');

        $hasResultStatus = $this->hasColumn('candidate_results', 'result_status');
        $resolveResultStatus = function ($row, $final) use ($hasResultStatus) {
            if ($hasResultStatus) {
                $stored = strtoupper(trim((string) ($row->result_status ?? '')));
                if (in_array($stored, ['COMPLETE', 'INC', 'ABS'], true)) {
                    return $stored;
                }
            }

            $decoded = is_array($final?->grading_breakdown ?? null)
                ? $final->grading_breakdown
                : json_decode((string) ($final?->grading_breakdown ?? ''), true);
            $irregular = strtoupper(trim((string) data_get($decoded, 'irregular_overall_status', '')));

            if (in_array($irregular, ['ABS', 'X'], true)) {
                return 'ABS';
            }
            if ($irregular !== '') {
                return 'INC';
            }

            return 'COMPLETE';
        };

        $rows->each(function ($row) use ($finalByCandidate, $hasResultStatus, $resolveResultStatus) {
            $final = $finalByCandidate->get($row->candidate_id);
            if (!$final) {
                return;
            }

            if (empty($row->overall_grade)) {
                $row->overall_grade = $final->grade;
            }
            if (empty($row->total_percentage)) {
                $row->total_percentage = $final->final_percentage;
            }

            $aggt = data_get($final->grading_breakdown, 'aggt_points');
            if ($aggt !== null && (!isset($row->grade_points) || $row->grade_points === null || $row->grade_points === '')) {
                $row->grade_points = $aggt;
            }
            if (!isset($row->division) || $row->division === null || $row->division === '') {
                $row->division = $final->division;
            }
            if (!isset($row->gpa) || $row->gpa === null || $row->gpa === '') {
                $row->gpa = $final->gpa;
            }
            if ($hasResultStatus) {
                $row->result_status = $resolveResultStatus($row, $final);
            }
        });

        $filenameBase = sprintf(
            'acsee-%s-%s-%s',
            $validated['report_type'],
            $examYear->year_label,
            now()->format('Ymd-His')
        );

        ReportExport::log(
            'acsee_results',
            'results_export_' . $validated['report_type'] . '_' . $validated['format'],
            [
                'exam_year_id' => $examYear->id,
                'exam_year' => $examYear->year_label,
                'report_type' => $validated['report_type'],
                'format' => $validated['format'],
                'mode' => $mode,
                'rows' => $rows->count(),
                'filters' => [
                    'region_id' => $validated['region_id'] ?? null,
                    'district_id' => $validated['district_id'] ?? null,
                    'school_id' => $validated['school_id'] ?? null,
                ],
            ],
            $scope['scope_type']
        );

        if ($validated['format'] === 'pdf') {
            if ($validated['report_type'] === 'district_school_results') {
                try {
                $region = !empty($validated['region_id'])
                    ? Region::query()->find((int) $validated['region_id'])
                    : null;
                $district = !empty($validated['district_id'])
                    ? District::query()->find((int) $validated['district_id'])
                    : null;
                $exportSchoolIds = $rows
                    ->pluck('candidate.school_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();
                $readinessIssues = $this->districtExportReadinessIssues(
                    (int) $validated['district_id'],
                    $examTypeId,
                    $yearValue,
                    $exportSchoolIds
                );
                if (!empty($readinessIssues)) {
                    return response()->json([
                        'success' => false,
                        'message' => $this->formatDistrictExportReadinessMessage($readinessIssues),
                    ], 422);
                }
                $schools = $rows
                    ->groupBy(fn ($r) => $this->exportCentreKey($r))
                    ->filter(fn ($group, $centreKey) => filled($centreKey))
                    ->map(function ($schoolRows, $centreKey) use ($region, $district) {
                        return $this->attachExportCentreFallback($schoolRows, (string) $centreKey, $region, $district);
                    });

                if ($schools->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No centre results found for selected district filters.',
                    ], 422);
                }

                $tempDir = storage_path('app/tmp/acsee-district-exports-' . uniqid());
                if (!is_dir($tempDir)) {
                    @mkdir($tempDir, 0755, true);
                }

                $districtLabel = strtoupper((string) ($district?->name ?? 'DISTRICT'));
                $districtLabel = preg_replace('/[\/\\\\:*?"<>|]+/', ' ', $districtLabel);
                $districtLabel = trim(preg_replace('/\s+/', ' ', $districtLabel));
                $yearLabel = trim((string) ($examYear->year_label ?? date('Y')));
                $zipFilename = sprintf('%s_%s.zip', $districtLabel !== '' ? $districtLabel : 'DISTRICT', $yearLabel);
                $zipPath = $tempDir . DIRECTORY_SEPARATOR . $zipFilename;
                $zip = new \ZipArchive();
                if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unable to create ZIP file for district bulk export.',
                    ], 500);
                }

                foreach ($schools as $schoolRows) {
                    $school = $schoolRows->first()?->candidate?->school;
                    $schoolCode = (string) ($school?->code ?: 'SCHOOL');
                    $schoolName = (string) ($school?->name ?: 'UNKNOWN');
                    $centerDescriptor = $this->districtSchoolFpdfService->centerDescriptor($schoolCode);
                    $safeSchoolName = preg_replace('/[^A-Za-z0-9_-]+/', '_', strtoupper($schoolName));
                    $safeCenterDescriptor = preg_replace('/[^A-Za-z0-9_-]+/', '_', strtoupper($centerDescriptor));
                    $pdfFilename = sprintf(
                        '%s_%s_%s.pdf',
                        strtoupper($schoolCode),
                        trim($safeSchoolName, '_'),
                        trim($safeCenterDescriptor, '_')
                    );
                    $pdfPath = $tempDir . DIRECTORY_SEPARATOR . $pdfFilename;

                    $this->districtSchoolFpdfService->generateSchoolPdf(
                        $schoolRows->values(),
                        $pdfPath,
                        (string) $examYear->year_label,
                        $region,
                        $district,
                        (string) (auth()->user()->name ?? 'System')
                    );
                    $zip->addFile($pdfPath, $pdfFilename);
                }

                $zip->close();

                register_shutdown_function(function () use ($tempDir) {
                    if (!is_dir($tempDir)) {
                        return;
                    }
                    foreach (glob($tempDir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
                        @unlink($file);
                    }
                    @rmdir($tempDir);
                });

                return response()->download($zipPath, $zipFilename, [
                    'Content-Type' => 'application/zip',
                ])->deleteFileAfterSend(true);
                } catch (Throwable $e) {
                    Log::error('District school ZIP export failed', [
                        'exam_year_id' => $examYear->id,
                        'region_id' => $validated['region_id'] ?? null,
                        'district_id' => $validated['district_id'] ?? null,
                        'user_id' => auth()->id(),
                        'error' => $e->getMessage(),
                    ]);

                    $message = str_contains(strtolower($e->getMessage()), 'gd extension')
                        ? 'Export failed: PDF image rendering requires PHP GD extension on this server.'
                        : 'Export failed while generating district ZIP.';

                    return response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 500);
                }
            }

            $tempPath = tempnam(sys_get_temp_dir(), 'acsee_lifecycle_results_');
            if ($tempPath === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to prepare PDF export file.',
                ], 500);
            }
            $pdfPath = $tempPath . '.pdf';
            @rename($tempPath, $pdfPath);

            $this->acseeResultsFpdfService->generate(
                $this->resultsExportService->buildSchoolSections($rows),
                (int) $examYear->year_label,
                null,
                now(),
                auth()->user()->name,
                $pdfPath
            );

            return response()->download($pdfPath, $filenameBase . '.pdf')->deleteFileAfterSend(true);
        }

        return response()->streamDownload(function () use ($rows, $validated, $examYear) {
            $out = fopen('php://output', 'w');
            fputs($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            if ($validated['report_type'] === 'school_summary') {
                fputcsv($out, ['School Code', 'School', 'District', 'Region', 'Candidates', 'Mean GPA', 'Mean AGGT', 'Div I', 'Div II', 'Div III', 'Div IV', 'Div 0']);
                $grouped = $rows->groupBy(fn ($r) => $r->candidate?->school_id);
                foreach ($grouped as $items) {
                    $first = $items->first();
                    $school = $first?->candidate?->school;
                    $completeItems = $items->filter(function ($item) use ($finalByCandidate, $resolveResultStatus) {
                        return $resolveResultStatus($item, $finalByCandidate->get($item->candidate_id)) === 'COMPLETE';
                    })->values();
                    $div = $completeItems->groupBy('division')->map->count();
                    fputcsv($out, [
                        $school?->code,
                        $school?->name,
                        $school?->district?->name,
                        $school?->region?->name,
                        $items->count(),
                        round((float) $completeItems->avg('gpa'), 2),
                        round((float) $completeItems->avg('grade_points'), 2),
                        (int) ($div['1'] ?? 0),
                        (int) ($div['2'] ?? 0),
                        (int) ($div['3'] ?? 0),
                        (int) ($div['4'] ?? 0),
                        (int) ($div['0'] ?? 0),
                    ]);
                }
            } else {
                $subjects = Subject::query()
                    ->where('exam_type_id', $this->acseeExamTypeId())
                    ->orderBy('code')
                    ->get(['id', 'code']);
                $header = ['Index Number', 'Candidate Name', 'Sex', 'School', 'District', 'Region'];
                foreach ($subjects as $subject) {
                    $header[] = 'Grade-' . $subject->code;
                }
                $header = array_merge($header, ['Overall Grade', 'AGGT', 'Division', 'GPA', 'Status', 'Year']);
                fputcsv($out, $header);

                foreach ($rows as $row) {
                    $line = [
                        $row->candidate?->candidate_id,
                        $row->candidate?->full_name,
                        $row->candidate?->gender,
                        $row->candidate?->school?->name,
                        $row->candidate?->school?->district?->name,
                        $row->candidate?->school?->region?->name,
                    ];
                    $marks = $row->subjectMarks->keyBy('subject_id');
                    foreach ($subjects as $subject) {
                        $line[] = $marks->get($subject->id)?->grade ?? ($marks->get($subject->id)?->subject_status ?? '');
                    }
                    $line = array_merge($line, [
                        $row->overall_grade,
                        $row->grade_points,
                        $row->division,
                        $row->gpa,
                        $resolveResultStatus($row, $finalByCandidate->get($row->candidate_id)),
                        $examYear->year_label,
                    ]);
                    fputcsv($out, $line);
                }
            }
            fclose($out);
        }, $filenameBase . '.csv', [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }

    public function readySchools(Request $request): JsonResponse
    {
        $this->extendExecutionWindow();
        $this->authorize('viewResults', CandidateResult::class);

        $perPage = max(5, min(100, (int) $request->input('per_page', 50)));
        $search = trim((string) $request->input('search', ''));
        $cacheKey = $this->resultsCacheKey('ready-schools', $request, ['per_page' => $perPage, 'search' => $search]);
        $data = Cache::remember($cacheKey, now()->addSeconds(30), function () use ($request, $perPage, $search) {
            $readyBatches = $this->scopedReadyBatches($request);

            $query = School::query()
                ->join('mark_import_batches', 'schools.id', '=', 'mark_import_batches.school_id')
                ->leftJoin('districts', 'schools.district_id', '=', 'districts.id')
                ->leftJoin('regions', 'schools.region_id', '=', 'regions.id')
                ->leftJoin('raw_marks', function ($join) {
                    $join->on('raw_marks.mark_import_batch_id', '=', 'mark_import_batches.id')
                        ->whereNotNull('raw_marks.candidate_id')
                        ->where('raw_marks.has_errors', false);
                })
                ->whereIn('mark_import_batches.id', (clone $readyBatches)->select('id'))
                ->when($search !== '', function (Builder $q) use ($search) {
                    $q->where(function (Builder $w) use ($search) {
                        $w->where('schools.code', 'like', "%{$search}%")
                            ->orWhere('schools.name', 'like', "%{$search}%");
                    });
                })
                ->groupBy(
                    'schools.id',
                    'schools.code',
                    'schools.name',
                    'schools.ownership',
                    'districts.name',
                    'regions.name'
                )
                ->selectRaw('
                    schools.id,
                    schools.code,
                    schools.name,
                    schools.ownership,
                    districts.name as district_name,
                    regions.name as region_name,
                    COUNT(DISTINCT raw_marks.candidate_id) as candidates_ready,
                    COUNT(DISTINCT mark_import_batches.subject_id) as subjects_ready,
                    COUNT(DISTINCT mark_import_batches.id) as batches_ready,
                    MAX(mark_import_batches.updated_at) as last_batch_at
                ')
                ->orderByDesc('candidates_ready')
                ->orderBy('schools.name');

            return $query->paginate($perPage)->toArray();
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    private function exportCentreKey(object $row): ?string
    {
        $schoolId = $row->candidate?->school_id;
        if (!empty($schoolId)) {
            return 'school:' . (int) $schoolId;
        }

        $schoolCode = trim((string) ($row->candidate?->school?->code ?? ''));
        if ($schoolCode !== '') {
            return 'code:' . strtoupper($schoolCode);
        }

        $candidateId = strtoupper(trim((string) ($row->candidate?->candidate_id ?? '')));
        if (preg_match('/^([SP][A-Z0-9]+)/', $candidateId, $matches)) {
            return 'code:' . $matches[1];
        }

        return null;
    }

    private function attachExportCentreFallback(Collection $schoolRows, string $centreKey, ?Region $region, ?District $district): Collection
    {
        $firstSchool = $schoolRows->first()?->candidate?->school;
        if ($firstSchool) {
            return $schoolRows->values();
        }

        $centreCode = str_starts_with($centreKey, 'code:')
            ? substr($centreKey, 5)
            : '';

        $centreName = $this->districtSchoolFpdfService->centerDescriptor($centreCode !== '' ? $centreCode : null);
        $syntheticSchool = new School();
        $syntheticSchool->forceFill([
            'code' => $centreCode !== '' ? $centreCode : 'UNKNOWN',
            'name' => $centreName,
            'district_id' => $district?->id,
            'region_id' => $region?->id,
        ]);
        if ($district) {
            $syntheticSchool->setRelation('district', $district);
        }
        if ($region) {
            $syntheticSchool->setRelation('region', $region);
        }

        return $schoolRows->map(function ($row) use ($syntheticSchool) {
            if ($row->candidate) {
                $row->candidate->setRelation('school', $syntheticSchool);
            }

            return $row;
        })->values();
    }

    private function districtExportReadinessIssues(int $districtId, int $examTypeId, int $yearValue, array $schoolIds = []): array
    {
        $schoolsQuery = School::query()
            ->where('district_id', $districtId)
            ->whereHas('candidates');

        if (!empty($schoolIds)) {
            $schoolsQuery->whereIn('id', $schoolIds);
        } else {
            $schoolsQuery->whereHas('candidates.candidateResults', function (Builder $query) use ($examTypeId, $yearValue) {
                $query->where('exam_type_id', $examTypeId)
                    ->where('year', $yearValue);
            });
        }

        $schools = $schoolsQuery->get(['id', 'code', 'name']);

        $issues = [];

        foreach ($schools as $school) {
            $candidateIds = Candidate::query()
                ->where('school_id', $school->id)
                ->pluck('id');

            if ($candidateIds->isEmpty()) {
                continue;
            }

            $selectionCount = CandidateSubjectSelection::query()
                ->whereIn('candidate_id', $candidateIds)
                ->where('exam_type_id', $examTypeId)
                ->where(function ($q) use ($yearValue) {
                    $q->where('year', $yearValue)
                      ->orWhereNull('year');
                })
                ->count();

            $batchCount = MarkImportBatch::query()
                ->where('school_id', $school->id)
                ->where('exam_type_id', $examTypeId)
                ->where('exam_year', $yearValue)
                ->count();

            $rawMarksCount = DB::table('raw_marks')
                ->join('candidates', 'candidates.id', '=', 'raw_marks.candidate_id')
                ->where('candidates.school_id', $school->id)
                ->count();

            $subjectMarksCount = SubjectMarks::query()
                ->whereIn('candidate_id', $candidateIds)
                ->where('exam_type_id', $examTypeId)
                ->where('year', $yearValue)
                ->count();

            $candidateResultsCount = CandidateResult::query()
                ->whereIn('candidate_id', $candidateIds)
                ->where('exam_type_id', $examTypeId)
                ->where('year', $yearValue)
                ->count();

            $finalGradesCount = FinalGrade::query()
                ->whereIn('candidate_id', $candidateIds)
                ->where('exam_type_id', $examTypeId)
                ->where('year', $yearValue)
                ->count();

            $reason = null;
            if ($selectionCount === 0) {
                $reason = 'no subject selections';
            } elseif ($batchCount === 0) {
                $reason = 'no imported mark batches';
            } elseif ($rawMarksCount === 0) {
                $reason = 'no raw marks';
            } elseif ($subjectMarksCount === 0) {
                $reason = 'no promoted subject marks';
            } elseif ($candidateResultsCount === 0 && $finalGradesCount === 0) {
                $reason = 'results not computed';
            }

            if ($reason !== null) {
                $issues[] = [
                    'code' => (string) $school->code,
                    'name' => (string) $school->name,
                    'reason' => $reason,
                ];
            }
        }

        return $issues;
    }

    private function formatDistrictExportReadinessMessage(array $issues): string
    {
        $visible = collect($issues)
            ->take(8)
            ->map(fn (array $issue) => sprintf('%s %s (%s)', $issue['code'], $issue['name'], $issue['reason']))
            ->implode('; ');

        $extra = count($issues) > 8
            ? ' +' . (count($issues) - 8) . ' more'
            : '';

        return 'District export blocked. Some centres are not ready: ' . $visible . $extra . '. Complete mark promotion/results computation, then retry.';
    }

    public function computeValidateReadiness(Request $request): JsonResponse
    {
        $this->extendExecutionWindow();
        $this->authorize('publishLock', CandidateResult::class);

        $examYearId = $request->integer('exam_year_id') ?: $this->resolveExamYearId($request);
        $examYear = $examYearId ? ExamYear::query()->find($examYearId) : null;
        if (!$examYear) {
            return response()->json([
                'success' => false,
                'message' => 'Exam year not found.',
            ], 422);
        }

        $readyBatches = $this->scopedReadyBatches(new Request([
            'exam_year_id' => $examYear->id,
            'region_id' => $request->input('region_id'),
            'district_id' => $request->input('district_id'),
            'school_id' => $request->input('school_id'),
        ]), (int) $examYear->year_label);

        $readyBatchCount = (clone $readyBatches)->count();
        $readySchoolCount = (clone $readyBatches)->whereNotNull('school_id')->distinct()->count('school_id');
        $readySubjectCount = (clone $readyBatches)->whereNotNull('subject_id')->distinct()->count('subject_id');
        $readyPairKeys = $this->liveMarkSetService->readyBatchPairKeys($readyBatches);
        $liveReadyMarks = $this->liveMarkSetService->currentLiveSubjectMarksCollection(
            new Request([
                'exam_year_id' => $examYear->id,
                'region_id' => $request->input('region_id'),
                'district_id' => $request->input('district_id'),
                'school_id' => $request->input('school_id'),
            ]),
            $this->acseeExamTypeId(),
            (int) $examYear->year_label,
            fn ($query, $scopeRequest, $candidateAlias, $schoolAlias) => $this->applyScopeFiltersToCandidateJoinQuery($query, $scopeRequest, $candidateAlias, $schoolAlias),
            false,
            null,
            $readyPairKeys
        );

        $readySubjectMarksRows = $liveReadyMarks->count();
        $readyCandidates = $liveReadyMarks->pluck('candidate_id')->unique()->count();

        $activeProfile = $this->activeProfile($examYear->id);
        $latestProfile = GradingProfile::query()
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('exam_year_id', $examYear->id)
            ->latest('version')
            ->first();
        $profile = $activeProfile ?: $latestProfile;

        $resolved = $this->normalizeToNectaEffectiveConfig(
            $this->gradingService->resolveProfileConfig($profile)
        );
        $validation = $this->validateFullConfig($resolved);

        // Auto-close stale pending/in-progress runs so readiness reflects actionable state.
        $staleCutoff = now()->subMinutes(10);
        ResultProcess::query()
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('exam_year_id', $examYear->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->where('created_at', '<=', $staleCutoff)
            ->update([
                'status' => 'failed',
                'completed_at' => now(),
                'finished_at' => now(),
                'error_message' => 'Auto-closed stale compute run during readiness check.',
            ]);

        $inProgress = ResultProcess::query()
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('exam_year_id', $examYear->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        $candidateResultsCount = CandidateResult::query()
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('year', (int) $examYear->year_label)
            ->count();

        $finalGradesCount = FinalGrade::query()
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('year', (int) $examYear->year_label)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'exam_year' => [
                    'id' => $examYear->id,
                    'label' => $examYear->year_label,
                ],
                'ready_queue' => [
                    'candidates' => $readyCandidates,
                    'subject_marks_rows' => $readySubjectMarksRows,
                    'schools' => $readySchoolCount,
                    'subjects' => $readySubjectCount,
                    'batches' => $readyBatchCount,
                ],
                'grading' => [
                    'profile_id' => $profile?->id,
                    'profile_name' => $profile?->name,
                    'is_active' => (bool) ($profile?->is_active ?? false),
                    'is_locked' => (bool) ($profile?->is_locked ?? false),
                    'errors' => $validation['errors'],
                    'warnings' => $validation['warnings'],
                ],
                'existing_results' => [
                    'candidate_results' => $candidateResultsCount,
                    'final_grades' => $finalGradesCount,
                ],
                'processing' => [
                    'in_progress_runs' => $inProgress,
                    'active_snapshot' => $this->activeSnapshot($examYear->id)?->only(['id', 'version', 'published_at']),
                    'latest_runs' => ResultProcess::query()
                        ->where('exam_type_id', $this->acseeExamTypeId())
                        ->where('exam_year_id', $examYear->id)
                        ->latest('created_at')
                        ->limit(10)
                        ->get(['id', 'type', 'status', 'total_candidates', 'processed_count', 'error_count', 'created_at', 'completed_at', 'stats', 'metadata'])
                        ->map(fn (ResultProcess $run) => array_merge(
                            $run->only(['id', 'type', 'status', 'total_candidates', 'processed_count', 'error_count', 'created_at', 'completed_at']),
                            $this->processSkipSummary($run)
                        ))
                        ->values(),
                ],
                'checks' => [
                    'has_ready_marks' => $readyCandidates > 0,
                    'has_grading_profile' => (bool) $profile,
                    'grading_is_valid' => empty($validation['errors']),
                    'no_running_process' => $inProgress === 0,
                ],
                'can_run' => $readyCandidates > 0 && empty($validation['errors']) && $inProgress === 0,
            ],
        ]);
    }

    public function computeValidateRun(Request $request, MarkPromotionService $promotionService): JsonResponse
    {
        $this->extendExecutionWindow(900);
        $this->authorize('publishLock', CandidateResult::class);

        $validated = $request->validate([
            'exam_year_id' => ['required', 'integer', 'exists:exam_years,id'],
            'run_type' => ['required', 'in:draft,final'],
            'promote_marks' => ['nullable', 'boolean'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'school_id' => ['nullable', 'integer', 'exists:schools,id'],
        ]);

        $examYear = ExamYear::query()->findOrFail((int) $validated['exam_year_id']);
        $examTypeId = $this->acseeExamTypeId();
        $runType = (string) $validated['run_type'];
        $promoteMarks = (bool) ($validated['promote_marks'] ?? true);

        $activeProfile = $this->activeProfile($examYear->id);
        $latestProfile = GradingProfile::query()
            ->where('exam_type_id', $examTypeId)
            ->where('exam_year_id', $examYear->id)
            ->latest('version')
            ->first();
        $profile = $activeProfile ?: $latestProfile;
        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'No grading configuration found for the selected exam year.',
            ], 422);
        }

        $resolved = $this->normalizeToNectaEffectiveConfig(
            $this->gradingService->resolveProfileConfig($profile)
        );
        $analysis = $this->validateFullConfig($resolved);
        if (!empty($analysis['errors'])) {
            return response()->json([
                'success' => false,
                'message' => 'Grading configuration is invalid. Fix grading setup before compute.',
                'data' => $analysis,
            ], 422);
        }

        // Auto-close stale pending/in-progress runs so one broken request does not block new compute.
        $staleCutoff = now()->subMinutes(10);
        ResultProcess::query()
            ->where('exam_type_id', $examTypeId)
            ->where('exam_year_id', $examYear->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->where('created_at', '<=', $staleCutoff)
            ->update([
                'status' => 'failed',
                'completed_at' => now(),
                'finished_at' => now(),
                'error_message' => 'Auto-closed stale compute run before starting a new one.',
            ]);

        $running = ResultProcess::query()
            ->where('exam_type_id', $examTypeId)
            ->where('exam_year_id', $examYear->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists();
        if ($running) {
            return response()->json([
                'success' => false,
                'message' => 'Another compute/validate run is already in progress.',
            ], 409);
        }

        $scopeRequest = new Request([
            'exam_year_id' => $examYear->id,
            'region_id' => $validated['region_id'] ?? null,
            'district_id' => $validated['district_id'] ?? null,
            'school_id' => $validated['school_id'] ?? null,
        ]);
        $scope = $this->resolveScopeFromRequest($scopeRequest, true);
        $readyBatches = $this->scopedReadyBatches($scopeRequest, (int) $examYear->year_label);
        $readyBatchIds = (clone $readyBatches)->pluck('id');
        if ($readyBatchIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No approved/locked mark-entry batches found in the selected scope.',
            ], 422);
        }

        $readyPairKeys = $this->liveMarkSetService->readyBatchPairKeys($readyBatches);
        $liveReadyMarks = $this->liveMarkSetService->currentLiveSubjectMarksCollection(
            $scopeRequest,
            $examTypeId,
            (int) $examYear->year_label,
            fn ($query, $scopeRequest, $candidateAlias, $schoolAlias) => $this->applyScopeFiltersToCandidateJoinQuery($query, $scopeRequest, $candidateAlias, $schoolAlias),
            true,
            null,
            $readyPairKeys
        );
        $candidateIds = $liveReadyMarks->pluck('candidate_id')->map(fn ($id) => (int) $id)->unique()->values();
        if ($candidateIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No promoted candidates found in ready mark-entry batches.',
            ], 422);
        }

        $process = ResultProcess::query()->create([
            'exam_type_id' => $examTypeId,
            'exam_year_id' => $examYear->id,
            'type' => $runType,
            'status' => 'in_progress',
            'config_fingerprint' => hash('sha256', json_encode($resolved)),
            'input_fingerprint' => hash('sha256', json_encode([
                'batch_ids' => $readyBatchIds->values()->all(),
                'ready_batch_count' => $readyBatchIds->count(),
                'candidate_count' => $candidateIds->count(),
            ])),
            'scope_type' => !empty($validated['school_id']) ? 'school' : (!empty($validated['district_id']) ? 'district' : (!empty($validated['region_id']) ? 'region' : 'national')),
            'scope_id' => $validated['school_id'] ?? $validated['district_id'] ?? $validated['region_id'] ?? null,
            'user_id' => auth()->id(),
            'total_candidates' => (int) $candidateIds->count(),
            'processed_count' => 0,
            'error_count' => 0,
            'processed_at' => now(),
            'started_at' => now(),
            'metadata' => [
                'mode' => 'compute_validate',
                'profile_id' => $profile->id,
                'scope' => [
                    'region_id' => $validated['region_id'] ?? null,
                    'district_id' => $validated['district_id'] ?? null,
                    'school_id' => $validated['school_id'] ?? null,
                ],
                'promote_marks' => $promoteMarks,
                'ready_batches' => $readyBatchIds->count(),
            ],
            'stats' => [
                'ready_batches' => $readyBatchIds->count(),
                'ready_candidates' => $candidateIds->count(),
            ],
        ]);

        $promotionTotals = [
            'promoted' => 0,
            'skipped' => 0,
            'failed' => 0,
            'inc' => 0,
            'absent' => 0,
            'total' => 0,
        ];
        $errors = [];

        try {
            if ($promoteMarks) {
                $batches = MarkImportBatch::query()->whereIn('id', $readyBatchIds)->get();
                foreach ($batches as $batch) {
                    $promotion = $promotionService->promote($batch);
                    foreach (array_keys($promotionTotals) as $key) {
                        $promotionTotals[$key] += (int) ($promotion[$key] ?? 0);
                    }
                }
            }

            $marks = $liveReadyMarks->whereIn('candidate_id', $candidateIds->all())->values();

            $marksByCandidate = $marks->groupBy('candidate_id');
            if ($marksByCandidate->isEmpty()) {
                throw new \RuntimeException('No promoted subject marks found for compute.');
            }
            $candidatesWithMarks = $marksByCandidate->keys()->map(fn ($id) => (int) $id);
            $skippedByReason = [
                'no_promoted_subject_marks' => (int) $candidateIds->diff($candidatesWithMarks)->count(),
                'no_graded_subjects' => 0,
                'candidate_compute_errors' => 0,
            ];

            $principalSelections = CandidateSubjectSelection::query()
                ->whereIn('candidate_id', $candidateIds)
                ->where('exam_type_id', $examTypeId)
                ->where(function ($q) use ($examYear) {
                    $q->where('exam_year_id', $examYear->id)
                        ->orWhere('year', (int) $examYear->year_label);
                })
                ->where('is_active', true)
                ->where('is_principal', true)
                ->get(['candidate_id', 'subject_id'])
                ->groupBy('candidate_id')
                ->map(fn ($rows) => $rows->pluck('subject_id')->map(fn ($id) => (int) $id)->unique()->values()->all());

            // ACSEE compute policy: persist GPA at 4 decimal places.
            $roundingDecimals = 4;
            $candidateRows = [];
            $finalRows = [];
            $subjectRows = [];
            $now = now();
            $processedCount = 0;
            $errorCount = 0;

            $candidateFlags = [
                'total_marks' => $this->hasColumn('candidate_results', 'total_marks'),
                'total_percentage' => $this->hasColumn('candidate_results', 'total_percentage'),
                'overall_grade' => $this->hasColumn('candidate_results', 'overall_grade'),
                'result_status' => $this->hasColumn('candidate_results', 'result_status'),
                'grade_points' => $this->hasColumn('candidate_results', 'grade_points'),
                'division' => $this->hasColumn('candidate_results', 'division'),
                'gpa' => $this->hasColumn('candidate_results', 'gpa'),
                'competence_level' => $this->hasColumn('candidate_results', 'competence_level'),
                'status' => $this->hasColumn('candidate_results', 'status'),
                'released_at' => $this->hasColumn('candidate_results', 'released_at'),
                'is_verified' => $this->hasColumn('candidate_results', 'is_verified'),
                'verified_at' => $this->hasColumn('candidate_results', 'verified_at'),
            ];
            $finalFlags = [
                'grading_profile_id' => $this->hasColumn('final_grades', 'grading_profile_id'),
                'overall_grade' => $this->hasColumn('final_grades', 'overall_grade'),
                'grade' => $this->hasColumn('final_grades', 'grade'),
                'grade_name' => $this->hasColumn('final_grades', 'grade_name'),
                'total_marks' => $this->hasColumn('final_grades', 'total_marks'),
                'final_percentage' => $this->hasColumn('final_grades', 'final_percentage'),
                'grade_points' => $this->hasColumn('final_grades', 'grade_points'),
                'gpa' => $this->hasColumn('final_grades', 'gpa'),
                'division' => $this->hasColumn('final_grades', 'division'),
                'competence_level' => $this->hasColumn('final_grades', 'competence_level'),
                'grading_breakdown' => $this->hasColumn('final_grades', 'grading_breakdown'),
            ];

            foreach ($marksByCandidate as $candidateId => $candidateMarks) {
                try {
                    $totalMarks = 0.0;
                    $totalSubjects = 0;
                    $subjectGrades = [];
                    $irregularStatuses = [];
                    $coreCompleteness = ['missing_subject_ids' => [], 'is_complete' => true];

                    foreach ($candidateMarks as $mark) {
                        $status = strtoupper((string) ($mark->subject_status ?? ''));
                        $subject = $mark->subject;
                        $paperValues = $this->paperValues($mark);
                        $requiredPaperCodes = $this->requiredPaperCodes($subject);
                        $missingRequiredPaper = collect($requiredPaperCodes)->contains(
                            fn (string $code) => !array_key_exists($code, $paperValues)
                        );

                        // Keep explicit ABS/X statuses from import/moderation.
                        if (!in_array($status, ['ABS', 'X'], true)) {
                            // Missing any required paper => INC and excluded from grading/division/GPA.
                            if ($missingRequiredPaper) {
                                $status = 'INC';
                            } elseif ($status === '') {
                                $status = null;
                            }
                        }

                        if (in_array((string) $status, ['INC', 'X', 'ABS'], true)) {
                            $irregularStatuses[] = (string) $status;
                            $subjectRows[] = $this->buildSubjectRow(
                                $mark,
                                null,
                                null,
                                (string) $status,
                                null,
                                $process->id,
                                $now
                            );
                            continue;
                        }

                        if (count($paperValues) === 0) {
                            $irregularStatuses[] = 'INC';
                            $subjectRows[] = $this->buildSubjectRow(
                                $mark,
                                null,
                                null,
                                'INC',
                                null,
                                $process->id,
                                $now
                            );
                            continue;
                        }

                        // Weighted normalization to 100-point scale (equal weights fallback).
                        $numericMark = $this->normalizeSubjectMarkTo100($paperValues, $subject);
                        $grade = $this->gradingService->calculateGradeWithRules((float) $numericMark, $resolved['grading_rules']);
                        $subjectName = (string) ($mark->subject->name ?? '');
                        $points = $this->gradingService->getGradePointsWithMapping($grade, $resolved['gpa_grade_points']);

                        $subjectRows[] = $this->buildSubjectRow(
                            $mark,
                            $numericMark,
                            $numericMark,
                            null,
                            $grade,
                            $process->id,
                            $now
                        );

                        $totalMarks += $numericMark;
                        $totalSubjects++;

                        $subjectGrades[] = [
                            'subject_id' => $mark->subject_id,
                            'subject_name' => $subjectName,
                            'grade' => $grade,
                            'points' => $points,
                        ];
                    }

                    if ($totalSubjects === 0) {
                        $irregularOverall = $this->resolveIrregularOverallStatus($irregularStatuses);
                        $computedResultStatus = in_array($irregularOverall, ['ABS', 'X'], true) ? 'ABS' : 'INC';
                        $candidateRow = [
                            'candidate_id' => (int) $candidateId,
                            'exam_type_id' => $examTypeId,
                            'year' => (int) $examYear->year_label,
                            'updated_at' => $now,
                            'created_at' => $now,
                        ];
                        if ($candidateFlags['total_marks']) $candidateRow['total_marks'] = 0;
                        if ($candidateFlags['total_percentage']) $candidateRow['total_percentage'] = 0;
                        if ($candidateFlags['overall_grade']) $candidateRow['overall_grade'] = $irregularOverall;
                        if ($candidateFlags['result_status']) $candidateRow['result_status'] = $computedResultStatus;
                        if ($candidateFlags['grade_points']) $candidateRow['grade_points'] = null;
                        if ($candidateFlags['division']) $candidateRow['division'] = '0';
                        if ($candidateFlags['gpa']) $candidateRow['gpa'] = 0;
                        if ($candidateFlags['competence_level']) $candidateRow['competence_level'] = 'Not Computed';
                        if ($candidateFlags['status']) $candidateRow['status'] = 'PENDING';
                        if ($candidateFlags['released_at']) $candidateRow['released_at'] = null;
                        if ($candidateFlags['is_verified']) $candidateRow['is_verified'] = true;
                        if ($candidateFlags['verified_at']) $candidateRow['verified_at'] = $now;
                        if ($this->hasColumn('candidate_results', 'process_id')) $candidateRow['process_id'] = $process->id;
                        if ($this->hasColumn('candidate_results', 'snapshot_id')) $candidateRow['snapshot_id'] = null;
                        $candidateRows[] = $candidateRow;

                        $finalRow = [
                            'candidate_id' => (int) $candidateId,
                            'exam_type_id' => $examTypeId,
                            'year' => (int) $examYear->year_label,
                            'updated_at' => $now,
                            'created_at' => $now,
                        ];
                        if ($finalFlags['grading_profile_id']) $finalRow['grading_profile_id'] = $profile->id;
                        if ($finalFlags['overall_grade']) $finalRow['overall_grade'] = $irregularOverall;
                        if ($finalFlags['grade']) $finalRow['grade'] = $irregularOverall;
                        if ($finalFlags['grade_name']) $finalRow['grade_name'] = 'IRREGULAR';
                        if ($finalFlags['total_marks']) $finalRow['total_marks'] = 0;
                        if ($finalFlags['final_percentage']) $finalRow['final_percentage'] = 0;
                        if ($finalFlags['grade_points']) $finalRow['grade_points'] = null;
                        if ($finalFlags['gpa']) $finalRow['gpa'] = 0;
                        if ($finalFlags['division']) $finalRow['division'] = '0';
                        if ($finalFlags['competence_level']) $finalRow['competence_level'] = 'Not Computed';
                        if ($finalFlags['grading_breakdown']) {
                            $finalRow['grading_breakdown'] = json_encode([
                                'aggt_points' => null,
                                'principal_passes' => 0,
                                'subjects_count' => 0,
                                'gpa_subjects_count' => 0,
                                'run_type' => $runType,
                                'irregular_overall_status' => $irregularOverall,
                                'missing_core_subject_ids' => $coreCompleteness['missing_subject_ids'] ?? [],
                            ]);
                        }
                        if ($this->hasColumn('final_grades', 'process_id')) $finalRow['process_id'] = $process->id;
                        if ($this->hasColumn('final_grades', 'snapshot_id')) $finalRow['snapshot_id'] = null;
                        $finalRows[] = $finalRow;
                        $processedCount++;
                        continue;
                    }

                    $averageMark = round($totalMarks / $totalSubjects, 2);
                    $coreSubjectIds = $principalSelections->get($candidateId);
                    $effectiveCoreSubjectIds = !empty($coreSubjectIds)
                        ? $coreSubjectIds
                        : collect($subjectGrades)
                            ->pluck('subject_id')
                            ->map(fn ($id) => (int) $id)
                            ->filter()
                            ->unique()
                            ->values()
                            ->all();
                    $coreCompleteness = $this->gradingService->analyzeCoreSubjectCompleteness($subjectGrades, $effectiveCoreSubjectIds);
                    if (!empty($coreSubjectIds) && !$coreCompleteness['is_complete']) {
                        $irregularStatuses[] = 'INC';
                    }
                    $aggtPoints = $this->gradingService->calculateAggtFromSubjectGrades($subjectGrades, $effectiveCoreSubjectIds);
                    $principalPassCount = $this->gradingService->countPrincipalPassesFromSubjectGrades($subjectGrades, $effectiveCoreSubjectIds);
                    $gpaRows = collect($subjectGrades)
                        ->filter(fn (array $row) => in_array((int) ($row['subject_id'] ?? 0), $effectiveCoreSubjectIds, true))
                        ->sortBy(fn (array $row) => (float) ($row['points'] ?? 99))
                        ->take(3)
                        ->values();
                    $gpaSubjects = $gpaRows->count();
                    $gpaPoints = (float) $gpaRows->sum(fn (array $row) => (float) ($row['points'] ?? 0));
                    $computedResultStatus = !empty($irregularStatuses) ? 'INC' : 'COMPLETE';
                    $irregularOverall = $computedResultStatus === 'COMPLETE'
                        ? null
                        : $this->resolveIrregularOverallStatus($irregularStatuses);

                    if ($computedResultStatus === 'COMPLETE') {
                        $overallGrade = $this->gradingService->calculateGradeWithRules($averageMark, $resolved['grading_rules']);
                        $gpa = $gpaSubjects > 0 ? round($gpaPoints / $gpaSubjects, $roundingDecimals) : 0.0;
                        $divisionInfo = $this->gradingService->calculateDivisionWithRulesAndEligibility(
                            (float) ($aggtPoints ?? 0),
                            (int) $principalPassCount,
                            $resolved['division_rules']
                        );
                        $division = $divisionInfo['division'] ?? 0;
                        $competenceInfo = $this->gradingService->getCompetenceByBasis($gpa, 'GPA', $resolved['competence_rules']);
                        $competence = (string) ($competenceInfo['level_label'] ?? 'Unknown');
                    } else {
                        $overallGrade = $irregularOverall ?: 'INC';
                        $gpa = 0.0;
                        $aggtPoints = null;
                        $principalPassCount = 0;
                        $gpaSubjects = 0;
                        $division = 0;
                        $competence = 'Not Computed';
                    }

                    $candidateRow = [
                        'candidate_id' => (int) $candidateId,
                        'exam_type_id' => $examTypeId,
                        'year' => (int) $examYear->year_label,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ];
                    if ($candidateFlags['total_marks']) $candidateRow['total_marks'] = $totalMarks;
                    if ($candidateFlags['total_percentage']) $candidateRow['total_percentage'] = $averageMark;
                    if ($candidateFlags['overall_grade']) $candidateRow['overall_grade'] = $overallGrade;
                    if ($candidateFlags['result_status']) $candidateRow['result_status'] = $computedResultStatus;
                    if ($candidateFlags['grade_points']) $candidateRow['grade_points'] = $aggtPoints;
                    if ($candidateFlags['division']) $candidateRow['division'] = (string) $division;
                    if ($candidateFlags['gpa']) $candidateRow['gpa'] = $gpa;
                    if ($candidateFlags['competence_level']) $candidateRow['competence_level'] = $competence;
                    if ($candidateFlags['status']) $candidateRow['status'] = 'PENDING';
                    if ($candidateFlags['released_at']) $candidateRow['released_at'] = null;
                    if ($candidateFlags['is_verified']) $candidateRow['is_verified'] = true;
                    if ($candidateFlags['verified_at']) $candidateRow['verified_at'] = $now;
                    if ($this->hasColumn('candidate_results', 'process_id')) $candidateRow['process_id'] = $process->id;
                    if ($this->hasColumn('candidate_results', 'snapshot_id')) $candidateRow['snapshot_id'] = null;
                    $candidateRows[] = $candidateRow;

                    $finalRow = [
                        'candidate_id' => (int) $candidateId,
                        'exam_type_id' => $examTypeId,
                        'year' => (int) $examYear->year_label,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ];
                    if ($finalFlags['grading_profile_id']) $finalRow['grading_profile_id'] = $profile->id;
                    if ($finalFlags['overall_grade']) $finalRow['overall_grade'] = $overallGrade;
                    if ($finalFlags['grade']) $finalRow['grade'] = $overallGrade;
                    if ($finalFlags['grade_name']) $finalRow['grade_name'] = $competence;
                    if ($finalFlags['total_marks']) $finalRow['total_marks'] = $totalMarks;
                    if ($finalFlags['final_percentage']) $finalRow['final_percentage'] = $averageMark;
                    if ($finalFlags['grade_points']) $finalRow['grade_points'] = $aggtPoints;
                    if ($finalFlags['gpa']) $finalRow['gpa'] = $gpa;
                    if ($finalFlags['division']) $finalRow['division'] = (string) $division;
                    if ($finalFlags['competence_level']) $finalRow['competence_level'] = $competence;
                    if ($finalFlags['grading_breakdown']) {
                        $finalRow['grading_breakdown'] = json_encode([
                            'aggt_points' => $aggtPoints,
                            'principal_passes' => $principalPassCount,
                            'subjects_count' => $totalSubjects,
                            'gpa_subjects_count' => $gpaSubjects,
                            'run_type' => $runType,
                            'irregular_overall_status' => $irregularOverall,
                            'missing_core_subject_ids' => $coreCompleteness['missing_subject_ids'] ?? [],
                        ]);
                    }
                    if ($this->hasColumn('final_grades', 'process_id')) $finalRow['process_id'] = $process->id;
                    if ($this->hasColumn('final_grades', 'snapshot_id')) $finalRow['snapshot_id'] = null;
                    $finalRows[] = $finalRow;
                    $processedCount++;
                } catch (\Throwable $e) {
                    $errorCount++;
                    $skippedByReason['candidate_compute_errors']++;
                    if (count($errors) < 25) {
                        $errors[] = [
                            'candidate_id' => (int) $candidateId,
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }

            // Draft compute rows are rewritten per run scope. Avoid upsert conflict assumptions
            // because unique keys differ across environments after snapshot-versioning migrations.
            // Do not rewrite live subject_marks here: those rows are promoted by Mark Entry and
            // remain the source input for Results compute and snapshot publication.
            $candidateDelete = DB::table('candidate_results')
                ->where('exam_type_id', $examTypeId)
                ->where('year', (int) $examYear->year_label)
                ->when($this->hasColumn('candidate_results', 'snapshot_id'), fn ($q) => $q->whereNull('candidate_results.snapshot_id'));
            $this->applyScopeToFinalTableQuery($candidateDelete, 'candidate_results', $scope);
            $candidateDelete->delete();

            $finalDelete = DB::table('final_grades')
                ->where('exam_type_id', $examTypeId)
                ->where('year', (int) $examYear->year_label)
                ->when($this->hasColumn('final_grades', 'snapshot_id'), fn ($q) => $q->whereNull('final_grades.snapshot_id'));
            $this->applyScopeToFinalTableQuery($finalDelete, 'final_grades', $scope);
            $finalDelete->delete();

            if (!empty($candidateRows)) {
                foreach (array_chunk($candidateRows, 500) as $chunk) {
                    DB::table('candidate_results')->insert($chunk);
                }
            }
            if (!empty($finalRows)) {
                foreach (array_chunk($finalRows, 500) as $chunk) {
                    DB::table('final_grades')->insert($chunk);
                }
            }

            $statsPayload = $this->buildResultStatsPayload($examYear, $process->id, null);
            $totalSkippedCount = max(0, ((int) $process->total_candidates) - $processedCount);
            $knownSkippedCount = array_sum($skippedByReason);
            if ($totalSkippedCount > $knownSkippedCount) {
                $skippedByReason['other'] = $totalSkippedCount - $knownSkippedCount;
            }
            ResultStatistic::query()->updateOrCreate(
                [
                    'exam_type' => 'ACSEE',
                    'exam_year_id' => $examYear->id,
                    'process_id' => $process->id,
                    'snapshot_id' => null,
                    'scope_type' => 'national',
                    'scope_id' => null,
                ],
                array_merge($statsPayload, [
                    'generated_at' => now(),
                ])
            );

            $process->update([
                'status' => 'completed',
                'processed_count' => $processedCount,
                'error_count' => $errorCount,
                'completed_at' => now(),
                'finished_at' => now(),
                'stats' => array_merge($statsPayload, [
                    'processed_candidates' => $processedCount,
                    'error_count' => $errorCount,
                    'skipped_candidates' => $totalSkippedCount,
                    'skipped_breakdown' => $skippedByReason,
                ]),
                'metadata' => array_merge($process->metadata ?? [], [
                    'promotion' => $promotionTotals,
                    'processed_candidates' => $processedCount,
                    'skipped_candidates' => $totalSkippedCount,
                    'skipped_breakdown' => $skippedByReason,
                    'errors_preview' => $errors,
                    'warnings' => $analysis['warnings'],
                ]),
            ]);

            return response()->json([
                'success' => true,
                'message' => strtoupper($runType) . ' compute/validate completed.',
                'data' => [
                    'process_id' => $process->id,
                    'processed_candidates' => $processedCount,
                    'error_count' => $errorCount,
                    'skipped_candidates' => $totalSkippedCount,
                    'skipped_breakdown' => $skippedByReason,
                    'promotion' => $promotionTotals,
                    'errors_preview' => $errors,
                ],
            ]);
        } catch (\Throwable $e) {
            $process->update([
                'status' => 'failed',
                'completed_at' => now(),
                'finished_at' => now(),
                'error_message' => $e->getMessage(),
                'metadata' => array_merge($process->metadata ?? [], [
                    'promotion' => $promotionTotals,
                    'errors_preview' => array_merge($errors, [['error' => $e->getMessage()]]),
                ]),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Compute/validate failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function computeProcesses(Request $request): JsonResponse
    {
        $this->authorize('viewResults', CandidateResult::class);

        $examYearId = $request->integer('exam_year_id') ?: $this->resolveExamYearId($request);
        $items = ResultProcess::query()
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->when($examYearId, fn (Builder $q) => $q->where('exam_year_id', $examYearId))
            ->latest('id')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function computeProcessShow(Request $request, int $id): JsonResponse
    {
        $this->authorize('viewResults', CandidateResult::class);

        $item = ResultProcess::query()
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $item,
        ]);
    }

    public function snapshots(Request $request): JsonResponse
    {
        $this->authorize('viewResults', CandidateResult::class);
        $examYearId = $request->integer('exam_year_id') ?: $this->resolveExamYearId($request);
        $scope = $this->resolveScopeFromRequest($request, true);
        $items = ResultSnapshot::query()
            ->where('exam_type', 'ACSEE')
            ->when($examYearId, fn (Builder $q) => $q->where('exam_year_id', $examYearId))
            ->when($this->hasColumn('result_snapshots', 'scope_type'), fn (Builder $q) => $q->where('scope_type', $scope['scope_type']))
            ->when($this->hasColumn('result_snapshots', 'scope_id'), function (Builder $q) use ($scope) {
                if ($scope['scope_id'] === null) {
                    $q->whereNull('scope_id');
                    return;
                }
                $q->where('scope_id', $scope['scope_id']);
            })
            ->latest('id')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewResults', CandidateResult::class);
        $examYearId = $request->integer('exam_year_id') ?: $this->resolveExamYearId($request);
        $mode = strtolower((string) $request->input('mode', 'published'));
        $scopeType = (string) $request->input('scope_type', 'national');
        $scopeId = $request->input('scope_id');

        $query = ResultStatistic::query()
            ->where('exam_type', 'ACSEE')
            ->when($examYearId, fn (Builder $q) => $q->where('exam_year_id', $examYearId))
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId);

        if ($mode === 'published') {
            $snapshot = $examYearId ? $this->activeSnapshot($examYearId) : null;
            if ($snapshot) {
                $query->where('snapshot_id', $snapshot->id);
            } else {
                $query->whereRaw('1=0');
            }
        } else {
            $query->whereNull('snapshot_id')->latest('process_id');
        }

        $stat = $query->latest('generated_at')->first();

        // Keep moderation dashboard stats fresh even when schema evolves (e.g. fallbacks from final_grades).
        if ($examYearId) {
            $examYear = ExamYear::query()->find($examYearId);
            if ($examYear) {
                if ($mode === 'published') {
                    $snapshot = $this->activeSnapshot($examYearId);
                    if ($snapshot) {
                        $payload = $this->buildResultStatsPayload($examYear, null, (int) $snapshot->id);
                        ResultStatistic::query()->updateOrCreate(
                            [
                                'exam_type' => 'ACSEE',
                                'exam_year_id' => $examYearId,
                                'snapshot_id' => $snapshot->id,
                                'process_id' => $snapshot->process_id,
                                'scope_type' => $scopeType,
                                'scope_id' => $scopeId,
                            ],
                            array_merge($payload, ['generated_at' => now()])
                        );
                        $stat = ResultStatistic::query()
                            ->where('exam_type', 'ACSEE')
                            ->where('exam_year_id', $examYearId)
                            ->where('scope_type', $scopeType)
                            ->where('scope_id', $scopeId)
                            ->where('snapshot_id', $snapshot->id)
                            ->latest('generated_at')
                            ->first();
                    }
                } else {
                    $processId = $stat?->process_id;
                    if (!$processId) {
                        $processId = ResultProcess::query()
                            ->where('exam_type_id', $this->acseeExamTypeId())
                            ->where('exam_year_id', $examYearId)
                            ->where('status', 'completed')
                            ->latest('id')
                            ->value('id');
                    }
                    if ($processId) {
                        $payload = $this->buildResultStatsPayload($examYear, (int) $processId, null);
                        ResultStatistic::query()->updateOrCreate(
                            [
                                'exam_type' => 'ACSEE',
                                'exam_year_id' => $examYearId,
                                'snapshot_id' => null,
                                'process_id' => $processId,
                                'scope_type' => $scopeType,
                                'scope_id' => $scopeId,
                            ],
                            array_merge($payload, ['generated_at' => now()])
                        );
                        $stat = ResultStatistic::query()
                            ->where('exam_type', 'ACSEE')
                            ->where('exam_year_id', $examYearId)
                            ->where('scope_type', $scopeType)
                            ->where('scope_id', $scopeId)
                            ->whereNull('snapshot_id')
                            ->latest('generated_at')
                            ->first();
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $stat,
        ]);
    }

    public function pendingReview(Request $request): JsonResponse
    {
        $this->authorize('viewResults', CandidateResult::class);

        $items = ResultProcess::query()
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('exam_year_id', $this->resolveExamYearId($request))
            ->whereIn('status', ['pending', 'in_progress', 'failed'])
            ->latest('created_at')
            ->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function schools(Request $request): JsonResponse
    {
        $this->authorize('viewResults', CandidateResult::class);

        $query = $this->scopedCandidateResults($request)
            ->join('candidates', 'candidate_results.candidate_id', '=', 'candidates.id')
            ->join('schools', 'candidates.school_id', '=', 'schools.id')
            ->selectRaw('schools.id, schools.code, schools.name, schools.region_id, schools.district_id, schools.council_id, COUNT(DISTINCT candidate_results.candidate_id) as candidates_count')
            ->groupBy('schools.id', 'schools.code', 'schools.name', 'schools.region_id', 'schools.district_id', 'schools.council_id')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->input('search');
                $q->where(function ($sub) use ($search) {
                    $sub->where('schools.name', 'like', "%{$search}%")
                        ->orWhere('schools.code', 'like', "%{$search}%");
                });
            })
            ->orderBy('schools.name');

        return response()->json([
            'success' => true,
            'data' => $query->paginate((int) $request->input('per_page', 15)),
        ]);
    }

    public function candidates(Request $request): JsonResponse
    {
        $this->authorize('viewResults', CandidateResult::class);

        $query = $this->scopedCandidateResults($request)
            ->join('candidates', 'candidate_results.candidate_id', '=', 'candidates.id')
            ->join('schools', 'candidates.school_id', '=', 'schools.id')
            ->select([
                'candidate_results.id',
                'candidate_results.candidate_id as candidate_pk',
                'candidates.candidate_id as index_number',
                'candidates.full_name',
                'candidates.gender',
                'schools.id as school_id',
                'schools.name as school_name',
                'candidate_results.overall_grade',
                'candidate_results.total_marks',
                'candidate_results.division',
                'candidate_results.status',
            ])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->input('search');
                $q->where(function ($sub) use ($search) {
                    $sub->where('candidates.full_name', 'like', "%{$search}%")
                        ->orWhere('candidates.candidate_id', 'like', "%{$search}%");
                });
            })
            ->orderBy('candidates.full_name');

        return response()->json([
            'success' => true,
            'data' => $query->paginate((int) $request->input('per_page', 15)),
        ]);
    }

    public function schoolSheet(Request $request, int $id): JsonResponse
    {
        $this->authorize('viewResults', CandidateResult::class);

        $school = School::query()->findOrFail($id);

        $results = $this->scopedCandidateResults($request)
            ->whereHas('candidate', fn (Builder $q) => $q->where('school_id', $school->id))
            ->with([
                'candidate:id,school_id,candidate_id,full_name,gender',
                'subjectMarks:id,candidate_id,subject_id,exam_type_id,year,marks_obtained,grade',
                'subjectMarks.subject:id,code,name',
            ])
            ->orderBy('candidate_id')
            ->paginate((int) $request->input('per_page', 30));

        return response()->json([
            'success' => true,
            'data' => [
                'school' => [
                    'id' => $school->id,
                    'code' => $school->code,
                    'name' => $school->name,
                ],
                'sheet' => $results,
            ],
        ]);
    }

    public function candidateStatement(Request $request, int $id): JsonResponse
    {
        $this->authorize('viewResults', CandidateResult::class);

        $candidate = Candidate::query()->with('school:id,name,code')->findOrFail($id);

        $result = $this->scopedCandidateResults($request)
            ->where('candidate_id', $candidate->id)
            ->with([
                'subjectMarks:id,candidate_id,subject_id,exam_type_id,year,marks_obtained,grade',
                'subjectMarks.subject:id,code,name',
            ])
            ->firstOrFail();

        $grade = FinalGrade::query()
            ->where('candidate_id', $candidate->id)
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('year', $this->resolveYear($request))
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'candidate' => [
                    'id' => $candidate->id,
                    'index_number' => $candidate->candidate_id,
                    'full_name' => $candidate->full_name,
                    'gender' => $candidate->gender,
                    'school' => $candidate->school,
                ],
                'result' => $result,
                'final_grade' => $grade,
            ],
        ]);
    }

    public function submissionLockingStatus(Request $request): JsonResponse
    {
        $this->authorize('viewResults', CandidateResult::class);
        $validated = $request->validate([
            'exam_year_id' => ['required', 'integer', 'exists:exam_years,id'],
            'scope_type' => ['nullable', 'in:national,region,district,school'],
            'scope_id' => ['nullable', 'integer'],
        ]);

        $examYear = ExamYear::query()->findOrFail((int) $validated['exam_year_id']);
        $scope = $this->resolveScopeFromRequest($request);

        $latestProcess = $this->latestProcessForScope($examYear, $scope);
        $latestCompleted = $this->latestProcessForScope($examYear, $scope, 'completed');
        $activeSnapshot = $this->activeSnapshot($examYear->id, $scope['scope_type'], $scope['scope_id']);
        $isLocked = $this->snapshotIsLocked($activeSnapshot);

        $draftCounts = $latestCompleted
            ? $this->draftCountsByProcess($examYear, $latestCompleted->id, $scope)
            : ['candidates' => 0, 'subject_marks' => 0, 'final_grades' => 0];

        $blockers = $latestCompleted
            ? $this->computeIrregularityBlockers($examYear, $latestCompleted->id, $scope)
            : ['missing_core_data' => 0, 'inc_count' => 0, 'abs_count' => 0, 'x_count' => 0];

        $readyBatches = $this->scopedReadyBatches(new Request([
            'exam_year_id' => $examYear->id,
            'region_id' => $scope['scope_type'] === 'region' ? $scope['scope_id'] : null,
            'district_id' => $scope['scope_type'] === 'district' ? $scope['scope_id'] : null,
            'school_id' => $scope['scope_type'] === 'school' ? $scope['scope_id'] : null,
        ]), (int) $examYear->year_label);

        $readyPairKeys = $this->liveMarkSetService->readyBatchPairKeys($readyBatches);
        $liveReadyMarks = $this->liveMarkSetService->currentLiveSubjectMarksCollection(
            new Request([
                'exam_year_id' => $examYear->id,
                'region_id' => $scope['scope_type'] === 'region' ? $scope['scope_id'] : null,
                'district_id' => $scope['scope_type'] === 'district' ? $scope['scope_id'] : null,
                'school_id' => $scope['scope_type'] === 'school' ? $scope['scope_id'] : null,
            ]),
            $this->acseeExamTypeId(),
            (int) $examYear->year_label,
            fn ($query, $scopeRequest, $candidateAlias, $schoolAlias) => $this->applyScopeFiltersToCandidateJoinQuery($query, $scopeRequest, $candidateAlias, $schoolAlias),
            false,
            null,
            $readyPairKeys
        );
        $readyMarkCandidates = $liveReadyMarks->pluck('candidate_id')->unique()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'exam_year_id' => $examYear->id,
                'latest_process' => $latestProcess?->only(['id', 'type', 'status', 'processed_count', 'error_count', 'finished_at', 'completed_at', 'stats']),
                'draft_counts' => $draftCounts,
                'active_snapshot' => $activeSnapshot?->only(['id', 'version', 'published_at', 'is_active', 'locked_at', 'lock_reason']),
                'is_locked' => $isLocked,
                'can_publish' => auth()->user()->can('publishLock', CandidateResult::class),
                'can_lock' => auth()->user()->can('publishLock', CandidateResult::class),
                'scope' => $scope,
                'blockers' => $blockers,
                'preconditions' => [
                    'has_approved_locked_marks' => $readyMarkCandidates > 0,
                    'latest_compute_completed' => (bool) $latestCompleted,
                    'has_draft_rows' => ((int) ($draftCounts['candidates'] ?? 0)) > 0,
                    'has_publish_permission' => auth()->user()->can('publishLock', CandidateResult::class),
                ],
                'recent_actions' => $this->recentSubmissionActions($examYear->id, $scope),
            ],
        ]);
    }

    public function publishSnapshot(Request $request): JsonResponse
    {
        $this->authorize('publishLock', CandidateResult::class);
        $validated = $request->validate([
            'exam_year_id' => ['required', 'integer', 'exists:exam_years,id'],
            'scope_type' => ['nullable', 'in:national,region,district,school'],
            'scope_id' => ['nullable', 'integer'],
            'publish_notes' => ['nullable', 'string'],
        ]);

        $examYear = ExamYear::query()->findOrFail((int) $validated['exam_year_id']);
        $scope = $this->resolveScopeFromRequest($request);
        $activeSnapshot = $this->activeSnapshot($examYear->id, $scope['scope_type'], $scope['scope_id']);
        if ($this->snapshotIsLocked($activeSnapshot)) {
            return response()->json([
                'success' => false,
                'message' => 'Published results are locked for this scope. Admin unlock is required.',
            ], 423);
        }

        $latestCompleted = $this->latestProcessForScope($examYear, $scope, 'completed');
        if (!$latestCompleted) {
            return response()->json([
                'success' => false,
                'message' => 'No completed compute run found for this scope. Run Compute/Validate first.',
            ], 422);
        }

        $draftCounts = $this->draftCountsByProcess($examYear, (int) $latestCompleted->id, $scope);
        if ((int) ($draftCounts['candidates'] ?? 0) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No draft candidate results found for latest completed run.',
            ], 422);
        }

        $copyCounts = ['candidate_results' => 0, 'final_grades' => 0, 'subject_marks' => 0];
        $snapshot = DB::transaction(function () use ($examYear, $scope, $latestCompleted, $validated, &$copyCounts) {
            ResultSnapshot::query()
                ->where('exam_type', 'ACSEE')
                ->where('exam_year_id', $examYear->id)
                ->when($this->hasColumn('result_snapshots', 'scope_type'), fn (Builder $q) => $q->where('scope_type', $scope['scope_type']))
                ->when($this->hasColumn('result_snapshots', 'scope_id'), function (Builder $q) use ($scope) {
                    if ($scope['scope_id'] === null) {
                        $q->whereNull('scope_id');
                        return;
                    }
                    $q->where('scope_id', $scope['scope_id']);
                })
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $latestVersion = ResultSnapshot::query()
                ->where('exam_type', 'ACSEE')
                ->where('exam_year_id', $examYear->id)
                ->when($this->hasColumn('result_snapshots', 'scope_type'), fn (Builder $q) => $q->where('scope_type', $scope['scope_type']))
                ->when($this->hasColumn('result_snapshots', 'scope_id'), function (Builder $q) use ($scope) {
                    if ($scope['scope_id'] === null) {
                        $q->whereNull('scope_id');
                        return;
                    }
                    $q->where('scope_id', $scope['scope_id']);
                })
                ->latest('id')
                ->value('version');
            $nextVersion = 'v' . (((int) preg_replace('/[^0-9]/', '', (string) $latestVersion)) + 1);

            $snapshot = ResultSnapshot::query()->create([
                'exam_type' => 'ACSEE',
                'exam_year_id' => $examYear->id,
                'process_id' => $latestCompleted->id,
                'scope_type' => $scope['scope_type'],
                'scope_id' => $scope['scope_id'],
                'version' => $nextVersion,
                'is_active' => true,
                'published_by' => auth()->id(),
                'published_at' => now(),
                'publish_notes' => $validated['publish_notes'] ?? null,
            ]);

            $copyCounts['candidate_results'] = $this->copyDraftRowsToSnapshot('candidate_results', $examYear, $latestCompleted, $snapshot, $scope);
            $copyCounts['final_grades'] = $this->copyDraftRowsToSnapshot('final_grades', $examYear, $latestCompleted, $snapshot, $scope);
            $copyCounts['subject_marks'] = $this->copyDraftRowsToSnapshot('subject_marks', $examYear, $latestCompleted, $snapshot, $scope);

            $snapshotHash = hash('sha256', json_encode([
                'snapshot_id' => $snapshot->id,
                'process_id' => $latestCompleted->id,
                'year' => $examYear->year_label,
                'scope_type' => $scope['scope_type'],
                'scope_id' => $scope['scope_id'],
                'counts' => $copyCounts,
            ]));
            $snapshot->update(['snapshot_hash' => $snapshotHash]);

            $statsPayload = $this->buildResultStatsPayload($examYear, null, (int) $snapshot->id);
            ResultStatistic::query()->updateOrCreate(
                [
                    'exam_type' => 'ACSEE',
                    'exam_year_id' => $examYear->id,
                    'snapshot_id' => $snapshot->id,
                    'process_id' => $latestCompleted->id,
                    'scope_type' => $scope['scope_type'],
                    'scope_id' => $scope['scope_id'],
                ],
                array_merge($statsPayload, ['generated_at' => now()])
            );

            return $snapshot;
        });

        $this->logGovernanceAction(auth()->user(), 'results_snapshot_publish', [
            'exam_year_id' => $examYear->id,
            'exam_year' => $examYear->year_label,
            'exam_type' => 'ACSEE',
            'snapshot_id' => $snapshot->id,
            'snapshot_version' => $snapshot->version,
            'scope' => $scope,
            'counts' => $copyCounts,
            'notes' => $validated['publish_notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Snapshot {$snapshot->version} published successfully.",
            'snapshot' => $snapshot,
            'counts' => $copyCounts,
        ]);
    }

    public function lockSnapshot(Request $request): JsonResponse
    {
        $this->authorize('publishLock', CandidateResult::class);
        $validated = $request->validate([
            'exam_year_id' => ['required', 'integer', 'exists:exam_years,id'],
            'scope_type' => ['nullable', 'in:national,region,district,school'],
            'scope_id' => ['nullable', 'integer'],
            'reason' => ['required', 'string', 'min:3'],
        ]);

        $examYear = ExamYear::query()->findOrFail((int) $validated['exam_year_id']);
        $scope = $this->resolveScopeFromRequest($request);
        $activeSnapshot = $this->activeSnapshot($examYear->id, $scope['scope_type'], $scope['scope_id']);
        if (!$activeSnapshot) {
            return response()->json([
                'success' => false,
                'message' => 'No active published snapshot found for this scope.',
            ], 422);
        }
        if ($this->snapshotIsLocked($activeSnapshot)) {
            return response()->json([
                'success' => true,
                'message' => 'Active snapshot is already locked.',
            ]);
        }

        DB::transaction(function () use ($activeSnapshot, $validated) {
            $activeSnapshot->update([
                'locked_by' => auth()->id(),
                'locked_at' => now(),
                'lock_reason' => trim((string) $validated['reason']),
            ]);
        });

        $this->logGovernanceAction(auth()->user(), 'results_snapshot_lock', [
            'exam_year_id' => $examYear->id,
            'exam_year' => $examYear->year_label,
            'exam_type' => 'ACSEE',
            'snapshot_id' => $activeSnapshot->id,
            'snapshot_version' => $activeSnapshot->version,
            'scope' => $scope,
            'reason' => trim((string) $validated['reason']),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Snapshot {$activeSnapshot->version} locked successfully.",
        ]);
    }

    public function publishLock(Request $request): JsonResponse
    {
        $published = $this->publishSnapshot($request);
        if ($published->status() >= 400) {
            return $published;
        }

        $payload = $published->getData(true);
        $lockRequest = new Request(array_merge($request->all(), [
            'reason' => 'Legacy publish-lock action',
        ]));
        $lockRequest->setUserResolver(fn () => $request->user());
        $locked = $this->lockSnapshot($lockRequest);
        if ($locked->status() >= 400) {
            return $locked;
        }

        return response()->json([
            'success' => true,
            'message' => ($payload['message'] ?? 'Snapshot published successfully.') . ' Snapshot locked.',
            'snapshot' => $payload['snapshot'] ?? null,
            'counts' => $payload['counts'] ?? null,
        ]);
    }

    public function adminUnlock(Request $request): JsonResponse
    {
        $this->authorize('adminUnlock', CandidateResult::class);
        $validated = $request->validate([
            'exam_year_id' => ['required', 'integer', 'exists:exam_years,id'],
            'scope_type' => ['nullable', 'in:national,region,district,school'],
            'scope_id' => ['nullable', 'integer'],
            'reason' => ['required', 'string', 'min:3'],
        ]);

        $examYear = ExamYear::query()->findOrFail((int) $validated['exam_year_id']);
        $scope = $this->resolveScopeFromRequest($request, true);
        $activeSnapshot = $this->activeSnapshot($examYear->id, $scope['scope_type'], $scope['scope_id']);
        if (!$activeSnapshot) {
            return response()->json([
                'success' => false,
                'message' => 'No active published snapshot found for this scope.',
            ], 422);
        }

        DB::transaction(function () use ($activeSnapshot, $validated) {
            $activeSnapshot->update([
                'locked_by' => null,
                'locked_at' => null,
                'lock_reason' => null,
                'unlocked_by' => auth()->id(),
                'unlocked_at' => now(),
                'unlock_reason' => trim((string) $validated['reason']),
            ]);
        });

        $this->logGovernanceAction(auth()->user(), 'results_snapshot_admin_unlock', [
            'exam_year_id' => $examYear->id,
            'exam_year' => $examYear->year_label,
            'exam_type' => 'ACSEE',
            'snapshot_id' => $activeSnapshot->id,
            'snapshot_version' => $activeSnapshot->version,
            'scope' => $scope,
            'reason' => trim((string) $validated['reason']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Active snapshot unlocked successfully.',
        ]);
    }

    public function gradingConfig(Request $request): JsonResponse
    {
        $this->authorize('viewResults', CandidateResult::class);

        $examYearId = $request->integer('exam_year_id') ?: $this->resolveExamYearId($request);
        $examYear = $examYearId ? ExamYear::query()->find($examYearId) : null;

        $activeProfile = $this->activeProfile($examYearId);
        $latestProfile = GradingProfile::query()
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('exam_year_id', $examYearId)
            ->latest('version')
            ->first();
        $profile = $activeProfile ?: $latestProfile;

        $resolved = $this->normalizeToNectaEffectiveConfig(
            $this->gradingService->resolveProfileConfig($profile)
        );
        $validation = $this->validateFullConfig($resolved);

        $lastAudit = GovernanceAuditLog::query()
            ->with('admin:id,name,email')
            ->where('data', 'like', '%results_grading_config_%')
            ->when($profile, fn ($q) => $q->where('data', 'like', '%"profile_id":' . $profile->id . '%'))
            ->latest('created_at')
            ->first();

        $warnings = $validation['warnings'];
        if (!$activeProfile && $latestProfile) {
            $warnings[] = 'No active config; showing latest saved.';
        }
        if (!$profile) {
            $warnings[] = 'No saved config found; showing service defaults.';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'meta' => [
                    'exam_type' => 'ACSEE',
                    'exam_year_id' => $examYearId,
                    'locked' => (bool) ($profile?->is_locked ?? false),
                    'active_config_id' => $activeProfile?->id,
                ],
                'permissions' => [
                    'can_edit' => auth()->user()->can('create', GradingProfile::class) || ($profile && auth()->user()->can('update', $profile)),
                    'can_activate' => $profile ? auth()->user()->can('update', $profile) : auth()->user()->can('create', GradingProfile::class),
                    'can_lock' => $profile ? auth()->user()->can('update', $profile) : false,
                    'can_preview' => auth()->user()->can('publishLock', CandidateResult::class),
                ],
                'grading' => [
                    'config' => [
                        'id' => $profile?->id,
                        'name' => $profile?->name ?? "ACSEE {$examYear?->year_label} Service Defaults",
                        'version' => $profile?->version ?? 0,
                        'is_active' => (bool) ($profile?->is_active ?? false),
                        'is_locked' => (bool) ($profile?->is_locked ?? false),
                        'updated_by' => $lastAudit?->admin?->name,
                        'updated_at' => $profile?->updated_at,
                        'last_modified_by' => $lastAudit?->admin?->name,
                        'last_modified_at' => $lastAudit?->created_at,
                        'description' => $profile?->description,
                    ],
                    'rules' => $resolved['grading_rules'],
                ],
                'gpa' => [
                    'settings' => $resolved['gpa_settings'],
                    'grade_points' => $resolved['gpa_grade_points'],
                ],
                'divisions' => [
                    'rules' => $resolved['division_rules'],
                ],
                'competence_levels' => [
                    'rules' => $resolved['competence_rules'],
                ],
                'warnings' => array_values(array_unique($warnings)),
            ],
        ]);
    }

    public function validateGradingConfig(Request $request): JsonResponse
    {
        $this->authorize('viewResults', CandidateResult::class);
        $examYearId = $request->integer('exam_year_id') ?: $this->resolveExamYearId($request);
        $configId = $request->integer('config_id');

        $profile = $configId
            ? GradingProfile::query()->where('exam_type_id', $this->acseeExamTypeId())->find($configId)
            : ($this->activeProfile($examYearId)
                ?: GradingProfile::query()
                    ->where('exam_type_id', $this->acseeExamTypeId())
                    ->where('exam_year_id', $examYearId)
                    ->latest('version')
                    ->first());

        $resolved = $this->normalizeToNectaEffectiveConfig(
            $this->gradingService->resolveProfileConfig($profile)
        );
        $validation = $this->validateFullConfig($resolved);

        return response()->json([
            'success' => empty($validation['errors']),
            'data' => $validation,
        ], empty($validation['errors']) ? 200 : 422);
    }

    public function validateGradingSetup(ValidateGradingSetupRequest $request): JsonResponse
    {
        $this->authorize('create', GradingProfile::class);

        $rules = collect($request->validated('rules'))->values()->map(function (array $row) {
            return [
                'grade' => $row['grade'],
                'name' => $row['grade_name'] ?? $row['grade'],
                'min_mark' => $row['min_percentage'],
                'max_mark' => $row['max_percentage'],
                'points' => $row['points'] ?? null,
                'is_disabled' => false,
            ];
        })->toArray();

        $payload = [
            'grading_rules' => $rules,
            'gpa_settings' => $this->gradingService->getDefaultGpaSettings(),
            'gpa_grade_points' => collect($this->gradingService->getGradePointsMapping())->map(fn ($v, $k) => ['grade' => $k, 'gpa_point_value' => $v])->values()->toArray(),
            'division_rules' => collect($this->gradingService->getDivisionBoundaries())->map(fn ($r, $i) => ['division_label' => (string) $r['division'], 'min_points' => $r['min'], 'max_points' => $r['max'], 'notes' => $r['competence'], 'sort_order' => $i, 'is_disabled' => false])->toArray(),
            'competence_rules' => collect($this->gradingService->getGpaCompetenceBoundaries())->map(fn ($r, $i) => ['level_label' => $r['competence'], 'min_value' => $r['min'], 'max_value' => $r['max'], 'basis' => 'GPA', 'color_code' => $r['color'], 'sort_order' => $i, 'is_disabled' => false])->toArray(),
        ];
        $analysis = $this->validateFullConfig($payload);

        return response()->json([
            'success' => empty($analysis['errors']),
            'data' => $analysis,
        ], empty($analysis['errors']) ? 200 : 422);
    }

    public function saveGradingConfig(SaveGradingConfigRequest $request): JsonResponse
    {
        return $this->persistGradingConfig($request->validated());
    }

    private function persistGradingConfig(array $validated): JsonResponse
    {
        $examYear = ExamYear::query()->findOrFail($validated['exam_year_id']);
        if ($examYear->published_at) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify grading configuration after results are published for this year.',
            ], 422);
        }
        $acseeExamTypeId = $this->acseeExamTypeId();

        $profile = null;
        $isCreate = empty($validated['config_id']);
        if ($isCreate) {
            $this->authorize('create', GradingProfile::class);
        } else {
            $profile = GradingProfile::query()->findOrFail($validated['config_id']);
            if ((int) $profile->exam_type_id !== (int) $acseeExamTypeId) {
                return response()->json(['success' => false, 'message' => 'Invalid grading profile for ACSEE.'], 422);
            }
            $this->authorize('update', $profile);
        }

        if ($profile?->is_locked) {
            return response()->json(['success' => false, 'message' => 'Locked grading profile cannot be edited.'], 403);
        }

        $analysis = $this->validateFullConfig([
            'grading_rules' => $validated['grading_rules'],
            'gpa_settings' => $validated['gpa_settings'],
            'gpa_grade_points' => $validated['gpa_grade_points'],
            'division_rules' => $validated['division_rules'],
            'competence_rules' => $validated['competence_rules'],
        ]);
        if (!empty($analysis['errors'])) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'data' => $analysis], 422);
        }

        DB::transaction(function () use (&$profile, $validated, $examYear, $acseeExamTypeId, $isCreate) {
            if ($isCreate) {
                $version = ((int) GradingProfile::query()
                    ->where('exam_type_id', $acseeExamTypeId)
                    ->where('exam_year_id', $examYear->id)
                    ->max('version')) + 1;

                $profile = GradingProfile::query()->create([
                    'exam_type_id' => $acseeExamTypeId,
                    'exam_year_id' => $examYear->id,
                    'code' => $this->generateProfileCode((int) $examYear->year_label, $version),
                    'name' => $validated['name'] ?? "ACSEE {$examYear->year_label} Profile",
                    'description' => $validated['description'] ?? null,
                    'version' => $version,
                    'is_active' => false,
                    'is_locked' => false,
                    'grade_boundaries' => $this->toGradeBoundariesPayloadFromConfig($validated['grading_rules']),
                    'gpa_mapping' => [
                        'settings' => $validated['gpa_settings'],
                        'grade_points' => array_values($validated['gpa_grade_points']),
                    ],
                    'competence_levels' => [
                        'rules' => array_values($validated['competence_rules']),
                        'division_rules' => array_values($validated['division_rules']),
                    ],
                ]);
            } else {
                $profile->update([
                    'exam_year_id' => $examYear->id,
                    'name' => $validated['name'] ?? $profile->name,
                    'description' => $validated['description'] ?? $profile->description,
                    'grade_boundaries' => $this->toGradeBoundariesPayloadFromConfig($validated['grading_rules']),
                    'gpa_mapping' => [
                        'settings' => $validated['gpa_settings'],
                        'grade_points' => array_values($validated['gpa_grade_points']),
                    ],
                    'competence_levels' => [
                        'rules' => array_values($validated['competence_rules']),
                        'division_rules' => array_values($validated['division_rules']),
                    ],
                ]);
            }

            $existingRules = $profile->gradingRules()->get()->keyBy('id');
            $seenIds = [];
            foreach (collect($validated['grading_rules'])->values() as $idx => $row) {
                $ruleData = [
                    'grade' => strtoupper((string) $row['grade']),
                    'grade_name' => $row['name'] ?? strtoupper((string) $row['grade']),
                    'min_percentage' => $row['min_mark'],
                    'max_percentage' => $row['max_mark'],
                    'points' => $row['points'] ?? $this->gradingService->getGradePoints(strtoupper((string) $row['grade'])),
                    'is_principal' => (bool) ($row['is_principal'] ?? false),
                    'is_subsidiary' => (bool) ($row['is_subsidiary'] ?? false),
                    'sort_order' => $row['sort_order'] ?? $idx,
                ];
                if ($this->hasColumn('grading_rules', 'is_disabled')) {
                    $ruleData['is_disabled'] = (bool) ($row['is_disabled'] ?? false);
                }

                if (!empty($row['id']) && $existingRules->has((int) $row['id'])) {
                    $rule = $existingRules[(int) $row['id']];
                    $rule->update($ruleData);
                    $seenIds[] = $rule->id;
                } else {
                    $created = $profile->gradingRules()->create($ruleData);
                    $seenIds[] = $created->id;
                }
            }

            if ($this->hasColumn('grading_rules', 'is_disabled')) {
                $profile->gradingRules()->whereNotIn('id', $seenIds)->update(['is_disabled' => true]);
            }
        });

        $this->logGovernanceAction(auth()->user(), $isCreate ? 'results_grading_config_save_created' : 'results_grading_config_save_updated', [
            'profile_id' => $profile->id,
            'exam_year_id' => $examYear->id,
            'exam_year' => $examYear->year_label,
            'exam_type' => 'ACSEE',
            'grading_rules_count' => count($validated['grading_rules']),
            'division_rules_count' => count($validated['division_rules']),
            'competence_rules_count' => count($validated['competence_rules']),
        ]);

        return $this->gradingConfig(new Request(['exam_year_id' => $examYear->id]));
    }

    public function upsertGradingConfig(UpsertGradingConfigRequest $request): JsonResponse
    {
        $mapped = [
            'exam_year_id' => $request->validated('exam_year_id'),
            'config_id' => $request->validated('id'),
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'grading_rules' => collect($request->validated('rules'))->map(function (array $row) {
                return [
                    'id' => $row['id'] ?? null,
                    'grade' => $row['grade'],
                    'name' => $row['grade_name'] ?? $row['grade'],
                    'min_mark' => $row['min_percentage'],
                    'max_mark' => $row['max_percentage'],
                    'points' => $row['points'] ?? null,
                    'is_principal' => (bool) ($row['is_principal'] ?? false),
                    'is_subsidiary' => (bool) ($row['is_subsidiary'] ?? false),
                    'sort_order' => $row['sort_order'] ?? null,
                    'is_disabled' => false,
                ];
            })->values()->toArray(),
            'gpa_settings' => $this->gradingService->getDefaultGpaSettings(),
            'gpa_grade_points' => collect($this->gradingService->getGradePointsMapping())->map(fn ($v, $k) => ['grade' => $k, 'gpa_point_value' => $v])->values()->toArray(),
            'division_rules' => collect($this->gradingService->getDivisionBoundaries())->map(fn ($r, $i) => ['division_label' => (string) $r['division'], 'min_points' => $r['min'], 'max_points' => $r['max'], 'sort_order' => $i, 'notes' => $r['competence'], 'is_disabled' => false])->toArray(),
            'competence_rules' => collect($this->gradingService->getGpaCompetenceBoundaries())->map(fn ($r, $i) => ['level_label' => $r['competence'], 'min_value' => $r['min'], 'max_value' => $r['max'], 'basis' => 'GPA', 'color_code' => $r['color'], 'sort_order' => $i, 'is_disabled' => false])->toArray(),
        ];

        return $this->persistGradingConfig($mapped);
    }

    public function activateGradingConfig(Request $request): JsonResponse
    {
        $request->validate([
            'profile_id' => ['required', 'integer', 'exists:grading_profiles,id'],
        ]);

        $profile = GradingProfile::query()->findOrFail((int) $request->input('profile_id'));
        $this->authorize('update', $profile);

        if ($profile->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Locked grading profile cannot be activated.',
            ], 422);
        }

        if ($profile->examYear?->published_at) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot activate a profile for a published exam year.',
            ], 422);
        }

        GradingProfile::query()
            ->where('exam_type_id', $profile->exam_type_id)
            ->where('exam_year_id', $profile->exam_year_id)
            ->update(['is_active' => false]);

        $profile->update(['is_active' => true]);

        $this->logGovernanceAction(auth()->user(), 'results_grading_config_activated', [
            'profile_id' => $profile->id,
            'exam_year_id' => $profile->exam_year_id,
            'exam_type' => 'ACSEE',
        ]);

        return response()->json(['success' => true, 'message' => 'Grading configuration activated.']);
    }

    public function lockGradingConfig(Request $request): JsonResponse
    {
        $request->validate([
            'profile_id' => ['required', 'integer', 'exists:grading_profiles,id'],
            'reason' => ['required', 'string', 'min:3'],
        ]);

        $profile = GradingProfile::query()->findOrFail((int) $request->input('profile_id'));
        $this->authorize('update', $profile);

        if ($profile->is_locked) {
            return response()->json([
                'success' => true,
                'message' => 'Grading profile already locked.',
            ]);
        }

        $profile->update([
            'is_locked' => true,
            'locked_at' => now(),
            'locked_by_id' => auth()->id(),
        ]);

        $this->logGovernanceAction(auth()->user(), 'results_grading_config_locked', [
            'profile_id' => $profile->id,
            'exam_year_id' => $profile->exam_year_id,
            'exam_type' => 'ACSEE',
            'reason' => trim((string) $request->input('reason')),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Grading configuration locked.',
        ]);
    }

    public function gradingChangeLog(Request $request): JsonResponse
    {
        $this->authorize('viewResults', CandidateResult::class);

        $examYearId = $request->integer('exam_year_id');
        $items = GovernanceAuditLog::query()
            ->with('admin:id,name,email')
            ->where('data', 'like', '%results_grading_config_%')
            ->when($examYearId, fn ($q) => $q->where('data', 'like', '%"exam_year_id":' . $examYearId . '%'))
            ->latest('created_at')
            ->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function rulesNotes(Request $request): JsonResponse
    {
        $this->extendExecutionWindow();
        $this->authorize('viewResults', CandidateResult::class);

        return response()->json([
            'success' => true,
            'data' => $this->gradingService->getAcseeRulesNotes(),
        ]);
    }

    private function extendExecutionWindow(int $seconds = 180): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        @set_time_limit($seconds);
        @ini_set('max_execution_time', (string) $seconds);
    }

    public function previewImpact(GradingPreviewImpactRequest $request): JsonResponse
    {
        $this->authorize('publishLock', CandidateResult::class);

        $validated = $request->validated();
        $examYear = ExamYear::query()->findOrFail((int) $validated['exam_year_id']);

        $scope = $validated['scope'] ?? [];
        $profile = !empty($validated['config_id'])
            ? GradingProfile::query()
                ->where('exam_type_id', $this->acseeExamTypeId())
                ->where('exam_year_id', $examYear->id)
                ->find($validated['config_id'])
            : $this->activeProfile($examYear->id);
        if (!$profile) {
            $profile = GradingProfile::query()
                ->where('exam_type_id', $this->acseeExamTypeId())
                ->where('exam_year_id', $examYear->id)
                ->latest('version')
                ->first();
        }
        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'No grading configuration found for the selected exam year.',
            ], 422);
        }
        $resolved = $this->normalizeToNectaEffectiveConfig(
            $this->gradingService->resolveProfileConfig($profile)
        );
        $analysis = $this->validateFullConfig($resolved);
        if (!empty($analysis['errors'])) {
            return response()->json(['success' => false, 'message' => 'Config validation failed for dry run.', 'data' => $analysis], 422);
        }

        $candidateQuery = Candidate::query()
            ->whereHas('marks', function ($q) use ($examYear) {
                $q->where('exam_type_id', $this->acseeExamTypeId())
                    ->where('year', (int) $examYear->year_label);
            })
            ->select('id', 'school_id');

        $this->applyUserScopeToCandidates($candidateQuery, auth()->user());

        if (!empty($scope['region_id'])) {
            $candidateQuery->whereHas('school', fn (Builder $q) => $q->where('region_id', $scope['region_id']));
        }
        if (!empty($scope['council_id'])) {
            $candidateQuery->whereHas('school', fn (Builder $q) => $q->where('council_id', $scope['council_id']));
        }
        if (!empty($scope['school_id'])) {
            $candidateQuery->where('school_id', $scope['school_id']);
        }

        $sampleInput = strtoupper(trim((string) ($validated['sample_size'] ?? 'ALL')));
        if ($sampleInput !== 'ALL') {
            $candidateQuery->limit((int) $sampleInput);
        }

        $candidateIds = $candidateQuery->pluck('id');

        $writeAttempted = false;
        DB::listen(static function ($query) use (&$writeAttempted) {
            if (preg_match('/^\s*(insert|update|delete|replace|alter|drop|create|truncate)\b/i', $query->sql)) {
                $writeAttempted = true;
            }
        });

        $marks = SubjectMarks::query()
            ->with('subject:id,name')
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('year', (int) $examYear->year_label)
            ->whereIn('candidate_id', $candidateIds)
            ->get(['candidate_id', 'subject_id', 'marks_obtained', 'percentage', 'grade', 'subject_status']);

        $principalSelections = CandidateSubjectSelection::query()
            ->whereIn('candidate_id', $candidateIds)
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where(function ($q) use ($examYear) {
                $q->where('exam_year_id', $examYear->id)
                    ->orWhere('year', (int) $examYear->year_label);
            })
            ->where('is_active', true)
            ->where('is_principal', true)
            ->get(['candidate_id', 'subject_id'])
            ->groupBy('candidate_id')
            ->map(fn ($rows) => $rows->pluck('subject_id')->map(fn ($id) => (int) $id)->unique()->values()->all());

        $currentResults = CandidateResult::query()
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('year', (int) $examYear->year_label)
            ->whereIn('candidate_id', $candidateIds)
            ->get(['candidate_id', 'division', 'overall_grade', 'gpa', 'competence_level']);

        if ($writeAttempted) {
            return response()->json([
                'success' => false,
                'message' => 'Dry-run safety guard triggered: write operation detected.',
            ], 500);
        }

        $groupedMarks = $marks->groupBy('candidate_id');
        $currentByCandidate = $currentResults->keyBy('candidate_id');

        $simulatedDivisionDistribution = [];
        $simulatedGpaDistribution = [];
        $simulatedCompetenceDistribution = [];
        $currentDivisionDistribution = [];
        $changedGpa = 0;
        $changedCompetence = 0;
        $changedDivisions = 0;
        $upgraded = 0;
        $downgraded = 0;

        foreach ($groupedMarks as $candidateId => $candidateMarks) {
            $totalPoints = 0.0;
            $gpaPoints = 0.0;
            $validSubjectCount = 0;
            $subjectGrades = [];

            foreach ($candidateMarks as $mark) {
                $status = strtoupper((string) ($mark->subject_status ?? ''));
                if (in_array($status, ['INC', 'X', 'ABS'], true)) {
                    continue;
                }

                $value = $mark->marks_obtained ?? $mark->percentage;
                if ($value === null) {
                    continue;
                }

                $grade = $this->gradingService->calculateGradeWithRules((float) $value, $resolved['grading_rules']);

                $subjectName = (string) ($mark->subject->name ?? '');
                if ($this->gradingService->isExcludedSubject($subjectName)) {
                    continue;
                }

                $points = $this->gradingService->getGradePointsWithMapping($grade, $resolved['gpa_grade_points']);
                $gpaPoints += $points;
                $validSubjectCount++;
                $subjectGrades[] = [
                    'subject_id' => $mark->subject_id,
                    'subject_name' => $subjectName,
                    'grade' => $grade,
                    'points' => $points,
                ];
            }

            $coreSubjectIds = $principalSelections->get($candidateId);
            $coreCompleteness = $this->gradingService->analyzeCoreSubjectCompleteness($subjectGrades, $coreSubjectIds);
            $aggtPoints = $this->gradingService->calculateAggtFromSubjectGrades($subjectGrades, $coreSubjectIds);
            $totalPoints = $aggtPoints ?? 0.0;
            $principalPassCount = $this->gradingService->countPrincipalPassesFromSubjectGrades($subjectGrades, $coreSubjectIds);

            $roundingDecimals = (int) ($resolved['gpa_settings']['rounding_decimals'] ?? 2);
            $rawGpa = $validSubjectCount > 0 ? ($gpaPoints / $validSubjectCount) : 0.0;
            $simGpa = round($rawGpa, $roundingDecimals);

            if (!empty($coreSubjectIds) && !$coreCompleteness['is_complete']) {
                $simDivision = 0;
                $simGpa = 0.0;
                $simCompetence = 'Not Computed';
                $simGpaBucket = number_format($simGpa, 2);
            } else {
                $divisionInfo = $this->gradingService->calculateDivisionWithRulesAndEligibility(
                    (float) $totalPoints,
                    (int) $principalPassCount,
                    $resolved['division_rules']
                );
                $simDivision = (int) ($divisionInfo['division'] ?? 0);
                $competenceInfo = $this->gradingService->getCompetenceByBasis($simGpa, 'GPA', $resolved['competence_rules']);
                $simCompetence = (string) ($competenceInfo['level_label'] ?? 'Unknown');
                $simGpaBucket = number_format($simGpa, 2);
            }

            $simulatedDivisionDistribution[$simDivision] = ($simulatedDivisionDistribution[$simDivision] ?? 0) + 1;
            $simulatedGpaDistribution[$simGpaBucket] = ($simulatedGpaDistribution[$simGpaBucket] ?? 0) + 1;
            $simulatedCompetenceDistribution[$simCompetence] = ($simulatedCompetenceDistribution[$simCompetence] ?? 0) + 1;

            $currentDivision = $this->normalizeDivision($currentByCandidate[$candidateId]->division ?? null);
            $currentGpa = (float) ($currentByCandidate[$candidateId]->gpa ?? -1);
            $currentCompetence = (string) ($currentByCandidate[$candidateId]->competence_level ?? '');

            if ($currentDivision !== null) {
                $currentDivisionDistribution[$currentDivision] = ($currentDivisionDistribution[$currentDivision] ?? 0) + 1;
            }

            if ($currentDivision !== null && $currentDivision !== $simDivision) {
                $changedDivisions++;
                if ($this->isDivisionUpgrade($currentDivision, $simDivision)) {
                    $upgraded++;
                } elseif ($this->isDivisionDowngrade($currentDivision, $simDivision)) {
                    $downgraded++;
                }
            }

            if ($currentGpa >= 0 && abs($currentGpa - $simGpa) > 0.0001) {
                $changedGpa++;
            }
            if ($currentCompetence !== '' && strcasecmp($currentCompetence, $simCompetence) !== 0) {
                $changedCompetence++;
            }
        }

        ksort($simulatedDivisionDistribution);
        ksort($simulatedGpaDistribution);
        ksort($simulatedCompetenceDistribution);
        ksort($currentDivisionDistribution);

        return response()->json([
            'success' => true,
            'data' => [
                'total_candidates_simulated' => $groupedMarks->count(),
                'simulated_division_distribution' => $simulatedDivisionDistribution,
                'simulated_gpa_distribution' => $simulatedGpaDistribution,
                'simulated_competence_distribution' => $simulatedCompetenceDistribution,
                'comparison_with_current' => [
                    'changed_divisions_count' => $changedDivisions,
                    'changed_gpa_count' => $changedGpa,
                    'changed_competence_count' => $changedCompetence,
                    'upgraded_count' => $upgraded,
                    'downgraded_count' => $downgraded,
                ],
                'validation_warnings' => array_merge(
                    $analysis['warnings'],
                    ['This is a preview only. No data has been written.']
                ),
            ],
        ]);
    }

    private function scopedCandidateResults(Request $request, ?int $year = null, bool $latestDraftPerCandidate = false): Builder
    {
        $resolvedExamYearId = $request->integer('exam_year_id') ?: $this->resolveExamYearId(new Request(['year' => $year ?? $this->resolveYear($request)]));
        $scope = $this->resolveScopeFromRequest($request, true);
        $preferDraft = strtolower((string) $request->input('mode', '')) === 'draft';
        $activeSnapshot = null;

        if (!$preferDraft) {
            $activeSnapshot = $this->activeSnapshot($resolvedExamYearId, $scope['scope_type'], $scope['scope_id']);

            if (!$activeSnapshot && $scope['scope_type'] !== 'national') {
                $activeSnapshot = $this->activeSnapshot($resolvedExamYearId, 'national', null);
            }
        }

        $query = CandidateResult::query()
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('year', $year ?? $this->resolveYear($request));

        if ($this->hasColumn('candidate_results', 'snapshot_id')) {
            if ($activeSnapshot) {
                $query->where('snapshot_id', $activeSnapshot->id);
            } else {
                if ($latestDraftPerCandidate) {
                    $query->whereNull('snapshot_id');
                    $query->whereIn('candidate_results.id', $this->latestDraftCandidateResultIdsSubquery($request, $year ?? $this->resolveYear($request)));
                } else {
                $latestProcessId = null;
                if ($this->hasColumn('candidate_results', 'process_id') && $resolvedExamYearId) {
                    $examYear = ExamYear::query()->find($resolvedExamYearId);
                    if ($examYear) {
                        $latestProcessId = $this->latestProcessForScope($examYear, $scope, 'completed')?->id;

                        if (!$latestProcessId && $scope['scope_type'] !== 'national') {
                            $latestProcessId = $this->latestProcessForScope($examYear, ['scope_type' => 'national', 'scope_id' => null], 'completed')?->id;
                        }
                    }
                }

                if ($latestProcessId) {
                    $query->where(function (Builder $q) use ($latestProcessId) {
                        $q->where('process_id', $latestProcessId)
                            ->whereNull('snapshot_id');
                    });
                }
                }
            }
        }

        $user = auth()->user();
        $this->applyUserScope($query, $user);

        if ($request->filled('region_id')) {
            $query->whereHas('candidate.school', fn (Builder $q) => $q->where('region_id', $request->integer('region_id')));
        }

        if ($request->filled('district_id')) {
            $query->whereHas('candidate.school', fn (Builder $q) => $q->where('district_id', $request->integer('district_id')));
        }

        if ($request->filled('school_id')) {
            $query->whereHas('candidate', fn (Builder $q) => $q->where('school_id', $request->integer('school_id')));
        }

        return $query;
    }

    private function latestDraftCandidateResultIdsSubquery(Request $request, int $year): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('candidate_results as latest_results')
            ->join('candidates as latest_candidates', 'latest_results.candidate_id', '=', 'latest_candidates.id')
            ->leftJoin('schools as latest_schools', 'latest_candidates.school_id', '=', 'latest_schools.id')
            ->where('latest_results.exam_type_id', $this->acseeExamTypeId())
            ->where('latest_results.year', $year)
            ->when($this->hasColumn('candidate_results', 'snapshot_id'), fn ($q) => $q->whereNull('latest_results.snapshot_id'))
            ->selectRaw('MAX(latest_results.id)')
            ->groupBy('latest_results.candidate_id');

        $this->applyScopeFiltersToCandidateJoinQuery($query, $request, 'latest_candidates', 'latest_schools');

        return $query;
    }

    private function finalGradesForExport(Request $request, $candidateIds, int $examTypeId, int $yearValue, string $mode): Builder
    {
        $query = FinalGrade::query()
            ->where('exam_type_id', $examTypeId)
            ->where('year', $yearValue)
            ->whereIn('candidate_id', $candidateIds);

        $published = strtolower($mode) === 'published';
        if ($published && $this->hasColumn('final_grades', 'snapshot_id')) {
            $scope = $this->resolveScopeFromRequest($request, true);
            $activeSnapshot = $this->activeSnapshot($request->integer('exam_year_id'), $scope['scope_type'], $scope['scope_id']);
            if (!$activeSnapshot && $scope['scope_type'] !== 'national') {
                $activeSnapshot = $this->activeSnapshot($request->integer('exam_year_id'), 'national', null);
            }

            if ($activeSnapshot) {
                return $query->where('snapshot_id', $activeSnapshot->id);
            }
        }

        if (strtolower($mode) === 'draft') {
            if ($this->hasColumn('final_grades', 'snapshot_id')) {
                $query->whereNull('snapshot_id');
            }

            return $query->whereIn('final_grades.id', $this->latestDraftFinalGradeIdsSubquery($request, $yearValue, $candidateIds));
        }

        return $query;
    }

    private function latestDraftFinalGradeIdsSubquery(Request $request, int $year, $candidateIds): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('final_grades as latest_final_grades')
            ->join('candidates as latest_candidates', 'latest_final_grades.candidate_id', '=', 'latest_candidates.id')
            ->leftJoin('schools as latest_schools', 'latest_candidates.school_id', '=', 'latest_schools.id')
            ->where('latest_final_grades.exam_type_id', $this->acseeExamTypeId())
            ->where('latest_final_grades.year', $year)
            ->whereIn('latest_final_grades.candidate_id', $candidateIds)
            ->when($this->hasColumn('final_grades', 'snapshot_id'), fn ($q) => $q->whereNull('latest_final_grades.snapshot_id'))
            ->selectRaw('MAX(latest_final_grades.id)')
            ->groupBy('latest_final_grades.candidate_id');

        $this->applyScopeFiltersToCandidateJoinQuery($query, $request, 'latest_candidates', 'latest_schools');

        return $query;
    }

    private function applyScopeFiltersToCandidateJoinQuery($query, Request $request, string $candidateAlias, string $schoolAlias): void
    {
        $user = auth()->user();
        $role = $user?->role?->code;
        $scopeType = $user?->scope?->scope_type;
        $scopeId = $user?->scope?->scope_id;

        if ($this->isSchoolRole($role)) {
            $schoolId = $user?->school_id ?? ($scopeType === 'school' ? $scopeId : null);
            if ($schoolId) {
                $query->where("{$candidateAlias}.school_id", $schoolId);
            }
        } elseif ($this->isDistrictRole($role)) {
            $districtId = $user?->district_id ?? ($scopeType === 'district' ? $scopeId : null);
            if ($districtId) {
                $query->where("{$schoolAlias}.district_id", $districtId);
            }
        } elseif ($this->isRegionalRole($role)) {
            $regionId = $user?->region_id ?? ($scopeType === 'region' ? $scopeId : null);
            if ($regionId) {
                $query->where("{$schoolAlias}.region_id", $regionId);
            }
        }

        if ($request->filled('region_id')) {
            $query->where("{$schoolAlias}.region_id", $request->integer('region_id'));
        }

        if ($request->filled('district_id')) {
            $query->where("{$schoolAlias}.district_id", $request->integer('district_id'));
        }

        if ($request->filled('school_id')) {
            $query->where("{$candidateAlias}.school_id", $request->integer('school_id'));
        }
    }

    private function scopedReadyBatches(Request $request, ?int $year = null): Builder
    {
        $query = MarkImportBatch::query()
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('exam_year', $year ?? $this->resolveYear($request))
            ->where(function (Builder $q) {
                // Ready-for-compute batches are terminally accepted batches only.
                // Exclude submitted/awaiting moderation states to avoid inflated readiness counts.
                $q->whereIn('status', [
                    MarkImportBatch::STATUS_APPROVED,
                    MarkImportBatch::STATUS_LOCKED,
                    MarkImportBatch::STATUS_PROCESSED,
                ])
                ->orWhere(function (Builder $legacy) {
                    $legacy->whereNull('status')
                        ->whereIn('lifecycle_state', ['approved', 'locked', 'processed']);
                });
            });

        $user = auth()->user();
        $this->applyUserScopeToBatches($query, $user);

        if ($request->filled('region_id')) {
            $query->where('region_id', $request->integer('region_id'));
        }

        if ($request->filled('district_id')) {
            $query->where('district_id', $request->integer('district_id'));
        }

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->integer('school_id'));
        }

        return $query;
    }

    private function resultsCacheKey(string $name, Request $request, array $extra = []): string
    {
        $parts = [
            'v2',
            $name,
            'u:' . (string) (auth()->id() ?? 0),
            'y:' . (string) ($request->input('exam_year_id') ?? $this->resolveExamYearId($request)),
            'r:' . (string) ($request->input('region_id') ?? ''),
            'd:' . (string) ($request->input('district_id') ?? ''),
            's:' . (string) ($request->input('school_id') ?? ''),
        ];
        foreach ($extra as $k => $v) {
            $parts[] = $k . ':' . (string) $v;
        }
        return 'acsee:' . implode('|', $parts);
    }

    private function applyUserScope(Builder $query, User $user): void
    {
        $role = $user->role?->code;
        $scopeType = $user->scope?->scope_type;
        $scopeId = $user->scope?->scope_id;

        if ($this->isSchoolRole($role)) {
            $schoolId = $user->school_id ?? ($scopeType === 'school' ? $scopeId : null);
            if ($schoolId) {
                $query->whereHas('candidate', fn (Builder $q) => $q->where('school_id', $schoolId));
            }
            return;
        }

        if ($this->isDistrictRole($role) && $scopeType === 'district' && $scopeId) {
            $query->whereHas('candidate.school', fn (Builder $q) => $q->where('district_id', $scopeId));
            return;
        }

        if ($this->isRegionalRole($role) && $scopeType === 'region' && $scopeId) {
            $query->whereHas('candidate.school', fn (Builder $q) => $q->where('region_id', $scopeId));
        }
    }

    private function applyUserScopeToBatches(Builder $query, User $user): void
    {
        $role = $user->role?->code;
        $scopeType = $user->scope?->scope_type;
        $scopeId = $user->scope?->scope_id;

        if ($this->isSchoolRole($role)) {
            $schoolId = $user->school_id ?? ($scopeType === 'school' ? $scopeId : null);
            if ($schoolId) {
                $query->where('school_id', $schoolId);
            }
            return;
        }

        if ($this->isDistrictRole($role)) {
            $districtId = $user->district_id ?? ($scopeType === 'district' ? $scopeId : null);
            if ($districtId) {
                $query->where('district_id', $districtId);
            }
            return;
        }

        if ($this->isRegionalRole($role)) {
            $regionId = $user->region_id ?? ($scopeType === 'region' ? $scopeId : null);
            if ($regionId) {
                $query->where('region_id', $regionId);
            }
        }
    }

    private function applyUserScopeToCandidates(Builder $query, User $user): void
    {
        $role = $user->role?->code;
        $scopeType = $user->scope?->scope_type;
        $scopeId = $user->scope?->scope_id;

        if ($this->isSchoolRole($role)) {
            $schoolId = $user->school_id ?? ($scopeType === 'school' ? $scopeId : null);
            if ($schoolId) {
                $query->where('school_id', $schoolId);
            }
            return;
        }

        if ($this->isDistrictRole($role) && $scopeType === 'district' && $scopeId) {
            $query->whereHas('school', fn (Builder $q) => $q->where('district_id', $scopeId));
            return;
        }

        if ($this->isRegionalRole($role) && $scopeType === 'region' && $scopeId) {
            $query->whereHas('school', fn (Builder $q) => $q->where('region_id', $scopeId));
        }
    }

    private function isRegionalRole(?string $role): bool
    {
        return in_array($role, ['regional_admin', 'regional_officer'], true);
    }

    private function isDistrictRole(?string $role): bool
    {
        return in_array($role, ['district_admin', 'district_supervisor', 'district_data_entry_officer'], true);
    }

    private function isSchoolRole(?string $role): bool
    {
        return in_array($role, ['school_user', 'school_registrar'], true);
    }

    private function applyPublishedFilter(Builder $query): void
    {
        if ($this->hasColumn('candidate_results', 'is_published')) {
            $query->where('is_published', true);
            return;
        }

        if ($this->hasColumn('candidate_results', 'status')) {
            $query->where('status', 'RELEASED');
            return;
        }

        if ($this->hasColumn('candidate_results', 'released_at')) {
            $query->whereNotNull('released_at');
        }
    }

    private function resolveYear(Request $request): int
    {
        if ($request->filled('year')) {
            return (int) $request->input('year');
        }

        if ($request->filled('exam_year_id')) {
            $examYear = ExamYear::query()->find($request->integer('exam_year_id'));
            if ($examYear) {
                return (int) $examYear->year_label;
            }
        }

        $activeYear = ExamYear::query()->where('is_active', true)->first();

        return (int) ($activeYear?->year_label ?? now()->year);
    }

    private function resolveExamYearId(Request $request): ?int
    {
        if ($request->filled('exam_year_id')) {
            return $request->integer('exam_year_id');
        }

        $resolvedYear = $this->resolveYear($request);

        return (int) ExamYear::query()->where('year_label', (string) $resolvedYear)->value('id');
    }

    private function acseeExamTypeId(): int
    {
        return (int) ExamType::query()->where('code', 'ACSEE')->value('id');
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;

        if (!array_key_exists($key, $this->columnCache)) {
            $this->columnCache[$key] = Schema::hasColumn($table, $column);
        }

        return $this->columnCache[$key];
    }

    private function activeProfile(?int $examYearId): ?GradingProfile
    {
        if ($examYearId === null) {
            return null;
        }

        return GradingProfile::query()
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('exam_year_id', $examYearId)
            ->where('is_active', true)
            ->latest('version')
            ->first();
    }

    private function paperValues(SubjectMarks $mark): array
    {
        $values = [];
        foreach (['paper_1', 'paper_2', 'paper_3'] as $field) {
            $v = $mark->{$field} ?? null;
            if ($v === null || $v === '') {
                continue;
            }
            $values[$field] = (float) $v;
        }

        return $values;
    }

    private function requiredPaperCodes(?Subject $subject): array
    {
        if (!$subject) {
            return ['paper_1'];
        }

        $weights = $this->paperWeightsForSubject((int) $subject->id);
        if (!empty($weights)) {
            return collect($weights)
                ->filter(fn (array $row) => (bool) ($row['is_required'] ?? true))
                ->pluck('paper_code')
                ->filter(fn (string $code) => in_array($code, ['paper_1', 'paper_2', 'paper_3'], true))
                ->values()
                ->all();
        }

        if (!$subject) {
            return ['paper_1'];
        }

        $written = max(1, min(2, (int) ($subject->written_papers ?? 1)));
        $codes = $written === 2 ? ['paper_1', 'paper_2'] : ['paper_1'];
        if (!empty($subject->has_practical)) {
            $codes[] = 'paper_3';
        }

        return $codes;
    }

    private function expectedPaperCountFromSubject(?Subject $subject): int
    {
        if (!$subject) {
            return 1;
        }

        $written = max(1, min(2, (int) ($subject->written_papers ?? 1)));
        return $written + (!empty($subject->has_practical) ? 1 : 0);
    }

    private function paperWeightsForSubject(int $subjectId): array
    {
        if (array_key_exists($subjectId, $this->paperWeightCache)) {
            return $this->paperWeightCache[$subjectId];
        }

        if (!Schema::hasTable('subject_paper_weights')) {
            $this->paperWeightCache[$subjectId] = [];
            return [];
        }

        $rows = SubjectPaperWeight::query()
            ->where('subject_id', $subjectId)
            ->where('is_active', true)
            ->whereIn('paper_code', ['paper_1', 'paper_2', 'paper_3'])
            ->orderBy('paper_code')
            ->get(['paper_code', 'weight', 'max_mark', 'is_required'])
            ->map(fn ($r) => [
                'paper_code' => (string) $r->paper_code,
                'weight' => (float) ($r->weight ?? 1.0),
                'max_mark' => $this->paperMaxMark((string) $r->paper_code, $r->max_mark),
                'is_required' => (bool) ($r->is_required ?? true),
            ])
            ->values()
            ->all();

        $this->paperWeightCache[$subjectId] = $rows;
        return $rows;
    }

    private function normalizeSubjectMarkTo100(array $paperValues, ?Subject $subject): float
    {
        if (empty($paperValues)) {
            return 0.0;
        }

        if ($subject) {
            $weights = $this->paperWeightsForSubject((int) $subject->id);
            if (!empty($weights)) {
                $weightedSum = 0.0;
                $weightedMax = 0.0;
                foreach ($weights as $row) {
                    $paperCode = (string) ($row['paper_code'] ?? '');
                    if ($paperCode === '' || !array_key_exists($paperCode, $paperValues)) {
                        continue;
                    }

                    $weight = (float) ($row['weight'] ?? 1.0);
                    $maxMark = (float) ($row['max_mark'] ?? 100.0);
                    $mark = (float) $paperValues[$paperCode];

                    $weightedSum += ($mark * $weight);
                    $weightedMax += ($maxMark * $weight);
                }

                if ($weightedMax > 0) {
                    // ACSEE compute policy: normalized subject mark rounded to whole number.
                    return round(($weightedSum / $weightedMax) * 100.0, 0);
                }
            }
        }

        $weightedSum = 0.0;
        $weightedMax = 0.0;
        foreach ($paperValues as $paperCode => $mark) {
            $weight = 1.0;
            $maxMark = $this->paperMaxMark((string) $paperCode);
            $weightedSum += ((float) $mark * $weight);
            $weightedMax += ($maxMark * $weight);
        }

        if ($weightedMax <= 0) {
            return 0.0;
        }

        // ACSEE compute policy: normalized subject mark rounded to whole number.
        return round(($weightedSum / $weightedMax) * 100.0, 0);
    }

    private function paperMaxMark(string $paperCode, mixed $configuredMax = null): float
    {
        $canonical = $paperCode === 'paper_3' ? 50.0 : 100.0;
        if ($configuredMax === null || $configuredMax === '') {
            return $canonical;
        }

        $value = (float) $configuredMax;
        return $value > 0 ? $value : $canonical;
    }

    private function buildSubjectRow(
        SubjectMarks $mark,
        ?float $marksObtained,
        ?float $percentage,
        ?string $subjectStatus,
        ?string $grade,
        int $processId,
        Carbon $timestamp
    ): array {
        $row = [
            'candidate_id' => (int) $mark->candidate_id,
            'subject_id' => (int) $mark->subject_id,
            'exam_type_id' => (int) $mark->exam_type_id,
            'year' => (int) $mark->year,
            'paper_1' => $mark->paper_1,
            'paper_2' => $mark->paper_2,
            'paper_3' => $mark->paper_3,
            'marks_obtained' => $marksObtained,
            'percentage' => $percentage,
            'grade' => $grade,
            'updated_at' => $timestamp,
            'created_at' => $timestamp,
        ];

        if ($this->hasColumn('subject_marks', 'subject_status')) {
            $row['subject_status'] = $subjectStatus;
        }
        if ($this->hasColumn('subject_marks', 'process_id')) {
            $row['process_id'] = $processId;
        }
        if ($this->hasColumn('subject_marks', 'snapshot_id')) {
            $row['snapshot_id'] = null;
        }

        return $row;
    }

    private function resolveIrregularOverallStatus(array $statuses): string
    {
        $normalized = collect($statuses)
            ->map(fn ($s) => strtoupper((string) $s))
            ->filter()
            ->values()
            ->all();

        if (in_array('X', $normalized, true)) {
            return 'X';
        }
        if (in_array('ABS', $normalized, true)) {
            return 'ABS';
        }
        if (in_array('INC', $normalized, true)) {
            return 'INC';
        }

        return 'INC';
    }

    private function resolveScopeFromRequest(Request $request, bool $allowAdminNational = false): array
    {
        $user = auth()->user();
        $role = $user?->role?->code;

        $requestedScopeType = (string) $request->input('scope_type', '');
        $requestedScopeId = $request->input('scope_id');

        if ($requestedScopeType === '') {
            if ($request->filled('school_id')) {
                $requestedScopeType = 'school';
                $requestedScopeId = (int) $request->input('school_id');
            } elseif ($request->filled('district_id')) {
                $requestedScopeType = 'district';
                $requestedScopeId = (int) $request->input('district_id');
            } elseif ($request->filled('region_id')) {
                $requestedScopeType = 'region';
                $requestedScopeId = (int) $request->input('region_id');
            }
        }

        if (in_array($role, ['super_admin', 'admin'], true)) {
            if ($allowAdminNational && $requestedScopeType === '') {
                return ['scope_type' => 'national', 'scope_id' => null];
            }

            $scopeType = $requestedScopeType !== '' ? $requestedScopeType : 'national';
            return [
                'scope_type' => $scopeType,
                'scope_id' => $scopeType === 'national' ? null : ($requestedScopeId !== null ? (int) $requestedScopeId : null),
            ];
        }

        if ($this->isSchoolRole($role)) {
            return [
                'scope_type' => 'school',
                'scope_id' => $user->school_id ?? $user->scope?->scope_id,
            ];
        }

        if ($this->isDistrictRole($role)) {
            return [
                'scope_type' => 'district',
                'scope_id' => $user->district_id ?? $user->scope?->scope_id,
            ];
        }

        if ($this->isRegionalRole($role)) {
            return [
                'scope_type' => 'region',
                'scope_id' => $user->region_id ?? $user->scope?->scope_id,
            ];
        }

        return ['scope_type' => 'national', 'scope_id' => null];
    }

    private function latestProcessForScope(ExamYear $examYear, array $scope, ?string $status = null): ?ResultProcess
    {
        return ResultProcess::query()
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('exam_year_id', $examYear->id)
            ->when($status !== null, fn (Builder $q) => $q->where('status', $status))
            ->when($this->hasColumn('result_processes', 'scope_type'), fn (Builder $q) => $q->where('scope_type', $scope['scope_type']))
            ->when($this->hasColumn('result_processes', 'scope_id'), function (Builder $q) use ($scope) {
                if ($scope['scope_id'] === null) {
                    $q->whereNull('scope_id');
                    return;
                }
                $q->where('scope_id', $scope['scope_id']);
            })
            ->latest('id')
            ->first();
    }

    private function snapshotIsLocked(?ResultSnapshot $snapshot): bool
    {
        if (!$snapshot) {
            return false;
        }
        if ($this->hasColumn('result_snapshots', 'locked_at')) {
            return !is_null($snapshot->locked_at);
        }
        return false;
    }

    private function scopeCandidateIds(array $scope): array
    {
        $query = Candidate::query()->select('id');
        if ($scope['scope_type'] === 'school' && !empty($scope['scope_id'])) {
            $query->where('school_id', (int) $scope['scope_id']);
        } elseif ($scope['scope_type'] === 'district' && !empty($scope['scope_id'])) {
            $query->whereHas('school', fn (Builder $q) => $q->where('district_id', (int) $scope['scope_id']));
        } elseif ($scope['scope_type'] === 'region' && !empty($scope['scope_id'])) {
            $query->whereHas('school', fn (Builder $q) => $q->where('region_id', (int) $scope['scope_id']));
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function applyScopeToFinalTableQuery($query, string $table, array $scope): void
    {
        if ($scope['scope_type'] === 'national') {
            return;
        }

        $candidateColumn = "{$table}.candidate_id";
        if (!Schema::hasColumn($table, 'candidate_id')) {
            return;
        }
        $candidateIds = $this->scopeCandidateIds($scope);
        if (empty($candidateIds)) {
            $query->whereRaw('1=0');
            return;
        }
        $query->whereIn($candidateColumn, $candidateIds);
    }

    private function draftCountsByProcess(ExamYear $examYear, int $processId, array $scope): array
    {
        $candidate = DB::table('candidate_results')
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('year', (int) $examYear->year_label)
            ->where('process_id', $processId)
            ->when($this->hasColumn('candidate_results', 'snapshot_id'), fn ($q) => $q->whereNull('snapshot_id'));
        $finalGrades = DB::table('final_grades')
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('year', (int) $examYear->year_label)
            ->where('process_id', $processId)
            ->when($this->hasColumn('final_grades', 'snapshot_id'), fn ($q) => $q->whereNull('snapshot_id'));

        $this->applyScopeToFinalTableQuery($candidate, 'candidate_results', $scope);
        $this->applyScopeToFinalTableQuery($finalGrades, 'final_grades', $scope);
        $candidateIds = (clone $candidate)->pluck('candidate_id');

        $subject = DB::table('subject_marks')
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('year', (int) $examYear->year_label)
            ->when($this->hasColumn('subject_marks', 'snapshot_id'), fn ($q) => $q->whereNull('snapshot_id'))
            ->when($candidateIds->isNotEmpty(), fn ($q) => $q->whereIn('candidate_id', $candidateIds), fn ($q) => $q->whereRaw('1 = 0'));

        return [
            'candidates' => (int) $candidate->count(),
            'final_grades' => (int) $finalGrades->count(),
            'subject_marks' => (int) $subject->count(),
        ];
    }

    private function computeIrregularityBlockers(ExamYear $examYear, int $processId, array $scope): array
    {
        if (!$this->hasColumn('subject_marks', 'subject_status')) {
            return ['missing_core_data' => 0, 'inc_count' => 0, 'abs_count' => 0, 'x_count' => 0];
        }

        $candidateQuery = DB::table('candidate_results')
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('year', (int) $examYear->year_label)
            ->where('process_id', $processId)
            ->when($this->hasColumn('candidate_results', 'snapshot_id'), fn ($q) => $q->whereNull('snapshot_id'));
        $this->applyScopeToFinalTableQuery($candidateQuery, 'candidate_results', $scope);
        $candidateIds = $candidateQuery->pluck('candidate_id');

        $query = DB::table('subject_marks')
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('year', (int) $examYear->year_label)
            ->when($this->hasColumn('subject_marks', 'snapshot_id'), fn ($q) => $q->whereNull('snapshot_id'))
            ->when($candidateIds->isNotEmpty(), fn ($q) => $q->whereIn('candidate_id', $candidateIds), fn ($q) => $q->whereRaw('1 = 0'));

        $inc = (clone $query)->where('subject_status', 'INC')->count();
        $abs = (clone $query)->where('subject_status', 'ABS')->count();
        $x = (clone $query)->where('subject_status', 'X')->count();
        return [
            'missing_core_data' => (int) $inc,
            'inc_count' => (int) $inc,
            'abs_count' => (int) $abs,
            'x_count' => (int) $x,
        ];
    }

    private function recentSubmissionActions(int $examYearId, array $scope): array
    {
        $logs = GovernanceAuditLog::query()
            ->with('admin:id,name,email')
            ->latest('created_at')
            ->limit(80)
            ->get();

        $allowedActions = [
            'results_snapshot_publish',
            'results_snapshot_lock',
            'results_snapshot_admin_unlock',
            'results_publish_lock',
            'results_admin_unlock',
        ];

        return $logs
            ->map(function (GovernanceAuditLog $log) {
                $payload = is_array($log->data) ? $log->data : [];
                return [
                    'time' => $log->created_at?->toISOString(),
                    'actor' => $log->admin?->name ?? $log->user?->name ?? 'System',
                    'action' => (string) data_get($payload, 'action', ''),
                    'snapshot_version' => data_get($payload, 'snapshot_version'),
                    'scope_type' => data_get($payload, 'scope.scope_type'),
                    'scope_id' => data_get($payload, 'scope.scope_id'),
                    'reason' => data_get($payload, 'reason') ?? data_get($payload, 'notes'),
                    'exam_year_id' => data_get($payload, 'exam_year_id'),
                ];
            })
            ->filter(function (array $row) use ($examYearId, $scope, $allowedActions) {
                if (!in_array($row['action'], $allowedActions, true)) {
                    return false;
                }
                if ((int) ($row['exam_year_id'] ?? 0) !== $examYearId) {
                    return false;
                }
                if ($scope['scope_type'] !== 'national') {
                    return $row['scope_type'] === $scope['scope_type']
                        && (int) ($row['scope_id'] ?? 0) === (int) ($scope['scope_id'] ?? 0);
                }
                return true;
            })
            ->take(20)
            ->values()
            ->all();
    }

    private function copyDraftRowsToSnapshot(string $table, ExamYear $examYear, ResultProcess $process, ResultSnapshot $snapshot, array $scope): int
    {
        if ($table === 'subject_marks') {
            $candidateQuery = DB::table('candidate_results')
                ->where('candidate_results.exam_type_id', $this->acseeExamTypeId())
                ->where('candidate_results.year', (int) $examYear->year_label)
                ->where('candidate_results.process_id', $process->id)
                ->when($this->hasColumn('candidate_results', 'snapshot_id'), fn ($q) => $q->whereNull('candidate_results.snapshot_id'));
            $this->applyScopeToFinalTableQuery($candidateQuery, 'candidate_results', $scope);
            $candidateIds = $candidateQuery->pluck('candidate_id');
            if ($candidateIds->isEmpty()) {
                return 0;
            }

            $latestRows = DB::table('subject_marks as sm')
                ->selectRaw('MAX(sm.id) as id')
                ->where('sm.exam_type_id', $this->acseeExamTypeId())
                ->where('sm.year', (int) $examYear->year_label)
                ->whereIn('sm.candidate_id', $candidateIds)
                ->when($this->hasColumn('subject_marks', 'snapshot_id'), fn ($q) => $q->whereNull('sm.snapshot_id'))
                ->groupBy('sm.candidate_id', 'sm.exam_type_id', 'sm.subject_id', 'sm.year');

            $rows = DB::table('subject_marks')
                ->joinSub($latestRows, 'latest_subject_marks', function ($join) {
                    $join->on('subject_marks.id', '=', 'latest_subject_marks.id');
                })
                ->orderBy('subject_marks.id')
                ->select('subject_marks.*')
                ->get();
        } else {
            $query = DB::table($table)
                ->where("{$table}.exam_type_id", $this->acseeExamTypeId())
                ->where("{$table}.year", (int) $examYear->year_label)
                ->where("{$table}.process_id", $process->id)
                ->when($this->hasColumn($table, 'snapshot_id'), fn ($q) => $q->whereNull("{$table}.snapshot_id"));
            $this->applyScopeToFinalTableQuery($query, $table, $scope);
            $rows = $query->orderBy("{$table}.id")->get();
        }

        if ($rows->isEmpty()) {
            return 0;
        }

        $now = now();
        $count = 0;
        $chunks = $rows->chunk(500);
        foreach ($chunks as $chunk) {
            $insertRows = [];
            foreach ($chunk as $row) {
                $payload = (array) $row;
                unset($payload['id']);
                if ($this->hasColumn($table, 'snapshot_id')) {
                    $payload['snapshot_id'] = $snapshot->id;
                }
                if ($table === 'candidate_results') {
                    if ($this->hasColumn($table, 'is_published')) $payload['is_published'] = true;
                    if ($this->hasColumn($table, 'published_at')) $payload['published_at'] = $now;
                    if ($this->hasColumn($table, 'status')) $payload['status'] = 'RELEASED';
                    if ($this->hasColumn($table, 'released_at')) $payload['released_at'] = $now;
                    if ($this->hasColumn($table, 'is_locked')) $payload['is_locked'] = false;
                    if ($this->hasColumn($table, 'locked_at')) $payload['locked_at'] = null;
                }
                if ($table === 'final_grades') {
                    if ($this->hasColumn($table, 'is_published')) $payload['is_published'] = true;
                    if ($this->hasColumn($table, 'published_at')) $payload['published_at'] = $now;
                    if ($this->hasColumn($table, 'is_locked')) $payload['is_locked'] = false;
                    if ($this->hasColumn($table, 'locked_at')) $payload['locked_at'] = null;
                }
                $payload['created_at'] = $now;
                $payload['updated_at'] = $now;
                $insertRows[] = $payload;
            }
            DB::table($table)->insert($insertRows);
            $count += count($insertRows);
        }

        return $count;
    }

    private function activeSnapshot(?int $examYearId, string $scopeType = 'national', mixed $scopeId = null): ?ResultSnapshot
    {
        if (!$examYearId) {
            return null;
        }

        return ResultSnapshot::query()
            ->where('exam_type', 'ACSEE')
            ->where('exam_year_id', $examYearId)
            ->when($this->hasColumn('result_snapshots', 'scope_type'), fn (Builder $q) => $q->where('scope_type', $scopeType))
            ->when($this->hasColumn('result_snapshots', 'scope_id'), function (Builder $q) use ($scopeId) {
                if ($scopeId === null) {
                    $q->whereNull('scope_id');
                    return;
                }
                $q->where('scope_id', (int) $scopeId);
            })
            ->where('is_active', true)
            ->latest('id')
            ->first();
    }

    private function buildResultStatsPayload(ExamYear $examYear, ?int $processId, ?int $snapshotId): array
    {
        $candidateQuery = CandidateResult::query()
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('year', (int) $examYear->year_label);

        if ($snapshotId && $this->hasColumn('candidate_results', 'snapshot_id')) {
            $candidateQuery->where('snapshot_id', $snapshotId);
        } elseif ($processId && $this->hasColumn('candidate_results', 'process_id') && $this->hasColumn('candidate_results', 'snapshot_id')) {
            $candidateQuery->where('process_id', $processId)->whereNull('snapshot_id');
        }

        $finalQuery = FinalGrade::query()
            ->where('exam_type_id', $this->acseeExamTypeId())
            ->where('year', (int) $examYear->year_label);
        if ($snapshotId && $this->hasColumn('final_grades', 'snapshot_id')) {
            $finalQuery->where('snapshot_id', $snapshotId);
        } elseif ($processId && $this->hasColumn('final_grades', 'process_id') && $this->hasColumn('final_grades', 'snapshot_id')) {
            $finalQuery->where('process_id', $processId)->whereNull('snapshot_id');
        }

        $completeCandidateQuery = clone $candidateQuery;
        if ($this->hasColumn('candidate_results', 'result_status')) {
            $completeCandidateQuery->where('result_status', 'COMPLETE');
        }

        $divisionCounts = [];
        if ($this->hasColumn('candidate_results', 'division')) {
            $divisionCounts = (clone $completeCandidateQuery)
                ->selectRaw('division, COUNT(*) as total')
                ->groupBy('division')
                ->pluck('total', 'division')
                ->toArray();
        } elseif ($this->hasColumn('final_grades', 'division')) {
            $divisionCounts = (clone $finalQuery)
                ->selectRaw('division, COUNT(*) as total')
                ->whereNotNull('division')
                ->groupBy('division')
                ->pluck('total', 'division')
                ->toArray();
        }

        $meanAggt = null;
        if ($this->hasColumn('candidate_results', 'grade_points')) {
            $meanAggt = (float) ((clone $completeCandidateQuery)->avg('grade_points') ?? 0);
        } elseif ($this->hasColumn('final_grades', 'grading_breakdown')) {
            $aggtValues = (clone $finalQuery)
                ->whereNotNull('grading_breakdown')
                ->pluck('grading_breakdown')
                ->map(function ($payload) {
                    $decoded = is_array($payload) ? $payload : json_decode((string) $payload, true);
                    $value = data_get($decoded, 'aggt_points');
                    return is_numeric($value) ? (float) $value : null;
                })
                ->filter(fn ($v) => $v !== null)
                ->values();
            if ($aggtValues->isNotEmpty()) {
                $meanAggt = (float) round($aggtValues->avg(), 2);
            }
        }

        $meanGpa = null;
        if ($this->hasColumn('candidate_results', 'gpa')) {
            $meanGpa = (float) ((clone $completeCandidateQuery)->avg('gpa') ?? 0);
        } elseif ($this->hasColumn('final_grades', 'gpa')) {
            $meanGpa = (float) ((clone $finalQuery)->avg('gpa') ?? 0);
        }

        $candidateIds = (clone $candidateQuery)->pluck('candidate_id');
        $schoolsCount = Candidate::query()->whereIn('id', $candidateIds)->distinct('school_id')->count('school_id');

        if ($snapshotId && $this->hasColumn('subject_marks', 'snapshot_id')) {
            $subjectQuery = SubjectMarks::query()
                ->where('subject_marks.exam_type_id', $this->acseeExamTypeId())
                ->where('subject_marks.year', (int) $examYear->year_label)
                ->where('snapshot_id', $snapshotId)
                ->whereIn('subject_marks.candidate_id', $candidateIds);
            $subjectGradeDistribution = $subjectQuery
                ->join('subjects', 'subject_marks.subject_id', '=', 'subjects.id')
                ->selectRaw('subjects.code as subject_code, subject_marks.grade as grade, COUNT(*) as total')
                ->groupBy('subjects.code', 'subject_marks.grade')
                ->get()
                ->groupBy('subject_code')
                ->map(fn ($rows) => $rows->pluck('total', 'grade')->toArray())
                ->toArray();
        } else {
            $liveSubjectMarks = $this->liveMarkSetService->currentLiveSubjectMarksCollection(
                new Request(['exam_year_id' => $examYear->id]),
                $this->acseeExamTypeId(),
                (int) $examYear->year_label,
                fn ($query, $scopeRequest, $candidateAlias, $schoolAlias) => $this->applyScopeFiltersToCandidateJoinQuery($query, $scopeRequest, $candidateAlias, $schoolAlias),
                true,
                $candidateIds->all()
            );
            $subjectGradeDistribution = $liveSubjectMarks
                ->groupBy(fn ($row) => (string) ($row->subject?->code ?? ''))
                ->map(function (Collection $rows) {
                    return $rows->groupBy(fn ($row) => (string) ($row->grade ?? ''))
                        ->map(fn (Collection $gradeRows) => $gradeRows->count())
                        ->toArray();
                })
                ->toArray();
        }

        $irregularityCounts = [
            'ABS' => 0,
            'INC' => 0,
            'X' => 0,
            'DIV0' => isset($divisionCounts['0']) ? (int) $divisionCounts['0'] : 0,
        ];
        if ($this->hasColumn('candidate_results', 'result_status')) {
            $irregularityCounts['ABS'] = (clone $candidateQuery)->where('result_status', 'ABS')->count();
            $irregularityCounts['INC'] = (clone $candidateQuery)->where('result_status', 'INC')->count();
        } elseif ($this->hasColumn('subject_marks', 'subject_status')) {
            $irregularityCounts['ABS'] = (clone $subjectQuery)->whereIn('subject_status', ['ABS', 'X'])->count();
            $irregularityCounts['INC'] = (clone $subjectQuery)->where('subject_status', 'INC')->count();
            $irregularityCounts['X'] = (clone $subjectQuery)->where('subject_status', 'X')->count();
        }

        return [
            'candidates_count' => (int) $candidateIds->count(),
            'schools_count' => (int) $schoolsCount,
            'mean_aggt' => $meanAggt !== null ? round((float) $meanAggt, 2) : null,
            'mean_gpa' => $meanGpa !== null ? round((float) $meanGpa, 2) : null,
            'division_counts' => $divisionCounts,
            'irregularity_counts' => $irregularityCounts,
            'subject_grade_distributions' => $subjectGradeDistribution,
        ];
    }

    private function processSkipSummary(ResultProcess $run): array
    {
        $total = (int) ($run->total_candidates ?? 0);
        $processed = (int) ($run->processed_count ?? 0);
        $status = strtolower((string) ($run->status ?? ''));
        $stats = is_array($run->stats) ? $run->stats : [];
        $metadata = is_array($run->metadata) ? $run->metadata : [];

        $skipped = data_get($stats, 'skipped_candidates');
        if ($skipped === null) {
            $skipped = data_get($metadata, 'skipped_candidates');
        }
        if ($skipped === null) {
            // For active runs, skipped is unknown until completion; avoid showing total as skipped.
            $skipped = in_array($status, ['pending', 'in_progress', 'running'], true)
                ? 0
                : max(0, $total - $processed);
        }

        $breakdown = data_get($stats, 'skipped_breakdown');
        if (!is_array($breakdown)) {
            $breakdown = data_get($metadata, 'skipped_breakdown');
        }
        if (!is_array($breakdown)) {
            $breakdown = [];
        }
        if (empty($breakdown) && (int) $skipped > 0) {
            $breakdown = [
                'no_promoted_subject_marks' => 0,
                'no_graded_subjects' => 0,
                'candidate_compute_errors' => 0,
                'other' => (int) $skipped,
            ];
        }

        return [
            'skipped_count' => (int) $skipped,
            'skipped_breakdown' => $breakdown,
        ];
    }

    private function validateFullConfig(array $payload): array
    {
        $errors = [];
        $warnings = [];

        $grading = collect($payload['grading_rules'] ?? [])->filter(fn ($r) => empty($r['is_disabled']))->values();
        $gpaPoints = collect($payload['gpa_grade_points'] ?? [])->values();
        $divisionRules = collect($payload['division_rules'] ?? [])->filter(fn ($r) => empty($r['is_disabled']))->values();
        $competenceRules = collect($payload['competence_rules'] ?? [])->filter(fn ($r) => empty($r['is_disabled']))->values();
        $gpaSettings = $payload['gpa_settings'] ?? [];

        if ($grading->isEmpty()) {
            $errors[] = 'At least one grading rule is required.';
        } else {
            $analysis = $this->validateRulesDetailed($grading->map(fn ($r) => [
                'grade' => $r['grade'] ?? '',
                'min_percentage' => $r['min_mark'] ?? null,
                'max_percentage' => $r['max_mark'] ?? null,
                'points' => $r['points'] ?? null,
            ])->toArray());
            $errors = array_merge($errors, $analysis['errors']);
            $warnings = array_merge($warnings, $analysis['warnings']);
        }

        if (!isset($gpaSettings['method']) || trim((string) $gpaSettings['method']) === '') {
            $errors[] = 'GPA settings: method is required.';
        }
        if (isset($gpaSettings['rounding_mode']) && !in_array($gpaSettings['rounding_mode'], ['half_up', 'half_down', 'ceil', 'floor'], true)) {
            $errors[] = 'GPA settings: rounding_mode must be one of half_up, half_down, ceil, floor.';
        }
        if ($gpaPoints->isEmpty()) {
            $errors[] = 'GPA grade-point mapping is required.';
        }

        if ($divisionRules->isEmpty()) {
            $errors[] = 'At least one division rule is required.';
        } else {
            $sorted = $divisionRules->sortBy('min_points')->values();
            for ($i = 0; $i < $sorted->count(); $i++) {
                $row = $sorted[$i];
                if ((float) $row['min_points'] > (float) $row['max_points']) {
                    $errors[] = "Division {$row['division_label']} has min_points greater than max_points.";
                }
                if ($i < $sorted->count() - 1) {
                    $next = $sorted[$i + 1];
                    if ((float) $row['max_points'] >= (float) $next['min_points']) {
                        $warnings[] = "Division rules {$row['division_label']} and {$next['division_label']} overlap.";
                    }
                }
            }
        }

        if ($competenceRules->isEmpty()) {
            $errors[] = 'At least one competence rule is required.';
        } else {
            $allowedBases = ['GPA', 'POINTS', 'MARKS', 'GRADE'];
            foreach ($competenceRules as $rule) {
                $basis = strtoupper((string) ($rule['basis'] ?? ''));
                if (!in_array($basis, $allowedBases, true)) {
                    $errors[] = "Competence rule {$rule['level_label']} has invalid basis.";
                }
                if ((float) ($rule['min_value'] ?? 0) > (float) ($rule['max_value'] ?? 0)) {
                    $errors[] = "Competence rule {$rule['level_label']} has min_value greater than max_value.";
                }
            }
        }

        return [
            'errors' => array_values(array_unique($errors)),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * Normalize loaded grading payload to an effective NECTA ACSEE shape for UI and calculations.
     * Read-only normalization: does not persist to DB.
     */
    private function normalizeToNectaEffectiveConfig(array $resolved): array
    {
        $gradeOrder = ['A', 'B', 'C', 'D', 'E', 'S', 'F'];
        $gradePoints = $this->gradingService->getGradePointsMapping();
        $rulesByGrade = collect($resolved['grading_rules'] ?? [])->keyBy(fn ($r) => strtoupper((string) ($r['grade'] ?? '')));

        $resolved['grading_rules'] = collect($gradeOrder)->map(function (string $grade, int $idx) use ($rulesByGrade, $gradePoints) {
            $row = (array) ($rulesByGrade->get($grade) ?? []);
            $isPrincipal = in_array($grade, ['A', 'B', 'C', 'D', 'E'], true);

            return [
                'id' => $row['id'] ?? null,
                'grade' => $grade,
                'name' => $row['name'] ?? null,
                'min_mark' => isset($row['min_mark']) ? (float) $row['min_mark'] : 0.0,
                'max_mark' => isset($row['max_mark']) ? (float) $row['max_mark'] : 0.0,
                'points' => (int) ($gradePoints[$grade] ?? ($row['points'] ?? 7)),
                'is_principal' => $isPrincipal,
                'is_subsidiary' => !$isPrincipal,
                'sort_order' => $row['sort_order'] ?? $idx,
                'is_disabled' => (bool) ($row['is_disabled'] ?? false),
            ];
        })->toArray();

        $resolved['gpa_grade_points'] = collect($gradeOrder)->map(function (string $grade) use ($gradePoints) {
            return [
                'grade' => $grade,
                'gpa_point_value' => (float) ($gradePoints[$grade] ?? 7),
            ];
        })->toArray();

        return $resolved;
    }

    private function validateRulesDetailed(array $rules): array
    {
        $errors = [];
        $warnings = [];

        $normalized = collect($rules)->map(function ($rule, $idx) {
            return [
                'idx' => $idx,
                'grade' => strtoupper((string) ($rule['grade'] ?? '')),
                'min' => isset($rule['min_percentage']) ? (float) $rule['min_percentage'] : null,
                'max' => isset($rule['max_percentage']) ? (float) $rule['max_percentage'] : null,
                'points' => $rule['points'] ?? null,
            ];
        })->sortByDesc('max')->values();

        $grades = $normalized->pluck('grade')->filter();
        if ($grades->count() !== $grades->unique()->count()) {
            $errors[] = 'Duplicate grade codes detected in grading rules.';
        }

        foreach ($normalized as $i => $rule) {
            if ($rule['min'] === null || $rule['max'] === null) {
                $errors[] = "Rule #" . ($i + 1) . ' has missing min/max values.';
                continue;
            }
            if ($rule['min'] > $rule['max']) {
                $errors[] = "Rule {$rule['grade']} has min greater than max.";
            }
            if ($rule['min'] < 0 || $rule['max'] > 100) {
                $errors[] = "Rule {$rule['grade']} is outside allowed 0-100 range.";
            }
            if ($rule['points'] !== null && ((int) $rule['points'] < 1 || (int) $rule['points'] > 20)) {
                $errors[] = "Rule {$rule['grade']} has invalid points value.";
            }
        }

        for ($i = 0; $i < $normalized->count() - 1; $i++) {
            $current = $normalized[$i];
            $next = $normalized[$i + 1];

            if ($next['max'] >= $current['min']) {
                $warnings[] = "Rules {$current['grade']} and {$next['grade']} overlap.";
            }

            if (round($current['min'] - $next['max'], 2) > 1.0) {
                $warnings[] = "Gap detected between {$current['grade']} and {$next['grade']} boundaries.";
            }
        }

        return [
            'errors' => array_values(array_unique($errors)),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    private function toGradeBoundariesPayload(array $rules): array
    {
        return collect($rules)->values()->map(function ($row) {
            return [
                'grade' => strtoupper((string) $row['grade']),
                'grade_name' => $row['grade_name'] ?? strtoupper((string) $row['grade']),
                'min' => (float) $row['min_percentage'],
                'max' => (float) $row['max_percentage'],
            ];
        })->toArray();
    }

    private function toGradeBoundariesPayloadFromConfig(array $rules): array
    {
        return collect($rules)->values()->map(function ($row) {
            return [
                'grade' => strtoupper((string) $row['grade']),
                'grade_name' => $row['name'] ?? strtoupper((string) $row['grade']),
                'min' => (float) $row['min_mark'],
                'max' => (float) $row['max_mark'],
                'points' => $row['points'] ?? null,
                'is_principal' => (bool) ($row['is_principal'] ?? false),
                'is_subsidiary' => (bool) ($row['is_subsidiary'] ?? false),
                'sort_order' => (int) ($row['sort_order'] ?? 0),
                'is_disabled' => (bool) ($row['is_disabled'] ?? false),
            ];
        })->toArray();
    }

    private function normalizeDivision(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $map = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, '0' => 0, 'O' => 0];
        $upper = strtoupper(trim((string) $value));

        return $map[$upper] ?? null;
    }

    private function isDivisionUpgrade(int $current, int $simulated): bool
    {
        if ($current === 0) {
            return $simulated > 0;
        }

        if ($simulated === 0) {
            return false;
        }

        return $simulated < $current;
    }

    private function isDivisionDowngrade(int $current, int $simulated): bool
    {
        if ($current === 0) {
            return false;
        }

        if ($simulated === 0) {
            return true;
        }

        return $simulated > $current;
    }

    private function generateProfileCode(int $yearLabel, int $version): string
    {
        return sprintf('ACSEE-%d-V%d-%s', $yearLabel, $version, now()->format('His'));
    }

    private function logGovernanceAction(User $actor, string $action, array $data): void
    {
        GovernanceAuditLog::log(
            GovernanceAuditLog::ACTION_IMPORT_COMPLETED,
            $actor->id,
            $actor->id,
            array_merge($data, [
                'action' => $action,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'logged_at' => Carbon::now()->toDateTimeString(),
            ])
        );
    }
}
