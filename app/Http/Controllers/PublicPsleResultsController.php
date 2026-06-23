<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\ExamType;
use App\Models\GradingProfile;
use App\Models\Region;
use App\Models\School;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\Schools\NectaPsle2025SchoolSyncService;

class PublicPsleResultsController extends Controller
{
    private const EXAM_TYPE_CODE = 'PSLE';

    private const SUBJECT_ORDER = [
        'KISWAHILI',
        'ENGLISH LANGUAGE',
        'SOCIAL STUDIES AND VOCATIONAL SKILLS',
        'MATHEMATICS',
        'SCIENCE AND TECHNOLOGY',
        'CIVIC AND MORAL EDUCATION',
    ];

    public function regions(string $examYear)
    {
        $examYear = (int) $examYear;

        if (!(auth()->check() && auth()->user()->is_admin)) {
            $this->checkPublicationStatus($examYear);
        }

        if (auth()->check() && auth()->user()->is_admin) {
            return app(\App\Http\Controllers\Admin\AdminPsleResultsController::class)->index(request(), $examYear);
        }

        $regions = $this->pslePortalBaseQuery()
            ->join('regions as r', 'r.id', '=', 's.region_id')
            ->leftJoin('candidates as c', function ($join) {
                $join->on('c.school_id', '=', 's.id')
                    ->where('c.exam_type', self::EXAM_TYPE_CODE);
            })
            ->selectRaw('r.id, r.code, r.name, count(distinct s.id) as schools_count, count(distinct c.id) as candidates_count')
            ->groupBy('r.id', 'r.code', 'r.name')
            ->orderBy('r.name')
            ->get();

        $entries = $regions->values()->map(fn ($region) => [
            'label' => $region->name,
            'url' => route('public.results.psle.districts', ['examYear' => $examYear, 'region' => $region->id]),
        ]);

        $meta = $this->portalMeta([
            'eyebrow' => 'PSLE Public Results Workspace',
            'header_title' => 'STANDARD SEVEN MOCK RESULTS - ' . $examYear,
            'hero_badge' => 'Regional Reporting Centre',
            'hero_title' => 'Browse PSLE public results by region.',
            'hero_copy' => 'Start from the regional level, then continue to districts, schools, and final school result pages using the same portal implementation pattern as ACSEE.',
            'stats_label' => 'Regions',
            'stats_copy' => 'Each region entry opens the next PSLE public-results level from the shared portal workspace.',
            'support_label' => 'Navigation',
            'support_value' => 'Region First',
            'support_copy' => 'Choose a region, then continue through the full PSLE hierarchy without leaving the portal.',
            'stats_title_two' => 'Columns',
            'stats_title_three' => 'Experience',
            'stats_title_four' => 'Flow',
            'stats_value_three' => 'Premium',
            'stats_value_four' => 'Structured',
            'stats_card_one' => 'Total regional entries currently available for public PSLE browsing.',
            'stats_card_two' => 'Balanced portal cards keep the first hierarchy level easy to scan.',
            'stats_card_three' => 'This page now uses the same shared portal implementation path as ACSEE.',
            'stats_card_four' => 'Move from regions to the next hierarchy level in one clean sequence.',
            'toolbar_title' => 'Available regional entries',
            'toolbar_copy' => 'Search by region name or use alphabet shortcuts to open the correct PSLE public-results path.',
            'entry_copy' => 'Open this region to view its available districts.',
            'search_placeholder' => 'Search Region from the list',
            'alpha_label' => 'CLICK ANY LETTER BELOW TO FILTER REGIONS BY ALPHABET',
            'alpha_all_label' => 'ALL REGIONS',
            'columns' => $this->portalColumns($entries->count(), 4),
        ]);

        return view('public.results-portal.index', [
            'link' => null,
            'entries' => $entries,
            'meta' => $meta,
            'token' => 'public-results-psle-regions-' . $examYear,
        ]);
    }

    public function districts(string $examYear, Region $region)
    {
        $examYear = (int) $examYear;

        if (!(auth()->check() && auth()->user()->is_admin)) {
            $this->checkPublicationStatus($examYear);
        }

        $districts = $this->pslePortalBaseQuery()
            ->where('s.region_id', $region->id)
            ->join('districts as d', 'd.id', '=', 's.district_id')
            ->leftJoin('candidates as c', function ($join) {
                $join->on('c.school_id', '=', 's.id')
                    ->where('c.exam_type', self::EXAM_TYPE_CODE);
            })
            ->selectRaw('d.id, d.code, d.name, count(distinct s.id) as schools_count, count(distinct c.id) as candidates_count')
            ->groupBy('d.id', 'd.code', 'd.name')
            ->orderBy('d.name')
            ->get();

        abort_if($districts->isEmpty(), 404, 'No PSLE public results found for the selected region.');

        $entries = $districts->values()->map(fn ($district) => [
            'label' => $district->name,
            'url' => route('public.results.psle.schools', [
                'examYear' => $examYear,
                'region' => $region->id,
                'district' => $district->id,
            ]),
        ]);

        $meta = $this->portalMeta([
            'eyebrow' => strtoupper($region->name) . ' Regional Workspace',
            'header_title' => 'STANDARD SEVEN MOCK RESULTS - ' . $examYear . ' - ' . strtoupper($region->name),
            'hero_badge' => strtoupper($region->name) . ' District Centre',
            'hero_title' => 'Open available districts for ' . strtoupper($region->name) . '.',
            'hero_copy' => 'Continue from the selected region into its available PSLE public-results districts using the same shared portal implementation path.',
            'stats_label' => 'Districts',
            'stats_copy' => 'All district entries for the selected region are presented through the shared portal workspace.',
            'support_label' => 'Region',
            'support_value' => strtoupper($region->name),
            'support_copy' => 'Search within this regional district list and open the next level directly.',
            'stats_title_two' => 'Columns',
            'stats_title_three' => 'Mode',
            'stats_title_four' => 'Navigation',
            'stats_value_three' => 'Detailed',
            'stats_value_four' => 'Direct',
            'stats_card_one' => 'The full district list currently available for the selected region.',
            'stats_card_two' => 'Card layout matches the same public portal browsing experience.',
            'stats_card_three' => 'Shared portal implementation keeps PSLE aligned with ACSEE public results.',
            'stats_card_four' => 'Open the required district without leaving the portal frame.',
            'toolbar_title' => strtoupper($region->name) . ' district entries',
            'toolbar_copy' => 'Search the district list or filter alphabetically to open the correct district for this region.',
            'entry_copy' => 'Open this district to view its available schools.',
            'search_placeholder' => 'Search District from the list',
            'alpha_label' => 'CLICK ANY LETTER BELOW TO FILTER DISTRICTS BY ALPHABET',
            'alpha_all_label' => 'ALL DISTRICTS',
            'columns' => $this->portalColumns($entries->count(), 4),
            'back_url' => route('public.results.psle.regions', ['examYear' => $examYear]),
            'back_label' => 'Back to Regions',
            'primary_action_url' => route('public.results.psle.regions', ['examYear' => $examYear]),
            'primary_action_label' => 'All Regions',
        ]);

        return view('public.results-portal.index', [
            'link' => null,
            'entries' => $entries,
            'meta' => $meta,
            'token' => 'public-results-psle-districts-' . $region->id,
        ]);
    }

    public function schools(string $examYear, Region $region, District $district)
    {
        $examYear = (int) $examYear;
        $this->assertDistrictBelongsToRegion($district, $region);

        if (!(auth()->check() && auth()->user()->is_admin)) {
            $this->checkPublicationStatus($examYear);
        }

        $schools = $this->pslePortalBaseQuery()
            ->where('s.region_id', $region->id)
            ->where('s.district_id', $district->id)
            ->leftJoin('candidates as c', function ($join) {
                $join->on('c.school_id', '=', 's.id')
                    ->where('c.exam_type', self::EXAM_TYPE_CODE);
            })
            ->selectRaw('s.id, s.code, s.name, count(distinct c.id) as candidates_count')
            ->groupBy('s.id', 's.code', 's.name')
            ->orderBy('s.name')
            ->get();

        abort_if($schools->isEmpty(), 404, 'No PSLE public results found for the selected district.');

        $entries = $schools->values()->map(fn ($school) => [
            'label' => trim(($school->code ? $school->code . ' - ' : '') . $school->name),
            'url' => route('public.results.psle.school', [
                'examYear' => $examYear,
                'region' => $region->id,
                'district' => $district->id,
                'school' => $school->id,
            ]),
        ]);

        $meta = $this->portalMeta([
            'eyebrow' => strtoupper($district->name) . ' District Workspace',
            'header_title' => 'STANDARD SEVEN MOCK RESULTS - ' . $examYear . ' - ' . strtoupper($district->name),
            'hero_badge' => strtoupper($district->name) . ' School Centre',
            'hero_title' => 'Open school results entries for ' . strtoupper($district->name) . '.',
            'hero_copy' => 'Choose the exact school entry and move directly into the public PSLE school-results page from the shared portal workspace.',
            'stats_label' => 'Schools',
            'stats_copy' => 'Available schools are arranged through the same portal browsing experience used by ACSEE public results.',
            'support_label' => 'District',
            'support_value' => strtoupper($district->name),
            'support_copy' => 'Search by school name or code, then open the matching school results entry.',
            'stats_title_two' => 'Columns',
            'stats_title_three' => 'Mode',
            'stats_title_four' => 'Access',
            'stats_value_three' => 'Focused',
            'stats_value_four' => 'One Click',
            'stats_card_one' => 'The full school list currently available for the selected district.',
            'stats_card_two' => 'The shared card layout keeps school browsing consistent with the portal.',
            'stats_card_three' => 'The PSLE route now follows the same implementation style as ACSEE public results.',
            'stats_card_four' => 'Open any school results page directly from its portal card.',
            'toolbar_title' => strtoupper($district->name) . ' school entries',
            'toolbar_copy' => 'Search by school name or code to open the correct public PSLE result page.',
            'entry_copy' => 'Open this school entry to review its PSLE public results page.',
            'search_placeholder' => 'Search School from the list',
            'alpha_label' => 'CLICK ANY LETTER BELOW TO FILTER SCHOOLS BY ALPHABET',
            'alpha_all_label' => 'ALL SCHOOLS',
            'columns' => $this->portalColumns($entries->count(), 3),
            'back_url' => route('public.results.psle.districts', ['examYear' => $examYear, 'region' => $region->id]),
            'back_label' => 'Back to Districts',
            'primary_action_url' => route('public.results.psle.districts', ['examYear' => $examYear, 'region' => $region->id]),
            'primary_action_label' => 'All Districts',
        ]);

        return view('public.results-portal.index', [
            'link' => null,
            'entries' => $entries,
            'meta' => $meta,
            'token' => 'public-results-psle-schools-' . $district->id,
        ]);
    }

    public function schoolResults(string $examYear, Region $region, District $district, School $school)
    {
        $examYear = (int) $examYear;
        $this->assertDistrictBelongsToRegion($district, $region);
        $this->assertSchoolBelongsToHierarchy($school, $region, $district);

        if (!(auth()->check() && auth()->user()->is_admin)) {
            $this->checkPublicationStatus($examYear);
        }

        $rows = $this->psleBaseQuery($examYear)
            ->where('s.id', $school->id)
            ->join('subjects as sub', 'sub.id', '=', 'sm.subject_id')
            ->select([
                'c.id as candidate_pk',
                'c.candidate_id',
                'c.prem_no',
                'c.full_name',
                'c.gender',
                'sub.code as subject_code',
                'sub.name as subject_name',
                'sm.marks_obtained',
                'sm.max_marks',
                'sm.percentage',
            ])
            ->orderBy('c.candidate_id')
            ->orderBy('sub.name')
            ->get();

        $resultsAvailable = $rows->isNotEmpty();
        $candidates = [];
        $subjectSummary = [];
        $sexSummary = [
            'F' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'INC' => 0, 'ABS' => 0],
            'M' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'INC' => 0, 'ABS' => 0],
        ];
        $totals = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'INC' => 0, 'ABS' => 0];
        $candidateCount = 0;
        $registeredCandidateCount = (int) DB::table('candidates')
            ->where('school_id', $school->id)
            ->where('exam_type', self::EXAM_TYPE_CODE)
            ->count();
        $registeredByGender = DB::table('candidates')
            ->where('school_id', $school->id)
            ->where('exam_type', self::EXAM_TYPE_CODE)
            ->selectRaw('upper(coalesce(gender, "")) as gender_key, count(*) as total')
            ->groupBy('gender_key')
            ->pluck('total', 'gender_key');
        $schoolAverage = 0.0;
        $schoolAverageGrade = 'E';
        $schoolAverageMeta = $this->gradeMeta($schoolAverageGrade);
        $passRateAC = 0.0;
        $passRateAD = 0.0;
        $topCandidate = null;
        $districtPosition = null;
        $districtSchoolsWithResults = 0;
        $regionalPosition = null;
        $regionalSchoolsWithResults = 0;

        if ($resultsAvailable) {
            [$candidates, $subjectSummary] = $this->buildSchoolResultsPayload($rows);

            // Paginate candidates
            $perPage = max(1, count($candidates));
            $page = 1;
            $paginatedCandidates = new LengthAwarePaginator(
                collect($candidates)->forPage($page, $perPage),
                count($candidates),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );

            $satGirlsCount = collect($candidates)->where('gender', 'F')->where('status', '!=', 'ABS')->count();
            $satBoysCount = collect($candidates)->where('gender', 'M')->where('status', '!=', 'ABS')->count();
            $candidateCount = $satGirlsCount + $satBoysCount;

            foreach ($candidates as $candidate) {
                $gender = strtoupper((string) ($candidate['gender'] ?? ''));
                if (!isset($sexSummary[$gender])) {
                    continue;
                }

                $sexSummary[$gender][$candidate['average_grade']]++;
            }

            foreach (['F', 'M'] as $gender) {
                foreach ($totals as $grade => $count) {
                    $totals[$grade] += $sexSummary[$gender][$grade];
                }
            }

            $schoolAverage = \App\Services\Results\PsleSchoolAverageService::calculate(
                (float) collect($candidates)->sum('total_score'),
                $candidateCount,
                $registeredCandidateCount
            )['average'];
            $schoolAverageGrade = $this->gradeFromScaledScore($schoolAverage / 6);
            $schoolAverageMeta = $this->gradeMeta($schoolAverageGrade);
            $passRateAC = $candidateCount > 0
                ? round((collect($candidates)->where('status', '!=', 'ABS')->whereIn('average_grade', ['A', 'B', 'C'])->count() / $candidateCount) * 100, 2)
                : 0.0;
            $passRateAD = $candidateCount > 0
                ? round((collect($candidates)->where('status', '!=', 'ABS')->whereIn('average_grade', ['A', 'B', 'C', 'D'])->count() / $candidateCount) * 100, 2)
                : 0.0;
            $topCandidate = collect($candidates)
                ->where('status', '!=', 'ABS')
                ->sortBy([
                    ['total_score', 'desc'],
                    ['candidate_id', 'asc'],
                ])
                ->first();

            [$districtPosition, $districtSchoolsWithResults] = $this->schoolPositionByScope($examYear, 'district', (int) $district->id, (int) $school->id);
            [$regionalPosition, $regionalSchoolsWithResults] = $this->schoolPositionByScope($examYear, 'region', (int) $region->id, (int) $school->id);
        }

        $satByGender = [
            'F' => $satGirlsCount ?? 0,
            'M' => $satBoysCount ?? 0,
        ];

        return view('public.results.psle.school', [
            'examYear' => $examYear,
            'region' => $region,
            'district' => $district,
            'school' => $school,
            'resultsAvailable' => $resultsAvailable,
            'candidates' => $paginatedCandidates ?? collect([]),
            'subjectSummary' => $subjectSummary,
            'sexSummary' => $sexSummary,
            'totals' => $totals,
            'candidateCount' => $candidateCount,
            'registeredCandidateCount' => $registeredCandidateCount,
            'registeredGirlsCount' => (int) ($registeredByGender['F'] ?? 0),
            'registeredBoysCount' => (int) ($registeredByGender['M'] ?? 0),
            'satGirlsCount' => (int) $satByGender['F'],
            'satBoysCount' => (int) $satByGender['M'],
            'schoolAverage' => $schoolAverage,
            'schoolAverageGrade' => $schoolAverageGrade,
            'schoolAverageMeta' => $schoolAverageMeta,
            'passRateAC' => $passRateAC,
            'passRateAD' => $passRateAD,
            'topCandidate' => $topCandidate,
            'districtPosition' => $districtPosition,
            'districtSchoolsWithResults' => $districtSchoolsWithResults,
            'regionalPosition' => $regionalPosition,
            'regionalSchoolsWithResults' => $regionalSchoolsWithResults,
        ]);
    }

    private function buildSchoolResultsPayload(Collection $rows): array
    {
        $subjectSummary = [];
        $candidates = $rows
            ->groupBy('candidate_pk')
            ->map(function (Collection $candidateRows) use (&$subjectSummary) {
                $candidate = $candidateRows->first();
                $subjectRows = $candidateRows
                    ->map(function ($row) use (&$subjectSummary) {
                        $isAbsent = strtoupper((string) ($row->grade ?? '')) === 'ABS' || is_null($row->marks_obtained);
                        $score50 = $isAbsent ? null : $this->scaledScore50((float) $row->marks_obtained, (float) ($row->max_marks ?: 100));
                        $grade = $isAbsent ? 'ABS' : $this->gradeFromScaledScore($score50);
                        $subjectLabel = $this->subjectLabel($row->subject_name);
                        $subjectOrderKey = strtoupper(trim((string) $row->subject_name));

                        if (!isset($subjectSummary[$subjectLabel])) {
                            $subjectSummary[$subjectLabel] = [
                                'code' => strtoupper((string) $row->subject_code),
                                'subject' => $subjectLabel,
                                'registered' => 0,
                                'sat' => 0,
                                'abs' => 0,
                                'with_results' => 0,
                                'passed' => 0,
                                'a_to_d' => 0,
                                'A' => 0,
                                'B' => 0,
                                'C' => 0,
                                'D' => 0,
                                'E' => 0,
                                'total_score' => 0.0,
                            ];
                        }

                        $subjectSummary[$subjectLabel]['registered']++;
                        if ($isAbsent) {
                            $subjectSummary[$subjectLabel]['abs']++;
                        } else {
                            $subjectSummary[$subjectLabel]['sat']++;
                            $subjectSummary[$subjectLabel]['with_results']++;
                            if (isset($subjectSummary[$subjectLabel][$grade])) {
                                $subjectSummary[$subjectLabel][$grade]++;
                            }
                            $subjectSummary[$subjectLabel]['passed'] += in_array($grade, ['A', 'B', 'C'], true) ? 1 : 0;
                            $subjectSummary[$subjectLabel]['a_to_d'] += in_array($grade, ['A', 'B', 'C', 'D'], true) ? 1 : 0;
                            $subjectSummary[$subjectLabel]['total_score'] += $score50;
                        }

                        return [
                            'subject' => $subjectLabel,
                            'score_50' => $score50,
                            'grade' => $grade,
                            'order_key' => $subjectOrderKey,
                            'is_absent' => $isAbsent,
                        ];
                    })
                    ->sortBy(fn (array $item) => $this->subjectOrderIndex($item['order_key']))
                    ->values();

                $satSubjects = $subjectRows->filter(fn (array $item) => !($item['is_absent'] ?? false));
                $satCount = $satSubjects->count();
                $totalScore = $satCount > 0 ? round($satSubjects->sum('score_50'), 4) : 0.0;
                $averageScore = $satCount > 0 ? round($totalScore / $satCount, 4) : null;

                $status = match (true) {
                    $satCount === 0 => 'ABS',
                    $satCount < 6 => 'INC',
                    default => 'COMPLETE',
                };

                $averageGrade = match ($status) {
                    'ABS' => 'ABS',
                    'INC' => 'INC',
                    default => (!is_null($averageScore) ? $this->gradeFromScaledScore($averageScore) : 'E'),
                };

                $aggregatePoints = $status === 'COMPLETE'
                    ? (int) $satSubjects->sum(fn (array $subject) => $this->gradePointFromGrade((string) ($subject['grade'] ?? 'E')))
                    : null;
                $gpa = $status === 'COMPLETE' && $satCount > 0
                    ? round($aggregatePoints / $satCount, 4)
                    : null;

                return [
                    'candidate_id' => $candidate->candidate_id,
                    'prem_no' => $candidate->prem_no,
                    'full_name' => $candidate->full_name,
                    'gender' => strtoupper((string) $candidate->gender),
                    'subject_rows' => $subjectRows->all(),
                    'subject_result_string' => $subjectRows->map(fn (array $subject) => "{$subject['subject']} - {$subject['grade']}")->implode(', '),
                    'total_score' => $status === 'COMPLETE' || $status === 'INC' ? $totalScore : null,
                    'average_score' => $averageScore,
                    'average_grade' => $averageGrade,
                    'aggregate_points' => $aggregatePoints,
                    'gpa' => $gpa,
                    'average_meta' => $this->gradeMeta($averageGrade),
                    'status' => $status,
                ];
            })
            ->sortBy('candidate_id')
            ->values();

        $positionByCandidate = [];
        $sortedForPosition = $candidates
            ->filter(fn (array $c) => ($c['status'] ?? '') === 'COMPLETE')
            ->sortBy([
                ['total_score', 'desc'],
                ['candidate_id', 'asc'],
            ])
            ->values();

        $currentPosition = 0;
        $lastScore = null;
        foreach ($sortedForPosition as $index => $candidate) {
            if ($lastScore === null || (float) $candidate['total_score'] !== (float) $lastScore) {
                $currentPosition = $index + 1;
                $lastScore = (float) $candidate['total_score'];
            }

            $positionByCandidate[$candidate['candidate_id']] = $currentPosition;
        }

        $candidates = $candidates
            ->map(function (array $candidate) use ($positionByCandidate) {
                $candidate['position'] = $positionByCandidate[$candidate['candidate_id']] ?? '-';

                return $candidate;
            })
            ->sortBy([
                ['total_score', 'desc'],
                ['candidate_id', 'asc'],
            ])
            ->values()
            ->all();

        $subjectSummary = collect($subjectSummary)
            ->map(function (array $summary) {
                $averageScore = $summary['with_results'] > 0
                    ? round($summary['total_score'] / $summary['with_results'], 4)
                    : 0.0;
                $grade = $this->gradeFromScaledScore($averageScore);
                $graded = $summary['A'] + $summary['B'] + $summary['C'] + $summary['D'] + $summary['E'];
                $gpa = $graded > 0
                    ? round(
                        (
                            ($summary['A'] * 1) +
                            ($summary['B'] * 2) +
                            ($summary['C'] * 3) +
                            ($summary['D'] * 4) +
                            ($summary['E'] * 5)
                        ) / $graded,
                        4
                    )
                    : 0.0;

                return array_merge($summary, [
                    'average_score' => $averageScore,
                    'grade' => $grade,
                    'gpa' => $gpa,
                    'competence_meta' => $this->gradeMeta($grade),
                    'cancelled' => 0,
                ]);
            })
            ->sortBy(function (array $summary) {
                $code = strtoupper((string) ($summary['code'] ?? ''));
                if (preg_match('/(\d+)$/', $code, $matches)) {
                    return (int) $matches[1];
                }

                return $this->subjectOrderIndex($summary['subject']);
            })
            ->values()
            ->all();

        return [$candidates, $subjectSummary];
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

    private function psleBaseQuery(int $examYear)
    {
        $publication = DB::table('psle_result_publications as prp')
            ->join('result_snapshots as rs', 'rs.id', '=', 'prp.snapshot_id')
            ->where('prp.exam_year_id', function ($query) use ($examYear) {
                $query->select('id')->from('exam_years')->where('year_label', $examYear)->limit(1);
            })
            ->where('prp.status', 'published')
            ->where('rs.is_active', true)
            ->where('rs.is_rolled_back', false)
            ->select('rs.id as snapshot_id')
            ->first();

        $snapshotId = $publication ? $publication->snapshot_id : 0;

        return DB::table('subject_marks as sm')
            ->join('candidates as c', 'c.id', '=', 'sm.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->where('sm.exam_type_id', $this->psleExamTypeId())
            ->where('sm.year', $examYear)
            ->where('sm.snapshot_id', $snapshotId)
            ->where('s.education_level', 'PRIMARY');
    }

    private function pslePortalBaseQuery()
    {
        return DB::table('schools as s')
            ->whereIn('s.source_system', [
                NectaPsle2025SchoolSyncService::SOURCE_SYSTEM,
                'IRMS_PSLE_DEMO',
            ])
            ->where('s.education_level', 'PRIMARY');
    }

    private function schoolPositionByScope(int $examYear, string $scope, int $scopeId, int $schoolId): array
    {
        $query = $this->psleBaseQuery($examYear);

        if ($scope === 'region') {
            $query->where('s.region_id', $scopeId);
        } else {
            $query->where('s.district_id', $scopeId);
        }

        $rows = $query
            ->select([
                's.id as school_id',
                'c.id as candidate_pk',
                'sm.marks_obtained',
                'sm.max_marks',
            ])
            ->get();

        if ($rows->isEmpty()) {
            return [null, 0];
        }

        $rankedSchoolIds = $rows
            ->groupBy('school_id')
            ->map(function (Collection $schoolRows, $currentSchoolId) {
                $candidateTotals = $schoolRows
                    ->groupBy('candidate_pk')
                    ->map(function (Collection $candidateRows) {
                        return round($candidateRows->sum(function ($row) {
                            return $this->scaledScore50((float) $row->marks_obtained, (float) ($row->max_marks ?: 100));
                        }), 4);
                    })
                    ->values();

                $candidateCount = $candidateTotals->count();
                if ($candidateCount === 0) {
                    return null;
                }

                return [
                    'school_id' => (int) $currentSchoolId,
                    'average' => round($candidateTotals->sum() / $candidateCount, 4),
                ];
            })
            ->filter()
            ->sortBy([
                ['average', 'desc'],
                ['school_id', 'asc'],
            ])
            ->values();

        $position = null;
        foreach ($rankedSchoolIds as $index => $schoolRow) {
            if ((int) $schoolRow['school_id'] === $schoolId) {
                $position = $index + 1;
                break;
            }
        }

        return [$position, $rankedSchoolIds->count()];
    }

    private function psleExamTypeId(): int
    {
        static $id = null;

        if ($id === null) {
            $id = (int) ExamType::query()->where('code', self::EXAM_TYPE_CODE)->value('id');
        }

        return $id;
    }

    private function assertDistrictBelongsToRegion(District $district, Region $region): void
    {
        abort_unless((int) $district->region_id === (int) $region->id, 404);
    }

    private function assertSchoolBelongsToHierarchy(School $school, Region $region, District $district): void
    {
        abort_unless((int) $school->region_id === (int) $region->id, 404);
        abort_unless((int) $school->district_id === (int) $district->id, 404);
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

    private function gradeMeta(string $grade): array
    {
        $competenceLabels = $this->psleCompetenceLabel();
        $meta = [
            'A' => ['label' => "Grade A ({$competenceLabels['A']})", 'color' => '#00A82A'],
            'B' => ['label' => "Grade B ({$competenceLabels['B']})", 'color' => '#1FEE0B'],
            'C' => ['label' => "Grade C ({$competenceLabels['C']})", 'color' => '#DEF043'],
            'D' => ['label' => "Grade D ({$competenceLabels['D']})", 'color' => '#FF772F'],
            'E' => ['label' => "Grade E ({$competenceLabels['E']})", 'color' => '#FF272F'],
        ];

        return $meta[$grade] ?? $meta['E'];
    }

    private function psleCompetenceLabel(string $grade = null): array|string
    {
        static $labels = null;

        if ($labels === null) {
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
                foreach (['A', 'B', 'C', 'D', 'E'] as $gradeKey) {
                    $gpa = $this->gradePointFromGrade($gradeKey);
                    $match = $rules->first(function (array $rule) use ($gpa) {
                        return (float) ($rule['min_value'] ?? -INF) <= $gpa
                            && (float) ($rule['max_value'] ?? INF) >= $gpa
                            && !($rule['is_disabled'] ?? false);
                    });

                    if (!empty($match['level_label'])) {
                        $labels[$gradeKey] = (string) $match['level_label'];
                    }
                }
            }
        }

        if ($grade === null) {
            return $labels;
        }

        return $labels[strtoupper($grade)] ?? 'Unknown';
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

    private function subjectLabel(string $subjectName): string
    {
        $map = [
            'KISWAHILI' => 'Kiswahili',
            'ENGLISH LANGUAGE' => 'English',
            'SOCIAL STUDIES AND VOCATIONAL SKILLS' => 'Maarifa',
            'MATHEMATICS' => 'Hisabati',
            'SCIENCE AND TECHNOLOGY' => 'Science',
            'CIVIC AND MORAL EDUCATION' => 'Uraia',
        ];

        $key = strtoupper(trim($subjectName));

        return $map[$key] ?? $subjectName;
    }

    private function subjectOrderIndex(string $subjectName): int
    {
        $map = [
            'KISWAHILI' => 0,
            'ENGLISH LANGUAGE' => 1,
            'SOCIAL STUDIES AND VOCATIONAL SKILLS' => 2,
            'MAARIFA' => 2,
            'MATHEMATICS' => 3,
            'HISABATI' => 3,
            'SCIENCE AND TECHNOLOGY' => 4,
            'SCIENCE' => 4,
            'CIVIC AND MORAL EDUCATION' => 5,
            'URAIA' => 5,
        ];

        $key = strtoupper(trim($subjectName));

        return $map[$key] ?? 99;
    }

    private function portalMeta(array $overrides = []): array
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
            'columns' => 3,
        ];

        return array_merge($base, $overrides);
    }

    private function portalColumns(int $entryCount, int $maxColumns): int
    {
        if ($entryCount <= 0) {
            return 1;
        }

        return max(1, min($entryCount, $maxColumns));
    }
}
