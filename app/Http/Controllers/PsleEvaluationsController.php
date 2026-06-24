<?php

namespace App\Http\Controllers;

use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\GradingProfile;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PsleEvaluationsController extends Controller
{
    public static $bypassTestSafeguard = false;

    private const EXAM_TYPE_CODE = 'PSLE';

    private const EXPECTED_SUBJECTS = 6;

    public function index()
    {
        $examYearValue = $this->activeYear();

        if (!(auth()->check() && auth()->user()->is_admin)) {
            $this->checkPublicationStatus($examYearValue);
        }

        $entries = collect([
            [
                'label' => 'ZONAL PERFORMANCE EVALUATIONS',
                'url' => route('evaluations.psle.zonalwise'),
            ],
            [
                'label' => 'REGIONAL PERFORMANCE EVALUATIONS',
                'url' => route('evaluations.psle.regionalwise'),
            ],
        ]);

        $meta = $this->baseMeta([
            'eyebrow' => 'PSLE Evaluation Workspace',
            'header_title' => 'PSLE EVALUATIONS - ' . $examYearValue,
            'toolbar_title' => 'Available PSLE evaluation paths',
            'toolbar_copy' => 'Choose the required path below to continue into zonal or regional evaluation browsing.',
            'entry_copy' => 'Open this PSLE evaluation workspace.',
            'search_placeholder' => 'Search Evaluation Path from the list',
            'alpha_label' => 'CLICK ANY LETTER BELOW TO FILTER EVALUATION PATHS BY ALPHABET',
            'alpha_all_label' => 'ALL PATHS',
            'columns' => 2,
            'back_url' => auth()->check() ? '/evaluations' : null,
            'back_label' => 'Back to Evaluations',
        ]);

        return view('public.results-portal.index', [
            'link' => null,
            'entries' => $entries,
            'meta' => $meta,
            'token' => 'internal-evaluations-psle-root',
        ]);
    }

    public function zonalwise()
    {
        $examYearValue = $this->activeYear();

        if (!(auth()->check() && auth()->user()->is_admin)) {
            $this->checkPublicationStatus($examYearValue);
        }

        $precalcService = app(\App\Services\Results\PslePrecalculationService::class);
        $statusesMap = $precalcService->getStatusesMap($examYearValue, 'zonal', null);

        $snapshotId = $precalcService->getActiveSnapshotId($examYearValue);
        $hasPayloads = false;
        if ($snapshotId) {
            $hasPayloads = \App\Models\PslePrecalculatedEvaluation::where('exam_year', $examYearValue)
                ->where('exam_type', 'PSLE')
                ->where('scope_type', 'zonal')
                ->where('scope_id', null)
                ->where('snapshot_id', $snapshotId)
                ->where('status', \App\Models\PslePrecalculatedEvaluation::STATUS_READY)
                ->exists();
        }
        $isReady = $snapshotId && $hasPayloads;

        $extraButtons = [];
        if ($isReady) {
            $extraButtons[] = [
                'label' => 'View Result Book',
                'url' => route('evaluations.psle.zonalwise.result-book'),
                'class' => 'top-btn secondary',
                'style' => 'border-color: #2563eb; color: #2563eb;',
                'disabled' => false,
            ];
            $extraButtons[] = [
                'label' => 'Download Result Book PDF',
                'url' => route('evaluations.psle.zonalwise.result-book.pdf'),
                'class' => 'top-btn primary',
                'disabled' => false,
            ];
        } else {
            $extraButtons[] = [
                'label' => 'View Result Book',
                'url' => '#',
                'class' => 'top-btn secondary',
                'style' => 'opacity: 0.5; cursor: not-allowed; border-color: #cbd5e1; color: #94a3b8;',
                'disabled' => true,
                'disabled_msg' => 'Kitabu cha Matokeo hakiwezi kuzalishwa kwa sasa kwa sababu matokeo ya Kanda hayajakamilika kuchakatwa au hayajafungwa/kuchapishwa rasmi.',
            ];
            $extraButtons[] = [
                'label' => 'Download Result Book PDF',
                'url' => '#',
                'class' => 'top-btn primary',
                'style' => 'opacity: 0.5; cursor: not-allowed; background: #64748b; color: #cbd5e1;',
                'disabled' => true,
                'disabled_msg' => 'Kitabu cha Matokeo hakiwezi kuzalishwa kwa sasa kwa sababu matokeo ya Kanda hayajakamilika kuchakatwa au hayajafungwa/kuchapishwa rasmi.',
            ];
        }

        $entries = $this->zonalEvaluationEntries()->map(fn (array $evaluation) => [
            'label' => $evaluation['label'],
            'url' => route('evaluations.psle.zonalwise.evaluation', ['evaluation' => $evaluation['key']]),
            'status' => $statusesMap->get($evaluation['key'], \App\Models\PslePrecalculatedEvaluation::STATUS_PENDING),
        ]);

        $entries = $entries->concat([
            [
                'label' => 'ZONAL RESULT BOOK / KITABU CHA MATOKEO',
                'url' => route('evaluations.psle.zonalwise.result-book'),
                'status' => $isReady ? 'ready' : 'pending',
                'description' => 'Tathmini ya jumla ya uendeshaji, usajili, mahudhurio, madaraja, na mchanganuo wa ufaulu kikanda.',
            ],
            [
                'label' => 'TAARIFA MOCK DRS VII 2026 TASIDO',
                'url' => route('evaluations.psle.zonalwise.taarifa-tasido'),
                'status' => $isReady ? 'ready' : 'pending',
                'description' => 'Ripoti rasmi ya tathmini ya mtihani wa utamilifu (Zonal Mock) darasa la saba kwa Kanda ya TASIDO.',
            ]
        ]);

        $meta = $this->baseMeta([
            'eyebrow' => 'PSLE Zonal Evaluation Workspace',
            'header_title' => 'PSLE ZONAL EVALUATIONS - ' . $examYearValue,
            'toolbar_title' => 'Available zonal evaluation entries',
            'toolbar_copy' => 'Use the search box to find the exact zonal report you need, then open it directly from the list below.',
            'entry_copy' => 'Open this zonal evaluation entry.',
            'search_placeholder' => 'Search Evaluation from the list',
            'alpha_label' => 'CLICK ANY LETTER BELOW TO FILTER EVALUATIONS BY ALPHABET',
            'alpha_all_label' => 'ALL EVALUATIONS',
            'columns' => 4,
            'back_url' => route('evaluations.psle.index'),
            'back_label' => 'Back to PSLE Evaluations',
            'primary_action_url' => route('evaluations.psle.regionalwise'),
            'primary_action_label' => 'Open Regionalwise',
            'extra_buttons' => $extraButtons,
        ]);

        return view('public.results-portal.index', [
            'link' => null,
            'entries' => $entries,
            'meta' => $meta,
            'token' => 'internal-evaluations-psle-zonalwise',
        ]);
    }

    public function regionalwise()
    {
        $examYearValue = $this->activeYear();

        if (!(auth()->check() && auth()->user()->is_admin)) {
            $this->checkPublicationStatus($examYearValue);
        }

        $entries = Region::query()
            ->where('name', 'NOT LIKE', '%UNASSIGNED%')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($region) => [
                'label' => $region->name,
                'url' => route('evaluations.psle.regionalwise.region', ['region' => $region->id]),
            ]);

        $meta = $this->baseMeta([
            'eyebrow' => 'PSLE Regional Evaluation Workspace',
            'header_title' => 'PSLE REGIONAL EVALUATIONS - ' . $examYearValue,
            'toolbar_title' => 'Available regional entries',
            'toolbar_copy' => 'Search by region name or use the alphabet shortcuts below to open the correct regional evaluation path.',
            'entry_copy' => 'Open this region to view its available PSLE evaluation categories.',
            'search_placeholder' => 'Search Region from the list',
            'alpha_label' => 'CLICK ANY LETTER BELOW TO FILTER REGIONS BY ALPHABET',
            'alpha_all_label' => 'ALL REGIONS',
            'columns' => 4,
            'back_url' => route('evaluations.psle.index'),
            'back_label' => 'Back to PSLE Evaluations',
            'primary_action_url' => route('evaluations.psle.zonalwise'),
            'primary_action_label' => 'Open Zonalwise',
        ]);

        return view('public.results-portal.index', [
            'link' => null,
            'entries' => $entries,
            'meta' => $meta,
            'token' => 'internal-evaluations-psle-regionalwise',
        ]);
    }

    public function regionalwiseRegion(Region $region)
    {
        $examYearValue = $this->activeYear();

        if (!(auth()->check() && auth()->user()->is_admin)) {
            $this->checkPublicationStatus($examYearValue);
        }

        $precalcService = app(\App\Services\Results\PslePrecalculationService::class);
        $statusesMap = $precalcService->getStatusesMap($examYearValue, 'regional', $region->id);

        $snapshotId = $precalcService->getActiveSnapshotId($examYearValue);
        $hasPayloads = false;
        if ($snapshotId) {
            $hasPayloads = \App\Models\PslePrecalculatedEvaluation::where('exam_year', $examYearValue)
                ->where('exam_type', 'PSLE')
                ->where('scope_type', 'regional')
                ->where('scope_id', $region->id)
                ->where('snapshot_id', $snapshotId)
                ->where('status', \App\Models\PslePrecalculatedEvaluation::STATUS_READY)
                ->exists();
        }
        $isReady = $snapshotId && $hasPayloads;

        $extraButtons = [];
        if ($isReady) {
            $extraButtons[] = [
                'label' => 'View Result Book',
                'url' => route('evaluations.psle.regionalwise.result-book', ['id' => $region->id]),
                'class' => 'top-btn secondary',
                'style' => 'border-color: #2563eb; color: #2563eb;',
                'disabled' => false,
            ];
            $extraButtons[] = [
                'label' => 'Download Result Book PDF',
                'url' => route('evaluations.psle.regionalwise.result-book.pdf', ['id' => $region->id]),
                'class' => 'top-btn primary',
                'disabled' => false,
            ];
        } else {
            $extraButtons[] = [
                'label' => 'View Result Book',
                'url' => '#',
                'class' => 'top-btn secondary',
                'style' => 'opacity: 0.5; cursor: not-allowed; border-color: #cbd5e1; color: #94a3b8;',
                'disabled' => true,
                'disabled_msg' => 'Kitabu cha Matokeo hakiwezi kuzalishwa kwa sasa kwa sababu matokeo ya Mkoa hayajakamilika kuchakatwa au hayajafungwa/kuchapishwa rasmi.',
            ];
            $extraButtons[] = [
                'label' => 'Download Result Book PDF',
                'url' => '#',
                'class' => 'top-btn primary',
                'style' => 'opacity: 0.5; cursor: not-allowed; background: #64748b; color: #cbd5e1;',
                'disabled' => true,
                'disabled_msg' => 'Kitabu cha Matokeo hakiwezi kuzalishwa kwa sasa kwa sababu matokeo ya Mkoa hayajakamilika kuchakatwa au hayajafungwa/kuchapishwa rasmi.',
            ];
        }

        $entries = $this->regionalEvaluationEntries()->map(fn (array $evaluation) => [
            'label' => $evaluation['label'],
            'url' => route('evaluations.psle.regionalwise.region.evaluation', [
                'region' => $region->id,
                'evaluation' => $evaluation['key'],
            ]),
            'status' => $statusesMap->get($evaluation['key'], \App\Models\PslePrecalculatedEvaluation::STATUS_PENDING),
        ]);

        $entries = $entries->concat([
            [
                'label' => 'KITABU CHA MATOKEO / RESULT BOOK REPORT',
                'url' => route('evaluations.psle.regionalwise.result-book', ['id' => $region->id]),
                'status' => $isReady ? 'ready' : 'pending',
                'description' => 'Tathmini ya jumla ya uendeshaji, usajili, mahudhurio, madaraja, na mchanganuo wa ufaulu kimkoa.',
            ]
        ]);

        $meta = $this->baseMeta([
            'eyebrow' => strtoupper($region->name) . ' Regional Workspace',
            'header_title' => 'PSLE REGIONAL EVALUATIONS - ' . $examYearValue . ' - ' . strtoupper($region->name),
            'toolbar_title' => strtoupper($region->name) . ' evaluation categories',
            'toolbar_copy' => 'Search the report list or filter alphabetically to open the correct evaluation category for this region.',
            'entry_copy' => 'Open this regional evaluation entry.',
            'search_placeholder' => 'Search Evaluation from the list',
            'alpha_label' => 'CLICK ANY LETTER BELOW TO FILTER EVALUATIONS BY ALPHABET',
            'alpha_all_label' => 'ALL EVALUATIONS',
            'columns' => 4,
            'back_url' => route('evaluations.psle.regionalwise'),
            'back_label' => 'Back to Regions',
            'primary_action_url' => route('evaluations.psle.regionalwise'),
            'primary_action_label' => 'All Regions',
            'extra_buttons' => $extraButtons,
        ]);

        return view('public.results-portal.index', [
            'link' => null,
            'entries' => $entries,
            'meta' => $meta,
            'token' => 'internal-evaluations-psle-regionalwise-' . $region->id,
        ]);
    }

    public function zonalwiseEvaluation(Request $request, string $evaluation)
    {
        @ini_set('memory_limit', '2048M');
        $examYearValue = $this->activeYear();

        if (!(auth()->check() && auth()->user()->is_admin)) {
            $this->checkPublicationStatus($examYearValue);
        }

        $evaluationMap = $this->zonalEvaluationEntries()->keyBy('key');
        abort_unless($evaluationMap->has($evaluation), 404);

        if (strtolower((string) $request->query('export')) === 'pdf') {
            return $this->zonalwiseEvaluationExport($request, $evaluation, 'pdf');
        }
        $label = (string) data_get($evaluationMap->get($evaluation), 'label', 'PSLE ZONAL EVALUATION');

        if ($request->query('check_status') || $request->wantsJson()) {
            $precalcService = app(\App\Services\Results\PslePrecalculationService::class);
            $status = $precalcService->getEvaluationStatus($examYearValue, 'zonal', null, $evaluation);
            return response()->json(['status' => $status]);
        }

        $payload = $this->getEvaluationData('zonal', null, $evaluation, $examYearValue);

        if (!$payload) {
            return $this->renderNotReadyPage('zonal', null, $evaluation, $examYearValue);
        }

        if ($evaluation === 'subjectwise-result-evaluation') {
            return view('evaluations.psle-regionalwise-subjectwise', [
                'region' => (object) ['name' => 'TASIDO ZONE'],
                'evaluationKey' => $evaluation,
                'evaluationLabel' => $payload['evaluationLabel'] ?? $label,
                'examYearValue' => $examYearValue,
                'rows' => collect($payload['rows']),
                'summary' => $payload['summary'],
            ]);
        }

        return view('evaluations.psle-regionalwise-schoolwise', [
            'region' => null,
            'evaluationLabel' => $payload['evaluationLabel'] ?? $label,
            'examYearValue' => $examYearValue,
            'rows' => collect($payload['rows']),
            'total' => $payload['total'],
            'tableMode' => $payload['tableMode'],
            'zonalRank' => $payload['zonalRank'] ?? null,
        ]);
    }

    public function zonalwiseEvaluationExport(Request $request, string $evaluation, string $format)
    {
        @ini_set('memory_limit', '2048M');
        abort_unless(in_array($format, ['pdf', 'xlsx'], true), 404);

        $examYearValue = $this->activeYear();

        if (!(auth()->check() && auth()->user()->is_admin)) {
            $this->checkPublicationStatus($examYearValue);
        }

        $evaluationMap = $this->zonalEvaluationEntries()->keyBy('key');
        abort_unless($evaluationMap->has($evaluation), 404);
        $label = (string) data_get($evaluationMap->get($evaluation), 'label', 'PSLE ZONAL EVALUATION');

        $payload = $this->getEvaluationData('zonal', null, $evaluation, $examYearValue);

        if (in_array($evaluation, ['general', 'regionalwise'], true)) {
            abort_unless($format === 'pdf', 404);
            $filename = 'psle_zonal_' . $evaluation . '_' . $examYearValue . '.pdf';
            $tempPath = tempnam(sys_get_temp_dir(), 'psle_zonal_grouped_eval_');
            if ($tempPath === false) {
                abort(500, 'Unable to prepare PDF export file.');
            }

            $pdfPath = $tempPath . '.pdf';
            @rename($tempPath, $pdfPath);

            if ($payload) {
                $rows = collect($payload['rows']);
                $total = $payload['total'];
            } else {
                [$rows, $total] = $this->buildZonalRegionalwiseGroupedRows($examYearValue);
            }

            $bestRow = $rows->sortBy('pos')->first();
            $leastRow = $rows->sortByDesc('pos')->first();

            $summary = [
                'region_name' => 'TASIDO ZONE',
                'zonal_rank' => [],
                'group_count_label' => 'TOTAL REGIONS',
                'group_count' => $rows->count(),
                'government_school_count' => 0,
                'non_government_school_count' => 0,
                'registered' => $total['registered'] ?? ['m' => 0, 'f' => 0, 't' => 0],
                'sat' => $total['sat'] ?? ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                'absent' => $total['absent'] ?? ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                'inc' => $total['inc'] ?? ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                'pass_ac_pct' => (float) data_get($total, 'pass_ac.pct', 0),
                'pass_ad_pct' => (float) data_get($total, 'pass_ad.pct', 0),
                'average_label' => 'REGIONALWISE AVERAGE',
                'regional_average' => $this->groupedAverage($rows),
                'regional_average_badge' => $this->averageBadgeData($this->groupedAverage($rows)),
                'best_label' => 'BEST REGION',
                'best_name' => $bestRow['region'] ?? '-',
                'best_average' => $bestRow['avg_marks'] ?? null,
                'best_average_badge' => $this->averageBadgeData($bestRow['avg_marks'] ?? null),
                'best_pos' => $bestRow['pos'] ?? null,
                'least_label' => 'LEAST REGION',
                'least_name' => $leastRow['region'] ?? '-',
                'least_average' => $leastRow['avg_marks'] ?? null,
                'least_average_badge' => $this->averageBadgeData($leastRow['avg_marks'] ?? null),
                'least_pos' => $leastRow['pos'] ?? null,
                'is_schoolwise' => false,
            ];

            $options = [
                'first_column_label' => 'REGION',
                'first_column_key' => 'region',
                'first_column_width' => 86,
                'hide_second_column' => true,
                'metric_width' => 8.0,
                'average_width' => 20,
                'gpa_width' => 20,
                'pos_width' => 10,
                'summary' => $summary,
            ];

            app(\App\Services\Results\PsleRegionalSchoolwiseFpdfService::class)
                ->generate(
                    (object) ['name' => 'TASIDO ZONE'],
                    $examYearValue,
                    $rows->all(),
                    $total,
                    $pdfPath,
                    $label,
                    $options
                );

            return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
        } elseif ($evaluation === 'councilwise') {
            abort_unless($format === 'pdf', 404);
            $filename = 'psle_zonal_' . $evaluation . '_' . $examYearValue . '.pdf';
            $tempPath = tempnam(sys_get_temp_dir(), 'psle_zonal_grouped_eval_');
            if ($tempPath === false) {
                abort(500, 'Unable to prepare PDF export file.');
            }

            $pdfPath = $tempPath . '.pdf';
            @rename($tempPath, $pdfPath);

            if ($payload) {
                $rows = collect($payload['rows']);
                $total = $payload['total'];
            } else {
                [$rows, $total] = $this->buildZonalCouncilwiseGroupedRows($examYearValue);
            }

            $bestRow = $rows->sortBy('pos')->first();
            $leastRow = $rows->sortByDesc('pos')->first();

            $summary = [
                'region_name' => 'TASIDO ZONE',
                'zonal_rank' => [],
                'group_count_label' => 'TOTAL COUNCILS',
                'group_count' => $rows->count(),
                'government_school_count' => 0,
                'non_government_school_count' => 0,
                'registered' => $total['registered'] ?? ['m' => 0, 'f' => 0, 't' => 0],
                'sat' => $total['sat'] ?? ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                'absent' => $total['absent'] ?? ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                'inc' => $total['inc'] ?? ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                'pass_ac_pct' => (float) data_get($total, 'pass_ac.pct', 0),
                'pass_ad_pct' => (float) data_get($total, 'pass_ad.pct', 0),
                'average_label' => 'COUNCILWISE AVERAGE',
                'regional_average' => $this->groupedAverage($rows),
                'regional_average_badge' => $this->averageBadgeData($this->groupedAverage($rows)),
                'best_label' => 'BEST COUNCIL',
                'best_name' => $bestRow['council'] ?? '-',
                'best_average' => $bestRow['avg_marks'] ?? null,
                'best_average_badge' => $this->averageBadgeData($bestRow['avg_marks'] ?? null),
                'best_pos' => $bestRow['pos'] ?? null,
                'least_label' => 'LEAST COUNCIL',
                'least_name' => $leastRow['council'] ?? '-',
                'least_average' => $leastRow['avg_marks'] ?? null,
                'least_average_badge' => $this->averageBadgeData($leastRow['avg_marks'] ?? null),
                'least_pos' => $leastRow['pos'] ?? null,
                'is_schoolwise' => false,
            ];

            $options = [
                'first_column_label' => 'COUNCIL',
                'first_column_key' => 'council',
                'first_column_width' => 34,
                'second_column_label' => 'REGION',
                'second_column_key' => 'region',
                'second_column_width' => 52,
                'hide_second_column' => false,
                'metric_width' => 8.0,
                'average_width' => 20,
                'gpa_width' => 20,
                'pos_width' => 10,
                'summary' => $summary,
            ];

            app(\App\Services\Results\PsleRegionalSchoolwiseFpdfService::class)
                ->generate(
                    (object) ['name' => 'TASIDO ZONE'],
                    $examYearValue,
                    $rows->all(),
                    $total,
                    $pdfPath,
                    $label,
                    $options
                );

            return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
        } elseif ($evaluation === 'schoolwise') {
            abort_unless($format === 'pdf', 404);
            $filename = 'psle_zonal_' . $evaluation . '_' . $examYearValue . '.pdf';
            $tempPath = tempnam(sys_get_temp_dir(), 'psle_zonal_grouped_eval_');
            if ($tempPath === false) {
                abort(500, 'Unable to prepare PDF export file.');
            }

            $pdfPath = $tempPath . '.pdf';
            @rename($tempPath, $pdfPath);

            if ($payload) {
                $rows = collect($payload['rows']);
                $total = $payload['total'];
            } else {
                [$rows, $total] = $this->buildZonalSchoolwiseGroupedRows($examYearValue);
            }

            $bestRow = $rows->sortBy('pos')->first();
            $leastRow = $rows->sortByDesc('pos')->first();

            $summary = [
                'region_name' => 'TASIDO ZONE',
                'zonal_rank' => [],
                'group_count_label' => 'TOTAL SCHOOLS',
                'group_count' => $rows->count(),
                'government_school_count' => $rows->filter(fn ($row) => strtoupper((string) ($row['ownership'] ?? '')) === 'GOVERNMENT')->count(),
                'non_government_school_count' => $rows->filter(fn ($row) => strtoupper((string) ($row['ownership'] ?? '')) === 'NON-GOVERNMENT')->count(),
                'registered' => $total['registered'] ?? ['m' => 0, 'f' => 0, 't' => 0],
                'sat' => $total['sat'] ?? ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                'absent' => $total['absent'] ?? ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                'inc' => $total['inc'] ?? ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                'pass_ac_pct' => (float) data_get($total, 'pass_ac.pct', 0),
                'pass_ad_pct' => (float) data_get($total, 'pass_ad.pct', 0),
                'average_label' => 'SCHOOLWISE AVERAGE',
                'regional_average' => $this->groupedAverage($rows),
                'regional_average_badge' => $this->averageBadgeData($this->groupedAverage($rows)),
                'best_label' => 'BEST SCHOOL',
                'best_name' => $bestRow['school'] ?? '-',
                'best_average' => $bestRow['avg_marks'] ?? null,
                'best_average_badge' => $this->averageBadgeData($bestRow['avg_marks'] ?? null),
                'best_pos' => $bestRow['pos'] ?? null,
                'least_label' => 'LEAST SCHOOL',
                'least_name' => $leastRow['school'] ?? '-',
                'least_average' => $leastRow['avg_marks'] ?? null,
                'least_average_badge' => $this->averageBadgeData($leastRow['avg_marks'] ?? null),
                'least_pos' => $leastRow['pos'] ?? null,
                'is_schoolwise' => true,
            ];

            $options = [
                'first_column_label' => 'SCHOOL',
                'first_column_key' => 'school',
                'first_column_width' => 38,
                'second_column_label' => 'COUNCIL',
                'second_column_key' => 'council',
                'second_column_width' => 28,
                'third_column_label' => 'REGION',
                'third_column_key' => 'region',
                'third_column_width' => 20,
                'hide_second_column' => false,
                'metric_width' => 8.0,
                'average_width' => 20,
                'gpa_width' => 20,
                'pos_width' => 10,
                'summary' => $summary,
            ];

            app(\App\Services\Results\PsleRegionalSchoolwiseFpdfService::class)
                ->generate(
                    (object) ['name' => 'TASIDO ZONE'],
                    $examYearValue,
                    $rows->all(),
                    $total,
                    $pdfPath,
                    $label,
                    $options
                );

            return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
        } elseif (in_array($evaluation, ['best-ten-councils', 'least-ten-councils'], true)) {
            abort_unless($format === 'pdf', 404);
            $filename = 'psle_zonal_' . $evaluation . '_' . $examYearValue . '.pdf';
            $tempPath = tempnam(sys_get_temp_dir(), 'psle_zonal_grouped_eval_');
            if ($tempPath === false) {
                abort(500, 'Unable to prepare PDF export file.');
            }

            $pdfPath = $tempPath . '.pdf';
            @rename($tempPath, $pdfPath);

            if ($payload) {
                $rows = collect($payload['rows']);
                $total = $payload['total'];
            } else {
                [$rows] = $this->buildZonalCouncilwiseGroupedRows($examYearValue);
                $rows = match ($evaluation) {
                    'best-ten-councils' => $rows->take(10)->values(),
                    'least-ten-councils' => $rows->sort(function ($left, $right) {
                        $leftAvg = $left['avg_marks'] ?? INF;
                        $rightAvg = $right['avg_marks'] ?? INF;
                        if ($leftAvg !== $rightAvg) {
                            return $leftAvg <=> $rightAvg;
                        }

                        $leftGpa = $left['gpa'] ?? INF;
                        $rightGpa = $right['gpa'] ?? INF;
                        if ($leftGpa !== $rightGpa) {
                            return $rightGpa <=> $leftGpa;
                        }

                        $leftCouncil = strtoupper((string) ($left['council'] ?? ''));
                        $rightCouncil = strtoupper((string) ($right['council'] ?? ''));
                        if ($leftCouncil !== $rightCouncil) {
                            return strcmp($leftCouncil, $rightCouncil);
                        }

                        return strcmp(strtoupper((string) ($left['region'] ?? '')), strtoupper((string) ($right['region'] ?? '')));
                    })->take(10)->values(),
                };
                $rows = $this->applyPositions($rows);
                $total = $this->summariseGroupedRows($rows);
            }

            $bestRow = $rows->sortBy('pos')->first();
            $leastRow = $rows->sortByDesc('pos')->first();

            $summary = [
                'region_name' => 'TASIDO ZONE',
                'zonal_rank' => [],
                'group_count_label' => 'TOTAL COUNCILS',
                'group_count' => $rows->count(),
                'government_school_count' => 0,
                'non_government_school_count' => 0,
                'registered' => $total['registered'] ?? ['m' => 0, 'f' => 0, 't' => 0],
                'sat' => $total['sat'] ?? ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                'absent' => $total['absent'] ?? ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                'inc' => $total['inc'] ?? ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                'pass_ac_pct' => (float) data_get($total, 'pass_ac.pct', 0),
                'pass_ad_pct' => (float) data_get($total, 'pass_ad.pct', 0),
                'average_label' => 'COUNCILWISE AVERAGE',
                'regional_average' => $this->groupedAverage($rows),
                'regional_average_badge' => $this->averageBadgeData($this->groupedAverage($rows)),
                'best_label' => 'BEST COUNCIL',
                'best_name' => $bestRow['council'] ?? '-',
                'best_average' => $bestRow['avg_marks'] ?? null,
                'best_average_badge' => $this->averageBadgeData($bestRow['avg_marks'] ?? null),
                'best_pos' => $bestRow['pos'] ?? null,
                'least_label' => 'LEAST COUNCIL',
                'least_name' => $leastRow['council'] ?? '-',
                'least_average' => $leastRow['avg_marks'] ?? null,
                'least_average_badge' => $this->averageBadgeData($leastRow['avg_marks'] ?? null),
                'least_pos' => $leastRow['pos'] ?? null,
                'is_schoolwise' => false,
            ];

            $options = [
                'first_column_label' => 'COUNCIL',
                'first_column_key' => 'council',
                'first_column_width' => 34,
                'second_column_label' => 'REGION',
                'second_column_key' => 'region',
                'second_column_width' => 52,
                'hide_second_column' => false,
                'metric_width' => 8.0,
                'average_width' => 20,
                'gpa_width' => 20,
                'pos_width' => 10,
                'summary' => $summary,
            ];

            app(\App\Services\Results\PsleRegionalSchoolwiseFpdfService::class)
                ->generate(
                    (object) ['name' => 'TASIDO ZONE'],
                    $examYearValue,
                    $rows->all(),
                    $total,
                    $pdfPath,
                    $label,
                    $options
                );

            return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
        } elseif (in_array($evaluation, ['best-ten-schools', 'least-ten-schools'], true)) {
            abort_unless($format === 'pdf', 404);
            $filename = 'psle_zonal_' . $evaluation . '_' . $examYearValue . '.pdf';
            $tempPath = tempnam(sys_get_temp_dir(), 'psle_zonal_grouped_eval_');
            if ($tempPath === false) {
                abort(500, 'Unable to prepare PDF export file.');
            }

            $pdfPath = $tempPath . '.pdf';
            @rename($tempPath, $pdfPath);

            if ($payload) {
                $rows = collect($payload['rows']);
                $total = $payload['total'];
            } else {
                [$rows] = $this->buildZonalSchoolwiseGroupedRows($examYearValue);
                $rows = match ($evaluation) {
                    'best-ten-schools' => $rows->take(10)->values(),
                    'least-ten-schools' => $rows->sort(function ($left, $right) {
                        $leftAvg = $left['avg_marks'] ?? INF;
                        $rightAvg = $right['avg_marks'] ?? INF;
                        if ($leftAvg !== $rightAvg) {
                            return $leftAvg <=> $rightAvg;
                        }

                        $leftGpa = $left['gpa'] ?? INF;
                        $rightGpa = $right['gpa'] ?? INF;
                        if ($leftGpa !== $rightGpa) {
                            return $rightGpa <=> $leftGpa;
                        }

                        $leftSchool = strtoupper((string) ($left['school'] ?? ''));
                        $rightSchool = strtoupper((string) ($right['school'] ?? ''));
                        if ($leftSchool !== $rightSchool) {
                            return strcmp($leftSchool, $rightSchool);
                        }

                        $leftCouncil = strtoupper((string) ($left['council'] ?? ''));
                        $rightCouncil = strtoupper((string) ($right['council'] ?? ''));
                        if ($leftCouncil !== $rightCouncil) {
                            return strcmp($leftCouncil, $rightCouncil);
                        }

                        return strcmp(strtoupper((string) ($left['region'] ?? '')), strtoupper((string) ($right['region'] ?? '')));
                    })->take(10)->values(),
                };
                $rows = $this->applyPositions($rows);
                $total = $this->summariseGroupedRows($rows);
            }

            $bestRow = $rows->sortBy('pos')->first();
            $leastRow = $rows->sortByDesc('pos')->first();

            $summary = [
                'region_name' => 'TASIDO ZONE',
                'zonal_rank' => [],
                'group_count_label' => 'TOTAL SCHOOLS',
                'group_count' => $rows->count(),
                'government_school_count' => $rows->filter(fn ($row) => strtoupper((string) ($row['ownership'] ?? '')) === 'GOVERNMENT')->count(),
                'non_government_school_count' => $rows->filter(fn ($row) => strtoupper((string) ($row['ownership'] ?? '')) === 'NON-GOVERNMENT')->count(),
                'registered' => $total['registered'] ?? ['m' => 0, 'f' => 0, 't' => 0],
                'sat' => $total['sat'] ?? ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                'absent' => $total['absent'] ?? ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                'inc' => $total['inc'] ?? ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                'pass_ac_pct' => (float) data_get($total, 'pass_ac.pct', 0),
                'pass_ad_pct' => (float) data_get($total, 'pass_ad.pct', 0),
                'average_label' => 'SCHOOLWISE AVERAGE',
                'regional_average' => $this->groupedAverage($rows),
                'regional_average_badge' => $this->averageBadgeData($this->groupedAverage($rows)),
                'best_label' => 'BEST SCHOOL',
                'best_name' => $bestRow['school'] ?? '-',
                'best_average' => $bestRow['avg_marks'] ?? null,
                'best_average_badge' => $this->averageBadgeData($bestRow['avg_marks'] ?? null),
                'best_pos' => $bestRow['pos'] ?? null,
                'least_label' => 'LEAST SCHOOL',
                'least_name' => $leastRow['school'] ?? '-',
                'least_average' => $leastRow['avg_marks'] ?? null,
                'least_average_badge' => $this->averageBadgeData($leastRow['avg_marks'] ?? null),
                'least_pos' => $leastRow['pos'] ?? null,
                'is_schoolwise' => true,
            ];

            $options = [
                'first_column_label' => 'SCHOOL',
                'first_column_key' => 'school',
                'first_column_width' => 38,
                'second_column_label' => 'COUNCIL',
                'second_column_key' => 'council',
                'second_column_width' => 28,
                'third_column_label' => 'REGION',
                'third_column_key' => 'region',
                'third_column_width' => 20,
                'hide_second_column' => false,
                'metric_width' => 8.0,
                'average_width' => 20,
                'gpa_width' => 20,
                'pos_width' => 10,
                'summary' => $summary,
            ];

            app(\App\Services\Results\PsleRegionalSchoolwiseFpdfService::class)
                ->generate(
                    (object) ['name' => 'TASIDO ZONE'],
                    $examYearValue,
                    $rows->all(),
                    $total,
                    $pdfPath,
                    $label,
                    $options
                );

            return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
        } elseif ($evaluation === 'ownership-result-evaluation') {
            abort_unless($format === 'pdf', 404);
            $filename = 'psle_zonal_' . $evaluation . '_' . $examYearValue . '.pdf';
            $tempPath = tempnam(sys_get_temp_dir(), 'psle_zonal_grouped_eval_');
            if ($tempPath === false) {
                abort(500, 'Unable to prepare PDF export file.');
            }

            $pdfPath = $tempPath . '.pdf';
            @rename($tempPath, $pdfPath);

            if ($payload) {
                $rows = collect($payload['rows']);
                $total = $payload['total'];
            } else {
                [$rows, $total] = $this->buildZonalOwnershipGroupedRows($examYearValue);
            }

            $bestRow = $rows->sortBy('pos')->first();
            $leastRow = $rows->sortByDesc('pos')->first();

            $summary = [
                'region_name' => 'TASIDO ZONE',
                'zonal_rank' => [],
                'group_count_label' => 'TOTAL GROUPS',
                'group_count' => $rows->count(),
                'government_school_count' => 0,
                'non_government_school_count' => 0,
                'registered' => $total['registered'] ?? ['m' => 0, 'f' => 0, 't' => 0],
                'sat' => $total['sat'] ?? ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                'absent' => $total['absent'] ?? ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                'inc' => $total['inc'] ?? ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                'pass_ac_pct' => (float) data_get($total, 'pass_ac.pct', 0),
                'pass_ad_pct' => (float) data_get($total, 'pass_ad.pct', 0),
                'average_label' => 'OWNERSHIP AVERAGE',
                'regional_average' => $this->groupedAverage($rows),
                'regional_average_badge' => $this->averageBadgeData($this->groupedAverage($rows)),
                'best_label' => 'BEST GROUP',
                'best_name' => $bestRow['ownership'] ?? '-',
                'best_average' => $bestRow['avg_marks'] ?? null,
                'best_average_badge' => $this->averageBadgeData($bestRow['avg_marks'] ?? null),
                'best_pos' => $bestRow['pos'] ?? null,
                'least_label' => 'LEAST GROUP',
                'least_name' => $leastRow['ownership'] ?? '-',
                'least_average' => $leastRow['avg_marks'] ?? null,
                'least_average_badge' => $this->averageBadgeData($leastRow['avg_marks'] ?? null),
                'least_pos' => $leastRow['pos'] ?? null,
                'is_schoolwise' => false,
            ];

            $options = [
                'first_column_label' => 'OWNERSHIP',
                'first_column_key' => 'ownership',
                'first_column_width' => 86,
                'hide_second_column' => true,
                'metric_width' => 8.0,
                'average_width' => 20,
                'gpa_width' => 20,
                'pos_width' => 10,
                'summary' => $summary,
            ];

            app(\App\Services\Results\PsleRegionalSchoolwiseFpdfService::class)
                ->generate(
                    (object) ['name' => 'TASIDO ZONE'],
                    $examYearValue,
                    $rows->all(),
                    $total,
                    $pdfPath,
                    $label,
                    $options
                );

            return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
        } elseif ($evaluation === 'subjectwise-result-evaluation') {
            abort_unless($format === 'pdf', 404);
            $filename = 'psle_zonal_' . $evaluation . '_' . $examYearValue . '.pdf';
            $tempPath = tempnam(sys_get_temp_dir(), 'psle_zonal_subj_eval_');
            if ($tempPath === false) {
                abort(500, 'Unable to prepare PDF export file.');
            }

            $pdfPath = $tempPath . '.pdf';
            @rename($tempPath, $pdfPath);

            if ($payload) {
                $rows = collect($payload['rows']);
                $summary = $payload['summary'];
            } else {
                $rows = $this->buildZonalSubjectwiseRows($examYearValue);
                $summary = $this->buildZonalSubjectwiseSummary($examYearValue);
            }

            app(\App\Services\Results\PsleRegionalSubjectwiseFpdfService::class)
                ->generate(
                    (object) ['name' => 'TASIDO ZONE'],
                    $examYearValue,
                    $rows->all(),
                    $summary,
                    $pdfPath,
                    $label
                );

            return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
        }

        abort(404);
    }

    public function regionalwiseEvaluation(Request $request, Region $region, string $evaluation)
    {
        @ini_set('memory_limit', '2048M');
        $examYearValue = $this->activeYear();

        if (!(auth()->check() && auth()->user()->is_admin)) {
            $this->checkPublicationStatus($examYearValue);
        }

        $evaluationMap = $this->regionalEvaluationEntries()->keyBy('key');
        abort_unless($evaluationMap->has($evaluation), 404);

        if ($request->query('check_status') || $request->wantsJson()) {
            $precalcService = app(\App\Services\Results\PslePrecalculationService::class);
            $status = $precalcService->getEvaluationStatus($examYearValue, 'regional', $region->id, $evaluation);
            return response()->json(['status' => $status]);
        }

        if (strtolower((string) $request->query('export')) === 'pdf') {
            return $this->regionalwiseEvaluationExport($request, $region, $evaluation, 'pdf');
        }

        $payload = $this->getEvaluationData('regional', $region->id, $evaluation, $examYearValue);

        if (!$payload) {
            return $this->renderNotReadyPage('regional', $region->id, $evaluation, $examYearValue);
        }

        $label = $payload['evaluationLabel'] ?? (string) data_get($evaluationMap->get($evaluation), 'label', 'PSLE EVALUATION');

        switch ($evaluation) {
            case 'general':
                return view('evaluations.psle-regionalwise-schoolwise', [
                    'region' => $region,
                    'evaluationLabel' => $label,
                    'examYearValue' => $examYearValue,
                    'rows' => collect($payload['rows']),
                    'total' => $payload['total'],
                    'tableMode' => $payload['tableMode'] ?? 'general',
                    'zonalRank' => $payload['zonalRank'] ?? null,
                ]);

            case 'councilwise':
            case 'best-ten-councils':
            case 'least-ten-councils':
                return view('evaluations.psle-regionalwise-schoolwise', [
                    'region' => $region,
                    'evaluationLabel' => $label,
                    'examYearValue' => $examYearValue,
                    'rows' => collect($payload['rows']),
                    'total' => $payload['total'],
                    'tableMode' => $payload['tableMode'] ?? 'councilwise',
                    'zonalRank' => $payload['zonalRank'] ?? null,
                ]);

            case 'schoolwise':
            case 'best-ten-schools':
            case 'least-ten-schools':
            case 'government-schools':
            case 'non-government-schools':
                return view('evaluations.psle-regionalwise-schoolwise', [
                    'region' => $region,
                    'evaluationLabel' => $label,
                    'examYearValue' => $examYearValue,
                    'rows' => collect($payload['rows']),
                    'total' => $payload['total'],
                    'tableMode' => $payload['tableMode'] ?? 'schoolwise',
                    'zonalRank' => $payload['zonalRank'] ?? null,
                ]);

            case 'districtwise':
                return view('evaluations.psle-regionalwise-schoolwise', [
                    'region' => $region,
                    'evaluationLabel' => $label,
                    'examYearValue' => $examYearValue,
                    'rows' => collect($payload['rows']),
                    'total' => $payload['total'],
                    'tableMode' => $payload['tableMode'] ?? 'districtwise',
                    'zonalRank' => $payload['zonalRank'] ?? null,
                ]);

            case 'ownership-result-evaluation':
                return view('evaluations.psle-regionalwise-schoolwise', [
                    'region' => $region,
                    'evaluationLabel' => $label,
                    'examYearValue' => $examYearValue,
                    'rows' => collect($payload['rows']),
                    'total' => $payload['total'],
                    'tableMode' => $payload['tableMode'] ?? 'ownership',
                    'zonalRank' => $payload['zonalRank'] ?? null,
                ]);

            case 'best-ten-girls':
            case 'least-ten-girls':
            case 'best-ten-boys':
            case 'least-ten-boys':
            case 'overall-best-ten-students':
            case 'overall-least-ten-students':
                return view('evaluations.psle-regionalwise-student-ranking', [
                    'region' => $region,
                    'evaluationKey' => $evaluation,
                    'evaluationLabel' => $label,
                    'examYearValue' => $examYearValue,
                    'rows' => collect($payload['rows']),
                    'summary' => $payload['summary'],
                ]);

            case 'subjectwise-result-evaluation':
            case 'subject-summary-evaluation':
                return view('evaluations.psle-regionalwise-subjectwise', [
                    'region' => $region,
                    'evaluationKey' => $evaluation,
                    'evaluationLabel' => $label,
                    'examYearValue' => $examYearValue,
                    'rows' => collect($payload['rows']),
                    'summary' => $payload['summary'],
                ]);

            case 'mark-entry-status-report':
                return view('evaluations.psle-regionalwise-mark-entry-status', [
                    'region' => $region,
                    'evaluationLabel' => $label,
                    'examYearValue' => $examYearValue,
                    'rows' => collect($payload['rows']),
                    'filters' => $payload['filters'] ?? [],
                    'summary' => $payload['summary'] ?? [],
                    'summary_export' => $payload['summary_export'] ?? [],
                ]);
        }

        return view('evaluations.psle-regionalwise-evaluation-detail', [
            'region' => $region,
            'evaluationLabel' => $label,
            'summary' => [],
            'columns' => [],
            'rows' => [],
        ]);
    }

    public function regionalwiseEvaluationExport(Request $request, Region $region, string $evaluation, string $format)
    {
        @ini_set('memory_limit', '2048M');
        abort_unless(in_array($format, ['pdf', 'xlsx'], true), 404);

        $examYearValue = $this->activeYear();

        if (!(auth()->check() && auth()->user()->is_admin)) {
            $this->checkPublicationStatus($examYearValue);
        }

        $evaluationMap = $this->regionalEvaluationEntries()->keyBy('key');
        abort_unless($evaluationMap->has($evaluation), 404);

        $payload = $this->getEvaluationData('regional', $region->id, $evaluation, $examYearValue);
        $candidateRows = null;
        if (!$payload) {
            $candidateRows = $this->regionalCandidateRows($region, $examYearValue);
        }

        $label = $payload['evaluationLabel'] ?? (string) data_get($evaluationMap->get($evaluation), 'label', 'PSLE EVALUATION');

        if ($evaluation !== 'mark-entry-status-report') {
            abort_unless($format === 'pdf', 404);
            $safeRegion = \Illuminate\Support\Str::slug((string) $region->name);

            if (in_array($evaluation, ['best-ten-girls', 'least-ten-girls', 'best-ten-boys', 'least-ten-boys', 'overall-best-ten-students', 'overall-least-ten-students'], true)) {
                if ($payload) {
                    $rows = collect($payload['rows']);
                } else {
                    $rows = $this->buildStudentRankingRows($candidateRows, $evaluation);
                }
                $filename = 'psle_regional_' . \Illuminate\Support\Str::slug($evaluation) . '_' . $safeRegion . '_' . $examYearValue . '.pdf';
                $tempPath = tempnam(sys_get_temp_dir(), 'psle_rank_eval_');
                if ($tempPath === false) {
                    abort(500, 'Unable to prepare PDF export file.');
                }
                $pdfPath = $tempPath . '.pdf';
                @rename($tempPath, $pdfPath);
                app(\App\Services\Results\PsleRegionalStudentRankingFpdfService::class)
                    ->generate($region, $examYearValue, $rows->all(), $pdfPath, $label);

                return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
            }

            if (in_array($evaluation, ['subjectwise-result-evaluation', 'subject-summary-evaluation'], true)) {
                if ($payload) {
                    $rows = collect($payload['rows']);
                    $summary = $payload['summary'];
                } else {
                    $rows = $this->buildSubjectwiseRows($candidateRows);
                    $summary = $this->buildSubjectwiseSummary($candidateRows, $region);
                }
                $filename = 'psle_regional_' . \Illuminate\Support\Str::slug($evaluation) . '_' . $safeRegion . '_' . $examYearValue . '.pdf';
                $tempPath = tempnam(sys_get_temp_dir(), 'psle_subj_eval_');
                if ($tempPath === false) {
                    abort(500, 'Unable to prepare PDF export file.');
                }
                $pdfPath = $tempPath . '.pdf';
                @rename($tempPath, $pdfPath);
                app(\App\Services\Results\PsleRegionalSubjectwiseFpdfService::class)
                    ->generate($region, $examYearValue, $rows->all(), $summary, $pdfPath, $label);

                return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
            }

            $groupedPayload = $this->groupedEvaluationExportPayload($region, $examYearValue, $candidateRows, $evaluation, $payload);
            abort_unless(!is_null($groupedPayload), 404);
            $filename = 'psle_regional_' . ($groupedPayload['file_key'] ?? 'evaluation') . '_' . $safeRegion . '_' . $examYearValue . '.pdf';
            $tempPath = tempnam(sys_get_temp_dir(), 'psle_grouped_eval_');
            if ($tempPath === false) {
                abort(500, 'Unable to prepare PDF export file.');
            }

            $pdfPath = $tempPath . '.pdf';
            @rename($tempPath, $pdfPath);

            app(\App\Services\Results\PsleRegionalSchoolwiseFpdfService::class)
                ->generate(
                    $region,
                    $examYearValue,
                    $groupedPayload['rows']->all(),
                    $groupedPayload['total'],
                    $pdfPath,
                    $label,
                    array_merge($groupedPayload['options'] ?? [], [
                        'summary' => $groupedPayload['summary'] ?? [],
                    ])
                );

            return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
        }

        if ($payload) {
            $markEntryPayload = $payload;
        } else {
            $markEntryPayload = $this->markEntryStatusPayload($request, $region, $label, $examYearValue, $candidateRows);
        }

        if ($format === 'pdf') {
            $safeRegion = \Illuminate\Support\Str::slug((string) $region->name);
            $filename = 'psle_mark_entry_status_' . $safeRegion . '_' . date('Y-m-d') . '.pdf';
            $tempPath = tempnam(sys_get_temp_dir(), 'psle_mark_status_');
            if ($tempPath === false) {
                abort(500, 'Unable to prepare PDF export file.');
            }
            $pdfPath = $tempPath . '.pdf';
            @rename($tempPath, $pdfPath);

            app(\App\Services\Results\PsleRegionalMarkEntryStatusFpdfService::class)
                ->generate(
                    $region,
                    $examYearValue,
                    collect($markEntryPayload['rows'])->all(),
                    $markEntryPayload['summary_export'],
                    $pdfPath,
                    $label,
                    $markEntryPayload['filters']
                );

            return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
        }

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, ['S/N', 'SCHOOL', 'REGIST', 'LAST ACTIVITY', 'KISW', 'ENG', 'SST', 'MATH', 'SCI', 'CME', 'TOTAL', 'MARKED SCRIPTS', 'MARKED %', 'PENDING SCRIPTS', 'PENDING %', 'COMPLETION %', 'STATUS']);
        foreach ($markEntryPayload['rows'] as $index => $row) {
            fputcsv($csv, [
                $index + 1,
                $row['school'],
                $row['registered'],
                $row['last_activity_date'] ?? '-',
                $row['kisw'],
                $row['eng'],
                $row['sst'],
                $row['math'],
                $row['sci'],
                $row['cme'],
                $row['total'],
                $row['marked_scripts'],
                number_format((float) $row['marked_pct'], 1),
                $row['pending_scripts'],
                number_format((float) $row['pending_pct'], 1),
                number_format((float) $row['completion'], 1),
                $row['status'],
            ]);
        }
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response("\xEF\xBB\xBF" . $content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="psle_mark_entry_status_' . strtolower((string) $region->name) . '_' . date('Y-m-d') . '.xlsx"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    private function groupedEvaluationExportPayload(Region $region, int $examYearValue, ?Collection $candidateRows, string $evaluation, ?array $payload = null): ?array
    {
        if ($payload) {
            $rows = collect($payload['rows']);
            $total = $payload['total'];

            $mode = match ($evaluation) {
                'general' => 'general',
                'councilwise', 'best-ten-councils', 'least-ten-councils' => 'councilwise',
                'districtwise' => 'districtwise',
                'ownership-result-evaluation' => 'ownership',
                default => 'schoolwise',
            };

            $options = match ($evaluation) {
                'general' => [
                    'file_key' => 'general',
                    'evaluation_key' => 'general',
                    'first_column_label' => 'SEX',
                    'first_column_key' => 'council',
                    'first_column_width' => 28,
                    'hide_second_column' => true,
                    'metric_width' => 8.25,
                    'average_width' => 20,
                    'gpa_width' => 20,
                    'pos_width' => 10,
                ],
                'councilwise' => [
                    'file_key' => 'councilwise',
                    'evaluation_key' => 'councilwise',
                    'first_column_label' => 'COUNCIL',
                    'first_column_key' => 'council',
                    'first_column_width' => 60,
                    'hide_second_column' => true,
                    'metric_width' => 8.25,
                    'average_width' => 20,
                    'gpa_width' => 20,
                    'pos_width' => 10,
                ],
                'best-ten-councils' => [
                    'file_key' => 'best_ten_councils',
                    'evaluation_key' => 'best-ten-councils',
                    'first_column_label' => 'COUNCIL',
                    'first_column_key' => 'council',
                    'first_column_width' => 60,
                    'hide_second_column' => true,
                    'metric_width' => 8.25,
                    'average_width' => 20,
                    'gpa_width' => 20,
                    'pos_width' => 10,
                ],
                'least-ten-councils' => [
                    'file_key' => 'least_ten_councils',
                    'evaluation_key' => 'least-ten-councils',
                    'first_column_label' => 'COUNCIL',
                    'first_column_key' => 'council',
                    'first_column_width' => 60,
                    'hide_second_column' => true,
                    'metric_width' => 8.25,
                    'average_width' => 20,
                    'gpa_width' => 20,
                    'pos_width' => 10,
                ],
                'schoolwise' => ['file_key' => 'schoolwise', 'evaluation_key' => 'schoolwise'],
                'best-ten-schools' => [
                    'file_key' => 'best_ten_schools',
                    'evaluation_key' => 'best-ten-schools',
                ],
                'least-ten-schools' => [
                    'file_key' => 'least_ten_schools',
                    'evaluation_key' => 'least-ten-schools',
                ],
                'government-schools' => ['file_key' => 'government_schools', 'evaluation_key' => 'government-schools'],
                'non-government-schools' => ['file_key' => 'non_government_schools', 'evaluation_key' => 'non-government-schools'],
                'districtwise' => [
                    'file_key' => 'districtwise',
                    'evaluation_key' => 'districtwise',
                    'first_column_label' => 'DISTRICT',
                    'first_column_key' => 'district',
                    'first_column_width' => 86,
                    'hide_second_column' => true,
                    'metric_width' => 8.0,
                    'average_width' => 20,
                    'gpa_width' => 20,
                    'pos_width' => 10,
                ],
                'ownership-result-evaluation' => [
                    'file_key' => 'ownership',
                    'evaluation_key' => 'ownership-result-evaluation',
                    'first_column_label' => 'OWNERSHIP',
                    'first_column_key' => 'ownership',
                    'first_column_width' => 64,
                    'second_column_label' => 'SCHOOLS',
                    'second_column_key' => 'schools_count',
                    'second_column_align' => 'C',
                    'second_column_width' => 22,
                    'metric_width' => 7.75,
                    'average_width' => 20,
                    'gpa_width' => 20,
                    'pos_width' => 10,
                ],
                default => [],
            };

            $serviceOptions = [
                'first_column_label' => $options['first_column_label'] ?? 'COUNCIL',
                'first_column_key' => $options['first_column_key'] ?? 'council',
                'first_column_width' => $options['first_column_width'] ?? 34,
                'hide_second_column' => $options['hide_second_column'] ?? false,
                'second_column_label' => $options['second_column_label'] ?? 'SCHOOL',
                'second_column_key' => $options['second_column_key'] ?? 'school',
                'second_column_align' => $options['second_column_align'] ?? 'L',
                'second_column_width' => $options['second_column_width'] ?? 97,
                'metric_width' => $options['metric_width'] ?? 7.5,
                'average_width' => $options['average_width'] ?? 18,
                'grd_width' => $options['grd_width'] ?? 8,
                'gpa_width' => $options['gpa_width'] ?? 18,
                'pos_width' => $options['pos_width'] ?? 9,
            ];

            return [
                'rows' => $rows,
                'total' => $total,
                'options' => $serviceOptions,
                'file_key' => $options['file_key'] ?? $mode,
                'summary' => $this->buildGroupedExportSummary(
                    $region,
                    $examYearValue,
                    $rows,
                    $total,
                    $mode,
                    (string) ($options['evaluation_key'] ?? $mode)
                ),
            ];
        }

        return match ($evaluation) {
            'general' => $this->groupedPayloadForMode(
                $region,
                $examYearValue,
                $candidateRows,
                'general',
                [
                    'file_key' => 'general',
                    'evaluation_key' => 'general',
                    'first_column_label' => 'SEX',
                    'first_column_key' => 'council',
                    'first_column_width' => 28,
                    'hide_second_column' => true,
                    'metric_width' => 8.25,
                    'average_width' => 20,
                    'gpa_width' => 20,
                    'pos_width' => 10,
                ]
            ),
            'councilwise' => $this->groupedPayloadForMode(
                $region,
                $examYearValue,
                $candidateRows,
                'councilwise',
                [
                    'file_key' => 'councilwise',
                    'evaluation_key' => 'councilwise',
                    'first_column_label' => 'COUNCIL',
                    'first_column_key' => 'council',
                    'first_column_width' => 60,
                    'hide_second_column' => true,
                    'metric_width' => 8.25,
                    'average_width' => 20,
                    'gpa_width' => 20,
                    'pos_width' => 10,
                ]
            ),
            'best-ten-councils' => $this->groupedPayloadForMode(
                $region,
                $examYearValue,
                $candidateRows,
                'councilwise',
                [
                    'file_key' => 'best_ten_councils',
                    'evaluation_key' => 'best-ten-councils',
                    'first_column_label' => 'COUNCIL',
                    'first_column_key' => 'council',
                    'first_column_width' => 60,
                    'hide_second_column' => true,
                    'metric_width' => 8.25,
                    'average_width' => 20,
                    'gpa_width' => 20,
                    'pos_width' => 10,
                    'rows_transform' => fn (Collection $rows) => $rows->take(10)->values(),
                ]
            ),
            'least-ten-councils' => $this->groupedPayloadForMode(
                $region,
                $examYearValue,
                $candidateRows,
                'councilwise',
                [
                    'file_key' => 'least_ten_councils',
                    'evaluation_key' => 'least-ten-councils',
                    'first_column_label' => 'COUNCIL',
                    'first_column_key' => 'council',
                    'first_column_width' => 60,
                    'hide_second_column' => true,
                    'metric_width' => 8.25,
                    'average_width' => 20,
                    'gpa_width' => 20,
                    'pos_width' => 10,
                    'rows_transform' => fn (Collection $rows) => $rows->sort(function ($left, $right) {
                        $leftAvg = $left['avg_marks'] ?? INF;
                        $rightAvg = $right['avg_marks'] ?? INF;
                        if ($leftAvg !== $rightAvg) {
                            return $leftAvg <=> $rightAvg;
                        }

                        $leftGpa = $left['gpa'] ?? INF;
                        $rightGpa = $right['gpa'] ?? INF;
                        if ($leftGpa !== $rightGpa) {
                            return $rightGpa <=> $leftGpa;
                        }

                        return strcmp((string) ($left['sort_label'] ?? ''), (string) ($right['sort_label'] ?? ''));
                    })->take(10)->values(),
                ]
            ),
            'schoolwise' => $this->groupedPayloadForMode($region, $examYearValue, $candidateRows, 'schoolwise', ['file_key' => 'schoolwise', 'evaluation_key' => 'schoolwise']),
            'best-ten-schools' => $this->groupedPayloadForMode(
                $region,
                $examYearValue,
                $candidateRows,
                'schoolwise',
                [
                    'file_key' => 'best_ten_schools',
                    'evaluation_key' => 'best-ten-schools',
                    'rows_transform' => fn (Collection $rows) => $rows->take(10)->values(),
                ]
            ),
            'least-ten-schools' => $this->groupedPayloadForMode(
                $region,
                $examYearValue,
                $candidateRows,
                'schoolwise',
                [
                    'file_key' => 'least_ten_schools',
                    'evaluation_key' => 'least-ten-schools',
                    'rows_transform' => fn (Collection $rows) => $rows->sort(function ($left, $right) {
                        $leftAvg = $left['avg_marks'] ?? INF;
                        $rightAvg = $right['avg_marks'] ?? INF;
                        if ($leftAvg !== $rightAvg) {
                            return $leftAvg <=> $rightAvg;
                        }

                        $leftGpa = $left['gpa'] ?? INF;
                        $rightGpa = $right['gpa'] ?? INF;
                        if ($leftGpa !== $rightGpa) {
                            return $rightGpa <=> $leftGpa;
                        }

                        return strcmp((string) ($left['sort_label'] ?? ''), (string) ($right['sort_label'] ?? ''));
                    })->take(10)->values(),
                ]
            ),
            'government-schools' => $this->groupedPayloadForMode(
                $region,
                $examYearValue,
                $candidateRows->filter(fn ($row) => strtoupper((string) ($row['ownership'] ?? '')) === 'GOVERNMENT')->values(),
                'schoolwise',
                ['file_key' => 'government_schools', 'evaluation_key' => 'government-schools']
            ),
            'non-government-schools' => $this->groupedPayloadForMode(
                $region,
                $examYearValue,
                $candidateRows->filter(fn ($row) => strtoupper((string) ($row['ownership'] ?? '')) === 'NON-GOVERNMENT')->values(),
                'schoolwise',
                ['file_key' => 'non_government_schools', 'evaluation_key' => 'non-government-schools']
            ),
            'districtwise' => $this->groupedPayloadForMode(
                $region,
                $examYearValue,
                $candidateRows,
                'districtwise',
                [
                    'file_key' => 'districtwise',
                    'evaluation_key' => 'districtwise',
                    'first_column_label' => 'DISTRICT',
                    'first_column_key' => 'district',
                    'first_column_width' => 86,
                    'hide_second_column' => true,
                    'metric_width' => 8.0,
                    'average_width' => 20,
                    'gpa_width' => 20,
                    'pos_width' => 10,
                ]
            ),
            'ownership-result-evaluation' => $this->groupedPayloadForMode(
                $region,
                $examYearValue,
                $candidateRows,
                'ownership',
                [
                    'file_key' => 'ownership',
                    'evaluation_key' => 'ownership-result-evaluation',
                    'first_column_label' => 'OWNERSHIP',
                    'first_column_key' => 'ownership',
                    'first_column_width' => 64,
                    'second_column_label' => 'SCHOOLS',
                    'second_column_key' => 'schools_count',
                    'second_column_align' => 'C',
                    'second_column_width' => 22,
                    'metric_width' => 7.75,
                    'average_width' => 20,
                    'gpa_width' => 20,
                    'pos_width' => 10,
                ]
            ),
            default => null,
        };
    }

    private function groupedPayloadForMode(Region $region, int $examYearValue, Collection $candidateRows, string $mode, array $options = []): array
    {
        [$rows] = $this->buildGroupedRows($candidateRows, $mode);

        if (isset($options['rows_transform']) && is_callable($options['rows_transform'])) {
            $rows = $options['rows_transform']($rows);
        }

        $rows = $this->applyPositions($rows);

        $serviceOptions = [
            'first_column_label' => $options['first_column_label'] ?? 'COUNCIL',
            'first_column_key' => $options['first_column_key'] ?? 'council',
            'first_column_width' => $options['first_column_width'] ?? 34,
            'hide_second_column' => $options['hide_second_column'] ?? false,
            'second_column_label' => $options['second_column_label'] ?? 'SCHOOL',
            'second_column_key' => $options['second_column_key'] ?? 'school',
            'second_column_align' => $options['second_column_align'] ?? 'L',
            'second_column_width' => $options['second_column_width'] ?? 97,
            'metric_width' => $options['metric_width'] ?? 7.5,
            'average_width' => $options['average_width'] ?? 18,
            'grd_width' => $options['grd_width'] ?? 8,
            'gpa_width' => $options['gpa_width'] ?? 18,
            'pos_width' => $options['pos_width'] ?? 9,
        ];

        return [
            'rows' => $rows,
            'total' => $this->summariseGroupedRows($rows),
            'options' => $serviceOptions,
            'file_key' => $options['file_key'] ?? $mode,
            'summary' => $this->buildGroupedExportSummary(
                $region,
                $examYearValue,
                $rows,
                $this->summariseGroupedRows($rows),
                $mode,
                (string) ($options['evaluation_key'] ?? $mode)
            ),
        ];
    }

    private function buildGroupedExportSummary(Region $region, int $examYearValue, Collection $rows, array $total, string $mode, string $evaluationKey): array
    {
        $isSchoolwiseEvaluation = $mode === 'schoolwise';
        $isCouncilwiseEvaluation = $mode === 'councilwise';
        $isDistrictwiseEvaluation = $mode === 'districtwise';
        $isOwnershipEvaluation = $mode === 'ownership';
        $isGeneralEvaluation = $mode === 'general';

        $groupCountLabel = match (true) {
            $isSchoolwiseEvaluation => 'TOTAL SCHOOLS',
            $isCouncilwiseEvaluation => 'TOTAL COUNCILS',
            $isDistrictwiseEvaluation => 'TOTAL DISTRICTS',
            $isOwnershipEvaluation => 'TOTAL OWNERSHIP GROUPS',
            $isGeneralEvaluation => 'TOTAL SEX GROUPS',
            default => 'TOTAL GROUPS',
        };

        $averageLabel = match (true) {
            $isSchoolwiseEvaluation => 'REGIONAL AVERAGE',
            $isCouncilwiseEvaluation => 'COUNCILWISE AVERAGE',
            $isDistrictwiseEvaluation => 'DISTRICTWISE AVERAGE',
            $isOwnershipEvaluation => 'OWNERSHIP GROUP AVERAGE',
            $isGeneralEvaluation => 'SEX GROUP AVERAGE',
            default => 'GROUP AVERAGE',
        };

        $bestLabel = match (true) {
            $isSchoolwiseEvaluation => 'BEST SCHOOL',
            $isCouncilwiseEvaluation => 'BEST COUNCIL',
            $isDistrictwiseEvaluation => 'BEST DISTRICT',
            $isOwnershipEvaluation => 'BEST OWNERSHIP GROUP',
            $isGeneralEvaluation => 'BEST SEX GROUP',
            default => 'BEST GROUP',
        };

        $leastLabel = match (true) {
            $isSchoolwiseEvaluation => 'LEAST SCHOOL',
            $isCouncilwiseEvaluation => 'LEAST COUNCIL',
            $isDistrictwiseEvaluation => 'LEAST DISTRICT',
            $isOwnershipEvaluation => 'LEAST OWNERSHIP GROUP',
            $isGeneralEvaluation => 'LEAST SEX GROUP',
            default => 'LEAST GROUP',
        };

        $regionalAverage = $this->groupedAverage($rows);
        $bestRow = $rows->sortBy('pos')->first();
        $leastRow = $rows->sortByDesc('pos')->first();

        $nameForRow = function (?array $row) use ($isSchoolwiseEvaluation, $isCouncilwiseEvaluation, $isDistrictwiseEvaluation, $isOwnershipEvaluation, $isGeneralEvaluation) {
            if (!$row) {
                return '-';
            }

            return match (true) {
                $isSchoolwiseEvaluation => (string) ($row['school'] ?? '-'),
                $isCouncilwiseEvaluation => (string) ($row['council'] ?? '-'),
                $isDistrictwiseEvaluation => (string) ($row['district'] ?? '-'),
                $isOwnershipEvaluation => (string) ($row['ownership'] ?? '-'),
                $isGeneralEvaluation => (string) ($row['council'] ?? '-'),
                default => '-',
            };
        };

        $governmentSchoolCount = $isSchoolwiseEvaluation
            ? $rows->filter(fn ($row) => strtoupper((string) ($row['ownership'] ?? '')) === 'GOVERNMENT')->count()
            : 0;
        $nonGovernmentSchoolCount = $isSchoolwiseEvaluation
            ? $rows->filter(fn ($row) => strtoupper((string) ($row['ownership'] ?? '')) === 'NON-GOVERNMENT')->count()
            : 0;

        $candidateFilter = match ($evaluationKey) {
            'government-schools' => fn (Collection $candidateRows) => $candidateRows->filter(fn ($row) => strtoupper((string) ($row['ownership'] ?? '')) === 'GOVERNMENT')->values(),
            'non-government-schools' => fn (Collection $candidateRows) => $candidateRows->filter(fn ($row) => strtoupper((string) ($row['ownership'] ?? '')) === 'NON-GOVERNMENT')->values(),
            default => null,
        };

        return [
            'region_name' => strtoupper((string) $region->name),
            'zonal_rank' => $this->zonalRankForGroupedMode(
                $examYearValue,
                $region,
                $mode,
                $candidateFilter,
                $evaluationKey === 'government-schools' ? 'gov' : ($evaluationKey === 'non-government-schools' ? 'non-gov' : 'none')
            ),
            'group_count_label' => $groupCountLabel,
            'group_count' => $rows->count(),
            'government_school_count' => $governmentSchoolCount,
            'non_government_school_count' => $nonGovernmentSchoolCount,
            'registered' => $total['registered'] ?? ['m' => 0, 'f' => 0, 't' => 0],
            'sat' => $total['sat'] ?? ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
            'absent' => $total['absent'] ?? ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
            'inc' => $total['inc'] ?? ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
            'pass_ac_pct' => (float) data_get($total, 'pass_ac.pct', 0),
            'pass_ad_pct' => (float) data_get($total, 'pass_ad.pct', 0),
            'average_label' => $averageLabel,
            'regional_average' => $regionalAverage,
            'regional_average_badge' => $this->averageBadgeData($regionalAverage),
            'best_label' => $bestLabel,
            'best_name' => $nameForRow($bestRow),
            'best_average' => $bestRow['avg_marks'] ?? null,
            'best_average_badge' => $this->averageBadgeData($bestRow['avg_marks'] ?? null),
            'best_pos' => $bestRow['pos'] ?? null,
            'least_label' => $leastLabel,
            'least_name' => $nameForRow($leastRow),
            'least_average' => $leastRow['avg_marks'] ?? null,
            'least_average_badge' => $this->averageBadgeData($leastRow['avg_marks'] ?? null),
            'least_pos' => $leastRow['pos'] ?? null,
            'is_schoolwise' => $isSchoolwiseEvaluation,
        ];
    }

    private function averageBadgeData(?float $average): ?array
    {
        if (is_null($average)) {
            return null;
        }

        $grade = match (true) {
            (float) $average >= 246 => 'A',
            (float) $average >= 186 => 'B',
            (float) $average >= 126 => 'C',
            (float) $average >= 66 => 'D',
            default => 'E',
        };

        $competence = $this->psleCompetenceLabels()[$grade] ?? 'Unsatisfactory';

        return [
            'grade' => $grade,
            'competence' => $competence,
            'color' => match ($grade) {
                'A' => '#00A82A',
                'B' => '#1FEE0B',
                'C' => '#DEF043',
                'D' => '#FF772F',
                default => '#FF272F',
            },
        ];
    }

    private function checkPublicationStatus(int $examYear): void
    {
        $hasActiveCorrection = DB::table('school_result_correction_batches')
            ->where('exam_year', $examYear)
            ->where('exam_type', 'PSLE')
            ->whereIn('status', ['open', 'corrected', 'recalculated'])
            ->exists();

        if ($hasActiveCorrection) {
            abort(403, "Results are temporarily under correction. Please check again later.");
        }

        $publication = DB::table('psle_result_publications as prp')
            ->join('result_snapshots as rs', 'rs.id', '=', 'prp.snapshot_id')
            ->where('prp.exam_year_id', function ($query) use ($examYear) {
                $query->select('id')->from('exam_years')->where('year_label', $examYear)->limit(1);
            })
            ->where('prp.status', 'published')
            ->where('rs.is_active', true)
            ->where('rs.is_rolled_back', false)
            ->exists();

        if (!$publication) {
            abort(403, "Results for {$examYear} are not yet published.");
        }
    }

    public function buildZonalRegionalwiseGroupedRows(int $examYearValue): array
    {
        $regions = Region::query()
            ->whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])
            ->get();

        $allRows = collect();

        foreach ($regions as $region) {
            $candidateRows = $this->regionalCandidateRows($region, $examYearValue)->map(function (array $row) use ($region) {
                $row['region'] = strtoupper($region->name);
                return $row;
            });
            [$rows] = $this->buildGroupedRows($candidateRows, 'regionalwise');
            
            if ($rows->isNotEmpty()) {
                $allRows = $allRows->merge($rows);
            }
            
            unset($candidateRows, $rows);
            gc_collect_cycles();
        }

        $sortedRows = $allRows->sort(function (array $left, array $right) {
            $leftAvg = $left['avg_marks'] ?? -INF;
            $rightAvg = $right['avg_marks'] ?? -INF;
            if ($leftAvg !== $rightAvg) {
                return $rightAvg <=> $leftAvg;
            }

            $leftGpa = $left['gpa'] ?? INF;
            $rightGpa = $right['gpa'] ?? INF;
            if ($leftGpa !== $rightGpa) {
                return $leftGpa <=> $rightGpa;
            }

            $leftPass = $left['pass_ac']['t'] ?? 0;
            $rightPass = $right['pass_ac']['t'] ?? 0;
            if ($leftPass !== $rightPass) {
                return $rightPass <=> $leftPass;
            }

            $leftName = strtoupper((string) ($left['sort_label'] ?? ''));
            $rightName = strtoupper((string) ($right['sort_label'] ?? ''));

            return strcmp($leftName, $rightName);
        })->values();

        $sortedRows = $this->applyPositions($sortedRows);
        $total = $this->summariseGroupedRows($sortedRows);

        return [$sortedRows, $total];
    }

    public function buildZonalCouncilwiseGroupedRows(int $examYearValue): array
    {
        $regions = Region::query()
            ->whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])
            ->get();

        $allRows = collect();

        foreach ($regions as $region) {
            $candidateRows = $this->regionalCandidateRows($region, $examYearValue, true, 'zonal-councilwise');
            [$rows] = $this->buildGroupedRows($candidateRows, 'zonal-councilwise');
            
            if ($rows->isNotEmpty()) {
                $allRows = $allRows->merge($rows);
            }
            
            unset($candidateRows, $rows);
            gc_collect_cycles();
        }

        $sortedRows = $allRows->sort(function (array $left, array $right) {
            $leftAvg = $left['avg_marks'] ?? -INF;
            $rightAvg = $right['avg_marks'] ?? -INF;
            if ($leftAvg !== $rightAvg) {
                return $rightAvg <=> $leftAvg;
            }

            $leftGpa = $left['gpa'] ?? INF;
            $rightGpa = $right['gpa'] ?? INF;
            if ($leftGpa !== $rightGpa) {
                return $leftGpa <=> $rightGpa;
            }

            $leftPass = $left['pass_ac']['t'] ?? 0;
            $rightPass = $right['pass_ac']['t'] ?? 0;
            if ($leftPass !== $rightPass) {
                return $rightPass <=> $leftPass;
            }

            $leftCouncilName = strtoupper((string) ($left['council'] ?? ''));
            $rightCouncilName = strtoupper((string) ($right['council'] ?? ''));
            if ($leftCouncilName !== $rightCouncilName) {
                return strcmp($leftCouncilName, $rightCouncilName);
            }

            $leftRegionName = strtoupper((string) ($left['region'] ?? ''));
            $rightRegionName = strtoupper((string) ($right['region'] ?? ''));
            return strcmp($leftRegionName, $rightRegionName);
        })->values();

        $sortedRows = $this->applyPositions($sortedRows);
        $total = $this->summariseGroupedRows($sortedRows);

        return [$sortedRows, $total];
    }

    public function buildZonalSchoolwiseGroupedRows(int $examYearValue): array
    {
        $regions = Region::query()
            ->whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])
            ->get();

        $allRows = collect();

        foreach ($regions as $region) {
            $candidateRows = $this->regionalCandidateRows($region, $examYearValue, true, 'zonal-schoolwise');
            [$rows] = $this->buildGroupedRows($candidateRows, 'zonal-schoolwise');
            
            if ($rows->isNotEmpty()) {
                $allRows = $allRows->merge($rows);
            }
            
            unset($candidateRows, $rows);
            gc_collect_cycles();
        }

        $sortedRows = $allRows->sort(function (array $left, array $right) {
            $leftAvg = $left['avg_marks'] ?? -INF;
            $rightAvg = $right['avg_marks'] ?? -INF;
            if ($leftAvg !== $rightAvg) {
                return $rightAvg <=> $leftAvg;
            }

            $leftGpa = $left['gpa'] ?? INF;
            $rightGpa = $right['gpa'] ?? INF;
            if ($leftGpa !== $rightGpa) {
                return $leftGpa <=> $rightGpa;
            }

            $leftPass = $left['pass_ac']['t'] ?? 0;
            $rightPass = $right['pass_ac']['t'] ?? 0;
            if ($leftPass !== $rightPass) {
                return $rightPass <=> $leftPass;
            }

            $leftSchoolName = strtoupper((string) ($left['school'] ?? ''));
            $rightSchoolName = strtoupper((string) ($right['school'] ?? ''));
            if ($leftSchoolName !== $rightSchoolName) {
                return strcmp($leftSchoolName, $rightSchoolName);
            }

            $leftCouncilName = strtoupper((string) ($left['council'] ?? ''));
            $rightCouncilName = strtoupper((string) ($right['council'] ?? ''));
            if ($leftCouncilName !== $rightCouncilName) {
                return strcmp($leftCouncilName, $rightCouncilName);
            }

            $leftRegionName = strtoupper((string) ($left['region'] ?? ''));
            $rightRegionName = strtoupper((string) ($right['region'] ?? ''));
            return strcmp($leftRegionName, $rightRegionName);
        })->values();

        $sortedRows = $this->applyPositions($sortedRows);
        $total = $this->summariseGroupedRows($sortedRows);

        return [$sortedRows, $total];
    }


    public function zonalCandidateRows(int $examYearValue, bool $lightweight = false, string $mode = '', int $chunkSize = 500): Collection
    {
        $publication = DB::table('psle_result_publications as prp')
            ->join('result_snapshots as rs', 'rs.id', '=', 'prp.snapshot_id')
            ->where('prp.exam_year_id', function ($query) use ($examYearValue) {
                $query->select('id')->from('exam_years')->where('year_label', $examYearValue)->limit(1);
            })
            ->where('prp.status', 'published')
            ->where('rs.is_active', true)
            ->where('rs.is_rolled_back', false)
            ->select('rs.id as snapshot_id')
            ->first();

        $snapshotId = $publication ? $publication->snapshot_id : 0;

        $regionIds = Region::query()
            ->whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])
            ->pluck('id')
            ->all();

        $selectCols = [
            'c.id as candidate_pk',
            'c.gender',
            's.id as school_id',
            's.ownership',
            'r.name as region_name',
        ];

        if (!$lightweight) {
            $selectCols = array_merge($selectCols, [
                'c.candidate_id as index_number',
                'c.prem_no',
                'c.full_name',
                's.code as school_code',
                's.name as school_name',
                'd.id as district_id',
                'd.code as district_code',
                'd.name as district_name',
                'dc.id as council_id',
                'dc.code as council_code',
                'dc.name as council_name',
            ]);
        }

        $registrationsQuery = DB::table('candidate_exam_registrations as cer')
            ->join('candidates as c', 'c.id', '=', 'cer.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->leftJoin('regions as r', 'r.id', '=', 's.region_id')
            ->leftJoin('districts as d', 'd.id', '=', 's.district_id')
            ->leftJoin('district_councils as dc', 'dc.id', '=', 's.council_id')
            ->whereIn('s.region_id', $regionIds)
            ->where('cer.exam_type_id', $this->psleExamTypeId())
            ->where('cer.year', $examYearValue)
            ->select($selectCols)
            ->orderBy('c.id');

        $candidateRows = collect();

        $registrationsQuery->chunk($chunkSize, function ($chunk) use (&$candidateRows, $snapshotId, $examYearValue, $lightweight, $mode) {
            $candidateIds = $chunk->pluck('candidate_pk')->all();

            $marksQuery = DB::table('subject_marks as sm')
                ->whereIn('sm.candidate_id', $candidateIds)
                ->where('sm.exam_type_id', $this->psleExamTypeId())
                ->where('sm.year', $examYearValue)
                ->where('sm.snapshot_id', $snapshotId);

            if ($lightweight) {
                $marksQuery->select([
                    'sm.candidate_id',
                    'sm.marks_obtained',
                    'sm.max_marks',
                    'sm.grade',
                ]);
            } else {
                $marksQuery->join('subjects as sb', 'sb.id', '=', 'sm.subject_id')
                    ->select([
                        'sm.candidate_id',
                        'sm.marks_obtained',
                        'sm.max_marks',
                        'sm.grade',
                        'sb.code as subject_code',
                        'sb.name as subject_name',
                    ]);
            }

            $marksByCandidate = $marksQuery->get()->groupBy('candidate_id');

            $mapped = $chunk->map(function ($candidate) use ($marksByCandidate, $lightweight, $mode) {
                if ($lightweight) {
                    $subjectRows = collect($marksByCandidate->get($candidate->candidate_pk, []))
                        ->map(function ($row) {
                            $isAbsent = strtoupper((string) ($row->grade ?? '')) === 'ABS' || is_null($row->marks_obtained);
                            $score = $isAbsent ? null : $this->scaledScore50((float) ($row->marks_obtained ?? 0), (float) ($row->max_marks ?: 100));
                            $grade = $isAbsent ? 'ABS' : $this->gradeFromScaledScore($score);

                            return [
                                'score' => $score,
                                'grade' => $grade,
                                'is_absent' => $isAbsent,
                            ];
                        });

                    $satSubjects = $subjectRows->filter(fn (array $item) => !($item['is_absent'] ?? false));
                    $subjectCount = $satSubjects->count();
                    $total = $subjectCount > 0 ? round((float) $satSubjects->sum('score'), 4) : 0;
                    $average = $subjectCount > 0 ? round($total / $subjectCount, 4) : null;
                    $aggt = $subjectCount > 0
                        ? (int) $satSubjects->sum(fn (array $item) => $this->gradePointFromGrade((string) ($item['grade'] ?? 'E')))
                        : null;
                    $gpa = $subjectCount > 0 && !is_null($aggt)
                        ? round($aggt / $subjectCount, 4)
                        : null;
                    $status = match (true) {
                        $subjectCount === 0 => 'ABS',
                        $subjectCount < self::EXPECTED_SUBJECTS => 'INC',
                        default => 'COMPLETE',
                    };
                    $overallGrade = $status === 'COMPLETE' && !is_null($average)
                        ? $this->gradeFromScaledScore($average)
                        : null;

                    return [
                        'candidate_pk' => (int) $candidate->candidate_pk,
                        'gender' => strtoupper(trim((string) ($candidate->gender ?? ''))),
                        'school_id' => (int) $candidate->school_id,
                        'region' => strtoupper(trim((string) ($candidate->region_name ?? '-'))),
                        'status' => $status,
                        'total_marks' => $status === 'COMPLETE' && $subjectCount > 0 ? round($total, 0) : null,
                        'avg_marks' => $status === 'COMPLETE' ? $average : null,
                        'overall_grade' => $overallGrade,
                        'gpa' => $status === 'COMPLETE' ? $gpa : null,
                    ];
                }

                $subjectRows = collect($marksByCandidate->get($candidate->candidate_pk, []))
                    ->map(function ($row) {
                        $isAbsent = strtoupper((string) ($row->grade ?? '')) === 'ABS' || is_null($row->marks_obtained);
                        $score = $isAbsent ? null : $this->scaledScore50((float) ($row->marks_obtained ?? 0), (float) ($row->max_marks ?: 100));
                        $grade = $isAbsent ? 'ABS' : $this->gradeFromScaledScore($score);

                        return [
                            'code' => strtoupper((string) ($row->subject_code ?? '')),
                            'subject_name' => strtoupper(trim((string) ($row->subject_name ?? ''))),
                            'subject_short' => $this->candidateSubjectLabel((string) ($row->subject_name ?? '')),
                            'score' => $score,
                            'grade' => $grade,
                            'is_absent' => $isAbsent,
                        ];
                    })
                    ->sortBy(fn (array $item) => $this->subjectOrderIndex((string) ($item['subject_name'] ?? '')))
                    ->values();

                $satSubjects = $subjectRows->filter(fn (array $item) => !($item['is_absent'] ?? false));
                $subjectCount = $satSubjects->count();
                $total = $subjectCount > 0 ? round((float) $satSubjects->sum('score'), 4) : 0;
                $average = $subjectCount > 0 ? round($total / $subjectCount, 4) : null;
                $aggt = $subjectCount > 0
                    ? (int) $satSubjects->sum(fn (array $item) => $this->gradePointFromGrade((string) ($item['grade'] ?? 'E')))
                    : null;
                $gpa = $subjectCount > 0 && !is_null($aggt)
                    ? round($aggt / $subjectCount, 4)
                    : null;
                $status = match (true) {
                    $subjectCount === 0 => 'ABS',
                    $subjectCount < self::EXPECTED_SUBJECTS => 'INC',
                    default => 'COMPLETE',
                };
                $overallGrade = $status === 'COMPLETE' && !is_null($average)
                    ? $this->gradeFromScaledScore($average)
                    : null;

                return [
                    'candidate_pk' => (int) $candidate->candidate_pk,
                    'index_number' => (string) ($candidate->index_number ?? '-'),
                    'prem_no' => (string) ($candidate->prem_no ?? '-'),
                    'candidate' => strtoupper(trim((string) ($candidate->full_name ?? $candidate->index_number))) ?: '-',
                    'gender' => strtoupper(trim((string) ($candidate->gender ?? ''))),
                    'school_id' => (int) $candidate->school_id,
                    'school' => trim(((string) ($candidate->school_code ?? '')) . ' - ' . ((string) ($candidate->school_name ?? ''))),
                    'school_name' => strtoupper(trim((string) ($candidate->school_name ?? ''))),
                    'region' => strtoupper(trim((string) ($candidate->region_name ?? '-'))),
                    'council' => strtoupper(trim((string) ($candidate->council_name ?? $candidate->district_name ?? '-'))) ?: '-',
                    'district' => strtoupper(trim(((string) ($candidate->district_code ?? '')) . ' - ' . ((string) ($candidate->district_name ?? '')))) ?: '-',
                    'ownership' => strtoupper(trim((string) ($candidate->ownership ?? 'UNKNOWN'))) ?: 'UNKNOWN',
                    'status' => $status,
                    'subject_rows' => $subjectRows->all(),
                    'subject_results_text' => $subjectRows->map(function (array $item) {
                        if ($item['is_absent'] ?? false) {
                            return "{$item['subject_short']} - ABS";
                        }
                        $score = number_format((float) $item['score'], 0);
                        return "{$item['subject_short']} - {$score} '{$item['grade']}'";
                    })->implode(', '),
                    'total_marks' => $status === 'COMPLETE' && $subjectCount > 0 ? round($total, 0) : null,
                    'avg_marks' => $status === 'COMPLETE' ? $average : null,
                    'overall_grade' => $overallGrade,
                    'gpa' => $status === 'COMPLETE' ? $gpa : null,
                ];
            });

            foreach ($mapped as $item) {
                $candidateRows->push($item);
            }

            unset($candidateIds, $marksQuery, $marksByCandidate, $mapped);
            gc_collect_cycles();
        });

        return $candidateRows;
    }

    public function regionalCandidateRows(Region $region, int $examYearValue, bool $lightweight = false, string $mode = '', int $chunkSize = 500): Collection
    {
        $publication = DB::table('psle_result_publications as prp')
            ->join('result_snapshots as rs', 'rs.id', '=', 'prp.snapshot_id')
            ->where('prp.exam_year_id', function ($query) use ($examYearValue) {
                $query->select('id')->from('exam_years')->where('year_label', $examYearValue)->limit(1);
            })
            ->where('prp.status', 'published')
            ->where('rs.is_active', true)
            ->where('rs.is_rolled_back', false)
            ->select('rs.id as snapshot_id')
            ->first();

        $snapshotId = $publication ? $publication->snapshot_id : 0;

        $selectCols = [
            'c.id as candidate_pk',
            'c.gender',
            's.id as school_id',
            's.ownership',
        ];

        if (!$lightweight) {
            $selectCols = array_merge($selectCols, [
                'c.candidate_id as index_number',
                'c.prem_no',
                'c.full_name',
                's.code as school_code',
                's.name as school_name',
                'r.id as region_id',
                'r.name as region_name',
                'd.id as district_id',
                'd.code as district_code',
                'd.name as district_name',
                'dc.id as council_id',
                'dc.code as council_code',
                'dc.name as council_name',
            ]);
        } else {
            if ($mode === 'councilwise' || $mode === 'zonal-councilwise') {
                $selectCols[] = 'r.id as region_id';
                $selectCols[] = 'r.name as region_name';
                $selectCols[] = 'dc.id as council_id';
                $selectCols[] = 'dc.name as council_name';
                $selectCols[] = 'd.name as district_name';
            } elseif ($mode === 'zonal-schoolwise') {
                $selectCols[] = 'r.id as region_id';
                $selectCols[] = 'r.name as region_name';
                $selectCols[] = 'dc.id as council_id';
                $selectCols[] = 'dc.name as council_name';
                $selectCols[] = 's.name as school_name';
            } elseif ($mode === 'districtwise') {
                $selectCols[] = 'd.name as district_name';
                $selectCols[] = 'd.code as district_code';
            }
        }

        $registrationsQuery = DB::table('candidate_exam_registrations as cer')
            ->join('candidates as c', 'c.id', '=', 'cer.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->leftJoin('regions as r', 'r.id', '=', 's.region_id')
            ->leftJoin('districts as d', 'd.id', '=', 's.district_id')
            ->leftJoin('district_councils as dc', 'dc.id', '=', 's.council_id')
            ->where('s.region_id', $region->id)
            ->where('cer.exam_type_id', $this->psleExamTypeId())
            ->where('cer.year', $examYearValue)
            ->select($selectCols)
            ->orderBy('c.id');

        $candidateRows = collect();

        $registrationsQuery->chunk($chunkSize, function ($chunk) use (&$candidateRows, $snapshotId, $examYearValue, $lightweight, $mode) {
            $candidateIds = $chunk->pluck('candidate_pk')->all();

            $marksQuery = DB::table('subject_marks as sm')
                ->whereIn('sm.candidate_id', $candidateIds)
                ->where('sm.exam_type_id', $this->psleExamTypeId())
                ->where('sm.year', $examYearValue)
                ->where('sm.snapshot_id', $snapshotId);

            if ($lightweight) {
                $marksQuery->select([
                    'sm.candidate_id',
                    'sm.marks_obtained',
                    'sm.max_marks',
                    'sm.grade',
                ]);
            } else {
                $marksQuery->join('subjects as sb', 'sb.id', '=', 'sm.subject_id')
                    ->select([
                        'sm.candidate_id',
                        'sm.marks_obtained',
                        'sm.max_marks',
                        'sm.grade',
                        'sb.code as subject_code',
                        'sb.name as subject_name',
                    ]);
            }

            $marksByCandidate = $marksQuery->get()->groupBy('candidate_id');

            $mapped = $chunk->map(function ($candidate) use ($marksByCandidate, $lightweight, $mode) {
                if ($lightweight) {
                    $subjectRows = collect($marksByCandidate->get($candidate->candidate_pk, []))
                        ->map(function ($row) {
                            $isAbsent = strtoupper((string) ($row->grade ?? '')) === 'ABS' || is_null($row->marks_obtained);
                            $score = $isAbsent ? null : $this->scaledScore50((float) ($row->marks_obtained ?? 0), (float) ($row->max_marks ?: 100));
                            $grade = $isAbsent ? 'ABS' : $this->gradeFromScaledScore($score);

                            return [
                                'score' => $score,
                                'grade' => $grade,
                                'is_absent' => $isAbsent,
                            ];
                        });

                    $satSubjects = $subjectRows->filter(fn (array $item) => !($item['is_absent'] ?? false));
                    $subjectCount = $satSubjects->count();
                    $total = $subjectCount > 0 ? round((float) $satSubjects->sum('score'), 4) : 0;
                    $average = $subjectCount > 0 ? round($total / $subjectCount, 4) : null;
                    $aggt = $subjectCount > 0
                        ? (int) $satSubjects->sum(fn (array $item) => $this->gradePointFromGrade((string) ($item['grade'] ?? 'E')))
                        : null;
                    $gpa = $subjectCount > 0 && !is_null($aggt)
                        ? round($aggt / $subjectCount, 4)
                        : null;
                    $status = match (true) {
                        $subjectCount === 0 => 'ABS',
                        $subjectCount < self::EXPECTED_SUBJECTS => 'INC',
                        default => 'COMPLETE',
                    };
                    $overallGrade = $status === 'COMPLETE' && !is_null($average)
                        ? $this->gradeFromScaledScore($average)
                        : null;

                    return [
                        'candidate_pk' => (int) $candidate->candidate_pk,
                        'gender' => strtoupper(trim((string) ($candidate->gender ?? ''))),
                        'school_id' => (int) $candidate->school_id,
                        'school' => $mode === 'zonal-schoolwise' ? strtoupper(trim((string) ($candidate->school_name ?? '-'))) : '-',
                        'school_name' => $mode === 'zonal-schoolwise' ? strtoupper(trim((string) ($candidate->school_name ?? '-'))) : '-',
                        'council' => ($mode === 'councilwise' || $mode === 'zonal-councilwise' || $mode === 'zonal-schoolwise') ? strtoupper(trim((string) ($candidate->council_name ?? $candidate->district_name ?? '-'))) : '-',
                        'council_id' => isset($candidate->council_id) ? (int) $candidate->council_id : null,
                        'region' => ($mode === 'councilwise' || $mode === 'zonal-councilwise' || $mode === 'zonal-schoolwise') ? strtoupper(trim((string) ($candidate->region_name ?? '-'))) : '-',
                        'region_id' => isset($candidate->region_id) ? (int) $candidate->region_id : null,
                        'district' => $mode === 'districtwise' ? strtoupper(trim(((string) ($candidate->district_code ?? '')) . ' - ' . ((string) ($candidate->district_name ?? '')))) : '-',
                        'ownership' => strtoupper(trim((string) ($candidate->ownership ?? 'UNKNOWN'))) ?: 'UNKNOWN',
                        'status' => $status,
                        'total_marks' => ($status === 'COMPLETE' || $status === 'INC') && $subjectCount > 0 ? round($total, 0) : null,
                        'avg_marks' => $status === 'COMPLETE' ? $average : null,
                        'overall_grade' => $overallGrade,
                        'gpa' => $status === 'COMPLETE' ? $gpa : null,
                    ];
                }

                $subjectRows = collect($marksByCandidate->get($candidate->candidate_pk, []))
                    ->map(function ($row) {
                        $isAbsent = strtoupper((string) ($row->grade ?? '')) === 'ABS' || is_null($row->marks_obtained);
                        $score = $isAbsent ? null : $this->scaledScore50((float) ($row->marks_obtained ?? 0), (float) ($row->max_marks ?: 100));
                        $grade = $isAbsent ? 'ABS' : $this->gradeFromScaledScore($score);

                        return [
                            'code' => strtoupper((string) ($row->subject_code ?? '')),
                            'subject_name' => strtoupper(trim((string) ($row->subject_name ?? ''))),
                            'subject_short' => $this->candidateSubjectLabel((string) ($row->subject_name ?? '')),
                            'score' => $score,
                            'grade' => $grade,
                            'is_absent' => $isAbsent,
                        ];
                    })
                    ->sortBy(fn (array $item) => $this->subjectOrderIndex((string) ($item['subject_name'] ?? '')))
                    ->values();

                $satSubjects = $subjectRows->filter(fn (array $item) => !($item['is_absent'] ?? false));
                $subjectCount = $satSubjects->count();
                $total = $subjectCount > 0 ? round((float) $satSubjects->sum('score'), 4) : 0;
                $average = $subjectCount > 0 ? round($total / $subjectCount, 4) : null;
                $aggt = $subjectCount > 0
                    ? (int) $satSubjects->sum(fn (array $item) => $this->gradePointFromGrade((string) ($item['grade'] ?? 'E')))
                    : null;
                $gpa = $subjectCount > 0 && !is_null($aggt)
                    ? round($aggt / $subjectCount, 4)
                    : null;
                $status = match (true) {
                    $subjectCount === 0 => 'ABS',
                    $subjectCount < self::EXPECTED_SUBJECTS => 'INC',
                    default => 'COMPLETE',
                };
                $overallGrade = $status === 'COMPLETE' && !is_null($average)
                    ? $this->gradeFromScaledScore($average)
                    : null;

                return [
                    'candidate_pk' => (int) $candidate->candidate_pk,
                    'index_number' => (string) ($candidate->index_number ?? '-'),
                    'prem_no' => (string) ($candidate->prem_no ?? '-'),
                    'candidate' => strtoupper(trim((string) ($candidate->full_name ?? $candidate->index_number))) ?: '-',
                    'gender' => strtoupper(trim((string) ($candidate->gender ?? ''))),
                    'school_id' => (int) $candidate->school_id,
                    'school' => trim(((string) ($candidate->school_code ?? '')) . ' - ' . ((string) ($candidate->school_name ?? ''))),
                    'school_name' => strtoupper(trim((string) ($candidate->school_name ?? ''))),
                    'region' => strtoupper(trim((string) ($candidate->region_name ?? '-'))),
                    'region_id' => isset($candidate->region_id) ? (int) $candidate->region_id : null,
                    'council' => strtoupper(trim((string) ($candidate->council_name ?? $candidate->district_name ?? '-'))) ?: '-',
                    'council_id' => isset($candidate->council_id) ? (int) $candidate->council_id : null,
                    'district' => strtoupper(trim(((string) ($candidate->district_code ?? '')) . ' - ' . ((string) ($candidate->district_name ?? '')))) ?: '-',
                    'ownership' => strtoupper(trim((string) ($candidate->ownership ?? 'UNKNOWN'))) ?: 'UNKNOWN',
                    'status' => $status,
                    'subject_rows' => $subjectRows->all(),
                    'subject_results_text' => $subjectRows->map(function (array $item) {
                        if ($item['is_absent'] ?? false) {
                            return "{$item['subject_short']} - ABS";
                        }
                        $score = number_format((float) $item['score'], 0);
                        return "{$item['subject_short']} - {$score} '{$item['grade']}'";
                    })->implode(', '),
                    'total_marks' => ($status === 'COMPLETE' || $status === 'INC') && $subjectCount > 0 ? round($total, 0) : null,
                    'avg_marks' => $status === 'COMPLETE' ? $average : null,
                    'overall_grade' => $overallGrade,
                    'aggt' => $status === 'COMPLETE' ? $aggt : null,
                    'gpa' => $status === 'COMPLETE' ? $gpa : null,
                ];
            });

            foreach ($mapped as $item) {
                $candidateRows->push($item);
            }

            unset($candidateIds, $marksQuery, $marksByCandidate, $mapped);
            gc_collect_cycles();
        });

        return $candidateRows;
    }

    public function buildGroupedRows(Collection $candidateRows, string $mode): array
    {
        $groups = [];

        foreach ($candidateRows as $candidate) {
            $groupKey = match ($mode) {
                'general' => strtoupper((string) ($candidate['gender'] ?? '')) === 'F' ? 'F' : 'M',
                'councilwise' => (string) ($candidate['council'] ?? '-'),
                'zonal-councilwise' => ((string) ($candidate['region_id'] ?? '0')) . '-' . ((string) ($candidate['council_id'] ?? '0')),
                'zonal-schoolwise' => (string) ($candidate['school_id'] ?? '0'),
                'districtwise' => (string) ($candidate['district'] ?? '-'),
                'ownership' => (string) ($candidate['ownership'] ?? 'UNKNOWN'),
                'regionalwise' => (string) ($candidate['region'] ?? '-'),
                default => (string) ($candidate['school_id'] ?? '0'),
            };

            if ($mode === 'regionalwise' && in_array(strtoupper(trim($groupKey)), ['-', '', 'UNASSIGNED', 'UNKNOWN'], true)) {
                continue;
            }

            if ($mode === 'zonal-councilwise') {
                $councilVal = strtoupper(trim((string) ($candidate['council'] ?? '')));
                $regionVal = strtoupper(trim((string) ($candidate['region'] ?? '')));
                if (in_array($councilVal, ['-', '', 'UNKNOWN', 'UNASSIGNED'], true) || in_array($regionVal, ['-', '', 'UNKNOWN', 'UNASSIGNED'], true)) {
                    continue;
                }
            }

            if ($mode === 'zonal-schoolwise') {
                $schoolVal = strtoupper(trim((string) ($candidate['school'] ?? '')));
                $councilVal = strtoupper(trim((string) ($candidate['council'] ?? '')));
                $regionVal = strtoupper(trim((string) ($candidate['region'] ?? '')));
                if (in_array($schoolVal, ['-', '', 'UNKNOWN', 'UNASSIGNED'], true) ||
                    in_array($councilVal, ['-', '', 'UNKNOWN', 'UNASSIGNED'], true) ||
                    in_array($regionVal, ['-', '', 'UNKNOWN', 'UNASSIGNED'], true)) {
                    continue;
                }
            }

            if ($mode === 'ownership') {
                $ownershipVal = strtoupper(trim((string) ($candidate['ownership'] ?? '')));
                if (in_array($ownershipVal, ['-', '', 'UNKNOWN', 'UNASSIGNED'], true)) {
                    continue;
                }
            }

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = $this->groupRowTemplate($candidate, $mode);
            }

            $bucket = &$groups[$groupKey];
            $genderKey = strtoupper((string) ($candidate['gender'] ?? '')) === 'F' ? 'f' : 'm';
            $bucket['registered'][$genderKey]++;
            $bucket['registered']['t']++;
            $schoolId = (int) ($candidate['school_id'] ?? 0);

            if ($schoolId > 0) {
                if (!isset($bucket['school_totals'][$schoolId])) {
                    $bucket['school_totals'][$schoolId] = [
                        'registered' => 0,
                        'sat' => 0,
                        'total_marks_sum' => 0.0,
                    ];
                }

                $bucket['school_totals'][$schoolId]['registered']++;
                if (($candidate['status'] ?? '') === 'COMPLETE') {
                    $bucket['school_totals'][$schoolId]['sat']++;
                    $bucket['school_totals'][$schoolId]['total_marks_sum'] += (float) ($candidate['total_marks'] ?? 0);
                }
            }

            if ($mode === 'ownership') {
                $bucket['school_ids'][(int) ($candidate['school_id'] ?? 0)] = true;
            }

            if (($candidate['status'] ?? '') === 'ABS') {
                $bucket['absent'][$genderKey]++;
                $bucket['absent']['t']++;
                unset($bucket);
                continue;
            }

            $bucket['sat'][$genderKey]++;
            $bucket['sat']['t']++;

            if (($candidate['status'] ?? '') === 'INC') {
                $bucket['inc'][$genderKey]++;
                $bucket['inc']['t']++;
                $bucket['total_marks_sum'] += (float) ($candidate['total_marks'] ?? 0);
                unset($bucket);
                continue;
            }

            $bucket['clean'][$genderKey]++;
            $bucket['clean']['t']++;
            $gradeKey = strtolower((string) ($candidate['overall_grade'] ?? 'e'));
            if (isset($bucket['grades'][$gradeKey])) {
                $bucket['grades'][$gradeKey][$genderKey]++;
                $bucket['grades'][$gradeKey]['t']++;
            }
            if (in_array(strtoupper((string) ($candidate['overall_grade'] ?? '')), ['A', 'B', 'C'], true)) {
                $bucket['pass_ac'][$genderKey]++;
                $bucket['pass_ac']['t']++;
            }
            if (in_array(strtoupper((string) ($candidate['overall_grade'] ?? '')), ['A', 'B', 'C', 'D'], true)) {
                $bucket['pass_ad'][$genderKey]++;
                $bucket['pass_ad']['t']++;
            }
            $bucket['total_marks_sum'] += (float) ($candidate['total_marks'] ?? 0);

            unset($bucket);
        }

        $rows = collect($groups)->map(function (array $row) use ($mode) {
            $registeredTotal = max((int) $row['registered']['t'], 0);
            $satTotal = max((int) $row['sat']['t'], 0);

            $row['absent']['pct'] = $registeredTotal > 0 ? ($row['absent']['t'] / $registeredTotal) * 100 : 0.0;
            $row['sat']['pct'] = $registeredTotal > 0 ? ($row['sat']['t'] / $registeredTotal) * 100 : 0.0;
            $row['inc']['pct'] = $registeredTotal > 0 ? ($row['inc']['t'] / $registeredTotal) * 100 : 0.0;
            $row['clean']['pct'] = $registeredTotal > 0 ? ($row['clean']['t'] / $registeredTotal) * 100 : 0.0;
            $row['pass_ac']['pct'] = $row['clean']['t'] > 0 ? ($row['pass_ac']['t'] / $row['clean']['t']) * 100 : 0.0;
            $row['pass_ad']['pct'] = $row['clean']['t'] > 0 ? ($row['pass_ad']['t'] / $row['clean']['t']) * 100 : 0.0;
            ['avg_marks' => $row['avg_marks'], 'avg_grade' => $row['avg_grade']] = $this->groupedAverageStats($row, (int) $row['clean']['t'], $mode);

            if ($mode === 'ownership') {
                $row['schools_count'] = count($row['school_ids']);
                unset($row['school_ids']);
            }

            unset($row['total_marks_sum'], $row['school_totals']);

            return $row;
        })->sort(function (array $left, array $right) {
            $leftAvg = $left['avg_marks'] ?? -INF;
            $rightAvg = $right['avg_marks'] ?? -INF;
            if ($leftAvg !== $rightAvg) {
                return $rightAvg <=> $leftAvg;
            }

            $leftPass = $left['pass_ac']['t'] ?? 0;
            $rightPass = $right['pass_ac']['t'] ?? 0;
            if ($leftPass !== $rightPass) {
                return $rightPass <=> $leftPass;
            }

            $leftName = strtoupper((string) ($left['sort_label'] ?? ''));
            $rightName = strtoupper((string) ($right['sort_label'] ?? ''));

            return strcmp($leftName, $rightName);
        })->values();

        $rows = $this->applyPositions($rows);

        return [$rows, $this->summariseGroupedRows($rows)];
    }

    public function buildStudentRankingRows(Collection $candidateRows, string $evaluation): Collection
    {
        $rows = $candidateRows
            ->filter(fn ($row) => ($row['status'] ?? '') === 'COMPLETE' && !is_null($row['total_marks'] ?? null));

        if (in_array($evaluation, ['best-ten-girls', 'least-ten-girls'], true)) {
            $rows = $rows->filter(fn ($row) => strtoupper((string) ($row['gender'] ?? '')) === 'F');
        }

        if (in_array($evaluation, ['best-ten-boys', 'least-ten-boys'], true)) {
            $rows = $rows->filter(fn ($row) => strtoupper((string) ($row['gender'] ?? '')) === 'M');
        }

        $isBest = in_array($evaluation, ['best-ten-girls', 'best-ten-boys', 'overall-best-ten-students'], true);
        $rows = $rows->sort(function (array $left, array $right) use ($isBest) {
            $leftTotal = (float) ($left['total_marks'] ?? 0);
            $rightTotal = (float) ($right['total_marks'] ?? 0);
            if ($leftTotal !== $rightTotal) {
                return $isBest ? ($rightTotal <=> $leftTotal) : ($leftTotal <=> $rightTotal);
            }

            return strcmp((string) ($left['index_number'] ?? ''), (string) ($right['index_number'] ?? ''));
        })->take(10)->values();

        return $rows->map(function (array $row, int $index) {
            $row['position'] = $index + 1;
            return $row;
        })->values();
    }

    public function buildSubjectwiseRows(Collection $candidateRows, ?Region $region = null, ?int $examYear = null): Collection
    {
        if ($region && $examYear) {
            return $this->buildSubjectwiseRowsFromDb([$region->id], $examYear);
        }

        $registered = $candidateRows->count();
        $subjectBuckets = [];

        foreach ($candidateRows as $candidate) {
            $subjects = collect($candidate['subject_rows'] ?? []);
            foreach ($this->subjectCatalog() as $subjectName => $subjectMeta) {
                if (!isset($subjectBuckets[$subjectName])) {
                    $subjectBuckets[$subjectName] = [
                        'code' => $subjectMeta['code'],
                        'name' => $subjectMeta['full'],
                        'registered' => $registered,
                        'sat' => 0,
                        'abs' => 0,
                        'grade_a' => 0,
                        'grade_b' => 0,
                        'grade_c' => 0,
                        'grade_d' => 0,
                        'grade_e' => 0,
                        'total_score' => 0.0,
                    ];
                }

                $subject = $subjects->first(fn ($item) => strtoupper((string) ($item['subject_name'] ?? '')) === $subjectName);
                if (!$subject) {
                    $subjectBuckets[$subjectName]['abs']++;
                    continue;
                }

                $subjectBuckets[$subjectName]['sat']++;
                $subjectBuckets[$subjectName]['total_score'] += (float) ($subject['score'] ?? 0);
                $gradeKey = 'grade_' . strtolower((string) ($subject['grade'] ?? 'e'));
                if (isset($subjectBuckets[$subjectName][$gradeKey])) {
                    $subjectBuckets[$subjectName][$gradeKey]++;
                }
            }
        }

        return collect($subjectBuckets)->map(function (array $row) {
            $graded = (int) $row['grade_a'] + (int) $row['grade_b'] + (int) $row['grade_c'] + (int) $row['grade_d'] + (int) $row['grade_e'];
            $average = $graded > 0 ? round($row['total_score'] / $graded, 4) : 0.0;
            $grade = $this->gradeFromScaledScore($average);
            $gpa = $graded > 0
                ? round((($row['grade_a'] * 1) + ($row['grade_b'] * 2) + ($row['grade_c'] * 3) + ($row['grade_d'] * 4) + ($row['grade_e'] * 5)) / $graded, 4)
                : null;

            return [
                'code' => $row['code'],
                'name' => $row['name'],
                'registered' => $row['registered'],
                'sat' => $row['sat'],
                'abs' => $row['abs'],
                'grade_a' => $row['grade_a'],
                'grade_b' => $row['grade_b'],
                'grade_c' => $row['grade_c'],
                'grade_d' => $row['grade_d'],
                'grade_e' => $row['grade_e'],
                'a_to_c' => $row['grade_a'] + $row['grade_b'] + $row['grade_c'],
                'a_to_d' => $row['grade_a'] + $row['grade_b'] + $row['grade_c'] + $row['grade_d'],
                'avg_marks' => $average,
                'grade' => $grade,
                'gpa' => $gpa,
                'competence' => $this->gradeMeta($grade),
            ];
        })->values();
    }

    public function buildSubjectwiseSummary(Collection $candidateRows, Region $region, ?int $examYear = null): array
    {
        if ($examYear) {
            return $this->buildSubjectwiseSummaryFromDb([$region->id], $examYear, $region->name);
        }

        $summary = [
            'F' => ['REGIST' => 0, 'SAT' => 0, 'WITHHELD' => 0, 'CLEAN' => 0, 'A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'INC' => 0, 'ABS' => 0],
            'M' => ['REGIST' => 0, 'SAT' => 0, 'WITHHELD' => 0, 'CLEAN' => 0, 'A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'INC' => 0, 'ABS' => 0],
        ];

        foreach ($candidateRows as $row) {
            $sex = strtoupper((string) ($row['gender'] ?? ''));
            if (!in_array($sex, ['F', 'M'], true)) {
                continue;
            }

            $summary[$sex]['REGIST']++;
            if (($row['status'] ?? '') === 'ABS') {
                $summary[$sex]['ABS']++;
                continue;
            }

            $summary[$sex]['SAT']++;
            if (($row['status'] ?? '') === 'INC') {
                $summary[$sex]['INC']++;
                continue;
            }

            $summary[$sex]['CLEAN']++;
            $grade = strtoupper((string) ($row['overall_grade'] ?? ''));
            if (isset($summary[$sex][$grade])) {
                $summary[$sex][$grade]++;
            }
        }

        $summary['T'] = ['REGIST' => 0, 'SAT' => 0, 'WITHHELD' => 0, 'CLEAN' => 0, 'A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'INC' => 0, 'ABS' => 0];
        foreach (array_keys($summary['T']) as $key) {
            $summary['T'][$key] = $summary['F'][$key] + $summary['M'][$key];
        }

        $completeRows = $candidateRows->filter(fn ($row) => ($row['status'] ?? '') === 'COMPLETE')->values();
        $overallAverage = round((float) ($completeRows->pluck('avg_marks')->filter(fn ($v) => $v !== null)->avg() ?? 0), 4);

        return [
            'subjects' => number_format($this->subjectCatalog()->count()),
            'grade_summary' => $summary,
            'overall' => [
                'region' => strtoupper((string) $region->name),
                'passed' => $summary['T']['A'] + $summary['T']['B'] + $summary['T']['C'],
                'average_score' => $overallAverage,
                'average_info' => $overallAverage > 0 ? $this->averageMarksCompetence($overallAverage) : null,
            ],
        ];
    }

    private function buildMarkEntryRows(Region $region, ?Collection $candidateRows, int $examYearValue, array $filters = []): Collection
    {
        $activityDate = trim((string) ($filters['activity_date'] ?? ''));
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));

        $publication = DB::table('psle_result_publications as prp')
            ->join('result_snapshots as rs', 'rs.id', '=', 'prp.snapshot_id')
            ->where('prp.exam_year_id', function ($query) use ($examYearValue) {
                $query->select('id')->from('exam_years')->where('year_label', $examYearValue)->limit(1);
            })
            ->where('prp.status', 'published')
            ->where('rs.is_active', true)
            ->where('rs.is_rolled_back', false)
            ->select('rs.id as snapshot_id')
            ->first();

        $snapshotId = $publication ? $publication->snapshot_id : 0;

        $marksQuery = DB::table('subject_marks as sm')
            ->join('subjects as sb', 'sb.id', '=', 'sm.subject_id')
            ->join('candidate_exam_registrations as cer', 'cer.candidate_id', '=', 'sm.candidate_id')
            ->join('candidates as c', 'c.id', '=', 'cer.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->where('s.region_id', $region->id)
            ->where('cer.exam_type_id', $this->psleExamTypeId())
            ->where('cer.year', $examYearValue)
            ->where('sm.exam_type_id', $this->psleExamTypeId())
            ->where('sm.year', $examYearValue)
            ->where('sm.snapshot_id', $snapshotId);

        if ($activityDate !== '') {
            $marksQuery->where(function ($query) use ($activityDate) {
                $query->whereDate('sm.created_at', $activityDate)
                    ->orWhereDate('sm.updated_at', $activityDate);
            });
        } elseif ($dateFrom !== '' || $dateTo !== '') {
            $from = $dateFrom !== '' ? $dateFrom : $dateTo;
            $to = $dateTo !== '' ? $dateTo : $dateFrom;

            $marksQuery->where(function ($query) use ($from, $to) {
                $query->whereBetween(DB::raw('DATE(sm.created_at)'), [$from, $to])
                    ->orWhereBetween(DB::raw('DATE(sm.updated_at)'), [$from, $to]);
            });
        }

        $marksData = $marksQuery->select([
            'c.school_id',
            DB::raw("COUNT(CASE WHEN UPPER(sb.name) = 'KISWAHILI' THEN 1 END) as kisw_count"),
            DB::raw("COUNT(CASE WHEN UPPER(sb.name) = 'ENGLISH LANGUAGE' THEN 1 END) as eng_count"),
            DB::raw("COUNT(CASE WHEN UPPER(sb.name) = 'SOCIAL STUDIES AND VOCATIONAL SKILLS' THEN 1 END) as sst_count"),
            DB::raw("COUNT(CASE WHEN UPPER(sb.name) = 'MATHEMATICS' THEN 1 END) as math_count"),
            DB::raw("COUNT(CASE WHEN UPPER(sb.name) = 'SCIENCE AND TECHNOLOGY' THEN 1 END) as sci_count"),
            DB::raw("COUNT(CASE WHEN UPPER(sb.name) = 'CIVIC AND MORAL EDUCATION' THEN 1 END) as cme_count"),
            DB::raw("MAX(COALESCE(sm.updated_at, sm.created_at)) as school_last_activity"),
        ])->groupBy('c.school_id')->get()->keyBy('school_id');

        $schools = DB::table('candidate_exam_registrations as cer')
            ->join('candidates as c', 'c.id', '=', 'cer.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->where('s.region_id', $region->id)
            ->where('cer.exam_type_id', $this->psleExamTypeId())
            ->where('cer.year', $examYearValue)
            ->select([
                's.id as school_id',
                's.name as school_name',
                DB::raw('COUNT(c.id) as registered_count'),
            ])
            ->groupBy('s.id', 's.name')
            ->get();

        return $schools->map(function ($school) use ($marksData) {
            $schoolId = $school->school_id;
            $registered = (int) $school->registered_count;
            $markRow = $marksData->get($schoolId);

            $kiswCount = $markRow ? (int) $markRow->kisw_count : 0;
            $engCount = $markRow ? (int) $markRow->eng_count : 0;
            $sstCount = $markRow ? (int) $markRow->sst_count : 0;
            $mathCount = $markRow ? (int) $markRow->math_count : 0;
            $sciCount = $markRow ? (int) $markRow->sci_count : 0;
            $cmeCount = $markRow ? (int) $markRow->cme_count : 0;
            $lastActivityAt = $markRow ? $markRow->school_last_activity : null;

            $expectedScripts = $registered * 6;
            $markedScripts = $kiswCount + $engCount + $sstCount + $mathCount + $sciCount + $cmeCount;
            $pendingScripts = max($expectedScripts - $markedScripts, 0);

            $markedPct = $expectedScripts > 0 ? ($markedScripts / $expectedScripts) * 100 : 0.0;
            $pendingPct = $expectedScripts > 0 ? ($pendingScripts / $expectedScripts) * 100 : 0.0;
            $completion = $markedPct;

            $status = match (true) {
                $completion >= 100 => 'Complete',
                $completion >= 80 => 'Near Complete',
                $completion > 0 => 'In Progress',
                default => 'Not Started',
            };

            return [
                'school' => (string) $school->school_name,
                'registered' => $registered,
                'kisw' => $kiswCount,
                'kisw_pct' => round($registered > 0 ? ($kiswCount / $registered) * 100 : 0.0, 1),
                'eng' => $engCount,
                'eng_pct' => round($registered > 0 ? ($engCount / $registered) * 100 : 0.0, 1),
                'sst' => $sstCount,
                'sst_pct' => round($registered > 0 ? ($sstCount / $registered) * 100 : 0.0, 1),
                'math' => $mathCount,
                'math_pct' => round($registered > 0 ? ($mathCount / $registered) * 100 : 0.0, 1),
                'sci' => $sciCount,
                'sci_pct' => round($registered > 0 ? ($sciCount / $registered) * 100 : 0.0, 1),
                'cme' => $cmeCount,
                'cme_pct' => round($registered > 0 ? ($cmeCount / $registered) * 100 : 0.0, 1),
                'total' => $expectedScripts,
                'marked_scripts' => $markedScripts,
                'marked_pct' => round($markedPct, 1),
                'pending_scripts' => $pendingScripts,
                'pending_pct' => round($pendingPct, 1),
                'completion' => round($completion, 1),
                'status' => $status,
                'last_activity_at' => $lastActivityAt,
                'last_activity_date' => $lastActivityAt ? date('Y-m-d', strtotime($lastActivityAt)) : null,
            ];
        })
        ->sort(function (array $left, array $right) {
            $leftCompletion = (float) ($left['completion'] ?? 0);
            $rightCompletion = (float) ($right['completion'] ?? 0);
            if ($leftCompletion !== $rightCompletion) {
                return $leftCompletion <=> $rightCompletion;
            }

            $leftPending = (int) ($left['pending_scripts'] ?? 0);
            $rightPending = (int) ($right['pending_scripts'] ?? 0);
            if ($leftPending !== $rightPending) {
                return $rightPending <=> $leftPending;
            }

            return strnatcasecmp((string) ($left['school'] ?? ''), (string) ($right['school'] ?? ''));
        })
        ->values()
        ->map(function (array $row, int $index) {
            $row['pos'] = $index + 1;
            return $row;
        });
    }

    public function markEntryStatusPayload(Request $request, Region $region, string $label, int $examYearValue, ?Collection $candidateRows = null): array
    {
        $filters = [
            'activity_date' => trim((string) $request->query('activity_date', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'status' => trim((string) $request->query('status', '')),
        ];

        $rows = $this->buildMarkEntryRows($region, $candidateRows, $examYearValue, $filters);
        if ($filters['status'] !== '') {
            $rows = $rows->filter(fn ($row) => strtoupper((string) ($row['status'] ?? '')) === strtoupper($filters['status']))->values();
        }

        return [
            'region' => $region,
            'evaluationLabel' => $label,
            'examYearValue' => $examYearValue,
            'rows' => $rows,
            'filters' => $filters,
            'summary' => [
                'schools' => number_format($rows->count()),
                'total_scripts' => number_format($rows->sum('total')),
                'marked_scripts' => number_format($rows->sum('marked_scripts')),
                'pending_scripts' => number_format($rows->sum('pending_scripts')),
                'completion' => number_format($rows->sum('total') > 0 ? ($rows->sum('marked_scripts') / $rows->sum('total')) * 100 : 0, 1),
            ],
            'summary_export' => [
                'schools' => number_format($rows->count()),
                'registered_candidates' => number_format((int) $rows->sum('registered')),
                'total_scripts' => number_format((int) $rows->sum('total')),
                'marked_scripts' => number_format((int) $rows->sum('marked_scripts')),
                'pending_scripts' => number_format((int) $rows->sum('pending_scripts')),
                'completion' => number_format((float) ($rows->sum('total') > 0 ? ($rows->sum('marked_scripts') / $rows->sum('total')) * 100 : 0), 1),
                'complete_schools' => number_format($rows->where('status', 'Complete')->count()),
                'in_progress_schools' => number_format($rows->where('status', 'In Progress')->count() + $rows->where('status', 'Near Complete')->count()),
                'not_started_schools' => number_format($rows->where('status', 'Not Started')->count()),
            ],
        ];
    }

    public function summariseGroupedRows(Collection $rows): array
    {
        $total = $this->groupTotalsTemplate();

        foreach ($rows as $row) {
            foreach (['m', 'f', 't'] as $sex) {
                $total['registered'][$sex] += $row['registered'][$sex];
                $total['absent'][$sex] += $row['absent'][$sex];
                $total['sat'][$sex] += $row['sat'][$sex];
                $total['inc'][$sex] += $row['inc'][$sex];
                $total['clean'][$sex] += $row['clean'][$sex];
            }

            foreach (['a', 'b', 'c', 'd', 'e'] as $grade) {
                foreach (['m', 'f', 't'] as $sex) {
                    $total['grades'][$grade][$sex] += $row['grades'][$grade][$sex];
                }
            }

            foreach (['m', 'f', 't'] as $sex) {
                $total['pass_ac'][$sex] += $row['pass_ac'][$sex];
                $total['pass_ad'][$sex] += $row['pass_ad'][$sex];
            }
        }

        $registeredTotal = max((int) $total['registered']['t'], 0);
        $satTotal = max((int) $total['sat']['t'], 0);
        $total['absent']['pct'] = $registeredTotal > 0 ? ($total['absent']['t'] / $registeredTotal) * 100 : 0.0;
        $total['sat']['pct'] = $registeredTotal > 0 ? ($total['sat']['t'] / $registeredTotal) * 100 : 0.0;
        $total['inc']['pct'] = $registeredTotal > 0 ? ($total['inc']['t'] / $registeredTotal) * 100 : 0.0;
        $total['clean']['pct'] = $registeredTotal > 0 ? ($total['clean']['t'] / $registeredTotal) * 100 : 0.0;
        $total['pass_ac']['pct'] = $satTotal > 0 ? ($total['pass_ac']['t'] / $satTotal) * 100 : 0.0;
        $total['pass_ad']['pct'] = $satTotal > 0 ? ($total['pass_ad']['t'] / $satTotal) * 100 : 0.0;

        return $total;
    }

    public function applyPositions(Collection $rows): Collection
    {
        return $rows->values()->map(function (array $row, int $index) {
            $row['pos'] = $index + 1;
            return $row;
        })->values();
    }

    private function groupedAverage(Collection $rows): ?float
    {
        $average = $rows->filter(function ($row) {
            return !is_null($row['avg_marks'] ?? null)
                && (float) ($row['avg_marks'] ?? 0) > 0
                && (int) data_get($row, 'sat.t', 0) > 0;
        })->pluck('avg_marks')->avg();

        return is_null($average) ? null : round((float) $average, 2);
    }

    private function groupedAverageStats(array $row, int $satTotal, string $mode): array
    {
        if ($mode === 'schoolwise' || $mode === 'zonal-schoolwise') {
            $average = $satTotal > 0
                ? \App\Services\Results\PsleSchoolAverageService::calculate(
                    (float) ($row['total_marks_sum'] ?? 0),
                    $satTotal,
                    max((int) $row['registered']['t'], 0)
                )['average']
                : null;
        } else {
            $schoolAverages = collect($row['school_totals'] ?? [])
                ->filter(fn ($school) => (int) ($school['sat'] ?? 0) > 0)
                ->map(function ($school) {
                    return \App\Services\Results\PsleSchoolAverageService::calculate(
                        (float) ($school['total_marks_sum'] ?? 0),
                        (int) $school['sat'],
                        (int) $school['registered']
                    )['average'];
                })
                ->filter(fn ($average) => (float) $average > 0)
                ->values();

            $average = $schoolAverages->isNotEmpty()
                ? round((float) $schoolAverages->avg(), 4)
                : null;
        }

        $grade = !is_null($average)
            ? $this->gradeFromScaledScore($average / self::EXPECTED_SUBJECTS)
            : null;

        return [
            'avg_marks' => $average,
            'avg_grade' => $grade,
        ];
    }

    public function zonalRankForGroupedMode(int $examYearValue, Region $currentRegion, string $mode, ?callable $candidateFilter = null, ?string $filterType = null): array
    {
        $filterTypeStr = $filterType ?: 'none';
        $cacheKey = "psle_zonal_rank_{$examYearValue}_{$currentRegion->id}_{$mode}_{$filterTypeStr}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 60, function () use ($examYearValue, $currentRegion, $mode, $candidateFilter) {
            $rankedRegions = Region::query()
                ->whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(function (Region $region) use ($examYearValue, $mode, $candidateFilter) {
                    $candidateRows = $this->regionalCandidateRows($region, $examYearValue, true, $mode);
                    if ($candidateFilter) {
                        $candidateRows = $candidateFilter($candidateRows);
                    }

                    [$rows] = $this->buildGroupedRows($candidateRows, $mode);

                    return [
                        'region_id' => $region->id,
                        'region_name' => strtoupper((string) $region->name),
                        'average' => $this->groupedAverage($rows),
                    ];
                })
                ->filter(fn ($row) => !is_null($row['average']))
                ->sort(function (array $left, array $right) {
                    $leftAvg = $left['average'] ?? -INF;
                    $rightAvg = $right['average'] ?? -INF;
                    if ($leftAvg !== $rightAvg) {
                        return $rightAvg <=> $leftAvg;
                    }

                    return strcmp((string) ($left['region_name'] ?? ''), (string) ($right['region_name'] ?? ''));
                })
                ->values();

            $rank = $rankedRegions->search(fn ($row) => (int) ($row['region_id'] ?? 0) === (int) $currentRegion->id);

            return [
                'position' => $rank === false ? null : $rank + 1,
                'total' => $rankedRegions->count(),
            ];
        });
    }

    private function groupRowTemplate(array $candidate, string $mode): array
    {
        $label = match ($mode) {
            'general' => strtoupper((string) ($candidate['gender'] ?? '')) === 'F' ? 'FEMALE' : 'MALE',
            'councilwise' => (string) ($candidate['council'] ?? '-'),
            'zonal-councilwise' => (string) ($candidate['council'] ?? '-'),
            'zonal-schoolwise' => (string) ($candidate['school'] ?? '-'),
            'districtwise' => (string) ($candidate['district'] ?? '-'),
            'ownership' => (string) ($candidate['ownership'] ?? 'UNKNOWN'),
            'regionalwise' => (string) ($candidate['region'] ?? '-'),
            default => (string) ($candidate['council'] ?? '-'),
        };

        return [
            'school_id' => isset($candidate['school_id']) ? (int) $candidate['school_id'] : null,
            'council' => $mode === 'general' ? $label : (string) ($candidate['council'] ?? '-'),
            'council_id' => isset($candidate['council_id']) ? (int) $candidate['council_id'] : null,
            'district' => (string) ($candidate['district'] ?? '-'),
            'ownership' => (string) ($candidate['ownership'] ?? 'UNKNOWN'),
            'school' => (string) ($candidate['school'] ?? '-'),
            'region' => (string) ($candidate['region'] ?? '-'),
            'region_id' => isset($candidate['region_id']) ? (int) $candidate['region_id'] : null,
            'sort_label' => ($mode === 'schoolwise' || $mode === 'zonal-schoolwise')
                ? (string) ($candidate['school'] ?? '-')
                : ($mode === 'ownership' ? (string) ($candidate['ownership'] ?? 'UNKNOWN') : $label),
            'registered' => ['m' => 0, 'f' => 0, 't' => 0],
            'absent' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
            'sat' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
            'inc' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
            'clean' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
            'grades' => [
                'a' => ['m' => 0, 'f' => 0, 't' => 0],
                'b' => ['m' => 0, 'f' => 0, 't' => 0],
                'c' => ['m' => 0, 'f' => 0, 't' => 0],
                'd' => ['m' => 0, 'f' => 0, 't' => 0],
                'e' => ['m' => 0, 'f' => 0, 't' => 0],
            ],
            'pass_ac' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
            'pass_ad' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
            'total_marks_sum' => 0.0,
            'avg_marks' => null,
            'avg_grade' => null,
            'school_ids' => [],
            'schools_count' => 0,
            'school_totals' => [],
        ];
    }

    private function groupTotalsTemplate(): array
    {
        return [
            'registered' => ['m' => 0, 'f' => 0, 't' => 0],
            'absent' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
            'sat' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
            'inc' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
            'clean' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
            'grades' => [
                'a' => ['m' => 0, 'f' => 0, 't' => 0],
                'b' => ['m' => 0, 'f' => 0, 't' => 0],
                'c' => ['m' => 0, 'f' => 0, 't' => 0],
                'd' => ['m' => 0, 'f' => 0, 't' => 0],
                'e' => ['m' => 0, 'f' => 0, 't' => 0],
            ],
            'pass_ac' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
            'pass_ad' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
        ];
    }

    private function subjectCatalog(): Collection
    {
        return collect([
            'KISWAHILI' => ['key' => 'KISWAHILI', 'code' => '01', 'full' => 'KISWAHILI'],
            'ENGLISH LANGUAGE' => ['key' => 'ENGLISH LANGUAGE', 'code' => '02', 'full' => 'ENGLISH LANGUAGE'],
            'SOCIAL STUDIES AND VOCATIONAL SKILLS' => ['key' => 'SOCIAL STUDIES AND VOCATIONAL SKILLS', 'code' => '03', 'full' => 'SOCIAL STUDIES AND VOCATIONAL SKILLS'],
            'MATHEMATICS' => ['key' => 'MATHEMATICS', 'code' => '04', 'full' => 'MATHEMATICS'],
            'SCIENCE AND TECHNOLOGY' => ['key' => 'SCIENCE AND TECHNOLOGY', 'code' => '05', 'full' => 'SCIENCE AND TECHNOLOGY'],
            'CIVIC AND MORAL EDUCATION' => ['key' => 'CIVIC AND MORAL EDUCATION', 'code' => '06', 'full' => 'CIVIC AND MORAL EDUCATION'],
        ]);
    }

    private function candidateSubjectLabel(string $subjectName): string
    {
        return match (strtoupper(trim($subjectName))) {
            'KISWAHILI' => 'KISWAHILI',
            'ENGLISH LANGUAGE' => 'ENGLISH',
            'SOCIAL STUDIES AND VOCATIONAL SKILLS' => 'SOCIAL',
            'MATHEMATICS' => 'MATHEMATICS',
            'SCIENCE AND TECHNOLOGY' => 'SCIENCE',
            'CIVIC AND MORAL EDUCATION' => 'CIVIC',
            default => strtoupper(trim($subjectName)),
        };
    }

    private function subjectOrderIndex(string $subjectName): int
    {
        $order = array_keys($this->subjectCatalog()->all());
        $index = array_search(strtoupper(trim($subjectName)), $order, true);
        return $index === false ? 99 : $index;
    }

    private function activeYear(): int
    {
        return (int) (ExamYear::query()->where('is_active', true)->value('year_label') ?? now()->year);
    }

    private function psleExamTypeId(): int
    {
        static $id = null;

        if ($id === null) {
            $id = (int) ExamType::query()->where('code', self::EXAM_TYPE_CODE)->value('id');
        }

        return $id;
    }

    private function scaledScore50(float $marksObtained, float $maxMarks): float
    {
        if ($maxMarks <= 0) {
            return 0.0;
        }

        return round(($marksObtained / $maxMarks) * 50, 4);
    }

    private function gradeFromScaledScore(float $score): string
    {
        if ($score >= 241 / 6) {
            return 'A';
        }

        if ($score >= 181 / 6) {
            return 'B';
        }

        if ($score >= 121 / 6) {
            return 'C';
        }

        if ($score >= 61 / 6) {
            return 'D';
        }

        return 'E';
    }

    private function gradePointFromGrade(string $grade): int
    {
        return match (strtoupper($grade)) {
            'A' => 1,
            'B' => 2,
            'C' => 3,
            'D' => 4,
            default => 5,
        };
    }

    private function gradeMeta(string $grade): array
    {
        $competenceLabels = $this->psleCompetenceLabels();
        $meta = [
            'A' => ['grade' => 'A', 'competence' => $competenceLabels['A'], 'color' => '#00A82A', 'label' => "Grade A ({$competenceLabels['A']})"],
            'B' => ['grade' => 'B', 'competence' => $competenceLabels['B'], 'color' => '#1FEE0B', 'label' => "Grade B ({$competenceLabels['B']})"],
            'C' => ['grade' => 'C', 'competence' => $competenceLabels['C'], 'color' => '#DEF043', 'label' => "Grade C ({$competenceLabels['C']})"],
            'D' => ['grade' => 'D', 'competence' => $competenceLabels['D'], 'color' => '#FF772F', 'label' => "Grade D ({$competenceLabels['D']})"],
            'E' => ['grade' => 'E', 'competence' => $competenceLabels['E'], 'color' => '#FF272F', 'label' => "Grade E ({$competenceLabels['E']})"],
        ];

        return $meta[strtoupper($grade)] ?? $meta['E'];
    }

    private function gpaCompetence(float $gpa): ?array
    {
        if ($gpa <= 0) {
            return null;
        }

        foreach (['A', 'B', 'C', 'D', 'E'] as $grade) {
            if ((float) $this->gradePointFromGrade($grade) === round($gpa, 0)) {
                return $this->gradeMeta($grade);
            }
        }

        return $this->gradeMeta('E');
    }

    private function averageMarksGrade(float $avgScore): string
    {
        if ($avgScore >= 40.1667) return 'A';
        if ($avgScore >= 30.1667) return 'B';
        if ($avgScore >= 20.1667) return 'C';
        if ($avgScore >= 10.1667) return 'D';
        return 'E';
    }

    private function averageMarksCompetence(float $avgScore): ?array
    {
        if ($avgScore <= 0) {
            return null;
        }
        $grade = $this->averageMarksGrade($avgScore);
        return $this->gradeMeta($grade);
    }

    private function psleCompetenceLabels(): array
    {
        static $labels = null;

        if ($labels !== null) {
            return $labels;
        }

        $labels = [
            'A' => 'Excellent',
            'B' => 'Very Good',
            'C' => 'Good',
            'D' => 'Satisfactory',
            'E' => 'Unsatisfactory',
        ];

        $profile = GradingProfile::query()
            ->where('code', 'like', 'PSLE-%')
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->first();

        $rules = collect(data_get($profile?->competence_levels, 'rules', []));
        if ($rules->isNotEmpty()) {
            foreach (['A', 'B', 'C', 'D', 'E'] as $grade) {
                $point = (float) $this->gradePointFromGrade($grade);
                $match = $rules->first(function (array $rule) use ($point) {
                    return (float) ($rule['min_value'] ?? -INF) <= $point
                        && (float) ($rule['max_value'] ?? INF) >= $point
                        && !($rule['is_disabled'] ?? false);
                });

                if (!empty($match['level_label'])) {
                    $labels[$grade] = (string) $match['level_label'];
                }
            }
        }

        return $labels;
    }

    public function zonalEvaluationEntries(): Collection
    {
        return collect([
            ['key' => 'general', 'label' => 'ZONAL GENERAL EVALUATION'],
            ['key' => 'regionalwise', 'label' => 'ZONAL REGIONALWISE EVALUATION'],
            ['key' => 'councilwise', 'label' => 'ZONAL COUNCILWISE EVALUATION'],
            ['key' => 'schoolwise', 'label' => 'ZONAL SCHOOLWISE EVALUATION'],
            ['key' => 'best-ten-councils', 'label' => 'ZONAL BEST TEN (10) COUNCILS'],
            ['key' => 'least-ten-councils', 'label' => 'ZONAL LEAST TEN (10) COUNCILS'],
            ['key' => 'best-ten-schools', 'label' => 'ZONAL BEST TEN (10) SCHOOLS'],
            ['key' => 'least-ten-schools', 'label' => 'ZONAL LEAST TEN (10) SCHOOLS'],
            ['key' => 'ownership-result-evaluation', 'label' => 'ZONAL OWNERSHIP RESULT EVALUATION'],
            ['key' => 'subjectwise-result-evaluation', 'label' => 'ZONAL SUBJECTWISE RESULT EVALUATION'],
        ]);
    }

    public function regionalEvaluationEntries(): Collection
    {
        return collect([
            ['key' => 'general', 'label' => 'GENERAL EVALUATION'],
            ['key' => 'councilwise', 'label' => 'COUNCILWISE EVALUATION'],
            ['key' => 'schoolwise', 'label' => 'SCHOOLWISE EVALUATION'],
            ['key' => 'districtwise', 'label' => 'DISTRICTWISE EVALUATION'],
            ['key' => 'best-ten-councils', 'label' => 'BEST TEN (10) COUNCILS'],
            ['key' => 'least-ten-councils', 'label' => 'LEAST TEN (10) COUNCILS'],
            ['key' => 'best-ten-schools', 'label' => 'BEST TEN (10) SCHOOLS'],
            ['key' => 'least-ten-schools', 'label' => 'LEAST TEN (10) SCHOOLS'],
            ['key' => 'best-ten-girls', 'label' => 'BEST TEN (10) GIRLS'],
            ['key' => 'least-ten-girls', 'label' => 'LEAST TEN (10) GIRLS'],
            ['key' => 'best-ten-boys', 'label' => 'BEST TEN (10) BOYS'],
            ['key' => 'least-ten-boys', 'label' => 'LEAST TEN (10) BOYS'],
            ['key' => 'overall-best-ten-students', 'label' => 'OVERALL TEN (10) BEST STUDENTS'],
            ['key' => 'overall-least-ten-students', 'label' => 'OVERALL TEN (10) LEAST STUDENTS'],
            ['key' => 'government-schools', 'label' => 'GOVERNMENT SCHOOLS'],
            ['key' => 'non-government-schools', 'label' => 'NON-GOVERNMENT SCHOOLS'],
            ['key' => 'ownership-result-evaluation', 'label' => 'OWNERSHIP RESULT EVALUATION'],
            ['key' => 'subjectwise-result-evaluation', 'label' => 'SUBJECTWISE RESULT EVALUATION'],
            ['key' => 'mark-entry-status-report', 'label' => 'MARK ENTRY STATUS REPORT'],
            ['key' => 'subject-summary-evaluation', 'label' => 'SUBJECT SUMMARY EVALUATION'],
        ]);
    }

    public function buildZonalOwnershipGroupedRows(int $examYearValue): array
    {
        $regions = Region::query()
            ->whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])
            ->get();

        $allCandidates = collect();

        foreach ($regions as $region) {
            $candidateRows = $this->regionalCandidateRows($region, $examYearValue, true, 'ownership');
            if ($candidateRows->isNotEmpty()) {
                $allCandidates = $allCandidates->merge($candidateRows);
            }
            unset($candidateRows);
            gc_collect_cycles();
        }

        [$rows, $total] = $this->buildGroupedRows($allCandidates, 'ownership');

        return [$rows, $total];
    }

    public function buildSubjectwiseRowsFromDb(array $regionIds, int $examYearValue): Collection
    {
        $publication = DB::table('psle_result_publications as prp')
            ->join('result_snapshots as rs', 'rs.id', '=', 'prp.snapshot_id')
            ->where('prp.exam_year_id', function ($query) use ($examYearValue) {
                $query->select('id')->from('exam_years')->where('year_label', $examYearValue)->limit(1);
            })
            ->where('prp.status', 'published')
            ->where('rs.is_active', true)
            ->where('rs.is_rolled_back', false)
            ->select('rs.id as snapshot_id')
            ->first();

        $snapshotId = $publication ? $publication->snapshot_id : 0;
        $examTypeId = $this->psleExamTypeId();

        $registered = DB::table('candidate_exam_registrations as cer')
            ->join('candidates as c', 'c.id', '=', 'cer.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->whereIn('s.region_id', $regionIds)
            ->where('cer.exam_type_id', $examTypeId)
            ->where('cer.year', $examYearValue)
            ->count();

        $stats = DB::table('subject_marks as sm')
            ->join('candidate_exam_registrations as cer', 'cer.candidate_id', '=', 'sm.candidate_id')
            ->join('candidates as c', 'c.id', '=', 'cer.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->join('subjects as sb', 'sb.id', '=', 'sm.subject_id')
            ->whereIn('s.region_id', $regionIds)
            ->where('cer.exam_type_id', $examTypeId)
            ->where('cer.year', $examYearValue)
            ->where('sm.exam_type_id', $examTypeId)
            ->where('sm.year', $examYearValue)
            ->where('sm.snapshot_id', $snapshotId)
            ->select([
                'sb.name as subject_name',
                'sb.code as subject_code',
                DB::raw("COUNT(CASE WHEN sm.grade = 'ABS' OR sm.marks_obtained IS NULL THEN 1 END) as abs_count"),
                DB::raw("COUNT(CASE WHEN sm.grade != 'ABS' AND sm.marks_obtained IS NOT NULL THEN 1 END) as sat_count"),
                DB::raw("SUM(CASE WHEN sm.grade != 'ABS' AND sm.marks_obtained IS NOT NULL THEN ROUND((sm.marks_obtained / COALESCE(sm.max_marks, 100)) * 50, 4) ELSE 0 END) as total_score"),
                DB::raw("COUNT(CASE WHEN sm.grade != 'ABS' AND sm.marks_obtained IS NOT NULL AND ROUND((sm.marks_obtained / COALESCE(sm.max_marks, 100)) * 50, 4) >= 40.1667 THEN 1 END) as grade_a"),
                DB::raw("COUNT(CASE WHEN sm.grade != 'ABS' AND sm.marks_obtained IS NOT NULL AND ROUND((sm.marks_obtained / COALESCE(sm.max_marks, 100)) * 50, 4) >= 30.1667 AND ROUND((sm.marks_obtained / COALESCE(sm.max_marks, 100)) * 50, 4) < 40.1667 THEN 1 END) as grade_b"),
                DB::raw("COUNT(CASE WHEN sm.grade != 'ABS' AND sm.marks_obtained IS NOT NULL AND ROUND((sm.marks_obtained / COALESCE(sm.max_marks, 100)) * 50, 4) >= 20.1667 AND ROUND((sm.marks_obtained / COALESCE(sm.max_marks, 100)) * 50, 4) < 30.1667 THEN 1 END) as grade_c"),
                DB::raw("COUNT(CASE WHEN sm.grade != 'ABS' AND sm.marks_obtained IS NOT NULL AND ROUND((sm.marks_obtained / COALESCE(sm.max_marks, 100)) * 50, 4) >= 10.1667 AND ROUND((sm.marks_obtained / COALESCE(sm.max_marks, 100)) * 50, 4) < 20.1667 THEN 1 END) as grade_d"),
                DB::raw("COUNT(CASE WHEN sm.grade != 'ABS' AND sm.marks_obtained IS NOT NULL AND ROUND((sm.marks_obtained / COALESCE(sm.max_marks, 100)) * 50, 4) < 10.1667 THEN 1 END) as grade_e"),
            ])
            ->groupBy('sb.id', 'sb.name', 'sb.code')
            ->get();

        $subjectBuckets = [];
        foreach ($this->subjectCatalog() as $subjectName => $subjectMeta) {
            $stat = $stats->first(fn ($s) => strtoupper((string) $s->subject_name) === strtoupper($subjectName));
            
            $sat = $stat ? (int) $stat->sat_count : 0;
            $abs = $registered - $sat;
            $gradeA = $stat ? (int) $stat->grade_a : 0;
            $gradeB = $stat ? (int) $stat->grade_b : 0;
            $gradeC = $stat ? (int) $stat->grade_c : 0;
            $gradeD = $stat ? (int) $stat->grade_d : 0;
            $gradeE = $stat ? (int) $stat->grade_e : 0;
            $totalScore = $stat ? (float) $stat->total_score : 0.0;
            
            $graded = $gradeA + $gradeB + $gradeC + $gradeD + $gradeE;
            $average = $graded > 0 ? round($totalScore / $graded, 4) : 0.0;
            $grade = $this->gradeFromScaledScore($average);
            $gpa = $graded > 0
                ? round((($gradeA * 1) + ($gradeB * 2) + ($gradeC * 3) + ($gradeD * 4) + ($gradeE * 5)) / $graded, 4)
                : null;
                
            $subjectBuckets[] = [
                'code' => $subjectMeta['code'],
                'name' => $subjectMeta['full'],
                'registered' => $registered,
                'sat' => $sat,
                'abs' => $abs,
                'grade_a' => $gradeA,
                'grade_b' => $gradeB,
                'grade_c' => $gradeC,
                'grade_d' => $gradeD,
                'grade_e' => $gradeE,
                'a_to_c' => $gradeA + $gradeB + $gradeC,
                'a_to_d' => $gradeA + $gradeB + $gradeC + $gradeD,
                'avg_marks' => $average,
                'grade' => $grade,
                'gpa' => $gpa,
                'competence' => $this->gradeMeta($grade),
            ];
        }

        return collect($subjectBuckets);
    }

    public function buildSubjectwiseSummaryFromDb(array $regionIds, int $examYearValue, string $regionLabel): array
    {
        $publication = DB::table('psle_result_publications as prp')
            ->join('result_snapshots as rs', 'rs.id', '=', 'prp.snapshot_id')
            ->where('prp.exam_year_id', function ($query) use ($examYearValue) {
                $query->select('id')->from('exam_years')->where('year_label', $examYearValue)->limit(1);
            })
            ->where('prp.status', 'published')
            ->where('rs.is_active', true)
            ->where('rs.is_rolled_back', false)
            ->select('rs.id as snapshot_id')
            ->first();

        $snapshotId = $publication ? $publication->snapshot_id : 0;
        $examTypeId = $this->psleExamTypeId();

        $candidateStats = DB::table('candidate_exam_registrations as cer')
            ->join('candidates as c', 'c.id', '=', 'cer.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->leftJoin('subject_marks as sm', function ($join) use ($examTypeId, $examYearValue, $snapshotId) {
                $join->on('sm.candidate_id', '=', 'c.id')
                    ->where('sm.exam_type_id', '=', $examTypeId)
                    ->where('sm.year', '=', $examYearValue)
                    ->where('sm.snapshot_id', '=', $snapshotId);
            })
            ->whereIn('s.region_id', $regionIds)
            ->where('cer.exam_type_id', $examTypeId)
            ->where('cer.year', $examYearValue)
            ->select([
                'c.gender',
                DB::raw("COUNT(CASE WHEN sm.grade != 'ABS' AND sm.marks_obtained IS NOT NULL THEN 1 END) as subject_count"),
                DB::raw("AVG(CASE WHEN sm.grade != 'ABS' AND sm.marks_obtained IS NOT NULL THEN ROUND((sm.marks_obtained / COALESCE(sm.max_marks, 100)) * 50, 4) END) as avg_score"),
                DB::raw("SUM(CASE WHEN sm.grade != 'ABS' AND sm.marks_obtained IS NOT NULL THEN 
                    CASE 
                        WHEN ROUND((sm.marks_obtained / COALESCE(sm.max_marks, 100)) * 50, 4) >= 40.1667 THEN 1
                        WHEN ROUND((sm.marks_obtained / COALESCE(sm.max_marks, 100)) * 50, 4) >= 30.1667 THEN 2
                        WHEN ROUND((sm.marks_obtained / COALESCE(sm.max_marks, 100)) * 50, 4) >= 20.1667 THEN 3
                        WHEN ROUND((sm.marks_obtained / COALESCE(sm.max_marks, 100)) * 50, 4) >= 10.1667 THEN 4
                        ELSE 5
                    END
                END) as aggt_sum")
            ])
            ->groupBy('c.id', 'c.gender')
            ->get();

        $summary = [
            'F' => ['REGIST' => 0, 'SAT' => 0, 'WITHHELD' => 0, 'CLEAN' => 0, 'A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'INC' => 0, 'ABS' => 0],
            'M' => ['REGIST' => 0, 'SAT' => 0, 'WITHHELD' => 0, 'CLEAN' => 0, 'A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'INC' => 0, 'ABS' => 0],
        ];

        $avgScoreSum = 0.0;
        $avgScoreCount = 0;

        foreach ($candidateStats as $row) {
            $sex = strtoupper((string) ($row->gender ?? ''));
            if ($sex !== 'F' && $sex !== 'M') {
                continue;
            }

            $summary[$sex]['REGIST']++;
            $subjectCount = (int) $row->subject_count;

            if ($subjectCount === 0) {
                $summary[$sex]['ABS']++;
                continue;
            }

            $summary[$sex]['SAT']++;
            if ($subjectCount < self::EXPECTED_SUBJECTS) {
                $summary[$sex]['INC']++;
                continue;
            }

            $summary[$sex]['CLEAN']++;
            $avgScore = $row->avg_score !== null ? (float) $row->avg_score : null;

            if ($avgScore !== null) {
                $grade = 'E';
                if ($avgScore >= 40.1667) {
                    $grade = 'A';
                } elseif ($avgScore >= 30.1667) {
                    $grade = 'B';
                } elseif ($avgScore >= 20.1667) {
                    $grade = 'C';
                } elseif ($avgScore >= 10.1667) {
                    $grade = 'D';
                }

                $summary[$sex][$grade]++;

                $avgScoreSum += $avgScore;
                $avgScoreCount++;
            }
        }

        $summary['T'] = ['REGIST' => 0, 'SAT' => 0, 'WITHHELD' => 0, 'CLEAN' => 0, 'A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'INC' => 0, 'ABS' => 0];
        foreach (array_keys($summary['T']) as $key) {
            $summary['T'][$key] = $summary['F'][$key] + $summary['M'][$key];
        }

        $overallAverage = $avgScoreCount > 0 ? round($avgScoreSum / $avgScoreCount, 4) : 0.0;

        return [
            'subjects' => number_format($this->subjectCatalog()->count()),
            'grade_summary' => $summary,
            'overall' => [
                'region' => strtoupper($regionLabel),
                'passed' => $summary['T']['A'] + $summary['T']['B'] + $summary['T']['C'],
                'average_score' => $overallAverage,
                'average_info' => $overallAverage > 0 ? $this->averageMarksCompetence($overallAverage) : null,
            ],
        ];
    }

    public function buildStudentRankingRowsOptimized(Region $region, int $examYearValue, string $evaluation): Collection
    {
        $publication = DB::table('psle_result_publications as prp')
            ->join('result_snapshots as rs', 'rs.id', '=', 'prp.snapshot_id')
            ->where('prp.exam_year_id', function ($query) use ($examYearValue) {
                $query->select('id')->from('exam_years')->where('year_label', $examYearValue)->limit(1);
            })
            ->where('prp.status', 'published')
            ->where('rs.is_active', true)
            ->where('rs.is_rolled_back', false)
            ->select('rs.id as snapshot_id')
            ->first();

        $snapshotId = $publication ? $publication->snapshot_id : 0;
        $examTypeId = $this->psleExamTypeId();

        $query = DB::table('candidate_exam_registrations as cer')
            ->join('candidates as c', 'c.id', '=', 'cer.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->leftJoin('regions as r', 'r.id', '=', 's.region_id')
            ->leftJoin('districts as d', 'd.id', '=', 's.district_id')
            ->leftJoin('district_councils as dc', 'dc.id', '=', 's.council_id')
            ->leftJoin('subject_marks as sm', function ($join) use ($examTypeId, $examYearValue, $snapshotId) {
                $join->on('sm.candidate_id', '=', 'c.id')
                    ->where('sm.exam_type_id', '=', $examTypeId)
                    ->where('sm.year', '=', $examYearValue)
                    ->where('sm.snapshot_id', '=', $snapshotId);
            })
            ->where('s.region_id', $region->id)
            ->where('cer.exam_type_id', $examTypeId)
            ->where('cer.year', $examYearValue)
            ->select([
                'c.id as candidate_pk',
                'c.candidate_id as index_number',
                'c.prem_no',
                'c.full_name',
                'c.gender',
                's.id as school_id',
                's.code as school_code',
                's.name as school_name',
                'r.name as region_name',
                'dc.name as council_name',
                'dc.id as council_id',
                'd.code as district_code',
                'd.name as district_name',
                's.ownership',
                DB::raw("COUNT(CASE WHEN sm.grade != 'ABS' AND sm.marks_obtained IS NOT NULL THEN 1 END) as subject_count"),
                DB::raw("SUM(CASE WHEN sm.grade != 'ABS' AND sm.marks_obtained IS NOT NULL THEN ROUND((sm.marks_obtained / COALESCE(sm.max_marks, 100)) * 50, 4) END) as total_marks"),
                DB::raw("AVG(CASE WHEN sm.grade != 'ABS' AND sm.marks_obtained IS NOT NULL THEN ROUND((sm.marks_obtained / COALESCE(sm.max_marks, 100)) * 50, 4) END) as avg_marks")
            ])
            ->groupBy(
                'c.id', 'c.candidate_id', 'c.prem_no', 'c.full_name', 'c.gender', 
                's.id', 's.code', 's.name', 'r.name', 'dc.name', 'dc.id', 'd.code', 'd.name', 's.ownership'
            )
            ->having('subject_count', '=', self::EXPECTED_SUBJECTS);

         if (in_array($evaluation, ['best-ten-girls', 'least-ten-girls'], true)) {
             $query->where('c.gender', 'F');
         }
         if (in_array($evaluation, ['best-ten-boys', 'least-ten-boys'], true)) {
             $query->where('c.gender', 'M');
         }

         $isBest = in_array($evaluation, ['best-ten-girls', 'best-ten-boys', 'overall-best-ten-students'], true);
         if ($isBest) {
             $query->orderBy('avg_marks', 'desc')
                   ->orderBy('total_marks', 'desc')
                   ->orderBy('index_number', 'asc');
         } else {
             $query->orderBy('avg_marks', 'asc')
                   ->orderBy('total_marks', 'asc')
                   ->orderBy('index_number', 'asc');
         }

         $candidates = $query->limit(10)->get();

        $candidateIds = $candidates->pluck('candidate_pk')->all();

        $marks = DB::table('subject_marks as sm')
            ->join('subjects as sb', 'sb.id', '=', 'sm.subject_id')
            ->whereIn('sm.candidate_id', $candidateIds)
            ->where('sm.exam_type_id', $examTypeId)
            ->where('sm.year', $examYearValue)
            ->where('sm.snapshot_id', $snapshotId)
            ->select([
                'sm.candidate_id',
                'sm.marks_obtained',
                'sm.max_marks',
                'sm.grade',
                'sb.code as subject_code',
                'sb.name as subject_name',
            ])
            ->get()
            ->groupBy('candidate_id');

        $rows = $candidates->map(function ($candidate, $index) use ($marks, $region) {
            $subjectRows = collect($marks->get($candidate->candidate_pk, []))
                ->map(function ($row) {
                    $isAbsent = strtoupper((string) ($row->grade ?? '')) === 'ABS' || is_null($row->marks_obtained);
                    $score = $isAbsent ? null : $this->scaledScore50((float) ($row->marks_obtained ?? 0), (float) ($row->max_marks ?: 100));
                    $grade = $isAbsent ? 'ABS' : $this->gradeFromScaledScore($score);

                    return [
                        'code' => strtoupper((string) ($row->subject_code ?? '')),
                        'subject_name' => strtoupper(trim((string) ($row->subject_name ?? ''))),
                        'subject_short' => $this->candidateSubjectLabel((string) ($row->subject_name ?? '')),
                        'score' => $score,
                        'grade' => $grade,
                        'is_absent' => $isAbsent,
                    ];
                })
                ->sortBy(fn (array $item) => $this->subjectOrderIndex((string) ($item['subject_name'] ?? '')))
                ->values();

            $average = (float) $candidate->avg_marks;

            return [
                'position' => $index + 1,
                'candidate_pk' => (int) $candidate->candidate_pk,
                'index_number' => (string) ($candidate->index_number ?? '-'),
                'prem_no' => (string) ($candidate->prem_no ?? '-'),
                'candidate' => strtoupper(trim((string) ($candidate->full_name ?? $candidate->index_number))) ?: '-',
                'gender' => strtoupper(trim((string) ($candidate->gender ?? ''))),
                'school_id' => (int) $candidate->school_id,
                'school' => trim(((string) ($candidate->school_code ?? '')) . ' - ' . ((string) ($candidate->school_name ?? ''))),
                'school_name' => strtoupper(trim((string) ($candidate->school_name ?? ''))),
                'region' => strtoupper(trim((string) ($candidate->region_name ?? '-'))),
                'region_id' => $region->id,
                'council' => strtoupper(trim((string) ($candidate->council_name ?? '-'))) ?: '-',
                'council_id' => isset($candidate->council_id) ? (int) $candidate->council_id : null,
                'district' => strtoupper(trim(((string) ($candidate->district_code ?? '')) . ' - ' . ((string) ($candidate->district_name ?? '')))) ?: '-',
                'ownership' => strtoupper(trim((string) ($candidate->ownership ?? 'UNKNOWN'))) ?: 'UNKNOWN',
                'status' => 'COMPLETE',
                'subject_rows' => $subjectRows->all(),
                'subject_results_text' => $subjectRows->map(function (array $item) {
                    if ($item['is_absent'] ?? false) {
                        return "{$item['subject_short']} - ABS";
                    }
                    $score = number_format((float) $item['score'], 0);
                    return "{$item['subject_short']} - {$score} '{$item['grade']}'";
                })->implode(', '),
                'total_marks' => round((float) $candidate->total_marks, 0),
                'avg_marks' => $average,
                'overall_grade' => $this->gradeFromScaledScore($average),
            ];
        });

        return $rows;
    }

    public function buildZonalSubjectwiseRows(int $examYearValue): Collection
    {
        $regionIds = Region::query()
            ->whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])
            ->pluck('id')
            ->all();

        return $this->buildSubjectwiseRowsFromDb($regionIds, $examYearValue);
    }

    public function buildZonalSubjectwiseSummary(int $examYearValue): array
    {
        $regionIds = Region::query()
            ->whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])
            ->pluck('id')
            ->all();

        return $this->buildSubjectwiseSummaryFromDb($regionIds, $examYearValue, 'TASIDO ZONE');
    }

    private function baseMeta(array $overrides = []): array
    {
        $base = [
            'title' => 'Zonal IRMS Portal',
            'description' => 'Examination Results',
            'keywords' => 'results, mock, NECTA',
            'author' => 'Examination Board',
            'header_top' => "PRIME MINISTER'S OFFICE",
            'header_subtitle' => 'REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT',
            'header_places' => 'ACADEMIC ZONE: TABORA, SINGIDA, IRINGA AND DODOMA (TASIDO)',
            'announcement' => 'Results have been officially published. Please use the search facility below to locate your school or examination centre.',
        ];

        return array_merge($base, $overrides);
    }

    public function rebuildCache(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403, 'Unauthorized');

        $request->validate([
            'scope_type' => 'required|string|in:zonal,regional',
            'scope_id' => 'nullable|integer',
            'evaluation_key' => 'required|string',
        ]);

        $examYearValue = $this->activeYear();
        $scopeType = $request->input('scope_type');
        $scopeId = $request->input('scope_id') ? (int) $request->input('scope_id') : null;
        $evaluationKey = $request->input('evaluation_key');

        \App\Jobs\PrecalculatePsleEvaluationsJob::dispatch($examYearValue, $scopeType, $scopeId, $evaluationKey, true);

        return response()->json(['message' => 'Cache rebuild started successfully.']);
    }

    public function getEvaluationData(string $scopeType, ?int $scopeId, string $evaluationKey, int $examYear): ?array
    {
        $precalcService = app(\App\Services\Results\PslePrecalculationService::class);
        $payload = $precalcService->getReadyPayloadOrNull($examYear, $scopeType, $scopeId, $evaluationKey);

        if (!$payload && app()->runningUnitTests() && !self::$bypassTestSafeguard) {
            $precalcService->precalculate($examYear, $scopeType, $scopeId, $evaluationKey, true);
            $payload = $precalcService->getReadyPayloadOrNull($examYear, $scopeType, $scopeId, $evaluationKey);
        }

        return $payload;
    }

    public function renderNotReadyPage(string $scopeType, ?int $scopeId, string $evaluationKey, int $examYear)
    {
        $precalcService = app(\App\Services\Results\PslePrecalculationService::class);
        $status = $precalcService->getEvaluationStatus($examYear, $scopeType, $scopeId, $evaluationKey);

        if ($status === \App\Models\PslePrecalculatedEvaluation::STATUS_PENDING) {
            \App\Jobs\PrecalculatePsleEvaluationsJob::dispatch($examYear, $scopeType, $scopeId, $evaluationKey, false);
        }

        $region = null;
        if ($scopeType === 'regional' && $scopeId) {
            $region = \App\Models\Region::find($scopeId);
        }

        return response()->view('evaluations.not-ready', [
            'scopeType' => $scopeType,
            'scopeId' => $scopeId,
            'evaluation' => $evaluationKey,
            'examYearValue' => $examYear,
            'status' => $status,
            'regionName' => $region ? $region->name : '',
            'isAdmin' => auth()->check() && auth()->user()->is_admin,
        ]);
    }
}
