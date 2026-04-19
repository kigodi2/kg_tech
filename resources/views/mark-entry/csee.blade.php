@extends('layout')

@section('content')
@include('registration.partials.theme')

<div class="registration-shell mark-entry-shell" x-data="cseeMarkEntryPage()" x-init="init()" x-cloak>
    <div class="w-full flex flex-col lg:flex-row gap-0 lg:gap-4">
        <aside class="mark-entry-sidebar hidden lg:block w-64 text-white min-h-screen sticky top-[140px] overflow-y-auto">
            <div class="mark-entry-sidebar-inner flex flex-col h-full">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h2 class="text-xl font-bold">CSEE Marks</h2>
                    <p class="text-xs text-gray-400 mt-1">Lifecycle Management</p>
                </div>

                <nav class="flex-1 overflow-y-auto py-6 space-y-8">

                <div class="mb-8">
                    <h3 class="px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Entry & Validation</h3>
                    <ul class="space-y-2">
                        <template x-for="tab in entryTabs" :key="tab.key">
                            <li>
                                <a href="#" @click.prevent="setActiveTab(tab.key)" :class="activeTab === tab.key ? 'sidebar-item-active' : 'text-gray-300 hover:bg-gray-800'" class="sidebar-link px-6 py-2 flex items-center gap-3 text-sm transition-colors">
                                    <span class="mark-entry-sidebar-link-inner">
                                        <i :class="tab.icon" class="w-4 mark-entry-sidebar-icon"></i>
                                        <span x-text="tab.label"></span>
                                    </span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>

                <div class="mb-8">
                    <h3 class="px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Moderation</h3>
                    <ul class="space-y-2">
                        <template x-for="tab in moderationTabs" :key="tab.key">
                            <li>
                                <a href="#" @click.prevent="setActiveTab(tab.key)" :class="activeTab === tab.key ? 'sidebar-item-active' : 'text-gray-300 hover:bg-gray-800'" class="sidebar-link px-6 py-2 flex items-center gap-3 text-sm transition-colors">
                                    <span class="mark-entry-sidebar-link-inner">
                                        <i :class="tab.icon" class="w-4 mark-entry-sidebar-icon"></i>
                                        <span x-text="tab.label"></span>
                                    </span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>

                <div class="mb-8">
                    <h3 class="px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Submission</h3>
                    <ul class="space-y-2">
                        <template x-for="tab in lockingTabs" :key="tab.key">
                            <li>
                                <a href="#" @click.prevent="setActiveTab(tab.key)" :class="activeTab === tab.key ? 'sidebar-item-active' : 'text-gray-300 hover:bg-gray-800'" class="sidebar-link px-6 py-2 flex items-center gap-3 text-sm transition-colors">
                                    <span class="mark-entry-sidebar-link-inner">
                                        <i :class="tab.icon" class="w-4 mark-entry-sidebar-icon"></i>
                                        <span x-text="tab.label"></span>
                                    </span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>

                <div class="mb-8">
                    <h3 class="px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Reports</h3>
                    <ul class="space-y-2">
                        <template x-for="tab in reportTabs" :key="tab.key">
                            <li>
                                <a href="#" @click.prevent="setActiveTab(tab.key)" :class="activeTab === tab.key ? 'sidebar-item-active' : 'text-gray-300 hover:bg-gray-800'" class="sidebar-link px-6 py-2 flex items-center gap-3 text-sm transition-colors">
                                    <span class="mark-entry-sidebar-link-inner">
                                        <i :class="tab.icon" class="w-4 mark-entry-sidebar-icon"></i>
                                        <span x-text="tab.label"></span>
                                    </span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>

                <div class="mb-8">
                    <h3 class="px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Monitoring</h3>
                    <ul class="space-y-2">
                        <template x-for="tab in auditTabs" :key="tab.key">
                            <li>
                                <a href="#" @click.prevent="setActiveTab(tab.key)" :class="activeTab === tab.key ? 'sidebar-item-active' : 'text-gray-300 hover:bg-gray-800'" class="sidebar-link px-6 py-2 flex items-center gap-3 text-sm transition-colors">
                                    <span class="mark-entry-sidebar-link-inner">
                                        <i :class="tab.icon" class="w-4 mark-entry-sidebar-icon"></i>
                                        <span x-text="tab.label"></span>
                                    </span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>

                <div class="mb-8">
                    <h3 class="px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Admin</h3>
                    <ul class="space-y-2">
                        <template x-for="tab in administrationTabs" :key="tab.key">
                            <li>
                                <a href="#" @click.prevent="setActiveTab(tab.key)" :class="activeTab === tab.key ? 'sidebar-item-active' : 'text-gray-300 hover:bg-gray-800'" class="sidebar-link px-6 py-2 flex items-center gap-3 text-sm transition-colors">
                                    <span class="mark-entry-sidebar-link-inner">
                                        <i :class="tab.icon" class="w-4 mark-entry-sidebar-icon"></i>
                                        <span x-text="tab.label"></span>
                                    </span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>

                </nav>

                <div class="px-6 py-4 border-t border-gray-700">
                    <p class="text-xs text-gray-400">{{ auth()->user()->name ?? 'User' }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ auth()->user()->role->name ?? 'Role' }}</p>
                </div>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <div class="px-4 sm:px-6 lg:px-8 pt-0 sm:pt-0">
                <div class="registration-page-header mark-entry-page-header px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
                    <div class="lg:hidden mb-3">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Navigate Section</label>
                        <select x-model="activeTab" @change="setActiveTab(activeTab)" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                            <template x-for="tab in tabs" :key="'mv-' + tab.key">
                                <option :value="tab.key" x-text="tab.label"></option>
                            </template>
                        </select>
                    </div>
                    <div class="mark-entry-hero">
                        <div class="mark-entry-hero-grid">
                            <div>
                                <span class="registration-page-kicker mark-entry-kicker">CSEE Operations Workspace</span>
                                <h1 class="registration-page-title">CSEE Mark Entry</h1>
                                <p class="registration-page-subtitle">Move from registered-subject coverage to mark-entry readiness, recent import tracking, and candidate follow-up in one CSEE workspace.</p>
                                <div class="registration-page-highlights">
                                    <span class="registration-page-chip">
                                        <i class="fas fa-layer-group"></i>
                                        <span x-text="activeTabCategory"></span>
                                    </span>
                                    <span class="registration-page-chip">
                                        <i class="fas fa-location-arrow"></i>
                                        <span x-text="activeWorkspaceMeta.title"></span>
                                    </span>
                                    <span class="registration-page-chip">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span x-text="filters.exam_year || 'Select Year'"></span>
                                    </span>
                                </div>
                            </div>
                            <aside class="mark-entry-hero-side">
                                <div class="registration-page-note">
                                    <h2>Workflow Guidance</h2>
                                    <p>Use this page to confirm CSEE candidate subject registration, see how many subject rows already have marks, and monitor recent CSEE import batches before deeper moderation work.</p>
                                </div>
                                <div class="flex flex-wrap gap-3">
                                    <a href="/exam-types/csee?tab=candidates" class="mark-entry-hero-action px-4 py-2 text-sm font-medium transition-colors flex items-center gap-2">
                                        <i class="fas fa-user-graduate"></i> Candidates
                                    </a>
                                    <a href="/exam-types/csee?tab=subjects" class="mark-entry-hero-action px-4 py-2 text-sm font-medium transition-colors flex items-center gap-2">
                                        <i class="fas fa-book"></i> Subjects
                                    </a>
                                </div>
                            </aside>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mark-entry-main px-4 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8 flex-1 overflow-y-auto">
                <div class="space-y-6">
        <section id="context" class="registration-surface p-5 sm:p-6 scroll-mt-32">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Exam Year</label>
                    <select x-model="filters.exam_year" @change="refreshDependentFilters(true)" class="w-full border border-slate-300 rounded-none px-3 py-2 text-sm bg-white">
                        <template x-for="year in examYears" :key="year.id">
                            <option :value="year.year_label" x-text="year.year_label"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Region</label>
                    <select x-model="filters.region_id" @change="onRegionChange()" class="w-full border border-slate-300 rounded-none px-3 py-2 text-sm bg-white">
                        <option value="">All Regions</option>
                        <template x-for="region in regions" :key="region.id">
                            <option :value="String(region.id)" x-text="region.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">District</label>
                    <select x-model="filters.district_id" @change="onDistrictChange()" class="w-full border border-slate-300 rounded-none px-3 py-2 text-sm bg-white">
                        <option value="">All Districts</option>
                        <template x-for="district in districts" :key="district.id">
                            <option :value="String(district.id)" x-text="district.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Centre</label>
                    <select x-model="filters.school_id" @change="onSchoolChange()" class="w-full border border-slate-300 rounded-none px-3 py-2 text-sm bg-white">
                        <option value="">All Centres</option>
                        <template x-for="school in schools" :key="school.id">
                            <option :value="String(school.id)" x-text="`${school.code} - ${school.name}`"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Subject</label>
                    <select x-model="filters.subject_id" @change="loadDashboard()" class="w-full border border-slate-300 rounded-none px-3 py-2 text-sm bg-white">
                        <option value="">All Subjects</option>
                        <template x-for="subject in subjects" :key="subject.id">
                            <option :value="String(subject.id)" x-text="`${subject.code} - ${subject.name}`"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Search</label>
                    <div class="flex gap-2">
                        <input x-model.debounce.300ms="filters.search" @input="loadDashboard()" type="text" placeholder="Index number, candidate, or centre..." class="w-full border border-slate-300 rounded-none px-3 py-2 text-sm bg-white">
                        <button @click="loadDashboard()" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-none whitespace-nowrap">Refresh</button>
                    </div>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-3 text-xs text-slate-500">
                <span><i class="fas fa-circle-info mr-1"></i> This first-pass CSEE mark-entry workspace shows registered subjects and entered marks from current data.</span>
                <span><i class="fas fa-arrows-rotate mr-1"></i> Use the filters to narrow readiness by year, region, district, centre, and subject.</span>
            </div>
        </section>

        <section id="coverage" class="grid gap-4 md:grid-cols-2 xl:grid-cols-5 scroll-mt-32">
            <template x-for="card in overviewCards()" :key="card.label">
                <article class="mark-entry-overview-card" :class="card.shell">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em]" :class="card.kickerClass" x-text="card.label"></p>
                            <p class="mt-4 font-black leading-none" :class="card.valueClass" x-text="card.value"></p>
                        </div>
                        <span class="mark-entry-overview-icon" :class="card.iconClass">
                            <i :class="card.icon"></i>
                        </span>
                    </div>
                    <p class="mt-4 text-sm leading-7 text-slate-600" x-text="card.detail"></p>
                </article>
            </template>
        </section>

        <section id="review" class="grid gap-6 xl:grid-cols-[1.5fr_1fr] scroll-mt-32">
            <article class="registration-surface overflow-hidden">
                <div class="px-5 sm:px-6 py-5 border-b border-slate-200 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-black text-slate-900">Candidate Coverage</h2>
                        <p class="mt-1 text-sm text-slate-600">Registered CSEE candidates with subject-allocation and mark-entry progress for the current scope.</p>
                    </div>
                    <div class="text-sm text-slate-500">
                        <span x-text="pagination.total"></span> records
                    </div>
                </div>
                <div class="overflow-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50 text-left">
                            <tr class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-5 py-3">Index Number</th>
                                <th class="px-5 py-3">Candidate</th>
                                <th class="px-5 py-3">Centre</th>
                                <th class="px-5 py-3">Subjects</th>
                                <th class="px-5 py-3">Entered</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <template x-if="loading">
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-sm text-slate-500">Loading CSEE mark-entry data...</td>
                                </tr>
                            </template>
                            <template x-if="!loading && candidates.length === 0">
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-sm text-slate-500">No CSEE candidates matched the current mark-entry scope.</td>
                                </tr>
                            </template>
                            <template x-for="candidate in candidates" :key="candidate.id">
                                <tr class="align-top">
                                    <td class="px-5 py-4 text-sm font-semibold text-slate-800" x-text="candidate.candidate_id"></td>
                                    <td class="px-5 py-4 text-sm text-slate-700">
                                        <div class="font-semibold" x-text="candidate.full_name"></div>
                                        <div class="mt-1 text-xs text-slate-500" x-text="candidate.gender"></div>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-slate-700">
                                        <div class="font-semibold" x-text="candidate.school.name"></div>
                                        <div class="mt-1 text-xs text-slate-500" x-text="candidate.school.code"></div>
                                        <div class="mt-1 text-xs text-slate-400" x-text="`${candidate.school.district} / ${candidate.school.region}`"></div>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-slate-700">
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="subject in candidate.registered_subjects" :key="`${candidate.id}-${subject.subject_id}`">
                                                <span class="inline-flex items-center gap-1 border px-2 py-1 text-xs font-semibold"
                                                      :class="subject.entered ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-600'">
                                                    <span x-text="subject.code"></span>
                                                    <i class="fas" :class="subject.entered ? 'fa-check-circle' : 'fa-clock'"></i>
                                                </span>
                                            </template>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-slate-700">
                                        <div class="font-semibold" x-text="`${candidate.entered_count} / ${candidate.registered_count}`"></div>
                                        <div class="mt-1 text-xs text-slate-500">subject rows entered</div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div class="px-5 sm:px-6 py-4 border-t border-slate-200 flex items-center justify-between gap-4">
                    <p class="text-sm text-slate-500">
                        Page <span x-text="pagination.current_page"></span> of <span x-text="pagination.last_page"></span>
                    </p>
                    <div class="flex gap-2">
                        <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" class="px-3 py-2 border border-slate-300 text-sm rounded-none disabled:opacity-50">Previous</button>
                        <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" class="px-3 py-2 border border-slate-300 text-sm rounded-none disabled:opacity-50">Next</button>
                    </div>
                </div>
            </article>

            <div class="space-y-6">
                <article class="registration-surface overflow-hidden">
                    <div class="px-5 sm:px-6 py-5 border-b border-slate-200">
                        <h2 class="text-xl font-black text-slate-900">Recent Batches</h2>
                        <p class="mt-1 text-sm text-slate-600">Latest CSEE mark import batches recorded for the selected scope.</p>
                    </div>
                    <div class="overflow-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50 text-left">
                                <tr class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <th class="px-5 py-3">Batch</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3">Rows</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <template x-if="recentBatches.length === 0">
                                    <tr>
                                        <td colspan="3" class="px-5 py-8 text-center text-sm text-slate-500">No CSEE mark batches were found for this scope yet.</td>
                                    </tr>
                                </template>
                                <template x-for="batch in recentBatches" :key="batch.id">
                                    <tr>
                                        <td class="px-5 py-4 text-sm text-slate-700">
                                            <div class="font-semibold" x-text="batch.batch_code"></div>
                                            <div class="mt-1 text-xs text-slate-500" x-text="batch.school"></div>
                                            <div class="mt-1 text-xs text-slate-400" x-text="batch.subject"></div>
                                        </td>
                                        <td class="px-5 py-4 text-sm">
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full"
                                                  :class="batchStatusClass(batch.status)"
                                                  x-text="batch.status_label"></span>
                                            <div class="mt-1 text-xs text-slate-400" x-text="batch.imported_at || '-'"></div>
                                        </td>
                                        <td class="px-5 py-4 text-sm text-slate-700">
                                            <div class="font-semibold" x-text="batch.rows"></div>
                                            <div class="mt-1 text-xs text-slate-500">
                                                Valid <span x-text="batch.valid_records"></span> | Errors <span x-text="batch.error_records"></span>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="registration-surface p-5 sm:p-6">
                    <h2 class="text-xl font-black text-slate-900">Next Step</h2>
                    <p class="mt-2 text-sm leading-7 text-slate-600">CSEE upload and moderation can now anchor on this scope-ready page. Candidate subject allocation and entered-mark coverage are already visible here, which gives us the same operational starting point PSLE uses before bulk entry work.</p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <a href="/exam-types/csee?tab=candidates" class="inline-flex items-center gap-2 border border-slate-300 px-4 py-2 text-sm font-semibold rounded-none text-slate-700 hover:bg-slate-50">
                            <i class="fas fa-user-check"></i> Review Candidate Subjects
                        </a>
                        <a href="/exam-types/csee?tab=subjects" class="inline-flex items-center gap-2 border border-slate-300 px-4 py-2 text-sm font-semibold rounded-none text-slate-700 hover:bg-slate-50">
                            <i class="fas fa-book-open"></i> Review Subject Catalog
                        </a>
                    </div>
                </article>
            </div>
        </section>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.sidebar-item-active {
    background: rgb(37 99 235);
    color: #fff;
    border-left: 4px solid rgb(29 78 216);
}

.mark-entry-sidebar {
    background: rgb(17 24 39);
    border-right: 1px solid rgb(55 65 81);
    box-shadow: none;
}

.mark-entry-sidebar-inner {
    min-height: calc(100vh - 140px);
}

.mark-entry-sidebar h2 {
    letter-spacing: 0;
}

.mark-entry-sidebar .sidebar-link {
    border-radius: 0;
    line-height: 1.35;
    border: 0;
}

.mark-entry-sidebar-link-inner {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
}

.mark-entry-sidebar-icon {
    width: 1rem;
    text-align: center;
    font-size: 0.875rem;
    color: inherit;
    transition: color 160ms ease;
}

.mark-entry-page-header {
    border-bottom: 1px solid rgba(203, 213, 225, 0.9);
    background: linear-gradient(180deg, rgba(248, 250, 252, 0.96) 0%, rgba(241, 245, 249, 0.98) 100%);
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.06);
    backdrop-filter: blur(10px);
}

.mark-entry-hero {
    border-radius: 28px;
    padding: 1.75rem 1.8rem;
    background: linear-gradient(135deg, rgba(17, 46, 84, 0.98) 0%, rgba(27, 77, 147, 0.95) 55%, rgba(13, 119, 106, 0.9) 100%);
    box-shadow: 0 22px 44px rgba(15, 23, 42, 0.16);
    position: relative;
    overflow: hidden;
}

.mark-entry-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at top left, rgba(255, 255, 255, 0.14), transparent 34%),
        radial-gradient(circle at bottom right, rgba(252, 209, 22, 0.12), transparent 28%);
    pointer-events: none;
}

.mark-entry-hero-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.3fr) minmax(280px, 0.7fr);
    gap: 24px;
    align-items: start;
}

.mark-entry-hero-side {
    display: grid;
    gap: 16px;
    align-content: start;
    justify-items: start;
}

.mark-entry-kicker {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    font-size: 0.76rem;
    font-weight: 800;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: #315586;
}

.mark-entry-kicker::before {
    content: "";
    width: 36px;
    height: 4px;
    border-radius: 999px;
    background: linear-gradient(90deg, #1eb53a 0%, #fcd116 58%, #00a3dd 100%);
}

.mark-entry-hero .registration-page-title {
    color: #ffffff;
}

.mark-entry-hero .registration-page-subtitle {
    color: rgba(255, 255, 255, 0.84);
}

.mark-entry-hero .registration-page-highlights {
    position: relative;
    z-index: 1;
}

.mark-entry-hero .registration-page-chip {
    border-color: rgba(148, 163, 184, 0.24);
    background: rgba(255, 255, 255, 0.12);
    color: #f8fafc;
}

.mark-entry-hero .registration-page-chip i {
    color: #fcd116;
}

.mark-entry-hero-side,
.mark-entry-hero-grid > div {
    position: relative;
    z-index: 1;
}

.mark-entry-hero .registration-page-note {
    border-radius: 24px;
    padding: 18px 18px 16px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #ffffff;
    box-shadow: 0 18px 34px rgba(15, 23, 42, 0.12);
    backdrop-filter: blur(10px);
}

.mark-entry-hero .registration-page-note h2 {
    color: #ffffff;
}

.mark-entry-hero .registration-page-note p {
    color: rgba(255, 255, 255, 0.82);
}

.mark-entry-hero-action {
    align-self: start;
    justify-self: start;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.18);
    background: rgba(29, 78, 216, 0.95);
    color: #ffffff;
    box-shadow: 0 14px 26px rgba(15, 23, 42, 0.16);
    backdrop-filter: blur(8px);
}

.mark-entry-hero-action:hover {
    background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
}

.mark-entry-overview-card,
.mark-entry-shell .registration-surface {
    border: 1px solid rgba(203, 213, 225, 0.92);
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.96);
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
}

.mark-entry-overview-card {
    padding: 1.5rem;
}

.mark-entry-overview-icon {
    display: inline-flex;
    height: 3.25rem;
    width: 3.25rem;
    align-items: center;
    justify-content: center;
    border-radius: 1.1rem;
    font-size: 1rem;
}

@media (max-width: 1279px) {
    .mark-entry-hero-grid {
        grid-template-columns: 1fr;
    }

    .mark-entry-hero-side {
        justify-items: start;
    }
}

@media (max-width: 1023px) {
    .mark-entry-shell {
        background: linear-gradient(180deg, #eff4fb 0%, #e8eef6 100%);
    }
}
</style>

<script>
    function cseeMarkEntryPage() {
        return {
            activeTab: 'context',
            loading: false,
            examYears: [],
            regions: [],
            districts: [],
            schools: [],
            subjects: [],
            candidates: [],
            recentBatches: [],
            summary: {
                candidate_count: 0,
                school_count: 0,
                subject_count: 0,
                registered_subject_rows: 0,
                entered_subject_rows: 0,
                batch_count: 0,
            },
            pagination: {
                total: 0,
                per_page: 100,
                current_page: 1,
                last_page: 1,
            },
            filters: {
                exam_year: '',
                region_id: '',
                district_id: '',
                school_id: '',
                subject_id: '',
                search: '',
                page: 1,
                per_page: 100,
            },
            entryTabs: [
                { key: 'context', label: 'Scope & Filters', icon: 'fas fa-sliders-h', category: 'Entry & Validation', title: 'Scope & Filters' },
            ],
            moderationTabs: [
                { key: 'review', label: 'Candidate Coverage', icon: 'fas fa-clipboard-check', category: 'Moderation & Review', title: 'Candidate Coverage' },
            ],
            lockingTabs: [
                { key: 'review', label: 'Readiness Status', icon: 'fas fa-lock', category: 'Submission & Locking', title: 'Readiness Status' },
            ],
            reportTabs: [
                { key: 'coverage', label: 'Overview Cards', icon: 'fas fa-chart-bar', category: 'Reports & Exports', title: 'Overview Cards' },
            ],
            auditTabs: [
                { key: 'review', label: 'Recent Batches', icon: 'fas fa-history', category: 'Monitoring & Audit', title: 'Recent Batches' },
            ],
            administrationTabs: [
                { key: 'context', label: 'Workspace Setup', icon: 'fas fa-cog', category: 'Administration', title: 'Workspace Setup' },
            ],

            async init() {
                await this.loadBootstrap();
                await this.refreshDependentFilters(false);
            },

            get tabs() {
                return [
                    ...this.entryTabs,
                    ...this.moderationTabs,
                    ...this.lockingTabs,
                    ...this.reportTabs,
                    ...this.auditTabs,
                    ...this.administrationTabs,
                ];
            },

            get activeTabMeta() {
                return this.tabs.find((tab) => tab.key === this.activeTab) || this.tabs[0] || {
                    category: 'Entry & Validation',
                    title: 'Scope & Filters',
                };
            },

            get activeTabCategory() {
                return this.activeTabMeta.category;
            },

            get activeWorkspaceMeta() {
                return {
                    title: this.activeTabMeta.title,
                    description: 'This CSEE workspace keeps scope selection, candidate subject coverage, and recent batch monitoring in one governed view before broader mark-entry workflows are expanded.',
                };
            },

            setActiveTab(tabKey) {
                this.activeTab = tabKey;
                this.$nextTick(() => {
                    document.getElementById(tabKey)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            },

            overviewCards() {
                return [
                    {
                        label: 'Candidates',
                        value: this.summary.candidate_count,
                        detail: 'Registered CSEE candidates within the current mark-entry scope.',
                        icon: 'fas fa-users',
                        shell: 'bg-white',
                        kickerClass: 'text-sky-600',
                        valueClass: 'text-4xl text-slate-900',
                        iconClass: 'bg-sky-50 text-sky-600',
                    },
                    {
                        label: 'Centres',
                        value: this.summary.school_count,
                        detail: 'Distinct centres represented by the selected CSEE registration scope.',
                        icon: 'fas fa-school',
                        shell: 'bg-white',
                        kickerClass: 'text-emerald-600',
                        valueClass: 'text-4xl text-slate-900',
                        iconClass: 'bg-emerald-50 text-emerald-600',
                    },
                    {
                        label: 'Subjects',
                        value: this.summary.subject_count,
                        detail: 'Distinct CSEE subjects already allocated to candidates in scope.',
                        icon: 'fas fa-book',
                        shell: 'bg-white',
                        kickerClass: 'text-violet-600',
                        valueClass: 'text-4xl text-slate-900',
                        iconClass: 'bg-violet-50 text-violet-600',
                    },
                    {
                        label: 'Registered Rows',
                        value: this.summary.registered_subject_rows,
                        detail: 'Total candidate-subject registrations ready to receive marks.',
                        icon: 'fas fa-list-check',
                        shell: 'bg-white',
                        kickerClass: 'text-amber-600',
                        valueClass: 'text-4xl text-slate-900',
                        iconClass: 'bg-amber-50 text-amber-600',
                    },
                    {
                        label: 'Entered Rows',
                        value: this.summary.entered_subject_rows,
                        detail: 'Candidate-subject rows that already have CSEE marks captured.',
                        icon: 'fas fa-pen-ruler',
                        shell: 'bg-white',
                        kickerClass: 'text-rose-600',
                        valueClass: 'text-4xl text-slate-900',
                        iconClass: 'bg-rose-50 text-rose-600',
                    },
                ];
            },

            async loadBootstrap() {
                const response = await fetch('/api/mark-entry/csee/bootstrap');
                const payload = await response.json();
                const data = payload.data || {};
                this.examYears = data.exam_years || [];
                this.regions = data.regions || [];
                this.filters.exam_year = data.active_year?.year_label || this.examYears[0]?.year_label || '';
            },

            async onRegionChange() {
                this.filters.district_id = '';
                this.filters.school_id = '';
                this.filters.subject_id = '';
                await this.refreshDependentFilters(true);
            },

            async onDistrictChange() {
                this.filters.school_id = '';
                this.filters.subject_id = '';
                await this.refreshDependentFilters(true);
            },

            async onSchoolChange() {
                this.filters.subject_id = '';
                await this.loadSubjects();
                await this.loadDashboard();
            },

            async refreshDependentFilters(loadDashboard = true) {
                await this.loadDistricts();
                await this.loadSchools();
                await this.loadSubjects();
                if (loadDashboard) {
                    this.filters.page = 1;
                    await this.loadDashboard();
                }
            },

            async loadDistricts() {
                const params = new URLSearchParams();
                if (this.filters.region_id) params.set('region_id', this.filters.region_id);
                const response = await fetch(`/api/mark-entry/csee/districts?${params.toString()}`);
                const payload = await response.json();
                this.districts = payload.data || [];
            },

            async loadSchools() {
                const params = new URLSearchParams();
                if (this.filters.exam_year) params.set('exam_year', this.filters.exam_year);
                if (this.filters.region_id) params.set('region_id', this.filters.region_id);
                if (this.filters.district_id) params.set('district_id', this.filters.district_id);
                const response = await fetch(`/api/mark-entry/csee/schools?${params.toString()}`);
                const payload = await response.json();
                this.schools = payload.data || [];
            },

            async loadSubjects() {
                const params = new URLSearchParams();
                if (this.filters.exam_year) params.set('exam_year', this.filters.exam_year);
                if (this.filters.region_id) params.set('region_id', this.filters.region_id);
                if (this.filters.district_id) params.set('district_id', this.filters.district_id);
                if (this.filters.school_id) params.set('school_id', this.filters.school_id);
                const response = await fetch(`/api/mark-entry/csee/subjects?${params.toString()}`);
                const payload = await response.json();
                this.subjects = payload.data || [];
            },

            async loadDashboard() {
                this.loading = true;
                try {
                    const params = new URLSearchParams();
                    Object.entries(this.filters).forEach(([key, value]) => {
                        if (value !== '' && value !== null && value !== undefined) {
                            params.set(key, value);
                        }
                    });

                    const response = await fetch(`/api/mark-entry/csee/dashboard?${params.toString()}`);
                    const payload = await response.json();
                    const data = payload.data || {};

                    this.summary = data.summary || this.summary;
                    this.candidates = data.candidates || [];
                    this.pagination = data.pagination || this.pagination;
                    this.recentBatches = data.recent_batches || [];
                } finally {
                    this.loading = false;
                }
            },

            async changePage(page) {
                if (page < 1 || page > this.pagination.last_page) return;
                this.filters.page = page;
                await this.loadDashboard();
            },

            batchStatusClass(status) {
                if (status === 'approved' || status === 'locked') return 'bg-emerald-50 text-emerald-700';
                if (status === 'submitted' || status === 'validated') return 'bg-amber-50 text-amber-700';
                if (status === 'rejected') return 'bg-rose-50 text-rose-700';
                return 'bg-slate-100 text-slate-700';
            },
        };
    }
</script>
@endsection
