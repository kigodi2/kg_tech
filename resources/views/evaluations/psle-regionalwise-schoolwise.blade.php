@extends('layout')

@section('content')
<style>
.keyboard-navigable-table td:focus {
    outline: 2px solid #f5d000;
    outline-offset: -2px;
    background-color: #fff8b3;
}
</style>
@php
    $isZonalCouncilwiseEvaluation = ($tableMode ?? null) === 'zonal-councilwise';
    $isZonalSchoolwiseEvaluation = ($tableMode ?? null) === 'zonal-schoolwise';
    $isOwnershipEvaluation = ($tableMode ?? null) === 'ownership' || str_contains(strtoupper((string) ($evaluationLabel ?? '')), 'OWNERSHIP');
    $isCouncilwiseEvaluation = !$isZonalCouncilwiseEvaluation && !$isZonalSchoolwiseEvaluation && (($tableMode ?? null) === 'councilwise' || str_contains(strtoupper((string) ($evaluationLabel ?? '')), 'COUNCIL'));
    $isDistrictwiseEvaluation = ($tableMode ?? null) === 'districtwise' || str_contains(strtoupper((string) ($evaluationLabel ?? '')), 'DISTRICT');
    $isGeneralEvaluation = ($tableMode ?? null) === 'general' || trim(strtoupper((string) ($evaluationLabel ?? ''))) === 'GENERAL EVALUATION';
    $isSchoolwiseEvaluation = ($tableMode ?? null) === 'schoolwise';
    $isRegionalwiseEvaluation = ($tableMode ?? null) === 'regionalwise';
    $showSummaryBlock = in_array(($tableMode ?? null), ['schoolwise', 'zonal-schoolwise', 'councilwise', 'zonal-councilwise', 'districtwise', 'ownership', 'general', 'regionalwise'], true);
    $hideSecondColumn = !$isZonalCouncilwiseEvaluation && !$isZonalSchoolwiseEvaluation && ($isCouncilwiseEvaluation || $isDistrictwiseEvaluation || $isGeneralEvaluation || $isRegionalwiseEvaluation);
    $showThirdColumn = $isZonalSchoolwiseEvaluation;
    $primaryColumnLabel = $isZonalSchoolwiseEvaluation ? 'SCHOOL' : ($isZonalCouncilwiseEvaluation ? 'COUNCIL' : ($isOwnershipEvaluation ? 'OWNERSHIP' : ($isGeneralEvaluation ? 'SEX' : ($isDistrictwiseEvaluation ? 'DISTRICT' : ($isRegionalwiseEvaluation ? 'REGION' : 'COUNCIL')))));
    $secondaryColumnLabel = $isZonalSchoolwiseEvaluation ? 'COUNCIL' : ($isZonalCouncilwiseEvaluation ? 'REGION' : ($isOwnershipEvaluation ? 'SCHOOLS' : 'SCHOOL'));
    $thirdColumnLabel = 'REGION';
    $primaryColumnKey = $isZonalSchoolwiseEvaluation ? 'school' : ($isZonalCouncilwiseEvaluation ? 'council' : ($isOwnershipEvaluation ? 'ownership' : ($isDistrictwiseEvaluation ? 'district' : ($isRegionalwiseEvaluation ? 'region' : 'council'))));
    $secondaryColumnKey = $isZonalSchoolwiseEvaluation ? 'council' : ($isZonalCouncilwiseEvaluation ? 'region' : ($isOwnershipEvaluation ? 'schools_count' : 'school'));
    $thirdColumnKey = 'region';
    $secondaryColumnAlign = $isOwnershipEvaluation ? 'text-center' : 'text-left';
    $primaryColumnWidth = ($isOwnershipEvaluation || $isZonalSchoolwiseEvaluation) ? '168px' : '92px';
    $secondaryColumnWidth = $isOwnershipEvaluation ? '64px' : ($isZonalSchoolwiseEvaluation ? '120px' : null);
    $metricWidth = '38px';
    $summaryMetricWidth = '52px';
    $groupCount = $rows->count();
    $regionalAverage = null;
    $bestRow = null;
    $leastRow = null;
    $tableColspan = $hideSecondColumn ? 33 : 34;
    if ($showThirdColumn) {
        $tableColspan++;
    }
    $groupCountLabel = match (true) {
        $isSchoolwiseEvaluation || $isZonalSchoolwiseEvaluation => 'TOTAL SCHOOLS',
        $isZonalCouncilwiseEvaluation => 'TOTAL COUNCILS',
        $isCouncilwiseEvaluation => 'TOTAL COUNCILS',
        $isDistrictwiseEvaluation => 'TOTAL DISTRICTS',
        $isOwnershipEvaluation => 'TOTAL OWNERSHIP GROUPS',
        $isGeneralEvaluation => 'TOTAL SEX GROUPS',
        $isRegionalwiseEvaluation => 'TOTAL REGIONS',
        default => 'TOTAL GROUPS',
    };
    $averageLabel = match (true) {
        $isSchoolwiseEvaluation => 'REGIONAL AVERAGE',
        $isZonalSchoolwiseEvaluation => 'ZONAL AVERAGE',
        $isZonalCouncilwiseEvaluation => 'COUNCILWISE AVERAGE',
        $isCouncilwiseEvaluation => 'COUNCILWISE AVERAGE',
        $isDistrictwiseEvaluation => 'DISTRICTWISE AVERAGE',
        $isOwnershipEvaluation => 'OWNERSHIP GROUP AVERAGE',
        $isGeneralEvaluation => 'SEX GROUP AVERAGE',
        $isRegionalwiseEvaluation => 'REGIONALWISE AVERAGE',
        default => 'GROUP AVERAGE',
    };
    $averageColumnLabel = match (true) {
        $isSchoolwiseEvaluation || $isZonalSchoolwiseEvaluation => 'SCHOOL AVERAGE',
        $isZonalCouncilwiseEvaluation => 'COUNCIL AVERAGE',
        $isCouncilwiseEvaluation => 'COUNCIL AVERAGE',
        $isDistrictwiseEvaluation => 'DISTRICT AVERAGE',
        $isOwnershipEvaluation => 'GROUP AVERAGE',
        $isGeneralEvaluation => 'SEX AVERAGE',
        $isRegionalwiseEvaluation => 'REGION AVERAGE',
        default => 'AVERAGE',
    };
    $bestLabel = match (true) {
        $isSchoolwiseEvaluation || $isZonalSchoolwiseEvaluation => 'BEST SCHOOL',
        $isZonalCouncilwiseEvaluation => 'BEST COUNCIL',
        $isCouncilwiseEvaluation => 'BEST COUNCIL',
        $isDistrictwiseEvaluation => 'BEST DISTRICT',
        $isOwnershipEvaluation => 'BEST OWNERSHIP GROUP',
        $isGeneralEvaluation => 'BEST SEX GROUP',
        $isRegionalwiseEvaluation => 'BEST REGION',
        default => 'BEST GROUP',
    };
    $leastLabel = match (true) {
        $isSchoolwiseEvaluation || $isZonalSchoolwiseEvaluation => 'LEAST SCHOOL',
        $isZonalCouncilwiseEvaluation => 'LEAST COUNCIL',
        $isCouncilwiseEvaluation => 'LEAST COUNCIL',
        $isDistrictwiseEvaluation => 'LEAST DISTRICT',
        $isOwnershipEvaluation => 'LEAST OWNERSHIP GROUP',
        $isGeneralEvaluation => 'LEAST SEX GROUP',
        $isRegionalwiseEvaluation => 'LEAST REGION',
        default => 'LEAST GROUP',
    };
    $bestName = null;
    $leastName = null;
    $regionalAverageGrade = null;
    $regionalAverageCompetence = null;
    $governmentSchoolCount = 0;
    $nonGovernmentSchoolCount = 0;
    $averageBadge = function ($average) {
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

        $competenceLabels = [
            'A' => 'Excellent',
            'B' => 'Very Good',
            'C' => 'Good',
            'D' => 'Satisfactory',
            'E' => 'Unsatisfactory',
        ];

        return [
            'grade' => $grade,
            'competence' => $competenceLabels[$grade] ?? 'Unsatisfactory',
            'color' => match ($grade) {
                'A' => '#00A82A',
                'B' => '#1FEE0B',
                'C' => '#DEF043',
                'D' => '#FF772F',
                default => '#FF272F',
            },
        ];
    };

    if ($showSummaryBlock && $rows->isNotEmpty()) {
        if ($isSchoolwiseEvaluation || $isZonalSchoolwiseEvaluation) {
            $governmentSchoolCount = $rows->filter(fn ($row) => strtoupper((string) ($row['ownership'] ?? '')) === 'GOVERNMENT')->count();
            $nonGovernmentSchoolCount = $rows->filter(fn ($row) => strtoupper((string) ($row['ownership'] ?? '')) === 'NON-GOVERNMENT')->count();
        }
        $regionalAverage = $rows->filter(function ($row) {
                return !is_null($row['avg_marks'] ?? null)
                    && (float) ($row['avg_marks'] ?? 0) > 0
                    && (int) data_get($row, 'sat.t', 0) > 0;
            })
            ->pluck('avg_marks')
            ->avg();
        $regionalAverage = is_null($regionalAverage) ? null : round((float) $regionalAverage, 2);
        if (!is_null($regionalAverage) && $regionalAverage > 0) {
            $regionalAverageGrade = \App\Models\GradingProfile::query()
                ->where('code', 'like', 'PSLE-%')
                ->orderByDesc('is_active')
                ->orderBy('id')
                ->first();

            $regionalAverageGrade = match (true) {
                $regionalAverage >= 246 => 'A',
                $regionalAverage >= 186 => 'B',
                $regionalAverage >= 126 => 'C',
                $regionalAverage >= 66 => 'D',
                default => 'E',
            };

            $competenceLabels = [
                'A' => 'Excellent',
                'B' => 'Very Good',
                'C' => 'Good',
                'D' => 'Satisfactory',
                'E' => 'Unsatisfactory',
            ];
            $regionalAverageCompetence = $competenceLabels[$regionalAverageGrade] ?? null;
        }
        $bestRow = $rows->sortBy('pos')->first();
        $leastRow = $rows->sortByDesc('pos')->first();
        $bestName = match (true) {
            $isSchoolwiseEvaluation || $isZonalSchoolwiseEvaluation => $bestRow['school'] ?? '-',
            $isZonalCouncilwiseEvaluation => $bestRow['council'] ?? '-',
            $isCouncilwiseEvaluation => $bestRow['council'] ?? '-',
            $isDistrictwiseEvaluation => $bestRow['district'] ?? '-',
            $isOwnershipEvaluation => $bestRow['ownership'] ?? '-',
            $isGeneralEvaluation => $bestRow['council'] ?? '-',
            $isRegionalwiseEvaluation => $bestRow['region'] ?? '-',
            default => '-',
        };
        $leastName = match (true) {
            $isSchoolwiseEvaluation || $isZonalSchoolwiseEvaluation => $leastRow['school'] ?? '-',
            $isZonalCouncilwiseEvaluation => $leastRow['council'] ?? '-',
            $isCouncilwiseEvaluation => $leastRow['council'] ?? '-',
            $isDistrictwiseEvaluation => $leastRow['district'] ?? '-',
            $isOwnershipEvaluation => $leastRow['ownership'] ?? '-',
            $isGeneralEvaluation => $leastRow['council'] ?? '-',
            $isRegionalwiseEvaluation => $leastRow['region'] ?? '-',
            default => '-',
        };
    }

    $barcodePayload = sprintf(
        'PSLE-%s-%s-WEB',
        substr((string) (preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($isRegionalwiseEvaluation || $isZonalCouncilwiseEvaluation || $isZonalSchoolwiseEvaluation || !$region ? 'ZONAL' : ($region->name ?? 'REG')))) ?: 'REG'), 0, 3),
        now()->format('Ymd-His')
    );
    $barcodePatterns = [
        '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
        '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
        '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
        'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn',
        'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
        'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
        'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn',
        'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn', 'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw',
        'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
        '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '$' => 'nwnwnwnnn',
        '/' => 'nwnwnnnwn', '+' => 'nwnnnwnwn', '%' => 'nnnwnwnwn', '*' => 'nwnnwnwnn',
    ];
    $barcodeBars = [];
    $barcodeWidth = 0;
    $narrowBar = 2;
    $wideBar = 5;
    foreach (str_split('*' . strtoupper($barcodePayload) . '*') as $char) {
        $pattern = $barcodePatterns[$char] ?? $barcodePatterns['-'];
        foreach (str_split($pattern) as $index => $bar) {
            $lineWidth = $bar === 'w' ? $wideBar : $narrowBar;
            $barcodeBars[] = ['draw' => $index % 2 === 0, 'width' => $lineWidth];
            $barcodeWidth += $lineWidth;
        }
        $barcodeBars[] = ['draw' => false, 'width' => $narrowBar];
        $barcodeWidth += $narrowBar;
    }
@endphp

<div style="background-color: #B0E0E6; min-height: 100vh; padding-top: 1.5rem; padding-bottom: 1.5rem; font-family: 'Maiandra GD', sans-serif; font-weight: 700; white-space: nowrap;">
    <div style="width: min(94vw, 1700px); margin: 0 auto; padding: 0 0.35rem;">
        <div style="margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <a href="{{ ($isRegionalwiseEvaluation || $isZonalCouncilwiseEvaluation || $isZonalSchoolwiseEvaluation) ? route('evaluations.psle.zonalwise') : route('evaluations.psle.regionalwise.region', ['region' => $region->id]) }}" style="color: #003366; text-decoration: none; font-weight: bold; font-size: 1.05rem;">
                ← Back to {{ ($isRegionalwiseEvaluation || $isZonalCouncilwiseEvaluation || $isZonalSchoolwiseEvaluation) ? 'Zonalwise' : ($region ? strtoupper($region->name) : 'Zonalwise') }}
            </a>

            <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}"
               style="border: 1px solid #065f46; background: linear-gradient(180deg, #10b981 0%, #059669 55%, #047857 100%); color: #ffffff; padding: 0.34rem 0.95rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.42rem; height: 38px; border-radius: 0.75rem; box-shadow: 0 3px 0 #064e3b, 0 10px 20px rgba(4, 120, 87, 0.24), inset 0 1px 0 rgba(255,255,255,0.28);">
                <i class="fas fa-file-pdf" style="color: #ffffff;"></i>
                <span>Export PDF</span>
                <span>&rarr;</span>
            </a>
        </div>

        <div style="background-color: #0b2f5b; padding-top: 1.2rem; padding-bottom: 1.2rem; padding-left: 1.5rem; padding-right: 1.5rem; margin-bottom: 0.2rem; position: relative;">
            <div class="flex items-center justify-between gap-4">
                <div class="flex-shrink-0">
                    <img src="{{ asset('images/emblem.png') }}" alt="Coat of Arms" class="h-20 w-20 object-contain">
                </div>

                <div class="flex-1 text-center px-4">
                    <p style="margin: 0; font-size: 1.2rem; font-weight: bold; color: #ffffff; line-height: 1.25;">PRIME MINISTER'S OFFICE</p>
                    <p style="margin: 0.25rem 0 0 0; font-size: 1.2rem; font-weight: bold; color: #f5d000; line-height: 1.25;">REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT</p>
                    <p style="margin: 0.25rem 0 0 0; font-size: 1.15rem; font-weight: bold; color: #ffffff; line-height: 1.25;">ACADEMIC ZONE: TABORA, SINGIDA, IRINGA AND DODOMA (TASIDO)</p>
                    <p style="margin: 0.25rem 0 0 0; font-size: 1.15rem; font-weight: bold; color: #f5d000; line-height: 1.25;">
                        @if($isRegionalwiseEvaluation || $isZonalCouncilwiseEvaluation || $isZonalSchoolwiseEvaluation)
                            {{ strtoupper($evaluationLabel ?? 'PSLE ZONAL EVALUATION') }} - {{ $examYearValue }}
                        @else
                            PSLE REGIONAL EVALUATIONS - {{ $examYearValue }} - {{ strtoupper($region->name) }}
                        @endif
                    </p>
                </div>

                <div class="flex-shrink-0">
                    <img src="{{ asset('images/emblem.png') }}" alt="Coat of Arms" class="h-20 w-20 object-contain">
                </div>
            </div>
            
            <div class="announcement">
                <div class="announcement-track">
                    <span class="announcement-copy">
                        <span class="fire-icon">&#128293;</span>
                        <span class="fire-text">Results have been officially published.</span>
                        <span class="fire-icon">&#128293;</span>
                    </span>
                </div>
            </div>
        </div>

        <div style="padding: 0 0.35rem; margin-bottom: 0.35rem;">
            <div style="height: 4px; width: 100%; background: #f5d000;"></div>
        </div>

        @if($showSummaryBlock)
            <div style="padding: 0.2rem 0.35rem 0.35rem 0.35rem; color: #000080;">
                <div style="font-size: 1rem; line-height: 1.35; white-space: normal;">
                    @if($isRegionalwiseEvaluation || $isZonalCouncilwiseEvaluation || $isZonalSchoolwiseEvaluation)
                        <p style="margin: 0 0 0.2rem 0;">ZONE: TASIDO</p>
                    @else
                        <p style="margin: 0 0 0.2rem 0;">REGION: {{ strtoupper($region->name) }}</p>
                    @endif
                    @if(!empty($zonalRank['position']) && !empty($zonalRank['total']))
                        <p style="margin: 0 0 0.2rem 0;">ZONAL RANK: {{ $zonalRank['position'] }} OUT OF {{ $zonalRank['total'] }}</p>
                    @endif
                    <p style="margin: 0 0 0.2rem 0;">{{ $groupCountLabel }}: {{ number_format($groupCount) }}@if($isSchoolwiseEvaluation || $isZonalSchoolwiseEvaluation) (GOVERNMENT: {{ number_format($governmentSchoolCount) }}, NON-GOVERNMENT: {{ number_format($nonGovernmentSchoolCount) }})@endif</p>
                    <p style="margin: 0 0 0.2rem 0;">TOTAL REGISTERED CANDIDATES: {{ number_format((int) data_get($total ?? [], 'registered.t', 0)) }} (F: {{ number_format((int) data_get($total ?? [], 'registered.f', 0)) }}, M: {{ number_format((int) data_get($total ?? [], 'registered.m', 0)) }})</p>
                    <p style="margin: 0 0 0.2rem 0;">TOTAL SAT CANDIDATES: {{ number_format((int) data_get($total ?? [], 'sat.t', 0)) }} (F: {{ number_format((int) data_get($total ?? [], 'sat.f', 0)) }}, M: {{ number_format((int) data_get($total ?? [], 'sat.m', 0)) }}) | TOTAL ABSENT CANDIDATES: {{ number_format((int) data_get($total ?? [], 'absent.t', 0)) }} (F: {{ number_format((int) data_get($total ?? [], 'absent.f', 0)) }}, M: {{ number_format((int) data_get($total ?? [], 'absent.m', 0)) }}) | TOTAL CANDIDATES WITH INCOMPLETES: {{ number_format((int) data_get($total ?? [], 'inc.t', 0)) }} (F: {{ number_format((int) data_get($total ?? [], 'inc.f', 0)) }}, M: {{ number_format((int) data_get($total ?? [], 'inc.m', 0)) }})</p>
                    <p style="margin: 0 0 0.2rem 0;">PASS RATE (A-C): {{ number_format((float) data_get($total ?? [], 'pass_ac.pct', 0), 2) }}% | PASS RATE (A-D): {{ number_format((float) data_get($total ?? [], 'pass_ad.pct', 0), 2) }}%</p>
                    <p style="margin: 0 0 0.2rem 0;">{{ $averageLabel }}: {{ is_null($regionalAverage) ? '-' : number_format((float) $regionalAverage, 2) }}@if($regionalAverageGrade && $regionalAverageCompetence) <span style="background: {{ match($regionalAverageGrade) { 'A' => '#00A82A', 'B' => '#1FEE0B', 'C' => '#DEF043', 'D' => '#FF772F', default => '#FF272F' } }}; color: #000080; padding: 0 0.25rem;">Grade {{ $regionalAverageGrade }} ({{ $regionalAverageCompetence }})</span>@endif</p>
                    <p style="margin: 0 0 0.2rem 0;">{{ $bestLabel }}: {{ $bestName ?? '-' }} @if($bestRow) (AVERAGE: {{ number_format((float) ($bestRow['avg_marks'] ?? 0), 2) }}@php($bestAverageBadge = $averageBadge($bestRow['avg_marks'] ?? null))@if($bestAverageBadge) <span style="background: {{ $bestAverageBadge['color'] }}; color: #000080; padding: 0 0.25rem;">Grade {{ $bestAverageBadge['grade'] }} ({{ $bestAverageBadge['competence'] }})</span>@endif, POS: {{ $bestRow['pos'] ?? '-' }}) @endif</p>
                    <p style="margin: 0;">{{ $leastLabel }}: {{ $leastName ?? '-' }} @if($leastRow) (AVERAGE: {{ number_format((float) ($leastRow['avg_marks'] ?? 0), 2) }}@php($leastAverageBadge = $averageBadge($leastRow['avg_marks'] ?? null))@if($leastAverageBadge) <span style="background: {{ $leastAverageBadge['color'] }}; color: #000080; padding: 0 0.25rem;">Grade {{ $leastAverageBadge['grade'] }} ({{ $leastAverageBadge['competence'] }})</span>@endif, POS: {{ $leastRow['pos'] ?? '-' }}) @endif</p>
                </div>
            </div>
        @endif

        <div style="background-color: #B0E0E6; padding: 0.35rem;">
            <div style="width: 100%; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch;">
                <table class="psle-evaluation-table keyboard-navigable-table" style="width: max-content; min-width: 100%; background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #999;">
                    <thead>
                        <tr style="background-color: #003366;">
                            <th colspan="{{ $tableColspan }}" style="border: 1px solid #999; padding: 0.18rem 0.25rem; font-size: 0.92rem; font-weight: bold; text-align: left; color: #FFFFFF;">{{ strtoupper($evaluationLabel) }}</th>
                        </tr>
                        <tr style="background-color: #003366; color: #FFFFFF;">
                            <th rowspan="2" style="border: 1px solid #999; padding: 0.14rem 0.12rem; text-align: center; font-size: 0.84rem; line-height: 1.05; width: 28px; min-width: 28px; max-width: 28px;">S/N</th>
                            <th rowspan="2" style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: left; font-size: 0.84rem; line-height: 1.05; min-width: {{ $primaryColumnWidth }};">{{ $primaryColumnLabel }}</th>
                            @unless($hideSecondColumn)
                                <th rowspan="2" style="border: 1px solid #999; padding: 0.14rem 0.16rem; text-align: {{ $isOwnershipEvaluation ? 'center' : 'left' }}; font-size: 0.84rem; line-height: 1.05; @if($secondaryColumnWidth) min-width: {{ $secondaryColumnWidth }}; @endif">{{ $secondaryColumnLabel }}</th>
                            @endunless
                            @if($showThirdColumn)
                                <th rowspan="2" style="border: 1px solid #999; padding: 0.14rem 0.16rem; text-align: left; font-size: 0.84rem; line-height: 1.05; min-width: 120px;">{{ $thirdColumnLabel }}</th>
                            @endif
                                <th colspan="3" style="border: 1px solid #999; padding: 0.14rem 0.22rem; text-align: center; font-size: 0.84rem; line-height: 1.05;">REGISTERED</th>
                                <th colspan="4" style="border: 1px solid #999; padding: 0.14rem 0.22rem; text-align: center; font-size: 0.84rem; line-height: 1.05;">ABSENT</th>
                                <th colspan="4" style="border: 1px solid #999; padding: 0.14rem 0.22rem; text-align: center; font-size: 0.84rem; line-height: 1.05;">SAT</th>
                                <th colspan="4" style="border: 1px solid #999; padding: 0.14rem 0.22rem; text-align: center; font-size: 0.84rem; line-height: 1.05;">INC</th>
                                <th rowspan="2" style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.84rem; line-height: 1.05;">A</th>
                                <th rowspan="2" style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.84rem; line-height: 1.05;">B</th>
                                <th rowspan="2" style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.84rem; line-height: 1.05;">C</th>
                                <th colspan="4" style="border: 1px solid #999; padding: 0.14rem 0.22rem; text-align: center; font-size: 0.84rem; line-height: 1.05;">A - C</th>
                                <th rowspan="2" style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.84rem; line-height: 1.05;">D</th>
                                <th colspan="4" style="border: 1px solid #999; padding: 0.14rem 0.22rem; text-align: center; font-size: 0.84rem; line-height: 1.05;">A - D</th>
                                <th rowspan="2" style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.84rem; line-height: 1.05;">E</th>
                                <th rowspan="2" style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; width: {{ $summaryMetricWidth }}; min-width: {{ $summaryMetricWidth }}; max-width: {{ $summaryMetricWidth }}; font-size: 0.68rem; line-height: 1.05; white-space: normal; word-break: break-word;">{{ $averageColumnLabel }}</th>
                                <th rowspan="2" style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; width: {{ $summaryMetricWidth }}; min-width: {{ $summaryMetricWidth }}; max-width: {{ $summaryMetricWidth }}; font-size: 0.84rem; line-height: 1.05;">GRD</th>
                                <th rowspan="2" style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; width: {{ $summaryMetricWidth }}; min-width: {{ $summaryMetricWidth }}; max-width: {{ $summaryMetricWidth }}; font-size: 0.84rem; line-height: 1.05;">POS</th>
                            </tr>
                            <tr style="background-color: #003366; color: #FFFFFF;">
                            @for($i = 0; $i < 4; $i++)
                                <th style="border: 1px solid #999; padding: 0.12rem 0.16rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">M</th>
                                <th style="border: 1px solid #999; padding: 0.12rem 0.16rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">F</th>
                                <th style="border: 1px solid #999; padding: 0.12rem 0.16rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">T</th>
                                @if($i > 0)
                                    <th style="border: 1px solid #999; padding: 0.12rem 0.16rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">%</th>
                                @endif
                            @endfor
                            <th style="border: 1px solid #999; padding: 0.12rem 0.16rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">M</th>
                            <th style="border: 1px solid #999; padding: 0.12rem 0.16rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">F</th>
                            <th style="border: 1px solid #999; padding: 0.12rem 0.16rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">T</th>
                            <th style="border: 1px solid #999; padding: 0.12rem 0.16rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">%</th>
                            <th style="border: 1px solid #999; padding: 0.12rem 0.16rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">M</th>
                            <th style="border: 1px solid #999; padding: 0.12rem 0.16rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">F</th>
                            <th style="border: 1px solid #999; padding: 0.12rem 0.16rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">T</th>
                            <th style="border: 1px solid #999; padding: 0.12rem 0.16rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">%</th>
                            </tr>
                    </thead>
                    <tbody style="background-color: LIGHTYELLOW; color: #000080;">
                        @forelse($rows as $index => $row)
                            <tr>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.08rem; text-align: center; font-size: 0.82rem; line-height: 1.05; width: 28px; min-width: 28px; max-width: 28px;">{{ $index + 1 }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: left; font-size: 0.82rem; line-height: 1.05; min-width: {{ $primaryColumnWidth }};">{{ $row[$primaryColumnKey] }}</td>
                                @unless($hideSecondColumn)
                                    <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.12rem; text-align: {{ $isOwnershipEvaluation ? 'center' : 'left' }}; font-size: 0.82rem; line-height: 1.05; @if($secondaryColumnWidth) min-width: {{ $secondaryColumnWidth }}; @endif">{{ $row[$secondaryColumnKey] }}</td>
                                @endunless
                                @if($showThirdColumn)
                                    <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.12rem; text-align: left; font-size: 0.82rem; line-height: 1.05; min-width: 120px;">{{ $row[$thirdColumnKey] }}</td>
                                @endif
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['registered']['m'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['registered']['f'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['registered']['t'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['absent']['m'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['absent']['f'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['absent']['t'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ number_format((float) $row['absent']['pct'], 0) }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['sat']['m'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['sat']['f'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['sat']['t'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ number_format((float) $row['sat']['pct'], 0) }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['inc']['m'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['inc']['f'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['inc']['t'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ number_format((float) $row['inc']['pct'], 0) }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['grades']['a']['t'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['grades']['b']['t'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['grades']['c']['t'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['pass_ac']['m'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['pass_ac']['f'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['pass_ac']['t'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ number_format((float) $row['pass_ac']['pct'], 0) }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['grades']['d']['t'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['pass_ad']['m'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['pass_ad']['f'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['pass_ad']['t'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ number_format((float) $row['pass_ad']['pct'], 0) }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['grades']['e']['t'] }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.12rem; text-align: center; width: {{ $summaryMetricWidth }}; min-width: {{ $summaryMetricWidth }}; max-width: {{ $summaryMetricWidth }}; font-size: 0.8rem; line-height: 1;">{{ is_null($row['avg_marks']) ? '-' : number_format((float) $row['avg_marks'], 2) }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $summaryMetricWidth }}; min-width: {{ $summaryMetricWidth }}; max-width: {{ $summaryMetricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['avg_grade'] ?? '-' }}</td>
                                <td tabindex="0" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; font-weight: bold; width: {{ $summaryMetricWidth }}; min-width: {{ $summaryMetricWidth }}; max-width: {{ $summaryMetricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['pos'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $tableColspan }}" style="border: 1px solid #999; padding: 1rem; text-align: center;">No PSLE regional evaluation rows are available for this category yet.</td>
                            </tr>
                        @endforelse
                        @if($rows->isNotEmpty() && isset($total))
                            <tr style="background-color: #cbd5e1; font-weight: bold; border-top: 2px solid #666; border-bottom: 2px solid #666;">
                                <td style="border: 1px solid #999; padding: 0.12rem 0.08rem; text-align: center; font-size: 0.82rem; line-height: 1.05; width: 28px; min-width: 28px; max-width: 28px;">&nbsp;</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: left; font-size: 0.82rem; line-height: 1.05; min-width: {{ $primaryColumnWidth }};">TOTAL</td>
                                @unless($hideSecondColumn)
                                    <td style="border: 1px solid #999; padding: 0.12rem 0.12rem; text-align: center; font-size: 0.82rem; line-height: 1.05; @if($secondaryColumnWidth) min-width: {{ $secondaryColumnWidth }}; @endif">&nbsp;</td>
                                @endunless
                                @if($showThirdColumn)
                                    <td style="border: 1px solid #999; padding: 0.12rem 0.12rem; text-align: center; font-size: 0.82rem; line-height: 1.05; min-width: 120px;">&nbsp;</td>
                                @endif
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'registered.m', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'registered.f', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'registered.t', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'absent.m', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'absent.f', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'absent.t', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ number_format((float) data_get($total, 'absent.pct', 0), 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'sat.m', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'sat.f', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'sat.t', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ number_format((float) data_get($total, 'sat.pct', 0), 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'inc.m', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'inc.f', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'inc.t', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ number_format((float) data_get($total, 'inc.pct', 0), 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'grades.a.t', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'grades.b.t', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'grades.c.t', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'pass_ac.m', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'pass_ac.f', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'pass_ac.t', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ number_format((float) data_get($total, 'pass_ac.pct', 0), 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'grades.d.t', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'pass_ad.m', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'pass_ad.f', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'pass_ad.t', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ number_format((float) data_get($total, 'pass_ad.pct', 0), 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ data_get($total, 'grades.e.t', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.12rem; text-align: center; width: {{ $summaryMetricWidth }}; min-width: {{ $summaryMetricWidth }}; max-width: {{ $summaryMetricWidth }}; font-size: 0.8rem; line-height: 1;">{{ is_null($regionalAverage) ? '-' : number_format((float) $regionalAverage, 2) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $summaryMetricWidth }}; min-width: {{ $summaryMetricWidth }}; max-width: {{ $summaryMetricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $regionalAverageGrade ?? '-' }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; font-weight: bold; width: {{ $summaryMetricWidth }}; min-width: {{ $summaryMetricWidth }}; max-width: {{ $summaryMetricWidth }}; font-size: 0.8rem; line-height: 1;">&nbsp;</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div style="display: flex; flex-direction: column; align-items: center; gap: 0.15rem; padding-top: 0.75rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="{{ $barcodeWidth }}" height="34" viewBox="0 0 {{ $barcodeWidth }} 34" role="img" aria-label="Evaluation barcode">
                    @php($barX = 0)
                    @foreach($barcodeBars as $bar)
                        @if($bar['draw'])
                            <rect x="{{ $barX }}" y="0" width="{{ $bar['width'] }}" height="26" fill="#0f172a"></rect>
                        @endif
                        @php($barX += $bar['width'])
                    @endforeach
                </svg>
                <div style="font-size: 0.78rem; color: #0f172a; letter-spacing: 0.04em;">{{ $barcodePayload }}</div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.querySelector('.keyboard-navigable-table');
    if (!table) return;

    table.addEventListener('keydown', function(e) {
        const active = document.activeElement;
        if (!active || active.tagName !== 'TD' || !table.contains(active)) return;

        const key = e.key;
        if (!['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(key)) return;

        const row = active.parentElement;
        if (!row || row.parentElement.tagName !== 'TBODY') return;

        const tbody = row.parentElement;
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const currentRowIndex = rows.indexOf(row);
        if (currentRowIndex === -1) return;

        const cells = Array.from(row.querySelectorAll('td'));
        const currentCellIndex = cells.indexOf(active);
        if (currentCellIndex === -1) return;

        let targetCell = null;

        if (key === 'ArrowLeft') {
            if (currentCellIndex > 0) {
                targetCell = cells[currentCellIndex - 1];
            }
        } else if (key === 'ArrowRight') {
            if (currentCellIndex < cells.length - 1) {
                targetCell = cells[currentCellIndex + 1];
            }
        } else if (key === 'ArrowUp') {
            if (currentRowIndex > 0) {
                const targetRow = rows[currentRowIndex - 1];
                const targetRowCells = Array.from(targetRow.querySelectorAll('td'));
                if (currentCellIndex < targetRowCells.length) {
                    targetCell = targetRowCells[currentCellIndex];
                }
            }
        } else if (key === 'ArrowDown') {
            if (currentRowIndex < rows.length - 1) {
                const targetRow = rows[currentRowIndex + 1];
                const targetRowCells = Array.from(targetRow.querySelectorAll('td'));
                if (currentCellIndex < targetRowCells.length) {
                    targetCell = targetRowCells[currentCellIndex];
                }
            }
        }

        if (targetCell) {
            e.preventDefault();
            targetCell.focus();
            targetCell.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        }
    });
});
</script>
@endsection
