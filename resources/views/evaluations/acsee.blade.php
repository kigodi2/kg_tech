@extends('layout')

@section('content')
<div class="min-h-screen bg-slate-50" style="font-family: 'Maiandra GD', sans-serif;">
    <div class="border-b border-slate-200 bg-white/95 backdrop-blur sticky top-0 z-30 shadow-sm">
        <div class="mx-auto max-w-7xl px-6 py-5 lg:px-8">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.25em] text-blue-700">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        Evaluation Centre
                    </div>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-900 lg:text-4xl">ACSEE Evaluations</h1>
                    <p class="mt-1 max-w-3xl text-sm text-slate-600 lg:text-base">
                        Review national performance insights through professionally organized zonal and regional evaluation reports.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <a href="/evaluations" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                        <i class="fas fa-arrow-left text-xs"></i>
                        Back to Evaluations
                    </a>
                    <a href="/evaluations/acsee/regionalwise" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-900/15 transition hover:-translate-y-0.5 hover:bg-slate-800">
                        Open Regional Reports
                        <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-6 py-8 lg:px-8">
        <section class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-blue-950 to-indigo-900 px-6 py-8 text-white shadow-2xl shadow-blue-950/20 lg:px-10 lg:py-10">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(96,165,250,0.28),_transparent_34%),radial-gradient(circle_at_bottom_left,_rgba(52,211,153,0.18),_transparent_26%)]"></div>
            <div class="relative grid gap-8 lg:grid-cols-[1.4fr_0.9fr] lg:items-center">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] text-blue-100">
                        Professional Reporting Workspace
                    </div>
                    <h2 class="mt-5 max-w-3xl text-3xl font-black leading-tight lg:text-5xl">
                        A cleaner, smarter gateway to ACSEE evaluation reporting.
                    </h2>
                    <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-200 lg:text-base">
                        Navigate quickly between strategic report groups, compare performance areas, and move straight into the evaluation views used by your team.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="/evaluations/acsee/zonalwise" class="inline-flex items-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-bold text-slate-900 transition hover:-translate-y-0.5 hover:bg-slate-100">
                            <i class="fas fa-globe text-blue-600"></i>
                            Explore Zonalwise
                        </a>
                        <a href="/evaluations/acsee/regionalwise" class="inline-flex items-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-white/15">
                            <i class="fas fa-map text-emerald-300"></i>
                            Explore Regionalwise
                        </a>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5 backdrop-blur-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-100">Coverage</p>
                                <h3 class="mt-2 text-xl font-bold">20 evaluation report types</h3>
                                <p class="mt-2 text-sm text-slate-200">Organized into zonal and regional review paths for faster access.</p>
                            </div>
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-400/20 text-blue-200">
                                <i class="fas fa-layer-group text-lg"></i>
                            </span>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5 backdrop-blur-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-emerald-100">Readiness</p>
                                <h3 class="mt-2 text-xl font-bold">Decision-focused navigation</h3>
                                <p class="mt-2 text-sm text-slate-200">Designed to guide reviewers directly to the correct analysis category.</p>
                            </div>
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-400/20 text-emerald-200">
                                <i class="fas fa-bolt text-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Evaluation Streams</span>
                    <span class="rounded-2xl bg-blue-50 p-3 text-blue-600"><i class="fas fa-sitemap"></i></span>
                </div>
                <p class="mt-4 text-4xl font-black text-slate-900">2</p>
                <p class="mt-2 text-sm text-slate-600">Zonalwise and regionalwise pathways with distinct report collections.</p>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Strategic Views</span>
                    <span class="rounded-2xl bg-emerald-50 p-3 text-emerald-600"><i class="fas fa-chart-line"></i></span>
                </div>
                <p class="mt-4 text-4xl font-black text-slate-900">40</p>
                <p class="mt-2 text-sm text-slate-600">Combined evaluation perspectives across rankings, summaries, and status reporting.</p>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">User Experience</span>
                    <span class="rounded-2xl bg-amber-50 p-3 text-amber-600"><i class="fas fa-star"></i></span>
                </div>
                <p class="mt-4 text-4xl font-black text-slate-900">A+</p>
                <p class="mt-2 text-sm text-slate-600">Modern card layout, better hierarchy, and clearer calls to action.</p>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Workflow</span>
                    <span class="rounded-2xl bg-violet-50 p-3 text-violet-600"><i class="fas fa-shield-alt"></i></span>
                </div>
                <p class="mt-4 text-4xl font-black text-slate-900">Fast</p>
                <p class="mt-2 text-sm text-slate-600">Choose report family, open view, and move into analysis with fewer clicks.</p>
            </div>
        </section>

        <section class="mt-8 grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70 lg:p-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-blue-700">Choose a reporting path</p>
                        <h3 class="mt-2 text-2xl font-black text-slate-900">Evaluation modules</h3>
                        <p class="mt-2 max-w-2xl text-sm text-slate-600">Each module is presented as a dedicated workspace so users can focus on the right level of review.</p>
                    </div>
                    <div class="text-sm text-slate-500">Built for supervisors, analysts, and review committees.</div>
                </div>

                <div class="mt-8 grid gap-5 lg:grid-cols-2">
                    <a href="/evaluations/acsee/zonalwise" class="group rounded-[1.75rem] border border-blue-100 bg-gradient-to-br from-blue-50 via-white to-sky-50 p-6 transition duration-200 hover:-translate-y-1 hover:border-blue-200 hover:shadow-xl hover:shadow-blue-100/70">
                        <div class="flex items-start justify-between gap-4">
                            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600 text-xl text-white shadow-lg shadow-blue-600/25">
                                <i class="fas fa-globe"></i>
                            </span>
                            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Zonalwise</span>
                        </div>
                        <h4 class="mt-6 text-2xl font-black text-slate-900">Zonal performance evaluations</h4>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Open zonal reports covering general performance, council rankings, school analysis, ownership categories, subject summaries, and top or least-performing groups.</p>
                        <div class="mt-6 flex items-center justify-between text-sm font-bold text-blue-700">
                            <span>Open module</span>
                            <i class="fas fa-arrow-right transition group-hover:translate-x-1"></i>
                        </div>
                    </a>

                    <a href="/evaluations/acsee/regionalwise" class="group rounded-[1.75rem] border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-teal-50 p-6 transition duration-200 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-xl hover:shadow-emerald-100/70">
                        <div class="flex items-start justify-between gap-4">
                            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-600 text-xl text-white shadow-lg shadow-emerald-600/25">
                                <i class="fas fa-map"></i>
                            </span>
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Regionalwise</span>
                        </div>
                        <h4 class="mt-6 text-2xl font-black text-slate-900">Regional performance evaluations</h4>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Access region-focused reports for school and council comparisons, district breakdowns, category-specific ranking lists, and subject-level performance evaluation.</p>
                        <div class="mt-6 flex items-center justify-between text-sm font-bold text-emerald-700">
                            <span>Open module</span>
                            <i class="fas fa-arrow-right transition group-hover:translate-x-1"></i>
                        </div>
                    </a>
                </div>
            </div>

            <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70 lg:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-slate-500">Why this page feels better</p>
                <h3 class="mt-2 text-2xl font-black text-slate-900">Professional improvements</h3>

                <div class="mt-6 space-y-4">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 text-blue-600"><i class="fas fa-check-circle"></i></span>
                            <div>
                                <h4 class="font-bold text-slate-900">Clear information hierarchy</h4>
                                <p class="mt-1 text-sm text-slate-600">Header, hero, summary metrics, and action modules are now visually separated and easier to scan.</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-4">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 text-emerald-600"><i class="fas fa-check-circle"></i></span>
                            <div>
                                <h4 class="font-bold text-slate-900">Strong call-to-action design</h4>
                                <p class="mt-1 text-sm text-slate-600">Primary routes are emphasized with premium cards and action buttons instead of a plain side menu.</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-4">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 text-violet-600"><i class="fas fa-check-circle"></i></span>
                            <div>
                                <h4 class="font-bold text-slate-900">Consistent modern styling</h4>
                                <p class="mt-1 text-sm text-slate-600">Rounded surfaces, soft shadows, gradients, and balanced spacing align this page with more polished sections of the system.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
