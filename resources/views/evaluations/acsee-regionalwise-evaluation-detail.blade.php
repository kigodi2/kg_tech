@extends('layout')

@section('content')
@php
    $summaryItems = collect($summary ?? []);
    $tableColumns = $columns ?? [];
    $tableRows = $rows ?? [];
@endphp

<div class="min-h-screen bg-slate-50" style="font-family: 'Maiandra GD', sans-serif;">
    <div class="border-b border-slate-200 bg-white/95 backdrop-blur sticky top-0 z-30 shadow-sm">
        <div class="mx-auto max-w-7xl px-6 py-5 lg:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.24em] text-blue-700">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        Regional Evaluation Detail
                    </div>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-900 lg:text-4xl">{{ $evaluationLabel }}</h1>
                    <p class="mt-2 text-sm text-slate-600 lg:text-base">
                        Region: <span class="font-bold text-slate-800">{{ strtoupper($region->name) }}</span>
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('evaluations.acsee.regionalwise.region', ['region' => $region->id]) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                        <span>&larr;</span>
                        <span>Back to {{ strtoupper($region->name) }}</span>
                    </a>
                    <a href="{{ route('evaluations.acsee.regionalwise') }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-900/15 transition hover:-translate-y-0.5 hover:bg-slate-800">
                        <span>All Regions</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-6 py-8 lg:px-8">
        <section class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-blue-950 to-indigo-900 px-6 py-8 text-white shadow-2xl shadow-blue-950/20 lg:px-10 lg:py-10">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(96,165,250,0.28),_transparent_34%),radial-gradient(circle_at_bottom_left,_rgba(52,211,153,0.18),_transparent_26%)]"></div>
            <div class="relative grid gap-8 lg:grid-cols-[1.3fr_0.9fr] lg:items-center">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] text-blue-100">
                        Detailed Regional Report
                    </div>
                    <h2 class="mt-5 max-w-3xl text-3xl font-black leading-tight lg:text-5xl">
                        Executive view for {{ strtoupper($region->name) }} {{ strtolower($evaluationLabel) }}.
                    </h2>
                    <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-200 lg:text-base">
                        Review the summary snapshot first, then scan the detailed dataset below through a cleaner, easier-to-read reporting layout.
                    </p>
                </div>

                <div class="grid gap-4">
                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5 backdrop-blur-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-100">Summary Fields</p>
                        <h3 class="mt-2 text-3xl font-black">{{ $summaryItems->count() }}</h3>
                        <p class="mt-2 text-sm text-slate-200">Key report indicators arranged into a clearer management snapshot.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5 backdrop-blur-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-emerald-100">Detailed Records</p>
                        <h3 class="mt-2 text-3xl font-black">{{ count($tableRows) }}</h3>
                        <p class="mt-2 text-sm text-slate-200">Rows of evaluation data displayed in a refined, responsive table.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Region</span>
                    <span class="rounded-2xl bg-blue-50 p-3 text-blue-600"><i class="fas fa-map"></i></span>
                </div>
                <p class="mt-4 text-2xl font-black text-slate-900">{{ strtoupper($region->name) }}</p>
                <p class="mt-2 text-sm text-slate-600">Current regional scope for this evaluation report.</p>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Evaluation</span>
                    <span class="rounded-2xl bg-emerald-50 p-3 text-emerald-600"><i class="fas fa-chart-line"></i></span>
                </div>
                <p class="mt-4 text-2xl font-black text-slate-900">{{ $evaluationLabel }}</p>
                <p class="mt-2 text-sm text-slate-600">Selected ACSEE evaluation category being reviewed.</p>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Columns</span>
                    <span class="rounded-2xl bg-amber-50 p-3 text-amber-600"><i class="fas fa-table-columns"></i></span>
                </div>
                <p class="mt-4 text-4xl font-black text-slate-900">{{ count($tableColumns) }}</p>
                <p class="mt-2 text-sm text-slate-600">Visible fields in the detailed report table.</p>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Records</span>
                    <span class="rounded-2xl bg-violet-50 p-3 text-violet-600"><i class="fas fa-database"></i></span>
                </div>
                <p class="mt-4 text-4xl font-black text-slate-900">{{ count($tableRows) }}</p>
                <p class="mt-2 text-sm text-slate-600">Detailed entries available for this specific report.</p>
            </div>
        </section>

        <section class="mt-8 grid gap-6 xl:grid-cols-[0.95fr_1.45fr]">
            <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70 lg:p-8">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-blue-700">Report Summary</p>
                        <h3 class="mt-2 text-2xl font-black text-slate-900">Snapshot overview</h3>
                    </div>
                    <span class="rounded-2xl bg-blue-50 px-3 py-2 text-xs font-bold uppercase tracking-[0.18em] text-blue-700">
                        {{ $summaryItems->count() }} fields
                    </span>
                </div>

                @if($summaryItems->isNotEmpty())
                    <div class="mt-6 space-y-3">
                        @foreach($summaryItems as $label => $value)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">{{ $label }}</p>
                                <p class="mt-2 text-base font-bold text-slate-900">{{ $value }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                        No summary details are available for this report.
                    </div>
                @endif
            </div>

            <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70 lg:p-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-emerald-700">Detailed Data</p>
                        <h3 class="mt-2 text-2xl font-black text-slate-900">Report table</h3>
                        <p class="mt-2 text-sm text-slate-600">A clearer and more professional tabular presentation for the underlying evaluation records.</p>
                    </div>
                    <span class="rounded-2xl bg-emerald-50 px-3 py-2 text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">
                        {{ count($tableRows) }} rows
                    </span>
                </div>

                <div class="mt-6 overflow-hidden rounded-[1.5rem] border border-slate-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-900 text-white">
                                <tr>
                                    <th class="whitespace-nowrap px-4 py-4 text-left text-xs font-bold uppercase tracking-[0.18em]">S/N</th>
                                    @foreach($tableColumns as $column)
                                        <th class="whitespace-nowrap px-4 py-4 text-left text-xs font-bold uppercase tracking-[0.18em]">{{ $column }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white text-sm text-slate-700">
                                @forelse($tableRows as $index => $row)
                                    <tr class="transition hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-4 py-4 font-bold text-slate-900">{{ $index + 1 }}</td>
                                        @foreach(($row['cells'] ?? []) as $idx => $cell)
                                            <td class="px-4 py-4 align-top">
                                                @if($idx === 0 && !empty($row['first_cell_url']))
                                                    <a href="{{ $row['first_cell_url'] }}" class="font-semibold text-blue-700 transition hover:text-blue-900 hover:underline">{{ $cell }}</a>
                                                @else
                                                    {{ $cell }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ max(count($tableColumns), 1) + 1 }}" class="px-4 py-10 text-center text-sm text-slate-500">
                                            No records found for this evaluation and region.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
