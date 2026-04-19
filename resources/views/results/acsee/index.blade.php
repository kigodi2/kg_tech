@extends('layout')

@section('content')
@include('registration.partials.theme')
<div class="registration-shell acsee-results-shell">
<div class="acsee-lifecycle-shell w-full min-h-screen flex gap-0" x-data="resultsAcseeLifecycle()" x-init="init()">
    <aside class="w-72 acsee-sidebar text-slate-100 min-h-screen sticky top-[140px] overflow-y-auto">
        <div class="p-6 lg:p-7">
            <div class="mb-8">
                <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.28em] text-cyan-100/90">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_12px_rgba(74,222,128,0.9)]"></span>
                    Results Workspace
                </div>
                <h2 class="mt-4 text-2xl font-bold text-white">Results Lifecycle</h2>
                <p class="mt-2 text-sm leading-6 text-slate-300">Final ACSEE processing, publication controls, and reporting in one structured workspace.</p>
            </div>

            <div class="mb-8">
                <h3 class="text-[11px] font-semibold text-slate-400 uppercase tracking-[0.24em] mb-3 flex items-center gap-2">
                    <i class="fas fa-chart-bar"></i> Entry & Validation
                </h3>
                <ul class="space-y-2">
                    <li><a href="#" @click.prevent="navigateToView('overview')" :class="menuClass('overview','acsee-nav-link-active','acsee-nav-link-idle')" class="acsee-nav-link">📋 Overview</a></li>
                    <li><a href="#" @click.prevent="navigateToView('grading-system')" :class="menuClass('grading-system','acsee-nav-link-active','acsee-nav-link-idle')" class="acsee-nav-link">⚙️ Grading System</a></li>
                    @if($canComputeValidate)
                    <li><a href="#" @click.prevent="navigateToView('entry-validation')" :class="menuClass('entry-validation','acsee-nav-link-active','acsee-nav-link-idle')" class="acsee-nav-link">🧮 Compute / Validate</a></li>
                    <li><a href="#" @click.prevent="navigateToView('computation-history')" :class="menuClass('computation-history','acsee-nav-link-active','acsee-nav-link-idle')" class="acsee-nav-link">🗂️ Computation History</a></li>
                    @endif
                </ul>
            </div>

            <div class="mb-8">
                <h3 class="text-[11px] font-semibold text-slate-400 uppercase tracking-[0.24em] mb-3 flex items-center gap-2">
                    <i class="fas fa-search"></i> Moderation & Review
                </h3>
                <ul class="space-y-2">
                    <li><a href="#" @click.prevent="navigateToView('moderation-review')" :class="menuClass('moderation-review','acsee-nav-link-active','acsee-nav-link-idle')" class="acsee-nav-link">📊 Review Overview</a></li>
                    <li><a href="#" @click.prevent="navigateToView('review-dashboard')" :class="menuClass('review-dashboard','acsee-nav-link-active','acsee-nav-link-idle')" class="acsee-nav-link">📈 Review Dashboard</a></li>
                    <li><a href="#" @click.prevent="navigateToView('pending-review')" :class="menuClass('pending-review','acsee-nav-link-active','acsee-nav-link-idle')" class="acsee-nav-link">⏳ Pending Review</a></li>
                    <li><a href="#" @click.prevent="navigateToView('outliers-extremes')" :class="menuClass('outliers-extremes','acsee-nav-link-active','acsee-nav-link-idle')" class="acsee-nav-link">🚩 Outliers & Extremes (Final)</a></li>
                </ul>
            </div>

            <div class="mb-8">
                <h3 class="text-[11px] font-semibold text-slate-400 uppercase tracking-[0.24em] mb-3 flex items-center gap-2">
                    <i class="fas fa-lock"></i> Submission & Locking
                </h3>
                <ul class="space-y-2">
                    <li><a href="#" @click.prevent="navigateToView('submission-locking')" :class="menuClass('submission-locking','acsee-nav-link-active','acsee-nav-link-idle')" class="acsee-nav-link">🔒 Publish / Lock</a></li>
                    <li><a href="#" @click.prevent="navigateToView('publish-lock')" :class="menuClass('publish-lock','acsee-nav-link-active','acsee-nav-link-idle')" class="acsee-nav-link">✅ Publish / Lock Action</a></li>
                    <li><a href="#" @click.prevent="navigateToView('snapshots-versions')" :class="menuClass('snapshots-versions','acsee-nav-link-active','acsee-nav-link-idle')" class="acsee-nav-link">🧾 Snapshots / Versions</a></li>
                    @if($canAdminUnlock)
                    <li><a href="#" @click.prevent="navigateToView('admin-unlock')" :class="menuClass('admin-unlock','acsee-nav-link-active','acsee-nav-link-idle')" class="acsee-nav-link">🔓 Admin Unlock</a></li>
                    @endif
                </ul>
            </div>

            <div class="mb-8">
                <h3 class="text-[11px] font-semibold text-slate-400 uppercase tracking-[0.24em] mb-3 flex items-center gap-2">
                    <i class="fas fa-file-alt"></i> Reports & Exports
                </h3>
                <ul class="space-y-2">
                    <li><a href="#" @click.prevent="navigateToView('reports-exports')" :class="menuClass('reports-exports','acsee-nav-link-active','acsee-nav-link-idle')" class="acsee-nav-link">📑 Reports Overview</a></li>
                    <li><a href="#" @click.prevent="navigateToView('school-results')" :class="menuClass('school-results','acsee-nav-link-active','acsee-nav-link-idle')" class="acsee-nav-link">🏫 School Results</a></li>
                    <li><a href="#" @click.prevent="navigateToView('candidate-results')" :class="menuClass('candidate-results','acsee-nav-link-active','acsee-nav-link-idle')" class="acsee-nav-link">👤 Candidate Results</a></li>
                    <li><a href="#" @click.prevent="navigateToView('exports')" :class="menuClass('exports','acsee-nav-link-active','acsee-nav-link-idle')" class="acsee-nav-link">📦 Exports</a></li>
                </ul>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-4 backdrop-blur">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Release</p>
                <div class="mt-2 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-lg font-semibold text-white whitespace-nowrap">ACSEE Results</p>
                        <p class="text-sm text-slate-300">Version 1.1</p>
                    </div>
                    <span class="rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 text-xs font-semibold text-emerald-200 whitespace-nowrap">Operational</span>
                </div>
            </div>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        <div class="px-4 pt-0 sm:px-6 sm:pt-0 lg:px-8 lg:pt-0">
            <div class="registration-page-header acsee-topbar px-4 py-4 sm:px-6 sm:py-5 lg:px-7">
                <div class="acsee-topbar-layout">
                    <div class="min-w-0">
                        <div class="lg:hidden mb-3">
                            <label class="block text-xs font-semibold text-white/80 mb-1">Navigate Section</label>
                            <select
                                x-model="activeView"
                                @change="navigateToView(activeView)"
                                class="w-full border border-white/20 rounded-xl px-3 py-2.5 text-sm bg-white/95 text-slate-800"
                            >
                                <template x-for="(cfg, key) in viewRegistry" :key="'rv-' + key">
                                    <option :value="key" x-text="displayViewLabel(key)"></option>
                                </template>
                            </select>
                        </div>

                        <span class="registration-page-kicker">ACSEE Operations Workspace</span>
                        <h1 class="registration-page-title">ACSEE Results</h1>
                        <p class="registration-page-subtitle acsee-hero-subtitle">Control computation readiness, moderation workflow, publication state, and final results reporting inside one governed lifecycle workspace.</p>

                        <div class="registration-page-highlights">
                            <span class="registration-page-chip">
                                <i class="fas fa-layer-group"></i>
                                <span x-text="viewRegistry[activeView]?.category || 'Workspace'"></span>
                            </span>
                            <span class="registration-page-chip">
                                <i class="fas fa-location-arrow"></i>
                                <span x-text="displayViewLabel(activeView)"></span>
                            </span>
                            <span class="registration-page-chip">
                                <i class="fas fa-calendar-alt"></i>
                                <span x-text="activeExamYearLabel()"></span>
                            </span>
                        </div>
                    </div>

                    <div class="acsee-topbar-side">
                        <div class="registration-page-note">
                            <h2>Workflow Guidance</h2>
                            <p>Move from readiness checks to computation, review, publication, and exports without leaving the lifecycle workspace.</p>
                        </div>
                        <button @click="navigateToView('overview')" x-show="activeView !== 'overview'" class="acsee-hero-action inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-white transition">
                            <i class="fas fa-home"></i> Back to Overview
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-4 py-6 sm:px-6 sm:py-6 lg:px-8 lg:py-8 flex-1 overflow-y-auto">
            <div class="space-y-6">
                <template x-if="activeView === 'overview'">
                    <section class="acsee-panel rounded-[28px] p-6 lg:p-8 space-y-6">
                        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.9fr)]">
                            <div class="acsee-hero-card rounded-[26px] p-6 lg:p-8">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="space-y-4">
                                        <div class="inline-flex items-center gap-2 rounded-full border border-cyan-200/70 bg-white/75 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-cyan-900">
                                            Executive Overview
                                        </div>
                                        <div>
                                            <h2 class="text-2xl lg:text-3xl font-bold tracking-tight text-slate-950">Results command center</h2>
                                            <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-700">A refined view of final ACSEE processing health, publication posture, and school readiness so operations teams can act quickly with confidence.</p>
                                        </div>
                                    </div>
                                    <div x-show="summary.active_snapshot" class="inline-flex items-center gap-2 self-start rounded-full border border-emerald-300/70 bg-emerald-50/90 px-4 py-2 text-xs font-semibold text-emerald-900 shadow-sm">
                                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                        Active Snapshot
                                        <span class="rounded-full bg-white px-2 py-0.5 text-emerald-700" x-text="summary.active_snapshot?.version || ('#' + summary.active_snapshot?.id)"></span>
                                    </div>
                                </div>

                                <div x-show="!summaryLoading" class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                    <div class="acsee-stat-card">
                                        <p class="acsee-stat-label">Computed Candidates</p>
                                        <p class="acsee-stat-value" x-text="summary.total_candidates ?? 0"></p>
                                        <p class="acsee-stat-note">Total records available for final result processing.</p>
                                    </div>
                                    <div class="acsee-stat-card">
                                        <p class="acsee-stat-label">Published</p>
                                        <p class="acsee-stat-value" x-text="summary.published_candidates ?? 0"></p>
                                        <p class="acsee-stat-note">Candidates already visible in released outputs.</p>
                                    </div>
                                    <div class="acsee-stat-card">
                                        <p class="acsee-stat-label">Published + Locked</p>
                                        <p class="acsee-stat-value" x-text="summary.locked_candidates ?? 0"></p>
                                        <p class="acsee-stat-note">Candidates protected from further workflow changes.</p>
                                    </div>
                                    <div class="acsee-stat-card">
                                        <p class="acsee-stat-label">Computed Schools</p>
                                        <p class="acsee-stat-value" x-text="summary.schools_with_results ?? 0"></p>
                                        <p class="acsee-stat-note">Schools with computed ACSEE results in scope.</p>
                                    </div>
                                </div>

                                <div x-show="summaryLoading" class="text-center py-10"><i class="fas fa-spinner animate-spin text-sky-500 text-xl"></i></div>
                            </div>

                            <div class="space-y-4">
                                <div x-show="rulesNotes" class="acsee-callout rounded-[24px] p-5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-700">Calculation Guidance</p>
                                            <p class="mt-2 text-base font-semibold text-slate-900" x-text="rulesNotes.title"></p>
                                        </div>
                                        <span class="rounded-full border border-white/70 bg-white/80 px-3 py-1 text-xs font-semibold text-sky-700 shadow-sm" x-text="rulesNotes.version"></span>
                                    </div>
                                    <p class="mt-3 text-sm leading-6 text-slate-700">
                                        Key reminders: multi-paper normalization, AGGT core-subject scope (GS excluded; BAM only if core), S/F handling, and Division 0 eligibility.
                                    </p>
                                </div>

                                <div class="rounded-[24px] border border-slate-200 bg-white/90 p-5 shadow-[0_20px_45px_rgba(15,23,42,0.06)]">
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Operational Focus</p>
                                    <div class="mt-4 space-y-3 text-sm text-slate-700">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-1 h-2.5 w-2.5 rounded-full bg-cyan-500"></span>
                                            <p>Monitor current readiness before compute and validate that school coverage matches expected national scope.</p>
                                        </div>
                                        <div class="flex items-start gap-3">
                                            <span class="mt-1 h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                            <p>Use the snapshot state and publication counts to judge whether results are safe to advance toward locking.</p>
                                        </div>
                                        <div class="flex items-start gap-3">
                                            <span class="mt-1 h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                                            <p>Escalate readiness gaps early by filtering schools, districts, and regions directly from the queue below.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-show="!summaryLoading" class="acsee-flow-panel rounded-[24px] p-5">
                            <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Cross-Module Workflow</p>
                                    <p class="mt-2 text-lg font-semibold text-slate-950">Mark Entry to Results publication pipeline</p>
                                    <p class="mt-1 text-sm text-slate-600">This flow clarifies where Mark Entry ends and where Results processing begins.</p>
                                </div>
                                <button @click="fetchSubmissionStatus()" class="self-start rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                                    Refresh workflow
                                </button>
                            </div>

                            <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                                <div class="acsee-flow-step" :class="workflowStageClass('approved')">
                                    <p class="acsee-flow-step-label">1. Mark Entry Approved</p>
                                    <p class="acsee-flow-step-state" x-text="workflowStageStatus('approved')"></p>
                                    <p class="acsee-flow-step-note">Reviewed batches accepted for locking.</p>
                                </div>
                                <div class="acsee-flow-step" :class="workflowStageClass('locked')">
                                    <p class="acsee-flow-step-label">2. Mark Entry Locked</p>
                                    <p class="acsee-flow-step-state" x-text="workflowStageStatus('locked')"></p>
                                    <p class="acsee-flow-step-note">Locked batches become eligible for promotion.</p>
                                </div>
                                <div class="acsee-flow-step" :class="workflowStageClass('promoted')">
                                    <p class="acsee-flow-step-label">3. Marks Promoted</p>
                                    <p class="acsee-flow-step-state" x-text="workflowStageStatus('promoted')"></p>
                                    <p class="acsee-flow-step-note">`subject_marks` is now the source of truth.</p>
                                </div>
                                <div class="acsee-flow-step" :class="workflowStageClass('computed')">
                                    <p class="acsee-flow-step-label">4. Results Computed</p>
                                    <p class="acsee-flow-step-state" x-text="workflowStageStatus('computed')"></p>
                                    <p class="acsee-flow-step-note">Draft/final runs produced candidate result rows.</p>
                                </div>
                                <div class="acsee-flow-step" :class="workflowStageClass('published')">
                                    <p class="acsee-flow-step-label">5. Snapshot Published</p>
                                    <p class="acsee-flow-step-state" x-text="workflowStageStatus('published')"></p>
                                    <p class="acsee-flow-step-note">A versioned published snapshot is active.</p>
                                </div>
                                <div class="acsee-flow-step" :class="workflowStageClass('snapshot_locked')">
                                    <p class="acsee-flow-step-label">6. Snapshot Locked</p>
                                    <p class="acsee-flow-step-state" x-text="workflowStageStatus('snapshot_locked')"></p>
                                    <p class="acsee-flow-step-note">Published snapshot is protected from change.</p>
                                </div>
                            </div>
                        </div>

                        <div x-show="!summaryLoading" class="grid gap-4 xl:grid-cols-4">
                            <div class="rounded-[22px] border border-slate-200 bg-white/90 p-5 shadow-[0_16px_35px_rgba(15,23,42,0.06)]">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Ready Input Audit</p>
                                <p class="mt-3 text-2xl font-bold text-slate-950" x-text="Number(summary.ready_queue?.candidates || 0).toLocaleString()"></p>
                                <p class="mt-1 text-sm text-slate-600">Candidates in the current live promoted mark set.</p>
                                <div class="mt-4 text-xs text-slate-500">
                                    <span x-text="`${Number(summary.ready_queue?.subject_marks_rows || 0).toLocaleString()} live subject rows`"></span>
                                </div>
                            </div>

                            <div class="rounded-[22px] border border-slate-200 bg-white/90 p-5 shadow-[0_16px_35px_rgba(15,23,42,0.06)]">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Latest Compute Run</p>
                                <div class="mt-3 flex items-center gap-2">
                                    <p class="text-2xl font-bold text-slate-950" x-text="overviewLatestProcess()?.id ? `#${overviewLatestProcess().id}` : 'None'"></p>
                                    <span x-show="overviewLatestProcess()" class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold" :class="submissionBadgeClass(overviewLatestProcess()?.status)" x-text="overviewLatestProcess()?.status || 'none'"></span>
                                </div>
                                <p class="mt-1 text-sm text-slate-600" x-text="overviewLatestProcess()?.type ? `${overviewLatestProcess().type.toUpperCase()} compute run` : 'No compute run recorded for this year.'"></p>
                                <div class="mt-4 text-xs text-slate-500">
                                    <span x-text="formatDateTime(overviewLatestProcess()?.completed_at || overviewLatestProcess()?.created_at)"></span>
                                </div>
                            </div>

                            <div class="rounded-[22px] border border-slate-200 bg-white/90 p-5 shadow-[0_16px_35px_rgba(15,23,42,0.06)]">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Snapshot Governance</p>
                                <p class="mt-3 text-2xl font-bold text-slate-950" x-text="summary.active_snapshot?.version || 'Not Published'"></p>
                                <p class="mt-1 text-sm text-slate-600" x-text="submission.status?.is_locked ? 'Published snapshot is locked.' : (summary.active_snapshot ? 'Published snapshot is open for governance actions.' : 'No active snapshot yet.')"></p>
                                <div class="mt-4 text-xs text-slate-500">
                                    <span x-text="summary.active_snapshot ? formatDateTime(summary.active_snapshot.published_at) : 'Awaiting first publication'"></span>
                                </div>
                            </div>

                            <div class="rounded-[22px] border border-slate-200 bg-white/90 p-5 shadow-[0_16px_35px_rgba(15,23,42,0.06)]">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Recent Workflow Action</p>
                                <p class="mt-3 text-lg font-bold text-slate-950" x-text="overviewRecentAction()?.action || 'No recent action'"></p>
                                <p class="mt-1 text-sm text-slate-600" x-text="overviewRecentAction()?.actor || 'System'"></p>
                                <div class="mt-4 text-xs text-slate-500">
                                    <span x-text="formatDateTime(overviewRecentAction()?.created_at || overviewRecentAction()?.time)"></span>
                                </div>
                            </div>
                        </div>

                        <div x-show="!summaryLoading" class="acsee-ready-panel rounded-[26px] p-5 lg:p-6">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-700">Pre-compute Readiness</p>
                                    <p class="mt-2 text-xl font-bold text-slate-950">Mark Entry Ready Queue</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-700">
                                Approved/locked marks available from Mark Entry for ACSEE
                                <span x-text="summary.ready_queue?.exam_year || ''"></span>.
                                    </p>
                                </div>
                                <div class="inline-flex items-center gap-2 self-start rounded-full border border-emerald-300 bg-white/80 px-4 py-2 text-xs font-semibold text-emerald-800 shadow-sm">
                                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                    Ready for results computation
                                </div>
                            </div>

                            <div class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-5">
                                <div class="acsee-metric-tile"><p class="acsee-stat-label">Candidates Ready</p><p class="acsee-metric-value text-emerald-700" x-text="summary.ready_queue?.candidates ?? 0"></p></div>
                                <div class="acsee-metric-tile"><p class="acsee-stat-label">Promoted Rows</p><p class="acsee-metric-value text-emerald-700" x-text="summary.ready_queue?.subject_marks_rows ?? 0"></p></div>
                                <div class="acsee-metric-tile"><p class="acsee-stat-label">Schools Ready</p><p class="acsee-metric-value text-emerald-700" x-text="summary.ready_queue?.schools ?? 0"></p></div>
                                <div class="acsee-metric-tile"><p class="acsee-stat-label">Subjects Ready</p><p class="acsee-metric-value text-emerald-700" x-text="summary.ready_queue?.subjects ?? 0"></p></div>
                                <div class="acsee-metric-tile"><p class="acsee-stat-label">Locked Subject Batches</p><p class="acsee-metric-value text-emerald-700" x-text="summary.ready_queue?.batches ?? 0"></p></div>
                            </div>

                            <div class="mt-6 rounded-[24px] border border-white/70 bg-white/85 p-4 shadow-[0_18px_40px_rgba(16,24,40,0.08)] space-y-4 backdrop-blur">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                    <div>
                                        <p class="text-lg font-semibold text-slate-900">Schools Ready for Results Processing</p>
                                        <p class="text-sm text-slate-500">Filter the queue by school, region, or district to verify readiness coverage.</p>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-center gap-2">
                                        <input type="text"
                                               x-model="readySchools.search"
                                               @input.debounce.400ms="loadReadySchools(1)"
                                               placeholder="Search school code/name..."
                                               class="acsee-filter-input border border-slate-200 bg-slate-50/80 px-4 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 focus:border-cyan-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-cyan-100 w-full sm:w-72">
                                        <div class="relative w-44" @click.outside="readySchools.regionOpen = false">
                                            <button
                                                @click="readySchools.regionOpen = !readySchools.regionOpen"
                                                class="acsee-filter-button w-full border border-slate-200 bg-white px-4 py-2.5 text-sm text-left flex items-center justify-between text-slate-700 shadow-sm"
                                            >
                                                <span x-text="selectedReadyRegionLabel()"></span>
                                                <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                            </button>
                                            <div x-show="readySchools.regionOpen" x-cloak class="acsee-filter-menu absolute z-50 mt-2 w-full overflow-hidden border border-slate-200 bg-white shadow-2xl shadow-slate-900/10">
                                                <input x-model="readySchools.regionSearch" type="text" placeholder="Search region..." class="acsee-filter-search w-full border-b border-slate-200 px-3 py-2.5 text-sm focus:outline-none">
                                                <div class="max-h-48 overflow-y-auto">
                                                    <div @click="selectReadyRegion('')" class="px-3 py-2 text-sm cursor-pointer hover:bg-cyan-50">All Regions</div>
                                                    <template x-for="region in filteredReadyRegions()" :key="'rrf-' + region.id">
                                                        <div @click="selectReadyRegion(String(region.id))" class="px-3 py-2 text-sm cursor-pointer hover:bg-cyan-50" x-text="region.name"></div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="relative w-44" @click.outside="readySchools.districtOpen = false">
                                            <button
                                                @click="if (readySchools.region_id) readySchools.districtOpen = !readySchools.districtOpen"
                                                :disabled="!readySchools.region_id"
                                                class="acsee-filter-button w-full border border-slate-200 px-4 py-2.5 text-sm text-left flex items-center justify-between disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed text-slate-700 shadow-sm"
                                                :class="readySchools.region_id ? 'bg-white' : 'bg-slate-100'"
                                            >
                                                <span x-text="selectedReadyDistrictLabel()"></span>
                                                <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                            </button>
                                            <div x-show="readySchools.districtOpen" x-cloak class="acsee-filter-menu absolute z-50 mt-2 w-full overflow-hidden border border-slate-200 bg-white shadow-2xl shadow-slate-900/10">
                                                <input x-model="readySchools.districtSearch" type="text" placeholder="Search district..." class="acsee-filter-search w-full border-b border-slate-200 px-3 py-2.5 text-sm focus:outline-none">
                                                <div class="max-h-48 overflow-y-auto">
                                                    <div @click="selectReadyDistrict('')" class="px-3 py-2 text-sm cursor-pointer hover:bg-cyan-50">All Districts</div>
                                                    <template x-for="district in filteredReadyDistricts()" :key="'rdf-' + district.id">
                                                        <div @click="selectReadyDistrict(String(district.id))" class="px-3 py-2 text-sm cursor-pointer hover:bg-cyan-50" x-text="district.name"></div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                        <button @click="loadReadySchools(1)" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white shadow-lg shadow-emerald-600/20 transition hover:-translate-y-0.5 hover:bg-emerald-700">Refresh</button>
                                    </div>
                                </div>

                                <div x-show="readySchoolsLoading" class="text-center py-4">
                                    <i class="fas fa-spinner animate-spin text-emerald-500"></i>
                                </div>

                                <div x-show="!readySchoolsLoading" class="overflow-x-auto rounded-[22px] border border-slate-200 bg-white shadow-[0_20px_40px_rgba(15,23,42,0.08)]">
                                    <table class="w-full">
                                        <thead class="bg-gradient-to-r from-slate-100 via-white to-cyan-50 border-b border-slate-200 sticky top-0 z-10">
                                            <tr>
                                                <th class="px-4 py-4 text-left text-[11px] font-semibold text-slate-600 uppercase tracking-[0.24em]">
                                                    <i class="fas fa-barcode mr-1 text-blue-600"></i>Code
                                                </th>
                                                <th class="px-4 py-4 text-left text-[11px] font-semibold text-slate-600 uppercase tracking-[0.24em]">
                                                    <i class="fas fa-school mr-1 text-purple-600"></i>School
                                                </th>
                                                <th class="px-4 py-4 text-left text-[11px] font-semibold text-slate-600 uppercase tracking-[0.24em]">
                                                    <i class="fas fa-landmark mr-1 text-emerald-600"></i>Ownership
                                                </th>
                                                <th class="px-4 py-4 text-left text-[11px] font-semibold text-slate-600 uppercase tracking-[0.24em]">
                                                    <i class="fas fa-flag mr-1 text-green-600"></i>District
                                                </th>
                                                <th class="px-4 py-4 text-left text-[11px] font-semibold text-slate-600 uppercase tracking-[0.24em]">
                                                    <i class="fas fa-map-pin mr-1 text-blue-500"></i>Region
                                                </th>
                                                <th class="px-4 py-4 text-right text-[11px] font-semibold text-slate-600 uppercase tracking-[0.24em]">
                                                    <i class="fas fa-users mr-1 text-emerald-600"></i>Candidates Ready
                                                </th>
                                                <th class="px-4 py-4 text-right text-[11px] font-semibold text-slate-600 uppercase tracking-[0.24em]">
                                                    <i class="fas fa-book mr-1 text-blue-600"></i>Subjects
                                                </th>
                                                <th class="px-4 py-4 text-right text-[11px] font-semibold text-slate-600 uppercase tracking-[0.24em]">
                                                    <i class="fas fa-layer-group mr-1 text-violet-600"></i>Batches
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <template x-if="(readySchools.items || []).length === 0">
                                                <tr>
                                                    <td colspan="8" class="px-6 py-8 text-center text-slate-500 text-sm">No schools found for current scope.</td>
                                                </tr>
                                            </template>
                                            <template x-for="(school, idx) in (readySchools.items || [])" :key="'ready-school-' + school.id">
                                                <tr class="transition-colors hover:bg-cyan-50/60">
                                                    <td class="px-4 py-4 text-sm font-mono font-semibold text-cyan-700" x-text="school.code || '—'"></td>
                                                    <td class="px-4 py-4 text-sm text-slate-800 font-medium" x-text="school.name || '—'"></td>
                                                    <td class="px-4 py-4 text-sm">
                                                        <span
                                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border"
                                                            :class="(() => {
                                                                const own = String(school.ownership || '').toUpperCase();
                                                                if (own.includes('NON')) return 'bg-amber-50 text-amber-700 border-amber-200';
                                                                if (own.includes('GOV')) return 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                                                return 'bg-gray-100 text-gray-700 border-gray-200';
                                                            })()"
                                                            x-text="school.ownership || '—'">
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-4 text-sm text-slate-700" x-text="school.district_name || '—'"></td>
                                                    <td class="px-4 py-4 text-sm text-slate-700" x-text="school.region_name || '—'"></td>
                                                    <td class="px-4 py-4 text-right">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200"
                                                              x-text="school.candidates_ready ?? 0"></span>
                                                    </td>
                                                    <td class="px-4 py-4 text-right">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border bg-blue-50 text-blue-700 border-blue-200"
                                                              x-text="school.subjects_ready ?? 0"></span>
                                                    </td>
                                                    <td class="px-4 py-4 text-right">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border bg-violet-50 text-violet-700 border-violet-200"
                                                              x-text="school.batches_ready ?? 0"></span>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>

                                <div x-show="!readySchoolsLoading" class="px-1 py-2 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between text-sm text-slate-600">
                                    <span>
                                        Showing <span x-text="readySchools.meta.from || 0"></span> - <span x-text="readySchools.meta.to || 0"></span>
                                        of <span x-text="readySchools.meta.total || 0"></span>
                                    </span>
                                    <div class="flex items-center gap-1">
                                        <button @click="loadReadySchools((readySchools.meta.current_page || 1) - 1)"
                                                :disabled="(readySchools.meta.current_page || 1) <= 1"
                                                class="rounded-xl bg-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-300 disabled:opacity-30 disabled:cursor-not-allowed">Prev</button>
                                        <button @click="loadReadySchools((readySchools.meta.current_page || 1) + 1)"
                                                :disabled="(readySchools.meta.current_page || 1) >= (readySchools.meta.last_page || 1)"
                                                class="rounded-xl bg-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-300 disabled:opacity-30 disabled:cursor-not-allowed">Next</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </template>

                <template x-if="activeView === 'grading-system'">
                    <section class="bg-white rounded-lg shadow p-6 space-y-5">
                        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 border-b border-gray-100 pb-4">
                            <div class="space-y-1">
                                <h2 class="text-xl font-bold text-gray-800">Grading System</h2>
                                <p class="text-sm text-gray-600">Professional NECTA-aligned setup for grading, GPA, divisions, and competence with safe no-write preview.</p>
                            </div>
                            <div class="w-full lg:w-64">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Exam Year</label>
                                <div class="relative" @click.outside="gradingYearOpen = false">
                                    <button
                                        type="button"
                                        @click="gradingYearOpen = !gradingYearOpen"
                                        class="acsee-filter-button w-full px-3 py-2 border border-gray-300 rounded-none text-sm h-10 bg-white text-left flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        <span x-text="selectedYearLabel(grading.exam_year_id)"></span>
                                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                    </button>
                                    <div x-show="gradingYearOpen" x-cloak class="acsee-filter-menu absolute z-50 w-full bg-white border border-gray-300 shadow-lg">
                                        <input x-model="gradingYearSearch" type="text" placeholder="Search year..." class="acsee-filter-search w-full border-b border-gray-200 px-3 py-2 text-sm focus:outline-none">
                                        <div class="max-h-48 overflow-y-auto">
                                            <template x-for="year in filteredYearOptions(gradingYearSearch)" :key="'grading-year-' + year.id">
                                                <div @click="selectGradingYear(String(year.id))" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50" x-text="year.year_label"></div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border rounded-lg border-blue-200 bg-blue-50 overflow-hidden">
                            <button type="button" @click="rulesOpen = !rulesOpen" class="w-full flex items-center justify-between px-4 py-3 text-left">
                                <div>
                                    <p class="font-semibold text-blue-900" x-text="rulesNotes?.title || 'NECTA ACSEE Calculation Reminders'"></p>
                                    <p class="text-xs text-blue-800" x-show="rulesNotes?.version">Version: <span x-text="rulesNotes?.version"></span></p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span x-show="rulesLoading" class="text-xs text-blue-700">Loading...</span>
                                    <i class="fas" :class="rulesOpen ? 'fa-chevron-up text-blue-700' : 'fa-chevron-down text-blue-700'"></i>
                                </div>
                            </button>
                            <div x-show="rulesOpen" class="px-4 pb-4 space-y-4">
                                <div class="bg-white border border-blue-100 rounded p-3 text-sm text-gray-700">
                                    <p class="font-semibold text-gray-800 mb-1">How to read these reminders</p>
                                    <p>Formulas are shown in plain language to make them easy for all users to understand. These are read-only reminders from the service logic.</p>
                                </div>
                                <template x-if="rulesNotes?.sections?.length">
                                    <div class="space-y-4">
                                        <template x-for="(section, sectionIdx) in rulesNotes.sections" :key="'rn-' + sectionIdx">
                                            <div class="bg-white border border-blue-100 rounded-lg p-4 space-y-3">
                                                <h4 class="font-semibold text-gray-800" x-text="section.heading"></h4>
                                                <ul class="list-disc pl-5 text-sm text-gray-700 space-y-1">
                                                    <template x-for="(item, itemIdx) in (section.items || [])" :key="'rni-' + sectionIdx + '-' + itemIdx">
                                                        <li x-text="item"></li>
                                                    </template>
                                                </ul>
                                                <div class="space-y-2" x-show="(section.formulas || []).length">
                                                    <template x-for="(formula, formulaIdx) in (section.formulas || [])" :key="'rnf-' + sectionIdx + '-' + formulaIdx">
                                                        <div class="rounded border border-gray-200 bg-gray-50 p-3">
                                                            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide" x-text="formula.label"></p>
                                                            <p class="text-sm text-gray-900 mt-1"><span class="font-semibold">Formula:</span> <span x-text="formula.plain"></span></p>
                                                        </div>
                                                    </template>
                                                </div>
                                                <template x-for="(table, tableIdx) in (section.tables || [])" :key="'rnt-' + sectionIdx + '-' + tableIdx">
                                                    <div class="overflow-x-auto">
                                                        <p class="text-xs font-semibold text-gray-600 mb-2" x-text="table.title"></p>
                                                        <table class="min-w-[260px] text-xs border border-gray-200 rounded overflow-hidden">
                                                            <thead class="bg-gray-100 text-gray-700">
                                                                <tr>
                                                                    <th class="px-2 py-1 text-left border-b border-gray-200">Grade</th>
                                                                    <th class="px-2 py-1 text-left border-b border-gray-200">Points</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <template x-for="(row, rowIdx) in (table.rows || [])" :key="'rntr-' + sectionIdx + '-' + tableIdx + '-' + rowIdx">
                                                                    <tr class="border-b border-gray-100">
                                                                        <td class="px-2 py-1" x-text="row.grade"></td>
                                                                        <td class="px-2 py-1" x-text="row.points"></td>
                                                                    </tr>
                                                                </template>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </template>
                                                <div x-show="(section.examples || []).length" class="space-y-1">
                                                    <template x-for="(example, exampleIdx) in (section.examples || [])" :key="'rne-' + sectionIdx + '-' + exampleIdx">
                                                        <p class="text-xs text-indigo-700 bg-indigo-50 border border-indigo-200 rounded px-2 py-1" x-text="example"></p>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div x-show="gradingLoading" class="text-center py-8"><i class="fas fa-spinner animate-spin text-blue-500"></i></div>

                        <div x-show="!gradingLoading" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div class="border rounded-lg p-4 bg-gray-50">
                                <p class="text-xs text-gray-500">Active Config</p>
                                <p class="font-semibold" x-text="grading.config?.name || 'No active configuration'"></p>
                                <p class="text-sm text-gray-600" x-text="'Version: ' + (grading.config?.version || '-')"></p>
                                <p class="text-sm text-gray-600" x-text="'Status: ' + (grading.config?.is_locked ? 'Locked' : (grading.config?.is_active ? 'Active' : 'Inactive'))"></p>
                                <p class="text-xs text-gray-500 mt-2" x-text="'Last modified by: ' + (grading.config?.last_modified_by || 'N/A')"></p>
                                <p class="text-xs text-gray-500" x-text="'Timestamp: ' + (grading.config?.last_modified_at || 'N/A')"></p>
                            </div>

                            <div class="border rounded-lg p-4 bg-amber-50 border-amber-200">
                                <p class="font-semibold text-amber-800">Preview Impact (Dry Run)</p>
                                <p class="text-sm text-amber-700">This is a preview only. No data has been written.</p>
                                <div class="grid grid-cols-2 gap-2 mt-3">
                                    <input x-model="preview.region_id" type="number" placeholder="Region ID (optional)" class="px-2 py-1 border rounded text-sm">
                                    <input x-model="preview.council_id" type="number" placeholder="Council ID (optional)" class="px-2 py-1 border rounded text-sm">
                                    <input x-model="preview.school_id" type="number" placeholder="School ID (optional)" class="px-2 py-1 border rounded text-sm">
                                    <input x-model="preview.sample_size" placeholder="Sample size: 50|100|ALL" class="px-2 py-1 border rounded text-sm">
                                </div>
                                <button @click="runPreviewImpact()" :disabled="previewLoading || !(canPreviewImpact && grading.permissions.can_preview)" class="mt-3 px-3 py-2 bg-indigo-600 text-white rounded text-sm disabled:bg-gray-400">Preview Impact (Dry Run)</button>
                            </div>
                        </div>

                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 border rounded-lg p-3 bg-gray-50">
                            <div class="flex flex-wrap gap-2 text-sm">
                                <button @click="gradingTab = 'grading'" :class="gradingTab === 'grading' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300'" class="px-3 py-1.5 rounded border font-medium">NECTA Grade Boundaries</button>
                                <button @click="gradingTab = 'gpa'" :class="gradingTab === 'gpa' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300'" class="px-3 py-1.5 rounded border font-medium">Institutional GPA</button>
                                <button @click="gradingTab = 'divisions'" :class="gradingTab === 'divisions' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300'" class="px-3 py-1.5 rounded border font-medium">Division by AGGT</button>
                                <button @click="gradingTab = 'competence'" :class="gradingTab === 'competence' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300'" class="px-3 py-1.5 rounded border font-medium">Competence Bands</button>
                            </div>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" x-model="editMode" :disabled="!grading.permissions.can_edit"> Edit Mode
                            </label>
                        </div>

                        <div class="border rounded-lg border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-800">
                            NECTA effective mode: A-E treated as principal passes, S/F as non-principal, AGGT points mapping fixed to A=1 ... F=7.
                            Division eligibility enforces principal passes >= 2, otherwise Division 0.
                        </div>

                        <div class="border rounded-lg overflow-hidden">
                            <div class="px-4 py-3 border-b bg-gray-50 flex items-center justify-between">
                                <h3 class="font-semibold" x-text="gradingTabTitle()"></h3>
                                <div class="flex gap-2">
                                    <button @click="addTabRule()" :disabled="!editMode || !grading.permissions.can_edit" class="px-2 py-1 bg-blue-600 text-white rounded text-xs disabled:bg-gray-400">Add Rule</button>
                                    <button @click="validateConfig()" :disabled="ruleActionLoading" class="px-2 py-1 bg-yellow-600 text-white rounded text-xs">Validate Setup</button>
                                    <button @click="saveRules()" :disabled="ruleActionLoading || !editMode || !grading.permissions.can_edit" class="px-2 py-1 bg-green-600 text-white rounded text-xs disabled:bg-gray-400">Save Config</button>
                                    <button @click="activateConfig()" :disabled="ruleActionLoading || !grading.config?.id || !grading.permissions.can_activate" class="px-2 py-1 bg-indigo-600 text-white rounded text-xs disabled:bg-gray-400">Activate</button>
                                    <button @click="lockConfig()" :disabled="ruleActionLoading || !grading.config?.id || !grading.permissions.can_lock" class="px-2 py-1 bg-red-600 text-white rounded text-xs disabled:bg-gray-400">Lock</button>
                                </div>
                            </div>

                            <div x-show="gradingTab === 'grading'" class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Grade</th>
                                            <th class="px-3 py-2 text-left">Name</th>
                                            <th class="px-3 py-2 text-left">Min Mark</th>
                                            <th class="px-3 py-2 text-left">Max Mark</th>
                                            <th class="px-3 py-2 text-left">Points</th>
                                            <th class="px-3 py-2 text-left">Principal</th>
                                            <th class="px-3 py-2 text-left">Subsidiary</th>
                                            <th class="px-3 py-2 text-left">Disabled</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(rule, idx) in grading.rules" :key="'gr-' + idx">
                                            <tr class="border-t">
                                                <td class="px-3 py-2"><input x-model="rule.grade" :disabled="!editMode" class="w-16 px-2 py-1 border rounded"></td>
                                                <td class="px-3 py-2"><input x-model="rule.name" :disabled="!editMode" class="w-40 px-2 py-1 border rounded"></td>
                                                <td class="px-3 py-2"><input x-model.number="rule.min_mark" :disabled="!editMode" type="number" step="0.01" class="w-24 px-2 py-1 border rounded"></td>
                                                <td class="px-3 py-2"><input x-model.number="rule.max_mark" :disabled="!editMode" type="number" step="0.01" class="w-24 px-2 py-1 border rounded"></td>
                                                <td class="px-3 py-2"><input x-model.number="rule.points" :disabled="!editMode" type="number" step="0.01" class="w-20 px-2 py-1 border rounded"></td>
                                                <td class="px-3 py-2"><input x-model="rule.is_principal" :disabled="!editMode" type="checkbox"></td>
                                                <td class="px-3 py-2"><input x-model="rule.is_subsidiary" :disabled="!editMode" type="checkbox"></td>
                                                <td class="px-3 py-2"><input x-model="rule.is_disabled" :disabled="!editMode" type="checkbox"></td>
                                            </tr>
                                        </template>
                                        <tr x-show="!grading.rules || grading.rules.length === 0"><td colspan="8" class="px-3 py-4 text-center text-gray-500">Not configured. Add Rule.</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <div x-show="gradingTab === 'gpa'" class="p-4 space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div><label class="text-xs text-gray-600">Method</label><input x-model="grading.gpa.settings.method" :disabled="!editMode" class="w-full px-2 py-1 border rounded text-sm"></div>
                                    <div><label class="text-xs text-gray-600">Max GPA</label><input x-model.number="grading.gpa.settings.max_gpa" :disabled="!editMode" type="number" step="0.01" class="w-full px-2 py-1 border rounded text-sm"></div>
                                    <div><label class="text-xs text-gray-600">Rounding Decimals</label><input x-model.number="grading.gpa.settings.rounding_decimals" :disabled="!editMode" type="number" class="w-full px-2 py-1 border rounded text-sm"></div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div><label class="text-xs text-gray-600">Rounding Mode</label><select x-model="grading.gpa.settings.rounding_mode" :disabled="!editMode" class="w-full px-2 py-1 border rounded text-sm"><option>half_up</option><option>half_down</option><option>ceil</option><option>floor</option></select></div>
                                    <div><label class="text-xs text-gray-600">Principal Count</label><input x-model.number="grading.gpa.settings.principal_count" :disabled="!editMode" type="number" class="w-full px-2 py-1 border rounded text-sm"></div>
                                    <div class="flex items-center gap-2 mt-5"><input type="checkbox" x-model="grading.gpa.settings.include_subsidiary" :disabled="!editMode"><span class="text-sm">Include Subsidiary</span></div>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-gray-100"><tr><th class="px-3 py-2 text-left">Grade</th><th class="px-3 py-2 text-left">GPA Point</th></tr></thead>
                                        <tbody>
                                            <template x-for="(row, i) in grading.gpa.grade_points" :key="'gp-' + i">
                                                <tr class="border-t">
                                                    <td class="px-3 py-2"><input x-model="row.grade" :disabled="!editMode" class="w-16 px-2 py-1 border rounded"></td>
                                                    <td class="px-3 py-2"><input x-model.number="row.gpa_point_value" :disabled="!editMode" type="number" step="0.01" class="w-24 px-2 py-1 border rounded"></td>
                                                </tr>
                                            </template>
                                            <tr x-show="!grading.gpa.grade_points || grading.gpa.grade_points.length === 0"><td colspan="2" class="px-3 py-4 text-center text-gray-500">Not configured. Add Rule.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div x-show="gradingTab === 'divisions'" class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-100"><tr><th class="px-3 py-2 text-left">Division</th><th class="px-3 py-2 text-left">Min Points</th><th class="px-3 py-2 text-left">Max Points</th><th class="px-3 py-2 text-left">Notes</th><th class="px-3 py-2 text-left">Disabled</th></tr></thead>
                                    <tbody>
                                        <template x-for="(row, i) in grading.divisions.rules" :key="'dr-' + i">
                                            <tr class="border-t">
                                                <td class="px-3 py-2"><input x-model="row.division_label" :disabled="!editMode" class="w-24 px-2 py-1 border rounded"></td>
                                                <td class="px-3 py-2"><input x-model.number="row.min_points" :disabled="!editMode" type="number" step="0.01" class="w-24 px-2 py-1 border rounded"></td>
                                                <td class="px-3 py-2"><input x-model.number="row.max_points" :disabled="!editMode" type="number" step="0.01" class="w-24 px-2 py-1 border rounded"></td>
                                                <td class="px-3 py-2"><input x-model="row.notes" :disabled="!editMode" class="w-40 px-2 py-1 border rounded"></td>
                                                <td class="px-3 py-2"><input x-model="row.is_disabled" :disabled="!editMode" type="checkbox"></td>
                                            </tr>
                                        </template>
                                        <tr x-show="!grading.divisions.rules || grading.divisions.rules.length === 0"><td colspan="5" class="px-3 py-4 text-center text-gray-500">Not configured. Add Rule.</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <div x-show="gradingTab === 'competence'" class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-100"><tr><th class="px-3 py-2 text-left">Level</th><th class="px-3 py-2 text-left">Basis</th><th class="px-3 py-2 text-left">Min</th><th class="px-3 py-2 text-left">Max</th><th class="px-3 py-2 text-left">Color</th><th class="px-3 py-2 text-left">Disabled</th></tr></thead>
                                    <tbody>
                                        <template x-for="(row, i) in grading.competence_levels.rules" :key="'cr-' + i">
                                            <tr class="border-t">
                                                <td class="px-3 py-2"><input x-model="row.level_label" :disabled="!editMode" class="w-40 px-2 py-1 border rounded"></td>
                                                <td class="px-3 py-2"><select x-model="row.basis" :disabled="!editMode" class="w-24 px-2 py-1 border rounded"><option>GPA</option><option>POINTS</option><option>MARKS</option><option>GRADE</option></select></td>
                                                <td class="px-3 py-2"><input x-model.number="row.min_value" :disabled="!editMode" type="number" step="0.01" class="w-24 px-2 py-1 border rounded"></td>
                                                <td class="px-3 py-2"><input x-model.number="row.max_value" :disabled="!editMode" type="number" step="0.01" class="w-24 px-2 py-1 border rounded"></td>
                                                <td class="px-3 py-2"><input x-model="row.color_code" :disabled="!editMode" class="w-28 px-2 py-1 border rounded"></td>
                                                <td class="px-3 py-2"><input x-model="row.is_disabled" :disabled="!editMode" type="checkbox"></td>
                                            </tr>
                                        </template>
                                        <tr x-show="!grading.competence_levels.rules || grading.competence_levels.rules.length === 0"><td colspan="6" class="px-3 py-4 text-center text-gray-500">Not configured. Add Rule.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div x-show="(grading.warnings || []).length" class="space-y-2">
                            <template x-for="(warn, i) in grading.warnings || []" :key="'gw' + i">
                                <div class="bg-blue-50 border border-blue-200 text-blue-700 px-3 py-2 rounded text-sm" x-text="warn"></div>
                            </template>
                        </div>

                        <div x-show="gradingValidation.errors.length || gradingValidation.warnings.length" class="space-y-2">
                            <template x-for="(err, i) in gradingValidation.errors" :key="'e' + i">
                                <div class="bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded text-sm" x-text="err"></div>
                            </template>
                            <template x-for="(warn, i) in gradingValidation.warnings" :key="'w' + i">
                                <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-3 py-2 rounded text-sm" x-text="warn"></div>
                            </template>
                        </div>

                        <div x-show="previewResult" class="border rounded-lg p-4 bg-indigo-50 border-indigo-200 space-y-3">
                            <p class="font-semibold text-indigo-800">Preview Impact (Dry Run)</p>
                            <p class="text-sm text-indigo-700">This is a preview only. No data has been written.</p>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
                                <div><p class="text-xs text-gray-500">Simulated Candidates</p><p class="font-semibold" x-text="previewResult.total_candidates_simulated"></p></div>
                                <div><p class="text-xs text-gray-500">Changed Divisions</p><p class="font-semibold" x-text="previewResult.comparison_with_current?.changed_divisions_count || 0"></p></div>
                                <div><p class="text-xs text-gray-500">Changed GPA</p><p class="font-semibold" x-text="previewResult.comparison_with_current?.changed_gpa_count || 0"></p></div>
                                <div><p class="text-xs text-gray-500">Changed Competence</p><p class="font-semibold" x-text="previewResult.comparison_with_current?.changed_competence_count || 0"></p></div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                                <div class="bg-white border rounded p-2">
                                    <p class="font-semibold text-gray-700 mb-1">Simulated GPA Distribution</p>
                                    <pre class="whitespace-pre-wrap" x-text="JSON.stringify(previewResult.simulated_gpa_distribution || {}, null, 2)"></pre>
                                </div>
                                <div class="bg-white border rounded p-2">
                                    <p class="font-semibold text-gray-700 mb-1">Simulated Division Distribution</p>
                                    <pre class="whitespace-pre-wrap" x-text="JSON.stringify(previewResult.simulated_division_distribution || {}, null, 2)"></pre>
                                </div>
                            </div>
                        </div>

                        <div class="border rounded-lg overflow-hidden">
                            <div class="px-4 py-3 border-b bg-gray-50 flex items-center justify-between">
                                <h3 class="font-semibold">Change Log</h3>
                                <button @click="loadGradingLog()" class="px-2 py-1 bg-gray-700 text-white rounded text-xs">Refresh</button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-100"><tr><th class="px-3 py-2 text-left">Time</th><th class="px-3 py-2 text-left">Actor</th><th class="px-3 py-2 text-left">Action</th></tr></thead>
                                    <tbody>
                                        <template x-for="row in gradingLog.data || []" :key="row.id">
                                            <tr class="border-t">
                                                <td class="px-3 py-2" x-text="row.created_at"></td>
                                                <td class="px-3 py-2" x-text="row.admin?.name || 'N/A'"></td>
                                                <td class="px-3 py-2" x-text="row.data?.action || '-' "></td>
                                            </tr>
                                        </template>
                                        <tr x-show="!gradingLog.data || gradingLog.data.length === 0"><td colspan="3" class="px-3 py-4 text-center text-gray-500">No grading config changes logged.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </template>

                <template x-if="activeView === 'entry-validation'">
                    <section class="bg-white rounded-lg shadow p-6 space-y-4">
                        <div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">Compute / Validate</h2>
                                <p class="text-sm text-gray-600">Generate ACSEE results from approved/locked Mark Entry batches and validate readiness.</p>
                            </div>
                        </div>

                        <div class="rounded-[20px] border border-slate-200 bg-slate-50/70 p-4 shadow-sm">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                                <div class="grid flex-1 gap-3 md:grid-cols-2 xl:grid-cols-4 2xl:max-w-[860px]">
                                    <div class="relative" @click.outside="computeYearOpen = false">
                                        <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Exam Year</p>
                                    <button
                                        type="button"
                                        @click="computeYearOpen = !computeYearOpen"
                                            class="acsee-filter-button w-full px-3 py-2 border border-gray-300 rounded-none text-sm h-10 bg-white text-left flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        <span x-text="selectedYearLabel(compute.exam_year_id)"></span>
                                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                    </button>
                                        <div x-show="computeYearOpen" x-cloak class="acsee-filter-menu absolute z-50 w-full bg-white border border-gray-300 shadow-lg">
                                        <input x-model="computeYearSearch" type="text" placeholder="Search year..." class="acsee-filter-search w-full border-b border-gray-200 px-3 py-2 text-sm focus:outline-none">
                                        <div class="max-h-48 overflow-y-auto">
                                            <template x-for="year in filteredYearOptions(computeYearSearch)" :key="'cy-' + year.id">
                                                <div @click="selectComputeYear(String(year.id))" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50" x-text="year.year_label"></div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                    <div class="relative" @click.outside="compute.regionOpen = false">
                                        <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Region</p>
                                        <button
                                            type="button"
                                            @click="compute.regionOpen = !compute.regionOpen"
                                            class="acsee-filter-button w-full px-3 py-2 border border-gray-300 rounded-none text-sm h-10 bg-white text-left flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        >
                                            <span x-text="selectedComputeRegionLabel()"></span>
                                            <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                        </button>
                                        <div x-show="compute.regionOpen" x-cloak class="acsee-filter-menu absolute z-50 w-full bg-white border border-gray-300 shadow-lg">
                                            <input x-model="compute.regionSearch" type="text" placeholder="Search region..." class="acsee-filter-search w-full border-b border-gray-200 px-3 py-2 text-sm focus:outline-none">
                                            <div class="max-h-48 overflow-y-auto">
                                                <div @click="selectComputeRegion('')" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50">All Regions</div>
                                                <template x-for="region in filteredComputeRegions()" :key="'compute-region-' + region.id">
                                                    <div @click="selectComputeRegion(String(region.id))" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50" x-text="region.name"></div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                    <div class="relative" @click.outside="compute.districtOpen = false">
                                        <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">District</p>
                                        <button
                                            type="button"
                                            @click="if (compute.region_id) compute.districtOpen = !compute.districtOpen"
                                            :disabled="!compute.region_id"
                                            :class="compute.region_id ? 'bg-white' : 'bg-slate-100'"
                                            class="acsee-filter-button w-full px-3 py-2 border border-gray-300 rounded-none text-sm h-10 text-left flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:text-gray-400"
                                        >
                                            <span x-text="selectedComputeDistrictLabel()"></span>
                                            <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                        </button>
                                        <div x-show="compute.districtOpen" x-cloak class="acsee-filter-menu absolute z-50 w-full bg-white border border-gray-300 shadow-lg">
                                            <input x-model="compute.districtSearch" type="text" placeholder="Search district..." class="acsee-filter-search w-full border-b border-gray-200 px-3 py-2 text-sm focus:outline-none">
                                            <div class="max-h-48 overflow-y-auto">
                                                <div @click="selectComputeDistrict('')" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50">All Districts</div>
                                                <template x-for="district in filteredComputeDistricts()" :key="'compute-district-' + district.id">
                                                    <div @click="selectComputeDistrict(String(district.id))" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50" x-text="district.name"></div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                    <div class="relative" @click.outside="compute.schoolOpen = false">
                                        <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">School</p>
                                        <button
                                            type="button"
                                            @click="if (compute.district_id) compute.schoolOpen = !compute.schoolOpen"
                                            :disabled="!compute.district_id"
                                            :class="compute.district_id ? 'bg-white' : 'bg-slate-100'"
                                            class="acsee-filter-button w-full px-3 py-2 border border-gray-300 rounded-none text-sm h-10 text-left flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:text-gray-400"
                                        >
                                            <span x-text="selectedComputeSchoolLabel()"></span>
                                            <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                        </button>
                                        <div x-show="compute.schoolOpen" x-cloak class="acsee-filter-menu absolute z-50 w-full bg-white border border-gray-300 shadow-lg">
                                            <input x-model="compute.schoolSearch" type="text" placeholder="Search school..." class="acsee-filter-search w-full border-b border-gray-200 px-3 py-2 text-sm focus:outline-none">
                                            <div class="max-h-56 overflow-y-auto">
                                                <div @click="selectComputeSchool('')" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50">All Schools In District</div>
                                                <template x-for="school in filteredComputeSchools()" :key="'compute-school-' + school.id">
                                                    <div @click="selectComputeSchool(String(school.id))" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50">
                                                        <span x-text="school.code ? `${school.code} - ${school.name}` : school.name"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                </div>

                                <div class="flex flex-wrap items-end gap-2 xl:w-auto xl:min-w-[260px] xl:justify-end">
                                    <a href="/admin/subject-paper-weights" class="inline-flex h-10 items-center justify-center border border-blue-300 px-4 text-sm font-medium text-blue-700 transition hover:bg-blue-50">Manage Paper Weights</a>
                                    <button @click="loadComputeReadiness()" class="inline-flex h-10 items-center justify-center border border-emerald-300 px-4 text-sm font-medium text-emerald-700 transition hover:bg-emerald-50">Validate Readiness</button>
                                </div>
                            </div>
                        </div>

                        <div x-show="compute.loading" class="text-center py-8">
                            <i class="fas fa-spinner animate-spin text-blue-500"></i>
                        </div>

                        <div x-show="!compute.loading && compute.readiness" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                                <div class="p-3 border rounded-lg bg-emerald-50"><p class="text-xs text-gray-500">Candidates Ready</p><p class="text-xl font-bold text-emerald-700" x-text="compute.readiness.ready_queue?.candidates ?? 0"></p></div>
                                <div class="p-3 border rounded-lg bg-emerald-50"><p class="text-xs text-gray-500">Promoted Rows</p><p class="text-xl font-bold text-emerald-700" x-text="compute.readiness.ready_queue?.subject_marks_rows ?? 0"></p></div>
                                <div class="p-3 border rounded-lg bg-emerald-50"><p class="text-xs text-gray-500">Schools Ready</p><p class="text-xl font-bold text-emerald-700" x-text="compute.readiness.ready_queue?.schools ?? 0"></p></div>
                                <div class="p-3 border rounded-lg bg-emerald-50"><p class="text-xs text-gray-500">Subjects Ready</p><p class="text-xl font-bold text-emerald-700" x-text="compute.readiness.ready_queue?.subjects ?? 0"></p></div>
                                <div class="p-3 border rounded-lg bg-emerald-50"><p class="text-xs text-gray-500">Locked Subject Batches</p><p class="text-xl font-bold text-emerald-700" x-text="compute.readiness.ready_queue?.batches ?? 0"></p></div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                <div class="border rounded-lg p-4 bg-gray-50">
                                    <h3 class="font-semibold text-gray-800 text-sm mb-2">Readiness Checks</h3>
                                    <div class="space-y-1 text-sm">
                                        <p><span class="font-medium">Ready Marks:</span> <span :class="compute.readiness.checks?.has_ready_marks ? 'text-emerald-700' : 'text-red-700'" x-text="compute.readiness.checks?.has_ready_marks ? 'OK' : 'Missing'"></span></p>
                                        <p><span class="font-medium">Grading Profile:</span> <span :class="compute.readiness.checks?.has_grading_profile ? 'text-emerald-700' : 'text-red-700'" x-text="compute.readiness.checks?.has_grading_profile ? 'Available' : 'Not found'"></span></p>
                                        <p><span class="font-medium">Grading Valid:</span> <span :class="compute.readiness.checks?.grading_is_valid ? 'text-emerald-700' : 'text-red-700'" x-text="compute.readiness.checks?.grading_is_valid ? 'Valid' : 'Invalid'"></span></p>
                                        <p><span class="font-medium">Active Run:</span> <span :class="compute.readiness.checks?.no_running_process ? 'text-emerald-700' : 'text-red-700'" x-text="compute.readiness.checks?.no_running_process ? 'None' : 'In progress'"></span></p>
                                        <p class="text-xs text-gray-500 pt-1">
                                            Profile: <span class="font-medium" x-text="compute.readiness.grading?.profile_name || '—'"></span>
                                        </p>
                                    </div>
                                </div>
                                <div class="border rounded-lg p-4 bg-gray-50 space-y-3">
                                    <h3 class="font-semibold text-gray-800 text-sm">Run Compute</h3>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" x-model="compute.promote_marks" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        Promote raw marks before compute
                                    </label>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button @click="runComputeValidate('draft')" :disabled="compute.running || !compute.readiness?.can_run" class="px-3 py-2 rounded-lg text-sm text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed">Run Draft</button>
                                        <button @click="runComputeValidate('final')" :disabled="compute.running || !compute.readiness?.can_run" class="px-3 py-2 rounded-lg text-sm text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 disabled:cursor-not-allowed">Run Final</button>
                                    </div>
                                    <p x-show="compute.message" class="text-sm" :class="compute.error ? 'text-red-700' : 'text-emerald-700'" x-text="compute.message"></p>
                                </div>
                            </div>

                            <div x-show="compute.readiness?.grading?.errors?.length" class="border border-red-200 bg-red-50 rounded-lg p-3">
                                <p class="text-sm font-semibold text-red-700 mb-1">Grading Errors</p>
                                <ul class="list-disc list-inside text-sm text-red-700">
                                    <template x-for="(err, idx) in (compute.readiness?.grading?.errors || [])" :key="'gerr-' + idx"><li x-text="err"></li></template>
                                </ul>
                            </div>

                            <div class="border rounded-lg overflow-hidden">
                                <div class="px-4 py-3 bg-gray-50 border-b">
                                    <h3 class="font-semibold text-gray-800 text-sm">Recent Compute Runs</h3>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="px-3 py-2 text-left">ID</th>
                                                <th class="px-3 py-2 text-left">Type</th>
                                                <th class="px-3 py-2 text-left">Status</th>
                                                <th class="px-3 py-2 text-left">Total</th>
                                                <th class="px-3 py-2 text-left">Processed</th>
                                                <th class="px-3 py-2 text-left">Skipped</th>
                                                <th class="px-3 py-2 text-left">Errors</th>
                                                <th class="px-3 py-2 text-left">Completed</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="row in (compute.readiness?.processing?.latest_runs || [])" :key="'pr-' + row.id">
                                                <tr class="border-t">
                                                    <td class="px-3 py-2" x-text="row.id"></td>
                                                    <td class="px-3 py-2 uppercase" x-text="row.type"></td>
                                                    <td class="px-3 py-2">
                                                        <span
                                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border"
                                                            :class="{
                                                                'bg-emerald-100 text-emerald-800 border-emerald-200': row.status === 'completed',
                                                                'bg-red-100 text-red-800 border-red-200': row.status === 'failed',
                                                                'bg-amber-100 text-amber-800 border-amber-200': row.status === 'in_progress' || row.status === 'running' || row.status === 'pending',
                                                                'bg-gray-100 text-gray-800 border-gray-200': !['completed','failed','in_progress','running','pending'].includes(row.status)
                                                            }"
                                                            x-text="String(row.status || 'unknown').replace('_', ' ')"
                                                        ></span>
                                                    </td>
                                                    <td class="px-3 py-2" x-text="row.total_candidates"></td>
                                                    <td class="px-3 py-2">
                                                        <span
                                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border"
                                                            :class="{
                                                                'bg-red-100 text-red-800 border-red-200': Number(row.processed_count || 0) === 0,
                                                                'bg-amber-100 text-amber-800 border-amber-200': Number(row.processed_count || 0) > 0 && Number(row.processed_count || 0) < Number(row.total_candidates || 0),
                                                                'bg-emerald-100 text-emerald-800 border-emerald-200': Number(row.processed_count || 0) >= Number(row.total_candidates || 0)
                                                            }"
                                                            x-text="row.processed_count ?? 0"
                                                        ></span>
                                                    </td>
                                                    <td class="px-3 py-2" x-text="row.skipped_count ?? Math.max(0, Number(row.total_candidates || 0) - Number(row.processed_count || 0))"></td>
                                                    <td class="px-3 py-2" x-text="row.error_count"></td>
                                                    <td class="px-3 py-2" x-text="row.completed_at || '—'"></td>
                                                </tr>
                                            </template>
                                            <tr x-show="!(compute.readiness?.processing?.latest_runs || []).length">
                                                <td colspan="8" class="px-3 py-4 text-center text-gray-500">No compute runs yet.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="border rounded-lg p-4 bg-amber-50" x-show="(compute.readiness?.processing?.latest_runs || []).length">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                    <h3 class="font-semibold text-gray-800 text-sm">Skipped Candidates Breakdown (Latest Run)</h3>
                                    <div class="text-xs text-gray-600">
                                        Run <span class="font-semibold" x-text="compute.readiness?.processing?.latest_runs?.[0]?.id || '—'"></span>
                                        <span class="mx-1">•</span>
                                        Total Skipped: <span class="font-semibold text-amber-700" x-text="compute.readiness?.processing?.latest_runs?.[0]?.skipped_count ?? 0"></span>
                                    </div>
                                </div>
                                <div class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
                                    <div class="p-3 border rounded bg-white">
                                        <p class="text-xs text-gray-500">No Promoted Subject Marks</p>
                                        <p class="text-lg font-bold text-amber-700" x-text="compute.readiness?.processing?.latest_runs?.[0]?.skipped_breakdown?.no_promoted_subject_marks ?? 0"></p>
                                    </div>
                                    <div class="p-3 border rounded bg-white">
                                        <p class="text-xs text-gray-500">No Graded Subjects</p>
                                        <p class="text-lg font-bold text-amber-700" x-text="compute.readiness?.processing?.latest_runs?.[0]?.skipped_breakdown?.no_graded_subjects ?? 0"></p>
                                    </div>
                                    <div class="p-3 border rounded bg-white">
                                        <p class="text-xs text-gray-500">Candidate Compute Errors</p>
                                        <p class="text-lg font-bold text-amber-700" x-text="compute.readiness?.processing?.latest_runs?.[0]?.skipped_breakdown?.candidate_compute_errors ?? 0"></p>
                                    </div>
                                    <div class="p-3 border rounded bg-white">
                                        <p class="text-xs text-gray-500">Other / Unclassified</p>
                                        <p class="text-lg font-bold text-amber-700" x-text="compute.readiness?.processing?.latest_runs?.[0]?.skipped_breakdown?.other ?? 0"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </template>

                <template x-if="activeView === 'moderation-review'">
                    <section class="bg-white rounded-lg shadow p-6 space-y-4">
                        <h2 class="text-xl font-bold text-gray-800">Moderation & Review</h2>
                        <p class="text-sm text-gray-600">Review distributions, outliers, and pending processing flags.</p>
                        <div class="flex items-center gap-2">
                            <button @click="loadResultStatistics('published')" class="px-3 py-1.5 rounded text-xs border border-blue-300 text-blue-700 hover:bg-blue-50">Published Stats</button>
                            <button @click="loadResultStatistics('draft')" class="px-3 py-1.5 rounded text-xs border border-emerald-300 text-emerald-700 hover:bg-emerald-50">Draft Stats</button>
                        </div>
                        <div x-show="statisticsLoading" class="text-center py-6"><i class="fas fa-spinner animate-spin text-blue-500"></i></div>
                        <div x-show="!statisticsLoading && statistics" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div class="p-3 border rounded bg-gray-50"><p class="text-xs text-gray-500">Candidates</p><p class="text-xl font-bold" x-text="statistics.candidates_count ?? 0"></p></div>
                            <div class="p-3 border rounded bg-gray-50"><p class="text-xs text-gray-500">Schools</p><p class="text-xl font-bold" x-text="statistics.schools_count ?? 0"></p></div>
                            <div class="p-3 border rounded bg-gray-50"><p class="text-xs text-gray-500">Mean AGGT</p><p class="text-xl font-bold" x-text="statistics.mean_aggt ?? '—'"></p></div>
                            <div class="p-3 border rounded bg-gray-50"><p class="text-xs text-gray-500">Mean GPA</p><p class="text-xl font-bold" x-text="statistics.mean_gpa ?? '—'"></p></div>
                        </div>
                    </section>
                </template>

                <template x-if="activeView === 'computation-history'">
                    <section class="bg-white rounded-lg shadow p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-800">Computation History</h2>
                            <button @click="loadComputeHistory()" class="px-3 py-1.5 rounded text-xs border border-blue-300 text-blue-700 hover:bg-blue-50">Refresh</button>
                        </div>
                        <div x-show="computeHistoryLoading" class="text-center py-8"><i class="fas fa-spinner animate-spin text-blue-500"></i></div>
                        <div x-show="!computeHistoryLoading" class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-100"><tr><th class="px-3 py-2 text-left">ID</th><th class="px-3 py-2 text-left">Type</th><th class="px-3 py-2 text-left">Status</th><th class="px-3 py-2 text-left">Total</th><th class="px-3 py-2 text-left">Processed</th><th class="px-3 py-2 text-left">Errors</th><th class="px-3 py-2 text-left">Started</th><th class="px-3 py-2 text-left">Finished</th></tr></thead>
                                <tbody>
                                    <template x-for="row in (computeHistory.data || [])" :key="'ch-' + row.id">
                                        <tr class="border-t">
                                            <td class="px-3 py-2" x-text="row.id"></td>
                                            <td class="px-3 py-2 uppercase" x-text="row.type"></td>
                                            <td class="px-3 py-2" x-text="row.status"></td>
                                            <td class="px-3 py-2" x-text="row.total_candidates"></td>
                                            <td class="px-3 py-2" x-text="row.processed_count"></td>
                                            <td class="px-3 py-2" x-text="row.error_count"></td>
                                            <td class="px-3 py-2" x-text="row.started_at || row.created_at"></td>
                                            <td class="px-3 py-2" x-text="row.finished_at || row.completed_at || '—'"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="!(computeHistory.data || []).length"><td colspan="8" class="px-3 py-4 text-center text-gray-500">No compute runs found.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </template>

                <template x-if="activeView === 'review-dashboard'">
                    <section class="bg-white rounded-lg shadow p-6 space-y-4">
                        <h2 class="text-xl font-bold text-gray-800">Review Dashboard</h2>
                        <div x-show="reviewLoading" class="text-center py-8"><i class="fas fa-spinner animate-spin text-blue-500"></i></div>
                        <div x-show="!reviewLoading" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="p-4 border rounded-lg bg-yellow-50"><p class="text-xs text-gray-500">Outliers</p><p class="text-2xl font-bold" x-text="review.outliers_count ?? 0"></p></div>
                            <div class="p-4 border rounded-lg bg-yellow-50"><p class="text-xs text-gray-500">Missing / Partial</p><p class="text-2xl font-bold" x-text="review.missing_or_partial_count ?? 0"></p></div>
                            <div class="p-4 border rounded-lg bg-yellow-50"><p class="text-xs text-gray-500">Divisions Tracked</p><p class="text-2xl font-bold" x-text="(review.division_distribution || []).length"></p></div>
                        </div>
                    </section>
                </template>

                <template x-if="activeView === 'pending-review'">
                    <section class="bg-white rounded-lg shadow p-6 space-y-4">
                        <h2 class="text-xl font-bold text-gray-800">Pending Review</h2>
                        <div x-show="pendingLoading" class="text-center py-8"><i class="fas fa-spinner animate-spin text-blue-500"></i></div>
                        <div x-show="!pendingLoading && pending.data && pending.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left">ID</th><th class="px-3 py-2 text-left">Type</th><th class="px-3 py-2 text-left">Status</th><th class="px-3 py-2 text-left">Processed</th><th class="px-3 py-2 text-left">Errors</th></tr></thead>
                                <tbody>
                                    <template x-for="row in pending.data" :key="row.id"><tr class="border-t"><td class="px-3 py-2" x-text="row.id"></td><td class="px-3 py-2" x-text="row.type"></td><td class="px-3 py-2" x-text="row.status"></td><td class="px-3 py-2" x-text="row.processed_count"></td><td class="px-3 py-2" x-text="row.error_count"></td></tr></template>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </template>

                <template x-if="activeView === 'outliers-extremes'">
                    <section class="bg-white rounded-lg shadow p-6 space-y-4">
                        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">Outliers & Extremes (Final)</h2>
                                <p class="text-sm text-gray-600">Read-only NECTA-style anomaly analytics from final tables only.</p>
                            </div>
                            <div class="p-0">
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                                    <div class="relative" @click.outside="outliers.yearOpen = false">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Exam Year</label>
                                        <button
                                            @click="outliers.yearOpen = !outliers.yearOpen"
                                            class="acsee-filter-button w-full border rounded px-2 py-1.5 text-sm bg-white text-left flex items-center justify-between"
                                        >
                                            <span x-text="selectedOutlierYearLabel()"></span>
                                            <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                        </button>
                                        <div x-show="outliers.yearOpen" x-cloak class="acsee-filter-menu absolute z-50 mt-1 w-full bg-white border rounded shadow-lg">
                                            <input x-model="outliers.yearSearch" type="text" placeholder="Search year..." class="acsee-filter-search w-full border-b px-2 py-1.5 text-sm">
                                            <div class="max-h-48 overflow-y-auto">
                                                <div @click="selectOutlierYear('')" class="px-2 py-1.5 text-sm cursor-pointer hover:bg-blue-50">All Years</div>
                                                <template x-for="year in filteredOutlierYears()" :key="'oyf-' + year.id">
                                                    <div @click="selectOutlierYear(String(year.id))" class="px-2 py-1.5 text-sm cursor-pointer hover:bg-blue-50" x-text="year.year_label"></div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="relative" @click.outside="outliers.regionOpen = false">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Region</label>
                                        <button
                                            @click="outliers.regionOpen = !outliers.regionOpen"
                                            class="acsee-filter-button w-full border rounded px-2 py-1.5 text-sm bg-white text-left flex items-center justify-between"
                                        >
                                            <span x-text="selectedOutlierRegionLabel()"></span>
                                            <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                        </button>
                                        <div x-show="outliers.regionOpen" x-cloak class="acsee-filter-menu absolute z-50 mt-1 w-full bg-white border rounded shadow-lg">
                                            <input x-model="outliers.regionSearch" type="text" placeholder="Search region..." class="acsee-filter-search w-full border-b px-2 py-1.5 text-sm">
                                            <div class="max-h-48 overflow-y-auto">
                                                <div @click="selectOutlierRegion('')" class="px-2 py-1.5 text-sm cursor-pointer hover:bg-blue-50">All Regions</div>
                                                <template x-for="region in filteredOutlierRegions()" :key="'orf-' + region.id">
                                                    <div @click="selectOutlierRegion(String(region.id))" class="px-2 py-1.5 text-sm cursor-pointer hover:bg-blue-50" x-text="region.name"></div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="relative" @click.outside="outliers.districtOpen = false">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">District</label>
                                        <button
                                            @click="if (outliers.filters.region_id) outliers.districtOpen = !outliers.districtOpen"
                                            :disabled="!outliers.filters.region_id"
                                            class="acsee-filter-button w-full border rounded px-2 py-1.5 text-sm text-left flex items-center justify-between disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
                                            :class="outliers.filters.region_id ? 'bg-white' : 'bg-gray-100'"
                                        >
                                            <span x-text="selectedOutlierDistrictLabel()"></span>
                                            <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                        </button>
                                        <div x-show="outliers.districtOpen" x-cloak class="acsee-filter-menu absolute z-50 mt-1 w-full bg-white border rounded shadow-lg">
                                            <input x-model="outliers.districtSearch" type="text" placeholder="Search district..." class="acsee-filter-search w-full border-b px-2 py-1.5 text-sm">
                                            <div class="max-h-48 overflow-y-auto">
                                                <div @click="selectOutlierDistrict('')" class="px-2 py-1.5 text-sm cursor-pointer hover:bg-blue-50">All Districts</div>
                                                <template x-for="district in filteredOutlierDistricts()" :key="'odf-' + district.id">
                                                    <div @click="selectOutlierDistrict(String(district.id))" class="px-2 py-1.5 text-sm cursor-pointer hover:bg-blue-50" x-text="district.name"></div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                                        <input x-model="outliers.filters.q" @input.debounce.400ms="applyOutliersFilters()" placeholder="candidate, school, subject..." class="acsee-filter-input w-full border rounded px-2 py-1.5 text-sm">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="p-4 border rounded-lg bg-yellow-50"><p class="text-xs text-gray-500">Flagged Candidates</p><p class="text-2xl font-bold" x-text="outliers.summary.flagged_candidates ?? 0"></p></div>
                            <div class="p-4 border rounded-lg bg-yellow-50"><p class="text-xs text-gray-500">Flagged Schools</p><p class="text-2xl font-bold" x-text="outliers.summary.flagged_schools ?? 0"></p></div>
                            <div class="p-4 border rounded-lg bg-yellow-50"><p class="text-xs text-gray-500">Top Outlier School</p><p class="text-sm font-semibold" x-text="outliers.summary.top_outlier_school?.school_name || '—'"></p></div>
                            <div class="p-4 border rounded-lg bg-yellow-50"><p class="text-xs text-gray-500">Top Outlier Subject</p><p class="text-sm font-semibold" x-text="outliers.summary.top_outlier_subject?.subject_name || '—'"></p></div>
                        </div>

                        <div class="border rounded-lg overflow-hidden">
                            <div class="bg-gray-50 border-b px-4 py-3 flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap gap-2">
                                    <button @click="outliers.activeTab = 'candidates'" class="px-3 py-1.5 rounded text-sm border" :class="outliers.activeTab === 'candidates' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300'">Candidate Outliers</button>
                                    <button @click="outliers.activeTab = 'schools'" class="px-3 py-1.5 rounded text-sm border" :class="outliers.activeTab === 'schools' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300'">School Outliers</button>
                                    <button @click="outliers.activeTab = 'subjects'" class="px-3 py-1.5 rounded text-sm border" :class="outliers.activeTab === 'subjects' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300'">Subject Distributions</button>
                                    <button @click="outliers.activeTab = 'missing'" class="px-3 py-1.5 rounded text-sm border" :class="outliers.activeTab === 'missing' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300'">Missing/Withheld</button>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="openFinalOutliersApproveModal()"
                                            :disabled="outliers.bulkApproving || outliers.loading || !['candidates','schools'].includes(outliers.activeTab)"
                                            class="px-3 py-1.5 rounded border text-xs font-semibold transition"
                                            :class="(outliers.bulkApproving || outliers.loading || !['candidates','schools'].includes(outliers.activeTab)) ? 'border-gray-300 text-gray-400 bg-gray-100 cursor-not-allowed' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50'">
                                        <span x-show="!outliers.bulkApproving">Approve All Flags</span>
                                        <span x-show="outliers.bulkApproving"><i class="fas fa-spinner fa-spin mr-1"></i> Approving...</span>
                                    </button>
                                    <button @click="exportOutliers('pdf')" class="px-3 py-1.5 rounded border border-blue-300 text-blue-700 text-xs hover:bg-blue-50">Export PDF</button>
                                    <button @click="exportOutliers('xlsx')" class="px-3 py-1.5 rounded border border-blue-300 text-blue-700 text-xs hover:bg-blue-50">Export XLSX</button>
                                </div>
                            </div>

                            <div class="p-4 space-y-3">
                                <div x-show="outliers.loading" class="text-center py-8"><i class="fas fa-spinner animate-spin text-blue-500"></i></div>
                                <div x-show="outliers.error && !outliers.loading" class="bg-red-50 border border-red-200 rounded p-3 text-sm text-red-700" x-text="outliers.error"></div>

                                <div x-show="!outliers.loading && !outliers.error" class="overflow-x-auto">
                                    <table class="min-w-full" x-show="outliers.activeTab === 'candidates'">
                                        <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300 sticky top-0 z-10">
                                            <tr>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-barcode mr-1 text-blue-600"></i>Index</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-user mr-1 text-gray-600"></i>Candidate</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-school mr-1 text-purple-600"></i>School</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-book mr-1 text-blue-600"></i>Subject</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-percent mr-1 text-emerald-600"></i>Mark</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-chart-line mr-1 text-orange-600"></i>Z-Score</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-flag mr-1 text-red-600"></i>Flag</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <template x-for="row in outliers.candidates.data" :key="'oc-' + row.candidate_id + '-' + row.subject_id">
                                                <tr class="hover:bg-blue-50 transition-colors">
                                                    <td class="px-3 py-3 text-sm font-medium text-gray-800" x-text="row.index_number"></td>
                                                    <td class="px-3 py-3 text-sm text-gray-800" x-text="row.candidate_name"></td>
                                                    <td class="px-3 py-3 text-sm text-gray-700" x-text="row.school_name"></td>
                                                    <td class="px-3 py-3 text-sm text-gray-700" x-text="row.subject_name"></td>
                                                    <td class="px-3 py-3 text-sm text-gray-700" x-text="row.mark"></td>
                                                    <td class="px-3 py-3 text-sm text-gray-700" x-text="row.z_score"></td>
                                                    <td class="px-3 py-3 text-sm">
                                                        <span class="px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800 text-xs font-semibold" x-text="row.flag"></span>
                                                    </td>
                                                </tr>
                                            </template>
                                            <tr x-show="!(outliers.candidates.data || []).length">
                                                <td colspan="7" class="px-6 py-4 text-center text-gray-500 text-sm">No candidate outliers found.</td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <table class="min-w-full" x-show="outliers.activeTab === 'schools'">
                                        <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300 sticky top-0 z-10">
                                            <tr>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-school mr-1 text-purple-600"></i>School</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-users mr-1 text-emerald-600"></i>Candidates</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-chart-bar mr-1 text-blue-600"></i>Mean</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-chart-line mr-1 text-orange-600"></i>Z-Score</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-award mr-1 text-amber-600"></i>% A Grades</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-flag mr-1 text-red-600"></i>Flag</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <template x-for="row in outliers.schools.data" :key="'os-' + row.school_id">
                                                <tr class="hover:bg-blue-50 transition-colors">
                                                    <td class="px-3 py-3 text-sm text-gray-800 font-medium" x-text="row.school_name"></td>
                                                    <td class="px-3 py-3 text-sm text-gray-700" x-text="row.candidate_count"></td>
                                                    <td class="px-3 py-3 text-sm text-gray-700" x-text="row.mean_mark"></td>
                                                    <td class="px-3 py-3 text-sm text-gray-700" x-text="row.z_score"></td>
                                                    <td class="px-3 py-3 text-sm text-gray-700" x-text="row.a_grade_pct"></td>
                                                    <td class="px-3 py-3 text-sm">
                                                        <span x-text="row.is_flagged ? (row.flag_reason || 'Flagged') : 'Normal'" :class="row.is_flagged ? 'text-red-700 font-semibold' : 'text-gray-500'"></span>
                                                    </td>
                                                </tr>
                                            </template>
                                            <tr x-show="!(outliers.schools.data || []).length">
                                                <td colspan="6" class="px-6 py-4 text-center text-gray-500 text-sm">No school outliers found.</td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <table class="min-w-full" x-show="outliers.activeTab === 'subjects'">
                                        <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300 sticky top-0 z-10">
                                            <tr>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-book mr-1 text-blue-600"></i>Subject</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-chart-bar mr-1 text-blue-600"></i>Mean</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-sliders-h mr-1 text-indigo-600"></i>Median</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-wave-square mr-1 text-purple-600"></i>Std Dev</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-arrow-down mr-1 text-gray-600"></i>Q1</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-arrow-up mr-1 text-gray-600"></i>Q3</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-angle-double-down mr-1 text-red-500"></i>Min</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-angle-double-up mr-1 text-emerald-600"></i>Max</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <template x-for="row in outliers.subjects.subject_distribution" :key="'od-' + row.subject_id">
                                                <tr class="hover:bg-blue-50 transition-colors">
                                                    <td class="px-3 py-3 text-sm text-gray-800 font-medium" x-text="row.subject_name"></td>
                                                    <td class="px-3 py-3 text-sm text-gray-700" x-text="row.mean"></td>
                                                    <td class="px-3 py-3 text-sm text-gray-700" x-text="row.median"></td>
                                                    <td class="px-3 py-3 text-sm text-gray-700" x-text="row.std_dev"></td>
                                                    <td class="px-3 py-3 text-sm text-gray-700" x-text="row.q1"></td>
                                                    <td class="px-3 py-3 text-sm text-gray-700" x-text="row.q3"></td>
                                                    <td class="px-3 py-3 text-sm text-gray-700" x-text="row.min"></td>
                                                    <td class="px-3 py-3 text-sm text-gray-700" x-text="row.max"></td>
                                                </tr>
                                            </template>
                                            <tr x-show="!(outliers.subjects.subject_distribution || []).length">
                                                <td colspan="8" class="px-6 py-4 text-center text-gray-500 text-sm">No subject distribution records found.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div x-show="outliers.activeTab === 'missing'" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="p-4 border rounded bg-gray-50"><p class="text-xs text-gray-500">INC</p><p class="text-xl font-bold" x-text="outliers.subjects.missing_withheld?.INC || 0"></p></div>
                                        <div class="p-4 border rounded bg-gray-50"><p class="text-xs text-gray-500">ABS</p><p class="text-xl font-bold" x-text="outliers.subjects.missing_withheld?.ABS || 0"></p></div>
                                        <div class="p-4 border rounded bg-gray-50"><p class="text-xs text-gray-500">X</p><p class="text-xl font-bold" x-text="outliers.subjects.missing_withheld?.X || 0"></p></div>
                                    </div>
                                </div>

                                <div x-show="['candidates','schools'].includes(outliers.activeTab) && (outliers.currentMeta().total || 0) > 0" class="flex items-center justify-between mt-2">
                                    <div class="text-sm text-gray-700">
                                        Page <span x-text="outliers.currentMeta().current_page"></span> of <span x-text="outliers.currentMeta().last_page"></span>,
                                        showing <span x-text="(outliers.currentRows() || []).length"></span> record(s) out of <span x-text="outliers.currentMeta().total"></span> total
                                    </div>
                                    <div class="inline-flex overflow-hidden rounded-md border border-blue-300 bg-white">
                                        <button type="button" class="px-3 py-2 text-sm border-r border-blue-300 text-blue-600 hover:bg-blue-50 disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed" :disabled="outliers.currentMeta().current_page === 1" @click.prevent="goToOutliersPage(1)">&laquo;</button>
                                        <button type="button" class="px-3 py-2 text-sm border-r border-blue-300 text-blue-600 hover:bg-blue-50 disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed" :disabled="outliers.currentMeta().current_page === 1" @click.prevent="goToOutliersPage((outliers.currentMeta().current_page || 1) - 1)">&lsaquo;</button>
                                        <template x-for="p in outliers.pageWindow()" :key="'orp-' + p"><button type="button" class="px-3 py-2 text-sm border-r border-blue-300" :class="p === outliers.currentMeta().current_page ? 'bg-blue-600 text-white font-semibold' : 'text-blue-600 hover:bg-blue-50'" @click.prevent="goToOutliersPage(p)" x-text="p"></button></template>
                                        <button type="button" class="px-3 py-2 text-sm border-r border-blue-300 text-blue-600 hover:bg-blue-50 disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed" :disabled="outliers.currentMeta().current_page === outliers.currentMeta().last_page" @click.prevent="goToOutliersPage((outliers.currentMeta().current_page || 1) + 1)">&rsaquo;</button>
                                        <button type="button" class="px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed" :disabled="outliers.currentMeta().current_page === outliers.currentMeta().last_page" @click.prevent="goToOutliersPage(outliers.currentMeta().last_page)">&raquo;</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-show="outliers.bulkApproveModalOpen" x-cloak class="fixed inset-0 z-[9999] bg-black/50 flex items-center justify-center p-4">
                            <div class="bg-white rounded-lg shadow-xl w-full max-w-xl" @click.outside="closeFinalOutliersApproveModal()">
                                <div class="px-5 py-4 border-b">
                                    <h3 class="text-lg font-bold text-gray-900">Approve All Flags</h3>
                                    <p class="text-sm text-gray-600 mt-1">Approve all visible final outlier flags for the current filter scope.</p>
                                </div>
                                <div class="px-5 py-4 space-y-3 text-sm">
                                    <div class="bg-amber-50 border border-amber-200 rounded p-3 text-amber-800">
                                        <p class="font-semibold">Review action</p>
                                        <p>This action marks current tab rows as resolved in final outliers review tracking.</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="border rounded p-3 bg-gray-50">
                                            <p class="text-xs text-gray-500">Visible rows (current tab/page)</p>
                                            <p class="text-xl font-bold text-gray-800" x-text="(outliers.currentRows() || []).length"></p>
                                        </div>
                                        <div class="border rounded p-3 bg-gray-50">
                                            <p class="text-xs text-gray-500">Current total (paged tabs)</p>
                                            <p class="text-xl font-bold text-gray-800" x-text="outliers.currentMeta().total || 0"></p>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Note (optional)</label>
                                        <textarea x-model="outliers.bulkApproveNote" rows="2" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Optional note for review audit..."></textarea>
                                    </div>
                                    <label class="flex items-start gap-2 text-sm text-gray-700">
                                        <input type="checkbox" x-model="outliers.bulkApproveAcknowledge" class="mt-0.5 rounded border-gray-300">
                                        <span>I understand this applies to current filters and will resolve flags for this tab.</span>
                                    </label>

                                    <template x-if="outliers.bulkApproveResult">
                                        <div class="rounded border p-3"
                                             :class="outliers.bulkApproveResult.ok ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'">
                                            <p class="font-semibold" x-text="outliers.bulkApproveResult.message"></p>
                                            <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                                                <div class="bg-white/70 border border-current/20 rounded px-2 py-1">
                                                    <span class="font-semibold">Resolved:</span>
                                                    <span x-text="outliers.bulkApproveResult.stats.resolved ?? 0"></span>
                                                </div>
                                                <div class="bg-white/70 border border-current/20 rounded px-2 py-1">
                                                    <span class="font-semibold">Skipped:</span>
                                                    <span x-text="outliers.bulkApproveResult.stats.skipped ?? 0"></span>
                                                </div>
                                                <div class="bg-white/70 border border-current/20 rounded px-2 py-1">
                                                    <span class="font-semibold">Failed:</span>
                                                    <span x-text="outliers.bulkApproveResult.stats.failed ?? 0"></span>
                                                </div>
                                            </div>
                                            <template x-if="(outliers.bulkApproveResult.reasons || []).length > 0">
                                                <div class="mt-2 text-xs">
                                                    <p class="font-semibold mb-1">Sample reasons</p>
                                                    <ul class="list-disc list-inside space-y-0.5">
                                                        <template x-for="(reason, idx) in outliers.bulkApproveResult.reasons" :key="'final-bulk-reason-' + idx">
                                                            <li x-text="reason"></li>
                                                        </template>
                                                    </ul>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                                <div class="px-5 py-4 border-t flex items-center justify-end gap-2">
                                    <button type="button" @click="closeFinalOutliersApproveModal()" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">Cancel</button>
                                    <button type="button"
                                            @click="approveAllFinalOutlierFlags()"
                                            :disabled="outliers.bulkApproving || !outliers.bulkApproveAcknowledge"
                                            class="px-4 py-2 rounded text-white font-semibold"
                                            :class="(outliers.bulkApproving || !outliers.bulkApproveAcknowledge) ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700'">
                                        <span x-show="!outliers.bulkApproving">Confirm Approve All</span>
                                        <span x-show="outliers.bulkApproving"><i class="fas fa-spinner fa-spin mr-1"></i> Processing...</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>
                </template>

                <template x-if="activeView === 'submission-locking'">
                    <section class="bg-white rounded-lg shadow p-6 space-y-5">
                        <div class="flex items-end justify-between gap-4">
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">Submission & Locking</h2>
                                <p class="text-sm text-gray-600">Snapshot-based publish and lock workflow with readiness checks and audit trail.</p>
                            </div>
                            <div class="w-44">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Exam Year</label>
                                <div class="relative" @click.outside="submissionYearOpen = false">
                                    <button
                                        type="button"
                                        @click="submissionYearOpen = !submissionYearOpen"
                                        class="acsee-filter-button w-full px-3 py-2 border border-gray-300 rounded-none text-sm h-10 bg-white text-left flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        <span x-text="selectedYearLabel(submission.exam_year_id)"></span>
                                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                    </button>
                                    <div x-show="submissionYearOpen" x-cloak class="acsee-filter-menu absolute z-50 w-full bg-white border border-gray-300 shadow-lg">
                                        <input x-model="submissionYearSearch" type="text" placeholder="Search year..." class="acsee-filter-search w-full border-b border-gray-200 px-3 py-2 text-sm focus:outline-none">
                                        <div class="max-h-48 overflow-y-auto">
                                            <template x-for="year in filteredYearOptions(submissionYearSearch)" :key="'submission-year-' + year.id">
                                                <div @click="selectSubmissionYear(String(year.id))" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50" x-text="year.year_label"></div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border rounded-lg p-4 bg-gray-50 space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-gray-800 text-sm">Status & Preconditions</h3>
                                <button @click="fetchSubmissionStatus()" class="px-3 py-1.5 rounded text-xs border border-blue-300 text-blue-700 hover:bg-blue-50">Refresh</button>
                            </div>
                            <div x-show="submission.loading" class="text-center py-4"><i class="fas fa-spinner animate-spin text-blue-500"></i></div>
                            <div x-show="!submission.loading" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                                <div class="p-3 bg-white border rounded">
                                    <p class="text-xs text-gray-500">Latest Compute Run</p>
                                    <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-xs font-semibold border"
                                          :class="submissionBadgeClass(submission.status.latest_process?.status)"
                                          x-text="submission.status.latest_process?.status || 'none'"></span>
                                </div>
                                <div class="p-3 bg-white border rounded">
                                    <p class="text-xs text-gray-500">Draft Results Available</p>
                                    <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-xs font-semibold border"
                                          :class="Number(submission.status.draft_counts?.candidates || 0) > 0 ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-red-100 text-red-800 border-red-200'"
                                          x-text="Number(submission.status.draft_counts?.candidates || 0) > 0 ? 'yes' : 'no'"></span>
                                </div>
                                <div class="p-3 bg-white border rounded">
                                    <p class="text-xs text-gray-500">Active Snapshot</p>
                                    <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-xs font-semibold border bg-blue-100 text-blue-800 border-blue-200"
                                          x-text="submission.status.active_snapshot?.version || 'none'"></span>
                                </div>
                                <div class="p-3 bg-white border rounded">
                                    <p class="text-xs text-gray-500">Locked</p>
                                    <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-xs font-semibold border"
                                          :class="submission.status.is_locked ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-emerald-100 text-emerald-800 border-emerald-200'"
                                          x-text="submission.status.is_locked ? 'yes' : 'no'"></span>
                                </div>
                            </div>
                            <div x-show="!submission.loading" class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                <div class="flex items-center justify-between p-2 bg-white border rounded">
                                    <span>1) Approved/locked marks exist</span>
                                    <span :class="submission.preconditionClass(submission.status.preconditions?.has_approved_locked_marks)" x-text="submission.preconditionLabel(submission.status.preconditions?.has_approved_locked_marks)"></span>
                                </div>
                                <div class="flex items-center justify-between p-2 bg-white border rounded">
                                    <span>2) Latest compute completed</span>
                                    <span :class="submission.preconditionClass(submission.status.preconditions?.latest_compute_completed)" x-text="submission.preconditionLabel(submission.status.preconditions?.latest_compute_completed)"></span>
                                </div>
                                <div class="flex items-center justify-between p-2 bg-white border rounded">
                                    <span>3) Draft candidate rows > 0</span>
                                    <span :class="submission.preconditionClass(submission.status.preconditions?.has_draft_rows)" x-text="submission.preconditionLabel(submission.status.preconditions?.has_draft_rows)"></span>
                                </div>
                                <div class="flex items-center justify-between p-2 bg-white border rounded">
                                    <span>4) Publish permission</span>
                                    <span :class="submission.preconditionClass(submission.status.preconditions?.has_publish_permission)" x-text="submission.preconditionLabel(submission.status.preconditions?.has_publish_permission)"></span>
                                </div>
                            </div>
                            <div x-show="!submission.loading" class="text-xs text-gray-600">
                                Scope: <span class="font-semibold" x-text="submission.status.scope?.scope_type || 'national'"></span>
                                <span x-show="submission.status.scope?.scope_id">#<span x-text="submission.status.scope?.scope_id"></span></span>
                                <span class="ml-3">INC: <span class="font-semibold" x-text="submission.status.blockers?.inc_count || 0"></span></span>
                                <span class="ml-2">ABS: <span class="font-semibold" x-text="submission.status.blockers?.abs_count || 0"></span></span>
                                <span class="ml-2">X: <span class="font-semibold" x-text="submission.status.blockers?.x_count || 0"></span></span>
                            </div>
                        </div>

                        <div class="border rounded-lg p-4 bg-gray-50 space-y-3">
                            <h3 class="font-semibold text-gray-800 text-sm">Publish & Lock Actions</h3>
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    @click="openPublishModal()"
                                    :disabled="!submission.canPublishNow() || submission.loading || submission.actionLoading"
                                    class="px-3 py-2 rounded-lg text-sm text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span x-text="submission.status.active_snapshot ? 'Publish New Version' : 'Publish Snapshot'"></span>
                                </button>
                                <button
                                    @click="openLockModal()"
                                    :disabled="!submission.canLockNow() || submission.loading || submission.actionLoading"
                                    class="px-3 py-2 rounded-lg text-sm text-white bg-amber-600 hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Lock Published Results
                                </button>
                                <button
                                    x-show="permissions.canAdminUnlock"
                                    @click="adminUnlockFromSubmission()"
                                    :disabled="!submission.status.is_locked || submission.loading || submission.actionLoading"
                                    class="px-3 py-2 rounded-lg text-sm text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Admin Unlock
                                </button>
                            </div>
                            <p x-show="submission.status.is_locked" class="text-sm text-amber-700">Locked - Admin Unlock required before publishing a new version.</p>
                            <p x-show="submission.message" class="text-sm" :class="submission.error ? 'text-red-700' : 'text-emerald-700'" x-text="submission.message"></p>
                        </div>

                        <div class="border rounded-lg p-4 bg-gray-50 space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-gray-800 text-sm">Activity & Audit Trail</h3>
                                <button @click="navigateToView('snapshots-versions')" class="px-3 py-1.5 rounded text-xs border border-emerald-300 text-emerald-700 hover:bg-emerald-50">View Snapshots / Versions</button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Time</th>
                                            <th class="px-3 py-2 text-left">Actor</th>
                                            <th class="px-3 py-2 text-left">Action</th>
                                            <th class="px-3 py-2 text-left">Snapshot</th>
                                            <th class="px-3 py-2 text-left">Scope</th>
                                            <th class="px-3 py-2 text-left">Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(row, idx) in (submission.status.recent_actions || [])" :key="'submission-audit-' + idx">
                                            <tr class="border-t">
                                                <td class="px-3 py-2 text-xs" x-text="row.time || '—'"></td>
                                                <td class="px-3 py-2" x-text="row.actor || '—'"></td>
                                                <td class="px-3 py-2 uppercase text-xs font-semibold" x-text="(row.action || '').replaceAll('_', ' ')"></td>
                                                <td class="px-3 py-2" x-text="row.snapshot_version || '—'"></td>
                                                <td class="px-3 py-2" x-text="(row.scope_type || 'national') + (row.scope_id ? (' #' + row.scope_id) : '')"></td>
                                                <td class="px-3 py-2" x-text="row.reason || '—'"></td>
                                            </tr>
                                        </template>
                                        <tr x-show="!(submission.status.recent_actions || []).length">
                                            <td colspan="6" class="px-3 py-4 text-center text-gray-500">No recent actions.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </template>

                <template x-if="activeView === 'publish-lock'">
                    <section class="bg-white rounded-lg shadow p-6 space-y-4">
                        <h2 class="text-xl font-bold text-gray-800">Publish / Lock Results</h2>
                        <div class="grid grid-cols-1 gap-4">
                            <div class="max-w-sm">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Exam Year</label>
                                <div class="relative" @click.outside="publishYearOpen = false">
                                    <button
                                        type="button"
                                        @click="publishYearOpen = !publishYearOpen"
                                        class="w-full border rounded px-3 py-2 text-sm bg-white text-left flex items-center justify-between"
                                    >
                                        <span x-text="selectedPublishYearLabel()"></span>
                                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                    </button>
                                    <div x-show="publishYearOpen" x-cloak class="absolute z-50 mt-1 w-full bg-white border rounded shadow-lg">
                                        <input x-model="publishYearSearch" type="text" placeholder="Search year..." class="w-full border-b px-3 py-2 text-sm">
                                        <div class="max-h-48 overflow-y-auto">
                                            <div
                                                @click="selectPublishYear('')"
                                                class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50"
                                            >Select Year</div>
                                            <template x-for="year in filteredPublishYears()" :key="'pub-year-' + year.id">
                                                <div
                                                    @click="selectPublishYear(String(year.id))"
                                                    class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50"
                                                    x-text="year.year_label"
                                                ></div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button @click="submitPublishLock()" :disabled="publishLoading" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm"><span x-show="!publishLoading">Publish / Lock</span><span x-show="publishLoading">Processing...</span></button>
                        <div x-show="publishMessage" class="p-3 rounded border" :class="publishError ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'" x-text="publishMessage"></div>
                    </section>
                </template>

                <template x-if="activeView === 'snapshots-versions'">
                    <section class="bg-white rounded-lg shadow p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-800">Snapshots / Versions</h2>
                            <button @click="loadSnapshots()" class="px-3 py-1.5 rounded text-xs border border-emerald-300 text-emerald-700 hover:bg-emerald-50">Refresh</button>
                        </div>
                        <div x-show="snapshotsLoading" class="text-center py-8"><i class="fas fa-spinner animate-spin text-emerald-500"></i></div>
                        <div x-show="!snapshotsLoading" class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-100"><tr><th class="px-3 py-2 text-left">ID</th><th class="px-3 py-2 text-left">Version</th><th class="px-3 py-2 text-left">Process</th><th class="px-3 py-2 text-left">Active</th><th class="px-3 py-2 text-left">Published At</th><th class="px-3 py-2 text-left">Hash</th></tr></thead>
                                <tbody>
                                    <template x-for="row in (snapshots.data || [])" :key="'sn-' + row.id">
                                        <tr class="border-t">
                                            <td class="px-3 py-2" x-text="row.id"></td>
                                            <td class="px-3 py-2 font-semibold" x-text="row.version"></td>
                                            <td class="px-3 py-2" x-text="row.process_id || '—'"></td>
                                            <td class="px-3 py-2">
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="row.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600'" x-text="row.is_active ? 'YES' : 'NO'"></span>
                                            </td>
                                            <td class="px-3 py-2" x-text="row.published_at || '—'"></td>
                                            <td class="px-3 py-2 font-mono text-xs" x-text="row.snapshot_hash || '—'"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="!(snapshots.data || []).length"><td colspan="6" class="px-3 py-4 text-center text-gray-500">No snapshots found.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </template>

                <template x-if="activeView === 'admin-unlock'">
                    <section class="bg-white rounded-lg shadow p-6 space-y-4">
                        <h2 class="text-xl font-bold text-gray-800">Admin Unlock / Unpublish</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="max-w-sm">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Exam Year</label>
                                <div class="relative" @click.outside="unlockYearOpen = false">
                                    <button
                                        type="button"
                                        @click="unlockYearOpen = !unlockYearOpen"
                                        class="w-full border rounded px-3 py-2 text-sm bg-white text-left flex items-center justify-between"
                                    >
                                        <span x-text="selectedUnlockYearLabel()"></span>
                                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                    </button>
                                    <div x-show="unlockYearOpen" x-cloak class="absolute z-50 mt-1 w-full bg-white border rounded shadow-lg">
                                        <input x-model="unlockYearSearch" type="text" placeholder="Search year..." class="w-full border-b px-3 py-2 text-sm">
                                        <div class="max-h-48 overflow-y-auto">
                                            <div
                                                @click="selectUnlockYear('')"
                                                class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50"
                                            >Select Year</div>
                                            <template x-for="year in filteredUnlockYears()" :key="'unlock-year-' + year.id">
                                                <div
                                                    @click="selectUnlockYear(String(year.id))"
                                                    class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50"
                                                    x-text="year.year_label"
                                                ></div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                                <input x-model="unlock.reason" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Required reason">
                            </div>
                        </div>
                        <button @click="submitAdminUnlock()" :disabled="unlockLoading" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm"><span x-show="!unlockLoading">Unpublish / Unlock</span><span x-show="unlockLoading">Processing...</span></button>
                        <div x-show="unlockMessage" class="p-3 rounded border" :class="unlockError ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'" x-text="unlockMessage"></div>
                    </section>
                </template>

                <template x-if="activeView === 'reports-exports'">
                    <section class="bg-white rounded-lg shadow p-6 space-y-5">
                        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">Reports & Exports</h2>
                                <p class="text-sm text-gray-600">National-grade reporting workspace for ACSEE published/draft outcomes with governed export trail.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <select x-model="reports.mode" @change="loadReportsOverview()" class="px-3 py-2 border rounded text-sm">
                                    <option value="published">Published Mode</option>
                                    <option value="draft">Draft Mode</option>
                                </select>
                                <div class="relative min-w-[170px]" @click.outside="reportsYearOpen = false">
                                    <button
                                        type="button"
                                        @click="reportsYearOpen = !reportsYearOpen"
                                        class="acsee-filter-button w-full px-3 py-2 border border-gray-300 rounded-none text-sm h-10 bg-white text-left flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        <span x-text="selectedYearLabel(reports.exam_year_id)"></span>
                                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                    </button>
                                    <div x-show="reportsYearOpen" x-cloak class="acsee-filter-menu absolute z-50 w-full bg-white border border-gray-300 shadow-lg">
                                        <input x-model="reportsYearSearch" type="text" placeholder="Search year..." class="acsee-filter-search w-full border-b border-gray-200 px-3 py-2 text-sm focus:outline-none">
                                        <div class="max-h-48 overflow-y-auto">
                                            <template x-for="year in filteredYearOptions(reportsYearSearch)" :key="'reports-year-' + year.id">
                                                <div @click="selectReportsYear(String(year.id))" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50" x-text="year.year_label"></div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <button @click="loadReportsOverview()" class="px-3 py-2 border rounded text-sm text-blue-700 border-blue-300 hover:bg-blue-50">Refresh</button>
                            </div>
                        </div>

                        <div x-show="reports.loading" class="text-center py-8"><i class="fas fa-spinner animate-spin text-blue-500"></i></div>

                        <div x-show="!reports.loading" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div class="p-3 border rounded bg-gray-50"><p class="text-xs text-gray-500">Candidates</p><p class="text-2xl font-bold text-gray-800" x-text="reports.summary.candidates ?? 0"></p></div>
                            <div class="p-3 border rounded bg-gray-50"><p class="text-xs text-gray-500">Schools</p><p class="text-2xl font-bold text-gray-800" x-text="reports.summary.schools ?? 0"></p></div>
                            <div class="p-3 border rounded bg-gray-50"><p class="text-xs text-gray-500">Mean GPA</p><p class="text-2xl font-bold text-gray-800" x-text="reports.summary.mean_gpa ?? '—'"></p></div>
                            <div class="p-3 border rounded bg-gray-50"><p class="text-xs text-gray-500">Mean AGGT</p><p class="text-2xl font-bold text-gray-800" x-text="reports.summary.mean_aggt ?? '—'"></p></div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <div class="border rounded-lg p-4 bg-indigo-50 border-indigo-200">
                                <p class="text-sm font-semibold text-indigo-900">Division Distribution</p>
                                <div class="mt-3 grid grid-cols-5 gap-2 text-center text-xs">
                                    <template x-for="label in ['1','2','3','4','0']" :key="'div-' + label">
                                        <div class="bg-white border rounded px-2 py-2">
                                            <p class="text-gray-500" x-text="'Div ' + label"></p>
                                            <p class="text-base font-bold text-indigo-700" x-text="reports.summary.division_counts?.[label] ?? 0"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <div class="border rounded-lg p-4 bg-emerald-50 border-emerald-200">
                                <p class="text-sm font-semibold text-emerald-900">Quick Report Actions</p>
                                <div class="mt-3 space-y-2 text-sm">
                                    <button @click="navigateToView('school-results')" class="w-full text-left px-3 py-2 rounded border bg-white hover:bg-emerald-50">School Performance Sheets</button>
                                    <button @click="navigateToView('candidate-results')" class="w-full text-left px-3 py-2 rounded border bg-white hover:bg-emerald-50">Candidate Statements</button>
                                    <button @click="navigateToView('exports')" class="w-full text-left px-3 py-2 rounded border bg-white hover:bg-emerald-50">Open Export Console</button>
                                </div>
                            </div>
                            <div class="border rounded-lg p-4 bg-amber-50 border-amber-200">
                                <p class="text-sm font-semibold text-amber-900">Scope</p>
                                <p class="text-sm text-amber-800 mt-2">
                                    <span class="font-semibold uppercase" x-text="reports.scope.scope_type || 'national'"></span>
                                    <span x-show="reports.scope.scope_id">#<span x-text="reports.scope.scope_id"></span></span>
                                </p>
                                <p class="text-xs text-amber-700 mt-2">All reports and exports are restricted to your authorized scope.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div class="border rounded-lg overflow-hidden">
                                <div class="px-4 py-3 bg-gray-50 border-b">
                                    <h3 class="font-semibold text-gray-800 text-sm">Top Schools by Candidate Count</h3>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="px-3 py-2 text-left">Code</th>
                                                <th class="px-3 py-2 text-left">School</th>
                                                <th class="px-3 py-2 text-left">District</th>
                                                <th class="px-3 py-2 text-right">Candidates</th>
                                                <th class="px-3 py-2 text-right">GPA</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(row, idx) in (reports.top_schools || [])" :key="'top-school-' + idx">
                                                <tr class="border-t hover:bg-green-50">
                                                    <td class="px-3 py-2 font-mono text-xs" x-text="row.code || '—'"></td>
                                                    <td class="px-3 py-2" x-text="row.name || '—'"></td>
                                                    <td class="px-3 py-2" x-text="row.district_name || '—'"></td>
                                                    <td class="px-3 py-2 text-right" x-text="row.candidates || 0"></td>
                                                    <td class="px-3 py-2 text-right" x-text="row.mean_gpa ? Number(row.mean_gpa).toFixed(2) : '—'"></td>
                                                </tr>
                                            </template>
                                            <tr x-show="!(reports.top_schools || []).length">
                                                <td colspan="5" class="px-3 py-4 text-center text-gray-500">No school summary rows.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="border rounded-lg overflow-hidden">
                                <div class="px-4 py-3 bg-gray-50 border-b">
                                    <h3 class="font-semibold text-gray-800 text-sm">Recent Export Activity</h3>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="px-3 py-2 text-left">Time</th>
                                                <th class="px-3 py-2 text-left">Actor</th>
                                                <th class="px-3 py-2 text-left">Type</th>
                                                <th class="px-3 py-2 text-left">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(row, idx) in (reports.recent_exports || [])" :key="'recent-export-' + idx">
                                                <tr class="border-t hover:bg-green-50">
                                                    <td class="px-3 py-2 text-xs" x-text="row.time || '—'"></td>
                                                    <td class="px-3 py-2" x-text="row.actor || '—'"></td>
                                                    <td class="px-3 py-2 text-xs uppercase" x-text="row.action || row.export_type"></td>
                                                    <td class="px-3 py-2">
                                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium border"
                                                              :class="row.status === 'completed' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-amber-100 text-amber-800 border-amber-200'"
                                                              x-text="row.status || 'completed'"></span>
                                                    </td>
                                                </tr>
                                            </template>
                                            <tr x-show="!(reports.recent_exports || []).length">
                                                <td colspan="4" class="px-3 py-4 text-center text-gray-500">No export audit entries.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </section>
                </template>

                <template x-if="activeView === 'school-results'">
                    <section class="bg-white rounded-lg shadow p-6 space-y-4">
                        <h2 class="text-xl font-bold text-gray-800">School Results</h2>
                        <div class="flex gap-2">
                            <input x-model="schoolSearch" type="text" placeholder="Search school" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <button @click="loadSchools()" class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm">Search</button>
                        </div>
                        <div x-show="schoolsLoading" class="text-center py-8"><i class="fas fa-spinner animate-spin text-blue-500"></i></div>
                        <div x-show="!schoolsLoading && schools.data && schools.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left">Code</th><th class="px-3 py-2 text-left">School</th><th class="px-3 py-2 text-left">Candidates</th><th class="px-3 py-2"></th></tr></thead>
                                <tbody>
                                    <template x-for="row in schools.data" :key="row.id"><tr class="border-t"><td class="px-3 py-2" x-text="row.code"></td><td class="px-3 py-2" x-text="row.name"></td><td class="px-3 py-2" x-text="row.candidates_count"></td><td class="px-3 py-2 text-right"><button @click="loadSchoolSheet(row.id)" class="px-2 py-1 bg-gray-800 text-white rounded text-xs">Sheet</button></td></tr></template>
                                </tbody>
                            </table>
                        </div>
                        <div x-show="selectedSchoolSheet" class="border rounded-lg p-3 bg-gray-50">
                            <p class="font-semibold" x-text="selectedSchoolSheet.school?.name"></p>
                            <p class="text-sm text-gray-600" x-text="'Rows: ' + (selectedSchoolSheet.sheet?.data?.length || 0)"></p>
                        </div>
                    </section>
                </template>

                <template x-if="activeView === 'candidate-results'">
                    <section class="bg-white rounded-lg shadow p-6 space-y-4">
                        <h2 class="text-xl font-bold text-gray-800">Candidate Results</h2>
                        <div class="flex gap-2">
                            <input x-model="candidateSearch" type="text" placeholder="Index number or name" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <button @click="loadCandidates()" class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm">Search</button>
                        </div>
                        <div x-show="candidatesLoading" class="text-center py-8"><i class="fas fa-spinner animate-spin text-blue-500"></i></div>
                        <div x-show="!candidatesLoading && candidates.data && candidates.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left">Index #</th><th class="px-3 py-2 text-left">Name</th><th class="px-3 py-2 text-left">School</th><th class="px-3 py-2 text-left">Grade</th><th class="px-3 py-2"></th></tr></thead>
                                <tbody>
                                    <template x-for="row in candidates.data" :key="row.id"><tr class="border-t"><td class="px-3 py-2" x-text="row.index_number"></td><td class="px-3 py-2" x-text="row.full_name"></td><td class="px-3 py-2" x-text="row.school_name"></td><td class="px-3 py-2" x-text="row.overall_grade"></td><td class="px-3 py-2 text-right"><button @click="loadCandidateStatement(row.candidate_pk)" class="px-2 py-1 bg-gray-800 text-white rounded text-xs">Statement</button></td></tr></template>
                                </tbody>
                            </table>
                        </div>
                        <div x-show="candidateStatement" class="border rounded-lg p-3 bg-gray-50">
                            <p class="font-semibold" x-text="candidateStatement.candidate?.full_name"></p>
                            <p class="text-sm text-gray-600" x-text="candidateStatement.candidate?.index_number"></p>
                        </div>
                    </section>
                </template>

                <template x-if="activeView === 'exports'">
                    <section class="bg-white rounded-lg shadow p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-800">Exports</h2>
                            <button @click="loadExportHistory()" class="px-3 py-1.5 rounded text-xs border border-blue-300 text-blue-700 hover:bg-blue-50">Refresh</button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-[160px_220px_220px] gap-3 justify-start">
                            <div class="w-full md:max-w-[160px]">
                                <div class="relative" @click.outside="exportsPanel.yearOpen = false">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Exam Year</label>
                                    <button
                                        @click="exportsPanel.yearOpen = !exportsPanel.yearOpen"
                                        class="acsee-filter-button w-full px-3 py-2 border border-gray-300 rounded-none focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm h-10 bg-white text-left flex items-center justify-between"
                                    >
                                        <span x-text="selectedExportYearLabel()"></span>
                                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                    </button>
                                    <div x-show="exportsPanel.yearOpen" x-cloak class="acsee-filter-menu absolute z-50 mt-1 w-full bg-white border border-gray-300 shadow-lg">
                                        <input x-model="exportsPanel.yearSearch" type="text" placeholder="Search year..." class="acsee-filter-search w-full border-b border-gray-200 px-3 py-2 text-sm focus:outline-none">
                                        <div class="max-h-48 overflow-y-auto">
                                            <template x-for="year in filteredExportYears()" :key="'exp-year-' + year.id">
                                                <div @click="selectExportYear(String(year.id))" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50" x-text="year.year_label"></div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="w-full md:max-w-[220px]">
                                <div class="relative" @click.outside="exportsPanel.regionOpen = false">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Region</label>
                                    <button
                                        @click="exportsPanel.regionOpen = !exportsPanel.regionOpen"
                                        class="acsee-filter-button w-full px-3 py-2 border border-gray-300 rounded-none focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm h-10 bg-white text-left flex items-center justify-between"
                                    >
                                        <span x-text="selectedExportRegionLabel()"></span>
                                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                    </button>
                                    <div x-show="exportsPanel.regionOpen" x-cloak class="acsee-filter-menu absolute z-50 mt-1 w-full bg-white border border-gray-300 shadow-lg">
                                        <input x-model="exportsPanel.regionSearch" type="text" placeholder="Search region..." class="acsee-filter-search w-full border-b border-gray-200 px-3 py-2 text-sm focus:outline-none">
                                        <div class="max-h-48 overflow-y-auto">
                                            <div @click="selectExportRegion('')" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50">All Regions</div>
                                            <template x-for="region in filteredExportRegions()" :key="'exp-region-' + region.id">
                                                <div @click="selectExportRegion(String(region.id))" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50" x-text="region.name"></div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="w-full md:max-w-[220px]">
                                <div class="relative" @click.outside="exportsPanel.districtOpen = false">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">District</label>
                                    <button
                                        @click="if (exportsPanel.region_id) exportsPanel.districtOpen = !exportsPanel.districtOpen"
                                        :disabled="!exportsPanel.region_id"
                                        class="acsee-filter-button w-full px-3 py-2 border border-gray-300 rounded-none focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm h-10 text-left flex items-center justify-between disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
                                        :class="exportsPanel.region_id ? 'bg-white' : 'bg-gray-100'"
                                    >
                                        <span x-text="selectedExportDistrictLabel()"></span>
                                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                    </button>
                                    <div x-show="exportsPanel.districtOpen" x-cloak class="acsee-filter-menu absolute z-50 mt-1 w-full bg-white border border-gray-300 shadow-lg">
                                        <input x-model="exportsPanel.districtSearch" type="text" placeholder="Search district..." class="acsee-filter-search w-full border-b border-gray-200 px-3 py-2 text-sm focus:outline-none">
                                        <div class="max-h-48 overflow-y-auto">
                                            <div @click="selectExportDistrict('')" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50">All Districts</div>
                                            <template x-for="district in filteredExportDistricts()" :key="'exp-district-' + district.id">
                                                <div @click="selectExportDistrict(String(district.id))" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50" x-text="district.name"></div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <div class="border rounded-lg p-4 bg-gray-50 space-y-2">
                                <p class="font-semibold text-gray-800">District-wise Centre Results</p>
                                <p class="text-xs text-gray-600">Downloads a ZIP containing one A3 portrait PDF per examination centre in the selected district, with full results header block.</p>
                                <div class="flex items-center gap-2">
                                    <button @click="downloadResultsExport('district_school_results','pdf')" :disabled="exportsPanel.downloading" class="px-3 py-2 rounded bg-red-600 text-white text-sm disabled:opacity-50">Download ZIP</button>
                                </div>
                            </div>
                        </div>

                        <div class="border rounded-lg p-4" :class="exportsPanel.readiness.ready ? 'bg-emerald-50 border-emerald-200' : 'bg-amber-50 border-amber-200'">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-gray-800">Export Readiness</p>
                                    <p class="text-xs mt-1" :class="exportsPanel.readiness.ready ? 'text-emerald-800' : 'text-amber-900'" x-text="exportsPanel.readiness.message || 'Readiness status unavailable.'"></p>
                                </div>
                                <button @click="loadExportReadiness()" class="px-3 py-1.5 rounded text-xs border border-blue-300 text-blue-700 hover:bg-blue-50">Refresh Check</button>
                            </div>

                            <div x-show="exportsPanel.readinessLoading" class="py-3 text-sm text-gray-500">
                                <i class="fas fa-spinner animate-spin"></i> Checking district export readiness...
                            </div>

                            <div x-show="!exportsPanel.readinessLoading && (exportsPanel.readiness.issues || []).length" class="mt-4 overflow-x-auto">
                                <table class="min-w-full text-sm border border-amber-200">
                                    <thead class="bg-amber-100">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Centre Code</th>
                                            <th class="px-3 py-2 text-left">Centre</th>
                                            <th class="px-3 py-2 text-left">Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="issue in (exportsPanel.readiness.issues || [])" :key="'exp-issue-' + issue.code + '-' + issue.reason">
                                            <tr class="border-t border-amber-200">
                                                <td class="px-3 py-2 font-semibold" x-text="issue.code"></td>
                                                <td class="px-3 py-2" x-text="issue.name"></td>
                                                <td class="px-3 py-2 text-amber-900" x-text="issue.reason"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <p x-show="exportsPanel.message" class="text-sm" :class="exportsPanel.error ? 'text-red-700' : 'text-emerald-700'" x-text="exportsPanel.message"></p>

                        <div class="border rounded-lg overflow-hidden">
                            <div class="px-4 py-3 bg-gray-50 border-b">
                                <h3 class="font-semibold text-gray-800 text-sm">Export History</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-3 py-2 text-left">ID</th>
                                            <th class="px-3 py-2 text-left">Time</th>
                                            <th class="px-3 py-2 text-left">Actor</th>
                                            <th class="px-3 py-2 text-left">Scope</th>
                                            <th class="px-3 py-2 text-left">Action</th>
                                            <th class="px-3 py-2 text-left">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="row in (exportsPanel.history.data || [])" :key="'exp-h-' + row.id">
                                            <tr class="border-t hover:bg-green-50">
                                                <td class="px-3 py-2" x-text="row.id"></td>
                                                <td class="px-3 py-2 text-xs" x-text="row.created_at || '—'"></td>
                                                <td class="px-3 py-2" x-text="row.user?.name || '—'"></td>
                                                <td class="px-3 py-2 uppercase text-xs" x-text="row.scope || '—'"></td>
                                                <td class="px-3 py-2 text-xs uppercase" x-text="row.action || row.export_type"></td>
                                                <td class="px-3 py-2">
                                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium border"
                                                          :class="row.status === 'completed' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-amber-100 text-amber-800 border-amber-200'"
                                                          x-text="row.status"></span>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr x-show="!(exportsPanel.history.data || []).length">
                                            <td colspan="6" class="px-3 py-4 text-center text-gray-500">No export records found.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </template>
            </div>
        </div>

        <div
            x-show="submission.publishModalOpen"
            x-transition.opacity
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @keydown.escape.window="submission.publishModalOpen = false"
        >
            <div class="absolute inset-0 bg-black/40" @click="submission.publishModalOpen = false"></div>
            <div class="relative w-full max-w-lg rounded-lg bg-white shadow-xl border border-gray-200">
                <div class="px-5 py-4 border-b">
                    <h3 class="text-base font-semibold text-gray-900">Confirm Publish Snapshot</h3>
                </div>
                <div class="px-5 py-4 text-sm text-gray-700 space-y-3">
                    <p>Publishing creates a new immutable snapshot version from latest completed draft results.</p>
                    <label class="flex items-start gap-2">
                        <input type="checkbox" x-model="submission.publishConfirm" class="mt-1 rounded border-gray-300">
                        <span>I understand publish creates immutable snapshot data and should be audited.</span>
                    </label>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Publish Notes (optional)</label>
                        <textarea x-model="submission.form.publish_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded text-sm" placeholder="Notes for audit trail"></textarea>
                    </div>
                </div>
                <div class="px-5 py-4 border-t flex items-center justify-end gap-2">
                    <button @click="submission.publishModalOpen = false" class="px-3 py-2 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button @click="confirmPublishSnapshot()" :disabled="submission.actionLoading || !submission.publishConfirm" class="px-3 py-2 text-sm rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50">Publish Snapshot</button>
                </div>
            </div>
        </div>

        <div
            x-show="submission.lockModalOpen"
            x-transition.opacity
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @keydown.escape.window="submission.lockModalOpen = false"
        >
            <div class="absolute inset-0 bg-black/40" @click="submission.lockModalOpen = false"></div>
            <div class="relative w-full max-w-lg rounded-lg bg-white shadow-xl border border-gray-200">
                <div class="px-5 py-4 border-b">
                    <h3 class="text-base font-semibold text-gray-900">Confirm Lock Published Results</h3>
                </div>
                <div class="px-5 py-4 text-sm text-gray-700 space-y-3">
                    <p>Locked results cannot be republished without Admin Unlock.</p>
                    <label class="flex items-start gap-2">
                        <input type="checkbox" x-model="submission.lockConfirm" class="mt-1 rounded border-gray-300">
                        <span>I understand locking restricts future publish actions.</span>
                    </label>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Lock Reason</label>
                        <input x-model="submission.form.lock_reason" type="text" class="w-full px-3 py-2 border border-gray-300 rounded text-sm" placeholder="Required reason">
                    </div>
                </div>
                <div class="px-5 py-4 border-t flex items-center justify-end gap-2">
                    <button @click="submission.lockModalOpen = false" class="px-3 py-2 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button @click="confirmLockSnapshot()" :disabled="submission.actionLoading || !submission.lockConfirm || !String(submission.form.lock_reason || '').trim()" class="px-3 py-2 text-sm rounded-lg bg-amber-600 text-white hover:bg-amber-700 disabled:opacity-50">Lock Results</button>
                </div>
            </div>
        </div>

        <div
            x-show="compute.confirm_modal_open"
            x-transition.opacity
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @keydown.escape.window="closeFinalComputeModal()"
        >
            <div class="absolute inset-0 bg-black/40" @click="closeFinalComputeModal()"></div>
            <div class="relative w-full max-w-md rounded-lg bg-white shadow-xl border border-gray-200">
                <div class="px-5 py-4 border-b">
                    <h3 class="text-base font-semibold text-gray-900">Confirm Final Compute</h3>
                </div>
                <div class="px-5 py-4 text-sm text-gray-700 space-y-2">
                    <p>Run FINAL compute for this scope?</p>
                    <p class="text-gray-600">This will overwrite computed result tables for the selected scope and year.</p>
                </div>
                <div class="px-5 py-4 border-t flex items-center justify-end gap-2">
                    <button
                        @click="closeFinalComputeModal()"
                        class="px-3 py-2 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        @click="confirmFinalCompute()"
                        :disabled="compute.running"
                        class="px-3 py-2 text-sm rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                        Confirm Run Final
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<style>
    .acsee-results-shell {
        padding-bottom: 1rem;
    }

    .acsee-lifecycle-shell {
        background: transparent;
    }

    .acsee-sidebar {
        background:
            linear-gradient(180deg, rgba(8, 19, 39, 0.98) 0%, rgba(15, 23, 42, 0.97) 38%, rgba(10, 32, 50, 0.98) 100%);
        border-right: 1px solid rgba(148, 163, 184, 0.16);
        box-shadow: 26px 0 60px rgba(15, 23, 42, 0.18);
    }

    .acsee-nav-link {
        display: block;
        border: 1px solid transparent;
        border-radius: 0.95rem;
        padding: 0.7rem 0.85rem;
        font-size: 0.92rem;
        font-weight: 500;
        line-height: 1.3;
        transition: transform 180ms ease, border-color 180ms ease, background-color 180ms ease, color 180ms ease, box-shadow 180ms ease;
    }

    .acsee-nav-link-idle {
        color: #cbd5e1;
    }

    .acsee-nav-link-idle:hover {
        color: #f8fafc;
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(255, 255, 255, 0.08);
        transform: translateX(4px);
    }

    .acsee-nav-link-active {
        color: #fff;
        background: linear-gradient(135deg, rgba(14, 165, 233, 0.92), rgba(37, 99, 235, 0.92));
        border-color: rgba(191, 219, 254, 0.65);
        box-shadow: 0 18px 32px rgba(37, 99, 235, 0.24);
    }

    .acsee-topbar {
        border-radius: 24px;
        box-shadow: 0 24px 54px rgba(15, 23, 42, 0.14);
    }

    .acsee-topbar-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(280px, 0.65fr);
        gap: 24px;
        align-items: start;
    }

    .acsee-topbar .registration-page-title {
        font-size: clamp(1.8rem, 3vw, 2.5rem);
        margin-bottom: 0.45rem;
    }

    .acsee-hero-subtitle {
        max-width: 56rem;
        font-size: 0.95rem;
        line-height: 1.65;
    }

    .acsee-topbar .registration-page-note {
        border-radius: 20px;
        padding: 16px 18px;
    }

    .acsee-topbar-side {
        display: grid;
        gap: 14px;
        align-content: start;
        justify-items: stretch;
    }

    .acsee-topbar .registration-page-highlights {
        margin-top: 16px;
    }

    .acsee-topbar .registration-page-chip {
        min-height: 34px;
        padding: 0 12px;
        font-size: 0.8rem;
    }

    .acsee-topbar .registration-page-note h2 {
        font-size: 0.95rem;
        margin-bottom: 6px;
    }

    .acsee-topbar .registration-page-note p {
        font-size: 0.84rem;
        line-height: 1.6;
    }

    .acsee-panel {
        background: rgba(255, 255, 255, 0.72);
        border: 1px solid rgba(255, 255, 255, 0.72);
        box-shadow: 0 26px 70px rgba(15, 23, 42, 0.08);
        backdrop-filter: blur(18px);
    }

    .acsee-hero-card {
        position: relative;
        overflow: hidden;
        background:
            radial-gradient(circle at top right, rgba(125, 211, 252, 0.42), transparent 30%),
            linear-gradient(135deg, #ffffff 0%, #eff8ff 50%, #ecfeff 100%);
        border: 1px solid rgba(186, 230, 253, 0.9);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
    }

    .acsee-hero-card::after {
        content: "";
        position: absolute;
        inset: auto -3rem -4rem auto;
        width: 14rem;
        height: 14rem;
        border-radius: 9999px;
        background: radial-gradient(circle, rgba(16, 185, 129, 0.18), transparent 68%);
        pointer-events: none;
    }

    .acsee-callout {
        background: linear-gradient(180deg, rgba(224, 242, 254, 0.95), rgba(240, 249, 255, 0.92));
        border: 1px solid rgba(125, 211, 252, 0.8);
        box-shadow: 0 18px 44px rgba(14, 165, 233, 0.12);
    }

    .acsee-stat-card,
    .acsee-metric-tile {
        border-radius: 1.1rem;
        border: 1px solid rgba(226, 232, 240, 0.95);
        background: rgba(255, 255, 255, 0.88);
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.06);
    }

    .acsee-stat-card {
        padding: 1.15rem;
    }

    .acsee-metric-tile {
        padding: 1rem;
    }

    .acsee-stat-label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #64748b;
    }

    .acsee-stat-value,
    .acsee-metric-value {
        margin-top: 0.45rem;
        font-size: clamp(1.65rem, 2vw, 2.35rem);
        line-height: 1.05;
        font-weight: 700;
        color: #0f172a;
    }

    .acsee-stat-note {
        margin-top: 0.55rem;
        font-size: 0.82rem;
        line-height: 1.5;
        color: #64748b;
    }

    .acsee-ready-panel {
        background:
            linear-gradient(180deg, rgba(236, 253, 245, 0.94), rgba(240, 253, 250, 0.92)),
            linear-gradient(135deg, rgba(52, 211, 153, 0.08), rgba(14, 165, 233, 0.05));
        border: 1px solid rgba(110, 231, 183, 0.9);
        box-shadow: 0 22px 55px rgba(16, 185, 129, 0.12);
    }

    .acsee-flow-panel {
        border: 1px solid rgba(226, 232, 240, 0.95);
        background: rgba(255, 255, 255, 0.88);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
    }

    .acsee-flow-step {
        border-radius: 18px;
        padding: 1rem;
        border: 1px solid rgba(226, 232, 240, 0.95);
        background: linear-gradient(180deg, rgba(248, 250, 252, 0.95), rgba(255, 255, 255, 0.96));
        min-height: 145px;
    }

    .acsee-flow-step-complete {
        border-color: rgba(110, 231, 183, 0.9);
        background: linear-gradient(180deg, rgba(236, 253, 245, 0.96), rgba(255, 255, 255, 0.98));
        box-shadow: 0 14px 28px rgba(16, 185, 129, 0.08);
    }

    .acsee-flow-step-pending {
        border-color: rgba(226, 232, 240, 0.95);
    }

    .acsee-flow-step-label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #64748b;
    }

    .acsee-flow-step-state {
        margin-top: 0.6rem;
        font-size: 1rem;
        font-weight: 700;
        line-height: 1.4;
        color: #0f172a;
    }

    .acsee-flow-step-note {
        margin-top: 0.55rem;
        font-size: 0.84rem;
        line-height: 1.55;
        color: #64748b;
    }

    .acsee-filter-input,
    .acsee-filter-button,
    .acsee-filter-menu,
    .acsee-filter-search {
        border-radius: 0 !important;
    }

    .acsee-results-shell select.acsee-square-select,
    .acsee-results-shell select.acsee-square-select:focus {
        border-radius: 0 !important;
    }

    .acsee-filter-menu {
        top: calc(100% - 1px) !important;
        overflow: hidden;
    }

    .acsee-filter-button {
        white-space: nowrap;
        position: relative;
    }

    .acsee-filter-button span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .acsee-filter-button:focus,
    .acsee-filter-button:focus-visible,
    .acsee-filter-search:focus,
    .acsee-filter-search:focus-visible {
        outline: none !important;
        box-shadow: inset 0 0 0 2px rgba(59, 130, 246, 0.7) !important;
    }

    .acsee-filter-menu .cursor-pointer {
        white-space: nowrap;
    }

    .acsee-hero-action {
        align-self: start;
        justify-self: start;
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        background: rgba(15, 23, 42, 0.22);
        box-shadow: 0 14px 26px rgba(15, 23, 42, 0.16);
        backdrop-filter: blur(8px);
    }

    .acsee-hero-action:hover {
        background: rgba(15, 23, 42, 0.34);
    }

    @media (max-width: 1024px) {
        .acsee-lifecycle-shell {
            flex-direction: column;
        }

        .acsee-sidebar {
            position: relative;
            top: 0;
            width: 100%;
            min-height: auto;
        }

        .acsee-topbar {
            top: 0;
        }

        .acsee-topbar-layout {
            grid-template-columns: 1fr;
        }

        .acsee-topbar-side {
            justify-items: start;
        }
    }
</style>

<script>
function resultsAcseeLifecycle() {
    const permissions = {
        canComputeValidate: @json($canComputeValidate),
        canAdminUnlock: @json($canAdminUnlock),
        canPreviewImpact: @json($canPreviewImpact),
    };

    return {
        // Pattern copied from Mark Entry lifecycle implementation:
        // - Sidebar links call navigateToView(viewKey)
        // - current view tracked in activeView
        // - initViewFromUrl() reads ?view=
        // - updateUrlState() writes ?view= via pushState
        // - handlePopState() syncs browser back/forward
        // - x-if ensures one active view rendered at a time
        activeView: 'overview',
        viewRegistry: {
            'overview': { label: '📋 Overview', category: 'Entry & Validation', lazy: true },
            'grading-system': { label: '⚙️ Grading System', category: 'Entry & Validation', lazy: true },
            'entry-validation': { label: '🧮 Compute / Validate', category: 'Entry & Validation', lazy: true },
            'computation-history': { label: '🗂️ Computation History', category: 'Entry & Validation', lazy: true },
            'moderation-review': { label: '📊 Review Overview', category: 'Moderation & Review', lazy: false },
            'review-dashboard': { label: '📈 Review Dashboard', category: 'Moderation & Review', lazy: true },
            'pending-review': { label: '⏳ Pending Review', category: 'Moderation & Review', lazy: true },
            'outliers-extremes': { label: '🚩 Outliers & Extremes (Final)', category: 'Moderation & Review', lazy: true },
            'submission-locking': { label: '🔒 Publish / Lock', category: 'Submission & Locking', lazy: false },
            'publish-lock': { label: '✅ Publish / Lock Action', category: 'Submission & Locking', lazy: false },
            'snapshots-versions': { label: '🧾 Snapshots / Versions', category: 'Submission & Locking', lazy: true },
            'admin-unlock': { label: '🔓 Admin Unlock', category: 'Submission & Locking', lazy: false },
            'reports-exports': { label: '📑 Reports Overview', category: 'Reports & Exports', lazy: false },
            'school-results': { label: '🏫 School Results', category: 'Reports & Exports', lazy: true },
            'candidate-results': { label: '👤 Candidate Results', category: 'Reports & Exports', lazy: true },
            'exports': { label: '📦 Exports', category: 'Reports & Exports', lazy: false },
        },

        permissions,
        canPreviewImpact: permissions.canPreviewImpact,

        summary: {},
        review: {},
        pending: {},
        schools: {},
        candidates: {},
        readySchools: {
            items: [],
            meta: { current_page: 1, last_page: 1, per_page: 50, total: 0, from: 0, to: 0 },
            search: '',
            region_id: '',
            district_id: '',
            regionOpen: false,
            districtOpen: false,
            regionSearch: '',
            districtSearch: '',
            regions: [],
            districts: [],
        },
        compute: {
            exam_year_id: '',
            region_id: '',
            district_id: '',
            school_id: '',
            regionOpen: false,
            districtOpen: false,
            schoolOpen: false,
            regionSearch: '',
            districtSearch: '',
            schoolSearch: '',
            schools: [],
            promote_marks: true,
            loading: false,
            running: false,
            poll_in_flight: false,
            poll_timer: null,
            auto_refresh_interval_ms: 5000,
            confirm_modal_open: false,
            error: false,
            message: '',
            readiness: null,
        },
        computeHistory: { data: [] },
        snapshots: { data: [] },
        statistics: null,
        reports: {
            exam_year_id: '',
            mode: 'published',
            loading: false,
            summary: {},
            top_schools: [],
            recent_exports: [],
            scope: { scope_type: 'national', scope_id: null },
        },
        exportsPanel: {
            exam_year_id: '',
            region_id: '',
            district_id: '',
            yearOpen: false,
            regionOpen: false,
            districtOpen: false,
            yearSearch: '',
            regionSearch: '',
            districtSearch: '',
            school_id: '',
            active_report_type: 'district_school_results',
            downloading: false,
            error: false,
            message: '',
            history: { data: [] },
            readinessLoading: false,
            readiness: { ready: true, issues: [], summary: { blocked_schools: 0 }, message: '' },
        },
        submission: {
            exam_year_id: '',
            loading: false,
            actionLoading: false,
            error: false,
            message: '',
            status: {
                preconditions: {},
                blockers: {},
                draft_counts: {},
                recent_actions: [],
                scope: { scope_type: 'national', scope_id: null },
            },
            publishModalOpen: false,
            lockModalOpen: false,
            publishConfirm: false,
            lockConfirm: false,
            form: {
                publish_notes: '',
                lock_reason: '',
                unlock_reason: '',
            },
            canPublishNow() {
                const s = this.status || {};
                const p = s.preconditions || {};
                return !!(s.can_publish && p.has_approved_locked_marks && p.latest_compute_completed && p.has_draft_rows && !s.is_locked);
            },
            canLockNow() {
                const s = this.status || {};
                return !!(s.can_lock && s.active_snapshot && !s.is_locked);
            },
            preconditionClass(flag) {
                return flag
                    ? 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border bg-emerald-100 text-emerald-800 border-emerald-200'
                    : 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border bg-red-100 text-red-800 border-red-200';
            },
            preconditionLabel(flag) {
                return flag ? 'OK' : 'Missing';
            },
        },
        selectedSchoolSheet: null,
        candidateStatement: null,

        summaryLoading: false,
        reviewLoading: false,
        pendingLoading: false,
        schoolsLoading: false,
        candidatesLoading: false,
        readySchoolsLoading: false,
        publishLoading: false,
        unlockLoading: false,
        gradingLoading: false,
        previewLoading: false,
        ruleActionLoading: false,
        computeHistoryLoading: false,
        snapshotsLoading: false,
        statisticsLoading: false,

        schoolSearch: '',
        candidateSearch: '',
        examYears: [],
        publish: { exam_year_id: '' },
        publishYearOpen: false,
        publishYearSearch: '',
        unlock: { exam_year_id: '', reason: '' },
        unlockYearOpen: false,
        unlockYearSearch: '',
        gradingYearOpen: false,
        gradingYearSearch: '',
        computeYearOpen: false,
        computeYearSearch: '',
        submissionYearOpen: false,
        submissionYearSearch: '',
        reportsYearOpen: false,
        reportsYearSearch: '',
        publishMessage: '',
        publishError: false,
        unlockMessage: '',
        unlockError: false,

        grading: {
            meta: {},
            permissions: { can_edit: false, can_activate: false, can_lock: false, can_preview: false },
            config: null,
            exam_year_id: '',
            rules: [],
            gpa: { settings: {}, grade_points: [] },
            divisions: { rules: [] },
            competence_levels: { rules: [] },
            warnings: [],
        },
        gradingTab: 'grading',
        editMode: false,
        gradingValidation: { errors: [], warnings: [] },
        gradingLog: {},
        preview: {
            exam_year_id: '',
            region_id: '',
            council_id: '',
            school_id: '',
            sample_size: '100',
        },
        previewResult: null,
        rulesNotes: null,
        rulesLoading: false,
        rulesOpen: true,
        outliers: {
            loading: false,
            error: '',
            activeTab: 'candidates',
            bulkApproving: false,
            bulkApproveModalOpen: false,
            bulkApproveAcknowledge: false,
            bulkApproveNote: '',
            bulkApproveResult: null,
            yearOpen: false,
            regionOpen: false,
            districtOpen: false,
            yearSearch: '',
            regionSearch: '',
            districtSearch: '',
            filters: { exam_year_id: '', region_id: '', district_id: '', council_id: '', school_id: '', q: '' },
            summary: {},
            regions: [],
            districts: [],
            candidates: { data: [], meta: { total: 0, per_page: 25, current_page: 1, last_page: 1, from: 0, to: 0 } },
            schools: { data: [], meta: { total: 0, per_page: 25, current_page: 1, last_page: 1, from: 0, to: 0 } },
            subjects: { subject_distribution: [], division_distribution: [], missing_withheld: {} },
            currentRows() {
                return this.activeTab === 'schools' ? this.schools.data : this.candidates.data;
            },
            currentMeta() {
                return this.activeTab === 'schools' ? this.schools.meta : this.candidates.meta;
            },
            pageWindow() {
                const meta = this.currentMeta();
                const current = meta.current_page || 1;
                const last = meta.last_page || 1;
                const size = 9;
                let start = Math.max(1, current - Math.floor(size / 2));
                let end = Math.min(last, start + size - 1);
                start = Math.max(1, end - size + 1);
                const pages = [];
                for (let p = start; p <= end; p++) pages.push(p);
                return pages;
            },
        },

        menuClass(viewKey, activeClass, inactiveClass) {
            return this.activeView === viewKey ? activeClass : inactiveClass;
        },

        displayViewLabel(viewKey) {
            const raw = this.viewRegistry[viewKey]?.label || 'Unknown View';
            return String(raw).replace(/^[^\p{L}\p{N}(]+/u, '').trim();
        },

        saveViewState() {
            try {
                localStorage.setItem('irms_acsee_results_state', JSON.stringify({
                    activeView: this.activeView,
                    timestamp: Date.now(),
                }));
            } catch (_) {}
        },

        restoreViewState() {
            try {
                const stored = localStorage.getItem('irms_acsee_results_state');
                if (!stored) return;
                const state = JSON.parse(stored);
                if (state.activeView && this.viewRegistry[state.activeView]) {
                    this.activeView = state.activeView;
                }
            } catch (_) {}
        },

        activeExamYearLabel() {
            const candidates = [
                this.compute?.exam_year_id,
                this.submission?.exam_year_id,
                this.reports?.exam_year_id,
                this.exportsPanel?.exam_year_id,
                this.grading?.exam_year_id,
                this.publish?.exam_year_id,
                this.unlock?.exam_year_id,
            ].filter(Boolean);
            const activeId = candidates[0];
            const found = (this.examYears || []).find(y => String(y.id) === String(activeId));
            return found?.year_label || 'Active cycle';
        },

        workflowStageComplete(stage) {
            const readyCandidates = Number(this.summary?.ready_queue?.candidates || 0);
            const readyRows = Number(this.summary?.ready_queue?.subject_marks_rows || 0);
            const latestComputeCompleted = !!this.submission?.status?.preconditions?.latest_compute_completed;
            const hasDraftRows = !!this.submission?.status?.preconditions?.has_draft_rows;
            const activeSnapshot = !!this.submission?.status?.active_snapshot;
            const lockedSnapshot = !!this.submission?.status?.is_locked;

            if (stage === 'approved') return readyCandidates > 0;
            if (stage === 'locked') return !!this.submission?.status?.preconditions?.has_approved_locked_marks;
            if (stage === 'promoted') return readyRows > 0;
            if (stage === 'computed') return latestComputeCompleted && hasDraftRows;
            if (stage === 'published') return activeSnapshot;
            if (stage === 'snapshot_locked') return lockedSnapshot;
            return false;
        },

        workflowStageStatus(stage) {
            if (stage === 'approved') {
                return this.workflowStageComplete(stage)
                    ? `${Number(this.summary?.ready_queue?.candidates || 0).toLocaleString()} candidates ready`
                    : 'Waiting for approved batches';
            }
            if (stage === 'locked') {
                return this.workflowStageComplete(stage)
                    ? `${Number(this.summary?.ready_queue?.batches || 0).toLocaleString()} locked batches`
                    : 'No locked batches in scope';
            }
            if (stage === 'promoted') {
                return this.workflowStageComplete(stage)
                    ? `${Number(this.summary?.ready_queue?.subject_marks_rows || 0).toLocaleString()} rows promoted`
                    : 'No promoted marks yet';
            }
            if (stage === 'computed') {
                return this.workflowStageComplete(stage)
                    ? (this.submission?.status?.latest_process?.status || 'completed')
                    : 'Compute pending';
            }
            if (stage === 'published') {
                return this.workflowStageComplete(stage)
                    ? (this.submission?.status?.active_snapshot?.version || 'published')
                    : 'No active snapshot';
            }
            if (stage === 'snapshot_locked') {
                return this.workflowStageComplete(stage) ? 'Locked' : 'Open';
            }
            return 'Unknown';
        },

        workflowStageClass(stage) {
            return this.workflowStageComplete(stage)
                ? 'acsee-flow-step-complete'
                : 'acsee-flow-step-pending';
        },

        overviewLatestProcess() {
            return (this.summary?.latest_processes || [])[0] || null;
        },

        overviewRecentAction() {
            return (this.submission?.status?.recent_actions || [])[0] || null;
        },

        formatDateTime(value) {
            if (!value) return '—';
            try {
                return new Intl.DateTimeFormat('en-GB', {
                    year: 'numeric',
                    month: 'short',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                }).format(new Date(value));
            } catch (_) {
                return value;
            }
        },

        async init() {
            if (!permissions.canComputeValidate) {
                delete this.viewRegistry['entry-validation'];
            }
            if (!permissions.canAdminUnlock) {
                delete this.viewRegistry['admin-unlock'];
            }

            this.restoreViewState();
            await this.loadExamYears();
            await this.loadReadyFilterOptions();
            this.initViewFromUrl();
            window.addEventListener('popstate', () => this.handlePopState());

            if (!this.viewRegistry[this.activeView]) {
                this.activeView = 'overview';
            }
            this.updatePageTitle(this.activeView);
            this.saveViewState();
            this.onViewEnter(this.activeView);
        },

        navigateToView(viewKey) {
            if (!this.viewRegistry[viewKey]) return;
            this.activeView = viewKey;
            this.updateUrlState(viewKey);
            this.updatePageTitle(viewKey);
            this.saveViewState();
            this.onViewEnter(viewKey);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        updateUrlState(viewKey) {
            const url = new URL(window.location);
            url.searchParams.set('view', viewKey);
            window.history.pushState({ view: viewKey }, '', url);
            this.saveViewState();
        },

        initViewFromUrl() {
            const url = new URL(window.location);
            const viewParam = url.searchParams.get('view');
            if (viewParam && this.viewRegistry[viewParam]) {
                this.activeView = viewParam;
                this.updatePageTitle(viewParam);
                this.saveViewState();
            }
        },

        handlePopState() {
            const url = new URL(window.location);
            const viewParam = url.searchParams.get('view');
            if (viewParam && this.viewRegistry[viewParam]) {
                this.activeView = viewParam;
                this.onViewEnter(viewParam);
                this.updatePageTitle(viewParam);
                this.saveViewState();
            }
        },

        updatePageTitle(viewKey) {
            const label = this.displayViewLabel(viewKey) || 'ACSEE Results';
            document.title = `${label} - ACSEE Results - IRMS`;
        },

        onViewEnter(viewKey) {
            if (viewKey === 'overview') {
                this.loadSummary();
                this.fetchRulesNotes();
                this.loadReadySchools(1);
                this.fetchSubmissionStatus();
            }
            if (viewKey === 'entry-validation') {
                this.loadComputeReadiness();
                this.startComputeAutoRefresh();
            } else {
                this.stopComputeAutoRefresh();
            }
            if (viewKey === 'computation-history') this.loadComputeHistory();
            if (viewKey === 'review-dashboard') this.loadReviewDashboard();
            if (viewKey === 'moderation-review') this.loadResultStatistics('published');
            if (viewKey === 'pending-review') this.loadPendingReview();
            if (viewKey === 'outliers-extremes') this.loadFinalOutliers();
            if (viewKey === 'submission-locking') this.fetchSubmissionStatus();
            if (viewKey === 'snapshots-versions') this.loadSnapshots();
            if (viewKey === 'reports-exports') this.loadReportsOverview();
            if (viewKey === 'school-results') this.loadSchools();
            if (viewKey === 'candidate-results') this.loadCandidates();
            if (viewKey === 'exports') {
                this.loadExportHistory();
                this.loadExportReadiness();
            }
            if (viewKey === 'grading-system') {
                this.fetchRulesNotes();
                this.loadGradingConfig();
                this.loadGradingLog();
            }
        },

        gradingTabTitle() {
            if (this.gradingTab === 'grading') return 'NECTA Grade Boundaries';
            if (this.gradingTab === 'gpa') return 'Institutional GPA Settings';
            if (this.gradingTab === 'divisions') return 'Division by AGGT Rules';
            return 'Competence Bands';
        },

        async loadExamYears() {
            try {
                const res = await fetch('/api/exam-years');
                const data = await res.json();
                this.examYears = Array.isArray(data) ? data : (data.exam_years || []);
                if (this.examYears.length > 0) {
                    const defaultYear = this.examYears[0].id;
                    this.publish.exam_year_id = this.publish.exam_year_id || defaultYear;
                    this.unlock.exam_year_id = this.unlock.exam_year_id || defaultYear;
                    this.submission.exam_year_id = this.submission.exam_year_id || defaultYear;
                    this.reports.exam_year_id = this.reports.exam_year_id || defaultYear;
                    this.exportsPanel.exam_year_id = this.exportsPanel.exam_year_id || defaultYear;
                    this.grading.exam_year_id = this.grading.exam_year_id || defaultYear;
                    this.preview.exam_year_id = this.preview.exam_year_id || defaultYear;
                    this.compute.exam_year_id = this.compute.exam_year_id || defaultYear;
                }
            } catch (_) {}
        },

        selectedPublishYearLabel() {
            const found = (this.examYears || []).find(y => String(y.id) === String(this.publish.exam_year_id));
            return found?.year_label || 'Select Year';
        },

        selectedYearLabel(value) {
            const found = (this.examYears || []).find(y => String(y.id) === String(value));
            return found?.year_label || 'Select Year';
        },

        filteredYearOptions(search) {
            const q = String(search || '').trim().toLowerCase();
            const years = this.examYears || [];
            if (!q) return years;
            return years.filter(y => String(y.year_label || '').toLowerCase().includes(q));
        },

        selectGradingYear(value) {
            this.grading.exam_year_id = String(value || '');
            this.gradingYearOpen = false;
            this.gradingYearSearch = '';
            this.loadGradingConfig();
        },

        selectComputeYear(value) {
            this.compute.exam_year_id = String(value || '');
            this.computeYearOpen = false;
            this.computeYearSearch = '';
            this.compute.school_id = '';
            this.compute.schoolSearch = '';
            this.compute.schoolOpen = false;
            this.compute.schools = [];
            this.loadReadyFilterOptions();
            if (this.compute.district_id) {
                this.loadComputeSchools();
            }
            this.loadComputeReadiness();
        },

        selectSubmissionYear(value) {
            this.submission.exam_year_id = String(value || '');
            this.submissionYearOpen = false;
            this.submissionYearSearch = '';
            this.fetchSubmissionStatus();
        },

        selectReportsYear(value) {
            this.reports.exam_year_id = String(value || '');
            this.reportsYearOpen = false;
            this.reportsYearSearch = '';
            this.loadReportsOverview();
        },

        filteredPublishYears() {
            const q = String(this.publishYearSearch || '').trim().toLowerCase();
            const years = this.examYears || [];
            if (!q) return years;
            return years.filter(y => String(y.year_label || '').toLowerCase().includes(q));
        },

        selectPublishYear(value) {
            this.publish.exam_year_id = String(value || '');
            this.publishYearOpen = false;
        },

        selectedUnlockYearLabel() {
            const found = (this.examYears || []).find(y => String(y.id) === String(this.unlock.exam_year_id));
            return found?.year_label || 'Select Year';
        },

        filteredUnlockYears() {
            const q = String(this.unlockYearSearch || '').trim().toLowerCase();
            const years = this.examYears || [];
            if (!q) return years;
            return years.filter(y => String(y.year_label || '').toLowerCase().includes(q));
        },

        selectUnlockYear(value) {
            this.unlock.exam_year_id = String(value || '');
            this.unlockYearOpen = false;
        },

        async loadSummary() {
            this.summaryLoading = true;
            try {
                const res = await fetch('/api/results/acsee/summary');
                const data = await res.json();
                this.summary = data.data || {};
            } finally {
                this.summaryLoading = false;
            }
        },

        async loadReviewDashboard() {
            this.reviewLoading = true;
            try {
                const res = await fetch('/api/results/acsee/review-dashboard');
                const data = await res.json();
                this.review = data.data || {};
            } finally {
                this.reviewLoading = false;
            }
        },

        async loadReadySchools(page = 1) {
            this.readySchoolsLoading = true;
            try {
                const params = new URLSearchParams();
                params.set('page', String(page));
                params.set('per_page', String(this.readySchools.meta.per_page || 50));
                if (this.readySchools.search) params.set('search', this.readySchools.search);
                if (this.readySchools.region_id) params.set('region_id', String(this.readySchools.region_id));
                if (this.readySchools.district_id) params.set('district_id', String(this.readySchools.district_id));
                const res = await fetch(`/api/results/acsee/ready-schools?${params.toString()}`);
                const json = await res.json();
                const payload = json.data || {};
                this.readySchools.items = payload.data || [];
                this.readySchools.meta = {
                    current_page: payload.current_page || 1,
                    last_page: payload.last_page || 1,
                    per_page: payload.per_page || 50,
                    total: payload.total || 0,
                    from: payload.from || 0,
                    to: payload.to || 0,
                };
            } finally {
                this.readySchoolsLoading = false;
            }
        },

        async loadReadyFilterOptions() {
            try {
                const [regionsRes, districts] = await Promise.all([
                    fetch('/api/regions'),
                    this.fetchAcseeDistrictOptions(),
                ]);
                const regionsJson = await regionsRes.json();

                const regions = Array.isArray(regionsJson) ? regionsJson : (regionsJson.data || []);

                this.readySchools.regions = (regions || [])
                    .map(r => ({ id: r.id, name: r.name }))
                    .filter(r => r.id && r.name);
                this.readySchools.districts = (districts || [])
                    .map(d => ({ id: d.id, name: d.name, region_id: d.region_id }))
                    .filter(d => d.id && d.name);
                if (this.readySchools.district_id) {
                    const exists = this.readySchools.districts.some(d => String(d.id) === String(this.readySchools.district_id));
                    if (!exists) {
                        this.readySchools.district_id = '';
                    }
                }
                if (this.exportsPanel.district_id) {
                    const exists = this.readySchools.districts.some(d => String(d.id) === String(this.exportsPanel.district_id));
                    if (!exists) {
                        this.exportsPanel.district_id = '';
                    }
                }
            } catch (_) {
                this.readySchools.regions = [];
                this.readySchools.districts = [];
            }
        },

        async fetchAcseeDistrictOptions() {
            const examYearId = String(this.compute.exam_year_id || this.exportsPanel.exam_year_id || '');
            const [allDistricts, schools] = await Promise.all([
                this.fetchAllDistrictOptions(),
                examYearId ? this.fetchAcseeSchoolOptions(examYearId) : Promise.resolve([]),
            ]);

            if (!examYearId) {
                return allDistricts;
            }

            const allowedDistrictIds = new Set(
                (schools || [])
                    .map((school) => String(school.district_id || ''))
                    .filter((value) => value !== '')
            );

            return (allDistricts || []).filter((district) => allowedDistrictIds.has(String(district.id)));
        },

        async fetchAllDistrictOptions(regionId = null) {
            const all = [];
            let page = 1;
            let totalPages = 1;
            const pageSize = 200;

            do {
                const params = new URLSearchParams({
                    page: String(page),
                    page_size: String(pageSize),
                });
                if (regionId) params.set('region_id', String(regionId));

                const res = await fetch(`/api/districts?${params.toString()}`);
                const json = await res.json();

                const rows = Array.isArray(json) ? json : (json.data || []);
                all.push(...rows);

                const pagination = json.pagination || {};
                totalPages = Number(pagination.total_pages || 1);
                page += 1;
            } while (page <= totalPages);

            return all;
        },

        async fetchAcseeSchoolOptions(examYearId) {
            const all = [];
            let page = 1;
            let totalPages = 1;
            const pageSize = 200;

            do {
                const params = new URLSearchParams({
                    exam_year_id: String(examYearId),
                    per_page: String(pageSize),
                    page: String(page),
                });

                const res = await fetch(`/api/results/acsee/schools?${params.toString()}`);
                const json = await res.json();
                const payload = json.data || {};
                const rows = Array.isArray(payload.data) ? payload.data : [];
                all.push(...rows);

                totalPages = Number(payload.last_page || 1);
                page += 1;
            } while (page <= totalPages);

            return all;
        },

        selectedReadyRegionLabel() {
            if (!this.readySchools.region_id) return 'Region...';
            const match = (this.readySchools.regions || []).find(r => String(r.id) === String(this.readySchools.region_id));
            return match?.name || 'Region...';
        },

        filteredReadyRegions() {
            const q = String(this.readySchools.regionSearch || '').trim().toLowerCase();
            const rows = this.readySchools.regions || [];
            if (!q) return rows;
            return rows.filter(r => String(r.name || '').toLowerCase().includes(q));
        },

        selectReadyRegion(value) {
            this.readySchools.region_id = String(value || '');
            this.readySchools.regionOpen = false;
            this.readySchools.regionSearch = '';

            // Reset district selection when region changes.
            this.readySchools.district_id = '';
            this.readySchools.districtSearch = '';
            this.readySchools.districtOpen = false;

            this.loadReadySchools(1);
        },

        selectedReadyDistrictLabel() {
            if (!this.readySchools.district_id) return 'District...';
            const match = (this.readySchools.districts || []).find(d => String(d.id) === String(this.readySchools.district_id));
            return match?.name || 'District...';
        },

        filteredReadyDistricts() {
            const q = String(this.readySchools.districtSearch || '').trim().toLowerCase();
            let rows = this.readySchools.districts || [];
            if (this.readySchools.region_id) {
                rows = rows.filter(d => String(d.region_id) === String(this.readySchools.region_id));
            }
            if (!q) return rows;
            return rows.filter(d => String(d.name || '').toLowerCase().includes(q));
        },

        selectReadyDistrict(value) {
            this.readySchools.district_id = String(value || '');
            this.readySchools.districtOpen = false;
            this.readySchools.districtSearch = '';
            this.loadReadySchools(1);
        },

        selectedComputeRegionLabel() {
            if (!this.compute.region_id) return 'All Regions';
            const match = (this.readySchools.regions || []).find(r => String(r.id) === String(this.compute.region_id));
            return match?.name || 'All Regions';
        },

        filteredComputeRegions() {
            const q = String(this.compute.regionSearch || '').trim().toLowerCase();
            const rows = this.readySchools.regions || [];
            if (!q) return rows;
            return rows.filter(r => String(r.name || '').toLowerCase().includes(q));
        },

        async selectComputeRegion(value) {
            this.compute.region_id = String(value || '');
            this.compute.regionOpen = false;
            this.compute.regionSearch = '';
            this.compute.district_id = '';
            this.compute.school_id = '';
            this.compute.districtSearch = '';
            this.compute.schoolSearch = '';
            this.compute.districtOpen = false;
            this.compute.schoolOpen = false;
            this.compute.schools = [];
            await this.loadComputeReadiness();
        },

        computeDistrictOptions() {
            let rows = this.readySchools.districts || [];
            if (!this.compute.region_id) return [];
            return rows.filter(d => String(d.region_id) === String(this.compute.region_id));
        },

        selectedComputeDistrictLabel() {
            if (!this.compute.region_id) return 'Select Region First';
            if (!this.compute.district_id) return 'All Districts';
            const match = this.computeDistrictOptions().find(d => String(d.id) === String(this.compute.district_id));
            return match?.name || 'All Districts';
        },

        filteredComputeDistricts() {
            const q = String(this.compute.districtSearch || '').trim().toLowerCase();
            const rows = this.computeDistrictOptions();
            if (!q) return rows;
            return rows.filter(d => String(d.name || '').toLowerCase().includes(q));
        },

        async selectComputeDistrict(value) {
            this.compute.district_id = String(value || '');
            this.compute.districtOpen = false;
            this.compute.districtSearch = '';
            this.compute.school_id = '';
            this.compute.schoolSearch = '';
            this.compute.schoolOpen = false;
            this.compute.schools = [];
            if (this.compute.district_id) {
                await this.loadComputeSchools();
            }
            await this.loadComputeReadiness();
        },

        selectedComputeSchoolLabel() {
            if (!this.compute.district_id) return 'Select District First';
            if (!this.compute.school_id) return 'All Schools In District';
            const match = (this.compute.schools || []).find(s => String(s.id) === String(this.compute.school_id));
            return match ? (match.code ? `${match.code} - ${match.name}` : match.name) : 'All Schools In District';
        },

        filteredComputeSchools() {
            const q = String(this.compute.schoolSearch || '').trim().toLowerCase();
            const rows = this.compute.schools || [];
            if (!q) return rows;
            return rows.filter(s => `${s.code || ''} ${s.name || ''}`.toLowerCase().includes(q));
        },

        async selectComputeSchool(value) {
            this.compute.school_id = String(value || '');
            this.compute.schoolOpen = false;
            this.compute.schoolSearch = '';
            await this.loadComputeReadiness();
        },

        async loadComputeSchools() {
            if (!this.compute.exam_year_id || !this.compute.district_id) {
                this.compute.schools = [];
                return;
            }

            const params = new URLSearchParams({
                exam_year_id: String(this.compute.exam_year_id),
                mode: 'draft',
                district_id: String(this.compute.district_id),
                per_page: '200',
            });

            if (this.compute.region_id) {
                params.set('region_id', String(this.compute.region_id));
            }

            const res = await fetch(`/api/results/acsee/schools?${params.toString()}`);
            const data = await res.json();
            const rows = data.data?.data || [];
            this.compute.schools = rows.map(s => ({
                id: s.id,
                code: s.code,
                name: s.name,
            }));
        },

        onExportRegionChanged() {
            if (!this.exportsPanel.region_id) {
                this.exportsPanel.district_id = '';
            }

            const currentDistrict = String(this.exportsPanel.district_id || '');
            if (!currentDistrict) return;

            const allowed = this.exportDistrictOptions().some(
                d => String(d.id) === currentDistrict
            );

            if (!allowed) {
                this.exportsPanel.district_id = '';
            }
        },

        selectedExportYearLabel() {
            const found = (this.examYears || []).find(y => String(y.id) === String(this.exportsPanel.exam_year_id));
            return found?.year_label || 'Select Year';
        },

        filteredExportYears() {
            const q = String(this.exportsPanel.yearSearch || '').trim().toLowerCase();
            const rows = this.examYears || [];
            if (!q) return rows;
            return rows.filter(y => String(y.year_label || '').toLowerCase().includes(q));
        },

        selectExportYear(value) {
            this.exportsPanel.exam_year_id = String(value || '');
            this.exportsPanel.yearOpen = false;
            this.exportsPanel.yearSearch = '';
            this.loadReadyFilterOptions();
            this.loadExportReadiness();
        },

        selectedExportRegionLabel() {
            if (!this.exportsPanel.region_id) return 'All Regions';
            const found = (this.readySchools.regions || []).find(r => String(r.id) === String(this.exportsPanel.region_id));
            return found?.name || 'All Regions';
        },

        filteredExportRegions() {
            const q = String(this.exportsPanel.regionSearch || '').trim().toLowerCase();
            const rows = this.readySchools.regions || [];
            if (!q) return rows;
            return rows.filter(r => String(r.name || '').toLowerCase().includes(q));
        },

        selectExportRegion(value) {
            this.exportsPanel.region_id = String(value || '');
            this.exportsPanel.regionOpen = false;
            this.exportsPanel.regionSearch = '';
            this.onExportRegionChanged();
            this.loadExportReadiness();
        },

        exportDistrictOptions() {
            let rows = this.readySchools.districts || [];
            if (!this.exportsPanel.region_id) return [];

            rows = rows.filter(
                d => String(d.region_id) === String(this.exportsPanel.region_id)
            );
            return rows;
        },

        selectedExportDistrictLabel() {
            if (!this.exportsPanel.region_id) return 'Select Region First';
            if (!this.exportsPanel.district_id) return 'All Districts';
            const found = this.exportDistrictOptions().find(d => String(d.id) === String(this.exportsPanel.district_id));
            return found?.name || 'All Districts';
        },

        filteredExportDistricts() {
            const q = String(this.exportsPanel.districtSearch || '').trim().toLowerCase();
            const rows = this.exportDistrictOptions() || [];
            if (!q) return rows;
            return rows.filter(d => String(d.name || '').toLowerCase().includes(q));
        },

        selectExportDistrict(value) {
            this.exportsPanel.district_id = String(value || '');
            this.exportsPanel.districtOpen = false;
            this.exportsPanel.districtSearch = '';
            this.loadExportReadiness();
        },

        async loadPendingReview() {
            this.pendingLoading = true;
            try {
                const res = await fetch('/api/results/acsee/pending-review');
                const data = await res.json();
                this.pending = data.data || {};
            } finally {
                this.pendingLoading = false;
            }
        },

        startComputeAutoRefresh() {
            this.stopComputeAutoRefresh();
            if (this.activeView !== 'entry-validation') return;

            const tick = async () => {
                if (document.hidden) return;
                await this.loadComputeReadiness({ silent: true, fromPoll: true });
                if ((this.compute.readiness?.processing?.in_progress_runs || 0) > 0) {
                    await this.loadSummary();
                }
            };

            this.compute.poll_timer = window.setInterval(
                tick,
                Math.max(3000, Number(this.compute.auto_refresh_interval_ms || 10000))
            );
        },

        stopComputeAutoRefresh() {
            if (this.compute.poll_timer) {
                window.clearInterval(this.compute.poll_timer);
                this.compute.poll_timer = null;
            }
            this.compute.poll_in_flight = false;
        },

        async loadComputeReadiness(options = {}) {
            const silent = !!options.silent;
            const fromPoll = !!options.fromPoll;
            if (!this.compute.exam_year_id) return;
            if (fromPoll && (this.compute.loading || this.compute.running || this.compute.poll_in_flight)) return;

            if (fromPoll) this.compute.poll_in_flight = true;
            if (!silent) {
                this.compute.loading = true;
                this.compute.error = false;
                this.compute.message = '';
            }
            try {
                const params = new URLSearchParams({ exam_year_id: String(this.compute.exam_year_id) });
                if (this.compute.region_id) params.set('region_id', String(this.compute.region_id));
                if (this.compute.district_id) params.set('district_id', String(this.compute.district_id));
                if (this.compute.school_id) params.set('school_id', String(this.compute.school_id));
                const res = await fetch(`/api/results/acsee/compute-validate/readiness?${params.toString()}`);
                const data = await res.json();
                if (!res.ok || !data.success) {
                    if (!silent) {
                        this.compute.error = true;
                        this.compute.message = data.message || 'Failed to validate compute readiness.';
                        this.compute.readiness = null;
                    }
                } else {
                    this.compute.readiness = data.data || null;
                }
            } catch (_) {
                if (!silent) {
                    this.compute.error = true;
                    this.compute.message = 'Failed to validate compute readiness.';
                    this.compute.readiness = null;
                }
            } finally {
                if (!silent) this.compute.loading = false;
                if (fromPoll) this.compute.poll_in_flight = false;
            }
        },

        async runComputeValidate(runType) {
            if (!this.compute.exam_year_id || this.compute.running) return;
            if (runType === 'final') {
                this.compute.confirm_modal_open = true;
                return;
            }
            return this.executeComputeValidate(runType);
        },

        closeFinalComputeModal() {
            this.compute.confirm_modal_open = false;
        },

        async confirmFinalCompute() {
            if (this.compute.running) return;
            this.compute.confirm_modal_open = false;
            await this.executeComputeValidate('final');
        },

        async executeComputeValidate(runType) {
            this.compute.running = true;
            this.compute.error = false;
            this.compute.message = '';

            try {
                const res = await fetch('/api/results/acsee/compute-validate/run', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({
                        exam_year_id: this.compute.exam_year_id,
                        run_type: runType,
                        promote_marks: !!this.compute.promote_marks,
                        region_id: this.compute.region_id || null,
                        district_id: this.compute.district_id || null,
                        school_id: this.compute.school_id || null,
                    })
                });
                const data = await res.json();
                this.compute.error = !res.ok || !data.success;
                this.compute.message = data.message || (this.compute.error ? 'Compute/validate failed.' : 'Compute/validate completed.');
                await this.loadComputeReadiness();
                await this.loadSummary();
            } catch (_) {
                this.compute.error = true;
                this.compute.message = 'Compute/validate failed.';
            } finally {
                this.compute.running = false;
            }
        },

        async loadComputeHistory() {
            if (!this.compute.exam_year_id) return;
            this.computeHistoryLoading = true;
            try {
                const params = new URLSearchParams({
                    exam_year_id: String(this.compute.exam_year_id),
                    per_page: '50',
                });
                const res = await fetch(`/api/results/acsee/compute-validate/processes?${params.toString()}`);
                const data = await res.json();
                this.computeHistory = data.data || { data: [] };
            } finally {
                this.computeHistoryLoading = false;
            }
        },

        async loadSnapshots() {
            if (!this.compute.exam_year_id) return;
            this.snapshotsLoading = true;
            try {
                const params = new URLSearchParams({
                    exam_year_id: String(this.compute.exam_year_id),
                    per_page: '50',
                });
                const res = await fetch(`/api/results/acsee/snapshots?${params.toString()}`);
                const data = await res.json();
                this.snapshots = data.data || { data: [] };
            } finally {
                this.snapshotsLoading = false;
            }
        },

        submissionBadgeClass(status) {
            const normalized = String(status || '').toLowerCase();
            if (normalized === 'completed') return 'bg-emerald-100 text-emerald-800 border-emerald-200';
            if (normalized === 'failed') return 'bg-red-100 text-red-800 border-red-200';
            if (['in_progress', 'running', 'pending'].includes(normalized)) return 'bg-amber-100 text-amber-800 border-amber-200';
            return 'bg-gray-100 text-gray-700 border-gray-200';
        },

        async fetchSubmissionStatus() {
            if (!this.submission.exam_year_id) return;
            this.submission.loading = true;
            this.submission.message = '';
            this.submission.error = false;
            try {
                const params = new URLSearchParams({
                    exam_year_id: String(this.submission.exam_year_id),
                });
                const res = await fetch(`/api/results/acsee/submission-locking/status?${params.toString()}`);
                const json = await res.json();
                if (!res.ok || !json.success) {
                    this.submission.error = true;
                    this.submission.message = json.message || 'Failed to load submission/locking status.';
                    return;
                }
                this.submission.status = json.data || this.submission.status;
                this.compute.exam_year_id = this.submission.exam_year_id;
                this.publish.exam_year_id = this.submission.exam_year_id;
                this.unlock.exam_year_id = this.submission.exam_year_id;
            } catch (_) {
                this.submission.error = true;
                this.submission.message = 'Failed to load submission/locking status.';
            } finally {
                this.submission.loading = false;
            }
        },

        openPublishModal() {
            this.submission.publishConfirm = false;
            this.submission.form.publish_notes = '';
            this.submission.publishModalOpen = true;
        },

        openLockModal() {
            this.submission.lockConfirm = false;
            this.submission.form.lock_reason = '';
            this.submission.lockModalOpen = true;
        },

        async confirmPublishSnapshot() {
            this.submission.actionLoading = true;
            this.submission.error = false;
            this.submission.message = '';
            try {
                const res = await fetch('/api/results/acsee/publish', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({
                        exam_year_id: this.submission.exam_year_id,
                        publish_notes: this.submission.form.publish_notes || null,
                    })
                });
                const json = await res.json();
                this.submission.error = !res.ok || !json.success;
                this.submission.message = json.message || (this.submission.error ? 'Failed to publish snapshot.' : 'Snapshot published.');
                this.submission.publishModalOpen = false;
                await this.fetchSubmissionStatus();
                await this.loadSnapshots();
                await this.loadSummary();
            } catch (_) {
                this.submission.error = true;
                this.submission.message = 'Failed to publish snapshot.';
            } finally {
                this.submission.actionLoading = false;
            }
        },

        async confirmLockSnapshot() {
            this.submission.actionLoading = true;
            this.submission.error = false;
            this.submission.message = '';
            try {
                const res = await fetch('/api/results/acsee/lock', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({
                        exam_year_id: this.submission.exam_year_id,
                        reason: String(this.submission.form.lock_reason || '').trim(),
                    })
                });
                const json = await res.json();
                this.submission.error = !res.ok || !json.success;
                this.submission.message = json.message || (this.submission.error ? 'Failed to lock snapshot.' : 'Snapshot locked.');
                this.submission.lockModalOpen = false;
                await this.fetchSubmissionStatus();
                await this.loadSnapshots();
                await this.loadSummary();
            } catch (_) {
                this.submission.error = true;
                this.submission.message = 'Failed to lock snapshot.';
            } finally {
                this.submission.actionLoading = false;
            }
        },

        async adminUnlockFromSubmission() {
            const reason = window.prompt('Admin unlock reason is required:');
            if (!reason || String(reason).trim().length < 3) {
                this.submission.error = true;
                this.submission.message = 'Admin unlock reason is required (minimum 3 characters).';
                return;
            }
            this.submission.actionLoading = true;
            this.submission.error = false;
            this.submission.message = '';
            try {
                const res = await fetch('/api/results/acsee/admin-unlock', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({
                        exam_year_id: this.submission.exam_year_id,
                        reason: String(reason).trim(),
                    })
                });
                const json = await res.json();
                this.submission.error = !res.ok || !json.success;
                this.submission.message = json.message || (this.submission.error ? 'Failed to unlock snapshot.' : 'Snapshot unlocked.');
                await this.fetchSubmissionStatus();
                await this.loadSnapshots();
                await this.loadSummary();
            } catch (_) {
                this.submission.error = true;
                this.submission.message = 'Failed to unlock snapshot.';
            } finally {
                this.submission.actionLoading = false;
            }
        },

        async loadResultStatistics(mode = 'published') {
            if (!this.compute.exam_year_id) return;
            this.statisticsLoading = true;
            try {
                const params = new URLSearchParams({
                    exam_year_id: String(this.compute.exam_year_id),
                    scope_type: 'national',
                    mode,
                });
                const res = await fetch(`/api/results/acsee/statistics?${params.toString()}`);
                const data = await res.json();
                this.statistics = data.data || null;
            } finally {
                this.statisticsLoading = false;
            }
        },

        async loadReportsOverview() {
            if (!this.reports.exam_year_id) return;
            this.reports.loading = true;
            try {
                const params = new URLSearchParams({
                    exam_year_id: String(this.reports.exam_year_id),
                    mode: this.reports.mode || 'published',
                });
                const res = await fetch(`/api/results/acsee/reports/overview?${params.toString()}`);
                const json = await res.json();
                const payload = json.data || {};
                this.reports.summary = payload.summary || {};
                this.reports.top_schools = payload.top_schools || [];
                this.reports.recent_exports = payload.recent_exports || [];
                this.reports.scope = payload.scope || { scope_type: 'national', scope_id: null };
            } finally {
                this.reports.loading = false;
            }
        },

        async loadExportHistory() {
            if (!this.exportsPanel.exam_year_id) return;
            try {
                const params = new URLSearchParams({
                    exam_year_id: String(this.exportsPanel.exam_year_id),
                    per_page: '50',
                });
                const res = await fetch(`/api/results/acsee/exports/history?${params.toString()}`);
                const json = await res.json();
                this.exportsPanel.history = json.data || { data: [] };
            } catch (_) {
                this.exportsPanel.history = { data: [] };
            }
        },

        async loadExportReadiness() {
            if (!this.exportsPanel.exam_year_id) return;
            this.exportsPanel.readinessLoading = true;
            try {
                const params = new URLSearchParams({
                    exam_year_id: String(this.exportsPanel.exam_year_id),
                });
                if (this.exportsPanel.district_id) {
                    params.set('district_id', String(this.exportsPanel.district_id));
                }
                const res = await fetch(`/api/results/acsee/exports/readiness?${params.toString()}`);
                const json = await res.json();
                this.exportsPanel.readiness = json.data || { ready: true, issues: [], summary: { blocked_schools: 0 }, message: '' };
            } catch (_) {
                this.exportsPanel.readiness = {
                    ready: true,
                    issues: [],
                    summary: { blocked_schools: 0 },
                    message: 'Unable to load export readiness right now.',
                };
            } finally {
                this.exportsPanel.readinessLoading = false;
            }
        },

        async downloadResultsExport(reportType, format) {
            if (this.exportsPanel.downloading || !this.exportsPanel.exam_year_id) return;
            this.exportsPanel.active_report_type = String(reportType || '');
            if (reportType === 'district_school_results') {
                this.exportsPanel.school_id = '';
            }
            this.exportsPanel.downloading = true;
            this.exportsPanel.error = false;
            this.exportsPanel.message = '';
            let downloadedFilename = null;
            try {
                const payload = {
                    exam_year_id: this.exportsPanel.exam_year_id,
                    report_type: reportType,
                    format,
                    mode: 'draft',
                    region_id: this.exportsPanel.region_id || null,
                    district_id: this.exportsPanel.district_id || null,
                    school_id: this.exportsPanel.school_id || null,
                };

                const res = await fetch('/api/results/acsee/exports/download', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify(payload)
                });

                const contentType = res.headers.get('Content-Type') || '';
                if (!res.ok || contentType.includes('application/json')) {
                    let msg = 'Export failed.';
                    try {
                        const j = await res.json();
                        msg = j.message || msg;
                    } catch (_) {}
                    this.exportsPanel.error = true;
                    this.exportsPanel.message = msg;
                    return;
                }

                const blob = await res.blob();
                const disposition = res.headers.get('Content-Disposition') || '';
                const matched = disposition.match(/filename=\"?([^\";]+)\"?/i);
                const filename = matched?.[1] || `acsee-export-${Date.now()}.${format}`;

                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);

                downloadedFilename = filename;
                this.exportsPanel.error = false;
                this.exportsPanel.message = `Export downloaded: ${filename}`;

                // Refresh panels in background; failures here should not flip a successful export to failed.
                Promise.allSettled([
                    this.loadExportHistory(),
                    this.loadReportsOverview(),
                ]);
            } catch (_) {
                if (downloadedFilename) {
                    this.exportsPanel.error = false;
                    this.exportsPanel.message = `Export downloaded: ${downloadedFilename}`;
                } else {
                    this.exportsPanel.error = true;
                    this.exportsPanel.message = 'Export failed.';
                }
            } finally {
                this.exportsPanel.downloading = false;
            }
        },

        async loadSchools() {
            this.schoolsLoading = true;
            try {
                const params = new URLSearchParams();
                if (this.schoolSearch) params.set('search', this.schoolSearch);
                const res = await fetch(`/api/results/acsee/schools?${params.toString()}`);
                const data = await res.json();
                this.schools = data.data || {};
            } finally {
                this.schoolsLoading = false;
            }
        },

        async loadCandidates() {
            this.candidatesLoading = true;
            try {
                const params = new URLSearchParams();
                if (this.candidateSearch) params.set('search', this.candidateSearch);
                const res = await fetch(`/api/results/acsee/candidates?${params.toString()}`);
                const data = await res.json();
                this.candidates = data.data || {};
            } finally {
                this.candidatesLoading = false;
            }
        },

        async loadSchoolSheet(schoolId) {
            const res = await fetch(`/api/results/acsee/school/${schoolId}/sheet`);
            const data = await res.json();
            this.selectedSchoolSheet = data.data || null;
        },

        async loadCandidateStatement(candidateId) {
            const res = await fetch(`/api/results/acsee/candidate/${candidateId}/statement`);
            const data = await res.json();
            this.candidateStatement = data.data || null;
        },

        async submitPublishLock() {
            this.publishLoading = true;
            this.publishMessage = '';
            this.publishError = false;
            try {
                const res = await fetch('/api/results/acsee/publish-lock', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify(this.publish)
                });
                const data = await res.json();
                this.publishError = !res.ok || !data.success;
                this.publishMessage = data.message || 'Failed to publish/lock results.';
            } catch (_) {
                this.publishError = true;
                this.publishMessage = 'Failed to publish/lock results.';
            } finally {
                this.publishLoading = false;
            }
        },

        async submitAdminUnlock() {
            this.unlockLoading = true;
            this.unlockMessage = '';
            this.unlockError = false;
            try {
                const res = await fetch('/api/results/acsee/admin-unlock', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify(this.unlock)
                });
                const data = await res.json();
                this.unlockError = !res.ok || !data.success;
                this.unlockMessage = data.message || 'Failed to unpublish/unlock results.';
            } catch (_) {
                this.unlockError = true;
                this.unlockMessage = 'Failed to unpublish/unlock results.';
            } finally {
                this.unlockLoading = false;
            }
        },

        addRule() {
            this.grading.rules.push({
                grade: '',
                name: '',
                min_mark: 0,
                max_mark: 0,
                points: null,
                is_principal: false,
                is_subsidiary: false,
                sort_order: this.grading.rules.length,
                is_disabled: false,
            });
        },

        addTabRule() {
            if (!this.editMode || !this.grading.permissions.can_edit) return;
            if (this.gradingTab === 'grading') return this.addRule();
            if (this.gradingTab === 'gpa') {
                this.grading.gpa.grade_points.push({ grade: '', gpa_point_value: 0 });
                return;
            }
            if (this.gradingTab === 'divisions') {
                this.grading.divisions.rules.push({ division_label: '', min_points: 0, max_points: 0, notes: '', sort_order: this.grading.divisions.rules.length, is_disabled: false });
                return;
            }
            this.grading.competence_levels.rules.push({ level_label: '', min_value: 0, max_value: 0, basis: 'GPA', color_code: '', sort_order: this.grading.competence_levels.rules.length, is_disabled: false });
        },

        async loadGradingConfig() {
            this.gradingLoading = true;
            try {
                const params = new URLSearchParams({ exam_year_id: this.grading.exam_year_id });
                const res = await fetch(`/api/results/acsee/grading/config?${params.toString()}`);
                const data = await res.json();
                const payload = data.data || {};
                this.grading.meta = payload.meta || {};
                this.grading.permissions = payload.permissions || { can_edit: false, can_activate: false, can_lock: false, can_preview: false };
                this.grading.config = payload.grading?.config || null;
                this.grading.rules = payload.grading?.rules || [];
                this.grading.gpa = payload.gpa || { settings: {}, grade_points: [] };
                this.grading.divisions = payload.divisions || { rules: [] };
                this.grading.competence_levels = payload.competence_levels || { rules: [] };
                this.grading.warnings = payload.warnings || [];
                this.gradingValidation.errors = [];
                this.preview.exam_year_id = this.grading.exam_year_id;
            } finally {
                this.gradingLoading = false;
            }
        },

        async validateConfig() {
            this.ruleActionLoading = true;
            try {
                const params = new URLSearchParams({ exam_year_id: this.grading.exam_year_id, config_id: this.grading.config?.id || '' });
                const res = await fetch(`/api/results/acsee/grading/validate?${params.toString()}`);
                const data = await res.json();
                this.gradingValidation = data.data || { errors: [], warnings: [] };
            } catch (_) {
                this.gradingValidation = { errors: ['Failed to validate setup.'], warnings: [] };
            } finally {
                this.ruleActionLoading = false;
            }
        },

        async saveRules() {
            if (!this.grading.permissions.can_edit) return;
            this.ruleActionLoading = true;
            try {
                const payload = {
                    config_id: this.grading.config?.id || null,
                    exam_year_id: this.grading.exam_year_id,
                    name: this.grading.config?.name || `ACSEE ${this.grading.exam_year_id} Profile`,
                    description: this.grading.config?.description || null,
                    grading_rules: this.grading.rules,
                    gpa_settings: this.grading.gpa.settings || {},
                    gpa_grade_points: this.grading.gpa.grade_points || [],
                    division_rules: this.grading.divisions.rules || [],
                    competence_rules: this.grading.competence_levels.rules || [],
                };
                const res = await fetch('/api/results/acsee/grading/config/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    this.gradingValidation.errors = [data.message || 'Failed to save grading config.'];
                } else {
                    await this.loadGradingConfig();
                    await this.loadGradingLog();
                }
            } finally {
                this.ruleActionLoading = false;
            }
        },

        async activateConfig() {
            if (!this.grading.permissions.can_activate || !this.grading.config?.id) return;
            this.ruleActionLoading = true;
            try {
                await fetch('/api/results/acsee/grading/config/activate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ profile_id: this.grading.config.id })
                });
                await this.loadGradingConfig();
                await this.loadGradingLog();
            } finally {
                this.ruleActionLoading = false;
            }
        },

        async lockConfig() {
            if (!this.grading.permissions.can_lock || !this.grading.config?.id) return;
            this.ruleActionLoading = true;
            try {
                const reason = window.prompt('Lock reason is required for audit:');
                if (!reason || reason.trim().length < 3) {
                    this.gradingValidation.errors = ['Lock reason is required (minimum 3 characters).'];
                    return;
                }
                await fetch('/api/results/acsee/grading/config/lock', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ profile_id: this.grading.config.id, reason: reason.trim() })
                });
                await this.loadGradingConfig();
                await this.loadGradingLog();
            } finally {
                this.ruleActionLoading = false;
            }
        },

        async loadGradingLog() {
            const params = new URLSearchParams({ exam_year_id: this.grading.exam_year_id });
            const res = await fetch(`/api/results/acsee/grading/changelog?${params.toString()}`);
            const data = await res.json();
            this.gradingLog = data.data || {};
        },

        async runPreviewImpact() {
            if (!(this.canPreviewImpact && this.grading.permissions.can_preview)) return;
            this.previewLoading = true;
            this.previewResult = null;
            try {
                const payload = {
                    exam_year_id: this.preview.exam_year_id,
                    config_id: this.grading.config?.id || null,
                    scope: {
                        region_id: this.preview.region_id || null,
                        council_id: this.preview.council_id || null,
                        school_id: this.preview.school_id || null,
                    },
                    sample_size: this.preview.sample_size || 'ALL',
                };

                const res = await fetch('/api/results/acsee/grading/preview-impact', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.previewResult = data.data;
                } else {
                    this.gradingValidation.errors = [data.message || 'Dry-run preview failed.'];
                }
            } finally {
                this.previewLoading = false;
            }
        },

        async fetchRulesNotes() {
            if (this.rulesLoading) return;
            this.rulesLoading = true;
            try {
                const res = await fetch('/api/results/acsee/rules-notes', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.rulesNotes = data.data || null;
                }
            } finally {
                this.rulesLoading = false;
            }
        },

        async loadRegionsForOutliers() {
            try {
                const [regionsRes, districts] = await Promise.all([
                    fetch('/api/regions'),
                    this.fetchAllDistrictOptions(),
                ]);
                const regionsJson = await regionsRes.json();
                const regions = Array.isArray(regionsJson) ? regionsJson : (regionsJson.data || []);

                this.outliers.regions = (regions || [])
                    .map(r => ({ id: r.id, name: r.name }))
                    .filter(r => r.id && r.name);
                this.outliers.districts = (districts || [])
                    .map(d => ({ id: d.id, name: d.name, region_id: d.region_id || d.regionId || null }))
                    .filter(d => d.id && d.name);
            } catch (_) {
                this.outliers.regions = [];
                this.outliers.districts = [];
            }
        },

        outlierDistrictOptions() {
            const all = this.outliers.districts || [];
            const regionId = Number(this.outliers.filters.region_id || 0);
            if (!regionId) return [];
            return all.filter(d => Number(d.region_id || 0) === regionId);
        },

        selectedOutlierYearLabel() {
            const found = (this.examYears || []).find(y => String(y.id) === String(this.outliers.filters.exam_year_id));
            return found?.year_label || 'All Years';
        },

        selectedOutlierRegionLabel() {
            const found = (this.outliers.regions || []).find(r => String(r.id) === String(this.outliers.filters.region_id));
            return found?.name || 'All Regions';
        },

        selectedOutlierDistrictLabel() {
            if (!this.outliers.filters.region_id) {
                return 'Select Region First';
            }
            const found = (this.outliers.districts || []).find(d => String(d.id) === String(this.outliers.filters.district_id));
            return found?.name || 'All Districts';
        },

        filteredOutlierYears() {
            const q = String(this.outliers.yearSearch || '').trim().toLowerCase();
            const years = this.examYears || [];
            if (!q) return years;
            return years.filter(y => String(y.year_label || '').toLowerCase().includes(q));
        },

        filteredOutlierRegions() {
            const q = String(this.outliers.regionSearch || '').trim().toLowerCase();
            const regions = this.outliers.regions || [];
            if (!q) return regions;
            return regions.filter(r => String(r.name || '').toLowerCase().includes(q));
        },

        filteredOutlierDistricts() {
            const q = String(this.outliers.districtSearch || '').trim().toLowerCase();
            const districts = this.outlierDistrictOptions() || [];
            if (!q) return districts;
            return districts.filter(d => String(d.name || '').toLowerCase().includes(q));
        },

        async selectOutlierYear(id) {
            this.outliers.filters.exam_year_id = id ? String(id) : '';
            this.outliers.yearOpen = false;
            await this.applyOutliersFilters();
        },

        async selectOutlierRegion(id) {
            this.outliers.filters.region_id = id ? String(id) : '';
            this.outliers.regionOpen = false;
            // reset district if it no longer belongs to the selected region
            const districtId = Number(this.outliers.filters.district_id || 0);
            if (districtId && !(this.outlierDistrictOptions() || []).some(d => Number(d.id) === districtId)) {
                this.outliers.filters.district_id = '';
            }
            await this.applyOutliersFilters();
        },

        async selectOutlierDistrict(id) {
            this.outliers.filters.district_id = id ? String(id) : '';
            this.outliers.districtOpen = false;
            await this.applyOutliersFilters();
        },

        openFinalOutliersApproveModal() {
            this.outliers.bulkApproveAcknowledge = false;
            this.outliers.bulkApproveNote = '';
            this.outliers.bulkApproveResult = null;
            this.outliers.bulkApproveModalOpen = true;
        },

        closeFinalOutliersApproveModal() {
            if (this.outliers.bulkApproving) return;
            this.outliers.bulkApproveModalOpen = false;
            this.outliers.bulkApproveAcknowledge = false;
            this.outliers.bulkApproveNote = '';
            this.outliers.bulkApproveResult = null;
        },

        outliersParams(page = null) {
            const params = new URLSearchParams();
            const f = this.outliers.filters;
            if (f.exam_year_id) params.set('exam_year_id', f.exam_year_id);
            if (f.region_id) params.set('region_id', f.region_id);
            if (f.district_id) params.set('district_id', f.district_id);
            if (f.council_id) params.set('council_id', f.council_id);
            if (f.school_id) params.set('school_id', f.school_id);
            if (f.q) params.set('q', f.q);
            const activeMeta = this.outliers.currentMeta();
            params.set('per_page', activeMeta.per_page || 25);
            params.set('page', page || activeMeta.current_page || 1);
            return params.toString();
        },

        normalizeMeta(meta) {
            return {
                total: Number(meta?.total || 0),
                per_page: Number(meta?.per_page || 25),
                current_page: Number(meta?.current_page || 1),
                last_page: Number(meta?.last_page || 1),
                from: Number(meta?.from || 0),
                to: Number(meta?.to || 0),
            };
        },

        async loadFinalOutliers() {
            this.outliers.loading = true;
            this.outliers.error = '';
            try {
                if ((this.outliers.regions || []).length === 0 || (this.outliers.districts || []).length === 0) {
                    await this.loadRegionsForOutliers();
                }
                this.outliers.filters.exam_year_id = this.outliers.filters.exam_year_id || this.publish.exam_year_id || '';

                const summaryRes = await fetch(`/api/results/acsee/outliers/summary?${this.outliersParams(1)}`);
                const summaryJson = await summaryRes.json();
                this.outliers.summary = summaryJson.data || {};

                const candidatesRes = await fetch(`/api/results/acsee/outliers/candidates?${this.outliersParams(this.outliers.candidates.meta.current_page || 1)}`);
                const candidatesJson = await candidatesRes.json();
                this.outliers.candidates.data = candidatesJson.data || [];
                this.outliers.candidates.meta = this.normalizeMeta(candidatesJson.meta);

                const schoolsRes = await fetch(`/api/results/acsee/outliers/schools?${this.outliersParams(this.outliers.schools.meta.current_page || 1)}`);
                const schoolsJson = await schoolsRes.json();
                this.outliers.schools.data = schoolsJson.data || [];
                this.outliers.schools.meta = this.normalizeMeta(schoolsJson.meta);

                const subjectsRes = await fetch(`/api/results/acsee/outliers/subjects?${this.outliersParams(1)}`);
                const subjectsJson = await subjectsRes.json();
                this.outliers.subjects = subjectsJson.data || { subject_distribution: [], division_distribution: [], missing_withheld: {} };
            } catch (e) {
                this.outliers.error = 'Failed to load final outliers: ' + e.message;
            } finally {
                this.outliers.loading = false;
            }
        },

        async applyOutliersFilters() {
            this.outliers.candidates.meta.current_page = 1;
            this.outliers.schools.meta.current_page = 1;
            await this.loadFinalOutliers();
        },

        async goToOutliersPage(page) {
            const target = Number(page);
            const meta = this.outliers.currentMeta();
            if (!target || target < 1 || target > (meta.last_page || 1) || target === meta.current_page) {
                return;
            }
            if (this.outliers.activeTab === 'schools') {
                this.outliers.schools.meta.current_page = target;
            } else {
                this.outliers.candidates.meta.current_page = target;
            }
            await this.loadFinalOutliers();
        },

        async exportOutliers(format) {
            try {
                const endpoint = format === 'xlsx'
                    ? '/api/results/acsee/outliers/export/xlsx'
                    : '/api/results/acsee/outliers/export/pdf';
                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify(this.outliers.filters),
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const blob = await res.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = format === 'xlsx'
                    ? `acsee-final-outliers-${format}.csv`
                    : `acsee-final-outliers-${format}.pdf`;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
            } catch (e) {
                this.outliers.error = 'Export failed: ' + e.message;
            }
        },

        async approveAllFinalOutlierFlags() {
            if (this.outliers.bulkApproving) return;
            if (!this.outliers.bulkApproveAcknowledge) return;

            this.outliers.bulkApproving = true;
            try {
                const res = await fetch('/api/results/acsee/outliers/approve-all', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        exam_year_id: this.outliers.filters.exam_year_id || null,
                        region_id: this.outliers.filters.region_id || null,
                        district_id: this.outliers.filters.district_id || null,
                        council_id: this.outliers.filters.council_id || null,
                        school_id: this.outliers.filters.school_id || null,
                        q: this.outliers.filters.q || null,
                        active_tab: this.outliers.activeTab || 'candidates',
                        note: this.outliers.bulkApproveNote || null,
                    }),
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.message || `HTTP ${res.status}`);
                }

                this.outliers.bulkApproveResult = {
                    ok: true,
                    message: data.message || 'Final outliers flags approved successfully.',
                    stats: data.stats || {},
                    reasons: Array.isArray(data.reasons) ? data.reasons : [],
                };
                await this.loadFinalOutliers();
                this.closeFinalOutliersApproveModal();
            } catch (e) {
                this.outliers.bulkApproveResult = {
                    ok: false,
                    message: 'Failed to approve final outlier flags: ' + e.message,
                    stats: {},
                    reasons: [],
                };
            } finally {
                this.outliers.bulkApproving = false;
            }
        },
    };
}
</script>
@endsection
