@extends('layout')

@section('content')
@php
    $studentCount = count($rows ?? []);
    $topStudent = collect($rows ?? [])->first();
    $heroTitle = 'Review the ranked student list, school placement, GPA, and division from one polished workspace.';
    $heroCopy = 'This view presents the selected student ranking evaluation in a cleaner dashboard format while preserving the detailed table and PDF export.';
    $topLabel = 'Top Student';
    $topCopy = $topStudent['school'] ?? 'No school available';
    $sectionCopy = 'Ranked students are listed in the same detailed report structure used in centre reports.';

    switch ($evaluationKey ?? '') {
        case 'best-ten-girls':
            $heroTitle = 'Review the ten highest-ranked girls with the same detailed report structure used in official centre reports.';
            $heroCopy = 'This ranking highlights the strongest female performance in the region using detailed subject results, totals, averages, AGGT, GPA, and position.';
            $topLabel = 'Best Ranked Girl';
            $sectionCopy = 'The ten highest-ranked girls are listed using the same detailed report structure used in centre reports.';
            break;
        case 'least-ten-girls':
            $heroTitle = 'Review the ten lowest-ranked girls with the same detailed report structure used in official centre reports.';
            $heroCopy = 'This ranking highlights the lowest female performance in the region using detailed subject results, totals, averages, AGGT, GPA, and position.';
            $topLabel = 'Lowest Ranked Girl';
            $sectionCopy = 'The ten lowest-ranked girls are listed using the same detailed report structure used in centre reports.';
            break;
        case 'best-ten-boys':
            $heroTitle = 'Review the ten highest-ranked boys with the same detailed report structure used in official centre reports.';
            $heroCopy = 'This ranking highlights the strongest male performance in the region using detailed subject results, totals, averages, AGGT, GPA, and position.';
            $topLabel = 'Best Ranked Boy';
            $sectionCopy = 'The ten highest-ranked boys are listed using the same detailed report structure used in centre reports.';
            break;
        case 'least-ten-boys':
            $heroTitle = 'Review the ten lowest-ranked boys with the same detailed report structure used in official centre reports.';
            $heroCopy = 'This ranking highlights the lowest male performance in the region using detailed subject results, totals, averages, AGGT, GPA, and position.';
            $topLabel = 'Lowest Ranked Boy';
            $sectionCopy = 'The ten lowest-ranked boys are listed using the same detailed report structure used in centre reports.';
            break;
        case 'overall-best-ten-students':
            $heroTitle = 'Review the ten highest-ranked students with the same detailed report structure used in official centre reports.';
            $heroCopy = 'This ranking highlights the strongest overall performance in the region using detailed subject results, totals, averages, AGGT, GPA, and position.';
            $topLabel = 'Best Ranked Student';
            $sectionCopy = 'The ten highest-ranked students are listed using the same detailed report structure used in centre reports.';
            break;
        case 'overall-least-ten-students':
            $heroTitle = 'Review the ten lowest-ranked students with the same detailed report structure used in official centre reports.';
            $heroCopy = 'This ranking highlights the lowest overall performance in the region using detailed subject results, totals, averages, AGGT, GPA, and position.';
            $topLabel = 'Lowest Ranked Student';
            $sectionCopy = 'The ten lowest-ranked students are listed using the same detailed report structure used in centre reports.';
            break;
    }
@endphp

<div class="min-h-screen bg-slate-50" style="font-family: 'Maiandra GD', sans-serif;">
    <div class="border-b border-slate-200 bg-white/95 backdrop-blur sticky top-0 z-30 shadow-sm">
        <div class="mx-auto max-w-[96rem] px-6 py-5 lg:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.24em] text-blue-700">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        Regional Student Ranking
                    </div>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-900 lg:text-4xl">{{ $evaluationLabel }}</h1>
                    <p class="mt-2 text-sm text-slate-600 lg:text-base">{{ strtoupper($region->name) }} · February {{ $examYearValue }}</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('evaluations.acsee.regionalwise.region', ['region' => $region->id]) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"><span>&larr;</span><span>Back to {{ strtoupper($region->name) }}</span></a>
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-700/20 transition hover:-translate-y-0.5 hover:bg-emerald-800"><span>Export PDF</span><span>&rarr;</span></a>
                </div>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-[96rem] px-6 py-8 lg:px-8">
        <section class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-blue-950 to-indigo-900 px-6 py-8 text-white shadow-2xl shadow-blue-950/20 lg:px-10 lg:py-10">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(96,165,250,0.28),_transparent_34%),radial-gradient(circle_at_bottom_left,_rgba(52,211,153,0.18),_transparent_26%)]"></div>
            <div class="relative grid gap-8 lg:grid-cols-[1.35fr_0.9fr] lg:items-center">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] text-blue-100">Student Performance Evaluation</div>
                    <h2 class="mt-5 max-w-4xl text-3xl font-black leading-tight lg:text-5xl">{{ $heroTitle }}</h2>
                    <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-200 lg:text-base">{{ $heroCopy }}</p>
                </div>
                <div class="grid gap-4">
                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5 backdrop-blur-sm"><p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-100">Students Listed</p><h3 class="mt-2 text-3xl font-black">{{ $studentCount }}</h3><p class="mt-2 text-sm text-slate-200">Total ranked students included in the current evaluation.</p></div>
                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5 backdrop-blur-sm"><p class="text-xs font-semibold uppercase tracking-[0.25em] text-emerald-100">{{ $topLabel }}</p><h3 class="mt-2 text-2xl font-black">{{ $topStudent['candidate'] ?? 'N/A' }}</h3><p class="mt-2 text-sm text-slate-200">{{ $topCopy }}</p></div>
                </div>
            </div>
        </section>

        <section class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70"><span class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Students</span><p class="mt-4 text-4xl font-black text-slate-900">{{ $summary['students'] }}</p></div>
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70"><span class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Average GPA</span><p class="mt-4 text-4xl font-black text-slate-900">{{ $summary['avg_gpa'] }}</p></div>
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70"><span class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Best GPA</span><p class="mt-4 text-4xl font-black text-slate-900">{{ $summary['best_gpa'] }}</p></div>
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70"><span class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Sex</span><p class="mt-4 text-4xl font-black text-slate-900">{{ $summary['sex'] }}</p></div>
        </section>

        <section class="mt-8 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70 lg:p-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.25em] text-blue-700">Detailed Student Table</p>
                    <h3 class="mt-2 text-2xl font-black text-slate-900">{{ $evaluationLabel }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ $sectionCopy }}</p>
                </div>
                <span class="rounded-2xl bg-slate-100 px-3 py-2 text-xs font-bold uppercase tracking-[0.18em] text-slate-700">{{ $studentCount }} students</span>
            </div>
            <div class="mt-6 overflow-hidden rounded-[1.5rem] border border-slate-400">
                <div class="overflow-x-auto bg-slate-950/95">
                    <table class="min-w-[1900px] w-full border-collapse whitespace-nowrap text-sm text-slate-700">
                        <thead>
                            <tr class="bg-[#003366] text-white">
                                <th class="border border-slate-500 px-3 py-3 text-left text-xs font-black uppercase tracking-[0.16em]">Council</th>
                                <th class="border border-slate-500 px-3 py-3 text-center text-xs font-black uppercase tracking-[0.16em]">CNO</th>
                                <th class="border border-slate-500 px-3 py-3 text-left text-xs font-black uppercase tracking-[0.16em]">School</th>
                                <th class="border border-slate-500 px-3 py-3 text-center text-xs font-black uppercase tracking-[0.16em]">Sex</th>
                                <th class="border border-slate-500 px-3 py-3 text-center text-xs font-black uppercase tracking-[0.16em]">Comb</th>
                                <th class="border border-slate-500 px-3 py-3 text-left text-xs font-black uppercase tracking-[0.16em]">Detailed Subjects Result</th>
                                <th class="border border-slate-500 px-3 py-3 text-center text-xs font-black uppercase tracking-[0.16em]">Total</th>
                                <th class="border border-slate-500 px-3 py-3 text-center text-xs font-black uppercase tracking-[0.16em]">Avg</th>
                                <th class="border border-slate-500 px-3 py-3 text-center text-xs font-black uppercase tracking-[0.16em]">Grd</th>
                                <th class="border border-slate-500 px-3 py-3 text-center text-xs font-black uppercase tracking-[0.16em]">Aggt</th>
                                <th class="border border-slate-500 px-3 py-3 text-center text-xs font-black uppercase tracking-[0.16em]">Div</th>
                                <th class="border border-slate-500 px-3 py-3 text-center text-xs font-black uppercase tracking-[0.16em]">GPA</th>
                                <th class="border border-slate-500 px-3 py-3 text-center text-xs font-black uppercase tracking-[0.16em]">Pos</th>
                            </tr>
                        </thead>
                        <tbody class="bg-[#fffde7]">
                            @foreach($rows as $index => $row)
                                <tr class="{{ $index % 2 === 0 ? 'bg-[#fffde7]' : 'bg-[#fff9c4]' }}">
                                    <td class="border border-slate-600 px-3 py-2 text-left text-slate-900 whitespace-nowrap">{{ $row['council'] }}</td>
                                    <td class="border border-slate-600 px-3 py-2 text-center text-slate-900 whitespace-nowrap">{{ $row['index_number'] }}</td>
                                    <td class="border border-slate-600 px-3 py-2 text-left text-slate-900 whitespace-nowrap">{{ $row['school'] }}</td>
                                    <td class="border border-slate-600 px-2 py-2 text-center text-slate-900 whitespace-nowrap">{{ $row['sex'] }}</td>
                                    <td class="border border-slate-600 px-2 py-2 text-center font-bold text-slate-900 whitespace-nowrap">{{ $row['combination'] }}</td>
                                    <td class="border border-slate-600 px-3 py-2 text-left text-slate-900 whitespace-nowrap">{{ $row['subject_results_text'] ?: '-' }}</td>
                                    <td class="border border-slate-600 px-2 py-2 text-center text-slate-900 whitespace-nowrap">{{ is_null($row['total_marks']) ? '-' : number_format($row['total_marks'], 0) }}</td>
                                    <td class="border border-slate-600 px-2 py-2 text-center text-slate-900 whitespace-nowrap">{{ is_null($row['avg_marks']) ? '-' : number_format($row['avg_marks'], 2) }}</td>
                                    <td class="border border-slate-600 px-2 py-2 text-center font-bold text-slate-900 whitespace-nowrap">{{ $row['overall_grade'] }}</td>
                                    <td class="border border-slate-600 px-2 py-2 text-center text-slate-900 whitespace-nowrap">{{ is_null($row['aggt']) ? '-' : $row['aggt'] }}</td>
                                    <td class="border border-slate-600 px-2 py-2 text-center font-bold text-slate-900 whitespace-nowrap">{{ $row['division'] }}</td>
                                    <td class="border border-slate-600 px-2 py-2 text-center font-bold text-slate-900 whitespace-nowrap">{{ number_format($row['gpa'], 4) }}</td>
                                    <td class="border border-slate-600 px-2 py-2 text-center font-bold text-blue-700 whitespace-nowrap">{{ $row['position'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
