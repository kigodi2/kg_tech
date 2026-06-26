@extends('layout')

@section('content')
@php
    $rowCount = count($rows ?? []);
    $topSubject = collect($rows ?? [])->sortByDesc('completion')->first();
@endphp

<div class="min-h-screen bg-slate-50" style="font-family: 'Maiandra GD', sans-serif;">
    <div class="border-b border-slate-200 bg-white/95 backdrop-blur sticky top-0 z-30 shadow-sm">
        <div class="mx-auto max-w-[96rem] px-6 py-5 lg:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.24em] text-blue-700">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        Regional Mark Entry Report
                    </div>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-900 lg:text-4xl">{{ $evaluationLabel }}</h1>
                    <p class="mt-2 text-sm text-slate-600 lg:text-base">{{ strtoupper($region->name) }} · February {{ $examYearValue }}</p>
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
                        Mark Entry Status Evaluation
                    </div>
                    <h2 class="mt-5 max-w-4xl text-3xl font-black leading-tight lg:text-5xl">
                        Review subject mark-entry coverage, pending workload, and completion status from one polished workspace.
                    </h2>
                    <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-200 lg:text-base">
                        This view presents the regional mark entry status in a clearer management format while preserving the detailed subject tracking table and PDF export.
                    </p>
                </div>

                <div class="grid gap-4">
                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5 backdrop-blur-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-100">Subjects Tracked</p>
                        <h3 class="mt-2 text-3xl font-black">{{ $rowCount }}</h3>
                        <p class="mt-2 text-sm text-slate-200">Total subjects included in the current mark entry status report.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5 backdrop-blur-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-emerald-100">Best Completion</p>
                        <h3 class="mt-2 text-2xl font-black">{{ $topSubject ? ($topSubject['subject'] ?? 'N/A') : 'N/A' }}</h3>
                        <p class="mt-2 text-sm text-slate-200">{{ ($topSubject && isset($topSubject['completion'])) ? number_format($topSubject['completion'], 1) . '%' : '-' }} completion in the current report.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70">
                <span class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Expected Entries</span>
                <p class="mt-4 text-4xl font-black text-slate-900">{{ $summary['expected_entries'] }}</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70">
                <span class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Marked Entries</span>
                <p class="mt-4 text-4xl font-black text-slate-900">{{ $summary['marked_entries'] }}</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70">
                <span class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Pending</span>
                <p class="mt-4 text-4xl font-black text-slate-900">{{ $summary['pending_entries'] }}</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70">
                <span class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Completion</span>
                <p class="mt-4 text-4xl font-black text-slate-900">{{ $summary['completion'] }}%</p>
            </div>
        </section>

        <section class="mt-8 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70 lg:p-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.25em] text-blue-700">Detailed Mark Entry Table</p>
                    <h3 class="mt-2 text-2xl font-black text-slate-900">Subject mark entry status</h3>
                    <p class="mt-2 text-sm text-slate-600">Subjects are listed with expected entries, marked entries, pending workload, completion rate, and status.</p>
                </div>
                <span class="rounded-2xl bg-slate-100 px-3 py-2 text-xs font-bold uppercase tracking-[0.18em] text-slate-700">{{ $rowCount }} subjects</span>
            </div>

            <div class="mt-6 overflow-hidden rounded-[1.5rem] border border-slate-400">
                <div class="overflow-x-auto bg-slate-950/95">
                    <table class="min-w-[1300px] w-full border-collapse whitespace-nowrap text-sm text-slate-700">
                        <thead>
                            <tr class="bg-amber-100 text-slate-900">
                                <th class="border border-slate-500 px-3 py-3 text-center text-xs font-black uppercase tracking-[0.16em]">S/N</th>
                                <th class="border border-slate-500 px-3 py-3 text-left text-xs font-black uppercase tracking-[0.16em]">Subject</th>
                                <th class="border border-slate-500 px-3 py-3 text-center text-xs font-black uppercase tracking-[0.16em]">Expected Entries</th>
                                <th class="border border-slate-500 px-3 py-3 text-center text-xs font-black uppercase tracking-[0.16em]">Marked Entries</th>
                                <th class="border border-slate-500 px-3 py-3 text-center text-xs font-black uppercase tracking-[0.16em]">Pending</th>
                                <th class="border border-slate-500 px-3 py-3 text-center text-xs font-black uppercase tracking-[0.16em]">Completion %</th>
                                <th class="border border-slate-500 px-3 py-3 text-left text-xs font-black uppercase tracking-[0.16em]">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            @foreach($rows as $index => $row)
                                @php
                                    $statusColor = match ($row['status']) {
                                        'Complete' => '#1FEE0B',
                                        'Near Complete' => '#DEF043',
                                        'In Progress' => '#FF772F',
                                        default => '#FF272F',
                                    };
                                @endphp
                                <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-slate-50' }} hover:bg-slate-50 transition-colors">
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $index + 1 }}</td>
                                    <td class="border border-slate-300 px-3 py-3 text-left text-slate-900">{{ $row['subject'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['expected_entries'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['marked_entries'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center">{{ $row['pending_entries'] }}</td>
                                    <td class="border border-slate-300 px-2 py-3 text-center font-bold text-slate-900">{{ number_format($row['completion'], 1) }}%</td>
                                    <td class="border border-slate-300 px-3 py-3 text-left font-medium" style="background: {{ $statusColor }}; color: #0f172a;">{{ $row['status'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-amber-100 text-slate-900">
                            <tr>
                                <td colspan="2" class="border border-slate-500 px-3 py-3 text-center text-xs font-black uppercase tracking-[0.16em]">Total</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $summary['expected_entries'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $summary['marked_entries'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $summary['pending_entries'] }}</td>
                                <td class="border border-slate-500 px-2 py-3 text-center">{{ $summary['completion'] }}%</td>
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
