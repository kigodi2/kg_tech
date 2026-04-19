@extends('layout')

@section('content')
@php
    $officialCatalog = collect(config('csee.official_subjects', []))->values();
    $officialLinks = config('csee.official_links', []);
@endphp
@include('registration.partials.theme')
<div class="registration-shell">
    <div class="registration-page-stack">
        @include('registration.partials.header', [
            'kicker' => 'CSEE Administration Workspace',
            'title' => 'CSEE Configuration',
            'subtitle' => 'Manage Certificate of Secondary Education governance from one workspace aligned to the official NECTA formats booklet, public results directory, yearly centre index, and publications archive.',
            'highlights' => [
                ['icon' => 'fas fa-book', 'text' => 'Official subject catalog'],
                ['icon' => 'fas fa-file-pdf', 'text' => '2022 formats booklet'],
                ['icon' => 'fas fa-square-poll-vertical', 'text' => 'NECTA result references'],
            ],
            'noteTitle' => 'Reference Alignment',
            'noteText' => 'This page is grounded in NECTA CSEE references: the CSEE results directory, the 2025 centre-based results index, the publications archive, and the local CSEE_FORMATS_2022.pdf booklet now available inside IRMS.',
        ])

        <div x-data="cseeManager()" x-init="init()" class="space-y-6">
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="registration-surface p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Official Subjects</p>
                    <div class="mt-3 flex items-end justify-between">
                        <strong class="text-4xl font-black text-slate-900" x-text="officialCatalog.length"></strong>
                        <span class="rounded-2xl bg-blue-50 p-3 text-blue-600"><i class="fas fa-book-open"></i></span>
                    </div>
                    <p class="mt-3 text-sm text-slate-600">Subjects listed from the October 2022 CSEE formats booklet table of contents.</p>
                </article>
                <article class="registration-surface p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Synced To IRMS</p>
                    <div class="mt-3 flex items-end justify-between">
                        <strong class="text-4xl font-black text-slate-900" x-text="subjects.length"></strong>
                        <span class="rounded-2xl bg-emerald-50 p-3 text-emerald-600"><i class="fas fa-rotate"></i></span>
                    </div>
                    <p class="mt-3 text-sm text-slate-600">Official subject records already materialized into the local CSEE subject registry.</p>
                </article>
                <article class="registration-surface p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Local Candidates</p>
                    <div class="mt-3 flex items-end justify-between">
                        <strong class="text-4xl font-black text-slate-900" x-text="candidateMeta.total"></strong>
                        <span class="rounded-2xl bg-amber-50 p-3 text-amber-600"><i class="fas fa-user-graduate"></i></span>
                    </div>
                    <p class="mt-3 text-sm text-slate-600">CSEE candidates currently available through the shared exam-type candidate API.</p>
                </article>
                <article class="registration-surface p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Source Pack</p>
                    <div class="mt-3 flex items-end justify-between">
                        <strong class="text-2xl font-black text-slate-900">4 refs</strong>
                        <span class="rounded-2xl bg-violet-50 p-3 text-violet-600"><i class="fas fa-link"></i></span>
                    </div>
                    <p class="mt-3 text-sm text-slate-600">Results directory, 2025 results index, publications archive, and local official booklet are all wired here.</p>
                </article>
            </section>

            <section class="registration-surface p-4">
                <div class="rounded-[2rem] border border-slate-200 bg-[linear-gradient(180deg,rgba(255,255,255,0.96)_0%,rgba(241,245,249,0.98)_100%)] p-3 shadow-[0_18px_45px_rgba(15,23,42,0.08),inset_0_1px_0_rgba(255,255,255,0.85)]">
                    <div class="flex flex-wrap gap-3 rounded-[1.65rem] border border-white/70 bg-white/55 p-2.5 shadow-[inset_0_1px_0_rgba(255,255,255,0.9)] backdrop-blur-sm">
                        <template x-for="tab in tabs" :key="tab.key">
                            <button
                                type="button"
                                @click="setActiveTab(tab.key)"
                                :class="activeTab === tab.key ? activeTabClass : inactiveTabClass"
                                class="group inline-flex items-center gap-3 rounded-[1.25rem] border px-5 py-3.5 text-sm font-bold transition duration-200"
                            >
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-[1rem] text-sm shadow-sm transition" :class="activeTab === tab.key ? activeIconClass : inactiveIconClass">
                                    <i :class="tab.icon"></i>
                                </span>
                                <span x-text="tab.label"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </section>

            <section x-show="activeTab === 'subjects'" class="space-y-6">
                <div class="registration-surface p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="max-w-4xl">
                            <h2 class="text-2xl font-black text-slate-900">Official CSEE Subject Catalog</h2>
                            <p class="mt-2 text-sm leading-7 text-slate-600">This catalog uses the local `CSEE_FORMATS_2022.pdf` booklet as the official source. The papers tab now reflects the official NECTA subject-by-subject paper structure from that booklet, while the sync action keeps the local registry aligned to the same source.</p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('exam-types.csee.formats.pdf') }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                <i class="fas fa-file-pdf mr-2"></i>Open Local Booklet
                            </a>
                            <button @click="syncOfficialSubjects()" :disabled="syncingSubjects" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
                                <i class="fas mr-2" :class="syncingSubjects ? 'fa-spinner animate-spin' : 'fa-rotate'"></i>
                                <span x-text="syncingSubjects ? 'Syncing...' : 'Sync Official Catalog'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="registration-surface registration-toolbar-card">
                    <div class="registration-toolbar-grid">
                        <div class="flex flex-col flex-1 min-w-[220px]">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                            <input x-model="subjectSearch" @input="filterSubjects()" type="text" placeholder="Search CSEE subject code or name..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="ml-auto flex gap-2 items-end self-end">
                            <button @click="subjectStatusFilter = 'all'; filterSubjects()" :class="subjectStatusFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 border border-slate-200'" class="px-4 py-2 rounded-lg text-sm font-semibold transition">All</button>
                            <button @click="subjectStatusFilter = 'synced'; filterSubjects()" :class="subjectStatusFilter === 'synced' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-700 border border-slate-200'" class="px-4 py-2 rounded-lg text-sm font-semibold transition">Synced</button>
                            <button @click="subjectStatusFilter = 'pending'; filterSubjects()" :class="subjectStatusFilter === 'pending' ? 'bg-amber-500 text-white' : 'bg-white text-slate-700 border border-slate-200'" class="px-4 py-2 rounded-lg text-sm font-semibold transition">Reference Only</button>
                        </div>
                    </div>
                </div>

                <div class="registration-surface registration-table-card overflow-x-auto">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[1120px]">
                            <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap">Code</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap">Subject</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap">NECTA Category</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap">IRMS Stream</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap">Booklet Page</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap">Registry Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="subject in filteredSubjects" :key="subject.code">
                                    <tr class="hover:bg-blue-50 transition-colors">
                                        <td class="px-3 py-1.5 text-[13px] font-mono font-semibold text-blue-700 bg-blue-50 rounded whitespace-nowrap" x-text="subject.code"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-800 font-medium whitespace-nowrap" x-text="subject.name"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600 whitespace-nowrap" x-text="subject.subject_group_label"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600 whitespace-nowrap" x-text="subject.stream_label"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600 whitespace-nowrap" x-text="'p. ' + subject.source_page"></td>
                                        <td class="px-3 py-1.5 text-sm whitespace-nowrap">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold" :class="subject.synced ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'" x-text="subject.synced ? 'Synced to IRMS' : 'Reference only'"></span>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="filteredSubjects.length === 0">
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500">No CSEE subjects match the current search or status filter.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section x-show="activeTab === 'papers'" class="space-y-6">
                <div class="registration-surface p-5">
                    <div class="rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900">
                        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="font-semibold uppercase tracking-[0.18em] text-[11px] text-emerald-800">Official Source On File</p>
                                <p class="mt-1 font-semibold">CERTIFICATE OF SECONDARY EDUCATION EXAMINATION FORMATS, October 2022</p>
                                <p class="mt-1 text-emerald-800/90">The local booklet states that it is the revised version effective from 2023 and covers each subject using Introduction, General Objectives, General Competencies, Content, and Rubric sections.</p>
                            </div>
                            <a href="{{ route('exam-types.csee.formats.pdf') }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center whitespace-nowrap rounded-xl border border-emerald-300 bg-white px-4 py-2.5 font-semibold text-emerald-800 transition hover:bg-emerald-100">
                                <i class="fas fa-file-pdf mr-2"></i>Open Official Booklet
                            </a>
                        </div>
                    </div>
                </div>

                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <article class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Effective Window</p>
                        <div class="mt-3 flex items-end justify-between">
                            <strong class="text-2xl font-black text-slate-900">From 2023</strong>
                            <span class="rounded-2xl bg-blue-50 p-3 text-blue-600"><i class="fas fa-calendar-check"></i></span>
                        </div>
                        <p class="mt-3 text-sm text-slate-600">The foreword states the booklet applies to Form Four national examinations with effect from 2023.</p>
                    </article>
                    <article class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Booklet Size</p>
                        <div class="mt-3 flex items-end justify-between">
                            <strong class="text-2xl font-black text-slate-900">175 pages</strong>
                            <span class="rounded-2xl bg-amber-50 p-3 text-amber-600"><i class="fas fa-book-bookmark"></i></span>
                        </div>
                        <p class="mt-3 text-sm text-slate-600">The local PDF contains a full subject-by-subject format book with appendices.</p>
                    </article>
                    <article class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">IRMS Mode</p>
                        <div class="mt-3 flex items-end justify-between">
                            <strong class="text-2xl font-black text-slate-900">Governance First</strong>
                            <span class="rounded-2xl bg-violet-50 p-3 text-violet-600"><i class="fas fa-shield-halved"></i></span>
                        </div>
                        <p class="mt-3 text-sm text-slate-600">The papers table now shows the NECTA rubric structure directly from the 2022 booklet instead of a placeholder extraction status.</p>
                    </article>
                </section>

                <div class="registration-surface registration-table-card overflow-x-auto">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[980px]">
                            <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-barcode mr-1 text-blue-600"></i>Code</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-book mr-1 text-violet-600"></i>Subject</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-layer-group mr-1 text-emerald-600"></i>NECTA Category</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-copy mr-1 text-amber-600"></i>Paper Count</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-file-lines mr-1 text-slate-500"></i>Booklet Page</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="subject in mergedSubjects" :key="subject.code + '-format'">
                                    <tr class="hover:bg-blue-50 transition-colors">
                                        <td class="px-3 py-1.5 text-[13px] font-mono font-semibold text-blue-700 whitespace-nowrap" x-text="subject.code"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-800 font-medium whitespace-nowrap" x-text="subject.name"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600 whitespace-nowrap" x-text="subject.subject_group_label"></td>
                                        <td class="px-3 py-1.5 text-sm whitespace-nowrap">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold" :class="Number(subject.written_papers || 1) === 2 ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800'" x-text="officialPaperCount(subject)"></span>
                                        </td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600 whitespace-nowrap">
                                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700" x-text="'p. ' + subject.source_page"></span>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="mergedSubjects.length === 0">
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">No CSEE paper structure rows are available from the official catalog.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-200 bg-gradient-to-r from-slate-50 via-white to-slate-50 px-6 py-5" x-show="mergedSubjects.length > 0">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex flex-wrap items-center gap-3 text-sm text-slate-600">
                                <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 font-semibold text-blue-700">
                                    <i class="fas fa-layer-group text-xs"></i>
                                    <span><span x-text="mergedSubjects.length"></span> official subjects loaded</span>
                                </div>
                                <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5">
                                    <i class="fas fa-book-open text-xs text-slate-400"></i>
                                    <span>Source: CSEE Formats 2022 booklet</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section x-show="activeTab === 'timetable'" class="space-y-6">
                <div class="registration-surface p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="max-w-4xl">
                            <h2 class="text-2xl font-black text-slate-900">CSEE Timetable Governance</h2>
                            <p class="mt-2 text-sm leading-7 text-slate-600">Unlike the PSLE page, this CSEE workspace does not yet have a locally staged timetable source file. This section is in place so the navigation matches the expected CSEE administration layout and so timetable governance can be added without reworking the page structure later.</p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ $officialLinks['publications'] ?? '#' }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                <i class="fas fa-arrow-up-right-from-square mr-2"></i>Open Publications
                            </a>
                            <a href="{{ route('exam-types.csee.formats.pdf') }}" target="_blank" rel="noopener noreferrer" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                <i class="fas fa-file-pdf mr-2"></i>Open Formats Booklet
                            </a>
                        </div>
                    </div>
                </div>

                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <article class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Timetable Source</p>
                        <div class="mt-3 flex items-end justify-between">
                            <strong class="text-2xl font-black text-slate-900">Pending</strong>
                            <span class="rounded-2xl bg-amber-50 p-3 text-amber-600"><i class="fas fa-hourglass-half"></i></span>
                        </div>
                        <p class="mt-3 text-sm text-slate-600">No official CSEE timetable PDF or source file is staged locally yet.</p>
                    </article>
                    <article class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Navigation Ready</p>
                        <div class="mt-3 flex items-end justify-between">
                            <strong class="text-2xl font-black text-slate-900">Yes</strong>
                            <span class="rounded-2xl bg-blue-50 p-3 text-blue-600"><i class="fas fa-calendar-days"></i></span>
                        </div>
                        <p class="mt-3 text-sm text-slate-600">The tab and layout are now in place so CSEE can mirror the PSLE workspace structure.</p>
                    </article>
                    <article class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Next Step</p>
                        <div class="mt-3 flex items-end justify-between">
                            <strong class="text-2xl font-black text-slate-900">Import</strong>
                            <span class="rounded-2xl bg-violet-50 p-3 text-violet-600"><i class="fas fa-file-import"></i></span>
                        </div>
                        <p class="mt-3 text-sm text-slate-600">Once you give me the official CSEE timetable source, I can wire preview and download actions here too.</p>
                    </article>
                </section>
            </section>

            <section x-show="activeTab === 'schools'" class="space-y-6">
                <div class="registration-surface p-6">
                    <div class="space-y-4">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between xl:gap-6">
                            <div class="xl:min-w-0">
                                <h2 class="text-2xl font-black text-slate-900">NECTA CSEE Centres Sync</h2>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 xl:flex-nowrap xl:justify-end">
                                <a href="{{ $officialLinks['results_2025'] ?? '#' }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                    <i class="fas fa-arrow-up-right-from-square mr-2"></i>Open 2025 Centre Index
                                </a>
                                <a href="/admin/api/exam-types/csee/schools/import-particulars/template" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                                    <i class="fas fa-file-csv mr-2"></i>Download CSV Template
                                </a>
                                <input x-ref="schoolParticularsCsvInput" type="file" accept=".csv,.txt" class="hidden" @change="handleSchoolParticularsFileChange($event)">
                                <button @click="$refs.schoolParticularsCsvInput.click()" :disabled="uploadingSchoolParticulars || autoEnrichingSchoolParticulars" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
                                    <i class="fas mr-2" :class="uploadingSchoolParticulars ? 'fa-spinner animate-spin' : 'fa-file-arrow-up'"></i>
                                    <span x-text="uploadingSchoolParticulars ? 'Uploading...' : 'Import CSV'"></span>
                                </button>
                                <button @click="autoImportSchoolParticulars()" :disabled="uploadingSchoolParticulars || autoEnrichingSchoolParticulars" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-amber-200 hover:bg-amber-50 hover:text-amber-700 disabled:cursor-not-allowed disabled:opacity-60">
                                    <i class="fas mr-2" :class="autoEnrichingSchoolParticulars ? 'fa-spinner animate-spin' : 'fa-wand-magic-sparkles'"></i>
                                    <span x-text="autoEnrichingSchoolParticulars ? 'Working...' : 'Auto Enrich'"></span>
                                </button>
                                <button @click="syncSchools()" :disabled="syncingSchools" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
                                    <i class="fas mr-2" :class="syncingSchools ? 'fa-spinner animate-spin' : 'fa-rotate'"></i>
                                    <span x-text="syncingSchools ? 'Syncing...' : 'Sync Centres'"></span>
                                </button>
                            </div>
                        </div>
                        <div class="max-w-none">
                            <p class="text-sm leading-7 text-slate-600">This sync reads the 2025 CSEE centre index and materializes each listed centre into the local `schools` table as a secondary centre record. District is the location field used for CSV import and for displaying local centre particulars.</p>
                        </div>
                    </div>
                </div>

                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <article class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Synced Centres</p>
                        <div class="mt-3 flex items-end justify-between">
                            <strong class="text-4xl font-black text-slate-900" x-text="schools.length"></strong>
                            <span class="rounded-2xl bg-blue-50 p-3 text-blue-600"><i class="fas fa-school"></i></span>
                        </div>
                        <p class="mt-3 text-sm text-slate-600">Local CSEE centre records currently stored from the NECTA 2025 index.</p>
                    </article>
                    <article class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Source System</p>
                        <div class="mt-3 flex items-end justify-between">
                            <strong class="text-2xl font-black text-slate-900">NECTA_CSEE_2025</strong>
                            <span class="rounded-2xl bg-emerald-50 p-3 text-emerald-600"><i class="fas fa-database"></i></span>
                        </div>
                        <p class="mt-3 text-sm text-slate-600">All synced records are tagged so they can be queried separately from other school sources.</p>
                    </article>
                    <article class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">School Type</p>
                        <div class="mt-3 flex items-end justify-between">
                            <strong class="text-2xl font-black text-slate-900">SECONDARY</strong>
                            <span class="rounded-2xl bg-amber-50 p-3 text-amber-600"><i class="fas fa-building-columns"></i></span>
                        </div>
                        <p class="mt-3 text-sm text-slate-600">Synced centre entries are stored as secondary-school records for CSEE workflows.</p>
                    </article>
                    <article class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Search Mode</p>
                        <div class="mt-3 flex items-end justify-between">
                            <strong class="text-2xl font-black text-slate-900">Code + Name</strong>
                            <span class="rounded-2xl bg-violet-50 p-3 text-violet-600"><i class="fas fa-magnifying-glass"></i></span>
                        </div>
                        <p class="mt-3 text-sm text-slate-600">Use the local filter to locate a centre by NECTA code or centre name after sync.</p>
                    </article>
                </section>

                <div class="registration-surface registration-toolbar-card">
                    <div class="registration-toolbar-grid">
                        <div class="flex flex-col min-w-[220px]">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Region</label>
                            <div class="relative" @click.outside="schoolRegionOpen = false">
                                <button
                                    @click="schoolRegionOpen = !schoolRegionOpen"
                                    class="w-full border border-gray-300 bg-white px-3 py-2 text-left transition-colors hover:bg-gray-50 flex items-center justify-between rounded-none"
                                >
                                    <span x-text="schoolFilterRegion ? (regions.find(region => String(region.id) === String(schoolFilterRegion))?.name || 'Selected Region') : 'All Regions'" class="text-gray-700 whitespace-nowrap"></span>
                                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                </button>
                                <div x-show="schoolRegionOpen" x-cloak class="absolute top-full left-0 right-0 z-30 flex flex-col overflow-hidden rounded-none border border-t-0 border-gray-300 bg-white">
                                    <input
                                        x-model="schoolRegionSearch"
                                        type="text"
                                        placeholder="Search regions..."
                                        class="filter-search-input border-b border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-0 rounded-none"
                                    >
                                    <div class="max-h-64 overflow-y-auto">
                                        <div @click="schoolFilterRegion = ''; schoolFilterDistrict = ''; schoolRegionOpen = false; loadSchools()" class="cursor-pointer px-3 py-2 text-sm transition-colors hover:bg-blue-500 hover:text-white">
                                            All Regions
                                        </div>
                                        <template x-for="region in regions.filter(region => region.name.toLowerCase().includes(schoolRegionSearch.toLowerCase()))" :key="'school-region-' + region.id">
                                            <div
                                                @click="schoolFilterRegion = String(region.id); schoolFilterDistrict = ''; schoolRegionOpen = false; loadSchools()"
                                                :class="String(schoolFilterRegion) === String(region.id) ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'"
                                                class="cursor-pointer px-3 py-2 text-sm transition-colors"
                                                x-text="region.name"
                                            ></div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col min-w-[220px]">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">District</label>
                            <div class="relative" @click.outside="schoolDistrictOpen = false">
                                <button
                                    @click="schoolDistrictOpen = !schoolDistrictOpen"
                                    class="w-full border border-gray-300 bg-white px-3 py-2 text-left transition-colors hover:bg-gray-50 flex items-center justify-between rounded-none"
                                >
                                    <span x-text="schoolFilterDistrict ? (filteredSchoolDistricts.find(district => String(district.id) === String(schoolFilterDistrict))?.name || 'Selected District') : 'All Districts'" class="text-gray-700 whitespace-nowrap"></span>
                                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                </button>
                                <div x-show="schoolDistrictOpen" x-cloak class="absolute top-full left-0 right-0 z-30 flex flex-col overflow-hidden rounded-none border border-t-0 border-gray-300 bg-white">
                                    <input
                                        x-model="schoolDistrictSearch"
                                        type="text"
                                        placeholder="Search districts..."
                                        class="filter-search-input border-b border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-0 rounded-none"
                                    >
                                    <div class="max-h-64 overflow-y-auto">
                                        <div @click="schoolFilterDistrict = ''; schoolDistrictOpen = false; loadSchools()" class="cursor-pointer px-3 py-2 text-sm transition-colors hover:bg-blue-500 hover:text-white">
                                            All Districts
                                        </div>
                                        <template x-for="district in filteredSchoolDistricts.filter(district => district.name.toLowerCase().includes(schoolDistrictSearch.toLowerCase()))" :key="'school-district-' + district.id">
                                            <div
                                                @click="schoolFilterDistrict = String(district.id); schoolDistrictOpen = false; loadSchools()"
                                                :class="String(schoolFilterDistrict) === String(district.id) ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'"
                                                class="cursor-pointer px-3 py-2 text-sm transition-colors"
                                                x-text="district.name"
                                            ></div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col flex-1 min-w-[220px]">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                            <input x-model="schoolSearch" @input.debounce.300ms="loadSchools()" type="text" placeholder="Search CSEE centres by code or name..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div x-show="selectedSchoolIds.length > 0" class="inline-flex items-center gap-2 rounded-none border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700">
                            <i class="fas fa-check-square"></i>
                            <span x-text="selectedSchoolIds.length + ' selected'"></span>
                        </div>
                        <div class="ml-auto flex gap-2 items-end self-end">
                            <button @click="loadSchools()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg transition-colors text-sm font-medium">
                                <i class="fas fa-rotate mr-2"></i>Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <div class="registration-surface registration-table-card overflow-x-auto">
                    <div x-show="loadingSchools" class="p-8 text-center text-slate-500">
                        <i class="fas fa-spinner animate-spin text-2xl"></i>
                    </div>
                    <div x-show="!loadingSchools" class="overflow-x-auto">
                        <table class="w-full min-w-[980px]">
                            <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap">
                                        <input
                                            type="checkbox"
                                            :checked="allVisibleSchoolsSelected"
                                            @change="toggleAllVisibleSchools($event.target.checked)"
                                            class="h-4 w-4 rounded-none border border-gray-400 text-blue-600 focus:ring-0"
                                            title="Select all visible centres"
                                        >
                                    </th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-barcode mr-1 text-blue-600"></i>Code</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-school mr-1 text-purple-600"></i>Centre Name</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-landmark mr-1 text-emerald-600"></i>Ownership</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-map mr-1 text-amber-600"></i>District</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-location-dot mr-1 text-slate-500"></i>Region</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="school in schools" :key="school.id">
                                    <tr class="hover:bg-blue-50 transition-colors">
                                        <td class="px-3 py-1.5 text-center">
                                            <input
                                                type="checkbox"
                                                :checked="selectedSchoolIds.includes(String(school.id))"
                                                @change="toggleSchoolSelection(school.id, $event.target.checked)"
                                                class="h-4 w-4 rounded-none border border-gray-400 text-blue-600 focus:ring-0"
                                            >
                                        </td>
                                        <td class="px-3 py-1.5 text-[13px] font-mono font-semibold text-blue-700 whitespace-nowrap" x-text="school.code"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-800 font-medium whitespace-nowrap" x-text="school.name"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600 whitespace-nowrap" x-text="school.ownership_label || '-'"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600 whitespace-nowrap" x-text="school.district_name || '-'"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600 whitespace-nowrap" x-text="school.region_name || 'Not yet enriched'"></td>
                                        <td class="px-3 py-1.5 text-sm">
                                            <div class="flex items-center justify-center gap-1">
                                                <button
                                                    type="button"
                                                    @click="viewSchoolParticulars(school)"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                                                    title="View Centre Particulars"
                                                >
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="openEditSchoolModal(school)"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded-md text-blue-600 transition hover:bg-blue-50 hover:text-blue-800"
                                                    title="Edit Centre"
                                                >
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="deleteSchool(school)"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded-md text-rose-600 transition hover:bg-rose-50 hover:text-rose-800"
                                                    title="Delete Centre"
                                                >
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="!loadingSchools && schools.length === 0">
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500">No local CSEE centres found yet. Use `Sync Centres` to import the NECTA 2025 centre index.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-200 bg-gradient-to-r from-slate-50 via-white to-slate-50 px-6 py-5" x-show="schools.length > 0">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex flex-wrap items-center gap-3 text-sm text-slate-600">
                                <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 font-semibold text-blue-700">
                                    <i class="fas fa-layer-group text-xs"></i>
                                    <span>Page <span x-text="schoolsCurrentPage"></span> of <span x-text="Math.max(schoolsTotalPages, 1)"></span></span>
                                </div>
                                <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5">
                                    <i class="fas fa-table-list text-xs text-slate-400"></i>
                                    <span>Showing <span class="font-semibold text-slate-800" x-text="schools.length"></span> of <span class="font-semibold text-slate-800" x-text="schoolsTotalCount"></span> centres</span>
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

            <div x-show="schoolParticularsModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4 py-6" @keydown.escape.window="closeSchoolParticularsModal()">
                <div class="absolute inset-0" @click="closeSchoolParticularsModal()"></div>
                <div class="relative w-full max-w-2xl rounded-[2rem] border border-slate-200 bg-white shadow-[0_25px_80px_rgba(15,23,42,0.22)]">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Centre Particulars</p>
                            <h3 class="mt-2 text-xl font-black text-slate-900" x-text="selectedSchoolParticulars?.name || 'CSEE Centre'"></h3>
                        </div>
                        <button type="button" @click="closeSchoolParticularsModal()" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close centre particulars">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="grid gap-4 px-6 py-6 md:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Centre Name</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900" x-text="selectedSchoolParticulars?.name || '-'"></p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Code</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900" x-text="selectedSchoolParticulars?.code || '-'"></p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Ownership</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900" x-text="selectedSchoolParticulars?.ownership_label || '-'"></p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">District</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900" x-text="selectedSchoolParticulars?.district_name || '-'"></p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 md:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Region</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900" x-text="selectedSchoolParticulars?.region_name || 'Not yet enriched'"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="schoolEditModalOpen" x-cloak class="fixed inset-0 z-[9999] flex items-start justify-center overflow-y-auto bg-slate-950/50 px-4 pb-6 pt-8" @keydown.escape.window="closeEditSchoolModal()">
                <div class="absolute inset-0" @click="closeEditSchoolModal()"></div>
                <div class="relative w-full max-w-2xl rounded-[2rem] border border-slate-200 bg-white shadow-[0_25px_80px_rgba(15,23,42,0.22)]">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-500">Edit Centre</p>
                            <h3 class="mt-2 text-xl font-black text-slate-900" x-text="schoolEditForm.name || 'CSEE Centre'"></h3>
                            <p class="mt-2 text-sm text-slate-600">Update the centre details shown in the CSEE sync register.</p>
                        </div>
                        <button type="button" @click="closeEditSchoolModal()" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close edit centre modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="grid gap-4 px-6 py-6 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Code</label>
                            <input x-model="schoolEditForm.code" type="text" class="w-full rounded-none border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-0">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Ownership</label>
                            <div class="relative" @click.outside="schoolEditOwnershipOpen = false">
                                <button
                                    type="button"
                                    @click="schoolEditOwnershipOpen = !schoolEditOwnershipOpen; schoolEditRegionOpen = false; schoolEditDistrictOpen = false"
                                    class="flex w-full items-center justify-between border border-slate-300 bg-white px-3 py-2.5 text-left text-sm text-slate-700 transition-colors hover:bg-slate-50 rounded-none"
                                >
                                    <span class="truncate" x-text="schoolEditForm.ownership || 'Select ownership'"></span>
                                    <i class="fas fa-chevron-down text-xs text-slate-500"></i>
                                </button>
                                <div x-show="schoolEditOwnershipOpen" x-cloak class="absolute top-full left-0 right-0 z-30 mt-1 flex max-h-56 flex-col overflow-hidden rounded-none border border-slate-300 bg-white">
                                    <input
                                        x-model="schoolEditOwnershipSearch"
                                        type="text"
                                        placeholder="Search ownership..."
                                        class="filter-search-input border-b border-slate-200 px-3 py-2 text-sm rounded-none focus:outline-none focus:ring-0"
                                    >
                                    <div class="max-h-56 overflow-y-auto">
                                        <template x-for="option in filteredSchoolEditOwnershipOptions" :key="'ownership-' + option">
                                            <div
                                                @click="selectSchoolEditOwnership(option)"
                                                :class="schoolEditForm.ownership === option ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'"
                                                class="cursor-pointer px-3 py-2 text-sm transition-colors"
                                                x-text="option"
                                            ></div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Centre Name</label>
                            <input x-model="schoolEditForm.name" type="text" class="w-full rounded-none border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-0">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Region</label>
                            <div class="relative" @click.outside="schoolEditRegionOpen = false">
                                <button
                                    type="button"
                                    @click="schoolEditRegionOpen = !schoolEditRegionOpen; schoolEditOwnershipOpen = false; schoolEditDistrictOpen = false"
                                    class="flex w-full items-center justify-between border border-slate-300 bg-white px-3 py-2.5 text-left text-sm text-slate-700 transition-colors hover:bg-slate-50 rounded-none"
                                >
                                    <span class="truncate" x-text="schoolEditForm.region_id ? (regions.find(region => String(region.id) === String(schoolEditForm.region_id))?.name || 'Selected Region') : 'Select region'"></span>
                                    <i class="fas fa-chevron-down text-xs text-slate-500"></i>
                                </button>
                                <div x-show="schoolEditRegionOpen" x-cloak class="absolute top-full left-0 right-0 z-30 mt-1 flex max-h-56 flex-col overflow-hidden rounded-none border border-slate-300 bg-white">
                                    <input
                                        x-model="schoolEditRegionSearch"
                                        type="text"
                                        placeholder="Search regions..."
                                        class="filter-search-input border-b border-slate-200 px-3 py-2 text-sm rounded-none focus:outline-none focus:ring-0"
                                    >
                                    <div class="max-h-56 overflow-y-auto">
                                        <div @click="selectSchoolEditRegion('')" class="cursor-pointer px-3 py-2 text-sm transition-colors hover:bg-blue-500 hover:text-white">
                                            Select region
                                        </div>
                                        <template x-for="region in filteredSchoolEditRegionOptions" :key="'edit-region-' + region.id">
                                            <div
                                                @click="selectSchoolEditRegion(region.id)"
                                                :class="String(schoolEditForm.region_id) === String(region.id) ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'"
                                                class="cursor-pointer px-3 py-2 text-sm transition-colors"
                                                x-text="region.name"
                                            ></div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">District</label>
                            <div class="relative" @click.outside="schoolEditDistrictOpen = false">
                                <button
                                    type="button"
                                    @click="schoolEditDistrictOpen = !schoolEditDistrictOpen; schoolEditOwnershipOpen = false; schoolEditRegionOpen = false"
                                    class="flex w-full items-center justify-between border border-slate-300 bg-white px-3 py-2.5 text-left text-sm text-slate-700 transition-colors hover:bg-slate-50 rounded-none"
                                >
                                    <span class="truncate" x-text="schoolEditForm.district_id ? (filteredSchoolEditDistricts.find(district => String(district.id) === String(schoolEditForm.district_id))?.name || 'Selected District') : 'Select district'"></span>
                                    <i class="fas fa-chevron-down text-xs text-slate-500"></i>
                                </button>
                                <div x-show="schoolEditDistrictOpen" x-cloak class="absolute top-full left-0 right-0 z-30 mt-1 flex max-h-56 flex-col overflow-hidden rounded-none border border-slate-300 bg-white">
                                    <input
                                        x-model="schoolEditDistrictSearch"
                                        type="text"
                                        placeholder="Search districts..."
                                        class="filter-search-input border-b border-slate-200 px-3 py-2 text-sm rounded-none focus:outline-none focus:ring-0"
                                    >
                                    <div class="max-h-56 overflow-y-auto">
                                        <div @click="selectSchoolEditDistrict('')" class="cursor-pointer px-3 py-2 text-sm transition-colors hover:bg-blue-500 hover:text-white">
                                            Select district
                                        </div>
                                        <template x-for="district in filteredSchoolEditDistrictOptions" :key="'edit-district-' + district.id">
                                            <div
                                                @click="selectSchoolEditDistrict(district.id)"
                                                :class="String(schoolEditForm.district_id) === String(district.id) ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'"
                                                class="cursor-pointer px-3 py-2 text-sm transition-colors"
                                                x-text="district.name"
                                            ></div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button type="button" @click="closeEditSchoolModal()" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="button" @click="saveEditedSchool()" :disabled="savingSchoolEdit" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                            <i class="fas mr-2" :class="savingSchoolEdit ? 'fa-spinner animate-spin' : 'fa-save'"></i>
                            <span x-text="savingSchoolEdit ? 'Saving...' : 'Save Changes'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <div x-show="schoolImportResultsModalOpen" x-cloak class="fixed inset-0 z-[9999] flex items-start justify-center overflow-y-auto bg-slate-950/50 px-4 pb-6 pt-8" @keydown.escape.window="closeSchoolImportResultsModal()">
                <div class="absolute inset-0" @click="closeSchoolImportResultsModal()"></div>
                <div class="relative w-full max-w-3xl rounded-[2rem] border bg-white shadow-[0_25px_80px_rgba(15,23,42,0.22)]" :class="schoolImportFailedRows.length ? 'border-rose-200' : 'border-amber-200'">
                    <div class="flex items-start justify-between gap-4 border-b px-6 py-5" :class="schoolImportFailedRows.length ? 'border-rose-100' : 'border-amber-100'">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em]" :class="schoolImportFailedRows.length ? 'text-rose-500' : 'text-amber-500'" x-text="schoolImportFailedRows.length ? 'Import Errors' : 'Import Summary'"></p>
                            <h3 class="mt-2 text-xl font-black text-slate-900" x-text="schoolImportFailedRows.length ? 'CSEE Particulars Import Failed' : 'CSEE Particulars Import Report'"></h3>
                            <p class="mt-2 text-sm text-slate-600" x-text="schoolImportFailedRows.length ? 'Review the exact row-level errors returned by the backend.' : 'See how many centres were created and how many existing centres were updated during this import.'"></p>
                        </div>
                        <button type="button" @click="closeSchoolImportResultsModal()" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close import results">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="max-h-[26rem] space-y-3 overflow-y-auto px-6 py-6">
                        <template x-if="schoolImportSummary">
                            <div class="grid gap-3 md:grid-cols-4">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Processed</p>
                                    <p class="mt-2 text-2xl font-black text-slate-900" x-text="schoolImportSummary.rows_processed || 0"></p>
                                </div>
                                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Updated</p>
                                    <p class="mt-2 text-2xl font-black text-emerald-800" x-text="schoolImportSummary.rows_replaced || 0"></p>
                                </div>
                                <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Created</p>
                                    <p class="mt-2 text-2xl font-black text-blue-800" x-text="schoolImportSummary.rows_created || 0"></p>
                                </div>
                                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Failed</p>
                                    <p class="mt-2 text-2xl font-black text-rose-800" x-text="schoolImportSummary.rows_failed || 0"></p>
                                </div>
                            </div>
                        </template>

                        <template x-if="schoolImportFailedRows.length">
                            <div class="space-y-3">
                                <template x-for="(error, index) in schoolImportFailedRows" :key="'error-' + index">
                                    <div class="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-900" x-text="error"></div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!schoolImportFailedRows.length && schoolImportSkippedRows.length">
                            <div class="space-y-3">
                                <template x-for="(skip, index) in schoolImportSkippedRows" :key="'skip-' + index">
                                    <div class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-900" x-text="skip"></div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!schoolImportFailedRows.length && !schoolImportSkippedRows.length">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">
                                Import completed successfully. Existing centres were replaced where matched, and new centres were created where missing.
                            </div>
                        </template>
                    </div>
                    <div class="flex justify-end border-t border-slate-200 px-6 py-4">
                        <button type="button" @click="closeSchoolImportResultsModal()" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <section x-show="activeTab === 'candidates'" class="space-y-6">
                <div class="registration-surface registration-toolbar-card">
                    <div class="registration-toolbar-grid">
                        <div class="flex flex-col min-w-[220px]">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Region</label>
                            <div class="relative" @click.outside="candidateRegionOpen = false">
                                <button
                                    @click="candidateRegionOpen = !candidateRegionOpen"
                                    class="w-full h-[40px] border border-gray-300 bg-white px-3 text-left text-sm transition-colors hover:bg-gray-50 flex items-center justify-between rounded-none"
                                >
                                    <span x-text="candidateFilterRegion ? (regions.find(region => String(region.id) === String(candidateFilterRegion))?.name || 'Selected Region') : 'All Regions'" class="text-gray-700 whitespace-nowrap"></span>
                                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                </button>
                                <div x-show="candidateRegionOpen" x-cloak class="absolute top-full left-0 right-0 z-30 flex flex-col overflow-hidden rounded-none border border-t-0 border-gray-300 bg-white">
                                    <input
                                        x-model="candidateRegionSearch"
                                        type="text"
                                        placeholder="Search regions..."
                                        class="filter-search-input border-b border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-0 rounded-none"
                                    >
                                    <div class="max-h-64 overflow-y-auto">
                                        <div @click="candidateFilterRegion = ''; candidateFilterDistrict = ''; candidateFilterSchool = ''; candidateRegionOpen = false; onCandidateRegionChange()" class="cursor-pointer px-3 py-2 text-sm transition-colors hover:bg-blue-500 hover:text-white">
                                            All Regions
                                        </div>
                                        <template x-for="region in filteredCandidateRegionOptions" :key="'candidate-region-' + region.id">
                                            <div
                                                @click="candidateFilterRegion = String(region.id); candidateFilterDistrict = ''; candidateFilterSchool = ''; candidateRegionOpen = false; onCandidateRegionChange()"
                                                :class="String(candidateFilterRegion) === String(region.id) ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'"
                                                class="cursor-pointer px-3 py-2 text-sm transition-colors"
                                                x-text="region.name"
                                            ></div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col min-w-[220px]">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">District</label>
                            <div class="relative" @click.outside="candidateDistrictOpen = false">
                                <button
                                    @click="candidateDistrictOpen = !candidateDistrictOpen"
                                    class="w-full h-[40px] border border-gray-300 bg-white px-3 text-left text-sm transition-colors hover:bg-gray-50 flex items-center justify-between rounded-none"
                                >
                                    <span x-text="candidateFilterDistrict ? (filteredCandidateDistricts.find(district => String(district.id) === String(candidateFilterDistrict))?.name || 'Selected District') : 'All Districts'" class="text-gray-700 whitespace-nowrap"></span>
                                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                </button>
                                <div x-show="candidateDistrictOpen" x-cloak class="absolute top-full left-0 right-0 z-30 flex flex-col overflow-hidden rounded-none border border-t-0 border-gray-300 bg-white">
                                    <input
                                        x-model="candidateDistrictSearch"
                                        type="text"
                                        placeholder="Search districts..."
                                        class="filter-search-input border-b border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-0 rounded-none"
                                    >
                                    <div class="max-h-64 overflow-y-auto">
                                        <div @click="candidateFilterDistrict = ''; candidateFilterSchool = ''; candidateDistrictOpen = false; onCandidateDistrictChange()" class="cursor-pointer px-3 py-2 text-sm transition-colors hover:bg-blue-500 hover:text-white">
                                            All Districts
                                        </div>
                                        <template x-for="district in filteredCandidateDistrictOptions" :key="'candidate-district-' + district.id">
                                            <div
                                                @click="candidateFilterDistrict = String(district.id); candidateFilterSchool = ''; candidateDistrictOpen = false; onCandidateDistrictChange()"
                                                :class="String(candidateFilterDistrict) === String(district.id) ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'"
                                                class="cursor-pointer px-3 py-2 text-sm transition-colors"
                                                x-text="district.name"
                                            ></div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col min-w-[520px]">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Centre</label>
                            <div class="relative" @click.outside="candidateSchoolOpen = false">
                                <button
                                    @click="candidateSchoolOpen = !candidateSchoolOpen"
                                    class="w-full h-[40px] border border-gray-300 bg-white px-3 text-left text-sm transition-colors hover:bg-gray-50 flex items-center justify-between rounded-none"
                                >
                                    <span x-text="candidateFilterSchool ? (filteredCandidateSchools.find(school => String(school.id) === String(candidateFilterSchool)) ? `${filteredCandidateSchools.find(school => String(school.id) === String(candidateFilterSchool)).code} - ${filteredCandidateSchools.find(school => String(school.id) === String(candidateFilterSchool)).name}` : 'Selected Centre') : 'All Centres'" class="text-gray-700 truncate pr-3"></span>
                                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                </button>
                                <div x-show="candidateSchoolOpen" x-cloak class="absolute top-full left-0 z-30 flex w-[520px] max-w-[80vw] flex-col overflow-hidden rounded-none border border-t-0 border-gray-300 bg-white">
                                    <input
                                        x-model="candidateSchoolSearch"
                                        type="text"
                                        placeholder="Search centres..."
                                        class="filter-search-input border-b border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-0 rounded-none"
                                    >
                                    <div class="max-h-64 overflow-y-auto">
                                        <div @click="candidateFilterSchool = ''; candidateSchoolOpen = false; onCandidateSchoolChange()" class="cursor-pointer px-3 py-2 text-sm transition-colors hover:bg-blue-500 hover:text-white">
                                            All Centres
                                        </div>
                                        <template x-for="school in filteredCandidateSchoolOptions" :key="'candidate-school-' + school.id">
                                            <div
                                                @click="candidateFilterSchool = String(school.id); candidateSchoolOpen = false; onCandidateSchoolChange()"
                                                :class="String(candidateFilterSchool) === String(school.id) ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'"
                                                class="cursor-pointer px-3 py-2 text-sm transition-colors"
                                                x-text="`${school.code} - ${school.name}`"
                                            ></div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div x-show="selectedCandidateIds.length > 0" class="inline-flex items-center gap-2 rounded-none border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700">
                            <i class="fas fa-check-square"></i>
                            <span x-text="selectedCandidateIds.length + ' selected'"></span>
                        </div>
                        <div class="w-full md:col-span-2 xl:col-span-4 flex flex-col gap-3 md:flex-row md:items-end">
                            <div class="flex flex-col flex-1 min-w-[220px]">
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Search</label>
                                <input x-model="candidateSearch" @input.debounce.300ms="refreshCandidates()" type="text" placeholder="Search index number, candidate, or centre..." class="w-full h-[40px] px-3 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-0" style="height: 40px !important; -webkit-appearance: none; appearance: none; line-height: 1.25rem;">
                            </div>
                            <div class="flex gap-2 items-end">
                            <button @click="openCandidateRegistrationPdfModal('single')" class="inline-flex h-[40px] items-center border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 rounded-lg">
                                <i class="fas fa-file-pdf mr-2"></i>Import Registration PDF
                            </button>
                            <button @click="openCandidateRegistrationPdfModal('bulk')" class="inline-flex h-[40px] items-center border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 rounded-lg">
                                <i class="fas fa-copy mr-2"></i>Bulk Import Registration PDFs
                            </button>
                            <button @click="openCandidateModal()" class="inline-flex h-[40px] items-center border border-emerald-200 bg-emerald-50 px-4 text-sm font-medium text-emerald-700 transition-colors hover:bg-emerald-100 rounded-lg">
                                <i class="fas fa-plus mr-2"></i>Add Candidate
                            </button>
                            <button x-show="selectedCandidateIds.length > 0" @click="bulkDeleteCandidates()" class="inline-flex h-[40px] items-center border border-rose-200 bg-rose-50 px-4 text-sm font-medium text-rose-700 transition-colors hover:bg-rose-100 rounded-lg">
                                <i class="fas fa-trash mr-2"></i>Delete Selected
                            </button>
                            <button @click="refreshCandidates()" class="inline-flex h-[40px] items-center bg-blue-600 hover:bg-blue-700 text-white px-4 rounded-lg transition-colors text-sm font-medium">
                                <i class="fas fa-rotate mr-2"></i>Refresh
                            </button>
                            </div>
                        </div>
                    </div>
                    <p class="mt-4 text-sm text-slate-600">Candidate school assignment is resolved from the linked school record, and the first 5 characters of the index number are used as the fallback centre code when matching or editing records.</p>
                </div>

                <div class="registration-surface registration-table-card overflow-x-auto">
                    <div x-show="loadingCandidates" class="p-8 text-center text-slate-500">
                        <i class="fas fa-spinner animate-spin text-2xl"></i>
                    </div>
                    <div x-show="!loadingCandidates" class="overflow-x-auto">
                        <table class="w-full min-w-[1360px]">
                            <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300">
                                <tr>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap">
                                        <input
                                            type="checkbox"
                                            :checked="allVisibleCandidatesSelected"
                                            @change="toggleAllVisibleCandidates($event.target.checked)"
                                            class="h-4 w-4 rounded-none border border-gray-400 text-blue-600 focus:ring-0"
                                            title="Select all visible candidates"
                                        >
                                    </th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap">Index Number</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap">Candidate</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap">Sex</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap">School</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap">Subjects</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="candidate in candidates" :key="candidate.id">
                                    <tr class="hover:bg-blue-50 transition-colors">
                                        <td class="px-3 py-1.5 text-center">
                                            <input
                                                type="checkbox"
                                                :checked="selectedCandidateIds.includes(String(candidate.id))"
                                                @change="toggleCandidateSelection(candidate.id, $event.target.checked)"
                                                class="h-4 w-4 rounded-none border border-gray-400 text-blue-600 focus:ring-0"
                                            >
                                        </td>
                                        <td class="px-3 py-1.5 text-[13px] font-mono font-semibold text-blue-700 whitespace-nowrap" x-text="candidate.candidate_id || '-'"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-800 font-medium whitespace-nowrap" x-text="candidate.full_name"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600 whitespace-nowrap" x-text="candidate.gender || '-'"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600 whitespace-nowrap" x-text="resolveCandidateSchoolName(candidate) || '-'"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600" x-text="candidate.allocated_subjects?.length ? candidate.allocated_subjects.map(subject => subject.code).join(', ') : '-'"></td>
                                        <td class="px-3 py-1.5 text-sm">
                                            <div class="flex items-center justify-center gap-1">
                                                <button type="button" @click="viewCandidate(candidate)" class="inline-flex h-7 w-7 items-center justify-center rounded-none text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" title="View Candidate">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" @click="openCandidateSubjectModal(candidate)" class="inline-flex h-7 w-7 items-center justify-center rounded-none text-emerald-600 transition hover:bg-emerald-50 hover:text-emerald-800" title="Assign Subjects">
                                                    <i class="fas fa-book-open"></i>
                                                </button>
                                                <button type="button" @click="editCandidate(candidate)" class="inline-flex h-7 w-7 items-center justify-center rounded-none text-blue-600 transition hover:bg-blue-50 hover:text-blue-800" title="Edit Candidate">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <button type="button" @click="deleteCandidate(candidate.id)" class="inline-flex h-7 w-7 items-center justify-center rounded-none text-rose-600 transition hover:bg-rose-50 hover:text-rose-800" title="Delete Candidate">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="!loadingCandidates && candidates.length === 0">
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500">No local CSEE candidates are available yet for the current registration year and search filter.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-200 bg-gradient-to-r from-slate-50 via-white to-slate-50 px-6 py-5" x-show="candidateMeta.total > 0">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex flex-wrap items-center gap-3 text-sm text-slate-600">
                                <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 font-semibold text-blue-700">
                                    <i class="fas fa-layer-group text-xs"></i>
                                    <span>Page <span x-text="candidateMeta.current_page || 1"></span> of <span x-text="candidateTotalPages"></span></span>
                                </div>
                                <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5">
                                    <i class="fas fa-user-graduate text-xs text-slate-400"></i>
                                    <span>Showing <span class="font-semibold text-slate-800" x-text="candidates.length"></span> of <span class="font-semibold text-slate-800" x-text="candidateMeta.total || 0"></span> candidates</span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                                <button @click="goToFirstCandidatesPage()" :disabled="(candidateMeta.current_page || 1) <= 1" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40" title="First page">
                                    <i class="fas fa-angles-left text-xs"></i>
                                </button>
                                <button @click="goToPreviousCandidatesPage()" :disabled="(candidateMeta.current_page || 1) <= 1" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40" title="Previous page">
                                    <i class="fas fa-chevron-left text-xs"></i>
                                    <span class="hidden sm:inline">Previous</span>
                                </button>
                                <div class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-2 py-2 shadow-sm">
                                    <template x-for="page in visibleCandidatePages" :key="'candidate-page-' + page">
                                        <button @click="goToCandidatesPage(page)" :class="(candidateMeta.current_page || 1) === page ? 'bg-blue-600 text-white shadow-md shadow-blue-200/80' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'" class="min-w-[2.5rem] rounded-xl px-3 py-2 text-sm font-semibold transition-colors" x-text="page"></button>
                                    </template>
                                </div>
                                <button @click="goToNextCandidatesPage()" :disabled="(candidateMeta.current_page || 1) >= candidateTotalPages" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40" title="Next page">
                                    <span class="hidden sm:inline">Next</span>
                                    <i class="fas fa-chevron-right text-xs"></i>
                                </button>
                                <button @click="goToLastCandidatesPage()" :disabled="(candidateMeta.current_page || 1) >= candidateTotalPages" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40" title="Last page">
                                    <i class="fas fa-angles-right text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div x-show="candidateViewModalOpen" x-cloak class="fixed inset-0 z-[9999] flex items-start justify-center bg-slate-950/55 px-4 pt-8 pb-6" @click.self="closeCandidateViewModal()">
                <div class="w-full max-w-3xl border border-slate-200 bg-white shadow-2xl">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">CSEE Candidate</p>
                            <h3 class="mt-2 text-xl font-black text-slate-900" x-text="viewingCandidate.full_name || 'Candidate Details'"></h3>
                            <p class="mt-1 text-sm font-mono text-slate-500" x-text="viewingCandidate.candidate_id || '-'"></p>
                        </div>
                        <button type="button" @click="closeCandidateViewModal()" class="inline-flex h-10 w-10 items-center justify-center border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 rounded-none">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="grid gap-4 px-6 py-6 md:grid-cols-2">
                        <div class="border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Index Number</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900" x-text="viewingCandidate.candidate_id || '-'"></p>
                        </div>
                        <div class="border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Sex</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900" x-text="viewingCandidate.gender || '-'"></p>
                        </div>
                        <div class="border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">School Code</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900" x-text="resolveCandidateSchoolCode(viewingCandidate) || '-'"></p>
                        </div>
                        <div class="border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Centre</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900" x-text="resolveCandidateSchoolName(viewingCandidate) || '-'"></p>
                        </div>
                        <div class="border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">District</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900" x-text="resolveCandidateDistrict(viewingCandidate) || '-'"></p>
                        </div>
                        <div class="border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Region</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900" x-text="resolveCandidateRegion(viewingCandidate) || '-'"></p>
                        </div>
                        <div class="border border-slate-200 bg-slate-50 px-4 py-3 md:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Allocated Subjects</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900" x-text="viewingCandidate.allocated_subjects?.length ? viewingCandidate.allocated_subjects.map(subject => subject.code).join(', ') : '-'"></p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button type="button" @click="closeCandidateViewModal()" class="border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 rounded-none">Close</button>
                        <button type="button" @click="closeCandidateViewModal(); editCandidate(viewingCandidate)" class="bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700 rounded-none">Edit Candidate</button>
                    </div>
                </div>
            </div>

            <div x-show="candidateModalOpen" x-cloak class="fixed inset-0 z-[9999] flex items-start justify-center bg-slate-950/55 px-4 pt-8 pb-6" @click.self="closeCandidateModal()">
                <div class="w-full max-w-3xl border border-slate-200 bg-white shadow-2xl">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">CSEE Candidate</p>
                            <h3 class="mt-2 text-xl font-black text-slate-900" x-text="editingCandidateId ? 'Edit Candidate' : 'Register New Candidate'"></h3>
                            <p class="mt-1 text-sm text-slate-500">The first 5 characters of the index number are used as the school code when auto-linking the centre.</p>
                        </div>
                        <button type="button" @click="closeCandidateModal()" class="inline-flex h-10 w-10 items-center justify-center border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 rounded-none">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <form @submit.prevent="saveCandidate()" class="space-y-6 px-6 py-6">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Index Number</label>
                                <input x-model="candidateForm.candidate_id" @input="autoSelectCandidateSchool()" type="text" placeholder="e.g. S51910001" class="w-full border border-slate-200 px-4 py-3 text-sm focus:border-blue-400 focus:outline-none focus:ring-0 rounded-none">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Sex</label>
                                <select x-model="candidateForm.gender" required class="w-full border border-slate-200 px-4 py-3 text-sm focus:border-blue-400 focus:outline-none focus:ring-0 rounded-none">
                                    <option value="">Select sex</option>
                                    <option value="M">Male</option>
                                    <option value="F">Female</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Candidate Name</label>
                            <input x-model="candidateForm.full_name" type="text" required class="w-full border border-slate-200 px-4 py-3 text-sm focus:border-blue-400 focus:outline-none focus:ring-0 rounded-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Centre</label>
                            <select x-model="candidateForm.school_id" required class="w-full border border-slate-200 px-4 py-3 text-sm focus:border-blue-400 focus:outline-none focus:ring-0 rounded-none">
                                <option value="">Select centre</option>
                                <template x-for="school in schoolDirectory" :key="'candidate-form-school-' + school.id">
                                    <option :value="String(school.id)" x-text="`${school.code} - ${school.name}`"></option>
                                </template>
                            </select>
                        </div>
                        <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
                            <button type="button" @click="closeCandidateModal()" class="border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 rounded-none">Cancel</button>
                            <button type="submit" :disabled="savingCandidate" class="bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50 rounded-none">
                                <span x-text="savingCandidate ? 'Saving...' : (editingCandidateId ? 'Update Candidate' : 'Register Candidate')"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div x-show="candidateSubjectModalOpen" x-cloak class="fixed inset-0 z-[9999] flex items-start justify-center bg-slate-950/55 px-4 pt-8 pb-6" @click.self="closeCandidateSubjectModal()">
                <div class="w-full max-w-5xl border border-slate-200 bg-white shadow-2xl">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">CSEE Subject Assignment</p>
                            <h3 class="mt-2 text-xl font-black text-slate-900" x-text="subjectEditingCandidate?.full_name || 'Assign Subjects'"></h3>
                            <p class="mt-1 text-sm font-mono text-slate-500" x-text="subjectEditingCandidate?.candidate_id || '-'"></p>
                        </div>
                        <button type="button" @click="closeCandidateSubjectModal()" class="inline-flex h-10 w-10 items-center justify-center border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 rounded-none">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="space-y-5 px-6 py-6 max-h-[calc(100vh-8rem)] overflow-y-auto">
                        <div class="grid gap-3 md:grid-cols-3">
                            <div class="rounded-none border border-emerald-200 bg-emerald-50 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Core Subjects</p>
                                <p class="mt-2 text-2xl font-black text-emerald-800" x-text="cseeCoreSubjectIds.length"></p>
                            </div>
                            <div class="rounded-none border border-blue-200 bg-blue-50 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Selected Total</p>
                                <p class="mt-2 text-2xl font-black text-blue-800" x-text="selectedCandidateSubjectCount"></p>
                            </div>
                            <div class="rounded-none border border-amber-200 bg-amber-50 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">NECTA Limit</p>
                                <p class="mt-2 text-2xl font-black text-amber-800">10</p>
                            </div>
                        </div>

                        <div class="rounded-none border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            The 7 NECTA core subjects are locked in automatically. You can add optional subjects up to a maximum of 10 total subjects for this candidate.
                        </div>

                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            <template x-for="subject in availableCseeSubjectOptions" :key="'csee-subject-option-' + subject.id">
                                <label class="flex items-start gap-3 border border-slate-200 bg-white px-4 py-3 transition hover:border-blue-200 hover:bg-blue-50 rounded-none">
                                    <input
                                        type="checkbox"
                                        :checked="isCandidateSubjectSelected(subject.id)"
                                        :disabled="isCoreCseeSubject(subject)"
                                        @change="toggleCandidateSubjectSelection(subject.id, $event.target.checked)"
                                        class="mt-1 h-4 w-4 rounded-none border border-slate-400 text-blue-600 focus:ring-0 disabled:opacity-70"
                                    >
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold text-slate-800" x-text="`${subject.code} - ${subject.name}`"></span>
                                        <span class="mt-1 inline-flex items-center gap-2 text-xs text-slate-500">
                                            <span x-text="subject.subject_group_label || subject.category || 'CSEE Subject'"></span>
                                            <span x-show="isCoreCseeSubject(subject)" class="rounded-full bg-emerald-100 px-2 py-0.5 font-semibold text-emerald-700">Core</span>
                                        </span>
                                    </span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button type="button" @click="closeCandidateSubjectModal()" class="border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 rounded-none">
                            Cancel
                        </button>
                        <button type="button" @click="saveCandidateSubjects()" :disabled="savingCandidateSubjects" class="bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50 rounded-none">
                            <span x-text="savingCandidateSubjects ? 'Saving...' : 'Save Subjects'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <div
                x-show="candidateImportModalOpen"
                x-cloak
                class="fixed inset-0 z-[9999] flex items-start justify-center bg-slate-950/55 px-4 pt-8 pb-6"
                @click.self="if (!candidateImportProcessing) { closeCandidateImportModal(); }"
            >
                <div class="w-full max-w-5xl border border-slate-200 bg-white shadow-2xl">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">CSEE Candidate Import</p>
                            <h3 class="mt-2 text-xl font-black text-slate-900">Import Candidates from CSV</h3>
                            <p class="mt-1 text-sm text-slate-500">The importer uses the first 5 characters of each CSEE index number as the school code.</p>
                        </div>
                        <button type="button" @click="if (!candidateImportProcessing) { closeCandidateImportModal(); }" class="inline-flex h-10 w-10 items-center justify-center border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 rounded-none">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="space-y-5 px-6 py-6 max-h-[calc(100vh-8rem)] overflow-y-auto">
                        <div x-show="candidateImportPhase === 'upload'" class="space-y-5">
                            <div class="rounded-none border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                                <p class="font-semibold">Expected columns</p>
                                <p class="mt-1">`candidate_id`, `full_name`, `gender`, optional `exam_type`, optional `exam_year`</p>
                            </div>

                            <div class="flex justify-end">
                                <button
                                    type="button"
                                    @click="downloadCandidateTemplate()"
                                    class="inline-flex items-center gap-2 border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 rounded-none"
                                    :disabled="candidateImportProcessing"
                                >
                                    <i class="fas fa-download"></i> Download CSEE Template
                                </button>
                            </div>

                            <div
                                @drop.prevent="handleCandidateImportDrop($event)"
                                @dragover.prevent="candidateImportDragActive = true"
                                @dragleave.prevent="candidateImportDragActive = false"
                                :class="candidateImportDragActive ? 'border-blue-500 bg-blue-50' : 'border-slate-200 bg-slate-50'"
                                class="rounded-none border-2 border-dashed px-6 py-10 text-center transition-colors"
                            >
                                <input
                                    type="file"
                                    id="csee-candidate-import-file-input"
                                    @change="handleCandidateImportFileSelect($event)"
                                    accept=".csv,.txt"
                                    class="hidden"
                                    :disabled="candidateImportProcessing"
                                >
                                <label for="csee-candidate-import-file-input" class="cursor-pointer block">
                                    <div class="mx-auto mb-4 inline-flex h-14 w-14 items-center justify-center rounded-none bg-white text-blue-700 shadow-sm">
                                        <i class="fas fa-cloud-arrow-up text-2xl"></i>
                                    </div>
                                    <p class="text-base font-semibold text-slate-700">Drop CSEE candidate CSV here or click to select</p>
                                    <p class="mt-2 text-sm text-slate-500">The importer derives the school code from the first 5 characters of `candidate_id`.</p>
                                </label>
                            </div>

                            <div x-show="candidateImportFile" class="rounded-none border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-slate-700">
                                <strong>Selected file:</strong>
                                <span x-text="candidateImportFile ? candidateImportFile.name : ''"></span>
                                <span class="text-slate-500" x-text="candidateImportFile ? '(' + (candidateImportFile.size / 1024).toFixed(1) + ' KB)' : ''"></span>
                            </div>

                            <div class="rounded-none border border-slate-200 bg-white px-4 py-4">
                                <p class="mb-3 text-sm font-semibold text-slate-700">If candidate already exists</p>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="radio" x-model="candidateImportExistsMode" value="skip" class="h-4 w-4 cursor-pointer">
                                        <span class="text-sm text-slate-700">
                                            <strong>Skip existing</strong>
                                            <span class="block text-xs text-slate-500">Leave the existing candidate record unchanged.</span>
                                        </span>
                                    </label>
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="radio" x-model="candidateImportExistsMode" value="replace" class="h-4 w-4 cursor-pointer">
                                        <span class="text-sm text-slate-700">
                                            <strong>Replace existing</strong>
                                            <span class="block text-xs text-slate-500">Update the saved candidate name, sex, and linked centre.</span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div x-show="candidateImportPhase === 'report'" class="space-y-4">
                            <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
                                <div class="rounded-none border border-slate-200 bg-slate-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total Rows</p>
                                    <p class="mt-2 text-2xl font-black text-slate-900" x-text="candidateImportReport.total_rows || 0"></p>
                                </div>
                                <div class="rounded-none border border-blue-200 bg-blue-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">New</p>
                                    <p class="mt-2 text-2xl font-black text-blue-800" x-text="candidateImportReport.create_count || 0"></p>
                                </div>
                                <div class="rounded-none border border-purple-200 bg-purple-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-purple-700">Update</p>
                                    <p class="mt-2 text-2xl font-black text-purple-800" x-text="candidateImportReport.update_count || 0"></p>
                                </div>
                                <div class="rounded-none border border-amber-200 bg-amber-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Skip</p>
                                    <p class="mt-2 text-2xl font-black text-amber-800" x-text="candidateImportReport.skip_count || 0"></p>
                                </div>
                                <div class="rounded-none border border-rose-200 bg-rose-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Errors</p>
                                    <p class="mt-2 text-2xl font-black text-rose-800" x-text="candidateImportReport.error_count || 0"></p>
                                </div>
                                <div class="rounded-none border border-emerald-200 bg-emerald-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Can Import</p>
                                    <p class="mt-2 text-2xl font-black text-emerald-800" x-text="candidateImportReport.can_import ? 'Yes' : 'No'"></p>
                                </div>
                            </div>

                            <div x-show="candidateImportReport.error_count > 0" class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-semibold text-slate-700">Errors Found</h4>
                                    <button
                                        @click="downloadCandidateImportErrors()"
                                        class="inline-flex items-center gap-2 border border-rose-200 bg-rose-50 px-3 py-1.5 text-sm font-medium text-rose-700 transition hover:bg-rose-100 rounded-none"
                                        :disabled="candidateImportReport.error_count === 0"
                                    >
                                        <i class="fas fa-download text-xs"></i> Download Errors
                                    </button>
                                </div>
                                <div class="overflow-hidden rounded-none border border-slate-200">
                                    <div class="max-h-64 overflow-auto">
                                        <table class="w-full text-sm">
                                            <thead class="bg-slate-100 border-b border-slate-200">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Row</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Index Number</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Candidate</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Error</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200">
                                                <template x-for="(error, idx) in candidateImportReport.errors.slice(0, 10)" :key="'csee-import-error-' + idx">
                                                    <tr>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="error.row_number"></td>
                                                        <td class="px-3 py-2 text-xs font-mono text-slate-600" x-text="error.candidate_id || '-'"></td>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="error.full_name || '-'"></td>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="error.primary_error || (error.error_messages && error.error_messages.length ? error.error_messages[0] : '-')"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div x-show="candidateImportReport.rows && candidateImportReport.rows.length > 0" class="space-y-2">
                                <h4 class="text-sm font-semibold text-slate-700">Import Plan</h4>
                                <div class="overflow-hidden rounded-none border border-slate-200">
                                    <div class="max-h-72 overflow-auto">
                                        <table class="w-full text-sm">
                                            <thead class="bg-slate-100 border-b border-slate-200">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Row</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Index Number</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Candidate</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Status</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Message</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200">
                                                <template x-for="(row, idx) in candidateImportReport.rows.slice(0, 20)" :key="'csee-import-row-' + idx">
                                                    <tr>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="row.row_number"></td>
                                                        <td class="px-3 py-2 text-xs font-mono text-slate-600" x-text="row.candidate_id || '-'"></td>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="row.full_name || '-'"></td>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="row.status || '-'"></td>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="row.messages && row.messages.length ? row.messages[0] : '-'"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-show="candidateImportPhase === 'processing'" class="flex flex-col items-center justify-center py-12">
                            <div class="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-none bg-blue-100 text-blue-700">
                                <i class="fas fa-spinner animate-spin text-3xl"></i>
                            </div>
                            <p class="text-lg font-semibold text-slate-700">Processing CSEE import...</p>
                            <p class="mt-2 text-sm text-slate-500" x-text="candidateImportProcessingMessage"></p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button
                            type="button"
                            @click="closeCandidateImportModal()"
                            class="border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 rounded-none"
                            :disabled="candidateImportProcessing"
                        >Close</button>
                        <button
                            type="button"
                            x-show="candidateImportPhase === 'upload'"
                            @click="validateCandidateImportFile()"
                            class="bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700 rounded-none"
                            :disabled="!candidateImportFile || candidateImportProcessing"
                        >Validate File</button>
                        <button
                            type="button"
                            x-show="candidateImportPhase === 'report'"
                            @click="candidateImportPhase = 'upload'"
                            class="border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 rounded-none"
                            :disabled="candidateImportProcessing"
                        >Back</button>
                        <button
                            type="button"
                            x-show="candidateImportPhase === 'report'"
                            @click="commitCandidateImportFile()"
                            class="bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700 rounded-none"
                            :disabled="!candidateImportReport.can_import || candidateImportProcessing"
                        >Import Candidates</button>
                    </div>
                </div>
            </div>

            <div
                x-show="candidateRegistrationPdfModalOpen"
                x-cloak
                class="fixed inset-0 z-[9999] flex items-start justify-center overflow-y-auto bg-slate-950/55 px-4 pt-8 pb-6"
                @click.self="if (!candidateRegistrationPdfProcessing) { closeCandidateRegistrationPdfModal(); }"
            >
                <div class="flex max-h-[calc(100vh-3.5rem)] w-full max-w-5xl flex-col border border-slate-200 bg-white shadow-2xl">
                    <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">CSEE Registration PDF Import</p>
                            <h3 class="mt-2 text-xl font-black text-slate-900" x-text="candidateRegistrationPdfMode === 'bulk' ? 'Bulk Import Subject Registration PDFs' : 'Import Subject Registration from PDF'"></h3>
                            <p class="mt-1 text-sm text-slate-500" x-text="candidateRegistrationPdfMode === 'bulk' ? 'Upload multiple NECTA registration printout PDFs. Each school file will be validated separately and then imported in one batch.' : 'Upload one NECTA registration printout PDF. Candidate rows and ticked subjects will be synchronized into the local CSEE registration records.'"></p>
                        </div>
                        <button type="button" @click="if (!candidateRegistrationPdfProcessing) { closeCandidateRegistrationPdfModal(); }" class="inline-flex h-10 w-10 items-center justify-center border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 rounded-none">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-6">
                        <div x-show="candidateRegistrationPdfPhase === 'upload'" class="space-y-5">
                            <div class="rounded-none border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                                <p class="font-semibold">Expected PDF format</p>
                                <p class="mt-1" x-text="candidateRegistrationPdfMode === 'bulk' ? 'NECTA registration printout PDFs showing candidate index numbers, sex, full names, and subject columns with tick marks. You can select many school PDFs at once.' : 'NECTA registration printout PDF showing candidate index numbers, sex, full names, and subject columns with tick marks.'"></p>
                            </div>

                            <div
                                @drop.prevent="handleCandidateRegistrationPdfDrop($event)"
                                @dragover.prevent="candidateRegistrationPdfDragActive = true"
                                @dragleave.prevent="candidateRegistrationPdfDragActive = false"
                                :class="candidateRegistrationPdfDragActive ? 'border-blue-500 bg-blue-50' : 'border-slate-200 bg-slate-50'"
                                class="rounded-none border-2 border-dashed px-6 py-10 text-center transition-colors"
                            >
                                <input
                                    type="file"
                                    id="csee-registration-pdf-input"
                                    @change="handleCandidateRegistrationPdfFileSelect($event)"
                                    accept=".pdf,application/pdf"
                                    :multiple="candidateRegistrationPdfMode === 'bulk'"
                                    class="hidden"
                                    :disabled="candidateRegistrationPdfProcessing"
                                >
                                <label for="csee-registration-pdf-input" class="cursor-pointer block">
                                    <div class="mx-auto mb-4 inline-flex h-14 w-14 items-center justify-center rounded-none bg-white text-rose-700 shadow-sm">
                                        <i class="fas fa-file-pdf text-2xl"></i>
                                    </div>
                                    <p class="text-base font-semibold text-slate-700" x-text="candidateRegistrationPdfMode === 'bulk' ? 'Drop registration PDFs here or click to select' : 'Drop registration PDF here or click to select'"></p>
                                    <p class="mt-2 text-sm text-slate-500" x-text="candidateRegistrationPdfMode === 'bulk' ? 'The parser reads the school code, exam year, candidate rows, and ticked subject columns from each PDF text.' : 'The parser reads the school code, exam year, candidate rows, and ticked subject columns from the PDF text.'"></p>
                                </label>
                            </div>

                            <div x-show="candidateRegistrationPdfFiles.length" class="rounded-none border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-slate-700">
                                <strong x-text="candidateRegistrationPdfMode === 'bulk' ? 'Selected PDFs:' : 'Selected PDF:'"></strong>
                                <span x-text="candidateRegistrationPdfMode === 'bulk' ? (candidateRegistrationPdfFiles.length + ' file(s)') : (candidateRegistrationPdfFiles[0]?.name || '')"></span>
                                <div class="mt-2 max-h-32 space-y-1 overflow-y-auto text-xs text-slate-600">
                                    <template x-for="(file, idx) in candidateRegistrationPdfFiles.slice(0, 12)" :key="'pdf-file-' + idx">
                                        <div x-text="`${file.name} (${(file.size / 1024).toFixed(1)} KB)`"></div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div x-show="candidateRegistrationPdfPhase === 'report'" class="space-y-4">
                            <div x-show="candidateRegistrationPdfReport.import_completed" class="rounded-none border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                                <p class="font-semibold">Import completed</p>
                                <p class="mt-1" x-text="candidateRegistrationPdfReport.message || 'Registration import finished successfully.'"></p>
                            </div>

                            <div x-show="candidateRegistrationPdfReport.message && !candidateRegistrationPdfReport.success" class="rounded-none border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                                <p class="font-semibold">Registration PDF could not be parsed</p>
                                <p class="mt-1" x-text="candidateRegistrationPdfReport.message"></p>
                            </div>

                            <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
                                <div class="rounded-none border border-slate-200 bg-slate-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total Rows</p>
                                    <p class="mt-2 text-2xl font-black text-slate-900" x-text="candidateRegistrationPdfReport.total_rows || 0"></p>
                                </div>
                                <div class="rounded-none border border-slate-200 bg-slate-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">PDF Files</p>
                                    <p class="mt-2 text-2xl font-black text-slate-900" x-text="candidateRegistrationPdfReport.total_files || 0"></p>
                                </div>
                                <div class="rounded-none border border-blue-200 bg-blue-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">New</p>
                                    <p class="mt-2 text-2xl font-black text-blue-800" x-text="candidateRegistrationPdfReport.create_count || 0"></p>
                                </div>
                                <div class="rounded-none border border-purple-200 bg-purple-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-purple-700">Updated</p>
                                    <p class="mt-2 text-2xl font-black text-purple-800" x-text="candidateRegistrationPdfReport.update_count || 0"></p>
                                </div>
                                <div class="rounded-none border border-amber-200 bg-amber-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Skipped</p>
                                    <p class="mt-2 text-2xl font-black text-amber-800" x-text="candidateRegistrationPdfReport.skip_count || 0"></p>
                                </div>
                                <div class="rounded-none border border-rose-200 bg-rose-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Errors</p>
                                    <p class="mt-2 text-2xl font-black text-rose-800" x-text="candidateRegistrationPdfReport.error_count || 0"></p>
                                </div>
                                <div class="rounded-none border border-emerald-200 bg-emerald-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Can Import</p>
                                    <p class="mt-2 text-2xl font-black text-emerald-800" x-text="candidateRegistrationPdfReport.can_import ? 'Yes' : 'No'"></p>
                                </div>
                            </div>

                            <div class="rounded-none border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                <p><strong>Importable Schools:</strong> <span x-text="candidateRegistrationPdfReport.importable_school_count || 0"></span></p>
                                <p class="mt-1"><strong>Failed Schools:</strong> <span x-text="candidateRegistrationPdfReport.failed_school_count || 0"></span></p>
                                <p class="mt-1"><strong>Exam Year:</strong> <span x-text="candidateRegistrationPdfReport.summary?.exam_year || '-'"></span></p>
                            </div>

                            <div x-show="candidateRegistrationPdfReport.schools && candidateRegistrationPdfReport.schools.length > 0" class="space-y-2">
                                <h4 class="text-sm font-semibold text-slate-700">School Summary</h4>
                                <div class="overflow-hidden rounded-none border border-slate-200">
                                    <div class="max-h-72 overflow-auto">
                                        <table class="w-full text-sm">
                                            <thead class="bg-slate-100 border-b border-slate-200">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700"><span class="inline-flex items-center gap-2"><i class="fas fa-file-pdf text-xs text-slate-500"></i><span>File</span></span></th>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700"><span class="inline-flex items-center gap-2"><i class="fas fa-school text-xs text-slate-500"></i><span>School</span></span></th>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700"><span class="inline-flex items-center gap-2"><i class="fas fa-list-ol text-xs text-slate-500"></i><span>Rows</span></span></th>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700"><span class="inline-flex items-center gap-2"><i class="fas fa-plus text-xs text-blue-600"></i><span>New</span></span></th>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700"><span class="inline-flex items-center gap-2"><i class="fas fa-pen text-xs text-purple-600"></i><span>Updated</span></span></th>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700"><span class="inline-flex items-center gap-2"><i class="fas fa-triangle-exclamation text-xs text-rose-600"></i><span>Errors</span></span></th>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700"><span class="inline-flex items-center gap-2"><i class="fas fa-circle-info text-xs text-emerald-600"></i><span>Status</span></span></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200">
                                                <template x-for="(school, idx) in candidateRegistrationPdfReport.schools" :key="'csee-registration-pdf-school-' + idx">
                                                    <tr>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="school.source_file_name || '-'"></td>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="school.school_code ? `${school.school_code} - ${school.school_name || ''}` : (school.school_name || '-')"></td>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="school.total_rows || 0"></td>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="school.create_count || 0"></td>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="school.update_count || 0"></td>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="school.error_count || 0"></td>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="candidateRegistrationPdfReport.import_completed ? (school.success ? 'Imported' : 'Failed') : (school.can_import ? 'Ready' : 'Needs attention')"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div x-show="candidateRegistrationPdfReport.error_count > 0" class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-semibold text-slate-700">Errors Found</h4>
                                    <button
                                        @click="downloadCandidateRegistrationPdfErrors()"
                                        class="inline-flex items-center gap-2 border border-rose-200 bg-rose-50 px-3 py-1.5 text-sm font-medium text-rose-700 transition hover:bg-rose-100 rounded-none"
                                        :disabled="candidateRegistrationPdfReport.error_count === 0"
                                    >
                                        <i class="fas fa-download text-xs"></i> Download Errors
                                    </button>
                                </div>
                                <div class="overflow-hidden rounded-none border border-slate-200">
                                    <div class="max-h-64 overflow-auto">
                                        <table class="w-full text-sm">
                                            <thead class="bg-slate-100 border-b border-slate-200">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700"><span class="inline-flex items-center gap-2"><i class="fas fa-hashtag text-xs text-slate-500"></i><span>Row</span></span></th>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700"><span class="inline-flex items-center gap-2"><i class="fas fa-file-pdf text-xs text-slate-500"></i><span>File</span></span></th>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700"><span class="inline-flex items-center gap-2"><i class="fas fa-id-card text-xs text-slate-500"></i><span>Index Number</span></span></th>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700"><span class="inline-flex items-center gap-2"><i class="fas fa-user text-xs text-slate-500"></i><span>Candidate</span></span></th>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700"><span class="inline-flex items-center gap-2"><i class="fas fa-circle-exclamation text-xs text-rose-600"></i><span>Error</span></span></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200">
                                                <template x-for="(error, idx) in candidateRegistrationPdfReport.errors.slice(0, 10)" :key="'csee-registration-pdf-error-' + idx">
                                                    <tr>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="error.row_number"></td>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="error.source_file_name || '-'"></td>
                                                        <td class="px-3 py-2 text-xs font-mono text-slate-600" x-text="error.candidate_id || '-'"></td>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="error.full_name || '-'"></td>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="error.primary_error || (error.error_messages && error.error_messages.length ? error.error_messages[0] : '-')"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div x-show="candidateRegistrationPdfReport.rows && candidateRegistrationPdfReport.rows.length > 0" class="space-y-2">
                                <h4 class="text-sm font-semibold text-slate-700">Candidate Preview</h4>
                                <div class="overflow-hidden rounded-none border border-slate-200">
                                    <div class="max-h-72 overflow-auto">
                                        <table class="w-full text-sm">
                                            <thead class="bg-slate-100 border-b border-slate-200">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700"><span class="inline-flex items-center gap-2"><i class="fas fa-hashtag text-xs text-slate-500"></i><span>Row</span></span></th>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700"><span class="inline-flex items-center gap-2"><i class="fas fa-id-card text-xs text-slate-500"></i><span>Index Number</span></span></th>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700"><span class="inline-flex items-center gap-2"><i class="fas fa-user text-xs text-slate-500"></i><span>Candidate</span></span></th>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700"><span class="inline-flex items-center gap-2"><i class="fas fa-book text-xs text-slate-500"></i><span>Subjects</span></span></th>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700"><span class="inline-flex items-center gap-2"><i class="fas fa-circle-info text-xs text-slate-500"></i><span>Status</span></span></th>
                                                    <th class="px-3 py-2 text-left font-semibold text-slate-700"><span class="inline-flex items-center gap-2"><i class="fas fa-comment-dots text-xs text-slate-500"></i><span>Message</span></span></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200">
                                                <template x-for="(row, idx) in candidateRegistrationPdfReport.rows.slice(0, 20)" :key="'csee-registration-pdf-row-' + idx">
                                                    <tr>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="row.row_number"></td>
                                                        <td class="px-3 py-2 text-xs font-mono text-slate-600" x-text="row.candidate_id || '-'"></td>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="row.full_name || '-'"></td>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="row.subject_codes && row.subject_codes.length ? row.subject_codes.join(', ') : '-'"></td>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="row.status || '-'"></td>
                                                        <td class="px-3 py-2 text-xs text-slate-600" x-text="row.messages && row.messages.length ? row.messages[0] : '-'"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-show="candidateRegistrationPdfPhase === 'processing'" class="flex flex-col items-center justify-center py-12">
                            <div class="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-none bg-rose-100 text-rose-700">
                                <i class="fas fa-spinner animate-spin text-3xl"></i>
                            </div>
                            <p class="text-lg font-semibold text-slate-700" x-text="candidateRegistrationPdfMode === 'bulk' ? 'Processing registration PDFs...' : 'Processing registration PDF...'"></p>
                            <p class="mt-2 text-sm text-slate-500" x-text="candidateRegistrationPdfProcessingMessage"></p>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button
                            type="button"
                            @click="closeCandidateRegistrationPdfModal()"
                            class="border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 rounded-none"
                            :disabled="candidateRegistrationPdfProcessing"
                        >Close</button>
                        <button
                            type="button"
                            x-show="candidateRegistrationPdfPhase === 'upload'"
                            @click="validateCandidateRegistrationPdfFile()"
                            class="bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700 rounded-none"
                            :disabled="!candidateRegistrationPdfFiles.length || candidateRegistrationPdfProcessing"
                        ><span x-text="candidateRegistrationPdfMode === 'bulk' ? 'Validate PDFs' : 'Validate PDF'"></span></button>
                        <button
                            type="button"
                            x-show="candidateRegistrationPdfPhase === 'report'"
                            @click="candidateRegistrationPdfPhase = 'upload'"
                            class="border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 rounded-none"
                            :disabled="candidateRegistrationPdfProcessing"
                            x-show="candidateRegistrationPdfPhase === 'report' && !candidateRegistrationPdfReport.import_completed"
                        >Back</button>
                        <button
                            type="button"
                            x-show="candidateRegistrationPdfPhase === 'report' && !candidateRegistrationPdfReport.import_completed"
                            @click="commitCandidateRegistrationPdfFile()"
                            class="bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700 rounded-none"
                            :disabled="!candidateRegistrationPdfReport.can_import || candidateRegistrationPdfProcessing"
                        ><span x-text="candidateRegistrationPdfMode === 'bulk' ? 'Import Registrations' : 'Import Registration'"></span></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function cseeManager() {
        return {
            activeTab: 'subjects',
            tabs: [
                { key: 'subjects', label: 'Subjects', icon: 'fas fa-book' },
                { key: 'papers', label: 'Paper Structure', icon: 'fas fa-layer-group' },
                { key: 'timetable', label: 'Timetable', icon: 'fas fa-calendar-alt' },
                { key: 'schools', label: 'Schools & Centres', icon: 'fas fa-school' },
                { key: 'candidates', label: 'Candidates', icon: 'fas fa-user-graduate' },
            ],
            activeTabClass: 'border-slate-900 bg-slate-900 text-white shadow-lg shadow-slate-900/15',
            inactiveTabClass: 'border-slate-200 bg-white text-slate-600 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700',
            activeIconClass: 'bg-white/15 text-white',
            inactiveIconClass: 'bg-slate-100 text-slate-500 group-hover:bg-blue-100 group-hover:text-blue-700',
            officialCatalog: @json($officialCatalog),
            cseeCoreSubjectCodes: @json(\App\Services\Candidates\CseeCandidateSubjectService::CORE_SUBJECT_CODES),
            subjects: [],
            mergedSubjects: [],
            filteredSubjects: [],
            subjectSearch: '',
            subjectStatusFilter: 'all',
            syncingSubjects: false,
            loadingSchools: false,
            syncingSchools: false,
            uploadingSchoolParticulars: false,
            autoEnrichingSchoolParticulars: false,
            savingSchoolEdit: false,
            schoolSearch: '',
            schoolFilterRegion: '',
            schoolFilterDistrict: '',
            schoolRegionOpen: false,
            schoolDistrictOpen: false,
            schoolRegionSearch: '',
            schoolDistrictSearch: '',
            selectedSchoolIds: [],
            schools: [],
            regions: [],
            districts: [],
            schoolsCurrentPage: 1,
            schoolsPageSize: 100,
            schoolsTotalCount: 0,
            schoolParticularsModalOpen: false,
            selectedSchoolParticulars: null,
            schoolEditModalOpen: false,
            schoolEditForm: {
                id: null,
                code: '',
                name: '',
                ownership: 'GOVERNMENT',
                region_id: '',
                district_id: '',
            },
            schoolEditOwnershipOpen: false,
            schoolEditOwnershipSearch: '',
            schoolEditRegionOpen: false,
            schoolEditRegionSearch: '',
            schoolEditDistrictOpen: false,
            schoolEditDistrictSearch: '',
            schoolImportResultsModalOpen: false,
            schoolImportSummary: null,
            schoolImportFailedRows: [],
            schoolImportSkippedRows: [],
            loadingCandidates: false,
            savingCandidate: false,
            candidateSearch: '',
            candidateFilterRegion: '',
            candidateFilterDistrict: '',
            candidateFilterSchool: '',
            candidateRegionOpen: false,
            candidateDistrictOpen: false,
            candidateSchoolOpen: false,
            candidateRegionSearch: '',
            candidateDistrictSearch: '',
            candidateSchoolSearch: '',
            candidatePageSize: 100,
            selectedCandidateIds: [],
            schoolDirectory: [],
            candidateModalOpen: false,
            candidateViewModalOpen: false,
            candidateImportModalOpen: false,
            candidateRegistrationPdfModalOpen: false,
            candidateSubjectModalOpen: false,
            editingCandidateId: null,
            viewingCandidate: {},
            subjectEditingCandidate: null,
            selectedCandidateSubjectIds: [],
            savingCandidateSubjects: false,
            candidateForm: {
                candidate_id: '',
                full_name: '',
                gender: '',
                school_id: '',
            },
            candidateImportFile: null,
            candidateImportPhase: 'upload',
            candidateImportProcessing: false,
            candidateImportProcessingMessage: '',
            candidateImportDragActive: false,
            candidateImportExistsMode: 'skip',
            candidateImportReport: {
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
                success: false,
            },
            candidateRegistrationPdfFiles: [],
            candidateRegistrationPdfMode: 'single',
            candidateRegistrationPdfPhase: 'upload',
            candidateRegistrationPdfProcessing: false,
            candidateRegistrationPdfProcessingMessage: '',
            candidateRegistrationPdfDragActive: false,
            candidateRegistrationPdfReport: {
                import_completed: false,
                total_files: 0,
                importable_school_count: 0,
                failed_school_count: 0,
                errors: [],
                warnings: [],
                total_rows: 0,
                create_count: 0,
                update_count: 0,
                skip_count: 0,
                error_count: 0,
                warning_count: 0,
                can_import: false,
                schools: [],
                rows: [],
                summary: {},
                message: '',
                success: true,
            },
            candidates: [],
            candidateMeta: {
                total: 0,
                current_page: 1,
                last_page: 1,
            },

            init() {
                const params = new URLSearchParams(window.location.search);
                const requestedTab = params.get('tab');
                if (requestedTab && this.tabs.some(tab => tab.key === requestedTab)) {
                    this.activeTab = requestedTab;
                }

                this.loadSubjects();
                this.loadRegions();
                this.loadDistricts();
                this.loadSchools();
                this.loadSchoolDirectory().then(() => this.loadCandidates());
            },

            get filteredSchoolEditDistricts() {
                if (!this.schoolEditForm.region_id) {
                    return this.districts;
                }

                return this.districts.filter(district => String(district.region_id) === String(this.schoolEditForm.region_id));
            },

            get filteredSchoolDistricts() {
                if (!this.schoolFilterRegion) {
                    return this.districts;
                }

                return this.districts.filter(district => String(district.region_id) === String(this.schoolFilterRegion));
            },

            get filteredCandidateDistricts() {
                if (!this.candidateFilterRegion) {
                    return this.districts;
                }

                return this.districts.filter(district => String(district.region_id) === String(this.candidateFilterRegion));
            },

            get filteredCandidateRegionOptions() {
                const query = this.candidateRegionSearch.toLowerCase();
                return this.regions.filter(region => region.name.toLowerCase().includes(query));
            },

            get filteredCandidateDistrictOptions() {
                const query = this.candidateDistrictSearch.toLowerCase();
                return this.filteredCandidateDistricts.filter(district => district.name.toLowerCase().includes(query));
            },

            get filteredCandidateSchools() {
                return this.schoolDirectory.filter(school => {
                    if (this.candidateFilterRegion) {
                        const schoolRegionId = school.region_id || this.resolveRegionId(school);
                        if (String(schoolRegionId || '') !== String(this.candidateFilterRegion)) {
                            return false;
                        }
                    }

                    if (this.candidateFilterDistrict) {
                        const schoolDistrictId = school.district_id || school.council_id;
                        if (String(schoolDistrictId || '') !== String(this.candidateFilterDistrict)) {
                            return false;
                        }
                    }

                    return true;
                });
            },

            get filteredCandidateSchoolOptions() {
                const query = this.candidateSchoolSearch.toLowerCase();

                return this.filteredCandidateSchools.filter(school => {
                    const label = `${school.code || ''} ${school.name || ''}`.toLowerCase();
                    return label.includes(query);
                });
            },

            get allVisibleSchoolsSelected() {
                if (!this.schools.length) {
                    return false;
                }

                return this.schools.every(school => this.selectedSchoolIds.includes(String(school.id)));
            },

            get schoolEditOwnershipOptions() {
                return ['GOVERNMENT', 'NON-GOVERNMENT'];
            },

            get filteredSchoolEditOwnershipOptions() {
                const query = this.schoolEditOwnershipSearch.toLowerCase();
                return this.schoolEditOwnershipOptions.filter(option => option.toLowerCase().includes(query));
            },

            get filteredSchoolEditRegionOptions() {
                const query = this.schoolEditRegionSearch.toLowerCase();
                return this.regions.filter(region => region.name.toLowerCase().includes(query));
            },

            get filteredSchoolEditDistrictOptions() {
                const query = this.schoolEditDistrictSearch.toLowerCase();
                return this.filteredSchoolEditDistricts.filter(district => district.name.toLowerCase().includes(query));
            },

            get allVisibleCandidatesSelected() {
                if (!this.candidates.length) {
                    return false;
                }

                return this.candidates.every(candidate => this.selectedCandidateIds.includes(String(candidate.id)));
            },

            get candidateTotalPages() {
                return Math.max(Number(this.candidateMeta.last_page || 1), 1);
            },

            get visibleCandidatePages() {
                return this.buildVisiblePages(Number(this.candidateMeta.current_page || 1), this.candidateTotalPages);
            },

            get cseeCoreSubjectIds() {
                return this.subjects
                    .filter(subject => this.cseeCoreSubjectCodes.includes(subject.code))
                    .map(subject => Number(subject.id));
            },

            get selectedCandidateSubjectCount() {
                const selected = Array.isArray(this.selectedCandidateSubjectIds) ? this.selectedCandidateSubjectIds : [];
                return Array.from(new Set([...selected.map(Number), ...this.cseeCoreSubjectIds])).length;
            },

            get availableCseeSubjectOptions() {
                return [...this.subjects].sort((left, right) => String(left.code).localeCompare(String(right.code)));
            },

            setActiveTab(tab) {
                this.activeTab = tab;
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                window.history.replaceState({}, '', url);
            },

            get schoolsTotalPages() {
                return Math.max(Math.ceil(this.schoolsTotalCount / this.schoolsPageSize), 1);
            },

            get visibleSchoolPages() {
                return this.buildVisiblePages(this.schoolsCurrentPage, this.schoolsTotalPages);
            },

            mergeSubjects() {
                const syncedMap = new Map(this.subjects.map(subject => [subject.code, subject]));

                this.mergedSubjects = this.officialCatalog.map(subject => {
                    const synced = syncedMap.get(subject.code);

                    return {
                        ...subject,
                        ...(synced || {}),
                        source_page: subject.source_page,
                        subject_group_label: subject.subject_group_label || synced?.subject_group_label,
                        paper_pattern_label: synced?.paper_pattern_label || 'Structured extraction pending',
                        stream_label: this.formatStreamLabel(synced?.category || subject.category),
                        synced: Boolean(synced),
                    };
                });

                this.filterSubjects();
            },

            filterSubjects() {
                const query = (this.subjectSearch || '').trim().toLowerCase();

                this.filteredSubjects = this.mergedSubjects.filter(subject => {
                    const matchesQuery = query === ''
                        || subject.code.toLowerCase().includes(query)
                        || subject.name.toLowerCase().includes(query)
                        || (subject.subject_group_label || '').toLowerCase().includes(query);

                    const matchesStatus = this.subjectStatusFilter === 'all'
                        || (this.subjectStatusFilter === 'synced' && subject.synced)
                        || (this.subjectStatusFilter === 'pending' && !subject.synced);

                    return matchesQuery && matchesStatus;
                });
            },

            formatStreamLabel(category) {
                if (category === 'ARTS') return 'Arts and Languages';
                if (category === 'SCIENCE') return 'Science';
                if (category === 'BUSINESS') return 'Applied and Technical';
                return category || '-';
            },

            officialPaperCount(subject) {
                const count = Number(subject?.written_papers || 1);
                return count === 2 ? '2 papers' : '1 paper';
            },

            officialPaperStructure(subject) {
                const code = subject?.code || '';

                const map = {
                    '010': '1 paper · 3 hours',
                    '011': '1 paper · 3 hours',
                    '012': '1 paper · 3 hours',
                    '013': '1 paper · 3 hours',
                    '014': '1 paper · 3 hours',
                    '015': '1 paper · 3 hours',
                    '016': '2 papers · Theory 3 hours + Practical 5 hours',
                    '017': '2 papers · Theory 3 hours + Practical 2 hours',
                    '018': '1 paper · 3 hours',
                    '019': '1 paper · 3 hours',
                    '021': '1 paper · 3 hours',
                    '022': '1 paper · 3 hours',
                    '023': '1 paper · 3 hours',
                    '024': '1 paper · 3 hours',
                    '025': '1 paper · 3 hours',
                    '026': '1 paper · 3 hours',
                    '031': '2 papers · Theory 3 hours + Practical 2.5 hours',
                    '032': '2 papers · Theory 3 hours + Practical 2.5 hours',
                    '033': '2 papers · Theory 3 hours + Practical 2.5 hours',
                    '034': '2 papers · Theory 3 hours + Practical 2.5 hours',
                    '035': '1 paper · 3 hours',
                    '036': '2 papers · Theory 3 hours + Practical paper',
                    '041': '1 paper · 3 hours',
                    '042': '1 paper · 3 hours',
                    '051': '2 papers · Theory 3 hours + Planning 1.5 hours + Practical 2.5 hours',
                    '052': '2 papers · Theory 3 hours + Practical 3 hours + Coursework',
                    '061': '1 paper · 3 hours',
                    '062': '1 paper · 3 hours',
                    '071': '1 paper · 3 hours',
                    '072': '1 paper · 3 hours',
                    '073': '1 paper · 3 hours',
                    '074': '1 paper · 3 hours',
                    '080': '1 paper · 3 hours',
                    '081': '1 paper · 3 hours',
                    '082': '1 paper · 3 hours',
                    '083': '1 paper · 3 hours',
                    '087': '1 paper · 3 hours',
                    '088': '1 paper · 3 hours',
                    '091': '1 paper · 3 hours',
                };

                return map[code] || 'Check official NECTA rubric';
            },

            officialPaperRubricNote(subject) {
                const code = subject?.code || '';

                const noteMap = {
                    '010': 'Mixed qualifying paper: Sections A and B, answer 16 of 24 questions.',
                    '011': 'Sections A-C; answer all in A and B, then 2 questions from section C.',
                    '012': 'Sections A-C; answer all in A and B, then 2 questions from section C.',
                    '013': 'Sections A-C; answer all in A and B, then 2 questions from section C.',
                    '014': '13 questions in sections A-C; candidates answer 9 questions for 100 marks.',
                    '015': 'Sections A-C; answer all in A and B, then 2 questions from section C.',
                    '016': 'Theory paper plus actual practical paper; practical covers drawing plus painting/designing.',
                    '017': 'Theory paper plus practical paper.',
                    '018': 'Sections A-C; answer all in A and B, then 2 questions from section C.',
                    '019': 'Sections A-C; answer all in A and B, then 2 questions from section C.',
                    '021': 'Sections A-C; answer all in A and B, then 2 questions from section C.',
                    '022': 'Sections A-C; one essay in section C is compulsory.',
                    '023': 'Sections A-C; answer all in A and B, then 2 questions from section C.',
                    '024': 'Sections A-C; answer all in A and B, then 2 questions from section C.',
                    '025': 'Sections A-C; answer all in A and B, then 2 questions from section C.',
                    '026': 'Sections A-C; answer all in A and B, then 2 questions from section C.',
                    '031': 'Theory and actual practical paper. NECTA practical checklist is sent before the exam.',
                    '032': 'Theory and practical paper. NECTA practical checklist is sent before the exam.',
                    '033': 'Theory and practical paper. NECTA practical alternatives may vary by centre.',
                    '034': 'Theory and practical paper.',
                    '035': 'Single theory paper in sections A-C.',
                    '036': 'Theory paper plus practical paper with 3 questions; candidates answer 2.',
                    '041': 'Sections A-B only; answer all 14 questions.',
                    '042': 'Sections A-B only; answer all 14 questions.',
                    '051': 'Practical paper is split into a planning session and a practical session.',
                    '052': 'Practical paper combines practical examination and coursework.',
                    '061': 'Sections A-C; answer all in A and B, then 2 questions from section C.',
                    '062': 'Sections A-C; all 9 questions are compulsory.',
                    '071': 'One theory paper; answer 10 of 11 questions.',
                    '072': 'One paper with 8 questions; all questions are compulsory.',
                    '073': 'Sections A-C; answer all in A and B, then 2 questions from section C.',
                    '074': 'Sections A-C; answer all in A and B, then 2 questions from section C.',
                    '080': 'Sections A-C; answer all in A and B, then 2 questions from section C.',
                    '081': 'Sections A-C; answer all in A and B, then 2 questions from section C.',
                    '082': 'One paper with 8 questions; all questions are compulsory.',
                    '083': 'One paper with 8 questions; all questions are compulsory.',
                    '087': 'Sections A-C; answer all in A and B, then 2 questions from section C.',
                    '088': 'Sections A-C; answer all in A and B, then 2 questions from section C.',
                    '091': 'One paper with 6 questions; answer 5 including a compulsory assembly drawing question.',
                };

                return noteMap[code] || 'Open the official booklet for the exact rubric wording.';
            },

            async loadSubjects() {
                try {
                    const response = await fetch('/admin/api/exam-types/CSEE/subjects');
                    const data = await response.json();
                    this.subjects = Array.isArray(data.data) ? data.data : [];
                    this.mergeSubjects();
                } catch (error) {
                    console.error('Error loading CSEE subjects:', error);
                    this.showMessage('Error loading CSEE subjects.', 'error');
                }
            },

            async loadRegions() {
                try {
                    const response = await fetch('/admin/api/regions?page_size=1000');
                    const data = await this.parseApiResponse(response);
                    this.regions = Array.isArray(data.data) ? data.data : [];
                } catch (error) {
                    console.error('Error loading regions:', error);
                }
            },

            async loadDistricts() {
                try {
                    const response = await fetch('/admin/api/districts?page_size=1000');
                    const data = await this.parseApiResponse(response);
                    this.districts = Array.isArray(data.data) ? data.data : [];
                } catch (error) {
                    console.error('Error loading districts:', error);
                }
            },

            async loadSchoolDirectory() {
                try {
                    const pageSize = 500;
                    let page = 1;
                    let lastPage = 1;
                    const allSchools = [];

                    do {
                        const params = new URLSearchParams({
                            page: String(page),
                            page_size: String(pageSize),
                        });
                        const response = await fetch(`/admin/api/exam-types/csee/schools?${params.toString()}`);
                        const data = await this.parseApiResponse(response);
                        allSchools.push(...(Array.isArray(data.data) ? data.data : []));
                        lastPage = Number(data.meta?.last_page || 1);
                        page += 1;
                    } while (page <= lastPage);

                    this.schoolDirectory = allSchools;
                } catch (error) {
                    console.error('Error loading CSEE school directory:', error);
                }
            },

            async syncOfficialSubjects() {
                this.syncingSubjects = true;

                try {
                    const response = await fetch('/admin/api/exam-types/csee/subjects/sync-official', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Unable to sync the official CSEE catalog.');
                    }

                    await this.loadSubjects();
                    this.showMessage(data.message || 'Official CSEE subject catalog synchronized successfully.', 'success');
                } catch (error) {
                    console.error('Error syncing CSEE subjects:', error);
                    this.showMessage(error.message || 'Unable to sync the official CSEE catalog.', 'error');
                } finally {
                    this.syncingSubjects = false;
                }
            },

            async loadCandidates() {
                this.loadingCandidates = true;

                try {
                    const params = new URLSearchParams({
                        page: String(this.candidateMeta.current_page || 1),
                        per_page: String(this.candidatePageSize),
                        q: this.candidateSearch || '',
                        region_id: this.candidateFilterRegion || '',
                        district_id: this.candidateFilterDistrict || '',
                        school_id: this.candidateFilterSchool || '',
                    });

                    const response = await fetch(`/admin/api/exam-types/CSEE/candidates?${params.toString()}`);
                    const data = await this.parseApiResponse(response);

                    this.candidates = (Array.isArray(data.data) ? data.data : []).map(candidate => {
                        const matchedSchool = this.findSchoolByCandidate(candidate);

                        return {
                            ...candidate,
                            school_id: candidate.school_id || matchedSchool?.id || '',
                            school_code: candidate.school_code || matchedSchool?.code || this.extractSchoolCodeFromCandidateId(candidate.candidate_id),
                            school_name: candidate.school_name && candidate.school_name !== '-' ? candidate.school_name : (matchedSchool?.name || '-'),
                            district_id: candidate.district_id || matchedSchool?.district_id || matchedSchool?.council_id || '',
                            district_name: candidate.district_name || matchedSchool?.district_name || matchedSchool?.council_name || '-',
                            region_id: candidate.region_id || matchedSchool?.region_id || this.resolveRegionId(matchedSchool) || '',
                            region_name: candidate.region_name || matchedSchool?.region_name || '-',
                        };
                    });
                    this.selectedCandidateIds = this.selectedCandidateIds.filter(id =>
                        this.candidates.some(candidate => String(candidate.id) === String(id))
                    );
                    this.candidateMeta = data.meta || { total: 0, current_page: 1, last_page: 1 };
                } catch (error) {
                    console.error('Error loading CSEE candidates:', error);
                    this.showMessage('Error loading CSEE candidates.', 'error');
                } finally {
                    this.loadingCandidates = false;
                }
            },

            refreshCandidates() {
                this.candidateMeta.current_page = 1;
                this.loadCandidates();
            },

            onCandidateRegionChange() {
                this.candidateFilterDistrict = '';
                this.candidateFilterSchool = '';
                this.candidateRegionSearch = '';
                this.candidateDistrictSearch = '';
                this.candidateSchoolSearch = '';
                this.refreshCandidates();
            },

            onCandidateDistrictChange() {
                this.candidateFilterSchool = '';
                this.candidateDistrictSearch = '';
                this.candidateSchoolSearch = '';
                this.refreshCandidates();
            },

            onCandidateSchoolChange() {
                this.candidateSchoolSearch = '';
                this.refreshCandidates();
            },

            goToCandidatesPage(page) {
                if (page >= 1 && page <= this.candidateTotalPages) {
                    this.candidateMeta.current_page = page;
                    this.loadCandidates();
                }
            },

            goToFirstCandidatesPage() {
                this.candidateMeta.current_page = 1;
                this.loadCandidates();
            },

            goToPreviousCandidatesPage() {
                if ((this.candidateMeta.current_page || 1) > 1) {
                    this.candidateMeta.current_page--;
                    this.loadCandidates();
                }
            },

            goToNextCandidatesPage() {
                if ((this.candidateMeta.current_page || 1) < this.candidateTotalPages) {
                    this.candidateMeta.current_page++;
                    this.loadCandidates();
                }
            },

            goToLastCandidatesPage() {
                this.candidateMeta.current_page = this.candidateTotalPages;
                this.loadCandidates();
            },

            toggleCandidateSelection(candidateId, checked) {
                const normalizedId = String(candidateId);

                if (checked) {
                    if (!this.selectedCandidateIds.includes(normalizedId)) {
                        this.selectedCandidateIds = [...this.selectedCandidateIds, normalizedId];
                    }
                    return;
                }

                this.selectedCandidateIds = this.selectedCandidateIds.filter(id => id !== normalizedId);
            },

            toggleAllVisibleCandidates(checked) {
                const visibleIds = this.candidates.map(candidate => String(candidate.id));

                if (checked) {
                    this.selectedCandidateIds = Array.from(new Set([
                        ...this.selectedCandidateIds,
                        ...visibleIds,
                    ]));
                    return;
                }

                this.selectedCandidateIds = this.selectedCandidateIds.filter(id => !visibleIds.includes(id));
            },

            extractSchoolCodeFromCandidateId(candidateId) {
                return String(candidateId || '').trim().toUpperCase().slice(0, 5);
            },

            findSchoolByCode(schoolCode) {
                if (!schoolCode) {
                    return null;
                }

                const normalizedCode = String(schoolCode).trim().toUpperCase();

                return this.schoolDirectory.find(school => String(school.code || '').trim().toUpperCase() === normalizedCode) || null;
            },

            findSchoolByCandidate(candidate) {
                if (!candidate) {
                    return null;
                }

                if (candidate.school_id) {
                    const byId = this.schoolDirectory.find(school => String(school.id) === String(candidate.school_id));
                    if (byId) {
                        return byId;
                    }
                }

                return this.findSchoolByCode(this.extractSchoolCodeFromCandidateId(candidate.candidate_id));
            },

            resolveCandidateSchoolCode(candidate) {
                return candidate?.school_code || this.findSchoolByCandidate(candidate)?.code || this.extractSchoolCodeFromCandidateId(candidate?.candidate_id);
            },

            resolveCandidateSchoolName(candidate) {
                return candidate?.school_name && candidate.school_name !== '-'
                    ? candidate.school_name
                    : (this.findSchoolByCandidate(candidate)?.name || '-');
            },

            resolveCandidateDistrict(candidate) {
                return candidate?.district_name && candidate.district_name !== '-'
                    ? candidate.district_name
                    : (this.findSchoolByCandidate(candidate)?.district_name || this.findSchoolByCandidate(candidate)?.council_name || '-');
            },

            resolveCandidateRegion(candidate) {
                return candidate?.region_name && candidate.region_name !== '-'
                    ? candidate.region_name
                    : (this.findSchoolByCandidate(candidate)?.region_name || '-');
            },

            openCandidateModal() {
                this.editingCandidateId = null;
                this.candidateForm = {
                    candidate_id: '',
                    full_name: '',
                    gender: '',
                    school_id: '',
                };
                this.candidateModalOpen = true;
            },

            closeCandidateModal() {
                this.candidateModalOpen = false;
                this.savingCandidate = false;
                this.editingCandidateId = null;
                this.candidateForm = {
                    candidate_id: '',
                    full_name: '',
                    gender: '',
                    school_id: '',
                };
            },

            openCandidateSubjectModal(candidate) {
                this.subjectEditingCandidate = candidate || null;
                this.selectedCandidateSubjectIds = Array.isArray(candidate?.allocated_subjects)
                    ? candidate.allocated_subjects.map(subject => Number(subject.id)).filter(Boolean)
                    : [];
                this.candidateSubjectModalOpen = true;
            },

            closeCandidateSubjectModal() {
                if (this.savingCandidateSubjects) {
                    return;
                }

                this.candidateSubjectModalOpen = false;
                this.subjectEditingCandidate = null;
                this.selectedCandidateSubjectIds = [];
                this.savingCandidateSubjects = false;
            },

            isCoreCseeSubject(subject) {
                return this.cseeCoreSubjectCodes.includes(String(subject?.code || ''));
            },

            isCandidateSubjectSelected(subjectId) {
                const normalizedId = Number(subjectId);
                return this.selectedCandidateSubjectIds.includes(normalizedId) || this.cseeCoreSubjectIds.includes(normalizedId);
            },

            toggleCandidateSubjectSelection(subjectId, checked) {
                const normalizedId = Number(subjectId);
                if (this.cseeCoreSubjectIds.includes(normalizedId)) {
                    return;
                }

                if (checked) {
                    const nextIds = Array.from(new Set([...this.selectedCandidateSubjectIds, normalizedId]));
                    const mergedCount = Array.from(new Set([...nextIds, ...this.cseeCoreSubjectIds])).length;
                    if (mergedCount > 10) {
                        this.showMessage('NECTA allows a maximum of 10 CSEE subjects per candidate.', 'error');
                        return;
                    }
                    this.selectedCandidateSubjectIds = nextIds;
                    return;
                }

                this.selectedCandidateSubjectIds = this.selectedCandidateSubjectIds.filter(id => Number(id) !== normalizedId);
            },

            async saveCandidateSubjects() {
                if (!this.subjectEditingCandidate?.id) {
                    return;
                }

                this.savingCandidateSubjects = true;

                try {
                    const payload = {
                        subject_ids: Array.from(new Set([
                            ...this.selectedCandidateSubjectIds.map(Number),
                            ...this.cseeCoreSubjectIds,
                        ])),
                    };

                    const response = await fetch(`/admin/api/exam-types/csee/candidates/${this.subjectEditingCandidate.id}/subjects`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await this.parseApiResponse(response);

                    if (!response.ok) {
                        throw new Error(data.message || 'Unable to update candidate subjects.');
                    }

                    await this.loadCandidates();
                    this.closeCandidateSubjectModal();
                    this.showMessage(data.message || 'Candidate subjects updated successfully.', 'success');
                } catch (error) {
                    console.error('Error updating CSEE candidate subjects:', error);
                    this.showMessage(error.message || 'Unable to update candidate subjects.', 'error');
                } finally {
                    this.savingCandidateSubjects = false;
                }
            },

            openCandidateImportModal() {
                this.resetCandidateImportModal();
                this.candidateImportModalOpen = true;
            },

            openCandidateRegistrationPdfModal(mode = 'single') {
                this.resetCandidateRegistrationPdfModal();
                this.candidateRegistrationPdfMode = mode === 'bulk' ? 'bulk' : 'single';
                this.candidateRegistrationPdfModalOpen = true;
            },

            closeCandidateImportModal() {
                if (this.candidateImportProcessing) {
                    return;
                }

                this.candidateImportModalOpen = false;
                this.resetCandidateImportModal();
            },

            closeCandidateRegistrationPdfModal(force = false) {
                if (this.candidateRegistrationPdfProcessing && !force) {
                    return;
                }

                this.candidateRegistrationPdfModalOpen = false;
                this.resetCandidateRegistrationPdfModal();
            },

            resetCandidateImportModal() {
                this.candidateImportFile = null;
                this.candidateImportPhase = 'upload';
                this.candidateImportProcessing = false;
                this.candidateImportProcessingMessage = '';
                this.candidateImportDragActive = false;
                this.candidateImportExistsMode = 'skip';
                this.candidateImportReport = {
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
                    success: false,
                };
            },

            resetCandidateRegistrationPdfModal() {
                this.candidateRegistrationPdfFiles = [];
                this.candidateRegistrationPdfMode = 'single';
                this.candidateRegistrationPdfPhase = 'upload';
                this.candidateRegistrationPdfProcessing = false;
                this.candidateRegistrationPdfProcessingMessage = '';
                this.candidateRegistrationPdfDragActive = false;
                this.candidateRegistrationPdfReport = {
                    import_completed: false,
                    total_files: 0,
                    importable_school_count: 0,
                    failed_school_count: 0,
                    errors: [],
                    warnings: [],
                    total_rows: 0,
                    create_count: 0,
                    update_count: 0,
                    skip_count: 0,
                    error_count: 0,
                    warning_count: 0,
                    can_import: false,
                    schools: [],
                    rows: [],
                    summary: {},
                    message: '',
                    success: true,
                };
            },

            handleCandidateImportFileSelect(event) {
                const files = event.target.files || [];
                if (files.length > 0) {
                    this.candidateImportFile = files[0];
                }
            },

            handleCandidateImportDrop(event) {
                this.candidateImportDragActive = false;
                const files = event.dataTransfer.files || [];
                if (files.length > 0) {
                    this.candidateImportFile = files[0];
                }
            },

            handleCandidateRegistrationPdfFileSelect(event) {
                const files = event.target.files || [];
                if (files.length > 0) {
                    this.candidateRegistrationPdfFiles = this.candidateRegistrationPdfMode === 'bulk'
                        ? Array.from(files)
                        : [files[0]];
                }
            },

            handleCandidateRegistrationPdfDrop(event) {
                this.candidateRegistrationPdfDragActive = false;
                const files = event.dataTransfer.files || [];
                if (files.length > 0) {
                    const pdfFiles = Array.from(files).filter(file => (file.type || '').includes('pdf') || /\.pdf$/i.test(file.name));
                    this.candidateRegistrationPdfFiles = this.candidateRegistrationPdfMode === 'bulk'
                        ? pdfFiles
                        : (pdfFiles[0] ? [pdfFiles[0]] : []);
                }
            },

            downloadCandidateTemplate() {
                window.location.href = '/admin/api/candidates/import/template?exam_type=CSEE';
            },

            async validateCandidateImportFile() {
                if (!this.candidateImportFile) {
                    this.showMessage('Please select a CSEE candidate CSV file.', 'error');
                    return;
                }

                this.candidateImportProcessing = true;
                this.candidateImportPhase = 'processing';
                this.candidateImportProcessingMessage = 'Validating CSEE candidate file...';

                try {
                    const formData = new FormData();
                    formData.append('file', this.candidateImportFile);
                    formData.append('exam_type', 'CSEE');
                    formData.append('on_exists_mode', this.candidateImportExistsMode);

                    const response = await fetch('/admin/api/candidates/import/validate', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: formData,
                    });

                    const data = await this.parseApiResponse(response);
                    this.candidateImportReport = data;
                    this.candidateImportPhase = 'report';
                    this.showMessage(data.message || 'Validation complete.', data.error_count > 0 ? 'error' : 'success');
                } catch (error) {
                    console.error('Error validating CSEE candidate import:', error);
                    this.candidateImportPhase = 'upload';
                    this.showMessage(error.message || 'Unable to validate CSEE candidate file.', 'error');
                } finally {
                    this.candidateImportProcessing = false;
                    this.candidateImportProcessingMessage = '';
                }
            },

            async commitCandidateImportFile() {
                if (!this.candidateImportFile || !this.candidateImportReport.can_import) {
                    this.showMessage('There are no valid CSEE candidate rows ready to import.', 'error');
                    return;
                }

                this.candidateImportProcessing = true;
                this.candidateImportPhase = 'processing';
                this.candidateImportProcessingMessage = 'Importing CSEE candidates...';

                try {
                    const formData = new FormData();
                    formData.append('file', this.candidateImportFile);
                    formData.append('exam_type', 'CSEE');
                    formData.append('on_exists_mode', this.candidateImportExistsMode);

                    const response = await fetch('/admin/api/candidates/import/commit', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: formData,
                    });

                    const data = await this.parseApiResponse(response);

                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Unable to import CSEE candidates.');
                    }

                    await this.loadCandidates();
                    this.closeCandidateImportModal();
                    this.showMessage(data.message || 'CSEE candidates imported successfully.', 'success');
                } catch (error) {
                    console.error('Error importing CSEE candidates:', error);
                    this.candidateImportPhase = 'report';
                    this.showMessage(error.message || 'Unable to import CSEE candidates.', 'error');
                } finally {
                    this.candidateImportProcessing = false;
                    this.candidateImportProcessingMessage = '';
                }
            },

            async downloadCandidateImportErrors() {
                if (!this.candidateImportReport.errors || this.candidateImportReport.errors.length === 0) {
                    return;
                }

                try {
                    const response = await fetch('/admin/api/candidates/import/download-errors', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ errors: this.candidateImportReport.errors }),
                    });

                    if (!response.ok) {
                        throw new Error('Unable to download import errors.');
                    }

                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `csee_candidate_import_errors_${Date.now()}.csv`;
                    document.body.appendChild(link);
                    link.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(link);
                } catch (error) {
                    console.error('Error downloading CSEE import errors:', error);
                    this.showMessage(error.message || 'Unable to download import errors.', 'error');
                }
            },

            async validateCandidateRegistrationPdfFile() {
                if (!this.candidateRegistrationPdfFiles.length) {
                    this.showMessage('Please select one or more CSEE registration PDF files.', 'error');
                    return;
                }

                this.candidateRegistrationPdfProcessing = true;
                this.candidateRegistrationPdfPhase = 'processing';
                this.candidateRegistrationPdfProcessingMessage = 'Reading and validating CSEE registration PDF...';

                try {
                    const formData = new FormData();
                    this.candidateRegistrationPdfFiles.forEach(file => formData.append('files[]', file));

                    const response = await fetch('/admin/api/candidates/import/csee-registration-pdf/validate', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: formData,
                    });

                    const data = await this.parseApiResponse(response);
                    this.candidateRegistrationPdfReport = data;
                    this.candidateRegistrationPdfPhase = 'report';
                    this.showMessage(data.message || 'Registration PDF validated successfully.', data.error_count > 0 ? 'error' : 'success');
                } catch (error) {
                    console.error('Error validating CSEE registration PDF:', error);
                    this.candidateRegistrationPdfReport = {
                        import_completed: false,
                        total_files: 0,
                        importable_school_count: 0,
                        failed_school_count: 0,
                        errors: [],
                        warnings: [],
                        total_rows: 0,
                        create_count: 0,
                        update_count: 0,
                        skip_count: 0,
                        error_count: 1,
                        warning_count: 0,
                        can_import: false,
                        schools: [],
                        rows: [],
                        summary: {},
                        message: error.message || 'Unable to validate the CSEE registration PDF.',
                        success: false,
                    };
                    this.candidateRegistrationPdfPhase = 'report';
                    this.showMessage(error.message || 'Unable to validate the CSEE registration PDF.', 'error');
                } finally {
                    this.candidateRegistrationPdfProcessing = false;
                    this.candidateRegistrationPdfProcessingMessage = '';
                }
            },

            async commitCandidateRegistrationPdfFile() {
                if (!this.candidateRegistrationPdfReport.can_import || !(this.candidateRegistrationPdfReport.commit_payloads || []).length) {
                    this.showMessage('There are no valid school registration PDFs ready to import.', 'error');
                    return;
                }

                this.candidateRegistrationPdfProcessing = true;
                this.candidateRegistrationPdfPhase = 'processing';
                this.candidateRegistrationPdfProcessingMessage = 'Importing registration subjects from PDF...';

                try {
                    const response = await fetch('/admin/api/candidates/import/csee-registration-pdf/commit', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            parsed_payloads: this.candidateRegistrationPdfReport.commit_payloads,
                        }),
                    });

                    const data = await this.parseApiResponse(response);

                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Unable to import the registration PDF.');
                    }

                    this.candidateRegistrationPdfReport = {
                        ...data,
                        import_completed: true,
                    };
                    this.candidateRegistrationPdfPhase = 'report';
                    this.showMessage(data.message || 'CSEE registration PDFs imported successfully.', 'success');
                    this.loadCandidates();
                } catch (error) {
                    console.error('Error importing CSEE registration PDF:', error);
                    this.candidateRegistrationPdfReport = {
                        ...this.candidateRegistrationPdfReport,
                        import_completed: false,
                        message: error.message || 'Unable to import the registration PDF.',
                        success: false,
                        can_import: false,
                    };
                    this.candidateRegistrationPdfPhase = 'report';
                    this.showMessage(error.message || 'Unable to import the registration PDF.', 'error');
                } finally {
                    this.candidateRegistrationPdfProcessing = false;
                    this.candidateRegistrationPdfProcessingMessage = '';
                }
            },

            async downloadCandidateRegistrationPdfErrors() {
                if (!this.candidateRegistrationPdfReport.errors || this.candidateRegistrationPdfReport.errors.length === 0) {
                    return;
                }

                try {
                    const response = await fetch('/admin/api/candidates/import/download-errors', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ errors: this.candidateRegistrationPdfReport.errors }),
                    });

                    if (!response.ok) {
                        throw new Error('Unable to download registration PDF import errors.');
                    }

                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `csee_registration_pdf_import_errors_${Date.now()}.csv`;
                    document.body.appendChild(link);
                    link.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(link);
                } catch (error) {
                    console.error('Error downloading registration PDF import errors:', error);
                    this.showMessage(error.message || 'Unable to download registration PDF import errors.', 'error');
                }
            },

            viewCandidate(candidate) {
                this.viewingCandidate = candidate || {};
                this.candidateViewModalOpen = true;
            },

            closeCandidateViewModal() {
                this.candidateViewModalOpen = false;
                this.viewingCandidate = {};
            },

            editCandidate(candidate) {
                this.editingCandidateId = candidate.id;
                this.candidateForm = {
                    candidate_id: candidate.candidate_id || '',
                    full_name: candidate.full_name || '',
                    gender: candidate.gender || '',
                    school_id: candidate.school_id ? String(candidate.school_id) : '',
                };
                this.candidateModalOpen = true;
            },

            autoSelectCandidateSchool() {
                const schoolCode = this.extractSchoolCodeFromCandidateId(this.candidateForm.candidate_id);
                const school = this.findSchoolByCode(schoolCode);

                if (school) {
                    this.candidateForm.school_id = String(school.id);
                }
            },

            async saveCandidate() {
                this.autoSelectCandidateSchool();

                if (!this.candidateForm.school_id) {
                    this.showMessage('No CSEE centre matched the first 5 characters of this index number.', 'error');
                    return;
                }

                this.savingCandidate = true;

                try {
                    const isEditing = Boolean(this.editingCandidateId);
                    const payload = {
                        candidate_id: this.candidateForm.candidate_id,
                        full_name: this.candidateForm.full_name,
                        gender: this.candidateForm.gender,
                        school_id: this.candidateForm.school_id,
                        exam_type: 'CSEE',
                        combination: null,
                        candidate_type: 'SCHOOL',
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

                    const data = await this.parseApiResponse(response);

                    if (!response.ok) {
                        throw new Error(data.message || 'Unable to save candidate.');
                    }

                    this.closeCandidateModal();
                    await this.loadCandidates();
                    this.showMessage(data.message || (isEditing ? 'Candidate updated successfully.' : 'Candidate registered successfully.'), 'success');
                } catch (error) {
                    console.error('Error saving CSEE candidate:', error);
                    this.showMessage(error.message || 'Unable to save candidate.', 'error');
                } finally {
                    this.savingCandidate = false;
                }
            },

            async deleteCandidate(candidateId) {
                if (!window.confirm('Delete this candidate record?')) {
                    return;
                }

                try {
                    const response = await fetch(`/admin/api/candidates/${candidateId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });

                    const data = await this.parseApiResponse(response);

                    if (!response.ok) {
                        throw new Error(data.message || 'Unable to delete candidate.');
                    }

                    this.selectedCandidateIds = this.selectedCandidateIds.filter(id => String(id) !== String(candidateId));
                    await this.loadCandidates();
                    this.showMessage(data.message || 'Candidate deleted successfully.', 'success');
                } catch (error) {
                    console.error('Error deleting CSEE candidate:', error);
                    this.showMessage(error.message || 'Unable to delete candidate.', 'error');
                }
            },

            async bulkDeleteCandidates() {
                if (!this.selectedCandidateIds.length) {
                    return;
                }

                if (!window.confirm(`Delete ${this.selectedCandidateIds.length} selected candidate(s)?`)) {
                    return;
                }

                try {
                    const response = await fetch('/admin/api/candidates/bulk-delete', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ ids: this.selectedCandidateIds }),
                    });

                    const data = await this.parseApiResponse(response);

                    if (!response.ok) {
                        throw new Error(data.message || 'Unable to delete selected candidates.');
                    }

                    this.selectedCandidateIds = [];
                    await this.loadCandidates();
                    this.showMessage(data.message || 'Selected candidates deleted successfully.', 'success');
                } catch (error) {
                    console.error('Error deleting selected CSEE candidates:', error);
                    this.showMessage(error.message || 'Unable to delete selected candidates.', 'error');
                }
            },

            async loadSchools() {
                this.loadingSchools = true;

                try {
                    const params = new URLSearchParams({
                        page: String(this.schoolsCurrentPage),
                        page_size: String(this.schoolsPageSize),
                        search: this.schoolSearch || '',
                        region_id: this.schoolFilterRegion || '',
                        district_id: this.schoolFilterDistrict || '',
                    });

                    const response = await fetch(`/admin/api/exam-types/csee/schools?${params.toString()}`);
                    const data = await this.parseApiResponse(response);
                    this.schools = Array.isArray(data.data) ? data.data : [];
                    this.selectedSchoolIds = this.selectedSchoolIds.filter(id =>
                        this.schools.some(school => String(school.id) === String(id))
                    );
                    this.schoolsTotalCount = Number(data.meta?.total || this.schools.length || 0);
                    this.schoolsCurrentPage = Number(data.meta?.current_page || this.schoolsCurrentPage || 1);
                } catch (error) {
                    console.error('Error loading CSEE centres:', error);
                    this.showMessage(error.message || 'Error loading CSEE centres.', 'error');
                } finally {
                    this.loadingSchools = false;
                }
            },

            toggleSchoolSelection(schoolId, checked) {
                const normalizedId = String(schoolId);

                if (checked) {
                    if (!this.selectedSchoolIds.includes(normalizedId)) {
                        this.selectedSchoolIds = [...this.selectedSchoolIds, normalizedId];
                    }
                    return;
                }

                this.selectedSchoolIds = this.selectedSchoolIds.filter(id => id !== normalizedId);
            },

            toggleAllVisibleSchools(checked) {
                const visibleIds = this.schools.map(school => String(school.id));

                if (checked) {
                    this.selectedSchoolIds = Array.from(new Set([
                        ...this.selectedSchoolIds,
                        ...visibleIds,
                    ]));
                    return;
                }

                this.selectedSchoolIds = this.selectedSchoolIds.filter(id => !visibleIds.includes(id));
            },

            async syncSchools() {
                this.syncingSchools = true;

                try {
                    const response = await fetch('/admin/api/exam-types/csee/schools/sync-necta-2025', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });

                    const data = await this.parseApiResponse(response);

                    if (!response.ok) {
                        throw new Error(data.message || 'Unable to sync CSEE centres.');
                    }

                    this.schoolsCurrentPage = 1;
                    await this.loadSchools();
                    await this.loadSchoolDirectory();
                    this.showMessage(data.message || 'NECTA CSEE 2025 centres sync completed.', 'success');
                } catch (error) {
                    console.error('Error syncing CSEE centres:', error);
                    this.showMessage(error.message || 'Unable to sync CSEE centres.', 'error');
                } finally {
                    this.syncingSchools = false;
                }
            },

            async handleSchoolParticularsFileChange(event) {
                const file = event.target.files?.[0];

                if (!file) {
                    return;
                }

                await this.importSchoolParticulars(file);
                event.target.value = '';
            },

            async autoImportSchoolParticulars() {
                await this.importSchoolParticulars(null, 'auto');
            },

            async importSchoolParticulars(file = null, mode = 'upload') {
                if (mode === 'upload') {
                    this.uploadingSchoolParticulars = true;
                } else {
                    this.autoEnrichingSchoolParticulars = true;
                }

                try {
                    const formData = new FormData();
                    if (file) {
                        formData.append('file', file);
                    }

                    const response = await fetch('/admin/api/exam-types/csee/schools/import-particulars', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: formData,
                    });

                    const data = await this.parseApiResponse(response);

                    if (! response.ok) {
                        const importErrors = Array.isArray(data.summary?.errors) && data.summary.errors.length
                            ? data.summary.errors
                            : [data.message || 'Unable to import CSEE school particulars.'];
                        this.openSchoolImportResultsModal(data.summary || null, importErrors, []);
                        const importError = importErrors[0];
                        throw new Error(importError);
                    }

                    const skippedRows = Array.isArray(data.summary?.skips) ? data.summary.skips : [];
                    this.openSchoolImportResultsModal(data.summary || null, [], skippedRows);

                    this.schoolsCurrentPage = 1;
                    await this.loadSchools();
                    await this.loadSchoolDirectory();
                    this.showMessage(data.message || 'CSEE school particulars imported successfully.', 'success');
                } catch (error) {
                    console.error('Error importing CSEE school particulars:', error);
                    this.showMessage(error.message || 'Unable to import CSEE school particulars.', 'error');
                } finally {
                    if (mode === 'upload') {
                        this.uploadingSchoolParticulars = false;
                    } else {
                        this.autoEnrichingSchoolParticulars = false;
                    }
                }
            },

            async parseApiResponse(response) {
                const contentType = response.headers.get('content-type') || '';

                if (contentType.includes('application/json')) {
                    return await response.json();
                }

                const text = await response.text();

                if (!response.ok) {
                    throw new Error(text.trim() || `Request failed with status ${response.status}.`);
                }

                throw new Error('Unexpected non-JSON response from server.');
            },

            buildVisiblePages(currentPage, totalPages) {
                if (totalPages <= 7) {
                    return Array.from({ length: totalPages }, (_, index) => index + 1);
                }

                const start = Math.max(1, Math.min(currentPage - 2, totalPages - 4));
                const end = Math.min(totalPages, start + 4);

                return Array.from({ length: end - start + 1 }, (_, index) => start + index);
            },

            goToSchoolsPage(page) {
                if (page >= 1 && page <= this.schoolsTotalPages) {
                    this.schoolsCurrentPage = page;
                    this.loadSchools();
                }
            },

            goToFirstSchoolsPage() {
                this.schoolsCurrentPage = 1;
                this.loadSchools();
            },

            goToPreviousSchoolsPage() {
                if (this.schoolsCurrentPage > 1) {
                    this.schoolsCurrentPage--;
                    this.loadSchools();
                }
            },

            goToNextSchoolsPage() {
                if (this.schoolsCurrentPage < this.schoolsTotalPages) {
                    this.schoolsCurrentPage++;
                    this.loadSchools();
                }
            },

            goToLastSchoolsPage() {
                this.schoolsCurrentPage = this.schoolsTotalPages;
                this.loadSchools();
            },

            resolveRegionId(school) {
                if (!school) {
                    return '';
                }

                if (school.region_id) {
                    return school.region_id;
                }

                const districtId = school.district_id || school.council_id;
                const district = this.districts.find(item => String(item.id) === String(districtId));
                return district ? district.region_id : '';
            },

            viewSchoolParticulars(school) {
                this.selectedSchoolParticulars = school;
                this.schoolParticularsModalOpen = true;
            },

            closeSchoolParticularsModal() {
                this.schoolParticularsModalOpen = false;
                this.selectedSchoolParticulars = null;
            },

            openEditSchoolModal(school) {
                this.schoolEditForm = {
                    id: school.id,
                    code: school.code || '',
                    name: school.name || '',
                    ownership: school.ownership || 'GOVERNMENT',
                    region_id: school.region_id ? String(school.region_id) : '',
                    district_id: school.district_id ? String(school.district_id) : '',
                };
                this.schoolEditOwnershipOpen = false;
                this.schoolEditOwnershipSearch = '';
                this.schoolEditRegionOpen = false;
                this.schoolEditRegionSearch = '';
                this.schoolEditDistrictOpen = false;
                this.schoolEditDistrictSearch = '';
                this.schoolEditModalOpen = true;
            },

            closeEditSchoolModal() {
                this.schoolEditModalOpen = false;
                this.savingSchoolEdit = false;
                this.schoolEditOwnershipOpen = false;
                this.schoolEditOwnershipSearch = '';
                this.schoolEditRegionOpen = false;
                this.schoolEditRegionSearch = '';
                this.schoolEditDistrictOpen = false;
                this.schoolEditDistrictSearch = '';
                this.schoolEditForm = {
                    id: null,
                    code: '',
                    name: '',
                    ownership: 'GOVERNMENT',
                    region_id: '',
                    district_id: '',
                };
            },

            selectSchoolEditOwnership(value) {
                this.schoolEditForm.ownership = value;
                this.schoolEditOwnershipOpen = false;
                this.schoolEditOwnershipSearch = '';
            },

            selectSchoolEditRegion(value) {
                this.schoolEditForm.region_id = value ? String(value) : '';
                this.schoolEditForm.district_id = '';
                this.schoolEditRegionOpen = false;
                this.schoolEditRegionSearch = '';
            },

            selectSchoolEditDistrict(value) {
                this.schoolEditForm.district_id = value ? String(value) : '';
                this.syncSchoolEditRegionFromDistrict();
                this.schoolEditDistrictOpen = false;
                this.schoolEditDistrictSearch = '';
            },

            syncSchoolEditDistrictRegion() {
                if (!this.schoolEditForm.region_id) {
                    return;
                }

                if (this.schoolEditForm.district_id) {
                    const district = this.districts.find(item => String(item.id) === String(this.schoolEditForm.district_id));
                    if (district && String(district.region_id) !== String(this.schoolEditForm.region_id)) {
                        this.schoolEditForm.district_id = '';
                    }
                }
            },

            syncSchoolEditRegionFromDistrict() {
                if (!this.schoolEditForm.district_id) {
                    return;
                }

                const district = this.districts.find(item => String(item.id) === String(this.schoolEditForm.district_id));
                if (district) {
                    this.schoolEditForm.region_id = String(district.region_id);
                }
            },

            async saveEditedSchool() {
                if (!this.schoolEditForm.id) {
                    return;
                }

                this.savingSchoolEdit = true;

                try {
                    const response = await fetch(`/admin/api/schools/${this.schoolEditForm.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            code: this.schoolEditForm.code,
                            name: this.schoolEditForm.name,
                            ownership: this.schoolEditForm.ownership,
                            region_id: this.schoolEditForm.region_id,
                            district_id: this.schoolEditForm.district_id,
                        }),
                    });

                    const data = await this.parseApiResponse(response);

                    if (!response.ok) {
                        throw new Error(data.message || 'Unable to update centre.');
                    }

                    this.closeEditSchoolModal();
                    await this.loadSchools();
                    await this.loadSchoolDirectory();
                    this.showMessage(data.message || 'Centre updated successfully.', 'success');
                } catch (error) {
                    console.error('Error updating centre:', error);
                    this.showMessage(error.message || 'Unable to update centre.', 'error');
                } finally {
                    this.savingSchoolEdit = false;
                }
            },

            async deleteSchool(school) {
                if (!window.confirm(`Delete centre ${school.code} - ${school.name}?`)) {
                    return;
                }

                try {
                    const response = await fetch(`/admin/api/schools/${school.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });

                    const data = await this.parseApiResponse(response);

                    if (!response.ok) {
                        throw new Error(data.message || 'Unable to delete centre.');
                    }

                    await this.loadSchools();
                    await this.loadSchoolDirectory();
                    this.showMessage(data.message || 'Centre deleted successfully.', 'success');
                } catch (error) {
                    console.error('Error deleting centre:', error);
                    this.showMessage(error.message || 'Unable to delete centre.', 'error');
                }
            },

            openSchoolImportResultsModal(summary = null, errors = [], skips = []) {
                this.schoolImportSummary = summary;
                this.schoolImportFailedRows = Array.isArray(errors) ? errors : [String(errors)];
                this.schoolImportSkippedRows = Array.isArray(skips) ? skips : [String(skips)];
                this.schoolImportResultsModalOpen = true;
            },

            closeSchoolImportResultsModal() {
                this.schoolImportResultsModalOpen = false;
                this.schoolImportSummary = null;
                this.schoolImportFailedRows = [];
                this.schoolImportSkippedRows = [];
            },

            showMessage(message, type) {
                const alertDiv = document.createElement('div');
                const bgClass = type === 'success'
                    ? 'bg-green-100 text-green-700 border-green-300'
                    : 'bg-red-100 text-red-700 border-red-300';

                alertDiv.className = `fixed top-24 right-8 ${bgClass} p-4 rounded-lg border max-w-sm z-50 shadow-lg`;
                alertDiv.textContent = message;
                alertDiv.style.wordWrap = 'break-word';

                document.body.appendChild(alertDiv);
                setTimeout(() => alertDiv.remove(), 4000);
            },
        };
    }
</script>
@endsection
