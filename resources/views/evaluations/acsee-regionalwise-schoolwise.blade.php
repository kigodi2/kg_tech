@extends('layout')

@section('content')
@php
    $schoolCount = count($rows ?? []);
    $bestSchool = collect($rows ?? [])->first();
    $isOwnershipEvaluation = ($tableMode ?? null) === 'ownership' || str_contains(strtoupper((string) ($evaluationLabel ?? '')), 'OWNERSHIP');
    $isCouncilwiseEvaluation = ($tableMode ?? null) === 'councilwise' || str_contains(strtoupper((string) ($evaluationLabel ?? '')), 'COUNCIL');
    $isDistrictwiseEvaluation = ($tableMode ?? null) === 'districtwise' || str_contains(strtoupper((string) ($evaluationLabel ?? '')), 'DISTRICT');
    $isGeneralEvaluation = ($tableMode ?? null) === 'general' || trim(strtoupper((string) ($evaluationLabel ?? ''))) === 'GENERAL';
    $isBestTenSchools = str_contains(strtoupper((string) ($evaluationLabel ?? '')), 'BEST TEN');
    $isLeastTenSchools = str_contains(strtoupper((string) ($evaluationLabel ?? '')), 'LEAST TEN');
    $isGovernmentSchools = str_contains(strtoupper((string) ($evaluationLabel ?? '')), 'GOVERNMENT SCHOOLS');
    $isNonGovernmentSchools = str_contains(strtoupper((string) ($evaluationLabel ?? '')), 'NON-GOVERNMENT SCHOOLS');
    $hideSecondColumn = $isCouncilwiseEvaluation || $isDistrictwiseEvaluation || $isGeneralEvaluation;
    $primaryColumnLabel = $isOwnershipEvaluation ? 'Ownership' : ($isGeneralEvaluation ? 'Sex' : ($isDistrictwiseEvaluation ? 'District' : 'Council'));
    $secondaryColumnLabel = $isOwnershipEvaluation ? 'Schools' : 'School';
    $primaryColumnKey = $isOwnershipEvaluation ? 'ownership' : ($isDistrictwiseEvaluation ? 'district' : 'council');
    $secondaryColumnKey = $isOwnershipEvaluation ? 'schools_count' : 'school';
    $secondaryColumnAlign = $isOwnershipEvaluation ? 'text-center' : 'text-left';
    $headerEyebrow = $isOwnershipEvaluation ? 'Regional Ownership Report' : ($isCouncilwiseEvaluation ? 'Regional Council Report' : ($isDistrictwiseEvaluation ? 'Regional District Report' : ($isGeneralEvaluation ? 'Regional General Report' : 'Regional Schoolwise Report')));
    $heroBadge = $isOwnershipEvaluation ? 'Ownership Performance Evaluation' : ($isCouncilwiseEvaluation ? 'Council Performance Evaluation' : ($isDistrictwiseEvaluation ? 'District Performance Evaluation' : ($isGeneralEvaluation ? 'General Performance Evaluation' : 'School Performance Evaluation')));
    $countBadgeUnit = $isOwnershipEvaluation ? 'groups' : ($isCouncilwiseEvaluation ? 'councils' : ($isDistrictwiseEvaluation ? 'districts' : ($isGeneralEvaluation ? 'sex groups' : 'schools')));
    $topItemLabel = data_get($bestSchool ?? [], $primaryColumnKey, 'N/A');

    $heroTitle = 'Review school rankings, participation, divisions, and GPA from one polished workspace.';
    $heroCopy = 'This view presents the regional schoolwise ACSEE evaluation in a cleaner dashboard format while preserving the complete ranking table for detailed review and export.';
    $statsLabel = 'Schools Ranked';
    $positionLabel = 'Top Position';
    $positionCopy = 'Highest-ranked school in the currently displayed regional evaluation.';
    $sectionTitle = 'Regional schoolwise performance';
    $sectionCopy = 'All schools are listed with participation, division outcomes, GPA, and position in a refined wide-table layout.';
    $countCopy = 'Total schools included in the regional schoolwise evaluation ranking.';

    if ($isBestTenSchools) {
        if ($isCouncilwiseEvaluation) {
            $heroTitle = 'Review the top ten councils, participation, divisions, and GPA from one polished workspace.';
            $heroCopy = 'This view presents the top ten councils in the regional ACSEE evaluation in a cleaner dashboard format while preserving the detailed table and PDF export.';
            $statsLabel = 'Top Councils';
            $positionLabel = 'Best Council';
            $positionCopy = 'Best-performing council within the current top ten ranking.';
            $sectionTitle = 'BEST TEN (10) COUNCILS PERFORMANCE';
            $sectionCopy = 'The ten highest-ranked councils are listed with participation, division outcomes, GPA, and position in a refined wide-table layout.';
            $countCopy = 'Total councils included in the current best ten councils ranking.';
        } else {
            $heroTitle = 'Review the top ten schools, participation, divisions, and GPA from one polished workspace.';
            $heroCopy = 'This view presents the top ten schools in the regional ACSEE evaluation in a cleaner dashboard format while preserving the detailed table and PDF export.';
            $statsLabel = 'Top Schools';
            $positionLabel = 'Best Ranked School';
            $positionCopy = 'Best-performing school within the current top ten ranking.';
            $sectionTitle = 'BEST TEN (10) SCHOOLS PERFORMANCE';
            $sectionCopy = 'The ten highest-ranked schools are listed with participation, division outcomes, GPA, and position in a refined wide-table layout.';
            $countCopy = 'Total schools included in the current best ten schools ranking.';
        }
    } elseif ($isLeastTenSchools) {
        if ($isCouncilwiseEvaluation) {
            $heroTitle = 'Review the least ten councils, participation, divisions, and GPA from one polished workspace.';
            $heroCopy = 'This view presents the least ten councils in the regional ACSEE evaluation in a cleaner dashboard format while preserving the detailed table and PDF export.';
            $statsLabel = 'Least Councils';
            $positionLabel = 'Lowest Council';
            $positionCopy = 'Lowest-performing council within the current least ten ranking.';
            $sectionTitle = 'LEAST TEN (10) COUNCILS PERFORMANCE';
            $sectionCopy = 'The ten lowest-ranked councils are listed with participation, division outcomes, GPA, and position in a refined wide-table layout.';
            $countCopy = 'Total councils included in the current least ten councils ranking.';
        } else {
            $heroTitle = 'Review the least ten schools, participation, divisions, and GPA from one polished workspace.';
            $heroCopy = 'This view presents the least ten schools in the regional ACSEE evaluation in a cleaner dashboard format while preserving the detailed table and PDF export.';
            $statsLabel = 'Least Schools';
            $positionLabel = 'Lowest Ranked School';
            $positionCopy = 'Lowest-performing school within the current least ten ranking.';
            $sectionTitle = 'LEAST TEN (10) SCHOOLS PERFORMANCE';
            $sectionCopy = 'The ten lowest-ranked schools are listed with participation, division outcomes, GPA, and position in a refined wide-table layout.';
            $countCopy = 'Total schools included in the current least ten schools ranking.';
        }
    } elseif ($isGovernmentSchools) {
        $heroTitle = 'Review government schools participation, divisions, and GPA from one polished workspace.';
        $heroCopy = 'This view presents government schools in the regional ACSEE evaluation in a cleaner dashboard format while preserving the detailed table and PDF export.';
        $statsLabel = 'Govt Schools';
        $positionLabel = 'Best Govt School';
        $positionCopy = 'Best-performing government school in the current regional evaluation.';
        $sectionTitle = 'GOVERNMENT SCHOOLS PERFORMANCE';
        $sectionCopy = 'Government schools are listed with participation, division outcomes, GPA, and position in a refined wide-table layout.';
        $countCopy = 'Total government schools included in the current regional evaluation.';
    } elseif ($isNonGovernmentSchools) {
        $heroTitle = 'Review non-government schools participation, divisions, and GPA from one polished workspace.';
        $heroCopy = 'This view presents non-government schools in the regional ACSEE evaluation in a cleaner dashboard format while preserving the detailed table and PDF export.';
        $statsLabel = 'Non-Govt Schools';
        $positionLabel = 'Best Non-Govt School';
        $positionCopy = 'Best-performing non-government school in the current regional evaluation.';
        $sectionTitle = 'NON-GOVERNMENT SCHOOLS PERFORMANCE';
        $sectionCopy = 'Non-government schools are listed with participation, division outcomes, GPA, and position in a refined wide-table layout.';
        $countCopy = 'Total non-government schools included in the current regional evaluation.';
    } elseif ($isGeneralEvaluation) {
        $heroTitle = 'Review sex-based participation, divisions, and GPA from one polished workspace.';
        $heroCopy = 'This view presents the regional general ACSEE evaluation in the same detailed structure as the schoolwise report while grouping candidates by sex.';
        $statsLabel = 'Sex Groups';
        $positionLabel = 'Best Group';
        $positionCopy = 'Best-performing sex group in the current regional evaluation.';
        $sectionTitle = 'REGIONAL GENERAL PERFORMANCE';
        $sectionCopy = 'Sex groups are listed with participation, division outcomes, GPA, and position in the same refined wide-table layout.';
        $countCopy = 'Total sex groups included in the current regional evaluation.';
    } elseif ($isDistrictwiseEvaluation) {
        $heroTitle = 'Review district rankings, participation, divisions, and GPA from one polished workspace.';
        $heroCopy = 'This view presents the regional district ACSEE evaluation in the same detailed structure as the councilwise report while using district as the lead grouping column.';
        $statsLabel = 'Districts Ranked';
        $positionLabel = 'Top District';
        $positionCopy = 'Highest-ranked district in the current regional evaluation.';
        $sectionTitle = 'REGIONAL DISTRICT PERFORMANCE';
        $sectionCopy = 'Districts are listed with participation, division outcomes, GPA, and position in the same refined wide-table layout.';
        $countCopy = 'Total districts included in the current regional evaluation ranking.';
    } elseif ($isCouncilwiseEvaluation) {
        $heroTitle = 'Review council rankings, participation, divisions, and GPA from one polished workspace.';
        $heroCopy = 'This view presents the regional council ACSEE evaluation in the same detailed structure as the schoolwise report while removing the school column.';
        $statsLabel = 'Councils Ranked';
        $positionLabel = 'Top Council';
        $positionCopy = 'Highest-ranked council in the current regional evaluation.';
        $sectionTitle = 'REGIONAL COUNCIL PERFORMANCE';
        $sectionCopy = 'Councils are listed with participation, division outcomes, GPA, and position in the same refined wide-table layout.';
        $countCopy = 'Total councils included in the current regional evaluation ranking.';
    } elseif ($isOwnershipEvaluation) {
        $heroTitle = 'Review ownership performance, schools count, participation, divisions, and GPA from one polished workspace.';
        $heroCopy = 'This view presents the regional ownership ACSEE evaluation in the same detailed structure as the schoolwise report while replacing council with ownership and school with schools count.';
        $statsLabel = 'Ownership Groups';
        $positionLabel = 'Best Ownership';
        $positionCopy = 'Best-performing ownership group in the current regional evaluation.';
        $sectionTitle = 'OWNERSHIP RESULT EVALUATION';
        $sectionCopy = 'Ownership groups are listed with schools count, participation, division outcomes, GPA, and position in the same refined wide-table layout.';
        $countCopy = 'Total ownership groups included in the current regional evaluation.';
    }
@endphp

<div class="min-h-screen bg-slate-50" style="font-family: 'Maiandra GD', sans-serif;">
    <div class="border-b border-slate-200 bg-white/95 backdrop-blur sticky top-0 z-30 shadow-sm">
        <div class="mx-auto max-w-[96rem] px-6 py-5 lg:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.24em] text-blue-700">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        {{ $headerEyebrow }}
                    </div>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-900 lg:text-4xl">{{ $evaluationLabel }}</h1>
                    <p class="mt-2 text-sm text-slate-600 lg:text-base">
                        {{ strtoupper($region->name) }} · February {{ $examYearValue }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('evaluations.acsee.regionalwise.region', ['region' => $region->id]) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                        <span>&larr;</span>
                        <span>Back to {{ strtoupper($region->name) }}</span>
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-700/20 transition hover:-translate-y-0.5 hover:bg-emerald-800">
                        <span>Export PDF</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-[96rem] px-6 py-8 lg:px-8">
        <section class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-blue-950 to-indigo-900 px-6 py-8 text-white shadow-2xl shadow-blue-950/20 lg:px-10 lg:py-10">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(96,165,250,0.28),_transparent_34%),radial-gradient(circle_at_bottom_left,_rgba(52,211,153,0.18),_transparent_26%)]"></div>
            <div class="relative grid gap-8 lg:grid-cols-[1.35fr_0.9fr] lg:items-center">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] text-blue-100">
                        {{ $heroBadge }}
                    </div>
                    <h2 class="mt-5 max-w-4xl text-3xl font-black leading-tight lg:text-5xl">
                        {{ $heroTitle }}
                    </h2>
                    <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-200 lg:text-base">
                        {{ $heroCopy }}
                    </p>
                </div>

                <div class="grid gap-4">
                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5 backdrop-blur-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-100">{{ $statsLabel }}</p>
                        <h3 class="mt-2 text-3xl font-black">{{ $schoolCount }}</h3>
                        <p class="mt-2 text-sm text-slate-200">{{ $countCopy }}</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5 backdrop-blur-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-emerald-100">{{ $positionLabel }}</p>
                        <h3 class="mt-2 text-2xl font-black">{{ $topItemLabel }}</h3>
                        <p class="mt-2 text-sm text-slate-200">{{ $positionCopy }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Registered</span>
                    <span class="rounded-2xl bg-blue-50 p-3 text-blue-600"><i class="fas fa-user-graduate"></i></span>
                </div>
                <p class="mt-4 text-4xl font-black text-slate-900">{{ $total['registered']['t'] }}</p>
                <p class="mt-2 text-sm text-slate-600">Total registered candidates across all ranked schools.</p>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Sat</span>
                    <span class="rounded-2xl bg-emerald-50 p-3 text-emerald-600"><i class="fas fa-clipboard-check"></i></span>
                </div>
                <p class="mt-4 text-4xl font-black text-slate-900">{{ $total['sat']['t'] }}</p>
                <p class="mt-2 text-sm text-slate-600">{{ number_format($total['sat']['pct'], 1) }}% of registered candidates sat for the exams.</p>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Absent</span>
                    <span class="rounded-2xl bg-amber-50 p-3 text-amber-600"><i class="fas fa-user-clock"></i></span>
                </div>
                <p class="mt-4 text-4xl font-black text-slate-900">{{ $total['absent']['t'] }}</p>
                <p class="mt-2 text-sm text-slate-600">{{ number_format($total['absent']['pct'], 1) }}% absentee rate in this regional ranking.</p>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Division I - IV</span>
                    <span class="rounded-2xl bg-violet-50 p-3 text-violet-600"><i class="fas fa-chart-line"></i></span>
                </div>
                <p class="mt-4 text-4xl font-black text-slate-900">{{ $total['division']['i_iv']['t'] }}</p>
                <p class="mt-2 text-sm text-slate-600">{{ number_format($total['division']['i_iv']['pct'], 1) }}% attained Division I to IV among candidates who sat.</p>
            </div>
        </section>

        <section class="mt-8 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70 lg:p-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.25em] text-blue-700">Detailed {{ $isOwnershipEvaluation ? 'Ownership' : 'Schoolwise' }} Table</p>
                    <h3 class="mt-2 text-2xl font-black text-slate-900">{{ $sectionTitle }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ $sectionCopy }}</p>
                </div>
                <span class="rounded-2xl bg-slate-100 px-3 py-2 text-xs font-bold uppercase tracking-[0.18em] text-slate-700">
                    {{ $schoolCount }} {{ $countBadgeUnit }}
                </span>
            </div>

            <div class="mt-6 overflow-hidden rounded-[1.5rem] border border-slate-400">
                <div class="overflow-x-auto bg-slate-950/95">
                    <table class="min-w-[1800px] w-full border-collapse whitespace-nowrap text-sm text-slate-700">
                        <thead>
                            <tr class="bg-amber-100 text-slate-900">
                                <th rowspan="3" class="border border-slate-500 px-3 py-3 text-center text-xs font-black uppercase tracking-[0.16em]">S/N</th>
                                <th rowspan="3" class="border border-slate-500 px-3 py-3 text-left text-xs font-black uppercase tracking-[0.16em]">{{ $primaryColumnLabel }}</th>
                                @unless($hideSecondColumn)
                                    <th rowspan="3" class="border border-slate-500 px-3 py-3 {{ $isOwnershipEvaluation ? 'text-center' : 'text-left' }} text-xs font-black uppercase tracking-[0.16em]">{{ $secondaryColumnLabel }}</th>
                                @endunless
                                <th colspan="3" class="border border-slate-500 px-3 py-3 text-xs font-black uppercase tracking-[0.16em]">Registered</th>
                                <th colspan="4" class="border border-slate-500 px-3 py-3 text-xs font-black uppercase tracking-[0.16em]">Absent</th>
                                <th colspan="4" class="border border-slate-500 px-3 py-3 text-xs font-black uppercase tracking-[0.16em]">Sat</th>
                                <th colspan="4" class="border border-slate-500 px-3 py-3 text-xs font-black uppercase tracking-[0.16em]">Inc</th>
                                <th colspan="13" class="border border-slate-500 px-3 py-3 text-xs font-black uppercase tracking-[0.16em]">Division</th>
                                <th rowspan="3" class="border border-slate-500 px-3 py-3 text-xs font-black uppercase tracking-[0.16em]">GPA</th>
                                <th rowspan="3" class="border border-slate-500 px-3 py-3 text-xs font-black uppercase tracking-[0.16em]">Pos</th>
                            </tr>
                            <tr class="bg-amber-50 text-slate-900">
                                <th rowspan="2" class="border border-slate-500 px-2 py-2 text-xs font-bold">M</th>
                                <th rowspan="2" class="border border-slate-500 px-2 py-2 text-xs font-bold">F</th>
                                <th rowspan="2" class="border border-slate-500 px-2 py-2 text-xs font-bold">T</th>
                                <th rowspan="2" class="border border-slate-500 px-2 py-2 text-xs font-bold">M</th>
                                <th rowspan="2" class="border border-slate-500 px-2 py-2 text-xs font-bold">F</th>
                                <th rowspan="2" class="border border-slate-500 px-2 py-2 text-xs font-bold">T</th>
                                <th rowspan="2" class="border border-slate-500 px-2 py-2 text-xs font-bold">%</th>
                                <th rowspan="2" class="border border-slate-500 px-2 py-2 text-xs font-bold">M</th>
                                <th rowspan="2" class="border border-slate-500 px-2 py-2 text-xs font-bold">F</th>
                                <th rowspan="2" class="border border-slate-500 px-2 py-2 text-xs font-bold">T</th>
                                <th rowspan="2" class="border border-slate-500 px-2 py-2 text-xs font-bold">%</th>
                                <th rowspan="2" class="border border-slate-500 px-2 py-2 text-xs font-bold">M</th>
                                <th rowspan="2" class="border border-slate-500 px-2 py-2 text-xs font-bold">F</th>
                                <th rowspan="2" class="border border-slate-500 px-2 py-2 text-xs font-bold">T</th>
                                <th rowspan="2" class="border border-slate-500 px-2 py-2 text-xs font-bold">%</th>
                                <th rowspan="2" class="border border-slate-500 px-2 py-2 text-xs font-bold">I</th>
                                <th rowspan="2" class="border border-slate-500 px-2 py-2 text-xs font-bold">II</th>
                                <th rowspan="2" class="border border-slate-500 px-2 py-2 text-xs font-bold">III</th>
                                <th colspan="4" class="border border-slate-500 px-2 py-2 text-xs font-bold">I - III</th>
                                <th rowspan="2" class="border border-slate-500 px-2 py-2 text-xs font-bold">IV</th>
                                <th colspan="4" class="border border-slate-500 px-2 py-2 text-xs font-bold">I - IV</th>
                                <th rowspan="2" class="border border-slate-500 px-2 py-2 text-xs font-bold">0</th>
                            </tr>
                            <tr class="bg-white text-slate-800">
                                @for($i = 0; $i < 2; $i++)
                                    <th class="border border-slate-500 px-2 py-2 text-xs font-bold">M</th>
                                    <th class="border border-slate-500 px-2 py-2 text-xs font-bold">F</th>
                                    <th class="border border-slate-500 px-2 py-2 text-xs font-bold">T</th>
                                    <th class="border border-slate-500 px-2 py-2 text-xs font-bold">%</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            @foreach($rows as $index => $row)
                                <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-slate-50' }} hover:bg-slate-50 transition-colors">
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $index + 1 }}</td>
                                    <td class="border border-slate-300 px-3 py-3 text-left text-slate-800">{{ $row[$primaryColumnKey] }}</td>
                                    @unless($hideSecondColumn)
                                        <td class="border border-slate-300 px-3 py-3 {{ $secondaryColumnAlign }} text-slate-900">{{ $row[$secondaryColumnKey] }}</td>
                                    @endunless
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['registered']['m'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['registered']['f'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['registered']['t'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['absent']['m'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['absent']['f'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['absent']['t'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ number_format($row['absent']['pct'], 0) }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['sat']['m'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['sat']['f'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['sat']['t'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ number_format($row['sat']['pct'], 0) }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['inc']['m'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['inc']['f'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['inc']['t'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ number_format($row['inc']['pct'], 0) }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['division']['i']['t'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['division']['ii']['t'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['division']['iii']['t'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['division']['i_iii']['m'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['division']['i_iii']['f'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['division']['i_iii']['t'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ number_format($row['division']['i_iii']['pct'], 0) }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['division']['iv']['t'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['division']['i_iv']['m'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['division']['i_iv']['f'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['division']['i_iv']['t'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ number_format($row['division']['i_iv']['pct'], 0) }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['division']['zero']['t'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center font-bold text-slate-900">{{ is_null($row['gpa']) ? '-' : number_format($row['gpa'], 4) }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center font-bold text-blue-700">{{ $row['pos'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-amber-100 text-slate-900">
                            <tr>
                                <td colspan="{{ $hideSecondColumn ? 2 : 3 }}" class="border border-slate-500 px-3 py-3 text-center text-xs font-black uppercase tracking-[0.16em]">Total</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['registered']['m'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['registered']['f'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['registered']['t'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['absent']['m'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['absent']['f'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['absent']['t'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ number_format($total['absent']['pct'], 1) }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['sat']['m'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['sat']['f'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['sat']['t'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ number_format($total['sat']['pct'], 1) }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['inc']['m'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['inc']['f'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['inc']['t'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ number_format($total['inc']['pct'], 1) }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['division']['i']['t'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['division']['ii']['t'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['division']['iii']['t'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['division']['i_iii']['m'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['division']['i_iii']['f'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['division']['i_iii']['t'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ number_format($total['division']['i_iii']['pct'], 2) }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['division']['iv']['t'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['division']['i_iv']['m'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['division']['i_iv']['f'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['division']['i_iv']['t'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ number_format($total['division']['i_iv']['pct'], 2) }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $total['division']['zero']['t'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">-</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">-</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
