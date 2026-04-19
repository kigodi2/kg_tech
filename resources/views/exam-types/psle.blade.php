@extends('layout')

@section('content')
@include('registration.partials.theme')
<style>
    .exam-filter-panel {
        background: rgba(255, 255, 255, 0.94);
        border: 1px solid rgba(203, 213, 225, 0.9);
        border-radius: 20px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
    }

    .exam-section {
        padding: 24px 28px;
    }

    .exam-field {
        min-width: 220px;
    }

    .exam-field--compact {
        min-width: 200px;
    }

    .exam-label {
        display: block;
        margin-bottom: 8px;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .exam-filter-form {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        flex-wrap: wrap;
    }

    .exam-search-input,
    .exam-select,
    .exam-dropdown {
        width: 100%;
        height: 46px;
        padding: 0 14px;
        border: 1px solid #cbd5e1 !important;
        border-radius: 0 !important;
        background: #ffffff;
        color: #0f172a;
        font-size: 14px;
        box-shadow: none !important;
    }

    .exam-dropdown {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        text-align: left;
        transition: border-color 0.2s ease, background-color 0.2s ease;
    }

    .exam-combobox-wrap {
        position: relative;
    }

    .exam-combobox-input {
        padding-right: 38px;
    }

    .exam-combobox-icon {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #0f172a;
        font-size: 13px;
        pointer-events: none;
    }

    .exam-search-input:focus,
    .exam-select:focus,
    .exam-dropdown:focus {
        outline: 2px solid rgba(59, 130, 246, 0.15);
        outline-offset: 0;
        border-color: #3b82f6 !important;
    }

    .exam-dropdown-menu {
        position: absolute;
        top: calc(100% - 1px);
        left: 0;
        right: 0;
        background: #242129;
        border: 1px solid #2f2a34;
        z-index: 30;
        border-top: 0;
        border-radius: 0 0 10px 10px;
        display: flex;
        flex-direction: column;
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.24);
        overflow: hidden;
    }

    .exam-dropdown-option {
        padding: 11px 14px;
        cursor: pointer;
        font-size: 14px;
        line-height: 1.4;
        color: rgba(255, 255, 255, 0.94);
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    .exam-dropdown-option:hover,
    .exam-dropdown-option.is-active {
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
    }

    .exam-dropdown-menu .filter-search-input {
        background: #242129 !important;
        color: #ffffff !important;
        border: 0 !important;
        border-bottom: 1px solid #3b3640 !important;
    }

    .exam-dropdown-menu .filter-search-input::placeholder {
        color: rgba(255, 255, 255, 0.45);
    }

    .exam-actions-row {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        margin-left: auto;
        flex-wrap: wrap;
    }

    .exam-button,
    .exam-button-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 46px;
        padding: 0 18px;
        border-radius: 12px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 700;
        transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
        white-space: nowrap;
    }

    .exam-button:hover,
    .exam-button-secondary:hover {
        transform: translateY(-1px);
    }

    .exam-button {
        background: #2563eb;
        color: #ffffff;
        border: 1px solid #2563eb;
    }

    .exam-button:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }

    .exam-button-secondary {
        background: #64748b;
        color: #ffffff;
        border: 1px solid #64748b;
    }

    .exam-button-secondary:hover {
        background: #475569;
        border-color: #475569;
    }

    .exam-section-text {
        margin-top: 14px;
        color: #64748b;
        font-size: 14px;
        line-height: 1.6;
    }

    @media (max-width: 720px) {
        .exam-section {
            padding: 18px 20px;
        }

        .exam-field,
        .exam-field--compact {
            min-width: 100%;
        }

        .exam-actions-row {
            margin-left: 0;
            width: 100%;
        }

        .exam-button,
        .exam-button-secondary {
            width: 100%;
        }
    }
</style>
<div class="registration-shell">
    <div class="registration-page-stack">
        @include('registration.partials.header', [
            'kicker' => 'PSLE Administration Workspace',
            'title' => 'PSLE Configuration',
            'subtitle' => 'Manage PSLE subjects, paper structure, school centres, and pupil registration with a primary-school workflow aligned to region, council, and school administration.',
            'highlights' => [
                ['icon' => 'fas fa-book', 'text' => 'Primary subject control'],
                ['icon' => 'fas fa-school', 'text' => 'School centre visibility'],
                ['icon' => 'fas fa-user-graduate', 'text' => 'Pupil register operations'],
            ],
            'noteTitle' => 'Implementation Direction',
            'noteText' => 'This page is an internal IRMS setup workspace. It borrows the PSLE hierarchy used by NECTA, but stays focused on administration rather than public result publication.',
        ])

        <div x-data="psleManager()" x-init="init()" class="space-y-6">
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="registration-surface p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Subjects</p>
                    <div class="mt-3 flex items-end justify-between">
                        <strong class="text-4xl font-black text-slate-900" x-text="subjects.length"></strong>
                        <span class="rounded-2xl bg-blue-50 p-3 text-blue-600"><i class="fas fa-book"></i></span>
                    </div>
                    <p class="mt-3 text-sm text-slate-600">Configured PSLE subjects and paper definitions.</p>
                </article>
                <article class="registration-surface p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Synced Councils</p>
                    <div class="mt-3 flex items-end justify-between">
                        <strong class="text-4xl font-black text-slate-900" x-text="syncedCouncilCount"></strong>
                        <span class="rounded-2xl bg-emerald-50 p-3 text-emerald-600"><i class="fas fa-map-signs"></i></span>
                    </div>
                    <p class="mt-3 text-sm text-slate-600">Councils represented by the current NECTA-synced PSLE school scope.</p>
                </article>
                <article class="registration-surface p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Synced Primary Schools</p>
                    <div class="mt-3 flex items-end justify-between">
                        <strong class="text-4xl font-black text-slate-900" x-text="syncedSchoolCount"></strong>
                        <span class="rounded-2xl bg-amber-50 p-3 text-amber-600"><i class="fas fa-school"></i></span>
                    </div>
                    <p class="mt-3 text-sm text-slate-600">NECTA-synced PSLE schools visible in the current scope.</p>
                </article>
                <article class="registration-surface p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Registered Pupils</p>
                    <div class="mt-3 flex items-end justify-between">
                        <strong class="text-4xl font-black text-slate-900" x-text="totalCandidates"></strong>
                        <span class="rounded-2xl bg-violet-50 p-3 text-violet-600"><i class="fas fa-user-graduate"></i></span>
                    </div>
                    <p class="mt-3 text-sm text-slate-600">PSLE pupil records currently visible within this workspace.</p>
                </article>
            </section>

            <section class="registration-surface p-4">
                <div class="rounded-[2rem] border border-slate-200 bg-[linear-gradient(180deg,rgba(255,255,255,0.96)_0%,rgba(241,245,249,0.98)_100%)] p-3 shadow-[0_18px_45px_rgba(15,23,42,0.08),inset_0_1px_0_rgba(255,255,255,0.85)]">
                    <div class="flex flex-wrap gap-3 rounded-[1.65rem] border border-white/70 bg-white/55 p-2.5 shadow-[inset_0_1px_0_rgba(255,255,255,0.9)] backdrop-blur-sm">
                        <a href="/exam-types/psle?tab=subjects" @click.prevent="setActiveTab('subjects')" :class="activeTab === 'subjects' ? activeTabClass : inactiveTabClass" class="group inline-flex items-center gap-3 rounded-[1.25rem] border px-5 py-3.5 text-sm font-bold transition duration-200">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-[1rem] text-sm shadow-sm transition" :class="activeTab === 'subjects' ? activeIconClass : inactiveIconClass">
                                <i class="fas fa-book"></i>
                            </span>
                            <span>Subjects</span>
                            <span x-show="activeTab === 'subjects'" class="h-2 w-2 rounded-full bg-white/95 shadow-[0_0_0_4px_rgba(255,255,255,0.18)]"></span>
                        </a>
                        <a href="/exam-types/psle?tab=papers" @click.prevent="setActiveTab('papers')" :class="activeTab === 'papers' ? activeTabClass : inactiveTabClass" class="group inline-flex items-center gap-3 rounded-[1.25rem] border px-5 py-3.5 text-sm font-bold transition duration-200">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-[1rem] text-sm shadow-sm transition" :class="activeTab === 'papers' ? activeIconClass : inactiveIconClass">
                                <i class="fas fa-layer-group"></i>
                            </span>
                            <span>Paper Structure</span>
                            <span x-show="activeTab === 'papers'" class="h-2 w-2 rounded-full bg-white/95 shadow-[0_0_0_4px_rgba(255,255,255,0.18)]"></span>
                        </a>
                        <a href="/exam-types/psle?tab=timetable" @click.prevent="setActiveTab('timetable')" :class="activeTab === 'timetable' ? activeTabClass : inactiveTabClass" class="group inline-flex items-center gap-3 rounded-[1.25rem] border px-5 py-3.5 text-sm font-bold transition duration-200">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-[1rem] text-sm shadow-sm transition" :class="activeTab === 'timetable' ? activeIconClass : inactiveIconClass">
                                <i class="fas fa-calendar-alt"></i>
                            </span>
                            <span>Timetable</span>
                            <span x-show="activeTab === 'timetable'" class="h-2 w-2 rounded-full bg-white/95 shadow-[0_0_0_4px_rgba(255,255,255,0.18)]"></span>
                        </a>
                        <a href="/exam-types/psle?tab=schools" @click.prevent="setActiveTab('schools')" :class="activeTab === 'schools' ? activeTabClass : inactiveTabClass" class="group inline-flex items-center gap-3 rounded-[1.25rem] border px-5 py-3.5 text-sm font-bold transition duration-200">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-[1rem] text-sm shadow-sm transition" :class="activeTab === 'schools' ? activeIconClass : inactiveIconClass">
                                <i class="fas fa-school"></i>
                            </span>
                            <span>Schools & Centres</span>
                            <span x-show="activeTab === 'schools'" class="h-2 w-2 rounded-full bg-white/95 shadow-[0_0_0_4px_rgba(255,255,255,0.18)]"></span>
                        </a>
                        <a href="/exam-types/psle?tab=pupils" @click.prevent="setActiveTab('pupils')" :class="activeTab === 'pupils' ? activeTabClass : inactiveTabClass" class="group inline-flex items-center gap-3 rounded-[1.25rem] border px-5 py-3.5 text-sm font-bold transition duration-200">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-[1rem] text-sm shadow-sm transition" :class="activeTab === 'pupils' ? activeIconClass : inactiveIconClass">
                                <i class="fas fa-user-graduate"></i>
                            </span>
                            <span>Pupil Register</span>
                            <span x-show="activeTab === 'pupils'" class="h-2 w-2 rounded-full bg-white/95 shadow-[0_0_0_4px_rgba(255,255,255,0.18)]"></span>
                        </a>
                    </div>
                </div>
            </section>

            <section x-show="activeTab === 'subjects'" class="space-y-6">
                <div class="registration-surface p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-2xl font-black text-slate-900">PSLE Subjects</h2>
                            <p class="mt-2 text-sm text-slate-600">PSLE subjects come from the official NECTA catalog. Synchronize the catalog instead of creating local subject variants.</p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button @click="downloadSubjectsTemplate()" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                <i class="fas fa-download mr-2"></i>Template
                            </button>
                            <button @click="syncOfficialSubjects()" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                <i class="fas fa-rotate mr-2"></i>Sync Official Catalog
                            </button>
                        </div>
                    </div>
                </div>

                <div class="registration-surface registration-toolbar-card">
                    <div class="registration-toolbar-grid">
                        <div class="flex flex-col flex-1 min-w-[220px]">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                            <input x-model="subjectSearch" @input="filterSubjects()" type="text" placeholder="Search PSLE subjects..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <div class="registration-surface registration-table-card overflow-x-auto">
                    <div x-show="loadingSubjects" class="p-8 text-center text-slate-500">
                        <i class="fas fa-spinner animate-spin text-2xl"></i>
                    </div>
                    <div x-show="!loadingSubjects" class="overflow-x-auto">
                        <table class="w-full min-w-[760px]">
                            <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-barcode mr-1 text-blue-600"></i>Code</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-book mr-1 text-gray-600"></i>Subject</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-layer-group mr-1 text-emerald-600"></i>Subject Group</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-note-sticky mr-1 text-amber-600"></i>Description</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="subject in filteredSubjects" :key="subject.id">
                                    <tr class="hover:bg-blue-50 transition-colors">
                                        <td class="px-3 py-1.5 text-[13px] font-mono font-semibold text-blue-700 bg-blue-50 rounded" x-text="subject.code"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-800 font-medium" x-text="subject.name"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600" x-text="formatPsleCategory(subject.category)"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600" x-text="subject.description || '-'"></td>
                                        <td class="px-3 py-1.5 text-sm whitespace-nowrap">
                                            <div class="flex items-center justify-center gap-1">
                                                <button @click="openSubjectModal(subject)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" title="View Subject">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button @click="openSubjectPapers(subject)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-blue-600 transition hover:bg-blue-50 hover:text-blue-800" title="Open Paper Governance">
                                                    <i class="fas fa-table-list"></i>
                                                </button>
                                                <button @click="openOfficialPaperSource(subject)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-emerald-600 transition hover:bg-emerald-50 hover:text-emerald-800" title="Open Official Source">
                                                    <i class="fas fa-file-arrow-up-right"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="!loadingSubjects && filteredSubjects.length === 0">
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">No PSLE subjects found. Use `Sync Official Catalog` to load the NECTA subject list.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section x-show="activeTab === 'papers'" class="space-y-6">
                <div class="registration-surface p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="max-w-3xl">
                            <h2 class="text-2xl font-black text-slate-900">PSLE Paper Governance</h2>
                            <p class="mt-2 text-sm leading-7 text-slate-600">Manage paper-structure readiness for official PSLE subjects from one controlled workspace. This area is intentionally governance-first: paper formats are not guessed locally and must be backed by official NECTA source material before activation.</p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button @click="openPaperGuidanceModal()" class="whitespace-nowrap rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                <i class="fas fa-circle-info mr-2"></i>Governance Notes
                            </button>
                            <button @click="showMessage('Official PSLE paper formats are pending verified NECTA source import.', 'error')" class="whitespace-nowrap rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                <i class="fas fa-shield-check mr-2"></i>Await Official Import
                            </button>
                        </div>
                    </div>
                </div>

                <div class="registration-surface p-5">
                    <div class="rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900">
                        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="font-semibold uppercase tracking-[0.18em] text-[11px] text-emerald-800">Official Source On File</p>
                                <p class="mt-1 font-semibold">FORMAT FOR PRIMARY SCHOOL LEAVING EXAMINATIONS, Revised January 2024</p>
                                <p class="mt-1 text-emerald-800/90">NECTA handles PSLE subject formats through one official format booklet, with each subject covered in its own section rather than through a public paper-setup dashboard.</p>
                            </div>
                            <a href="https://necta.go.tz/webroot/uploads/news/FORMAT_PSLE_2024_ENGLISH.pdf" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center whitespace-nowrap rounded-xl border border-emerald-300 bg-white px-4 py-2.5 font-semibold text-emerald-800 transition hover:bg-emerald-100">
                                <i class="fas fa-file-pdf mr-2"></i>Open Official Booklet
                            </a>
                        </div>
                    </div>
                </div>

                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <article class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Official Subjects</p>
                        <div class="mt-3 flex items-end justify-between">
                            <strong class="text-4xl font-black text-slate-900" x-text="subjects.length"></strong>
                            <span class="rounded-2xl bg-blue-50 p-3 text-blue-600"><i class="fas fa-book-open"></i></span>
                        </div>
                        <p class="mt-3 text-sm text-slate-600">Subjects currently controlled by the official PSLE catalog.</p>
                    </article>
                    <article class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Verified Formats</p>
                        <div class="mt-3 flex items-end justify-between">
                        <strong class="text-4xl font-black text-slate-900" x-text="verifiedPaperCount"></strong>
                        <span class="rounded-2xl bg-emerald-50 p-3 text-emerald-600"><i class="fas fa-check-double"></i></span>
                    </div>
                        <p class="mt-3 text-sm text-slate-600">Subjects mapped to the official NECTA PSLE format booklet now on file.</p>
                    </article>
                    <article class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Pending Source</p>
                        <div class="mt-3 flex items-end justify-between">
                        <strong class="text-4xl font-black text-slate-900" x-text="pendingPaperCount"></strong>
                        <span class="rounded-2xl bg-amber-50 p-3 text-amber-600"><i class="fas fa-hourglass-half"></i></span>
                    </div>
                        <p class="mt-3 text-sm text-slate-600">Subjects still waiting for internal extraction into structured IRMS paper fields.</p>
                    </article>
                    <article class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Policy Mode</p>
                        <div class="mt-3 flex items-end justify-between">
                            <strong class="text-2xl font-black text-slate-900">Read Only</strong>
                            <span class="rounded-2xl bg-violet-50 p-3 text-violet-600"><i class="fas fa-lock"></i></span>
                        </div>
                        <p class="mt-3 text-sm text-slate-600">Local paper edits stay blocked until official format definitions are loaded.</p>
                    </article>
                </section>

                <div class="registration-surface registration-toolbar-card">
                    <div class="registration-toolbar-grid">
                        <div class="flex flex-col flex-1 min-w-[240px]">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                            <input x-model="paperSearch" @input="filterPaperSubjects()" type="text" placeholder="Search PSLE subjects for paper governance..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="ml-auto flex gap-2 items-end self-end">
                            <button @click="paperStatusFilter = 'all'; filterPaperSubjects()" :class="paperStatusFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 border border-slate-200'" class="px-4 py-2 rounded-lg text-sm font-semibold transition">All</button>
                            <button @click="paperStatusFilter = 'pending'; filterPaperSubjects()" :class="paperStatusFilter === 'pending' ? 'bg-amber-500 text-white' : 'bg-white text-slate-700 border border-slate-200'" class="px-4 py-2 rounded-lg text-sm font-semibold transition">Pending Source</button>
                            <button @click="paperStatusFilter = 'verified'; filterPaperSubjects()" :class="paperStatusFilter === 'verified' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-700 border border-slate-200'" class="px-4 py-2 rounded-lg text-sm font-semibold transition">Verified</button>
                        </div>
                    </div>
                </div>

                <div class="registration-surface registration-table-card overflow-x-auto">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[1220px]">
                            <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-barcode mr-1 text-blue-600"></i>Code</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-book mr-1 text-gray-600"></i>Subject</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-layer-group mr-1 text-emerald-600"></i>Subject Group</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-shield-halved mr-1 text-amber-600"></i>Format Status</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-file-lines mr-1 text-violet-600"></i>Official Source</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-note-sticky mr-1 text-slate-500"></i>Governance Note</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="subject in filteredPaperSubjects" :key="subject.id || subject.code">
                                    <tr class="hover:bg-blue-50 transition-colors">
                                        <td class="px-3 py-1.5 text-[13px] font-mono font-semibold text-blue-700 bg-blue-50 rounded whitespace-nowrap" x-text="subject.code"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-800 font-medium whitespace-nowrap" x-text="subject.name"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600 whitespace-nowrap" x-text="formatPsleCategory(subject.category)"></td>
                                        <td class="px-3 py-1.5 text-sm">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap" :class="paperStatusKey(subject) === 'verified' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'" x-text="paperStatusLabel(subject)"></span>
                                        </td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600 whitespace-nowrap" x-text="paperSourceLabel(subject)"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600 whitespace-nowrap" x-text="paperGovernanceNote(subject)"></td>
                                        <td class="px-3 py-1.5 text-sm whitespace-nowrap">
                                            <div class="flex items-center justify-center gap-1">
                                                <button @click="openPaperSubjectModal(subject)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" title="View Governance">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button @click="openOfficialPaperSource(subject)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-emerald-600 transition hover:bg-emerald-50 hover:text-emerald-800" title="Open Source">
                                                    <i class="fas fa-file-arrow-up-right"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="filteredPaperSubjects.length === 0">
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500">No PSLE paper-governance subjects match the current search or status filter.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            </section>

            <section x-show="activeTab === 'timetable'" class="space-y-6">
                <div class="registration-surface p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="max-w-4xl">
                            <h2 class="text-2xl font-black text-slate-900">PSLE Zonal Timetable</h2>
                            <p class="mt-2 text-sm leading-7 text-slate-600">This timetable workspace is aligned to the zonal PSLE LaTeX source used for the May 2026 mock schedule. It preserves the same official-style structure: date and day, session time, hidden code, subject title, and controlled break windows.</p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button @click="openTimetablePreview()" class="whitespace-nowrap rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                <i class="fas fa-magnifying-glass mr-2"></i>Print Preview
                            </button>
                            <button @click="printTimetable()" class="whitespace-nowrap rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                <i class="fas fa-print mr-2"></i>Print PDF
                            </button>
                            <button @click="openTimetableSourceModal()" class="whitespace-nowrap rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                <i class="fas fa-file-lines mr-2"></i>Source Notes
                            </button>
                        </div>
                    </div>
                </div>

                <div class="registration-surface p-5">
                    <div class="rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900">
                        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="font-semibold uppercase tracking-[0.18em] text-[11px] text-emerald-800">Source Alignment</p>
                                <p class="mt-1 font-semibold">RATIBA YA MTIHANI WA UTAMILIFU DARASA LA SABA KANDA YA KITAALUMA, MEI 2026</p>
                                <p class="mt-1 text-emerald-800/90">Structured from the zonal LaTeX timetable used by the academic special zone for Tanga, Iringa, Singida, Morogoro, Dodoma, Lindi, Mtwara, and Tabora.</p>
                            </div>
                            <span class="inline-flex items-center justify-center whitespace-nowrap rounded-xl border border-emerald-300 bg-white px-4 py-2.5 font-semibold text-emerald-800">
                                <i class="fas fa-calendar-days mr-2"></i>Mock Timetable 2026
                            </span>
                        </div>
                    </div>
                </div>

                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <article class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Exam Days</p>
                        <div class="mt-3 flex items-end justify-between">
                            <strong class="text-4xl font-black text-slate-900" x-text="timetableDays.length"></strong>
                            <span class="rounded-2xl bg-blue-50 p-3 text-blue-600"><i class="fas fa-calendar-week"></i></span>
                        </div>
                        <p class="mt-3 text-sm text-slate-600">Scheduled days in the current zonal mock timetable.</p>
                    </article>
                    <article class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Exam Slots</p>
                        <div class="mt-3 flex items-end justify-between">
                            <strong class="text-4xl font-black text-slate-900" x-text="timetableExamSlotCount"></strong>
                            <span class="rounded-2xl bg-emerald-50 p-3 text-emerald-600"><i class="fas fa-clock"></i></span>
                        </div>
                        <p class="mt-3 text-sm text-slate-600">Timed examination sitting windows excluding designated breaks.</p>
                    </article>
                    <article class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Break Windows</p>
                        <div class="mt-3 flex items-end justify-between">
                            <strong class="text-4xl font-black text-slate-900" x-text="timetableBreakCount"></strong>
                            <span class="rounded-2xl bg-amber-50 p-3 text-amber-600"><i class="fas fa-mug-hot"></i></span>
                        </div>
                        <p class="mt-3 text-sm text-slate-600">Official break intervals preserved exactly from the zonal source timetable.</p>
                    </article>
                    <article class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Medium Tracks</p>
                        <div class="mt-3 flex items-end justify-between">
                            <strong class="text-2xl font-black text-slate-900">SWA + ENG</strong>
                            <span class="rounded-2xl bg-violet-50 p-3 text-violet-600"><i class="fas fa-language"></i></span>
                        </div>
                        <p class="mt-3 text-sm text-slate-600">The timetable preserves both Kiswahili and English-medium subject rows where the zonal format includes them.</p>
                    </article>
                </section>

                <div class="registration-surface registration-toolbar-card">
                    <div class="registration-toolbar-grid">
                        <div class="flex flex-col min-w-[220px]">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Day</label>
                            <div class="relative" @click.outside="timetableDayOpen = false">
                                <button type="button" class="w-full px-3 py-2 border border-gray-300 text-left bg-white hover:bg-gray-50 transition-colors flex justify-between items-center rounded-none" @click="timetableDayOpen = !timetableDayOpen">
                                    <span class="truncate" x-text="selectedTimetableDay ? (timetableDays.find(day => day.date === selectedTimetableDay)?.label || 'All Days') : 'All Days'"></span>
                                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                </button>
                                <div x-show="timetableDayOpen" class="absolute top-full left-0 right-0 bg-white border border-t-0 border-gray-300 z-30 rounded-none flex flex-col" x-transition>
                                    <div class="max-h-56 overflow-y-auto">
                                        <div @click="setTimetableDay('')" class="px-3 py-2 hover:bg-blue-500 hover:text-white cursor-pointer text-sm transition-colors">All Days</div>
                                        <template x-for="day in timetableDays" :key="day.date">
                                            <div @click="setTimetableDay(day.date)" :class="selectedTimetableDay === day.date ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'" class="px-3 py-2 cursor-pointer text-sm transition-colors" x-text="day.label"></div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col flex-1 min-w-[240px]">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                            <input x-model="timetableSearch" @input="filterTimetableEntries()" type="text" placeholder="Search subject, code, or day..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="ml-auto flex gap-2 items-end self-end">
                            <button @click="timetableTypeFilter = 'all'; filterTimetableEntries()" :class="timetableTypeFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 border border-slate-200'" class="px-4 py-2 rounded-lg text-sm font-semibold transition whitespace-nowrap">All Rows</button>
                            <button @click="timetableTypeFilter = 'exam'; filterTimetableEntries()" :class="timetableTypeFilter === 'exam' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-700 border border-slate-200'" class="px-4 py-2 rounded-lg text-sm font-semibold transition whitespace-nowrap">Exam Only</button>
                            <button @click="timetableTypeFilter = 'break'; filterTimetableEntries()" :class="timetableTypeFilter === 'break' ? 'bg-amber-500 text-white' : 'bg-white text-slate-700 border border-slate-200'" class="px-4 py-2 rounded-lg text-sm font-semibold transition whitespace-nowrap">Breaks</button>
                        </div>
                    </div>
                </div>

                <div class="registration-surface registration-table-card overflow-x-auto">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[1120px]">
                            <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-calendar-day mr-1 text-blue-600"></i>Date & Day</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-clock mr-1 text-emerald-600"></i>Time</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-hashtag mr-1 text-amber-600"></i>Hidden Code</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-book mr-1 text-violet-600"></i>Subject</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-language mr-1 text-slate-500"></i>Track</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-layer-group mr-1 text-indigo-600"></i>Session Type</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-note-sticky mr-1 text-slate-500"></i>Operational Note</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="entry in filteredTimetableEntries" :key="entry.key">
                                    <tr :class="entry.type === 'break' ? 'bg-amber-50 hover:bg-amber-100/70' : 'hover:bg-blue-50'" class="transition-colors">
                                        <td class="px-3 py-1.5 text-[13px] font-semibold text-slate-800 whitespace-nowrap" x-text="entry.dayLabel"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-slate-700 whitespace-nowrap" x-text="entry.time"></td>
                                        <td class="px-3 py-1.5 text-[13px] font-mono font-semibold whitespace-nowrap" :class="entry.type === 'break' ? 'text-amber-700' : 'text-blue-700'" x-text="entry.code || '-'"></td>
                                        <td class="px-3 py-1.5 text-[13px] font-medium whitespace-nowrap" :class="entry.type === 'break' ? 'text-amber-900' : 'text-slate-800'" x-text="entry.subject"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-slate-600 whitespace-nowrap" x-text="entry.track"></td>
                                        <td class="px-3 py-1.5 text-sm whitespace-nowrap">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold" :class="entry.type === 'break' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'" x-text="entry.type === 'break' ? 'Break' : 'Exam Sitting'"></span>
                                        </td>
                                        <td class="px-3 py-1.5 text-[13px] text-slate-600 whitespace-nowrap" x-text="entry.note"></td>
                                    </tr>
                                </template>
                                <tr x-show="filteredTimetableEntries.length === 0">
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500">No timetable rows match the current day or search filter.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <section class="grid gap-4 xl:grid-cols-2">
                    <article class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Day One Layout</p>
                        <h3 class="mt-3 text-xl font-black text-slate-900">20.05.2026 · JUMATANO</h3>
                        <div class="mt-4 space-y-3">
                            <template x-for="entry in timetableEntries.filter(item => item.date === '20.05.2026')" :key="entry.key + '-card'">
                                <div class="rounded-2xl border px-4 py-3" :class="entry.type === 'break' ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white'">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900" x-text="entry.subject"></p>
                                            <p class="mt-1 text-xs text-slate-500" x-text="entry.track + ' · ' + entry.note"></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-semibold text-slate-800" x-text="entry.time"></p>
                                            <p class="mt-1 text-xs font-mono text-slate-500" x-text="entry.code || '-'"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </article>
                    <article class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Day Two Layout</p>
                        <h3 class="mt-3 text-xl font-black text-slate-900">21.05.2026 · ALHAMISI</h3>
                        <div class="mt-4 space-y-3">
                            <template x-for="entry in timetableEntries.filter(item => item.date === '21.05.2026')" :key="entry.key + '-card'">
                                <div class="rounded-2xl border px-4 py-3" :class="entry.type === 'break' ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white'">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900" x-text="entry.subject"></p>
                                            <p class="mt-1 text-xs text-slate-500" x-text="entry.track + ' · ' + entry.note"></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-semibold text-slate-800" x-text="entry.time"></p>
                                            <p class="mt-1 text-xs font-mono text-slate-500" x-text="entry.code || '-'"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </article>
                </section>
            </section>
            </section>

            <section x-show="activeTab === 'schools'" class="space-y-6">
                <div class="registration-surface exam-filter-panel exam-section">
                    <div class="exam-filter-form">
                        <div class="exam-field exam-field--compact">
                            <label class="exam-label">Exam Year</label>
                            <input
                                x-model="examYearSearch"
                                @input="syncExamYearSelection()"
                                @change="syncExamYearSelection()"
                                list="psle_schools_exam_year_options"
                                class="exam-search-input"
                                placeholder="Search exam year"
                                autocomplete="off"
                            >
                            <datalist id="psle_schools_exam_year_options">
                                <option value=""></option>
                                <template x-for="year in examYears" :key="'schools-year-' + year.id">
                                    <option :value="year.year_label"></option>
                                </template>
                            </datalist>
                        </div>
                        <div class="exam-field exam-field--compact">
                            <label class="exam-label">Region</label>
                            <input
                                x-model="regionSearch"
                                @input="syncRegionSelection()"
                                @change="syncRegionSelection()"
                                list="psle_schools_region_options"
                                class="exam-search-input"
                                placeholder="Search region"
                                autocomplete="off"
                            >
                            <datalist id="psle_schools_region_options">
                                <option value=""></option>
                                <template x-for="region in regions" :key="'schools-region-' + region.id">
                                    <option :value="region.name"></option>
                                </template>
                            </datalist>
                        </div>
                        <div class="exam-field exam-field--compact">
                            <label class="exam-label">Council</label>
                            <input
                                x-model="districtSearch"
                                @input="syncDistrictSelection()"
                                @change="syncDistrictSelection()"
                                list="psle_schools_district_options"
                                class="exam-search-input"
                                placeholder="Search council"
                                autocomplete="off"
                            >
                            <datalist id="psle_schools_district_options">
                                <option value=""></option>
                                <template x-for="district in filteredDistricts" :key="'schools-district-' + district.id">
                                    <option :value="district.name"></option>
                                </template>
                            </datalist>
                        </div>
                        <div class="exam-field flex-1">
                            <label class="exam-label">Search</label>
                            <input x-model="schoolSearch" @input.debounce.300ms="loadSchools()" type="text" placeholder="Search primary schools..." class="exam-search-input">
                        </div>
                        <div class="exam-actions-row">
                            <button @click="syncNectaSchools()" :disabled="syncingSchools" class="exam-button">
                                <i class="fas" :class="syncingSchools ? 'fa-spinner animate-spin' : 'fa-rotate'"></i>
                                <span x-text="syncingSchools ? 'Syncing...' : (filterRegion ? 'Sync Selected Region' : 'Sync Registered Regions')"></span>
                            </button>
                            <button @click="resetFilters()" class="exam-button-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    </div>
                    <p class="exam-section-text">This sync pulls PSLE 2025 primary schools from the official NECTA site for the selected region, or for all registered regions when no region filter is active.</p>
                </div>

                <div x-show="selectedSchoolItems.size > 0" class="flex gap-2 items-center bg-blue-50 p-4 rounded-lg border border-blue-200 shadow-sm">
                    <span class="text-sm font-medium text-gray-700">
                        <span x-text="selectedSchoolItems.size"></span> school(s) selected
                    </span>
                    <button @click="bulkDeleteSchools()" class="ml-auto bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors text-sm font-medium flex items-center gap-2">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                </div>

                <div class="registration-surface registration-table-card overflow-x-auto">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[820px]">
                            <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-3 text-left">
                                        <input
                                            type="checkbox"
                                            @change="toggleSelectAllSchools()"
                                            :checked="paginatedVisibleSchools.length > 0 && paginatedVisibleSchools.every(school => selectedSchoolItems.has(school.id))"
                                            class="w-4 h-4 cursor-pointer"
                                            title="Select all visible schools"
                                        >
                                    </th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-barcode mr-1 text-blue-600"></i>Code</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-school mr-1 text-purple-600"></i>Primary School</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-building-columns mr-1 text-emerald-600"></i>Ownership</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-map mr-1 text-amber-600"></i>Council</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-location-dot mr-1 text-slate-500"></i>Region</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="school in paginatedVisibleSchools" :key="school.id">
                                    <tr class="hover:bg-blue-50 transition-colors" :class="selectedSchoolItems.has(school.id) ? 'bg-blue-100' : ''">
                                        <td class="px-3 py-1.5 text-left">
                                            <input
                                                type="checkbox"
                                                :checked="selectedSchoolItems.has(school.id)"
                                                @change="toggleSchoolSelection(school.id)"
                                                class="w-4 h-4 cursor-pointer"
                                            >
                                        </td>
                                        <td class="px-3 py-1.5 text-[13px] font-mono font-semibold text-blue-700 bg-blue-50 rounded" x-text="school.code || '-'"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-800 font-medium" x-text="school.name"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600" x-text="school.ownership || '-'"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600" x-text="resolveDistrictName(school)"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600" x-text="resolveRegionName(school)"></td>
                                        <td class="px-3 py-1.5 text-sm">
                                            <div class="flex items-center justify-center gap-1">
                                                <button @click="viewSchool(school)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" title="View School">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button @click="openSchoolPupils(school)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-blue-600 transition hover:bg-blue-50 hover:text-blue-800" title="Open Pupils">
                                                    <i class="fas fa-user-graduate"></i>
                                                </button>
                                                <button @click="deleteSchool(school.id)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-red-600 transition hover:bg-red-50 hover:text-red-800" title="Delete School">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="visibleSchools.length === 0">
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500">No primary schools match the current filters.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-200 bg-gradient-to-r from-slate-50 via-white to-slate-50 px-6 py-5" x-show="visibleSchools.length > 0">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex flex-wrap items-center gap-3 text-sm text-slate-600">
                                <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 font-semibold text-blue-700">
                                    <i class="fas fa-layer-group text-xs"></i>
                                    <span>Page <span x-text="schoolsCurrentPage"></span> of <span x-text="Math.max(schoolsTotalPages, 1)"></span></span>
                                </div>
                                <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5">
                                    <i class="fas fa-table-list text-xs text-slate-400"></i>
                                    <span>Showing <span class="font-semibold text-slate-800" x-text="paginatedVisibleSchools.length"></span> of <span class="font-semibold text-slate-800" x-text="visibleSchools.length"></span> schools</span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                                <button @click="goToFirstSchoolsPage()" :disabled="schoolsCurrentPage <= 1" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40" title="First page">
                                    <i class="fas fa-angles-left text-xs"></i>
                                </button>
                                <button @click="goToPreviousSchoolsPage()" :disabled="schoolsCurrentPage <= 1" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40" title="Previous page">
                                    <i class="fas fa-chevron-left text-xs"></i>
                                    <span class="hidden sm:inline">Previous</span>
                                </button>
                                <div class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-2 py-2 shadow-sm">
                                    <template x-for="page in visibleSchoolPages" :key="page">
                                        <button @click="goToSchoolsPage(page)" :class="schoolsCurrentPage === page ? 'bg-blue-600 text-white shadow-md shadow-blue-200/80' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'" class="min-w-[2.5rem] rounded-xl px-3 py-2 text-sm font-semibold transition-colors" x-text="page"></button>
                                    </template>
                                </div>
                                <button @click="goToNextSchoolsPage()" :disabled="schoolsCurrentPage >= schoolsTotalPages" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40" title="Next page">
                                    <span class="hidden sm:inline">Next</span>
                                    <i class="fas fa-chevron-right text-xs"></i>
                                </button>
                                <button @click="goToLastSchoolsPage()" :disabled="schoolsCurrentPage >= schoolsTotalPages" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40" title="Last page">
                                    <i class="fas fa-angles-right text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section x-show="activeTab === 'pupils'" class="space-y-6">
                <div class="registration-surface exam-filter-panel exam-section">
                    <div class="exam-filter-form">
                        <div class="exam-field exam-field--compact">
                            <label class="exam-label">Region</label>
                            <input
                                x-model="regionSearch"
                                @input="syncRegionSelection()"
                                @change="syncRegionSelection()"
                                list="psle_pupils_region_options"
                                class="exam-search-input"
                                placeholder="Search region"
                                autocomplete="off"
                            >
                            <datalist id="psle_pupils_region_options">
                                <option value=""></option>
                                <template x-for="region in regions" :key="'pupils-region-' + region.id">
                                    <option :value="region.name"></option>
                                </template>
                            </datalist>
                        </div>
                        <div class="exam-field exam-field--compact">
                            <label class="exam-label">Council</label>
                            <input
                                x-model="districtSearch"
                                @input="syncDistrictSelection()"
                                @change="syncDistrictSelection()"
                                list="psle_pupils_district_options"
                                class="exam-search-input"
                                placeholder="Search council"
                                autocomplete="off"
                            >
                            <datalist id="psle_pupils_district_options">
                                <option value=""></option>
                                <template x-for="district in filteredDistricts" :key="'pupils-district-' + district.id">
                                    <option :value="district.name"></option>
                                </template>
                            </datalist>
                        </div>
                        <div class="exam-field" style="min-width: 320px;">
                            <label class="exam-label">School</label>
                            <input
                                x-model="schoolOptionSearch"
                                @input="syncSchoolSelection()"
                                @change="syncSchoolSelection()"
                                list="psle_pupils_school_options"
                                class="exam-search-input disabled:bg-gray-100 disabled:cursor-not-allowed disabled:text-gray-400"
                                :disabled="!filterDistrict"
                                :placeholder="filterDistrict ? 'Search school' : 'Select Council First'"
                                autocomplete="off"
                            >
                            <datalist id="psle_pupils_school_options">
                                <option value=""></option>
                                <template x-for="school in filteredSchools" :key="'pupils-school-' + school.id">
                                    <option :value="formatSchoolOptionLabel(school)"></option>
                                </template>
                            </datalist>
                        </div>
                        <div class="exam-field flex-1">
                            <label class="exam-label">Search</label>
                            <input x-model="candidateSearch" @input.debounce.300ms="loadCandidates()" type="text" placeholder="Search pupils..." class="exam-search-input">
                        </div>
                        <div class="exam-actions-row">
                            <button @click="openToolsModal()" class="exam-button">
                                <i class="fas fa-wrench"></i> Tools
                                <i class="fas fa-arrow-up-right-from-square text-xs opacity-80"></i>
                            </button>
                            <button @click="exportCandidatesCSV()" class="exam-button">
                                <i class="fas fa-file-csv"></i> Export
                            </button>
                            <button @click="openCandidateModal()" class="exam-button-secondary" style="background:#16a34a; border-color:#16a34a; color:#ffffff;">
                                <i class="fas fa-plus"></i> Add Pupil
                            </button>
                        </div>
                    </div>
                </div>

                <div x-show="selectedCandidateItems.size > 0" class="flex gap-2 items-center bg-blue-50 p-4 rounded-lg border border-blue-200 shadow-sm">
                    <span class="text-sm font-medium text-gray-700">
                        <span x-text="selectedCandidateItems.size"></span> pupil(s) selected
                    </span>
                    <button @click="bulkDeleteCandidates()" class="ml-auto bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors text-sm font-medium flex items-center gap-2">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                </div>

                <div class="registration-surface registration-table-card overflow-x-auto">
                    <div x-show="loadingCandidates" class="p-8 text-center text-slate-500">
                        <i class="fas fa-spinner animate-spin text-2xl"></i>
                    </div>
                    <div x-show="!loadingCandidates" class="overflow-x-auto">
                        <table class="w-full min-w-[980px]">
                            <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-3 text-left">
                                        <input
                                            type="checkbox"
                                            @change="toggleSelectAllCandidates()"
                                            :checked="candidates.length > 0 && candidates.every(candidate => selectedCandidateItems.has(candidate.id))"
                                            class="w-4 h-4 cursor-pointer"
                                            title="Select all visible pupils"
                                        >
                                    </th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-barcode mr-1 text-blue-600"></i>Candidate Number</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-id-card mr-1 text-indigo-600"></i>PReM No</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-user mr-1 text-gray-600"></i>Pupil Name</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-venus-mars mr-1"></i>Sex</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-school mr-1 text-purple-600"></i>Primary School</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-map mr-1"></i>Council</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-book mr-1 text-emerald-600"></i>Subjects</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-info-circle mr-1"></i>Status</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="candidate in candidates" :key="candidate.id">
                                    <tr class="hover:bg-blue-50 transition-colors" :class="selectedCandidateItems.has(candidate.id) ? 'bg-blue-100' : ''">
                                        <td class="px-3 py-1.5 text-left">
                                            <input
                                                type="checkbox"
                                                :checked="selectedCandidateItems.has(candidate.id)"
                                                @change="toggleCandidateSelection(candidate.id)"
                                                class="w-4 h-4 cursor-pointer"
                                            >
                                        </td>
                                        <td class="px-3 py-1.5 text-[13px] font-mono font-semibold text-blue-700 bg-blue-50 rounded" x-text="candidate.candidate_id || '-'"></td>
                                        <td class="px-3 py-1.5 text-[13px] font-mono text-gray-600" x-text="candidate.prem_no || '-'"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-800 font-medium" x-text="candidate.full_name"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600 text-center font-medium" x-text="candidate.gender === 'M' ? '♂ M' : (candidate.gender === 'F' ? '♀ F' : '-')"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600" x-text="candidate.school_name || '-'"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600" x-text="candidate.district_name || resolveCandidateDistrict(candidate)"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600" x-text="candidate.allocated_subjects && candidate.allocated_subjects.length ? candidate.allocated_subjects.map(subject => subject.code).join(', ') : '-'"></td>
                                        <td class="px-3 py-1.5 text-sm">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold" :class="(candidate.status || 'registered') === 'registered' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'" x-text="candidate.status || 'registered'"></span>
                                        </td>
                                        <td class="px-3 py-1.5 text-sm">
                                            <div class="flex gap-1">
                                                <button @click="viewCandidate(candidate)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" title="View Pupil"><i class="fas fa-eye"></i></button>
                                                <button @click="editCandidate(candidate)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-blue-600 transition hover:bg-blue-50 hover:text-blue-800"><i class="fas fa-edit"></i></button>
                                                <button @click="deleteCandidate(candidate.id)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-red-600 transition hover:bg-red-50 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="!loadingCandidates && candidates.length === 0">
                                    <td colspan="10" class="px-6 py-10 text-center text-sm text-slate-500">No pupils found for the current PSLE filters.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-200 bg-gradient-to-r from-slate-50 via-white to-slate-50 px-6 py-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex flex-wrap items-center gap-3 text-sm text-slate-600">
                                <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 font-semibold text-blue-700">
                                    <i class="fas fa-layer-group text-xs"></i>
                                    <span>Page <span x-text="currentPage"></span> of <span x-text="Math.max(totalPages, 1)"></span></span>
                                </div>
                                <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5">
                                    <i class="fas fa-table-list text-xs text-slate-400"></i>
                                    <span>Showing <span class="font-semibold text-slate-800" x-text="candidates.length"></span> of <span class="font-semibold text-slate-800" x-text="totalCandidates"></span> pupils</span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                                <button @click="goToFirstCandidatesPage()" :disabled="currentPage <= 1" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40" title="First page">
                                    <i class="fas fa-angles-left text-xs"></i>
                                </button>
                                <button @click="goToPreviousCandidatesPage()" :disabled="currentPage <= 1" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40" title="Previous page">
                                    <i class="fas fa-chevron-left text-xs"></i>
                                    <span class="hidden sm:inline">Previous</span>
                                </button>
                                <div class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-2 py-2 shadow-sm">
                                    <template x-for="page in visibleCandidatePages" :key="page">
                                        <button @click="goToCandidatesPage(page)" :class="currentPage === page ? 'bg-blue-600 text-white shadow-md shadow-blue-200/80' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'" class="min-w-[2.5rem] rounded-xl px-3 py-2 text-sm font-semibold transition-colors" x-text="page"></button>
                                    </template>
                                </div>
                                <button @click="goToNextCandidatesPage()" :disabled="currentPage >= totalPages" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40" title="Next page">
                                    <span class="hidden sm:inline">Next</span>
                                    <i class="fas fa-chevron-right text-xs"></i>
                                </button>
                                <button @click="goToLastCandidatesPage()" :disabled="currentPage >= totalPages" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40" title="Last page">
                                    <i class="fas fa-angles-right text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div
                x-show="subjectModalOpen"
                class="fixed inset-0 z-[9996] flex items-center justify-center bg-slate-950/55 p-4"
                style="display: none;"
                @click.self="subjectModalOpen = false"
            >
                <div class="registration-modal-shell max-w-3xl">
                    <div class="registration-modal-header">
                        <div class="registration-modal-header-content">
                            <div>
                                <span class="registration-modal-kicker"><i class="fas fa-book text-amber-300"></i>PSLE Subject</span>
                                <h2 class="registration-modal-title" x-text="viewingSubject.name || 'Subject Details'"></h2>
                                <p class="registration-modal-subtitle">Review the official PSLE subject record synchronized from the NECTA catalog.</p>
                            </div>
                            <button @click="subjectModalOpen = false" class="registration-modal-close">&times;</button>
                        </div>
                    </div>
                    <div class="registration-modal-body">
                        <div class="registration-modal-panel p-6 space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Subject Code</label>
                                    <input type="text" readonly :value="viewingSubject.code || '-'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-mono text-slate-700">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Subject Group</label>
                                    <input type="text" readonly :value="formatPsleCategory(viewingSubject.category)" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Official Subject Name</label>
                                <input type="text" readonly :value="viewingSubject.name || '-'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Description</label>
                                <textarea readonly rows="3" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700" x-text="viewingSubject.description || '-'"></textarea>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Official Source</label>
                                <input type="text" readonly :value="paperSourceLabel(viewingSubject)" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            </div>
                            <div class="registration-modal-actions">
                                <button type="button" @click="subjectModalOpen = false" class="registration-modal-button registration-modal-button-secondary">Close</button>
                                <button type="button" @click="subjectModalOpen = false; openSubjectPapers(viewingSubject)" class="registration-modal-button registration-modal-button-secondary">Open Paper Governance</button>
                                <button type="button" @click="openOfficialPaperSource(viewingSubject)" class="registration-modal-button registration-modal-button-primary">Open Official Source</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                x-show="paperGuidanceModalOpen"
                class="fixed inset-0 z-[9996] flex items-center justify-center bg-slate-950/55 p-4"
                style="display: none;"
                @click.self="paperGuidanceModalOpen = false"
            >
                <div class="registration-modal-shell max-w-3xl">
                    <div class="registration-modal-header">
                        <div class="registration-modal-header-content">
                            <div>
                                <span class="registration-modal-kicker"><i class="fas fa-scroll text-amber-300"></i>PSLE Paper Governance</span>
                                <h2 class="registration-modal-title">Official Paper Structure Policy</h2>
                                <p class="registration-modal-subtitle">This workspace governs readiness for PSLE paper definitions without publishing unverified structure data.</p>
                            </div>
                            <button @click="paperGuidanceModalOpen = false" class="registration-modal-close">&times;</button>
                        </div>
                    </div>
                    <div class="registration-modal-body">
                        <div class="registration-modal-panel p-6 space-y-4 text-sm leading-7 text-slate-600">
                            <p>PSLE paper structure must be driven by official NECTA examination format documents. Until those formats are loaded into IRMS, this section remains read-only and governance-focused.</p>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="font-semibold text-slate-900">Required official data before activation</p>
                                <ul class="mt-3 space-y-2">
                                    <li>Subject-specific paper title or format reference</li>
                                    <li>Paper count and sequencing where officially defined</li>
                                    <li>Duration and marks allocation</li>
                                    <li>Source document or official publication reference</li>
                                </ul>
                            </div>
                            <div class="registration-modal-actions">
                                <button type="button" @click="paperGuidanceModalOpen = false" class="registration-modal-button registration-modal-button-primary">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                x-show="paperSubjectModalOpen"
                class="fixed inset-0 z-[9996] flex items-center justify-center bg-slate-950/55 p-4"
                style="display: none;"
                @click.self="paperSubjectModalOpen = false"
            >
                <div class="registration-modal-shell max-w-3xl">
                    <div class="registration-modal-header">
                        <div class="registration-modal-header-content">
                            <div>
                                <span class="registration-modal-kicker"><i class="fas fa-book-open text-amber-300"></i>Paper Subject Review</span>
                                <h2 class="registration-modal-title" x-text="viewingPaperSubject.name || 'PSLE Subject'"></h2>
                                <p class="registration-modal-subtitle">Governance review for PSLE paper structure readiness.</p>
                            </div>
                            <button @click="paperSubjectModalOpen = false" class="registration-modal-close">&times;</button>
                        </div>
                    </div>
                    <div class="registration-modal-body">
                        <div class="registration-modal-panel p-6 space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Subject Code</label>
                                    <input type="text" readonly :value="viewingPaperSubject.code || '-'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-mono text-slate-700">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Subject Group</label>
                                    <input type="text" readonly :value="formatPsleCategory(viewingPaperSubject.category)" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Format Status</label>
                                <input type="text" readonly :value="paperStatusLabel(viewingPaperSubject)" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Official Source</label>
                                <textarea readonly rows="2" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700" x-text="paperSourceLabel(viewingPaperSubject)"></textarea>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Governance Position</label>
                                <textarea readonly rows="3" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700" x-text="paperGovernanceLongNote(viewingPaperSubject)"></textarea>
                            </div>
                            <div class="registration-modal-actions">
                                <button type="button" @click="paperSubjectModalOpen = false" class="registration-modal-button registration-modal-button-secondary">Close</button>
                                <button type="button" @click="openOfficialPaperSource(viewingPaperSubject)" class="registration-modal-button registration-modal-button-secondary">Open Source</button>
                                <button type="button" @click="paperSubjectModalOpen = false; openPaperGuidanceModal()" class="registration-modal-button registration-modal-button-primary">View Governance Notes</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                x-show="timetableSourceModalOpen"
                class="fixed inset-0 z-[9996] flex items-center justify-center bg-slate-950/55 p-4"
                style="display: none;"
                @click.self="timetableSourceModalOpen = false"
            >
                <div class="registration-modal-shell max-w-4xl">
                    <div class="registration-modal-header">
                        <div class="registration-modal-header-content">
                            <div>
                                <span class="registration-modal-kicker"><i class="fas fa-calendar-days text-amber-300"></i>PSLE Timetable Source</span>
                                <h2 class="registration-modal-title">Zonal Timetable Reference</h2>
                                <p class="registration-modal-subtitle">This timetable tab is derived from the zonal PSLE LaTeX source used for the May 2026 mock examination programme.</p>
                            </div>
                            <button @click="timetableSourceModalOpen = false" class="registration-modal-close">&times;</button>
                        </div>
                    </div>
                    <div class="registration-modal-body">
                        <div class="registration-modal-panel space-y-5 p-6">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm leading-7 text-slate-600">
                                <p class="font-semibold text-slate-900">Source heading</p>
                                <p class="mt-2">OFISI YA WAZIRI MKUU, TAWALA ZA MIKOA NA SERIKALI ZA MITAA, KANDA MAALUMU YA KITAALUMA.</p>
                                <p class="mt-2">RATIBA YA MTIHANI WA UTAMILIFU DARASA LA SABA KANDA YA KITAALUMA, MEI 2026.</p>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Regions Covered</p>
                                    <p class="mt-3 text-sm font-semibold leading-7 text-slate-900">TANGA, IRINGA, SINGIDA, MOROGORO, DODOMA, LINDI, MTWARA, and TABORA.</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Controlled Columns</p>
                                    <p class="mt-3 text-sm font-semibold leading-7 text-slate-900">Date and day, time, hidden code, and subject are kept exactly in the structure used by the LaTeX timetable.</p>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-sm leading-7 text-emerald-900">
                                <p class="font-semibold">Governance position</p>
                                <p class="mt-2">This workspace mirrors the zonal timetable as an administrative reference. It does not infer extra sessions, durations, or sequence changes beyond the source document.</p>
                            </div>
                            <div class="registration-modal-actions">
                                <button type="button" @click="timetableSourceModalOpen = false" class="registration-modal-button registration-modal-button-secondary">Close</button>
                                <button type="button" @click="printTimetable()" class="registration-modal-button registration-modal-button-primary">Print Timetable</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                x-show="toolsModalOpen"
                class="fixed inset-0 z-[9998] flex items-center justify-center bg-slate-950/55 p-4"
                style="display: none;"
                @click.self="closeToolsModal()"
                @keydown.escape.window="closeToolsModal()"
                x-transition.opacity
            >
                <div class="w-full max-w-4xl overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-2xl shadow-slate-900/20" x-transition>
                    <div class="relative overflow-hidden border-b border-slate-200 bg-gradient-to-r from-slate-900 via-blue-900 to-emerald-800 px-6 py-6 text-white">
                        <div class="absolute inset-y-0 right-0 w-56 bg-[radial-gradient(circle_at_top_right,_rgba(255,255,255,0.16),_transparent_68%)]"></div>
                        <div class="relative flex items-start justify-between gap-4">
                            <div>
                                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-white/80">
                                    <i class="fas fa-screwdriver-wrench text-[0.7rem] text-amber-300"></i>
                                    Pupil Tools
                                </span>
                                <h2 class="mt-4 text-2xl font-bold tracking-tight text-white">PSLE pupil import and export workspace</h2>
                                <p class="mt-2 max-w-2xl text-sm leading-6 text-white/80">
                                    Launch the candidate registration workspace, download a PSLE-ready template, export the current filtered pupil list, or open the pupil form from one controlled panel.
                                </p>
                            </div>
                            <button @click="closeToolsModal()" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/15 bg-white/10 text-lg text-white/80 transition hover:bg-white/15 hover:text-white" type="button" aria-label="Close tools">&times;</button>
                        </div>
                    </div>
                    <div class="grid gap-4 bg-slate-50 p-6 md:grid-cols-2 xl:grid-cols-4">
                        <button type="button" @click="launchCandidateImportFlow()" class="group flex h-full flex-col rounded-3xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-100/70">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-blue-700"><i class="fas fa-file-import text-lg"></i></span>
                            <h3 class="mt-5 text-base font-bold text-slate-900">Import Pupils</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Open the main candidate import workspace to validate and upload PSLE pupil records in bulk.</p>
                            <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-blue-700">Open import workspace<i class="fas fa-arrow-right text-xs transition group-hover:translate-x-0.5"></i></span>
                        </button>
                        <button type="button" @click="downloadCandidateTemplate()" class="group flex h-full flex-col rounded-3xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-100/70">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-700"><i class="fas fa-download text-lg"></i></span>
                            <h3 class="mt-5 text-base font-bold text-slate-900">PSLE Template</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Download a simple PSLE pupil CSV template with candidate number, name, sex, and school code columns.</p>
                            <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-amber-700">Download template<i class="fas fa-arrow-right text-xs transition group-hover:translate-x-0.5"></i></span>
                        </button>
                        <button type="button" @click="exportCandidateExcel()" class="group flex h-full flex-col rounded-3xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-100/70">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700"><i class="fas fa-file-excel text-lg"></i></span>
                            <h3 class="mt-5 text-base font-bold text-slate-900">Export Current Data</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Export the currently filtered PSLE pupil list for offline review or downstream reporting.</p>
                            <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-emerald-700">Export filtered pupils<i class="fas fa-arrow-right text-xs transition group-hover:translate-x-0.5"></i></span>
                        </button>
                        <button type="button" @click="openPupilRegistrationFromTools()" class="group flex h-full flex-col rounded-3xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-100/70">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-700"><i class="fas fa-user-plus text-lg"></i></span>
                            <h3 class="mt-5 text-base font-bold text-slate-900">Register Pupil</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Open the embedded PSLE pupil registration form directly from this tools panel.</p>
                            <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-indigo-700">Open pupil form<i class="fas fa-arrow-right text-xs transition group-hover:translate-x-0.5"></i></span>
                        </button>
                    </div>
                    <div class="flex flex-col gap-3 border-t border-slate-200 bg-white px-6 py-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                        <p class="leading-6">This modal reuses the registration workspace pattern while keeping PSLE-specific actions inside the current page.</p>
                        <button type="button" @click="closeToolsModal()" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 font-semibold text-slate-700 transition hover:bg-slate-100">Close</button>
                    </div>
                </div>
            </div>

            <div
                x-show="pupilImportModalOpen"
                class="fixed inset-0 z-[9998] flex items-center justify-center bg-slate-950/55 p-4"
                style="display: none;"
                @click.self="if (!importProcessing) { pupilImportModalOpen = false; resetPupilImportModal(); }"
            >
                <div class="registration-modal-shell max-w-5xl flex flex-col" x-transition @click.stop>
                    <div class="registration-modal-header flex-shrink-0">
                        <div class="registration-modal-header-content">
                            <div>
                                <span class="registration-modal-kicker">
                                    <i class="fas fa-file-import text-amber-300"></i>
                                    PSLE Pupil Import
                                </span>
                                <h2 class="registration-modal-title">Import PSLE Pupils</h2>
                                <p class="registration-modal-subtitle">Validate PSLE pupil files, review duplicates and warnings, and commit only approved records without leaving this workspace.</p>
                            </div>
                            <button
                                @click="if (!importProcessing) { pupilImportModalOpen = false; resetPupilImportModal(); }"
                                class="registration-modal-close"
                                :disabled="importProcessing"
                                type="button"
                            >&times;</button>
                        </div>
                    </div>

                    <div class="registration-modal-body flex-1 space-y-6">
                        <div x-show="importPhase === 'upload'" class="space-y-4">
                            <div class="registration-modal-note max-w-3xl">
                                <div class="registration-modal-note-icon">
                                    <i class="fas fa-circle-info"></i>
                                </div>
                                <div>
                                    <strong>Step 1: Prepare PSLE file</strong>
                                    <p>Use the PSLE pupil template, then upload the completed CSV for duplicate review and validation.</p>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button
                                    type="button"
                                    @click="downloadCandidateTemplate()"
                                    class="registration-modal-button registration-modal-button-success text-sm"
                                    :disabled="importProcessing"
                                >
                                    <i class="fas fa-download"></i> Download PSLE Template
                                </button>
                            </div>

                            <div
                                @drop.prevent="handleImportDrop($event)"
                                @dragover.prevent="importDragActive = true"
                                @dragleave.prevent="importDragActive = false"
                                :class="importDragActive ? 'border-blue-500 bg-blue-50 shadow-[0_18px_30px_rgba(59,130,246,0.12)]' : ''"
                                class="registration-dropzone cursor-pointer"
                            >
                                <input
                                    type="file"
                                    id="psle-import-file-input"
                                    @change="handleImportFileSelect($event)"
                                    accept=".csv,.txt"
                                    class="hidden"
                                    :disabled="importProcessing"
                                >
                                <label for="psle-import-file-input" class="cursor-pointer block">
                                    <span class="registration-dropzone-icon">
                                        <i class="fas fa-cloud-arrow-up"></i>
                                    </span>
                                    <p class="text-lg font-semibold text-slate-700">Drop PSLE pupil CSV here or click to select</p>
                                    <p class="text-sm text-slate-500 mt-2">Expected columns: candidate_number, PReM_No, pupil_name, sex, school_code</p>
                                </label>
                            </div>

                            <div x-show="importFile" class="registration-modal-panel border-blue-200 bg-blue-50/80 p-4">
                                <p class="text-sm text-gray-700">
                                    <strong>Selected file:</strong> <span x-text="importFile ? importFile.name : ''"></span>
                                    <span class="text-gray-500" x-text="importFile ? '(' + (importFile.size / 1024).toFixed(1) + ' KB)' : ''"></span>
                                </p>
                            </div>

                            <div class="registration-modal-panel p-4">
                                <p class="text-sm font-semibold text-gray-700 mb-3">If pupil already exists:</p>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="radio" x-model="onExistsMode" value="skip" class="w-4 h-4 cursor-pointer">
                                        <span class="text-sm text-gray-700">
                                            <strong>Skip existing</strong>
                                            <span class="text-gray-500 block text-xs">Do not overwrite existing pupil records</span>
                                        </span>
                                    </label>
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="radio" x-model="onExistsMode" value="replace" class="w-4 h-4 cursor-pointer">
                                        <span class="text-sm text-gray-700">
                                            <strong>Replace existing</strong>
                                            <span class="text-gray-500 block text-xs">Update existing pupil name, sex, PReM number, and school assignment</span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div x-show="importPhase === 'report'" class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-700">Step 2: Review Results</h3>

                            <div class="registration-modal-stats !grid-cols-2 xl:!grid-cols-4">
                                <div class="registration-modal-stat">
                                    <p class="registration-modal-stat-label">Total Rows</p>
                                    <p class="registration-modal-stat-value text-gray-800 mt-1" x-text="importReport.total_rows || 0"></p>
                                </div>
                                <div class="registration-modal-stat">
                                    <p class="registration-modal-stat-label text-blue-600">New</p>
                                    <p class="registration-modal-stat-value text-blue-800 mt-1" x-text="importReport.create_count || 0"></p>
                                </div>
                                <div class="registration-modal-stat">
                                    <p class="registration-modal-stat-label text-purple-600">Will Update</p>
                                    <p class="registration-modal-stat-value text-purple-800 mt-1" x-text="importReport.update_count || 0"></p>
                                </div>
                                <div class="registration-modal-stat">
                                    <p class="registration-modal-stat-label text-amber-600">Will Skip</p>
                                    <p class="registration-modal-stat-value text-amber-800 mt-1" x-text="importReport.skip_count || 0"></p>
                                </div>
                                <div class="registration-modal-stat">
                                    <p class="registration-modal-stat-label text-red-600">Errors</p>
                                    <p class="registration-modal-stat-value text-red-800 mt-1" x-text="importReport.error_count || 0"></p>
                                </div>
                                <div class="registration-modal-stat">
                                    <p class="registration-modal-stat-label text-amber-600">Warnings</p>
                                    <p class="registration-modal-stat-value text-amber-800 mt-1" x-text="importReport.warning_count || 0"></p>
                                </div>
                                <div class="registration-modal-stat">
                                    <p class="registration-modal-stat-label text-green-600">Can Import</p>
                                    <p class="registration-modal-stat-value text-green-800 mt-1" x-text="importReport.can_import ? 'Yes ✓' : 'No ✗'"></p>
                                </div>
                            </div>

                            <div x-show="importReport.warning_count > 0" class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                <p class="font-semibold">Potential duplicates or PSLE data-quality warnings were detected.</p>
                                <p class="mt-1">Review the flagged rows before commit. Warnings do not block import.</p>
                            </div>

                            <div x-show="importReport.error_count > 0" class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <h4 class="font-semibold text-gray-700">Errors Found</h4>
                                    <button
                                        @click="downloadImportErrors()"
                                        class="inline-flex items-center gap-2 px-3 py-1 bg-red-100 text-red-700 rounded text-sm hover:bg-red-200 transition-colors"
                                        :disabled="importReport.error_count === 0"
                                    >
                                        <i class="fas fa-download text-xs"></i> Download Errors
                                    </button>
                                </div>
                                <div class="registration-modal-panel overflow-hidden">
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead class="bg-gray-100 border-b border-gray-200">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Row</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Candidate No</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Pupil Name</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Error</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                <template x-for="(error, idx) in importReport.errors.slice(0, 10)" :key="idx">
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-3 py-2 text-gray-600 text-xs" x-text="error.row_number"></td>
                                                        <td class="px-3 py-2 text-gray-600 font-mono text-xs" x-text="error.candidate_id || '-'"></td>
                                                        <td class="px-3 py-2 text-gray-600 text-xs" x-text="error.full_name || '-'"></td>
                                                        <td class="px-3 py-2">
                                                            <span class="inline-block bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-medium" x-text="error.primary_error"></span>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div x-show="importReport.rows && importReport.rows.length > 0" class="space-y-2">
                                <h4 class="font-semibold text-gray-700">Import Plan</h4>
                                <div class="registration-modal-panel overflow-hidden">
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead class="bg-gray-100 border-b border-gray-200">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Row</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Candidate No</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">PReM No</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Pupil Name</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Status</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Message</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                <template x-for="(row, idx) in importReport.rows.slice(0, 20)" :key="idx">
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-3 py-2 text-gray-600 text-xs" x-text="row.row_number"></td>
                                                        <td class="px-3 py-2 text-gray-600 font-mono text-xs" x-text="row.candidate_id || '-'"></td>
                                                        <td class="px-3 py-2 text-gray-600 font-mono text-xs" x-text="row.prem_no || '-'"></td>
                                                        <td class="px-3 py-2 text-gray-600 text-xs" x-text="row.full_name || '-'"></td>
                                                        <td class="px-3 py-2">
                                                            <template x-if="row.status === 'NEW'"><span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-semibold">NEW</span></template>
                                                            <template x-if="row.status === 'SKIP'"><span class="inline-block bg-slate-100 text-slate-800 px-2 py-1 rounded text-xs font-semibold">SKIP</span></template>
                                                            <template x-if="row.status === 'REPLACE'"><span class="inline-block bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs font-semibold">UPDATE</span></template>
                                                            <template x-if="row.status === 'WARNING'"><span class="inline-block bg-amber-100 text-amber-800 px-2 py-1 rounded text-xs font-semibold">WARNING</span></template>
                                                            <template x-if="row.status === 'ERROR'"><span class="inline-block bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-semibold">ERROR</span></template>
                                                        </td>
                                                        <td class="px-3 py-2 text-xs text-gray-600" x-text="row.messages && row.messages.length ? row.messages[0] : '-'"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-show="importPhase === 'processing'" class="flex flex-col items-center justify-center py-12">
                            <div class="inline-flex h-20 w-20 items-center justify-center rounded-[28px] bg-blue-100 text-blue-700 shadow-inner shadow-blue-200/70 mb-4">
                                <i class="fas fa-spinner animate-spin text-4xl"></i>
                            </div>
                            <p class="text-lg font-semibold text-gray-700">Processing PSLE Import...</p>
                            <p class="text-sm text-gray-500 mt-2" x-text="importProcessingMessage"></p>
                        </div>
                    </div>

                    <div class="registration-modal-actions">
                        <button
                            type="button"
                            @click="pupilImportModalOpen = false; resetPupilImportModal();"
                            class="registration-modal-button registration-modal-button-secondary"
                            :disabled="importProcessing"
                        >Close</button>
                        <button
                            type="button"
                            x-show="importPhase === 'upload'"
                            @click="validateImportFile()"
                            class="registration-modal-button registration-modal-button-primary"
                            :disabled="!importFile || importProcessing"
                        >Validate File</button>
                        <button
                            type="button"
                            x-show="importPhase === 'report'"
                            @click="importPhase = 'upload'"
                            class="registration-modal-button registration-modal-button-secondary"
                            :disabled="importProcessing"
                        >Back</button>
                        <button
                            type="button"
                            x-show="importPhase === 'report'"
                            @click="commitImportFile()"
                            class="registration-modal-button registration-modal-button-primary"
                            :disabled="!importReport.can_import || importProcessing"
                        >Import Pupils</button>
                    </div>
                </div>
            </div>

            <div
                x-show="schoolViewModalOpen"
                class="fixed inset-0 z-[9996] flex items-center justify-center bg-slate-950/55 p-4"
                style="display: none;"
                @click.self="schoolViewModalOpen = false"
            >
                <div class="registration-modal-shell max-w-2xl">
                    <div class="registration-modal-header">
                        <div class="registration-modal-header-content">
                            <div>
                                <span class="registration-modal-kicker"><i class="fas fa-school text-amber-300"></i>PSLE School</span>
                                <h2 class="registration-modal-title">School Details</h2>
                                <p class="registration-modal-subtitle">Review the synchronized NECTA school record and jump directly into the related pupil register.</p>
                            </div>
                            <button @click="schoolViewModalOpen = false" class="registration-modal-close">&times;</button>
                        </div>
                    </div>
                    <div class="registration-modal-body">
                        <div class="registration-modal-panel p-6 space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">School Code</label>
                                    <input type="text" readonly :value="viewingSchool.code || '-'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-mono text-slate-700">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Ownership</label>
                                    <input type="text" readonly :value="viewingSchool.ownership || '-'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Primary School</label>
                                <input type="text" readonly :value="viewingSchool.name || '-'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Council</label>
                                    <input type="text" readonly :value="resolveDistrictName(viewingSchool)" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Region</label>
                                    <input type="text" readonly :value="resolveRegionName(viewingSchool)" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Source</label>
                                <input type="text" readonly :value="viewingSchool.source_system || 'NECTA_PSLE_2025'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            </div>
                            <div class="registration-modal-actions">
                                <button type="button" @click="schoolViewModalOpen = false" class="registration-modal-button registration-modal-button-secondary">Close</button>
                                <button type="button" @click="openSchoolPupils(viewingSchool); schoolViewModalOpen = false" class="registration-modal-button registration-modal-button-primary">Open Pupils</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                x-show="candidateViewModalOpen"
                class="fixed inset-0 z-[9996] flex items-center justify-center bg-slate-950/55 p-4"
                style="display: none;"
                @click.self="candidateViewModalOpen = false"
            >
                <div class="registration-modal-shell max-w-3xl">
                    <div class="registration-modal-header">
                        <div class="registration-modal-header-content">
                            <div>
                                <span class="registration-modal-kicker"><i class="fas fa-user-graduate text-amber-300"></i>PSLE Pupil</span>
                                <h2 class="registration-modal-title">Pupil Details</h2>
                                <p class="registration-modal-subtitle">Review the pupil record, school assignment, and currently allocated PSLE subject set.</p>
                            </div>
                            <button @click="candidateViewModalOpen = false" class="registration-modal-close">&times;</button>
                        </div>
                    </div>
                    <div class="registration-modal-body">
                        <div class="registration-modal-panel p-6 space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Candidate Number</label>
                                    <input type="text" readonly :value="viewingCandidate.candidate_id || '-'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-mono text-slate-700">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Sex</label>
                                    <input type="text" readonly :value="viewingCandidate.gender === 'M' ? 'Male' : (viewingCandidate.gender === 'F' ? 'Female' : '-')" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Pupil Name</label>
                                <input type="text" readonly :value="viewingCandidate.full_name || '-'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Primary School</label>
                                    <input type="text" readonly :value="viewingCandidate.school_name || '-'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Council</label>
                                    <input type="text" readonly :value="viewingCandidate.district_name || resolveCandidateDistrict(viewingCandidate)" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Allocated Subjects</label>
                                <textarea readonly rows="3" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700" x-text="viewingCandidate.allocated_subjects && viewingCandidate.allocated_subjects.length ? viewingCandidate.allocated_subjects.map(subject => subject.code + ' - ' + subject.name).join(', ') : '-'"></textarea>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Status</label>
                                <input type="text" readonly :value="viewingCandidate.status || 'registered'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            </div>
                            <div class="registration-modal-actions">
                                <button type="button" @click="candidateViewModalOpen = false" class="registration-modal-button registration-modal-button-secondary">Close</button>
                                <button type="button" @click="candidateViewModalOpen = false; editCandidate(viewingCandidate)" class="registration-modal-button registration-modal-button-primary">Edit Pupil</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                x-show="candidateModalOpen"
                class="fixed inset-0 z-[9995] flex items-center justify-center bg-slate-950/55 p-4"
                style="display: none;"
                @click.self="candidateModalOpen = false"
            >
                <div class="registration-modal-shell max-w-3xl">
                    <div class="registration-modal-header">
                        <div class="registration-modal-header-content">
                            <div>
                                <span class="registration-modal-kicker"><i class="fas fa-user-graduate text-amber-300"></i>PSLE Pupil</span>
                                <h2 class="registration-modal-title" x-text="editingCandidateId ? 'Edit Pupil' : 'Register New Pupil'"></h2>
                                <p class="registration-modal-subtitle">Capture candidate number, pupil identity, and the primary school assignment used for PSLE administration.</p>
                            </div>
                            <button @click="candidateModalOpen = false" class="registration-modal-close">&times;</button>
                        </div>
                    </div>
                    <form @submit.prevent="saveCandidate()" class="registration-modal-body">
                        <div class="registration-modal-panel space-y-4 p-6">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Candidate Number</label>
                                <input x-model="candidateForm.candidate_id" @input="autoSelectSchool()" type="text" placeholder="e.g., PS0102001-0004" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-100">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Pupil Name</label>
                                <input x-model="candidateForm.full_name" type="text" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-100">
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Sex</label>
                                    <select x-model="candidateForm.gender" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-100">
                                        <option value="">Select sex</option>
                                        <option value="M">Male</option>
                                        <option value="F">Female</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Primary School</label>
                                    <select x-model="candidateForm.school_id" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-100">
                                        <option value="">Select primary school</option>
                                        <template x-for="school in schools" :key="school.id">
                                            <option :value="school.id" x-text="school.code + ' - ' + school.name"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                            <div class="registration-modal-actions">
                                <button type="button" @click="candidateModalOpen = false" class="registration-modal-button registration-modal-button-secondary">Cancel</button>
                                <button type="submit" class="registration-modal-button registration-modal-button-primary" x-text="editingCandidateId ? 'Update Pupil' : 'Register Pupil'"></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function psleManager() {
        return {
            activeTab: 'subjects',
            activeTabClass: 'border-blue-300 bg-[linear-gradient(135deg,#2563eb_0%,#1d4ed8_52%,#0f766e_100%)] text-white shadow-[0_18px_32px_rgba(37,99,235,0.26)] -translate-y-0.5',
            inactiveTabClass: 'border-transparent bg-transparent text-slate-700 hover:border-slate-200 hover:bg-white/90 hover:text-slate-900 hover:shadow-[0_10px_20px_rgba(15,23,42,0.08)]',
            activeIconClass: 'bg-white/18 text-white ring-1 ring-white/18',
            inactiveIconClass: 'bg-slate-100 text-slate-500 ring-1 ring-slate-200 group-hover:bg-blue-50 group-hover:text-blue-700 group-hover:ring-blue-100',
            examTypeCode: 'PSLE',
            examYears: [],
            examYear: '',
            examYearSearch: '',
            subjects: [],
            filteredSubjects: [],
            subjectSearch: '',
            paperSearch: '',
            paperStatusFilter: 'all',
            filteredPaperSubjects: [],
            loadingSubjects: false,
            subjectModalOpen: false,
            regions: [],
            districts: [],
            schools: [],
            filterRegion: '',
            filterDistrict: '',
            filterSchool: '',
            regionOpen: false,
            districtOpen: false,
            schoolOpen: false,
            regionSearch: '',
            districtSearch: '',
            schoolOptionSearch: '',
            schoolSearch: '',
            syncingSchools: false,
            toolsModalOpen: false,
            schoolsCurrentPage: 1,
            schoolsPageSize: 100,
            selectedSchoolItems: new Set(),
            candidates: [],
            loadingCandidates: false,
            candidateSearch: '',
            currentPage: 1,
            candidatePageSize: 100,
            totalPages: 1,
            totalCandidates: 0,
            selectedCandidateItems: new Set(),
            paperGuidanceModalOpen: false,
            paperSubjectModalOpen: false,
            viewingPaperSubject: {},
            viewingSubject: {},
            timetableSearch: '',
            timetableTypeFilter: 'all',
            selectedTimetableDay: '',
            timetableDayOpen: false,
            timetableSourceModalOpen: false,
            schoolViewModalOpen: false,
            candidateViewModalOpen: false,
            viewingSchool: {},
            viewingCandidate: {},
            candidateModalOpen: false,
            editingCandidateId: null,
            candidateForm: { candidate_id: '', full_name: '', gender: '', school_id: '' },
            pupilImportModalOpen: false,
            importFile: null,
            importPhase: 'upload',
            importProcessing: false,
            importProcessingMessage: '',
            importDragActive: false,
            onExistsMode: 'skip',
            importReport: {
                errors: [],
                warnings: [],
                total_rows: 0,
                create_count: 0,
                update_count: 0,
                skip_count: 0,
                error_count: 0,
                warning_count: 0,
                can_import: false,
                rows: [],
                summary: {},
                message: '',
                success: false
            },

            get filteredDistricts() {
                if (!this.filterRegion) return this.districts;
                return this.districts.filter(district => String(district.region_id) === String(this.filterRegion));
            },

            get filteredRegionOptions() {
                const query = (this.regionSearch || '').toLowerCase().trim();
                return this.regions.filter(region => !query || (region.name || '').toLowerCase().includes(query));
            },

            get filteredDistrictOptions() {
                const query = (this.districtSearch || '').toLowerCase().trim();
                return this.filteredDistricts.filter(district => !query || (district.name || '').toLowerCase().includes(query));
            },

            get filteredSchools() {
                return this.schools.filter(school => {
                    const matchesRegion = !this.filterRegion || String(this.resolveRegionId(school)) === String(this.filterRegion);
                    const matchesDistrict = !this.filterDistrict || String(school.district_id) === String(this.filterDistrict);
                    return matchesRegion && matchesDistrict;
                });
            },

            get filteredSchoolOptions() {
                const query = (this.schoolOptionSearch || '').toLowerCase().trim();
                return this.filteredSchools.filter(school => {
                    if (!query) return true;
                    return `${school.code || ''} ${school.name || ''}`.toLowerCase().includes(query);
                });
            },

            get visibleSchools() {
                const query = (this.schoolSearch || '').toLowerCase().trim();
                return this.filteredSchools.filter(school => {
                    if (!query) return true;
                    return `${school.code || ''} ${school.name || ''}`.toLowerCase().includes(query);
                });
            },

            get schoolsTotalPages() {
                return Math.max(Math.ceil(this.visibleSchools.length / this.schoolsPageSize), 1);
            },

            get paginatedVisibleSchools() {
                const start = (this.schoolsCurrentPage - 1) * this.schoolsPageSize;
                return this.visibleSchools.slice(start, start + this.schoolsPageSize);
            },

            get visibleSchoolPages() {
                return this.buildVisiblePages(this.schoolsCurrentPage, this.schoolsTotalPages);
            },

            get syncedCouncilCount() {
                return new Set((this.schools || []).map(school => school.district_id).filter(Boolean)).size;
            },

            get syncedSchoolCount() {
                return (this.schools || []).length;
            },

            get visibleCandidatePages() {
                return this.buildVisiblePages(this.currentPage, this.totalPages);
            },

            async init() {
                this.syncTabFromUrl();
                window.addEventListener('popstate', () => this.syncTabFromUrl());
                await this.loadExamYears();
                await this.loadRegions();
                await this.loadDistricts();
                await this.loadSchools();
                await this.loadSubjects();
                await this.loadCandidates();
            },

            syncTabFromUrl() {
                const params = new URLSearchParams(window.location.search);
                const tab = params.get('tab');
                const validTabs = ['subjects', 'papers', 'timetable', 'schools', 'pupils'];
                this.activeTab = validTabs.includes(tab) ? tab : 'subjects';
            },

            setActiveTab(tab) {
                const validTabs = ['subjects', 'papers', 'timetable', 'schools', 'pupils'];
                if (!validTabs.includes(tab)) return;
                this.activeTab = tab;
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                window.history.replaceState({}, '', url.toString());
            },

            async loadExamYears() {
                try {
                    const response = await fetch('/admin/api/exam-years');
                    const data = await response.json();
                    this.examYears = data.exam_years || [];
                    const active = this.examYears.find(year => year.is_active);
                    if (active && !this.examYear) {
                        this.examYear = String(active.year_label);
                    }
                    this.syncFilterSearchLabels();
                } catch (error) {
                    this.showMessage('Failed to load exam years', 'error');
                }
            },

            async loadRegions() {
                try {
                    const response = await fetch('/admin/api/regions');
                    const data = await response.json();
                    this.regions = data.data || [];
                    this.syncFilterSearchLabels();
                } catch (error) {
                    this.showMessage('Failed to load regions', 'error');
                }
            },

            async loadDistricts() {
                try {
                    const response = await fetch('/admin/api/districts?page_size=999');
                    const data = await response.json();
                    this.districts = data.data || [];
                    this.syncFilterSearchLabels();
                } catch (error) {
                    this.showMessage('Failed to load councils', 'error');
                }
            },

            async loadSchools() {
                try {
                    const params = new URLSearchParams();
                    if (this.filterRegion) params.set('region_id', this.filterRegion);
                    if (this.filterDistrict) params.set('district_id', this.filterDistrict);
                    if (this.schoolSearch) params.set('search', this.schoolSearch);

                    const response = await fetch(`/admin/api/exam-types/psle/schools?${params.toString()}`);
                    const data = await response.json();
                    this.schools = data.data || [];
                    this.schoolsCurrentPage = 1;
                    this.selectedSchoolItems.clear();
                    this.syncFilterSearchLabels();
                } catch (error) {
                    this.showMessage('Failed to load primary schools', 'error');
                }
            },

            async syncNectaSchools() {
                this.syncingSchools = true;
                try {
                    const response = await fetch('/admin/api/exam-types/psle/schools/sync-necta-2025', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            region_id: this.filterRegion || null,
                        }),
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to sync NECTA PSLE schools');
                    }

                    await this.loadDistricts();
                    await this.loadSchools();

                    const summary = data.summary || {};
                    const summaryText = `${summary.regions_processed || 0} region(s), ${summary.districts_synced || 0} council(s), ${summary.schools_synced || 0} school(s) synced`;
                    this.showMessage(summaryText, 'success');
                } catch (error) {
                    this.showMessage(error.message || 'Failed to sync NECTA PSLE schools', 'error');
                } finally {
                    this.syncingSchools = false;
                }
            },

            async loadSubjects() {
                this.loadingSubjects = true;
                try {
                    const response = await fetch('/admin/api/exam-types/PSLE/subjects');
                    const data = await response.json();
                    this.subjects = data.data || [];
                    this.filteredSubjects = this.subjects;
                    this.filteredPaperSubjects = this.subjects;
                } catch (error) {
                    this.showMessage('Failed to load PSLE subjects', 'error');
                } finally {
                    this.loadingSubjects = false;
                }
            },

            filterSubjects() {
                const query = (this.subjectSearch || '').toLowerCase().trim();
                this.filteredSubjects = this.subjects.filter(subject => {
                    if (!query) return true;
                    return `${subject.code || ''} ${subject.name || ''}`.toLowerCase().includes(query);
                });
            },

            openSubjectModal(subject) {
                this.viewingSubject = subject || {};
                this.subjectModalOpen = true;
            },

            openSubjectPapers(subject) {
                this.setActiveTab('papers');
                this.paperSearch = `${subject?.code || ''} ${subject?.name || ''}`.trim();
                this.filterPaperSubjects();
                this.openPaperSubjectModal(subject);
            },

            filterPaperSubjects() {
                const query = (this.paperSearch || '').toLowerCase().trim();
                this.filteredPaperSubjects = this.subjects.filter(subject => {
                    const matchesSearch = !query || `${subject.code || ''} ${subject.name || ''}`.toLowerCase().includes(query);
                    const status = this.paperStatusKey(subject);
                    const matchesStatus = this.paperStatusFilter === 'all' || this.paperStatusFilter === status;
                    return matchesSearch && matchesStatus;
                });
            },

            formatPsleCategory(category) {
                if (category === 'ARTS') return 'Language and Literacy';
                if (category === 'SCIENCE') return 'Mathematics and Science';
                if (category === 'BUSINESS') return 'Social Studies and General Learning';
                return category || '-';
            },

            paperStatusKey(subject) {
                const code = subject?.code || '';
                return this.pslePaperSourceMap[code] ? 'verified' : 'pending';
            },

            paperStatusLabel(subject) {
                return this.paperStatusKey(subject) === 'verified'
                    ? 'Verified Official Format'
                    : 'Awaiting Official Source';
            },

            get pslePaperSourceMap() {
                return {
                    'PSLE-01': 'PSLE Format Booklet 2024 · Section 01 KISWAHILI',
                    'PSLE-02': 'PSLE Format Booklet 2024 · Section 02 ENGLISH LANGUAGE',
                    'PSLE-03': 'PSLE Format Booklet 2024 · Section 03 SOCIAL STUDIES AND VOCATIONAL SKILLS',
                    'PSLE-04': 'PSLE Format Booklet 2024 · Section 04 MATHEMATICS',
                    'PSLE-05': 'PSLE Format Booklet 2024 · Section 05 SCIENCE AND TECHNOLOGY',
                    'PSLE-06': 'PSLE Format Booklet 2024 · Section 06 CIVIC AND MORAL EDUCATION',
                };
            },

            paperSourceLabel(subject) {
                const code = subject?.code || '';
                return this.pslePaperSourceMap[code] || 'Official source not yet attached';
            },

            paperGovernanceNote(subject) {
                return this.paperStatusKey(subject) === 'verified'
                    ? 'Booklet-linked'
                    : 'Awaiting source mapping';
            },

            paperGovernanceLongNote(subject) {
                if (this.paperStatusKey(subject) === 'verified') {
                    return 'This subject is linked to the official NECTA PSLE 2024 format booklet. Internal extraction of paper count, duration, and marks into structured IRMS fields can proceed without relying on assumptions.';
                }

                return 'Paper count, duration, and weighting remain hidden until official source material is verified and loaded. This avoids introducing assumptions into PSLE administration.';
            },

            get verifiedPaperCount() {
                return this.subjects.filter(subject => this.paperStatusKey(subject) === 'verified').length;
            },

            get pendingPaperCount() {
                return this.subjects.filter(subject => this.paperStatusKey(subject) === 'pending').length;
            },

            get timetableDays() {
                return [
                    { date: '20.05.2026', day: 'JUMATANO', label: '20.05.2026 · JUMATANO' },
                    { date: '21.05.2026', day: 'ALHAMISI', label: '21.05.2026 · ALHAMISI' },
                ];
            },

            get timetableEntries() {
                return [
                    {
                        key: '2026-05-20-01',
                        date: '20.05.2026',
                        day: 'JUMATANO',
                        dayLabel: '20.05.2026 · JUMATANO',
                        time: '2:00 -- 3:40',
                        code: '01',
                        subject: 'KISWAHILI',
                        track: 'Kiswahili Medium',
                        type: 'exam',
                        note: 'Opening language paper in the zonal sequence.',
                    },
                    {
                        key: '2026-05-20-break-1',
                        date: '20.05.2026',
                        day: 'JUMATANO',
                        dayLabel: '20.05.2026 · JUMATANO',
                        time: '3:40 -- 4:30',
                        code: '',
                        subject: 'MAPUMZIKO',
                        track: 'Administrative Break',
                        type: 'break',
                        note: 'Controlled rest interval from the source timetable.',
                    },
                    {
                        key: '2026-05-20-04',
                        date: '20.05.2026',
                        day: 'JUMATANO',
                        dayLabel: '20.05.2026 · JUMATANO',
                        time: '4:30 -- 6:30',
                        code: '04',
                        subject: 'HISABATI',
                        track: 'Kiswahili Medium',
                        type: 'exam',
                        note: 'Shared sitting window with the English-medium mathematics paper.',
                    },
                    {
                        key: '2026-05-20-04E',
                        date: '20.05.2026',
                        day: 'JUMATANO',
                        dayLabel: '20.05.2026 · JUMATANO',
                        time: '4:30 -- 6:30',
                        code: '04E',
                        subject: 'MATHEMATICS',
                        track: 'English Medium',
                        type: 'exam',
                        note: 'Parallel bilingual paper in the same zonal sitting window.',
                    },
                    {
                        key: '2026-05-20-break-2',
                        date: '20.05.2026',
                        day: 'JUMATANO',
                        dayLabel: '20.05.2026 · JUMATANO',
                        time: '6:30 -- 8:30',
                        code: '',
                        subject: 'MAPUMZIKO',
                        track: 'Administrative Break',
                        type: 'break',
                        note: 'Controlled rest interval before the final evening sitting.',
                    },
                    {
                        key: '2026-05-20-06',
                        date: '20.05.2026',
                        day: 'JUMATANO',
                        dayLabel: '20.05.2026 · JUMATANO',
                        time: '8:30 -- 10:00',
                        code: '06',
                        subject: 'URAIA NA MAADILI',
                        track: 'Kiswahili Medium',
                        type: 'exam',
                        note: 'Final Kiswahili-medium sitting for day one.',
                    },
                    {
                        key: '2026-05-20-06E',
                        date: '20.05.2026',
                        day: 'JUMATANO',
                        dayLabel: '20.05.2026 · JUMATANO',
                        time: '8:30 -- 10:00',
                        code: '06E',
                        subject: 'CIVIC AND MORAL EDUCATION',
                        track: 'English Medium',
                        type: 'exam',
                        note: 'English-medium parallel paper for the day-one civic session.',
                    },
                    {
                        key: '2026-05-21-02',
                        date: '21.05.2026',
                        day: 'ALHAMISI',
                        dayLabel: '21.05.2026 · ALHAMISI',
                        time: '2:00 -- 3:40',
                        code: '02',
                        subject: 'ENGLISH LANGUAGE',
                        track: 'English Medium',
                        type: 'exam',
                        note: 'Opening paper for day two.',
                    },
                    {
                        key: '2026-05-21-break-1',
                        date: '21.05.2026',
                        day: 'ALHAMISI',
                        dayLabel: '21.05.2026 · ALHAMISI',
                        time: '3:40 -- 4:30',
                        code: '',
                        subject: 'MAPUMZIKO',
                        track: 'Administrative Break',
                        type: 'break',
                        note: 'Controlled rest interval from the zonal source timetable.',
                    },
                    {
                        key: '2026-05-21-05',
                        date: '21.05.2026',
                        day: 'ALHAMISI',
                        dayLabel: '21.05.2026 · ALHAMISI',
                        time: '4:30 -- 6:00',
                        code: '05',
                        subject: 'SAYANSI NA TEKNOLOJIA',
                        track: 'Kiswahili Medium',
                        type: 'exam',
                        note: 'Shared sitting window with the English-medium science paper.',
                    },
                    {
                        key: '2026-05-21-05E',
                        date: '21.05.2026',
                        day: 'ALHAMISI',
                        dayLabel: '21.05.2026 · ALHAMISI',
                        time: '4:30 -- 6:00',
                        code: '05E',
                        subject: 'SCIENCE AND TECHNOLOGY',
                        track: 'English Medium',
                        type: 'exam',
                        note: 'Parallel bilingual paper in the same zonal sitting window.',
                    },
                    {
                        key: '2026-05-21-break-2',
                        date: '21.05.2026',
                        day: 'ALHAMISI',
                        dayLabel: '21.05.2026 · ALHAMISI',
                        time: '6:00 -- 8:00',
                        code: '',
                        subject: 'MAPUMZIKO',
                        track: 'Administrative Break',
                        type: 'break',
                        note: 'Controlled rest interval before the closing paper window.',
                    },
                    {
                        key: '2026-05-21-03',
                        date: '21.05.2026',
                        day: 'ALHAMISI',
                        dayLabel: '21.05.2026 · ALHAMISI',
                        time: '8:00 -- 9:30',
                        code: '03',
                        subject: 'MAARIFA YA JAMII NA STADI ZA KAZI',
                        track: 'Kiswahili Medium',
                        type: 'exam',
                        note: 'Closing Kiswahili-medium sitting in the zonal programme.',
                    },
                    {
                        key: '2026-05-21-03E',
                        date: '21.05.2026',
                        day: 'ALHAMISI',
                        dayLabel: '21.05.2026 · ALHAMISI',
                        time: '8:00 -- 9:30',
                        code: '03E',
                        subject: 'SOCIAL STUDIES AND VOCATIONAL SKILLS',
                        track: 'English Medium',
                        type: 'exam',
                        note: 'Closing English-medium parallel paper for day two.',
                    },
                ];
            },

            get filteredTimetableEntries() {
                const query = (this.timetableSearch || '').toLowerCase().trim();

                return this.timetableEntries.filter(entry => {
                    const matchesDay = !this.selectedTimetableDay || entry.date === this.selectedTimetableDay;
                    const matchesType = this.timetableTypeFilter === 'all' || entry.type === this.timetableTypeFilter;
                    const haystack = [
                        entry.date,
                        entry.day,
                        entry.dayLabel,
                        entry.time,
                        entry.code,
                        entry.subject,
                        entry.track,
                        entry.note,
                    ].join(' ').toLowerCase();
                    const matchesSearch = !query || haystack.includes(query);

                    return matchesDay && matchesType && matchesSearch;
                });
            },

            get timetableExamSlotCount() {
                return this.timetableEntries.filter(entry => entry.type === 'exam').length;
            },

            get timetableBreakCount() {
                return this.timetableEntries.filter(entry => entry.type === 'break').length;
            },

            openPaperGuidanceModal() {
                this.paperGuidanceModalOpen = true;
            },

            openPaperSubjectModal(subject) {
                this.viewingPaperSubject = subject || {};
                this.paperSubjectModalOpen = true;
            },

            openOfficialPaperSource(subject) {
                if (this.paperStatusKey(subject) !== 'verified') {
                    this.showMessage('Official source is not yet attached for this subject.', 'error');
                    return;
                }

                window.open('https://necta.go.tz/webroot/uploads/news/FORMAT_PSLE_2024_ENGLISH.pdf', '_blank', 'noopener');
            },

            filterTimetableEntries() {
                this.timetableDayOpen = false;
            },

            setTimetableDay(date) {
                this.selectedTimetableDay = date;
                this.timetableDayOpen = false;
            },

            openTimetableSourceModal() {
                this.timetableSourceModalOpen = true;
            },

            openTimetablePreview() {
                window.open('/exam-types/psle/timetable/pdf?disposition=inline', '_blank', 'noopener');
            },

            printTimetable() {
                const printWindow = window.open('/exam-types/psle/timetable/pdf?disposition=inline', '_blank');
                if (!printWindow) {
                    this.showMessage('Unable to open PDF print window.', 'error');
                    return;
                }

                const triggerPrint = () => {
                    try {
                        printWindow.focus();
                        printWindow.print();
                    } catch (error) {
                        // Browser PDF viewers vary; opening the inline PDF is the safe fallback.
                    }
                };

                printWindow.onload = triggerPrint;
                setTimeout(triggerPrint, 1800);
            },

            async syncOfficialSubjects() {
                try {
                    const response = await fetch('/admin/api/exam-types/psle/subjects/sync-official', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to synchronize PSLE catalog');
                    }

                    await this.loadSubjects();
                    this.showMessage(data.message || 'Official PSLE subject catalog synchronized.', 'success');
                } catch (error) {
                    this.showMessage(error.message || 'Failed to synchronize PSLE catalog', 'error');
                }
            },

            async loadCandidates() {
                this.loadingCandidates = true;
                try {
                    let url = `/admin/api/exam-types/PSLE/candidates?page=${this.currentPage}&per_page=${this.candidatePageSize}&q=${encodeURIComponent(this.candidateSearch || '')}`;
                    if (this.examYear) url += `&exam_year=${encodeURIComponent(this.examYear)}`;
                    if (this.filterRegion) url += `&region_id=${this.filterRegion}`;
                    if (this.filterDistrict) url += `&district_id=${this.filterDistrict}`;
                    if (this.filterSchool) url += `&school_id=${this.filterSchool}`;

                    const response = await fetch(url);
                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.error || 'Failed to load pupils');
                    }

                    const meta = data.meta || {};
                    this.candidates = (data.data || []).map(candidate => ({
                        ...candidate,
                        district_name: this.resolveCandidateDistrict(candidate),
                        region_name: this.resolveCandidateRegion(candidate),
                    }));
                    this.totalCandidates = meta.total || 0;
                    this.totalPages = meta.last_page || 1;
                    this.selectedCandidateItems.clear();
                } catch (error) {
                    this.showMessage(error.message || 'Failed to load pupils', 'error');
                } finally {
                    this.loadingCandidates = false;
                }
            },

            onRegionChange() {
                this.filterDistrict = '';
                this.filterSchool = '';
                this.currentPage = 1;
                this.loadSchools();
                this.loadCandidates();
            },

            onExamYearChange() {
                this.currentPage = 1;
                this.loadCandidates();
            },

            onDistrictChange() {
                this.filterSchool = '';
                this.currentPage = 1;
                this.loadSchools();
                this.loadCandidates();
            },

            formatSchoolOptionLabel(school) {
                if (!school) return '';
                return `${school.code ? school.code + ' - ' : ''}${school.name || ''}`.trim();
            },

            syncFilterSearchLabels() {
                this.examYearSearch = this.examYear || '';

                this.regionSearch = this.filterRegion
                    ? (this.regions.find(region => String(region.id) === String(this.filterRegion))?.name || '')
                    : '';

                this.districtSearch = this.filterDistrict
                    ? (this.filteredDistricts.find(district => String(district.id) === String(this.filterDistrict))?.name || '')
                    : '';

                this.schoolOptionSearch = this.filterSchool
                    ? this.formatSchoolOptionLabel(this.filteredSchools.find(school => String(school.id) === String(this.filterSchool)))
                    : '';
            },

            syncExamYearSelection() {
                const value = (this.examYearSearch || '').trim();

                if (!value) {
                    if (!this.examYear) return;
                    this.examYear = '';
                    this.onExamYearChange();
                    this.syncFilterSearchLabels();
                    return;
                }

                const match = this.examYears.find(year => String(year.year_label).toLowerCase() === value.toLowerCase());
                if (!match || String(this.examYear) === String(match.year_label)) return;

                this.examYear = String(match.year_label);
                this.onExamYearChange();
                this.syncFilterSearchLabels();
            },

            syncRegionSelection() {
                const value = (this.regionSearch || '').trim();

                if (!value) {
                    if (!this.filterRegion) return;
                    this.filterRegion = '';
                    this.onRegionChange();
                    this.syncFilterSearchLabels();
                    return;
                }

                const match = this.regions.find(region => (region.name || '').toLowerCase() === value.toLowerCase());
                if (!match || String(this.filterRegion) === String(match.id)) return;

                this.filterRegion = match.id;
                this.onRegionChange();
                this.syncFilterSearchLabels();
            },

            syncDistrictSelection() {
                const value = (this.districtSearch || '').trim();

                if (!value) {
                    if (!this.filterDistrict) return;
                    this.filterDistrict = '';
                    this.onDistrictChange();
                    this.syncFilterSearchLabels();
                    return;
                }

                const match = this.filteredDistricts.find(district => (district.name || '').toLowerCase() === value.toLowerCase());
                if (!match || String(this.filterDistrict) === String(match.id)) return;

                this.filterDistrict = match.id;
                this.onDistrictChange();
                this.syncFilterSearchLabels();
            },

            syncSchoolSelection() {
                const value = (this.schoolOptionSearch || '').trim();

                if (!value) {
                    if (!this.filterSchool) return;
                    this.filterSchool = '';
                    this.currentPage = 1;
                    this.loadCandidates();
                    this.syncFilterSearchLabels();
                    return;
                }

                const match = this.filteredSchools.find(school => this.formatSchoolOptionLabel(school).toLowerCase() === value.toLowerCase());
                if (!match || String(this.filterSchool) === String(match.id)) return;

                this.filterSchool = match.id;
                this.currentPage = 1;
                this.loadCandidates();
                this.syncFilterSearchLabels();
            },

            resetFilters() {
                const active = this.examYears.find(year => year.is_active);
                if (active) {
                    this.examYear = String(active.year_label);
                }
                this.filterRegion = '';
                this.filterDistrict = '';
                this.filterSchool = '';
                this.regionOpen = false;
                this.districtOpen = false;
                this.schoolOpen = false;
                this.regionSearch = '';
                this.districtSearch = '';
                this.schoolOptionSearch = '';
                this.schoolSearch = '';
                this.candidateSearch = '';
                this.schoolsCurrentPage = 1;
                this.currentPage = 1;
                this.loadSchools();
                this.loadCandidates();
            },

            selectRegion(regionId) {
                this.filterRegion = regionId;
                this.regionOpen = false;
                this.syncFilterSearchLabels();
                this.onRegionChange();
            },

            selectDistrict(districtId) {
                this.filterDistrict = districtId;
                this.districtOpen = false;
                this.syncFilterSearchLabels();
                this.onDistrictChange();
            },

            selectSchool(schoolId) {
                this.filterSchool = schoolId;
                this.schoolOpen = false;
                this.syncFilterSearchLabels();
                this.currentPage = 1;
                this.loadCandidates();
            },

            buildVisiblePages(currentPage, totalPages) {
                const total = Math.max(totalPages || 1, 1);
                const start = Math.max(1, currentPage - 2);
                const end = Math.min(total, start + 4);
                const adjustedStart = Math.max(1, end - 4);
                return Array.from({ length: end - adjustedStart + 1 }, (_, index) => adjustedStart + index);
            },

            goToSchoolsPage(page) {
                this.schoolsCurrentPage = page;
            },

            goToFirstSchoolsPage() {
                this.schoolsCurrentPage = 1;
            },

            goToPreviousSchoolsPage() {
                if (this.schoolsCurrentPage > 1) this.schoolsCurrentPage--;
            },

            goToNextSchoolsPage() {
                if (this.schoolsCurrentPage < this.schoolsTotalPages) this.schoolsCurrentPage++;
            },

            goToLastSchoolsPage() {
                this.schoolsCurrentPage = this.schoolsTotalPages;
            },

            goToCandidatesPage(page) {
                this.currentPage = page;
                this.loadCandidates();
            },

            goToFirstCandidatesPage() {
                if (this.currentPage <= 1) return;
                this.currentPage = 1;
                this.loadCandidates();
            },

            goToPreviousCandidatesPage() {
                if (this.currentPage <= 1) return;
                this.currentPage--;
                this.loadCandidates();
            },

            goToNextCandidatesPage() {
                if (this.currentPage >= this.totalPages) return;
                this.currentPage++;
                this.loadCandidates();
            },

            goToLastCandidatesPage() {
                if (this.currentPage >= this.totalPages) return;
                this.currentPage = this.totalPages;
                this.loadCandidates();
            },

            async deleteSchool(id) {
                if (!confirm('Delete this school record?')) return;

                try {
                    const response = await fetch(`/admin/api/schools/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });

                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to delete school');
                    }

                    this.selectedSchoolItems.delete(id);
                    await this.loadSchools();
                    this.showMessage('School deleted successfully', 'success');
                } catch (error) {
                    this.showMessage(error.message || 'Failed to delete school', 'error');
                }
            },

            async bulkDeleteSchools() {
                if (this.selectedSchoolItems.size === 0) return;
                const count = this.selectedSchoolItems.size;
                if (!confirm(`Delete ${count} selected school(s)?`)) return;

                try {
                    const response = await fetch('/admin/api/schools/bulk-delete', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ ids: Array.from(this.selectedSchoolItems) }),
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to delete selected schools');
                    }

                    this.selectedSchoolItems.clear();
                    await this.loadSchools();
                    this.showMessage(`${data.deleted || count} school(s) deleted successfully`, 'success');
                } catch (error) {
                    this.showMessage(error.message || 'Failed to delete selected schools', 'error');
                }
            },

            toggleSchoolSelection(id) {
                if (this.selectedSchoolItems.has(id)) {
                    this.selectedSchoolItems.delete(id);
                    return;
                }
                this.selectedSchoolItems.add(id);
            },

            toggleSelectAllSchools() {
                const visibleIds = this.paginatedVisibleSchools.map(school => school.id);
                const allSelected = visibleIds.length > 0 && visibleIds.every(id => this.selectedSchoolItems.has(id));

                if (allSelected) {
                    visibleIds.forEach(id => this.selectedSchoolItems.delete(id));
                    return;
                }

                visibleIds.forEach(id => this.selectedSchoolItems.add(id));
            },

            toggleCandidateSelection(id) {
                if (this.selectedCandidateItems.has(id)) {
                    this.selectedCandidateItems.delete(id);
                    return;
                }
                this.selectedCandidateItems.add(id);
            },

            toggleSelectAllCandidates() {
                const visibleIds = this.candidates.map(candidate => candidate.id);
                const allSelected = visibleIds.length > 0 && visibleIds.every(id => this.selectedCandidateItems.has(id));

                if (allSelected) {
                    visibleIds.forEach(id => this.selectedCandidateItems.delete(id));
                    return;
                }

                visibleIds.forEach(id => this.selectedCandidateItems.add(id));
            },

            async bulkDeleteCandidates() {
                if (this.selectedCandidateItems.size === 0) return;
                const count = this.selectedCandidateItems.size;
                if (!confirm(`Delete ${count} selected pupil record(s)?`)) return;

                try {
                    const response = await fetch('/admin/api/candidates/bulk-delete', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ ids: Array.from(this.selectedCandidateItems) }),
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to delete selected pupils');
                    }

                    this.selectedCandidateItems.clear();
                    await this.loadCandidates();
                    this.showMessage(`${data.deleted || count} pupil(s) deleted successfully`, 'success');
                } catch (error) {
                    this.showMessage(error.message || 'Failed to delete selected pupils', 'error');
                }
            },

            openCandidateModal() {
                this.editingCandidateId = null;
                this.candidateForm = { candidate_id: '', full_name: '', gender: '', school_id: '' };
                this.candidateModalOpen = true;
            },

            openToolsModal() {
                this.toolsModalOpen = true;
            },

            closeToolsModal() {
                this.toolsModalOpen = false;
            },

            launchCandidateImportFlow() {
                this.closeToolsModal();
                this.resetPupilImportModal();
                this.pupilImportModalOpen = true;
            },

            downloadCandidateTemplate() {
                this.closeToolsModal();
                const headers = ['candidate_number', 'PReM_No', 'pupil_name', 'sex', 'school_code'].join(',');
                const sample = ['PS0102001-0001', 'PREM-001', 'PUPIL NAME', 'M', 'PS0102001'].join(',');
                const blob = new Blob([[headers, sample].join('\n')], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `PSLE_pupil_template_${Date.now()}.csv`;
                document.body.appendChild(link);
                link.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(link);
            },

            exportCandidateExcel() {
                this.closeToolsModal();
                this.exportCandidatesCSV();
            },

            openPupilRegistrationFromTools() {
                this.closeToolsModal();
                this.openCandidateModal();
            },

            resetPupilImportModal() {
                this.importFile = null;
                this.importPhase = 'upload';
                this.importProcessing = false;
                this.importProcessingMessage = '';
                this.importDragActive = false;
                this.onExistsMode = 'skip';
                this.importReport = {
                    errors: [],
                    warnings: [],
                    total_rows: 0,
                    create_count: 0,
                    update_count: 0,
                    skip_count: 0,
                    error_count: 0,
                    warning_count: 0,
                    can_import: false,
                    rows: [],
                    summary: {},
                    message: '',
                    success: false
                };
            },

            handleImportFileSelect(event) {
                const files = event.target.files || [];
                if (files.length > 0) {
                    this.importFile = files[0];
                }
            },

            handleImportDrop(event) {
                this.importDragActive = false;
                const files = event.dataTransfer.files || [];
                if (files.length > 0) {
                    this.importFile = files[0];
                }
            },

            async validateImportFile() {
                if (!this.importFile) {
                    this.showMessage('Please select a PSLE pupil import file.', 'error');
                    return;
                }

                this.importProcessing = true;
                this.importPhase = 'processing';
                this.importProcessingMessage = 'Validating PSLE pupil file...';

                try {
                    const formData = new FormData();
                    formData.append('file', this.importFile);
                    formData.append('exam_type', 'PSLE');
                    formData.append('on_exists_mode', this.onExistsMode);

                    const response = await fetch('/admin/api/candidates/import/validate', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: formData,
                    });

                    const data = await response.json();
                    this.importReport = data;
                    this.importPhase = 'report';
                    this.importProcessing = false;

                    if (!data.success && data.error_count > 0) {
                        this.showMessage(`Validation complete: ${data.error_count} error(s) found`, 'error');
                    } else if (data.can_import) {
                        const total = (data.create_count || 0) + (data.update_count || 0);
                        const warningText = data.warning_count > 0 ? ` with ${data.warning_count} warning(s)` : '';
                        this.showMessage(`Validation complete: ${total} pupil record(s) ready to import${warningText}`, data.warning_count > 0 ? 'error' : 'success');
                    } else {
                        this.showMessage('Validation complete: No valid PSLE pupil records to import', 'error');
                    }
                } catch (error) {
                    this.importProcessing = false;
                    this.importPhase = 'upload';
                    this.showMessage('Error validating PSLE pupil file: ' + error.message, 'error');
                }
            },

            async commitImportFile() {
                if (!this.importFile || !this.importReport.can_import) {
                    this.showMessage('Cannot proceed: invalid file or no valid PSLE pupil records.', 'error');
                    return;
                }

                this.importProcessing = true;
                this.importPhase = 'processing';
                this.importProcessingMessage = 'Importing PSLE pupil records...';

                try {
                    const formData = new FormData();
                    formData.append('file', this.importFile);
                    formData.append('exam_type', 'PSLE');
                    formData.append('on_exists_mode', this.onExistsMode);

                    const response = await fetch('/admin/api/candidates/import/commit', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: formData,
                    });

                    const data = await response.json();
                    this.importProcessing = false;

                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Failed to import PSLE pupil records');
                    }

                    await this.loadCandidates();
                    this.activeTab = 'pupils';
                    this.pupilImportModalOpen = false;
                    this.resetPupilImportModal();
                    this.showMessage(data.message || 'PSLE pupil records imported successfully.', 'success');
                } catch (error) {
                    this.importProcessing = false;
                    this.importPhase = 'report';
                    this.showMessage(error.message || 'Failed to import PSLE pupil records', 'error');
                }
            },

            async downloadImportErrors() {
                if (!this.importReport.errors || this.importReport.errors.length === 0) {
                    return;
                }

                try {
                    const response = await fetch('/admin/api/candidates/import/download-errors', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ errors: this.importReport.errors }),
                    });

                    if (!response.ok) {
                        throw new Error('Failed to download error report');
                    }

                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `psle_pupil_import_errors_${Date.now()}.csv`;
                    document.body.appendChild(link);
                    link.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(link);
                } catch (error) {
                    this.showMessage(error.message || 'Failed to download import errors', 'error');
                }
            },

            viewSchool(school) {
                this.viewingSchool = school || {};
                this.schoolViewModalOpen = true;
            },

            openSchoolPupils(school) {
                if (!school) return;
                this.activeTab = 'pupils';
                this.filterRegion = this.resolveRegionId(school) || '';
                this.filterDistrict = school.district_id || '';
                this.filterSchool = school.id || '';
                this.currentPage = 1;
                this.schoolViewModalOpen = false;
                this.loadCandidates();
            },

            viewCandidate(candidate) {
                this.viewingCandidate = candidate || {};
                this.candidateViewModalOpen = true;
            },

            editCandidate(candidate) {
                this.editingCandidateId = candidate.id;
                this.candidateForm = {
                    candidate_id: candidate.candidate_id || '',
                    full_name: candidate.full_name || '',
                    gender: candidate.gender || '',
                    school_id: candidate.school_id || '',
                };
                this.candidateViewModalOpen = false;
                this.candidateModalOpen = true;
            },

            async saveCandidate() {
                try {
                    const payload = {
                        candidate_id: this.candidateForm.candidate_id,
                        full_name: this.candidateForm.full_name,
                        gender: this.candidateForm.gender,
                        school_id: this.candidateForm.school_id,
                        exam_type: 'PSLE',
                        combination: null,
                    };

                    const url = this.editingCandidateId ? `/admin/api/candidates/${this.editingCandidateId}` : '/admin/api/candidates';
                    const method = this.editingCandidateId ? 'PUT' : 'POST';

                    const response = await fetch(url, {
                        method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to save pupil');
                    }

                    this.candidateModalOpen = false;
                    await this.loadCandidates();
                    this.showMessage(this.editingCandidateId ? 'Pupil updated' : 'Pupil registered', 'success');
                } catch (error) {
                    this.showMessage(error.message || 'Failed to save pupil', 'error');
                }
            },

            async deleteCandidate(id) {
                if (!confirm('Delete this pupil record?')) return;
                try {
                    const response = await fetch(`/admin/api/candidates/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });
                    if (!response.ok) {
                        throw new Error('Failed to delete pupil');
                    }
                    await this.loadCandidates();
                    this.showMessage('Pupil deleted', 'success');
                } catch (error) {
                    this.showMessage(error.message || 'Failed to delete pupil', 'error');
                }
            },

            autoSelectSchool() {
                const value = (this.candidateForm.candidate_id || '').trim().toUpperCase();
                const dashIndex = value.indexOf('-');
                if (dashIndex <= 0) return;
                const schoolCode = value.slice(0, dashIndex);
                const school = this.schools.find(item => (item.code || '').toUpperCase() === schoolCode);
                if (school) {
                    this.candidateForm.school_id = school.id;
                }
            },

            resolveRegionId(school) {
                if (school.region_id) return school.region_id;
                const district = this.districts.find(item => String(item.id) === String(school.district_id));
                return district ? district.region_id : '';
            },

            resolveDistrictName(school) {
                const district = this.districts.find(item => String(item.id) === String(school.district_id));
                return district ? district.name : '-';
            },

            resolveRegionName(school) {
                const region = this.regions.find(item => String(item.id) === String(this.resolveRegionId(school)));
                return region ? region.name : '-';
            },

            resolveCandidateDistrict(candidate) {
                const school = this.schools.find(item => String(item.id) === String(candidate.school_id));
                return school ? this.resolveDistrictName(school) : '-';
            },

            resolveCandidateRegion(candidate) {
                const school = this.schools.find(item => String(item.id) === String(candidate.school_id));
                return school ? this.resolveRegionName(school) : '-';
            },

            downloadSubjectsTemplate() {
                const headers = ['Code', 'Name', 'Category', 'Written Papers'].join(',');
                const blob = new Blob([headers], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `PSLE_subjects_template_${Date.now()}.csv`;
                document.body.appendChild(link);
                link.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(link);
            },

            exportCandidatesCSV() {
                const headers = ['Candidate Number', 'PReM No', 'Pupil Name', 'Sex', 'Primary School', 'Council', 'Region', 'Status'].join(',');
                const rows = this.candidates.map(candidate => ([
                    candidate.candidate_id || '',
                    candidate.prem_no || '',
                    candidate.full_name || '',
                    candidate.gender || '',
                    candidate.school_name || '',
                    candidate.district_name || this.resolveCandidateDistrict(candidate),
                    candidate.region_name || this.resolveCandidateRegion(candidate),
                    candidate.status || 'registered',
                ]).map(value => `"${String(value).replace(/"/g, '""')}"`).join(','));
                const blob = new Blob([[headers, ...rows].join('\n')], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `PSLE_pupils_${Date.now()}.csv`;
                document.body.appendChild(link);
                link.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(link);
            },

            showMessage(message, type) {
                const alertDiv = document.createElement('div');
                let bgClass = 'bg-slate-100 text-slate-700 border-slate-300';
                if (type === 'success') bgClass = 'bg-green-100 text-green-700 border-green-300';
                if (type === 'error') bgClass = 'bg-red-100 text-red-700 border-red-300';
                alertDiv.className = `fixed top-24 right-8 ${bgClass} max-w-sm rounded-xl border p-4 shadow-lg z-[10000]`;
                alertDiv.textContent = message;
                document.body.appendChild(alertDiv);
                setTimeout(() => alertDiv.remove(), 4000);
            },
        };
    }
</script>
@endsection
