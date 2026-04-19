@extends('layout')

@section('content')
@include('registration.partials.theme')

<div class="registration-shell mark-entry-shell">
    <div class="w-full flex flex-col lg:flex-row gap-0 lg:gap-4" x-data="psleMarkEntryManager()" x-init="init()">
        <aside class="mark-entry-sidebar hidden lg:block w-64 text-gray-100 min-h-screen sticky top-[140px] overflow-y-auto">
            <div class="mark-entry-sidebar-inner p-6">
                <h2 class="text-lg font-bold text-white mb-6">Mark Entry Lifecycle</h2>

                <div class="mb-8">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <i class="fas fa-chart-bar"></i> Entry & Validation
                    </h3>
                    <ul class="space-y-2">
                        <template x-for="tab in entryTabs" :key="tab.key">
                            <li>
                                <a href="#" @click.prevent="setActiveTab(tab.key)" :class="activeTab === tab.key ? 'bg-blue-600 text-white pl-3 border-l-4 border-blue-400' : 'text-gray-300 hover:text-blue-400'" class="sidebar-link text-sm transition cursor-pointer block rounded px-2 py-1">
                                    <span class="mark-entry-sidebar-link-inner">
                                        <i :class="tab.icon" class="mark-entry-sidebar-icon"></i>
                                        <span x-text="tab.label"></span>
                                    </span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>

                <div class="mb-8">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <i class="fas fa-search"></i> Moderation & Review
                    </h3>
                    <ul class="space-y-2">
                        <template x-for="tab in moderationTabs" :key="tab.key">
                            <li>
                                <a href="#" @click.prevent="setActiveTab(tab.key)" :class="activeTab === tab.key ? 'bg-yellow-600 text-white pl-3 border-l-4 border-yellow-400' : 'text-gray-300 hover:text-yellow-400'" class="sidebar-link text-sm transition cursor-pointer block rounded px-2 py-1">
                                    <span class="mark-entry-sidebar-link-inner">
                                        <i :class="tab.icon" class="mark-entry-sidebar-icon"></i>
                                        <span x-text="tab.label"></span>
                                    </span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>

                <div class="mb-8">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <i class="fas fa-lock"></i> Submission & Locking
                    </h3>
                    <ul class="space-y-2">
                        <template x-for="tab in lockingTabs" :key="tab.key">
                            <li>
                                <a href="#" @click.prevent="setActiveTab(tab.key)" :class="activeTab === tab.key ? 'bg-green-600 text-white pl-3 border-l-4 border-green-400' : 'text-gray-300 hover:text-green-400'" class="sidebar-link text-sm transition cursor-pointer block rounded px-2 py-1">
                                    <span class="mark-entry-sidebar-link-inner">
                                        <i :class="tab.icon" class="mark-entry-sidebar-icon"></i>
                                        <span x-text="tab.label"></span>
                                    </span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>

                <div class="mb-8">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <i class="fas fa-file-alt"></i> Reports & Exports
                    </h3>
                    <ul class="space-y-2">
                        <template x-for="tab in reportTabs" :key="tab.key">
                            <li>
                                <a href="#" @click.prevent="setActiveTab(tab.key)" :class="activeTab === tab.key ? 'bg-purple-600 text-white pl-3 border-l-4 border-purple-400' : 'text-gray-300 hover:text-purple-400'" class="sidebar-link text-sm transition cursor-pointer block rounded px-2 py-1">
                                    <span class="mark-entry-sidebar-link-inner">
                                        <i :class="tab.icon" class="mark-entry-sidebar-icon"></i>
                                        <span x-text="tab.label"></span>
                                    </span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>

                <div class="mb-8">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <i class="fas fa-clock"></i> Monitoring & Audit
                    </h3>
                    <ul class="space-y-2">
                        <template x-for="tab in auditTabs" :key="tab.key">
                            <li>
                                <a href="#" @click.prevent="setActiveTab(tab.key)" :class="activeTab === tab.key ? 'bg-blue-600 text-white pl-3 border-l-4 border-blue-400' : 'text-gray-300 hover:text-blue-300'" class="sidebar-link text-sm transition cursor-pointer block rounded px-2 py-1">
                                    <span class="mark-entry-sidebar-link-inner">
                                        <i :class="tab.icon" class="mark-entry-sidebar-icon"></i>
                                        <span x-text="tab.label"></span>
                                    </span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>

                <div class="mb-8">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <i class="fas fa-cog"></i> Administration
                    </h3>
                    <ul class="space-y-2">
                        <template x-for="tab in administrationTabs" :key="tab.key">
                            <li>
                                <a href="#" @click.prevent="setActiveTab(tab.key)" :class="activeTab === tab.key ? 'bg-indigo-600 text-white pl-3 border-l-4 border-indigo-400' : 'text-gray-300 hover:text-indigo-400'" class="sidebar-link text-sm transition cursor-pointer block rounded px-2 py-1">
                                    <span class="mark-entry-sidebar-link-inner">
                                        <i :class="tab.icon" class="mark-entry-sidebar-icon"></i>
                                        <span x-text="tab.label"></span>
                                    </span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>

                <hr class="my-6 border-gray-700">
                <p class="text-xs text-gray-500 text-center">PSLE Mark Entry v1.0</p>
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
                                <span class="registration-page-kicker mark-entry-kicker">PSLE Operations Workspace</span>
                                <h1 class="registration-page-title">PSLE Mark Entry</h1>
                                <p class="registration-page-subtitle">Move from context selection to upload, moderation, locking, and reporting in one controlled PSLE workflow.</p>
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
                                        <span x-text="examYear || 'Select Year'"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mark-entry-main px-4 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8 flex-1 overflow-y-auto">
                <div class="space-y-6">
                    <div class="registration-surface mark-entry-context-card p-6">
                        <div class="mark-entry-step-heading">
                            <div>
                                <h2 class="text-lg font-bold text-gray-800">1. Select Context</h2>
                                <p class="mark-entry-step-copy">Set the working year, geography, school, and subject before downloading templates, uploading marks, or moving PSLE batches through review and locking.</p>
                            </div>
                            <div class="mark-entry-step-badges">
                                <span class="mark-entry-step-badge"><i class="fas fa-calendar-alt"></i> Exam cycle</span>
                                <span class="mark-entry-step-badge"><i class="fas fa-school"></i> School scope</span>
                                <span class="mark-entry-step-badge"><i class="fas fa-book-open"></i> Subject target</span>
                            </div>
                        </div>

                        <div class="mark-entry-context-grid">
                            <div class="mark-entry-context-field mark-entry-context-field-year flex flex-col h-full">
                                        <div class="flex flex-col">
                                            <label class="mb-2 block text-sm font-semibold text-gray-700">Exam Year</label>
                                            <div class="relative" @click.outside="yearOpen = false">
                                                <button @click="yearOpen = !yearOpen; regionOpen = false; districtOpen = false; schoolOpen = false; subjectOpen = false" class="w-full px-3 py-2 border border-gray-300 text-left bg-white hover:bg-gray-50 transition-colors flex justify-between items-center rounded-none text-sm h-10">
                                                    <span class="truncate text-gray-700" x-text="selectedYearRecord?.year_label || 'Select year'"></span>
                                                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                                </button>
                                                <div x-show="yearOpen" x-cloak class="absolute top-full left-0 right-0 bg-white border border-t-0 border-gray-300 z-50 rounded-none flex flex-col" x-transition>
                                                    <input x-model="yearSearch" type="text" placeholder="Search year..." class="filter-search-input px-3 py-2 border-b border-gray-200 rounded-none focus:outline-none focus:ring-0 text-sm flex-shrink-0">
                                                    <div class="max-h-56 overflow-y-auto">
                                                        <div @click="chooseYear('')" class="cursor-pointer px-3 py-2 text-sm transition-colors hover:bg-blue-500 hover:text-white">Select year</div>
                                                        <template x-for="year in searchableYears" :key="year.id">
                                                            <div @click="chooseYear(year.year_label)" :class="String(examYear) === String(year.year_label) ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'" class="cursor-pointer px-3 py-2 text-sm transition-colors" x-text="year.year_label"></div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                            </div>

                            <div class="mark-entry-context-field mark-entry-context-field-region flex flex-col h-full">
                                        <div class="flex flex-col">
                                            <label class="mb-2 block text-sm font-semibold text-gray-700">Region</label>
                                            <div class="relative" @click.outside="regionOpen = false">
                                                <button @click="regionOpen = !regionOpen; districtOpen = false; schoolOpen = false; subjectOpen = false" class="w-full px-3 py-2 border border-gray-300 text-left bg-white hover:bg-gray-50 transition-colors flex justify-between items-center rounded-none text-sm h-10">
                                                    <span class="truncate text-gray-700" x-text="selectedRegionRecord?.name || 'All regions'"></span>
                                                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                                </button>
                                                <div x-show="regionOpen" x-cloak class="absolute top-full left-0 right-0 bg-white border border-t-0 border-gray-300 z-50 rounded-none flex flex-col" x-transition>
                                                    <input x-model="regionSearch" type="text" placeholder="Search regions..." class="filter-search-input px-3 py-2 border-b border-gray-200 rounded-none focus:outline-none focus:ring-0 text-sm flex-shrink-0">
                                                    <div class="max-h-56 overflow-y-auto">
                                                        <div @click="chooseRegion('')" class="cursor-pointer px-3 py-2 text-sm transition-colors hover:bg-blue-500 hover:text-white">All regions</div>
                                                        <template x-for="region in searchableRegions" :key="region.id">
                                                            <div @click="chooseRegion(region.id)" :class="String(selectedRegion) === String(region.id) ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'" class="cursor-pointer px-3 py-2 text-sm transition-colors" x-text="region.name"></div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                            </div>

                            <div class="mark-entry-context-field mark-entry-context-field-district flex flex-col h-full">
                                        <div class="flex flex-col">
                                            <label class="mb-2 block text-sm font-semibold text-gray-700">Council</label>
                                            <div class="relative" @click.outside="districtOpen = false">
                                                <button @click="districtOpen = !districtOpen; regionOpen = false; schoolOpen = false; subjectOpen = false" class="w-full px-3 py-2 border border-gray-300 text-left bg-white hover:bg-gray-50 transition-colors flex justify-between items-center rounded-none text-sm h-10">
                                                    <span class="truncate text-gray-700" x-text="selectedDistrictRecord?.name || 'All councils'"></span>
                                                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                                </button>
                                                <div x-show="districtOpen" x-cloak class="absolute top-full left-0 right-0 bg-white border border-t-0 border-gray-300 z-50 rounded-none flex flex-col" x-transition>
                                                    <input x-model="districtSearch" type="text" placeholder="Search councils..." class="filter-search-input px-3 py-2 border-b border-gray-200 rounded-none focus:outline-none focus:ring-0 text-sm flex-shrink-0">
                                                    <div class="max-h-56 overflow-y-auto">
                                                        <div @click="chooseDistrict('')" class="cursor-pointer px-3 py-2 text-sm transition-colors hover:bg-blue-500 hover:text-white">All councils</div>
                                                        <template x-for="district in searchableDistricts" :key="district.id">
                                                            <div @click="chooseDistrict(district.id)" :class="String(selectedDistrict) === String(district.id) ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'" class="cursor-pointer px-3 py-2 text-sm transition-colors" x-text="district.name"></div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                            </div>

                            <div class="mark-entry-context-field mark-entry-context-field-school flex flex-col h-full">
                                        <div class="flex min-w-[320px] flex-col">
                                            <label class="mb-2 block text-sm font-semibold text-gray-700">Primary School</label>
                                            <div class="relative" @click.outside="schoolOpen = false">
                                                <button @click="schoolOpen = !schoolOpen; regionOpen = false; districtOpen = false; subjectOpen = false" class="w-full px-3 py-2 border border-gray-300 text-left bg-white hover:bg-gray-50 transition-colors flex justify-between items-center rounded-none text-sm h-10">
                                                    <span class="block min-w-0 flex-1 whitespace-nowrap pr-3 text-gray-700" x-text="selectedSchoolRecord ? schoolDisplayLabel(selectedSchoolRecord) : 'All primary schools'"></span>
                                                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                                </button>
                                                <div x-show="schoolOpen" x-cloak class="absolute top-full left-0 z-50 flex w-[680px] max-w-[78vw] flex-col rounded-none border border-t-0 border-gray-300 bg-white" x-transition>
                                                    <input x-model="schoolSearch" type="text" placeholder="Search schools..." class="filter-search-input px-3 py-2 border-b border-gray-200 rounded-none focus:outline-none focus:ring-0 text-sm flex-shrink-0">
                                                    <div class="max-h-56 overflow-y-auto">
                                                        <div @click="chooseSchool('')" class="cursor-pointer px-3 py-2 text-sm transition-colors hover:bg-blue-500 hover:text-white">All primary schools</div>
                                                        <template x-for="school in searchableSchools" :key="school.id">
                                                            <div @click="chooseSchool(school.id)" :class="String(selectedSchool) === String(school.id) ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'" class="cursor-pointer whitespace-nowrap px-3 py-2 text-sm transition-colors" x-text="schoolDisplayLabel(school)"></div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                            </div>

                            <div class="mark-entry-context-field mark-entry-context-field-subject flex flex-col h-full">
                                        <div class="flex min-w-[280px] flex-col">
                                            <label class="mb-2 block text-sm font-semibold text-gray-700">Subject</label>
                                            <div class="relative" @click.outside="subjectOpen = false">
                                                <button @click="subjectOpen = !subjectOpen; regionOpen = false; districtOpen = false; schoolOpen = false" class="w-full px-3 py-2 border border-gray-300 text-left bg-white hover:bg-gray-50 transition-colors flex justify-between items-center rounded-none text-sm h-10">
                                                    <span class="block min-w-0 flex-1 whitespace-nowrap pr-3 text-gray-700" x-text="selectedSubjectRecord ? (selectedSubjectRecord.code + ' - ' + selectedSubjectRecord.name) : 'All subjects'"></span>
                                                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                                </button>
                                                <div x-show="subjectOpen" x-cloak class="absolute top-full left-0 z-50 flex w-[560px] max-w-[72vw] flex-col rounded-none border border-t-0 border-gray-300 bg-white" x-transition>
                                                    <input x-model="subjectSearchFilter" type="text" placeholder="Search subjects..." class="filter-search-input px-3 py-2 border-b border-gray-200 rounded-none focus:outline-none focus:ring-0 text-sm flex-shrink-0">
                                                    <div class="max-h-56 overflow-y-auto">
                                                        <div @click="chooseSubject('')" class="cursor-pointer whitespace-nowrap px-3 py-2 text-sm transition-colors hover:bg-blue-500 hover:text-white">All subjects</div>
                                                        <template x-for="subject in searchableSubjects" :key="subject.id">
                                                            <div @click="chooseSubject(subject.id)" :class="String(selectedSubject) === String(subject.id) ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'" class="cursor-pointer whitespace-nowrap px-3 py-2 text-sm transition-colors" x-text="subject.code + ' - ' + subject.name"></div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                            </div>

                            <div class="mark-entry-context-field mark-entry-context-field-reset flex items-end h-full">
                                <button type="button" @click="resetContext()" class="mark-entry-secondary-btn w-full px-4 py-2 text-gray-800 font-medium text-sm transition-colors h-10">
                                    Reset
                                </button>
                            </div>
                        </div>

                        <div class="mark-entry-context-footer">
                            <div class="mark-entry-scope-summary">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Current Scope</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900" x-text="scopeSummary"></p>
                            </div>
                            <div class="mark-entry-readiness-grid">
                                <template x-for="check in readinessChecks" :key="check.key">
                                    <div class="mark-entry-readiness-item" :class="check.ready ? 'mark-entry-readiness-item-ready' : 'mark-entry-readiness-item-pending'">
                                        <i :class="check.ready ? 'fas fa-circle-check' : 'fas fa-circle-notch'"></i>
                                        <span x-text="check.label"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="registration-surface mark-entry-tab-shell scroll-mt-32">
                        <div class="mark-entry-mode-bar">
                            <button type="button" @click="setActiveTab('single')" :class="entryMode === 'single' ? 'mark-entry-mode-button-active' : 'text-gray-600'" class="mark-entry-mode-button py-4 font-medium transition-colors">
                                <i class="fas fa-file-csv mr-2"></i>Single Subject CSV
                            </button>
                            <button type="button" @click="setActiveTab('school')" :class="entryMode === 'school' ? 'mark-entry-mode-button-active' : 'text-gray-600'" class="mark-entry-mode-button py-4 font-medium transition-colors">
                                <i class="fas fa-box mr-2"></i>School Bulk ZIP
                            </button>
                            <button type="button" @click="setActiveTab('district')" :class="entryMode === 'district' ? 'mark-entry-mode-button-active' : 'text-gray-600'" class="mark-entry-mode-button py-4 font-medium transition-colors">
                                <i class="fas fa-archive mr-2"></i>District Bulk ZIP
                            </button>
                        </div>
                    </div>

            <section x-show="activeTab === 'single'" class="space-y-6">
                <div>
                    <article class="registration-surface p-6">
                        <div class="mark-entry-step-heading">
                            <div>
                                <h2 class="text-lg font-bold text-gray-800">2. Single Subject Mark Upload</h2>
                                <p class="mark-entry-step-copy">Use the guided instructions, action tools, and upload area below to complete one controlled subject submission.</p>
                            </div>
                            <div class="mark-entry-step-badges">
                                <span class="mark-entry-step-badge"><i class="fas fa-school"></i> School scope</span>
                                <span class="mark-entry-step-badge"><i class="fas fa-book-open"></i> Subject target</span>
                                <span class="mark-entry-step-badge"><i class="fas fa-circle-check"></i> Validation first</span>
                            </div>
                        </div>

                        <div class="mark-entry-instruction-card p-4 mt-4">
                            <p class="text-sm text-blue-900 mb-2">
                                <strong>Important Instructions for Single Subject CSV:</strong>
                            </p>
                            <ul class="text-sm text-blue-800 list-disc list-inside space-y-1">
                                <li><strong>Step 1:</strong> Confirm year, region, council, school, and subject in the context card above.</li>
                                <li><strong>Step 2:</strong> Generate and use the governed PSLE template for the selected school and subject.</li>
                                <li><strong>Step 3:</strong> Fill only the mark values and preserve candidate identity columns exactly as issued.</li>
                                <li><strong>Step 4:</strong> Validate the completed file before committing it into the PSLE intake pipeline.</li>
                            </ul>
                        </div>

                        <div class="mark-entry-inline-tools mt-4 flex flex-wrap items-center gap-3">
                            <button @click="openImportModal('single_csv')" class="mark-entry-primary-btn px-4 py-2 text-white font-medium text-sm transition-colors flex items-center gap-2">
                                <i class="fas fa-file-upload"></i> Upload Single Subject CSV
                            </button>
                            <div class="rounded-2xl border px-4 py-2.5 text-sm font-semibold" :class="isTemplateReady ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-900'">
                                <i :class="isTemplateReady ? 'fas fa-circle-check mr-2' : 'fas fa-clock mr-2'"></i>
                                <span x-text="isTemplateReady ? 'Ready to issue template' : 'Select school and subject'"></span>
                            </div>
                            <button @click="downloadTemplate()" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                                <i class="fas fa-download mr-2"></i>Download CSV Template
                            </button>
                            <button @click="openImportModal('single_csv')" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                <i class="fas fa-cloud-upload-alt mr-2"></i>Upload Completed CSV
                            </button>
                            <button @click="exportScopedPupils()" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-100">
                                <i class="fas fa-file-export mr-2"></i>Export Scoped Pupils
                            </button>
                        </div>
                    </article>
                </div>
            </section>

            <section x-show="activeTab === 'school'" class="space-y-6">
                <div class="grid gap-4">
                    <article class="registration-surface p-6">
                        <div class="mark-entry-step-heading">
                            <div>
                                <h2 class="text-lg font-bold text-gray-800">3. School Bulk ZIP Upload</h2>
                                <p class="mark-entry-step-copy">Prepare one governed package per primary school, then validate and commit all included subject files together.</p>
                            </div>
                            <div class="mark-entry-step-badges">
                                <span class="mark-entry-step-badge"><i class="fas fa-school"></i> School package</span>
                                <span class="mark-entry-step-badge"><i class="fas fa-layer-group"></i> Multiple subjects</span>
                                <span class="mark-entry-step-badge"><i class="fas fa-circle-check"></i> Validation first</span>
                            </div>
                        </div>

                        <div class="mark-entry-instruction-card p-4 mt-4">
                            <p class="text-sm text-blue-900 mb-2">
                                <strong>Important Instructions for School Bulk ZIP:</strong>
                            </p>
                            <ul class="text-sm text-blue-800 list-disc list-inside space-y-1">
                                <li><strong>Step 1:</strong> Confirm year, region, council, and primary school before building the package.</li>
                                <li><strong>Step 2:</strong> Generate governed subject templates for that one school and keep each subject as a separate CSV.</li>
                                <li><strong>Step 3:</strong> Compress the subject CSV files into one school ZIP without changing candidate identity columns.</li>
                                <li><strong>Step 4:</strong> Validate the ZIP first, then commit it only after the intake checks pass.</li>
                            </ul>
                        </div>

                        <div class="mark-entry-inline-tools mt-4 flex flex-wrap items-center gap-3">
                            <button @click="openImportModal('school_zip')" class="rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-violet-700">
                                <i class="fas fa-upload mr-2"></i>Upload School ZIP
                            </button>
                            <div class="rounded-2xl border px-4 py-2.5 text-sm font-semibold" :class="isImportScopeReady ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-900'">
                                <i :class="isImportScopeReady ? 'fas fa-circle-check mr-2' : 'fas fa-clock mr-2'"></i>
                                <span x-text="isImportScopeReady ? 'Ready to intake school ZIP' : 'Select school scope'"></span>
                            </div>
                            <button @click="downloadTemplate()" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                <i class="fas fa-file-csv mr-2"></i>Generate Subject Template
                            </button>
                        </div>

                        <div x-show="false" class="mt-6 overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_30px_70px_rgba(15,23,42,0.12)]">
                            <div class="bg-[linear-gradient(135deg,#7c3aed_0%,#6d28d9_48%,#4f46e5_100%)] px-8 py-7 text-white">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.22em]">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <span x-text="importModeBadge"></span>
                                        </span>
                                        <h3 class="mt-5 text-3xl font-black" x-text="importModalTitle"></h3>
                                        <p class="mt-3 max-w-3xl text-sm leading-7 text-white/85" x-text="importModalDescription"></p>
                                    </div>
                                    <button @click="schoolZipIntakeOpen = false" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white transition hover:bg-white/20">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-6 px-8 py-7">
                                <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">School ZIP Intake Workspace</p>
                                        <p class="mt-1 text-sm text-slate-600">Work through upload, validation, and commit in order for one selected primary school.</p>
                                    </div>
                                    <div class="rounded-2xl border px-4 py-2 text-sm font-semibold" :class="importStep === 1 ? 'border-violet-200 bg-violet-50 text-violet-800' : importStep === 2 ? 'border-blue-200 bg-blue-50 text-blue-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800'">
                                        <span x-text="importStep === 1 ? 'Step 1: Upload' : importStep === 2 ? 'Step 2: Validate' : 'Step 3: Commit'"></span>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
                                    <div class="flex items-center gap-4 text-sm">
                                        <div class="flex items-center gap-2" :class="importStep >= 1 ? 'text-violet-700' : 'text-slate-400'">
                                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full border font-semibold" :class="importStep >= 1 ? 'border-violet-600 bg-violet-50' : 'border-slate-300'">1</span>
                                            Upload
                                        </div>
                                        <div class="h-px flex-1 bg-slate-200"></div>
                                        <div class="flex items-center gap-2" :class="importStep >= 2 ? 'text-violet-700' : 'text-slate-400'">
                                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full border font-semibold" :class="importStep >= 2 ? 'border-violet-600 bg-violet-50' : 'border-slate-300'">2</span>
                                            Validate
                                        </div>
                                        <div class="h-px flex-1 bg-slate-200"></div>
                                        <div class="flex items-center gap-2" :class="importStep >= 3 ? 'text-violet-700' : 'text-slate-400'">
                                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full border font-semibold" :class="importStep >= 3 ? 'border-violet-600 bg-violet-50' : 'border-slate-300'">3</span>
                                            Commit
                                        </div>
                                    </div>
                                </div>

                                <div x-show="importStep === 1" class="space-y-5">
                                    <div class="rounded-3xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                                        <p class="font-semibold">Scope protection</p>
                                        <p class="mt-2 leading-7" x-text="importScopeMessage"></p>
                                    </div>

                                    <div class="grid gap-4 lg:grid-cols-2">
                                        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-[0_14px_28px_rgba(15,23,42,0.05)]">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Intake Scope</p>
                                            <div class="mt-4 grid gap-4 md:grid-cols-2">
                                                <div class="md:col-span-2">
                                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Exam Year</label>
                                                    <select x-model="examYear" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                                                        <option value="">Select year</option>
                                                        <template x-for="year in examYears" :key="year.id">
                                                            <option :value="year.year_label" x-text="year.year_label"></option>
                                                        </template>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Region</label>
                                                    <select x-model="selectedRegion" @change="onRegionChange()" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                                                        <option value="">All regions</option>
                                                        <template x-for="region in regions" :key="region.id">
                                                            <option :value="region.id" x-text="region.name"></option>
                                                        </template>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Council</label>
                                                    <select x-model="selectedDistrict" @change="onDistrictChange()" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                                                        <option value="">All councils</option>
                                                        <template x-for="district in filteredDistricts" :key="district.id">
                                                            <option :value="district.id" x-text="district.name"></option>
                                                        </template>
                                                    </select>
                                                </div>
                                                <div class="md:col-span-2">
                                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Primary School</label>
                                                    <select x-model="selectedSchool" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                                                        <option value="">Select primary school</option>
                                                        <template x-for="school in schools" :key="school.id">
                                                            <option :value="school.id" x-text="schoolDisplayLabel(school)"></option>
                                                        </template>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Resolved Intake Context</p>
                                            <dl class="mt-4 space-y-3 text-sm">
                                                <div class="flex items-start justify-between gap-4">
                                                    <dt class="font-semibold text-slate-600">Mode</dt>
                                                    <dd class="text-right font-semibold text-slate-900" x-text="importModeLabel"></dd>
                                                </div>
                                                <div class="flex items-start justify-between gap-4">
                                                    <dt class="font-semibold text-slate-600">Year</dt>
                                                    <dd class="text-right text-slate-900" x-text="examYear || '-'"></dd>
                                                </div>
                                                <div class="flex items-start justify-between gap-4">
                                                    <dt class="font-semibold text-slate-600">Region</dt>
                                                    <dd class="text-right text-slate-900" x-text="selectedRegionRecord?.name || 'All regions'"></dd>
                                                </div>
                                                <div class="flex items-start justify-between gap-4">
                                                    <dt class="font-semibold text-slate-600">Council</dt>
                                                    <dd class="text-right text-slate-900" x-text="selectedDistrictRecord?.name || 'All councils'"></dd>
                                                </div>
                                                <div class="flex items-start justify-between gap-4">
                                                    <dt class="font-semibold text-slate-600">Primary School</dt>
                                                    <dd class="text-right text-slate-900" x-text="selectedSchoolRecord?.name || '-'"></dd>
                                                </div>
                                            </dl>

                                            <div class="mt-5 rounded-2xl border px-4 py-3 text-sm" :class="isImportScopeReady ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-900'">
                                                <p class="font-semibold" x-text="isImportScopeReady ? 'Intake scope ready' : 'Scope still incomplete'"></p>
                                                <p class="mt-1 leading-6" x-text="importScopeMessage"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-[1.7rem] border-2 border-dashed border-slate-300 bg-slate-50 px-8 py-10 text-center transition hover:border-violet-400 hover:bg-violet-50/50">
                                        <input x-ref="importInlineFileInput" type="file" class="hidden" :accept="importAccept" @change="handleImportFileChange($event)">
                                        <button @click="triggerImportFilePicker('inline')" class="inline-flex h-16 w-16 items-center justify-center rounded-3xl bg-white text-2xl text-violet-600 shadow-[0_14px_28px_rgba(124,58,237,0.18)]">
                                            <i class="fas fa-cloud-arrow-up"></i>
                                        </button>
                                        <p class="mt-4 text-xl font-bold text-slate-900">Choose file for intake</p>
                                        <p class="mt-2 text-sm text-slate-600" x-text="importFormatHint"></p>
                                        <div x-show="importFile" class="mt-5 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left shadow-sm">
                                            <p class="text-sm font-semibold text-slate-900" x-text="importFile?.name"></p>
                                            <p class="mt-1 text-xs text-slate-500" x-text="formatFileSize(importFile?.size)"></p>
                                        </div>
                                    </div>

                                    <div class="flex justify-end gap-3">
                                        <button @click="validateImportFile()" :disabled="!importFile || importProcessing || !isImportScopeReady" class="rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-50">
                                            <span x-show="!importProcessing"><i class="fas fa-check mr-2"></i>Validate File</span>
                                            <span x-show="importProcessing"><i class="fas fa-spinner fa-spin mr-2"></i>Validating...</span>
                                        </button>
                                    </div>
                                </div>

                                <div x-show="importStep === 2" class="space-y-5">
                                    <div class="grid gap-4 md:grid-cols-4">
                                        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4">
                                            <p class="text-xs text-slate-600">Rows Found</p>
                                            <p class="mt-2 text-2xl font-black text-blue-700" x-text="importValidation.rows || 0"></p>
                                        </div>
                                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                                            <p class="text-xs text-slate-600">Valid Rows</p>
                                            <p class="mt-2 text-2xl font-black text-emerald-700" x-text="importValidation.validRows || 0"></p>
                                        </div>
                                        <div class="rounded-2xl border border-red-200 bg-red-50 p-4">
                                            <p class="text-xs text-slate-600">Errors</p>
                                            <p class="mt-2 text-2xl font-black text-red-700" x-text="importValidation.errors.length || 0"></p>
                                        </div>
                                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                            <p class="text-xs text-slate-600">Warnings</p>
                                            <p class="mt-2 text-2xl font-black text-amber-700" x-text="importValidation.warnings.length || 0"></p>
                                        </div>
                                    </div>

                                    <div class="rounded-3xl border border-slate-200 bg-white overflow-hidden" x-show="importValidation.preview.length">
                                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800">Preview</div>
                                        <div class="max-h-64 overflow-auto">
                                            <table class="w-full text-sm">
                                                <thead class="sticky top-0 bg-white">
                                                    <tr>
                                                        <th class="px-3 py-2 text-left">Candidate Number</th>
                                                        <th class="px-3 py-2 text-left">PReM No</th>
                                                        <th class="px-3 py-2 text-left">Sex</th>
                                                        <th class="px-3 py-2 text-left">Mark</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="row in importValidation.preview" :key="row.line">
                                                        <tr class="border-t border-slate-100">
                                                            <td class="px-3 py-2" x-text="row.candidate_number || '-'"></td>
                                                            <td class="px-3 py-2" x-text="row.prem_no || '-'"></td>
                                                            <td class="px-3 py-2" x-text="row.sex || '-'"></td>
                                                            <td class="px-3 py-2" x-text="row.mark || '-'"></td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="rounded-3xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-900" x-show="importValidation.errors.length">
                                        <p class="font-semibold">Validation errors</p>
                                        <div class="mt-2 space-y-1">
                                            <template x-for="issue in importValidation.errors" :key="issue">
                                                <p x-text="issue"></p>
                                            </template>
                                        </div>
                                    </div>

                                    <div class="rounded-3xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900" x-show="importValidation.warnings.length">
                                        <p class="font-semibold">Validation warnings</p>
                                        <div class="mt-2 space-y-1">
                                            <template x-for="issue in importValidation.warnings" :key="issue">
                                                <p x-text="issue"></p>
                                            </template>
                                        </div>
                                    </div>

                                    <div class="flex justify-between gap-3">
                                        <button @click="importStep = 1" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                            Back
                                        </button>
                                        <button @click="commitImportFile()" :disabled="importValidation.errors.length > 0 || importProcessing || !importValidation.can_commit" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                                            <span x-show="!importProcessing"><i class="fas fa-layer-group mr-2"></i>Commit To Intake</span>
                                            <span x-show="importProcessing"><i class="fas fa-spinner fa-spin mr-2"></i>Committing...</span>
                                        </button>
                                    </div>
                                </div>

                                <div x-show="importStep === 3" class="space-y-5">
                                    <div class="rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900">
                                        <p class="font-semibold">File committed successfully</p>
                                        <p class="mt-2 leading-7" x-text="importCommitResult.message || 'The uploaded file has been committed into the PSLE intake pipeline and recorded below.'"></p>
                                    </div>
                                    <div class="rounded-3xl border border-slate-200 bg-white px-5 py-4">
                                        <dl class="grid gap-4 md:grid-cols-2 text-sm">
                                            <div>
                                                <dt class="font-semibold text-slate-600">Mode</dt>
                                                <dd class="mt-1 text-slate-900" x-text="importModeLabel"></dd>
                                            </div>
                                            <div>
                                                <dt class="font-semibold text-slate-600">File</dt>
                                                <dd class="mt-1 text-slate-900" x-text="importFile?.name || '-'"></dd>
                                            </div>
                                            <div>
                                                <dt class="font-semibold text-slate-600">Rows</dt>
                                                <dd class="mt-1 text-slate-900" x-text="importCommitRowCount"></dd>
                                            </div>
                                            <div>
                                                <dt class="font-semibold text-slate-600">Scope</dt>
                                                <dd class="mt-1 text-slate-900" x-text="scopeSummary"></dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>

                </div>
            </section>

            <section x-show="activeTab === 'district'" class="space-y-6">
                <div class="grid gap-4">
                    <article class="registration-surface p-6">
                        <div class="mark-entry-step-heading">
                            <div>
                                <h2 class="text-lg font-bold text-gray-800">4. District Bulk ZIP Upload</h2>
                                <p class="mark-entry-step-copy">Prepare one governed district package per council, then validate and commit the bundled school files together.</p>
                            </div>
                            <div class="mark-entry-step-badges">
                                <span class="mark-entry-step-badge"><i class="fas fa-building"></i> Council package</span>
                                <span class="mark-entry-step-badge"><i class="fas fa-box-archive"></i> Multiple schools</span>
                                <span class="mark-entry-step-badge"><i class="fas fa-circle-check"></i> Validation first</span>
                            </div>
                        </div>

                        <div class="mark-entry-instruction-card p-4 mt-4">
                            <p class="text-sm text-blue-900 mb-2">
                                <strong>Important Instructions for District Bulk ZIP:</strong>
                            </p>
                            <ul class="text-sm text-blue-800 list-disc list-inside space-y-1">
                                <li><strong>Step 1:</strong> Confirm year, region, and council before preparing the district package.</li>
                                <li><strong>Step 2:</strong> Keep each school package distinct inside the district ZIP for easier reconciliation and error tracking.</li>
                                <li><strong>Step 3:</strong> Use only official PSLE subject templates and codes inside every included file.</li>
                                <li><strong>Step 4:</strong> Validate the district ZIP before committing it into the intake pipeline.</li>
                            </ul>
                        </div>

                        <div class="mark-entry-inline-tools mt-4 flex flex-wrap items-center gap-3">
                            <button @click="openImportModal('district_zip')" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                <i class="fas fa-upload mr-2"></i>Upload District ZIP
                            </button>
                            <div class="rounded-2xl border px-4 py-2.5 text-sm font-semibold" :class="selectedDistrict ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-900'">
                                <i :class="selectedDistrict ? 'fas fa-circle-check mr-2' : 'fas fa-clock mr-2'"></i>
                                <span x-text="selectedDistrict ? 'Ready to intake district ZIP' : 'Select council scope'"></span>
                            </div>
                            <button @click="exportScopedPupils()" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-100">
                                <i class="fas fa-file-export mr-2"></i>Export Scoped Pupils
                            </button>
                        </div>
                    </article>
                </div>
            </section>

            <section x-show="activeTab === 'review'" class="space-y-6">
                <div class="registration-surface p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Readiness And Review</p>
                            <h3 class="mt-3 text-2xl font-black text-slate-900">Scoped Pupil Review Table</h3>
                            <p class="mt-2 text-sm leading-7 text-slate-600">Review pupils before issuing templates or preparing school and council packages. This is the working roster that protects mark files from mismatched candidate identity and school scope.</p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                <span class="font-semibold">Loaded:</span>
                                <span x-text="candidates.length"></span>
                                <span class="text-slate-500">of</span>
                                <span x-text="totalCandidates"></span>
                            </div>
                            <button @click="exportScopedPupils()" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-100">
                                <i class="fas fa-file-export mr-2"></i>Export Current Data
                            </button>
                        </div>
                    </div>
                </div>

                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <article class="registration-surface p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Female</p>
                        <p class="mt-3 text-3xl font-black text-slate-900" x-text="genderBreakdown.female"></p>
                    </article>
                    <article class="registration-surface p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Male</p>
                        <p class="mt-3 text-3xl font-black text-slate-900" x-text="genderBreakdown.male"></p>
                    </article>
                    <article class="registration-surface p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">With PReM No</p>
                        <p class="mt-3 text-3xl font-black text-slate-900" x-text="genderBreakdown.withPrem"></p>
                    </article>
                    <article class="registration-surface p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Current Page</p>
                        <p class="mt-3 text-3xl font-black text-slate-900"><span x-text="candidatePage"></span>/<span x-text="candidateLastPage"></span></p>
                    </article>
                </section>

                <div class="registration-surface registration-toolbar-card">
                    <div class="registration-toolbar-grid">
                        <div class="flex flex-col flex-1 min-w-[260px]">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Search pupils</label>
                            <input x-model="candidateSearch" type="text" placeholder="Search by candidate number, PReM No, pupil name, or school..." class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="ml-auto flex items-end gap-2 self-end">
                            <button @click="loadCandidates(1)" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                                <i class="fas fa-rotate mr-2"></i>Reload
                            </button>
                        </div>
                    </div>
                </div>

                <div class="registration-surface registration-table-card overflow-x-auto">
                    <div x-show="loadingCandidates" class="p-8 text-center text-slate-500">
                        <i class="fas fa-spinner animate-spin text-2xl"></i>
                    </div>
                    <div x-show="!loadingCandidates" class="overflow-x-auto">
                        <table class="w-full min-w-[1080px]">
                            <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Candidate Number</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">PReM No</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Pupil Name</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Sex</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Primary School</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Council</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Subject Allocation</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="candidate in filteredCandidates" :key="candidate.id">
                                    <tr class="hover:bg-blue-50 transition-colors">
                                        <td class="px-3 py-1.5 text-[13px] font-mono font-semibold text-blue-700" x-text="candidate.candidate_id || '-'"></td>
                                        <td class="px-3 py-1.5 text-[13px] font-mono text-slate-600" x-text="candidate.prem_no || '-'"></td>
                                        <td class="px-3 py-1.5 text-[13px] font-medium text-slate-900" x-text="candidate.full_name || '-'"></td>
                                        <td class="px-3 py-1.5 text-center text-[13px] text-slate-600" x-text="candidate.gender || '-'"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-slate-600" x-text="candidate.school_name || '-'"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-slate-600" x-text="candidate.district_name || '-'"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-slate-600" x-text="subjectAllocationLabel(candidate)"></td>
                                    </tr>
                                </template>
                                <tr x-show="!loadingCandidates && filteredCandidates.length === 0">
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500">No pupils found for the current PSLE mark-entry scope or search term.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-slate-600">Showing <span class="font-semibold text-slate-900" x-text="filteredCandidates.length"></span> pupil(s) on this page.</p>
                        <div class="flex items-center gap-2">
                            <button @click="goToCandidatePage(1)" :disabled="candidatePage <= 1" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
                                First
                            </button>
                            <button @click="goToCandidatePage(candidatePage - 1)" :disabled="candidatePage <= 1" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
                                Previous
                            </button>
                            <span class="rounded-lg bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700">
                                Page <span x-text="candidatePage"></span> of <span x-text="candidateLastPage"></span>
                            </span>
                            <button @click="goToCandidatePage(candidatePage + 1)" :disabled="candidatePage >= candidateLastPage" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
                                Next
                            </button>
                            <button @click="goToCandidatePage(candidateLastPage)" :disabled="candidatePage >= candidateLastPage" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
                                Last
                            </button>
                        </div>
                    </div>
                </div>

                <div class="registration-surface registration-table-card overflow-x-auto">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <div>
                            <h4 class="text-lg font-black text-slate-900">Recent Intake Activity</h4>
                            <p class="mt-1 text-sm text-slate-600">PSLE batches committed through this workspace are listed here for immediate operator review.</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[900px]">
                            <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300">
                                <tr>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Mode</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">File</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Scope</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Rows</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Status</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="entry in recentBatches" :key="entry.id">
                                    <tr class="hover:bg-blue-50 transition-colors">
                                        <td class="px-3 py-1.5 text-[13px] font-semibold text-slate-900" x-text="entry.modeLabel"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-slate-600" x-text="entry.fileName"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-slate-600" x-text="entry.scope"></td>
                                        <td class="px-3 py-1.5 text-center text-[13px] font-semibold text-slate-900" x-text="entry.rows"></td>
                                        <td class="px-3 py-1.5 text-center">
                                            <span class="rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em]" :class="entry.status === 'validated' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800'" x-text="entry.status"></span>
                                        </td>
                                        <td class="px-3 py-1.5 text-[13px] text-slate-600" x-text="entry.time"></td>
                                    </tr>
                                </template>
                                <tr x-show="recentBatches.length === 0">
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500">No PSLE batches have been committed yet from this workspace.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section x-show="activeTab === 'moderation'" class="space-y-6">
                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <template x-for="card in moderationSummaryCards" :key="card.label">
                        <article class="rounded-[1.6rem] border p-5" :class="card.tone">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em]" x-text="card.label"></p>
                            <p class="mt-3 text-3xl font-black" x-text="card.value"></p>
                        </article>
                    </template>
                </section>

                <div class="registration-surface registration-table-card overflow-x-auto">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <div>
                            <h4 class="text-lg font-black text-slate-900">Moderation Queue</h4>
                            <p class="mt-1 text-sm text-slate-600">Move validated batches into review, then approve or reject them with clear operational notes.</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[1240px]">
                            <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300">
                                <tr>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Batch</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Scope</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Subject</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Rows</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Status</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Review Note</th>
                                    <th class="px-3 py-2.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="batch in lifecycleDashboard.batches" :key="batch.id">
                                    <tr class="hover:bg-blue-50 transition-colors">
                                        <td class="px-3 py-2 text-[13px] font-semibold text-slate-900" x-text="batch.batch_code"></td>
                                        <td class="px-3 py-2 text-[13px] text-slate-600" x-text="batch.scope"></td>
                                        <td class="px-3 py-2 text-[13px] text-slate-600" x-text="batch.subject_code + ' - ' + batch.subject_name"></td>
                                        <td class="px-3 py-2 text-center text-[13px] font-semibold text-slate-900" x-text="batch.rows"></td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em]" :class="statusPillClass(batch.status)" x-text="batch.status"></span>
                                        </td>
                                        <td class="px-3 py-2 text-[13px] text-slate-600" x-text="batch.review_feedback || batch.rejection_reason || '-'"></td>
                                        <td class="px-3 py-2">
                                            <div class="flex justify-end gap-2">
                                                <button x-show="canSubmitBatch(batch)" @click="openBatchActionModal('submit', batch)" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white">Submit</button>
                                                <button x-show="canApproveBatch(batch)" @click="openBatchActionModal('approve', batch)" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white">Approve</button>
                                                <button x-show="canRejectBatch(batch)" @click="openBatchActionModal('reject', batch)" class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white">Reject</button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="!lifecycleDashboard.batches.length">
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500">No PSLE batches are available in the current moderation scope.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section x-show="activeTab === 'locking'" class="space-y-6">
                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <template x-for="card in lockingSummaryCards" :key="card.label">
                        <article class="rounded-[1.6rem] border p-5" :class="card.tone">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em]" x-text="card.label"></p>
                            <p class="mt-3 text-3xl font-black" x-text="card.value"></p>
                        </article>
                    </template>
                </section>

                <div class="registration-surface registration-table-card overflow-x-auto">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <div>
                            <h4 class="text-lg font-black text-slate-900">Submission Locking Board</h4>
                            <p class="mt-1 text-sm text-slate-600">Only approved PSLE batches should move into locking. Locked batches represent the controlled final intake state.</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[1180px]">
                            <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300">
                                <tr>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Batch</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">School</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Council</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Subject</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Status</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Locked At</th>
                                    <th class="px-3 py-2.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="batch in lifecycleDashboard.batches" :key="'lock-' + batch.id">
                                    <tr class="hover:bg-blue-50 transition-colors">
                                        <td class="px-3 py-2 text-[13px] font-semibold text-slate-900" x-text="batch.batch_code"></td>
                                        <td class="px-3 py-2 text-[13px] text-slate-600" x-text="batch.school_name || '-'"></td>
                                        <td class="px-3 py-2 text-[13px] text-slate-600" x-text="batch.district_name || '-'"></td>
                                        <td class="px-3 py-2 text-[13px] text-slate-600" x-text="batch.subject_code"></td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em]" :class="statusPillClass(batch.status)" x-text="batch.status"></span>
                                        </td>
                                        <td class="px-3 py-2 text-[13px] text-slate-600" x-text="batch.locked_at || '-'"></td>
                                        <td class="px-3 py-2">
                                            <div class="flex justify-end gap-2">
                                                <button x-show="canLockBatch(batch)" @click="openBatchActionModal('lock', batch)" class="rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white">Lock</button>
                                                <button x-show="canUnlockBatch(batch)" @click="openBatchActionModal('unlock', batch)" class="rounded-lg bg-slate-700 px-3 py-1.5 text-xs font-semibold text-white">Unlock</button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="!lifecycleDashboard.batches.length">
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500">No PSLE batches are available in the current locking scope.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section x-show="activeTab === 'reports'" class="space-y-6">
                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <article class="registration-surface p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Batches</p>
                        <p class="mt-3 text-3xl font-black text-slate-900" x-text="reportsDashboard.summary?.batch_count || 0"></p>
                    </article>
                    <article class="registration-surface p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Rows</p>
                        <p class="mt-3 text-3xl font-black text-slate-900" x-text="reportsDashboard.summary?.row_count || 0"></p>
                    </article>
                    <article class="registration-surface p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Locked</p>
                        <p class="mt-3 text-3xl font-black text-slate-900" x-text="reportsDashboard.summary?.locked_count || 0"></p>
                    </article>
                    <article class="registration-surface p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Warnings</p>
                        <p class="mt-3 text-3xl font-black text-slate-900" x-text="reportsDashboard.summary?.warning_count || 0"></p>
                    </article>
                </section>

                <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                    <div class="registration-surface p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="max-w-3xl">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Blank Marking Scoresheets</p>
                                <h4 class="mt-2 text-xl font-black text-slate-900">Professional PSLE Scoresheet Export</h4>
                                <p class="mt-2 text-sm leading-7 text-slate-600">Generate blank PSLE marking scoresheets for handwritten use before CSV entry begins. You can export a single subject sheet for the current school, or governed ZIP packages for the current school, council, or region using the scope already selected at the top of this workspace.</p>
                            </div>
                            <div class="inline-flex rounded-2xl border border-slate-200 bg-slate-50 p-1">
                                <button @click="scoresheetMode = 'approved'; loadScoresheetSubjects()" :class="scoresheetMode === 'approved' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600'" class="rounded-xl px-4 py-2 text-sm font-semibold transition">Official Print</button>
                                <button @click="scoresheetMode = 'all'; loadScoresheetSubjects()" :class="scoresheetMode === 'all' ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-600'" class="rounded-xl px-4 py-2 text-sm font-semibold transition">Preview Copy</button>
                            </div>
                        </div>

                        <div x-show="scoresheetMode === 'all'" class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            Preview copy uses the same candidate roster but is labeled for internal verification rather than operational printing.
                        </div>

                        <div class="mt-5 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Print Layout</p>
                                <p class="mt-1 text-sm text-slate-600">Use formal archive for filing, sign-off, and supervisory packs. Use condensed print to fit more candidate rows per page during live marking.</p>
                            </div>
                            <div class="inline-flex rounded-2xl border border-slate-200 bg-white p-1">
                                <button @click="scoresheetLayout = 'formal'" :class="scoresheetLayout === 'formal' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600'" class="rounded-xl px-4 py-2 text-sm font-semibold transition">Formal Archive</button>
                                <button @click="scoresheetLayout = 'condensed'" :class="scoresheetLayout === 'condensed' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600'" class="rounded-xl px-4 py-2 text-sm font-semibold transition">Condensed Print</button>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4 lg:grid-cols-2">
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Current Export Scope</p>
                                <dl class="mt-4 space-y-3 text-sm">
                                    <div class="flex items-start justify-between gap-4">
                                        <dt class="font-semibold text-slate-600">Exam Year</dt>
                                        <dd class="text-right text-slate-900" x-text="examYear || '-'"></dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-4">
                                        <dt class="font-semibold text-slate-600">Region</dt>
                                        <dd class="text-right text-slate-900" x-text="selectedRegionRecord?.name || 'All regions'"></dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-4">
                                        <dt class="font-semibold text-slate-600">Council</dt>
                                        <dd class="text-right text-slate-900" x-text="selectedDistrictRecord?.name || 'All councils'"></dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-4">
                                        <dt class="font-semibold text-slate-600">Primary School</dt>
                                        <dd class="text-right text-slate-900" x-text="selectedSchoolRecord?.name || '-'"></dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-4">
                                        <dt class="font-semibold text-slate-600">Subject</dt>
                                        <dd class="text-right text-slate-900" x-text="selectedSubjectRecord ? (selectedSubjectRecord.code + ' - ' + selectedSubjectRecord.name) : '-'"></dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="rounded-3xl border border-blue-200 bg-blue-50 p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Scoresheet Subjects</p>
                                        <p class="mt-2 text-sm leading-7 text-blue-900">The current school has <span class="font-bold" x-text="scoresheetSubjects.length"></span> subject roster(s) available for blank scoresheet printing.</p>
                                    </div>
                                    <button @click="loadScoresheetSubjects()" class="rounded-xl border border-blue-200 bg-white px-3 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-100">
                                        Refresh
                                    </button>
                                </div>
                                <div class="mt-4 max-h-48 space-y-2 overflow-y-auto pr-1">
                                    <template x-for="subject in scoresheetSubjects" :key="subject.id">
                                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-blue-100 bg-white px-4 py-3">
                                            <div>
                                                <p class="text-sm font-semibold text-slate-900" x-text="subject.code + ' - ' + subject.name"></p>
                                                <p class="mt-1 text-xs text-slate-500" x-text="subject.subject_group_label || subject.paper_pattern_label || 'PSLE governed subject'"></p>
                                            </div>
                                            <button @click="selectedSubject = String(subject.id)" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
                                                Use Subject
                                            </button>
                                        </div>
                                    </template>
                                    <div x-show="!scoresheetSubjects.length" class="rounded-2xl border border-dashed border-blue-200 bg-white px-4 py-6 text-sm text-slate-600">
                                        No PSLE subject rosters are available for the current school in the selected exam year.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-3">
                            <button @click="downloadScoresheet('single')" :disabled="!examYear || !selectedSchool || !selectedSubject || scoresheetExporting || !selectedScoresheetSubjectRecord" class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                                <i :class="scoresheetExporting ? 'fas fa-spinner animate-spin mr-2' : 'fas fa-file-pdf mr-2'"></i>Single PDF
                            </button>
                            <button @click="downloadScoresheet('school')" :disabled="!examYear || !selectedSchool || scoresheetExporting" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                                <i :class="scoresheetExporting ? 'fas fa-spinner animate-spin mr-2' : 'fas fa-box-archive mr-2'"></i>School ZIP
                            </button>
                            <button @click="downloadScoresheet('district')" :disabled="!examYear || !selectedDistrict || scoresheetExporting" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                                <i :class="scoresheetExporting ? 'fas fa-spinner animate-spin mr-2' : 'fas fa-building-columns mr-2'"></i>Council ZIP
                            </button>
                            <button @click="downloadScoresheet('region')" :disabled="!examYear || !selectedRegion || scoresheetExporting" class="rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:bg-slate-300">
                                <i :class="scoresheetExporting ? 'fas fa-spinner animate-spin mr-2' : 'fas fa-earth-africa mr-2'"></i>Region ZIP
                            </button>
                        </div>

                        <div class="mt-5 rounded-3xl border border-emerald-200 bg-emerald-50 p-5">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div class="max-w-3xl">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Entered Marks Sheets</p>
                                    <p class="mt-2 text-sm leading-7 text-emerald-900">Use this export after CSV entry to print the entered-mark verification sheet. It excludes pupil names and shows candidate number, PReM No, sex, entered mark, batch code, and verification fields. Single and school exports use approved or locked data for internal verification, while council and region packages are restricted to locked data.</p>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-3">
                                <button @click="downloadEnteredMarksSheet('single')" :disabled="!examYear || !selectedSchool || !selectedSubject || scoresheetExporting" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:bg-slate-300">
                                    <i :class="scoresheetExporting ? 'fas fa-spinner animate-spin mr-2' : 'fas fa-file-lines mr-2'"></i>Entered Sheet PDF
                                </button>
                                <button @click="downloadEnteredMarksSheet('school')" :disabled="!examYear || !selectedSchool || scoresheetExporting" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                                    <i :class="scoresheetExporting ? 'fas fa-spinner animate-spin mr-2' : 'fas fa-box-archive mr-2'"></i>Entered School ZIP
                                </button>
                                <button @click="downloadEnteredMarksSheet('district')" :disabled="!examYear || !selectedDistrict || scoresheetExporting" class="rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                                    <i :class="scoresheetExporting ? 'fas fa-spinner animate-spin mr-2' : 'fas fa-building-columns mr-2'"></i>Entered Council ZIP
                                </button>
                                <button @click="downloadEnteredMarksSheet('region')" :disabled="!examYear || !selectedRegion || scoresheetExporting" class="rounded-xl bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-cyan-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                                    <i :class="scoresheetExporting ? 'fas fa-spinner animate-spin mr-2' : 'fas fa-earth-africa mr-2'"></i>Entered Region ZIP
                                </button>
                            </div>
                        </div>

                        <div x-show="scoresheetMessage" class="mt-4 rounded-2xl border px-4 py-3 text-sm" :class="scoresheetMessageType === 'error' ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900'" x-text="scoresheetMessage"></div>
                    </div>

                    <div class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Export Guidance</p>
                        <div class="mt-4 space-y-3">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p class="text-sm font-semibold text-slate-900">Recommended operating mode</p>
                                <p class="mt-1 text-sm text-slate-600">Use `Official Print` for the sheets that will go into the marking room. Reserve `Preview Copy` for internal roster verification before printing the operational pack.</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p class="text-sm font-semibold text-slate-900">Professional control</p>
                                <p class="mt-1 text-sm text-slate-600">Keep the current subject selected before exporting a single PDF. That avoids accidental mismatch between the visible candidate roster and the printed marking sheet.</p>
                            </div>
                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                                <p class="text-sm font-semibold text-emerald-900">Entered marks control</p>
                                <p class="mt-1 text-sm text-emerald-800">Entered marks sheets are controlled by export level: single and school sheets may use approved batches for internal verification, but council and region packages are limited to locked PSLE batches. Draft and validation-stage intake should be reviewed on screen, not printed.</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p class="text-sm font-semibold text-slate-900">Bulk packaging</p>
                                <p class="mt-1 text-sm text-slate-600">Use school ZIP for direct handoff to a head teacher, council ZIP for district quality control, and region ZIP only when you need one consolidated supervisory package.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="registration-surface p-6">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Operational Reports</p>
                            <h4 class="mt-2 text-xl font-black text-slate-900">PSLE Reporting and Export Surface</h4>
                            <p class="mt-2 text-sm text-slate-600">Use the scoped summary tables below for operational review, then export the current PSLE mark-entry report as CSV.</p>
                        </div>
                        <button @click="exportReportCsv()" class="whitespace-nowrap rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white">
                            <i class="fas fa-file-export mr-2"></i>Export Current Report
                        </button>
                    </div>
                </div>

                <div class="grid gap-6 xl:grid-cols-3">
                    <div class="registration-surface registration-table-card overflow-x-auto xl:col-span-1">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <h4 class="text-lg font-black text-slate-900">Status Summary</h4>
                        </div>
                        <table class="w-full min-w-[320px]">
                            <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300">
                                <tr>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Status</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Batches</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Rows</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="row in reportsDashboard.status_rows" :key="row.status">
                                    <tr>
                                        <td class="px-3 py-2 text-[13px] font-semibold text-slate-900" x-text="row.label"></td>
                                        <td class="px-3 py-2 text-center text-[13px] text-slate-600" x-text="row.batch_count"></td>
                                        <td class="px-3 py-2 text-center text-[13px] text-slate-600" x-text="row.rows"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="registration-surface registration-table-card overflow-x-auto xl:col-span-2">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <h4 class="text-lg font-black text-slate-900">Subject Breakdown</h4>
                        </div>
                        <table class="w-full min-w-[640px]">
                            <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300">
                                <tr>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Subject</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Batches</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Rows</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Locked</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="row in reportsDashboard.subject_rows" :key="row.subject_code">
                                    <tr>
                                        <td class="px-3 py-2 text-[13px] text-slate-700" x-text="row.subject_code + ' - ' + row.subject_name"></td>
                                        <td class="px-3 py-2 text-center text-[13px] text-slate-600" x-text="row.batches"></td>
                                        <td class="px-3 py-2 text-center text-[13px] text-slate-600" x-text="row.rows"></td>
                                        <td class="px-3 py-2 text-center text-[13px] text-slate-600" x-text="row.locked"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="registration-surface registration-table-card overflow-x-auto">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h4 class="text-lg font-black text-slate-900">School Submission Coverage</h4>
                    </div>
                    <table class="w-full min-w-[920px]">
                        <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300">
                            <tr>
                                <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Primary School</th>
                                <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Council</th>
                                <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Batches</th>
                                <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Rows</th>
                                <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Locked</th>
                                <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Pending</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="row in reportsDashboard.school_rows" :key="row.school_code + row.school_name">
                                <tr>
                                    <td class="px-3 py-2 text-[13px] text-slate-700" x-text="row.school_code + ' - ' + row.school_name"></td>
                                    <td class="px-3 py-2 text-[13px] text-slate-600" x-text="row.council_name"></td>
                                    <td class="px-3 py-2 text-center text-[13px] text-slate-600" x-text="row.batches"></td>
                                    <td class="px-3 py-2 text-center text-[13px] text-slate-600" x-text="row.rows"></td>
                                    <td class="px-3 py-2 text-center text-[13px] text-slate-600" x-text="row.locked"></td>
                                    <td class="px-3 py-2 text-center text-[13px] text-slate-600" x-text="row.pending"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </section>

            <section x-show="activeTab === 'audit'" class="space-y-6">
                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <article class="registration-surface p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Timeline Events</p>
                        <p class="mt-3 text-3xl font-black text-slate-900" x-text="auditDashboard.summary?.events || 0"></p>
                    </article>
                    <article class="registration-surface p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Reviews</p>
                        <p class="mt-3 text-3xl font-black text-slate-900" x-text="auditDashboard.summary?.reviews || 0"></p>
                    </article>
                    <article class="registration-surface p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Imports</p>
                        <p class="mt-3 text-3xl font-black text-slate-900" x-text="auditDashboard.summary?.imports || 0"></p>
                    </article>
                    <article class="registration-surface p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Locked Batches</p>
                        <p class="mt-3 text-3xl font-black text-slate-900" x-text="auditDashboard.summary?.locked || 0"></p>
                    </article>
                </section>

                <div class="registration-surface registration-table-card overflow-x-auto">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h4 class="text-lg font-black text-slate-900">Audit Timeline</h4>
                        <p class="mt-1 text-sm text-slate-600">Imports, moderation decisions, locking actions, and exports are kept visible in one scoped PSLE activity feed.</p>
                    </div>
                    <table class="w-full min-w-[960px]">
                        <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300">
                            <tr>
                                <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Time</th>
                                <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Batch</th>
                                <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Action</th>
                                <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Actor</th>
                                <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Message</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="entry in auditDashboard.timeline" :key="entry.time + entry.batch_code + entry.action">
                                <tr>
                                    <td class="px-3 py-2 text-[13px] text-slate-600" x-text="entry.time || '-'"></td>
                                    <td class="px-3 py-2 text-[13px] font-semibold text-slate-900" x-text="entry.batch_code || '-'"></td>
                                    <td class="px-3 py-2 text-[13px] text-slate-700" x-text="entry.action || '-'"></td>
                                    <td class="px-3 py-2 text-[13px] text-slate-600" x-text="entry.actor || '-'"></td>
                                    <td class="px-3 py-2 text-[13px] text-slate-600" x-text="entry.message || '-'"></td>
                                </tr>
                            </template>
                            <tr x-show="!auditDashboard.timeline.length">
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">No scoped PSLE audit activity is available yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section x-show="activeTab === 'admin'" class="space-y-6">
                <div class="grid gap-6 xl:grid-cols-2">
                    <div class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Administration Snapshot</p>
                        <h4 class="mt-3 text-xl font-black text-slate-900">PSLE Operational Configuration</h4>
                        <div class="mt-5 space-y-3">
                            <template x-for="item in administrationDashboard.settings" :key="item.label">
                                <div class="flex items-start justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <span class="text-sm font-semibold text-slate-700" x-text="item.label"></span>
                                    <span class="text-sm text-right text-slate-900" x-text="item.value"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Governance Rules</p>
                        <h4 class="mt-3 text-xl font-black text-slate-900">Control Model</h4>
                        <div class="mt-5 space-y-3">
                            <template x-for="item in administrationDashboard.governance" :key="item.label">
                                <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3">
                                    <p class="text-sm font-semibold text-blue-900" x-text="item.label"></p>
                                    <p class="mt-1 text-sm text-blue-800" x-text="item.value"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </section>

            <div x-show="batchActionModalOpen" x-transition.opacity class="fixed inset-0 z-[1200] flex items-start justify-center overflow-y-auto bg-slate-950/45 px-4 pb-8 pt-[9.5rem]" x-cloak>
                <div @click.outside="closeBatchActionModal()" class="my-auto w-full max-w-2xl overflow-hidden rounded-[2rem] border border-white/40 bg-white shadow-[0_40px_100px_rgba(15,23,42,0.28)]">
                    <div class="bg-[linear-gradient(135deg,#0f172a_0%,#1e3a8a_48%,#0f766e_100%)] px-8 py-7 text-white">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.22em]">
                                    <i class="fas fa-diagram-successor"></i>
                                    Batch Control Action
                                </span>
                                <h3 class="mt-5 text-3xl font-black" x-text="batchActionTitle"></h3>
                                <p class="mt-3 max-w-2xl text-sm leading-7 text-white/85" x-text="batchActionPrompt"></p>
                            </div>
                            <button @click="closeBatchActionModal()" class="inline-flex h-12 w-12 items-center justify-center rounded-full border border-white/20 bg-white/10 text-lg text-white transition hover:bg-white/20">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="space-y-5 px-8 py-7">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                            <dl class="space-y-3 text-sm">
                                <div class="flex justify-between gap-4">
                                    <dt class="font-semibold text-slate-600">Batch</dt>
                                    <dd class="text-right text-slate-900" x-text="batchActionTarget?.batch_code || '-'"></dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="font-semibold text-slate-600">Scope</dt>
                                    <dd class="text-right text-slate-900" x-text="batchActionTarget?.scope || '-'"></dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="font-semibold text-slate-600">Current Status</dt>
                                    <dd class="text-right text-slate-900" x-text="batchActionTarget?.status || '-'"></dd>
                                </div>
                            </dl>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                <span x-text="batchActionNoteRequired ? 'Required Note' : 'Operational Note'"></span>
                            </label>
                            <textarea x-model="batchActionNote" rows="4" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" :placeholder="batchActionNoteRequired ? 'Provide a clear reason for this PSLE batch action...' : 'Optional approval or control note...'"></textarea>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button @click="closeBatchActionModal()" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</button>
                            <button @click="submitBatchAction()" :disabled="batchActionProcessing" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50">
                                <span x-show="!batchActionProcessing" x-text="batchActionLabel"></span>
                                <span x-show="batchActionProcessing"><i class="fas fa-spinner fa-spin mr-2"></i>Processing...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="importModalOpen" x-transition.opacity class="fixed inset-0 z-[1200] flex items-start justify-center overflow-y-auto bg-slate-950/45 px-4 pb-8 pt-[9.5rem]" x-cloak>
                <div @click.outside="closeImportModal()" class="my-auto w-full max-w-5xl overflow-hidden rounded-[2rem] border border-white/40 bg-white shadow-[0_40px_100px_rgba(15,23,42,0.28)]">
                    <div class="bg-[linear-gradient(135deg,#1d4ed8_0%,#1e3a8a_48%,#0f766e_100%)] px-8 py-7 text-white">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.22em]">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span x-text="importModeBadge"></span>
                                </span>
                                <h3 class="mt-5 text-3xl font-black" x-text="importModalTitle"></h3>
                                <p class="mt-3 max-w-3xl text-sm leading-7 text-white/85" x-text="importModalDescription"></p>
                            </div>
                            <button @click="closeImportModal()" class="inline-flex h-12 w-12 items-center justify-center rounded-full border border-white/20 bg-white/10 text-lg text-white transition hover:bg-white/20">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="space-y-6 px-8 py-7">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
                            <div class="flex items-center gap-4 text-sm">
                                <div class="flex items-center gap-2" :class="importStep >= 1 ? 'text-blue-600' : 'text-slate-400'">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full border font-semibold" :class="importStep >= 1 ? 'border-blue-600 bg-blue-50' : 'border-slate-300'">1</span>
                                    Upload
                                </div>
                                <div class="h-px flex-1 bg-slate-200"></div>
                                <div class="flex items-center gap-2" :class="importStep >= 2 ? 'text-blue-600' : 'text-slate-400'">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full border font-semibold" :class="importStep >= 2 ? 'border-blue-600 bg-blue-50' : 'border-slate-300'">2</span>
                                    Validate
                                </div>
                                <div class="h-px flex-1 bg-slate-200"></div>
                                <div class="flex items-center gap-2" :class="importStep >= 3 ? 'text-blue-600' : 'text-slate-400'">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full border font-semibold" :class="importStep >= 3 ? 'border-blue-600 bg-blue-50' : 'border-slate-300'">3</span>
                                    Commit
                                </div>
                            </div>
                        </div>

                        <div x-show="importStep === 1" class="space-y-5">
                            <div class="rounded-3xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                                <p class="font-semibold">Scope protection</p>
                                <p class="mt-2 leading-7" x-text="importScopeMessage"></p>
                            </div>

                            <div x-show="importMode !== 'single_csv' || true">
                                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-[0_14px_28px_rgba(15,23,42,0.05)]">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Intake Scope</p>
                                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                                        <div class="md:col-span-2">
                                            <label class="mb-2 block text-sm font-semibold text-slate-700">Exam Year</label>
                                            <select x-model="examYear" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <option value="">Select year</option>
                                                <template x-for="year in examYears" :key="year.id">
                                                    <option :value="year.year_label" x-text="year.year_label"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-slate-700">Region</label>
                                            <select x-model="selectedRegion" @change="onRegionChange()" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <option value="">All regions</option>
                                                <template x-for="region in regions" :key="region.id">
                                                    <option :value="region.id" x-text="region.name"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-slate-700">Council</label>
                                            <select x-model="selectedDistrict" @change="onDistrictChange()" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <option value="">All councils</option>
                                                <template x-for="district in filteredDistricts" :key="district.id">
                                                    <option :value="district.id" x-text="district.name"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="md:col-span-2" x-show="importMode !== 'district_zip'">
                                            <label class="mb-2 block text-sm font-semibold text-slate-700">Primary School</label>
                                            <select x-model="selectedSchool" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <option value="">Select primary school</option>
                                                <template x-for="school in schools" :key="school.id">
                                                    <option :value="school.id" x-text="schoolDisplayLabel(school)"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="md:col-span-2" x-show="importMode === 'single_csv'">
                                            <label class="mb-2 block text-sm font-semibold text-slate-700">Subject</label>
                                            <select x-model="selectedSubject" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <option value="">Select subject</option>
                                                <template x-for="subject in subjects" :key="subject.id">
                                                    <option :value="subject.id" x-text="subject.code + ' - ' + subject.name"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-[1.7rem] border-2 border-dashed border-slate-300 bg-slate-50 px-8 py-10 text-center transition hover:border-blue-400 hover:bg-blue-50/50">
                                <input x-ref="importModalFileInput" type="file" class="hidden" :accept="importAccept" @change="handleImportFileChange($event)">
                                <button @click="triggerImportFilePicker('modal')" class="inline-flex h-16 w-16 items-center justify-center rounded-3xl bg-white text-2xl text-blue-600 shadow-[0_14px_28px_rgba(37,99,235,0.16)]">
                                    <i class="fas fa-cloud-arrow-up"></i>
                                </button>
                                <p class="mt-4 text-xl font-bold text-slate-900">Choose file for intake</p>
                                <p class="mt-2 text-sm text-slate-600" x-text="importFormatHint"></p>
                                <div x-show="importFile" class="mt-5 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left shadow-sm">
                                    <p class="text-sm font-semibold text-slate-900" x-text="importFile?.name"></p>
                                    <p class="mt-1 text-xs text-slate-500" x-text="formatFileSize(importFile?.size)"></p>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3">
                                <button @click="closeImportModal()" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                    Cancel
                                </button>
                                <button @click="validateImportFile()" :disabled="!importFile || importProcessing || !isImportScopeReady" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                                    <span x-show="!importProcessing"><i class="fas fa-check mr-2"></i>Validate File</span>
                                    <span x-show="importProcessing"><i class="fas fa-spinner fa-spin mr-2"></i>Validating...</span>
                                </button>
                            </div>
                        </div>

                        <div x-show="importStep === 2" class="space-y-5">
                            <div class="grid gap-4 md:grid-cols-4">
                                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4">
                                    <p class="text-xs text-slate-600">Rows Found</p>
                                    <p class="mt-2 text-2xl font-black text-blue-700" x-text="importValidation.rows || 0"></p>
                                </div>
                                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                                    <p class="text-xs text-slate-600">Valid Rows</p>
                                    <p class="mt-2 text-2xl font-black text-emerald-700" x-text="importValidation.validRows || 0"></p>
                                </div>
                                <div class="rounded-2xl border border-red-200 bg-red-50 p-4">
                                    <p class="text-xs text-slate-600">Errors</p>
                                    <p class="mt-2 text-2xl font-black text-red-700" x-text="importValidation.errors.length || 0"></p>
                                </div>
                                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                    <p class="text-xs text-slate-600">Warnings</p>
                                    <p class="mt-2 text-2xl font-black text-amber-700" x-text="importValidation.warnings.length || 0"></p>
                                </div>
                            </div>

                            <div class="rounded-3xl border border-slate-200 bg-white overflow-hidden" x-show="importValidation.preview.length">
                                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800">Preview</div>
                                <div class="max-h-64 overflow-auto">
                                    <table class="w-full text-sm">
                                        <thead class="sticky top-0 bg-white">
                                            <tr>
                                                <th class="px-3 py-2 text-left">Candidate Number</th>
                                                <th class="px-3 py-2 text-left">PReM No</th>
                                                <th class="px-3 py-2 text-left">Sex</th>
                                                <th class="px-3 py-2 text-left">Mark</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="row in importValidation.preview" :key="row.line">
                                                <tr class="border-t border-slate-100">
                                                    <td class="px-3 py-2" x-text="row.candidate_number || '-'"></td>
                                                    <td class="px-3 py-2" x-text="row.prem_no || '-'"></td>
                                                    <td class="px-3 py-2" x-text="row.sex || '-'"></td>
                                                    <td class="px-3 py-2" x-text="row.mark || '-'"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="rounded-3xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-900" x-show="importValidation.errors.length">
                                <p class="font-semibold">Validation errors</p>
                                <div class="mt-2 space-y-1">
                                    <template x-for="issue in importValidation.errors" :key="issue">
                                        <p x-text="issue"></p>
                                    </template>
                                </div>
                            </div>

                            <div class="rounded-3xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900" x-show="importValidation.warnings.length">
                                <p class="font-semibold">Validation warnings</p>
                                <div class="mt-2 space-y-1">
                                    <template x-for="issue in importValidation.warnings" :key="issue">
                                        <p x-text="issue"></p>
                                    </template>
                                </div>
                            </div>

                            <div class="flex justify-between gap-3">
                                <button @click="importStep = 1" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                    Back
                                </button>
                                <button @click="commitImportFile()" :disabled="importValidation.errors.length > 0 || importProcessing || !importValidation.can_commit" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                                    <span x-show="!importProcessing"><i class="fas fa-layer-group mr-2"></i>Commit To Intake</span>
                                    <span x-show="importProcessing"><i class="fas fa-spinner fa-spin mr-2"></i>Committing...</span>
                                </button>
                            </div>
                        </div>

                        <div x-show="importStep === 3" class="space-y-5">
                            <div class="rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900">
                                <p class="font-semibold">File committed successfully</p>
                                <p class="mt-2 leading-7" x-text="importCommitResult.message || 'The uploaded file has been committed into the PSLE intake pipeline and recorded below.'"></p>
                            </div>
                            <div class="rounded-3xl border border-slate-200 bg-white px-5 py-4">
                                <dl class="grid gap-4 md:grid-cols-2 text-sm">
                                    <div>
                                        <dt class="font-semibold text-slate-600">Mode</dt>
                                        <dd class="mt-1 text-slate-900" x-text="importModeLabel"></dd>
                                    </div>
                                    <div>
                                        <dt class="font-semibold text-slate-600">File</dt>
                                        <dd class="mt-1 text-slate-900" x-text="importFile?.name || '-'"></dd>
                                    </div>
                                    <div>
                                        <dt class="font-semibold text-slate-600">Rows</dt>
                                        <dd class="mt-1 text-slate-900" x-text="importCommitRowCount"></dd>
                                    </div>
                                    <div>
                                        <dt class="font-semibold text-slate-600">Scope</dt>
                                        <dd class="mt-1 text-slate-900" x-text="scopeSummary"></dd>
                                    </div>
                                </dl>
                            </div>
                            <div class="flex justify-end">
                                <button @click="closeImportModal()" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.mark-entry-sidebar {
    background: linear-gradient(180deg, rgba(8, 20, 43, 0.98) 0%, rgba(9, 27, 53, 0.98) 54%, rgba(9, 33, 57, 0.98) 100%);
    border-right: 1px solid rgba(148, 163, 184, 0.18);
    box-shadow: inset -1px 0 0 rgba(255, 255, 255, 0.04);
}

.mark-entry-sidebar-inner {
    min-height: calc(100vh - 140px);
}

.mark-entry-sidebar h2 {
    font-size: 1.3rem;
    letter-spacing: -0.03em;
}

.mark-entry-sidebar .sidebar-link {
    border-radius: 16px;
    padding: 0.72rem 0.9rem;
    line-height: 1.35;
    border: 1px solid transparent;
}

.mark-entry-sidebar-link-inner {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    width: 100%;
}

.mark-entry-sidebar-icon {
    width: 1.05rem;
    text-align: center;
    font-size: 0.95rem;
    color: rgba(255, 255, 255, 0.78);
    transition: color 160ms ease;
}

.mark-entry-sidebar .sidebar-link:hover {
    background: rgba(51, 65, 85, 0.55);
    border-color: rgba(96, 165, 250, 0.18);
}

.mark-entry-sidebar .sidebar-link:hover .mark-entry-sidebar-icon,
.mark-entry-sidebar .sidebar-link.text-white .mark-entry-sidebar-icon {
    color: #ffffff;
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
    display: block;
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

.mark-entry-context-card,
.mark-entry-tab-shell,
.mark-entry-overview-card {
    border: 1px solid rgba(203, 213, 225, 0.92);
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.96);
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
}

.mark-entry-context-card {
    position: relative;
    overflow: visible;
    background: linear-gradient(90deg, #1eb53a 0%, #fcd116 48%, #00a3dd 100%) top / 100% 4px no-repeat, rgba(255, 255, 255, 0.96);
}

.mark-entry-context-card h2 {
    letter-spacing: -0.03em;
}

.mark-entry-step-heading {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 16px;
    align-items: start;
    margin-bottom: 1.15rem;
}

.mark-entry-step-copy {
    margin: 0.42rem 0 0;
    color: #64748b;
    font-size: 0.92rem;
    line-height: 1.65;
    max-width: 52rem;
}

.mark-entry-step-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.mark-entry-step-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 36px;
    padding: 0 13px;
    border-radius: 999px;
    border: 1px solid rgba(191, 219, 254, 0.9);
    background: rgba(239, 246, 255, 0.92);
    color: #1e3a8a;
    font-size: 0.8rem;
    font-weight: 700;
}

.mark-entry-context-grid {
    display: grid;
    grid-template-columns: minmax(110px, 0.9fr) minmax(140px, 1fr) minmax(220px, 1.45fr) minmax(260px, 1.75fr) minmax(260px, 1.75fr) minmax(110px, 0.8fr);
    gap: 14px;
    align-items: start;
}

.mark-entry-context-field {
    min-width: 0;
}

.mark-entry-shell .filter-dropdown-btn,
.mark-entry-shell .filter-input,
.mark-entry-shell select,
.mark-entry-shell input.filter-search-input[type="text"],
.mark-entry-shell .relative > button[class*="rounded-none"],
.mark-entry-shell .relative > div.absolute,
.mark-entry-shell .relative > div.absolute input[type="text"] {
    border-radius: 0 !important;
}

.mark-entry-shell .relative > div.absolute {
    top: calc(100% - 1px);
    overflow: hidden;
}

.mark-entry-context-field .whitespace-nowrap {
    overflow: hidden;
    text-overflow: ellipsis;
}

.mark-entry-tab-shell {
    overflow: hidden;
}

.mark-entry-mode-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
    padding: 14px 18px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.94) 0%, rgba(248, 250, 252, 0.98) 100%);
}

.mark-entry-mode-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    padding: 0 18px;
    border-radius: 14px;
    border: 1px solid transparent;
    font-weight: 700;
    background: transparent;
}

.mark-entry-mode-button:hover {
    background: rgba(239, 246, 255, 0.78);
    color: #1d4ed8;
}

.mark-entry-mode-button-active {
    background: linear-gradient(180deg, rgba(239, 246, 255, 0.98) 0%, rgba(219, 234, 254, 0.98) 100%);
    color: #1d4ed8 !important;
    border-color: rgba(147, 197, 253, 0.95);
    box-shadow: inset 0 -2px 0 #2563eb;
}

.mark-entry-primary-btn {
    border-radius: 16px;
    border: 1px solid rgba(37, 99, 235, 0.14);
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    box-shadow: 0 14px 26px rgba(37, 99, 235, 0.18);
}

.mark-entry-primary-btn:hover {
    background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
}

.mark-entry-secondary-btn {
    border-radius: 16px;
    border: 1px solid rgba(203, 213, 225, 0.95);
    background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%);
}

.mark-entry-secondary-btn:hover {
    background: linear-gradient(180deg, #f1f5f9 0%, #cbd5e1 100%);
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

.mark-entry-workspace-panel {
    background: linear-gradient(145deg, rgba(15, 23, 42, 0.98) 0%, rgba(15, 74, 118, 0.95) 52%, rgba(13, 115, 119, 0.92) 100%);
    border-color: rgba(30, 64, 175, 0.2);
    box-shadow: 0 22px 42px rgba(15, 23, 42, 0.14);
}

.mark-entry-pipeline-row {
    display: flex;
    align-items: start;
    justify-content: space-between;
    gap: 16px;
    padding: 0.95rem 1rem;
    border-radius: 1rem;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.mark-entry-pipeline-row-ready {
    background: rgba(15, 118, 110, 0.18);
}

.mark-entry-pipeline-row-pending {
    background: rgba(255, 255, 255, 0.05);
}

.mark-entry-pipeline-icon {
    display: inline-flex;
    height: 2.25rem;
    width: 2.25rem;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    border: 1px solid transparent;
    flex-shrink: 0;
}

.mark-entry-pipeline-status {
    display: inline-flex;
    min-height: 2rem;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    padding: 0 0.8rem;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    white-space: nowrap;
}

.mark-entry-context-footer {
    margin-top: 1.25rem;
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    align-items: center;
    border-radius: 20px;
    border: 1px solid rgba(226, 232, 240, 0.92);
    background: linear-gradient(180deg, rgba(248, 250, 252, 0.96) 0%, rgba(241, 245, 249, 0.96) 100%);
    padding: 1rem 1.1rem;
}

.mark-entry-scope-summary {
    flex: 1 1 420px;
    min-width: 280px;
}

.mark-entry-readiness-grid {
    display: flex;
    flex: 2 1 520px;
    flex-wrap: nowrap;
    gap: 10px;
    justify-content: flex-end;
    overflow-x: auto;
}

.mark-entry-readiness-item {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    min-height: 44px;
    flex: 0 0 auto;
    white-space: nowrap;
    border-radius: 16px;
    padding: 0 14px;
    font-size: 0.85rem;
    font-weight: 700;
}

.mark-entry-readiness-item-ready {
    border: 1px solid rgba(167, 243, 208, 0.92);
    background: rgba(236, 253, 245, 0.95);
    color: #065f46;
}

.mark-entry-readiness-item-pending {
    border: 1px solid rgba(253, 230, 138, 0.92);
    background: rgba(255, 251, 235, 0.96);
    color: #92400e;
}

.mark-entry-instruction-card {
    border-radius: 20px;
    border: 1px solid rgba(147, 197, 253, 0.8);
    background: linear-gradient(180deg, rgba(239, 246, 255, 0.96) 0%, rgba(226, 238, 255, 0.92) 100%);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
}

.mark-entry-inline-tools {
    border-radius: 18px;
    border: 1px solid rgba(191, 219, 254, 0.9);
    background: linear-gradient(180deg, rgba(248, 250, 252, 0.9) 0%, rgba(241, 245, 249, 0.92) 100%);
    padding: 12px 14px;
}

.mark-entry-shell .fixed.inset-0.bg-black\/50,
.mark-entry-shell .fixed.inset-0.bg-black\/60 {
    backdrop-filter: blur(8px);
}

@media (max-width: 1279px) {
    .mark-entry-hero-grid {
        grid-template-columns: 1fr;
    }

    .mark-entry-hero-side {
        justify-items: start;
    }

    .mark-entry-context-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .mark-entry-context-field-subject,
    .mark-entry-context-field-school,
    .mark-entry-context-field-district {
        grid-column: span 2;
    }
}

@media (max-width: 1023px) {
    .mark-entry-shell {
        background: linear-gradient(180deg, #eff4fb 0%, #e8eef6 100%);
    }

    .mark-entry-context-grid {
        grid-template-columns: 1fr;
    }

    .mark-entry-context-footer,
    .mark-entry-readiness-grid {
        justify-content: flex-start;
    }

    .mark-entry-readiness-item {
        flex-basis: auto;
    }

    .mark-entry-context-field-subject,
    .mark-entry-context-field-school,
    .mark-entry-context-field-district {
        grid-column: span 1;
    }

    .mark-entry-mode-bar {
        flex-direction: column;
        align-items: stretch;
    }

    .mark-entry-mode-button {
        justify-content: flex-start;
    }
}
</style>

<script>
    function psleMarkEntryManager() {
        return {
            tabs: [
                { key: 'single', label: 'Single Subject CSV', icon: 'fas fa-file-csv', family: 'intake' },
                { key: 'school', label: 'School Bulk ZIP', icon: 'fas fa-box-open', family: 'intake' },
                { key: 'district', label: 'District Bulk ZIP', icon: 'fas fa-archive', family: 'intake' },
                { key: 'review', label: 'Readiness & Review', icon: 'fas fa-clipboard-check', family: 'intake' },
                { key: 'moderation', label: 'Moderation & Review', icon: 'fas fa-user-check', family: 'control' },
                { key: 'locking', label: 'Submission Locking', icon: 'fas fa-lock', family: 'control' },
                { key: 'reports', label: 'Reports & Exports', icon: 'fas fa-chart-column', family: 'control' },
                { key: 'audit', label: 'Monitoring & Audit', icon: 'fas fa-shield-halved', family: 'control' },
                { key: 'admin', label: 'Administration', icon: 'fas fa-sliders', family: 'control' },
            ],
            activeTab: 'single',
            activeTabClass: 'border-blue-300 bg-[linear-gradient(135deg,#2563eb_0%,#1d4ed8_52%,#0f766e_100%)] text-white shadow-[0_18px_32px_rgba(37,99,235,0.26)] -translate-y-0.5',
            inactiveTabClass: 'border-transparent bg-transparent text-slate-700 hover:border-slate-200 hover:bg-white/90 hover:text-slate-900 hover:shadow-[0_10px_20px_rgba(15,23,42,0.08)]',
            activeIconClass: 'bg-white/18 text-white ring-1 ring-white/18',
            inactiveIconClass: 'bg-slate-100 text-slate-500 ring-1 ring-slate-200 group-hover:bg-blue-50 group-hover:text-blue-700 group-hover:ring-blue-100',
            templateColumns: ['Candidate Number', 'PReM No', 'Sex', 'School Code', 'Subject Code', 'Mark'],
            schoolWorkflowSteps: [
                { title: 'Generate school templates', text: 'Prepare one governed subject CSV per school using the pupil scope visible in this workspace.' },
                { title: 'Keep subjects separate', text: 'A school ZIP should contain distinct subject CSV files rather than merged or manually restructured spreadsheets.' },
                { title: 'Preserve candidate identity', text: 'Do not re-order identifiers or edit candidate number and PReM number columns after template generation.' },
            ],
            districtWorkflowSteps: [
                { title: 'Confirm council scope', text: 'District packaging starts only after the correct council is selected and the visible school list is confirmed.' },
                { title: 'Group by school', text: 'Inside a district ZIP, keep each school package distinct so reconciliation and error tracking remain simple.' },
                { title: 'Use official subject codes', text: 'Every file inside the district package must use the governed PSLE subject code from the official catalog.' },
            ],
            examYears: [],
            regions: [],
            districts: [],
            schools: [],
            subjects: [],
            candidates: [],
            totalCandidates: 0,
            loadingCandidates: false,
            examYear: '',
            selectedRegion: '',
            selectedDistrict: '',
            selectedSchool: '',
            selectedSubject: '',
            yearOpen: false,
            regionOpen: false,
            districtOpen: false,
            schoolOpen: false,
            subjectOpen: false,
            yearSearch: '',
            regionSearch: '',
            districtSearch: '',
            schoolSearch: '',
            subjectSearchFilter: '',
            candidateSearch: '',
            candidatePage: 1,
            candidateLastPage: 1,
            candidatePerPage: 100,
            importModalOpen: false,
            importMode: 'single_csv',
            schoolZipIntakeOpen: false,
            importStep: 1,
            importFile: null,
            importProcessing: false,
            importValidation: { rows: 0, validRows: 0, errors: [], warnings: [], preview: [], can_commit: false },
            importCommitResult: {},
            recentBatches: [],
            lifecycleDashboard: { summary: {}, batches: [], subject_breakdown: [], school_breakdown: [] },
            reportsDashboard: { summary: {}, status_rows: [], subject_rows: [], school_rows: [] },
            scoresheetMode: 'approved',
            scoresheetLayout: 'formal',
            scoresheetSubjects: [],
            scoresheetExporting: false,
            scoresheetMessage: '',
            scoresheetMessageType: 'success',
            auditDashboard: { summary: {}, timeline: [] },
            administrationDashboard: { settings: [], governance: [] },
            batchActionModalOpen: false,
            batchActionType: '',
            batchActionTarget: null,
            batchActionNote: '',
            batchActionProcessing: false,

            get filteredDistricts() {
                if (!this.selectedRegion) {
                    return this.districts;
                }

                return this.districts.filter(district => String(district.region_id) === String(this.selectedRegion));
            },

            get searchableRegions() {
                const term = this.regionSearch.trim().toLowerCase();
                if (!term) return this.regions;
                return this.regions.filter(region => String(region.name || '').toLowerCase().includes(term));
            },

            get searchableYears() {
                const term = this.yearSearch.trim().toLowerCase();
                if (!term) return this.examYears;
                return this.examYears.filter(year => String(year.year_label || '').toLowerCase().includes(term));
            },

            get searchableDistricts() {
                const term = this.districtSearch.trim().toLowerCase();
                if (!term) return this.filteredDistricts;
                return this.filteredDistricts.filter(district => String(district.name || '').toLowerCase().includes(term));
            },

            get searchableSchools() {
                const term = this.schoolSearch.trim().toLowerCase();
                if (!term) return this.schools;
                return this.schools.filter(school => {
                    return [
                        school.name,
                        school.code,
                        school.registration_number,
                        school.district_name,
                    ].some(value => String(value || '').toLowerCase().includes(term));
                });
            },

            get searchableSubjects() {
                const term = this.subjectSearchFilter.trim().toLowerCase();
                if (!term) return this.subjects;
                return this.subjects.filter(subject => {
                    return [
                        subject.name,
                        subject.code,
                    ].some(value => String(value || '').toLowerCase().includes(term));
                });
            },

            get overviewCards() {
                return [
                    {
                        label: 'Official Subjects',
                        value: this.subjects.length,
                        icon: 'fas fa-book-open',
                        detail: 'Fixed PSLE subject catalog loaded for templates, intake, and reporting.',
                        shell: 'border-blue-100 bg-[linear-gradient(180deg,rgba(239,246,255,0.9)_0%,rgba(255,255,255,0.96)_100%)]',
                        kickerClass: 'text-blue-700',
                        valueClass: 'text-slate-950',
                        iconClass: 'bg-blue-100 text-blue-700',
                        footerClass: 'border-blue-100 text-slate-600',
                    },
                    {
                        label: 'Scoped Schools',
                        value: this.schools.length,
                        icon: 'fas fa-school',
                        detail: 'NECTA-synced PSLE primary schools currently visible in the active scope.',
                        shell: 'border-emerald-100 bg-[linear-gradient(180deg,rgba(236,253,245,0.92)_0%,rgba(255,255,255,0.96)_100%)]',
                        kickerClass: 'text-emerald-700',
                        valueClass: 'text-slate-950',
                        iconClass: 'bg-emerald-100 text-emerald-700',
                        footerClass: 'border-emerald-100 text-slate-600',
                    },
                    {
                        label: 'Scoped Pupils',
                        value: this.totalCandidates,
                        icon: 'fas fa-user-graduate',
                        detail: 'Pupil roster available for review, governed template export, and mark-entry matching.',
                        shell: 'border-amber-100 bg-[linear-gradient(180deg,rgba(255,251,235,0.94)_0%,rgba(255,255,255,0.96)_100%)]',
                        kickerClass: 'text-amber-700',
                        valueClass: 'text-slate-950',
                        iconClass: 'bg-amber-100 text-amber-700',
                        footerClass: 'border-amber-100 text-slate-600',
                    },
                    {
                        label: 'Template Status',
                        value: this.isTemplateReady ? 'Ready' : 'Pending Scope',
                        icon: this.isTemplateReady ? 'fas fa-circle-check' : 'fas fa-hourglass-half',
                        detail: 'Single-subject template issuance is enabled only when school, subject, and roster are aligned.',
                        shell: this.isTemplateReady
                            ? 'border-emerald-100 bg-[linear-gradient(180deg,rgba(236,253,245,0.94)_0%,rgba(255,255,255,0.96)_100%)]'
                            : 'border-slate-200 bg-[linear-gradient(180deg,rgba(248,250,252,0.98)_0%,rgba(255,255,255,0.96)_100%)]',
                        kickerClass: this.isTemplateReady ? 'text-emerald-700' : 'text-slate-500',
                        valueClass: this.isTemplateReady ? 'text-emerald-700 text-[2.1rem]' : 'text-slate-900 text-[2.1rem]',
                        iconClass: this.isTemplateReady ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500',
                        footerClass: this.isTemplateReady ? 'border-emerald-100 text-slate-600' : 'border-slate-200 text-slate-600',
                    },
                ];
            },

            get intakeTabs() {
                return this.tabs.filter(tab => tab.family === 'intake');
            },

            get controlTabs() {
                return this.tabs.filter(tab => tab.family === 'control');
            },

            get entryTabs() {
                return this.tabs.filter(tab => ['single', 'school', 'district'].includes(tab.key));
            },

            get moderationTabs() {
                return this.tabs.filter(tab => ['review', 'moderation'].includes(tab.key));
            },

            get lockingTabs() {
                return this.tabs.filter(tab => ['locking'].includes(tab.key));
            },

            get reportTabs() {
                return this.tabs.filter(tab => ['reports'].includes(tab.key));
            },

            get auditTabs() {
                return this.tabs.filter(tab => ['audit'].includes(tab.key));
            },

            get administrationTabs() {
                return this.tabs.filter(tab => ['admin'].includes(tab.key));
            },

            get activeTabCategory() {
                if (this.entryTabs.some(tab => tab.key === this.activeTab)) return 'Entry & Validation';
                if (this.moderationTabs.some(tab => tab.key === this.activeTab)) return 'Moderation & Review';
                if (this.lockingTabs.some(tab => tab.key === this.activeTab)) return 'Submission & Locking';
                if (this.reportTabs.some(tab => tab.key === this.activeTab)) return 'Reports & Exports';
                if (this.auditTabs.some(tab => tab.key === this.activeTab)) return 'Monitoring & Audit';
                if (this.administrationTabs.some(tab => tab.key === this.activeTab)) return 'Administration';
                return 'Workspace';
            },

            get selectedRegionRecord() {
                return this.regions.find(region => String(region.id) === String(this.selectedRegion)) || null;
            },

            get selectedYearRecord() {
                return this.examYears.find(year => String(year.year_label) === String(this.examYear)) || null;
            },

            get selectedDistrictRecord() {
                return this.districts.find(district => String(district.id) === String(this.selectedDistrict)) || null;
            },

            get selectedSchoolRecord() {
                return this.schools.find(school => String(school.id) === String(this.selectedSchool)) || null;
            },

            get selectedSubjectRecord() {
                return this.subjects.find(subject => String(subject.id) === String(this.selectedSubject)) || null;
            },

            get selectedScoresheetSubjectRecord() {
                return this.scoresheetSubjects.find(subject => String(subject.id) === String(this.selectedSubject)) || null;
            },

            get isTemplateReady() {
                return Boolean(this.selectedSchool && this.selectedSubject && this.totalCandidates > 0);
            },

            get activeWorkspaceMeta() {
                const map = {
                    single: {
                        title: 'Single Subject CSV Intake',
                        description: 'Generate one governed subject file for one primary school, validate it against the roster, and commit it into PSLE intake with full scope discipline.',
                        stats: [
                            { label: 'School', value: this.selectedSchoolRecord?.code || 'Not selected' },
                            { label: 'Subject', value: this.selectedSubjectRecord?.code || 'Not selected' },
                            { label: 'Pupils', value: this.totalCandidates || 0 },
                        ],
                    },
                    school: {
                        title: 'School Bulk ZIP Intake',
                        description: 'Prepare and accept one ZIP package for a single school while keeping subject files separate, governed, and traceable to the same pupil scope.',
                        stats: [
                            { label: 'School', value: this.selectedSchoolRecord?.code || 'Not selected' },
                            { label: 'Council', value: this.selectedDistrictRecord?.name || 'Not selected' },
                            { label: 'Schools Loaded', value: this.schools.length || 0 },
                        ],
                    },
                    district: {
                        title: 'District Bulk ZIP Intake',
                        description: 'Control district-level packaging for PSLE by keeping schools distinct inside the council submission path and validating scope before upload.',
                        stats: [
                            { label: 'Council', value: this.selectedDistrictRecord?.name || 'Not selected' },
                            { label: 'Schools Loaded', value: this.schools.length || 0 },
                            { label: 'Subjects', value: this.subjects.length || 0 },
                        ],
                    },
                    review: {
                        title: 'Readiness and Roster Review',
                        description: 'Review the active pupil scope, identity details, and subject allocation before templates are issued or intake decisions are made.',
                        stats: [
                            { label: 'Loaded', value: this.candidates.length || 0 },
                            { label: 'Total Scope', value: this.totalCandidates || 0 },
                            { label: 'Page', value: `${this.candidatePage}/${this.candidateLastPage}` },
                        ],
                    },
                    moderation: {
                        title: 'Moderation and Review Control',
                        description: 'Move PSLE batches from validated intake into operational review, then approve or reject them with clear feedback and status visibility.',
                        stats: [
                            { label: 'Validated', value: this.lifecycleDashboard.summary?.validated || 0 },
                            { label: 'Submitted', value: this.lifecycleDashboard.summary?.submitted || 0 },
                            { label: 'Rejected', value: this.lifecycleDashboard.summary?.rejected || 0 },
                        ],
                    },
                    locking: {
                        title: 'Submission Locking Control',
                        description: 'Lock only approved PSLE batches, keep unlocks governed, and preserve a clear final intake state before downstream processing.',
                        stats: [
                            { label: 'Approved', value: this.lifecycleDashboard.summary?.approved || 0 },
                            { label: 'Locked', value: this.lifecycleDashboard.summary?.locked || 0 },
                            { label: 'Rows', value: this.lifecycleDashboard.summary?.rows || 0 },
                        ],
                    },
                    reports: {
                        title: 'Reports and Export Surface',
                        description: 'Review current PSLE operational coverage by status, subject, and school, then export the scoped mark-entry report for downstream use.',
                        stats: [
                            { label: 'Batches', value: this.reportsDashboard.summary?.batch_count || 0 },
                            { label: 'Rows', value: this.reportsDashboard.summary?.row_count || 0 },
                            { label: 'Warnings', value: this.reportsDashboard.summary?.warning_count || 0 },
                        ],
                    },
                    audit: {
                        title: 'Monitoring and Audit Timeline',
                        description: 'Track imports, moderation decisions, locking actions, and related PSLE lifecycle events in one scoped operational timeline.',
                        stats: [
                            { label: 'Events', value: this.auditDashboard.summary?.events || 0 },
                            { label: 'Reviews', value: this.auditDashboard.summary?.reviews || 0 },
                            { label: 'Imports', value: this.auditDashboard.summary?.imports || 0 },
                        ],
                    },
                    admin: {
                        title: 'Administration Snapshot',
                        description: 'Keep a light governance view of PSLE intake modes, current year, schools, subject catalog, and operational control rules without overloading the workspace.',
                        stats: [
                            { label: 'Settings', value: this.administrationDashboard.settings?.length || 0 },
                            { label: 'Rules', value: this.administrationDashboard.governance?.length || 0 },
                            { label: 'Year', value: this.examYear || '-' },
                        ],
                    },
                };

                return map[this.activeTab] || map.single;
            },

            get entryMode() {
                return ['single', 'school', 'district'].includes(this.activeTab) ? this.activeTab : 'single';
            },

            get isImportScopeReady() {
                if (!this.examYear) {
                    return false;
                }

                if (this.importMode === 'single_csv') {
                    return Boolean(this.selectedSchool && this.selectedSubject);
                }

                if (this.importMode === 'school_zip') {
                    return Boolean(this.selectedSchool);
                }

                return Boolean(this.selectedDistrict);
            },

            get importModeLabel() {
                return this.importMode === 'single_csv'
                    ? 'Single Subject CSV'
                    : this.importMode === 'school_zip'
                        ? 'School Bulk ZIP'
                        : 'District Bulk ZIP';
            },

            get importModeBadge() {
                return this.importMode === 'single_csv'
                    ? 'Single CSV Intake'
                    : this.importMode === 'school_zip'
                        ? 'School ZIP Intake'
                        : 'District ZIP Intake';
            },

            get importModalTitle() {
                return this.importMode === 'single_csv'
                    ? 'Upload PSLE Subject CSV'
                    : this.importMode === 'school_zip'
                        ? 'Upload School Bulk ZIP'
                        : 'Upload District Bulk ZIP';
            },

            get importModalDescription() {
                return this.importMode === 'single_csv'
                    ? 'Use the generated school-and-subject template, then validate it before it is staged into the PSLE intake pipeline.'
                    : this.importMode === 'school_zip'
                        ? 'Upload one ZIP package for a selected school. The package should contain governed subject CSV files built from PSLE templates.'
                        : 'Upload one district ZIP package after confirming the council scope and the included PSLE schools.';
            },

            get importAccept() {
                return this.importMode === 'single_csv' ? '.csv,.txt' : '.zip';
            },

            get importFormatHint() {
                return this.importMode === 'single_csv' ? 'CSV or TXT up to 5MB' : 'ZIP package up to 200MB';
            },

            get importScopeMessage() {
                if (this.importMode === 'single_csv') {
                    if (!this.selectedSchool || !this.selectedSubject) {
                        return 'Select one primary school and one subject before uploading a completed PSLE subject CSV.';
                    }

                    return `Current scope is ready for a single-subject file: ${this.scopeSummary}.`;
                }

                if (this.importMode === 'school_zip') {
                    return this.selectedSchool
                        ? `Current scope is ready for a school package: ${this.scopeSummary}.`
                        : 'Select one primary school before uploading a school ZIP package.';
                }

                return this.selectedDistrict
                    ? `Current scope is ready for a district package: ${this.scopeSummary}.`
                    : 'Select one council before uploading a district ZIP package.';
            },

            get pipelineStages() {
                return [
                    {
                        key: 'scope',
                        title: 'Scope Locked',
                        icon: 'fas fa-location-dot',
                        ready: Boolean(this.examYear && (this.selectedRegion || this.selectedDistrict || this.selectedSchool)),
                        text: 'Context is defined through year, geography, and operational school/council scope.',
                    },
                    {
                        key: 'template',
                        title: 'Template Ready',
                        icon: 'fas fa-file-csv',
                        ready: this.isTemplateReady,
                        text: 'A governed subject file can be generated only when the school, subject, and pupil roster are aligned.',
                    },
                    {
                        key: 'upload',
                        title: 'Upload Intake',
                        icon: 'fas fa-cloud-arrow-up',
                        ready: this.recentBatches.length > 0,
                        text: 'Operators can now upload CSV or ZIP packages through the PSLE intake modal.',
                    },
                    {
                        key: 'review',
                        title: 'Roster Review',
                        icon: 'fas fa-clipboard-check',
                        ready: this.totalCandidates > 0,
                        text: 'The pupil review table remains visible so intake decisions stay tied to the active roster.',
                    },
                ];
            },

            get scopeSummary() {
                const year = this.examYear || 'No year selected';
                const region = this.selectedRegionRecord?.name || 'All regions';
                const district = this.selectedDistrictRecord?.name || 'All councils';
                const school = this.selectedSchoolRecord?.name || 'All primary schools';
                const subject = this.selectedSubjectRecord?.name || 'No subject selected';

                return `${year} · ${region} · ${district} · ${school} · ${subject}`;
            },

            get importCommitRowCount() {
                if (this.importCommitResult?.batch?.rows) {
                    return this.importCommitResult.batch.rows;
                }

                if (Array.isArray(this.importCommitResult?.batches)) {
                    return this.importCommitResult.batches.reduce((sum, batch) => sum + Number(batch.rows || 0), 0);
                }

                return this.importValidation.rows || 0;
            },

            get moderationSummaryCards() {
                return [
                    { label: 'Validated', value: this.lifecycleDashboard.summary?.validated || 0, tone: 'bg-blue-50 text-blue-700 border-blue-200' },
                    { label: 'Submitted', value: this.lifecycleDashboard.summary?.submitted || 0, tone: 'bg-amber-50 text-amber-700 border-amber-200' },
                    { label: 'Approved', value: this.lifecycleDashboard.summary?.approved || 0, tone: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
                    { label: 'Rejected', value: this.lifecycleDashboard.summary?.rejected || 0, tone: 'bg-rose-50 text-rose-700 border-rose-200' },
                ];
            },

            get lockingSummaryCards() {
                return [
                    { label: 'Locked', value: this.lifecycleDashboard.summary?.locked || 0, tone: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
                    { label: 'Rows in Scope', value: this.lifecycleDashboard.summary?.rows || 0, tone: 'bg-slate-50 text-slate-700 border-slate-200' },
                    { label: 'Schools in Scope', value: this.lifecycleDashboard.summary?.schools || 0, tone: 'bg-blue-50 text-blue-700 border-blue-200' },
                    { label: 'Warnings', value: this.lifecycleDashboard.summary?.warnings || 0, tone: 'bg-amber-50 text-amber-700 border-amber-200' },
                ];
            },

            get batchActionTitle() {
                return this.batchActionType === 'submit'
                    ? 'Submit Batch For Review'
                    : this.batchActionType === 'approve'
                        ? 'Approve Batch'
                        : this.batchActionType === 'reject'
                            ? 'Reject Batch'
                            : this.batchActionType === 'lock'
                                ? 'Lock Batch'
                                : 'Unlock Batch';
            },

            get batchActionLabel() {
                return this.batchActionType === 'submit'
                    ? 'Submit Batch'
                    : this.batchActionType === 'approve'
                        ? 'Approve Batch'
                        : this.batchActionType === 'reject'
                            ? 'Reject Batch'
                            : this.batchActionType === 'lock'
                                ? 'Lock Batch'
                                : 'Unlock Batch';
            },

            get batchActionPrompt() {
                return this.batchActionType === 'submit'
                    ? 'Move this validated PSLE batch into the moderation queue.'
                    : this.batchActionType === 'approve'
                        ? 'Approve this reviewed PSLE batch so it can move to submission locking.'
                        : this.batchActionType === 'reject'
                            ? 'Return this PSLE batch for correction with a clear rejection reason.'
                            : this.batchActionType === 'lock'
                                ? 'Lock this approved PSLE batch and promote the marks into the final mark-entry state.'
                                : 'Unlock this PSLE batch and return it to the submitted state for controlled correction.';
            },

            get batchActionNoteRequired() {
                return ['reject', 'unlock'].includes(this.batchActionType);
            },

            get readinessChecks() {
                return [
                    { key: 'year', label: 'Exam year selected', ready: Boolean(this.examYear) },
                    { key: 'school', label: 'Primary school selected', ready: Boolean(this.selectedSchool) },
                    { key: 'subject', label: 'Subject selected', ready: Boolean(this.selectedSubject) },
                    { key: 'pupils', label: 'Pupil roster loaded', ready: this.totalCandidates > 0 },
                ];
            },

            get templateIssueChecklist() {
                return [
                    {
                        label: 'School scope',
                        ready: Boolean(this.selectedSchool),
                        detail: this.selectedSchoolRecord ? this.selectedSchoolRecord.name : 'Select one PSLE primary school before issuing a template.',
                    },
                    {
                        label: 'Subject code',
                        ready: Boolean(this.selectedSubject),
                        detail: this.selectedSubjectRecord ? `${this.selectedSubjectRecord.code} - ${this.selectedSubjectRecord.name}` : 'Choose one official PSLE subject code.',
                    },
                    {
                        label: 'Pupil roster',
                        ready: this.totalCandidates > 0,
                        detail: this.totalCandidates > 0 ? `${this.totalCandidates} pupil(s) available in the current scope.` : 'No pupils are currently available for this scope.',
                    },
                ];
            },

            get genderBreakdown() {
                return this.filteredCandidates.reduce((summary, candidate) => {
                    const gender = String(candidate.gender || '').toUpperCase();
                    if (gender === 'F' || gender === 'FEMALE') {
                        summary.female += 1;
                    } else if (gender === 'M' || gender === 'MALE') {
                        summary.male += 1;
                    }

                    if (candidate.prem_no) {
                        summary.withPrem += 1;
                    }

                    return summary;
                }, { female: 0, male: 0, withPrem: 0 });
            },

            get filteredCandidates() {
                const term = this.candidateSearch.trim().toLowerCase();
                if (!term) {
                    return this.candidates;
                }

                return this.candidates.filter(candidate => {
                    return [
                        candidate.candidate_id,
                        candidate.prem_no,
                        candidate.full_name,
                        candidate.school_name,
                        candidate.district_name,
                    ].some(value => String(value || '').toLowerCase().includes(term));
                });
            },

            get visibleSchools() {
                return this.schools.slice(0, 12);
            },

            get districtSchoolBreakdown() {
                return this.filteredDistricts
                    .map(district => ({
                        id: district.id,
                        name: district.name,
                        region_name: this.regions.find(region => String(region.id) === String(district.region_id))?.name || '-',
                        school_count: this.schools.filter(school => String(school.district_id) === String(district.id)).length,
                    }))
                    .filter(row => row.school_count > 0 || !this.selectedDistrict);
            },

            async init() {
                this.initializeTabFromUrl();
                await this.loadExamYears();
                await this.loadRegions();
                await this.loadDistricts();
                await this.loadSubjects();
                await this.refreshScope();
                await this.loadRecentBatches();
            },

            initializeTabFromUrl() {
                const url = new URL(window.location.href);
                const requestedTab = url.searchParams.get('tab');
                const allowedTabs = this.tabs.map(tab => tab.key);
                this.activeTab = allowedTabs.includes(requestedTab) ? requestedTab : 'single';
            },

            tabHref(tabKey) {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabKey);
                return url.pathname + url.search;
            },

            setActiveTab(tabKey) {
                this.activeTab = tabKey;
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabKey);
                window.history.replaceState({}, '', url.toString());
            },

            async loadExamYears() {
                try {
                    const response = await fetch('/api/exam-years');
                    const data = await response.json();
                    this.examYears = data.exam_years || [];
                    const active = this.examYears.find(year => year.is_active);
                    if (active && !this.examYear) {
                        this.examYear = active.year_label;
                    }
                } catch (error) {
                    this.showMessage('Failed to load exam years', 'error');
                }
            },

            async loadRegions() {
                try {
                    const response = await fetch('/api/regions');
                    const data = await response.json();
                    this.regions = data.data || [];
                } catch (error) {
                    this.showMessage('Failed to load regions', 'error');
                }
            },

            async loadDistricts() {
                try {
                    const response = await fetch('/api/districts?page_size=999');
                    const data = await response.json();
                    this.districts = data.data || [];
                } catch (error) {
                    this.showMessage('Failed to load councils', 'error');
                }
            },

            async loadSchools() {
                try {
                    const params = new URLSearchParams();
                    if (this.selectedRegion) params.set('region_id', this.selectedRegion);
                    if (this.selectedDistrict) params.set('district_id', this.selectedDistrict);
                    const response = await fetch(`/api/exam-types/psle/schools?${params.toString()}`);
                    const data = await response.json();
                    this.schools = data.data || [];

                    if (this.selectedSchool && !this.schools.some(school => String(school.id) === String(this.selectedSchool))) {
                        this.selectedSchool = '';
                    }
                } catch (error) {
                    this.showMessage('Failed to load PSLE schools', 'error');
                }
            },

            async loadSubjects() {
                try {
                    const response = await fetch('/api/exam-types/PSLE/subjects');
                    const data = await response.json();
                    this.subjects = data.data || [];
                } catch (error) {
                    this.showMessage('Failed to load PSLE subjects', 'error');
                }
            },

            async loadCandidates(page = 1) {
                this.loadingCandidates = true;
                try {
                    const params = new URLSearchParams();
                    params.set('page', String(page));
                    params.set('per_page', String(this.candidatePerPage));
                    if (this.examYear) params.set('exam_year', this.examYear);
                    if (this.selectedRegion) params.set('region_id', this.selectedRegion);
                    if (this.selectedDistrict) params.set('district_id', this.selectedDistrict);
                    if (this.selectedSchool) params.set('school_id', this.selectedSchool);

                    const response = await fetch(`/api/exam-types/PSLE/candidates?${params.toString()}`);
                    const data = await response.json();
                    this.candidates = data.data || [];
                    this.totalCandidates = data.meta?.total || this.candidates.length;
                    this.candidatePage = data.meta?.current_page || page;
                    this.candidateLastPage = data.meta?.last_page || 1;
                } catch (error) {
                    this.showMessage('Failed to load PSLE pupils', 'error');
                } finally {
                    this.loadingCandidates = false;
                }
            },

            async refreshScope() {
                this.candidatePage = 1;
                await this.loadSchools();
                await this.loadCandidates(1);
                await this.loadLifecycleDashboard();
                await this.loadReportsDashboard();
                await this.loadScoresheetSubjects();
                await this.loadAuditDashboard();
                await this.loadAdministrationDashboard();
            },

            async onRegionChange() {
                this.selectedDistrict = '';
                this.selectedSchool = '';
                await this.refreshScope();
            },

            async onDistrictChange() {
                this.selectedSchool = '';
                await this.refreshScope();
            },

            async resetContext() {
                this.examYear = this.examYears.find(year => year.is_active)?.year_label || '';
                this.selectedRegion = '';
                this.selectedDistrict = '';
                this.selectedSchool = '';
                this.selectedSubject = '';
                this.candidateSearch = '';
                await this.refreshScope();
            },

            async goToCandidatePage(page) {
                const target = Math.max(1, Math.min(page, this.candidateLastPage));
                if (target === this.candidatePage) {
                    return;
                }

                await this.loadCandidates(target);
            },

            selectSchool(schoolId) {
                this.selectedSchool = String(schoolId);
                this.refreshScope();
            },

            async chooseRegion(regionId) {
                this.selectedRegion = regionId ? String(regionId) : '';
                this.regionOpen = false;
                this.regionSearch = '';
                await this.onRegionChange();
            },

            async chooseYear(yearLabel) {
                this.examYear = yearLabel ? String(yearLabel) : '';
                this.yearOpen = false;
                this.yearSearch = '';
                await this.refreshScope();
            },

            async chooseDistrict(districtId) {
                this.selectedDistrict = districtId ? String(districtId) : '';
                this.districtOpen = false;
                this.districtSearch = '';
                await this.onDistrictChange();
            },

            async chooseSchool(schoolId) {
                this.selectedSchool = schoolId ? String(schoolId) : '';
                this.schoolOpen = false;
                this.schoolSearch = '';
                await this.refreshScope();
            },

            async chooseSubject(subjectId) {
                this.selectedSubject = subjectId ? String(subjectId) : '';
                this.subjectOpen = false;
                this.subjectSearchFilter = '';
                await this.refreshScope();
            },

            selectDistrict(districtId) {
                this.selectedDistrict = String(districtId);
                this.onDistrictChange();
            },

            schoolDisplayLabel(school) {
                if (!school) {
                    return '-';
                }

                const code = school.code || school.registration_number || '-';
                return `${code} - ${school.name}`;
            },

            subjectAllocationLabel(candidate) {
                if (!candidate.allocated_subjects || !candidate.allocated_subjects.length) {
                    return '-';
                }

                return candidate.allocated_subjects.map(subject => subject.code).join(', ');
            },

            async collectTemplateRows() {
                const params = new URLSearchParams();
                params.set('per_page', String(this.candidatePerPage));
                if (this.examYear) params.set('exam_year', this.examYear);
                if (this.selectedRegion) params.set('region_id', this.selectedRegion);
                if (this.selectedDistrict) params.set('district_id', this.selectedDistrict);
                if (this.selectedSchool) params.set('school_id', this.selectedSchool);

                let page = 1;
                let lastPage = 1;
                const rows = [];

                do {
                    params.set('page', String(page));
                    const response = await fetch(`/api/exam-types/PSLE/candidates?${params.toString()}`);
                    const data = await response.json();
                    const candidates = data.data || [];
                    lastPage = data.meta?.last_page || 1;

                    candidates.forEach(candidate => {
                        rows.push([
                            candidate.candidate_id || '',
                            candidate.prem_no || '',
                            candidate.gender || '',
                            this.schools.find(school => String(school.id) === String(candidate.school_id))?.code || '',
                            this.selectedSubjectRecord?.code || '',
                            '',
                        ]);
                    });

                    page += 1;
                } while (page <= lastPage);

                return rows;
            },

            async downloadTemplate() {
                if (!this.selectedSchool || !this.selectedSubject) {
                    this.showMessage('Select a primary school and subject before downloading a PSLE mark template.', 'error');
                    return;
                }

                const rows = await this.collectTemplateRows();
                if (rows.length === 0) {
                    this.showMessage('No pupils found for the selected school and subject scope.', 'error');
                    return;
                }

                const headers = ['candidate_number', 'prem_no', 'sex', 'school_code', 'subject_code', 'mark'];
                this.downloadCsv(
                    [headers, ...rows],
                    `psle_mark_template_${(this.selectedSchoolRecord?.code || 'school').toLowerCase()}_${(this.selectedSubjectRecord?.code || 'subject').toLowerCase()}.csv`
                );
            },

            exportScopedPupils() {
                if (!this.filteredCandidates.length) {
                    this.showMessage('There are no scoped pupils to export.', 'error');
                    return;
                }

                const rows = [
                    ['candidate_number', 'prem_no', 'sex', 'school_code', 'school_name', 'council'],
                    ...this.filteredCandidates.map(candidate => [
                        candidate.candidate_id || '',
                        candidate.prem_no || '',
                        candidate.gender || '',
                        this.schools.find(school => String(school.id) === String(candidate.school_id))?.code || '',
                        candidate.school_name || '',
                        candidate.district_name || '',
                    ]),
                ];

                this.downloadCsv(rows, `psle_scoped_pupils_page_${this.candidatePage}.csv`);
            },

            downloadCsv(rows, filename) {
                const csvRows = rows.map(row =>
                    row.map(value => `"${String(value ?? '').replace(/"/g, '""')}"`).join(',')
                );

                const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(link);
            },

            openImportModal(mode) {
                this.importMode = mode;
                this.importModalOpen = true;
                this.importStep = 1;
                this.importFile = null;
                this.importProcessing = false;
                this.importValidation = { rows: 0, validRows: 0, errors: [], warnings: [], preview: [], can_commit: false };
                this.importCommitResult = {};
            },

            prepareSchoolZipIntake(openPicker = false) {
                this.importMode = 'school_zip';
                this.importModalOpen = false;
                this.schoolZipIntakeOpen = true;
                this.importStep = 1;
                this.importFile = null;
                this.importProcessing = false;
                this.importValidation = { rows: 0, validRows: 0, errors: [], warnings: [], preview: [], can_commit: false };
                this.importCommitResult = {};

                if (openPicker) {
                    this.$nextTick(() => this.triggerImportFilePicker('inline'));
                }
            },

            closeImportModal() {
                this.importModalOpen = false;
            },

            handleImportFileChange(event) {
                this.importFile = event.target.files[0] || null;
            },

            triggerImportFilePicker(target = 'modal') {
                const refName = target === 'inline' ? 'importInlineFileInput' : 'importModalFileInput';
                this.$refs?.[refName]?.click();
            },

            formatFileSize(size) {
                if (!size) return '0 B';
                const units = ['B', 'KB', 'MB', 'GB'];
                let value = size;
                let index = 0;
                while (value > 1024 && index < units.length - 1) {
                    value /= 1024;
                    index += 1;
                }
                return `${value.toFixed(1)} ${units[index]}`;
            },

            async validateImportFile() {
                if (!this.isImportScopeReady) {
                    this.showMessage(this.importScopeMessage, 'error');
                    return;
                }

                if (!this.importFile) {
                    this.showMessage('Choose a file first.', 'error');
                    return;
                }

                this.importProcessing = true;
                this.importValidation = { rows: 0, validRows: 0, errors: [], warnings: [], preview: [], can_commit: false };

                try {
                    const formData = new FormData();
                    formData.append('file', this.importFile);
                    formData.append('exam_year', this.examYear || '');
                    if (this.selectedSchool) formData.append('school_id', this.selectedSchool);
                    if (this.selectedSubject) formData.append('subject_id', this.selectedSubject);
                    if (this.selectedDistrict) formData.append('district_id', this.selectedDistrict);

                    const endpoint = this.importMode === 'single_csv'
                        ? '/mark-entry/psle/single/validate'
                        : this.importMode === 'school_zip'
                            ? '/mark-entry/psle/bulk/school/validate-zip'
                            : '/mark-entry/psle/bulk/district/validate-zip';

                    const response = await fetch(endpoint, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                    });
                    const payload = await response.json();

                    if (!response.ok) {
                        throw new Error(payload.message || 'Validation failed.');
                    }

                    this.importValidation = {
                        rows: payload.totals?.total_rows || 0,
                        validRows: payload.totals?.valid_rows || 0,
                        errors: Array.isArray(payload.errors) ? payload.errors.map(item => item.message || item) : [],
                        warnings: Array.isArray(payload.warnings) ? payload.warnings : [],
                        preview: Array.isArray(payload.preview) ? payload.preview : [],
                        can_commit: Boolean(payload.can_commit),
                    };

                    this.importStep = 2;
                } catch (error) {
                    this.importValidation.errors.push(error.message || 'Failed to validate the selected file.');
                    this.importStep = 2;
                } finally {
                    this.importProcessing = false;
                }
            },

            async commitImportFile() {
                if (!this.isImportScopeReady) {
                    this.showMessage(this.importScopeMessage, 'error');
                    return;
                }

                this.importProcessing = true;
                try {
                    const formData = new FormData();
                    formData.append('file', this.importFile);
                    formData.append('exam_year', this.examYear || '');
                    if (this.selectedSchool) formData.append('school_id', this.selectedSchool);
                    if (this.selectedSubject) formData.append('subject_id', this.selectedSubject);
                    if (this.selectedDistrict) formData.append('district_id', this.selectedDistrict);

                    const endpoint = this.importMode === 'single_csv'
                        ? '/mark-entry/psle/single/commit'
                        : this.importMode === 'school_zip'
                            ? '/mark-entry/psle/bulk/school/commit-zip'
                            : '/mark-entry/psle/bulk/district/commit-zip';

                    const response = await fetch(endpoint, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                    });
                    const payload = await response.json();

                    if (!response.ok || payload.success === false) {
                        throw new Error(payload.message || 'Commit failed.');
                    }

                    this.importCommitResult = payload;
                    await this.loadRecentBatches();
                    this.importStep = 3;
                    this.showMessage(payload.message || 'PSLE file committed successfully.', 'success');
                } catch (error) {
                    this.showMessage(error.message || 'Failed to commit the selected file.', 'error');
                } finally {
                    this.importProcessing = false;
                }
            },

            async loadRecentBatches() {
                try {
                    const response = await fetch('/api/mark-entry/psle/recent-batches');
                    const payload = await response.json();
                    this.recentBatches = payload.data || [];
                } catch (_error) {
                    this.recentBatches = [];
                }
            },

            async loadLifecycleDashboard() {
                try {
                    const response = await fetch(`/api/mark-entry/psle/lifecycle/dashboard?${this.currentScopeParams().toString()}`);
                    const payload = await response.json();
                    this.lifecycleDashboard = payload.data || { summary: {}, batches: [], subject_breakdown: [], school_breakdown: [] };
                } catch (_error) {
                    this.lifecycleDashboard = { summary: {}, batches: [], subject_breakdown: [], school_breakdown: [] };
                }
            },

            async loadReportsDashboard() {
                try {
                    const response = await fetch(`/api/mark-entry/psle/reports/summary?${this.currentScopeParams().toString()}`);
                    const payload = await response.json();
                    this.reportsDashboard = payload.data || { summary: {}, status_rows: [], subject_rows: [], school_rows: [] };
                } catch (_error) {
                    this.reportsDashboard = { summary: {}, status_rows: [], subject_rows: [], school_rows: [] };
                }
            },

            async loadScoresheetSubjects() {
                if (!this.examYear || !this.selectedSchool) {
                    this.scoresheetSubjects = [];
                    return;
                }

                try {
                    const params = new URLSearchParams({
                        exam_year: this.examYear,
                        school_id: this.selectedSchool,
                        mode: this.scoresheetMode,
                    });

                    const response = await fetch(`/api/mark-entry/psle/reports/scoresheet-subjects?${params.toString()}`);
                    const payload = await response.json();
                    this.scoresheetSubjects = payload.data || [];
                } catch (_error) {
                    this.scoresheetSubjects = [];
                }
            },

            async loadAuditDashboard() {
                try {
                    const response = await fetch(`/api/mark-entry/psle/audit/summary?${this.currentScopeParams().toString()}`);
                    const payload = await response.json();
                    this.auditDashboard = payload.data || { summary: {}, timeline: [] };
                } catch (_error) {
                    this.auditDashboard = { summary: {}, timeline: [] };
                }
            },

            async loadAdministrationDashboard() {
                try {
                    const response = await fetch(`/api/mark-entry/psle/admin/summary?${this.currentScopeParams().toString()}`);
                    const payload = await response.json();
                    this.administrationDashboard = payload.data || { settings: [], governance: [] };
                } catch (_error) {
                    this.administrationDashboard = { settings: [], governance: [] };
                }
            },

            currentScopeParams() {
                const params = new URLSearchParams();
                if (this.examYear) params.set('exam_year', this.examYear);
                if (this.selectedRegion) params.set('region_id', this.selectedRegion);
                if (this.selectedDistrict) params.set('district_id', this.selectedDistrict);
                if (this.selectedSchool) params.set('school_id', this.selectedSchool);
                if (this.selectedSubject) params.set('subject_id', this.selectedSubject);
                return params;
            },

            statusPillClass(status) {
                const key = String(status || '').toLowerCase();
                if (key === 'validated') return 'bg-blue-100 text-blue-800';
                if (key === 'submitted') return 'bg-amber-100 text-amber-800';
                if (key === 'approved') return 'bg-emerald-100 text-emerald-800';
                if (key === 'locked') return 'bg-teal-100 text-teal-800';
                if (key === 'rejected') return 'bg-rose-100 text-rose-800';
                return 'bg-slate-100 text-slate-700';
            },

            canSubmitBatch(batch) {
                return ['validated', 'draft'].includes(String(batch?.status || '').toLowerCase());
            },

            canApproveBatch(batch) {
                return String(batch?.status || '').toLowerCase() === 'submitted';
            },

            canRejectBatch(batch) {
                return String(batch?.status || '').toLowerCase() === 'submitted';
            },

            canLockBatch(batch) {
                return String(batch?.status || '').toLowerCase() === 'approved';
            },

            canUnlockBatch(batch) {
                return String(batch?.status || '').toLowerCase() === 'locked';
            },

            openBatchActionModal(action, batch) {
                this.batchActionType = action;
                this.batchActionTarget = batch;
                this.batchActionNote = '';
                this.batchActionModalOpen = true;
            },

            closeBatchActionModal() {
                this.batchActionModalOpen = false;
                this.batchActionType = '';
                this.batchActionTarget = null;
                this.batchActionNote = '';
                this.batchActionProcessing = false;
            },

            async submitBatchAction() {
                if (!this.batchActionTarget?.id) return;
                if (this.batchActionNoteRequired && this.batchActionNote.trim().length < 10) {
                    this.showMessage('Provide a clear reason of at least 10 characters.', 'error');
                    return;
                }

                this.batchActionProcessing = true;

                try {
                    const endpoint = `/api/mark-entry/psle/batches/${this.batchActionTarget.id}/${this.batchActionType}`;
                    const body = {};
                    if (this.batchActionType === 'approve' && this.batchActionNote.trim()) body.feedback = this.batchActionNote.trim();
                    if (this.batchActionType === 'reject' || this.batchActionType === 'unlock') body.reason = this.batchActionNote.trim();

                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        body: JSON.stringify(body),
                    });
                    const payload = await response.json();
                    if (!response.ok || payload.success === false) {
                        throw new Error(payload.message || 'Batch action failed.');
                    }

                    this.showMessage(payload.message || 'Batch action completed.', 'success');
                    this.closeBatchActionModal();
                    await this.loadLifecycleDashboard();
                    await this.loadReportsDashboard();
                    await this.loadAuditDashboard();
                    await this.loadRecentBatches();
                } catch (error) {
                    this.showMessage(error.message || 'Batch action failed.', 'error');
                } finally {
                    this.batchActionProcessing = false;
                }
            },

            exportReportCsv() {
                const url = new URL('/api/mark-entry/psle/reports/export', window.location.origin);
                this.currentScopeParams().forEach((value, key) => url.searchParams.set(key, value));
                window.open(url.toString(), '_blank');
            },

            async downloadScoresheet(level) {
                if (!this.examYear) {
                    this.showMessage('Select an exam year before exporting a PSLE scoresheet.', 'error');
                    return;
                }

                if (level === 'single' && (!this.selectedSchool || !this.selectedSubject)) {
                    this.showMessage('Select a primary school and one subject before exporting a single scoresheet.', 'error');
                    return;
                }

                if (level === 'school' && !this.selectedSchool) {
                    this.showMessage('Select a primary school before exporting the school ZIP package.', 'error');
                    return;
                }

                if (level === 'district' && !this.selectedDistrict) {
                    this.showMessage('Select a council before exporting the district ZIP package.', 'error');
                    return;
                }

                if (level === 'region' && !this.selectedRegion) {
                    this.showMessage('Select a region before exporting the region ZIP package.', 'error');
                    return;
                }

                const url = new URL(
                    level === 'single'
                        ? '/api/mark-entry/psle/reports/scoresheet-pdf'
                        : level === 'school'
                            ? '/api/mark-entry/psle/reports/scoresheet-pdf/school-zip'
                            : level === 'district'
                                ? '/api/mark-entry/psle/reports/scoresheet-pdf/district-zip'
                                : '/api/mark-entry/psle/reports/scoresheet-pdf/region-zip',
                    window.location.origin
                );

                url.searchParams.set('exam_year', this.examYear);
                url.searchParams.set('mode', this.scoresheetMode);
                url.searchParams.set('layout', this.scoresheetLayout);

                if (level === 'single' || level === 'school') {
                    url.searchParams.set('school_id', this.selectedSchool);
                }

                if (level === 'single') {
                    url.searchParams.set('subject_id', this.selectedSubject);
                }

                if (level === 'district') {
                    url.searchParams.set('district_id', this.selectedDistrict);
                }

                if (level === 'region') {
                    url.searchParams.set('region_id', this.selectedRegion);
                }

                this.scoresheetExporting = true;
                this.scoresheetMessage = '';

                try {
                    const response = await fetch(url.toString(), { headers: { Accept: 'application/octet-stream' } });
                    if (!response.ok) {
                        let message = 'Failed to generate the requested PSLE scoresheet export.';
                        try {
                            const payload = await response.json();
                            message = payload.message || message;
                        } catch (_error) {
                            const text = await response.text();
                            if (text) {
                                message = text;
                            }
                        }

                        if (level === 'school' && /No PSLE scoresheet subjects were found/i.test(message)) {
                            message = 'No PSLE scoresheet subjects were found for the selected school and exam year.';
                        }

                        throw new Error(message);
                    }

                    const blob = await response.blob();
                    const objectUrl = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    const disposition = response.headers.get('content-disposition') || '';
                    const filenameMatch = disposition.match(/filename=\"?([^"]+)\"?/i);
                    link.href = objectUrl;
                    link.download = filenameMatch?.[1] || (level === 'single' ? 'psle_scoresheet.pdf' : 'psle_scoresheets.zip');
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(objectUrl);

                    this.scoresheetMessage = `${level.charAt(0).toUpperCase() + level.slice(1)} scoresheet export is ready.`;
                    this.scoresheetMessageType = 'success';
                } catch (error) {
                    this.scoresheetMessage = error.message || 'Failed to export the requested scoresheet.';
                    this.scoresheetMessageType = 'error';
                    this.showMessage(this.scoresheetMessage, 'error');
                } finally {
                    this.scoresheetExporting = false;
                }
            },

            async downloadEnteredMarksSheet(level) {
                if (!this.examYear) {
                    this.showMessage('Select an exam year before exporting an entered marks sheet.', 'error');
                    return;
                }

                if (this.scoresheetMode !== 'approved') {
                    this.showMessage('Entered marks sheets are available only for approved or locked PSLE batches. Switch to Official Print first.', 'error');
                    return;
                }

                if (level === 'single' && (!this.selectedSchool || !this.selectedSubject)) {
                    this.showMessage('Select a primary school and one subject before exporting a single entered marks sheet.', 'error');
                    return;
                }

                if (level === 'school' && !this.selectedSchool) {
                    this.showMessage('Select a primary school before exporting the entered school ZIP package.', 'error');
                    return;
                }

                if (level === 'district' && !this.selectedDistrict) {
                    this.showMessage('Select a council before exporting the entered council ZIP package.', 'error');
                    return;
                }

                if (level === 'region' && !this.selectedRegion) {
                    this.showMessage('Select a region before exporting the entered region ZIP package.', 'error');
                    return;
                }

                const url = new URL(
                    level === 'single'
                        ? '/api/mark-entry/psle/reports/entered-marks-pdf'
                        : level === 'school'
                            ? '/api/mark-entry/psle/reports/entered-marks-pdf/school-zip'
                            : level === 'district'
                                ? '/api/mark-entry/psle/reports/entered-marks-pdf/district-zip'
                                : '/api/mark-entry/psle/reports/entered-marks-pdf/region-zip',
                    window.location.origin
                );

                url.searchParams.set('exam_year', this.examYear);
                url.searchParams.set('mode', 'approved');

                if (level === 'single' || level === 'school') {
                    url.searchParams.set('school_id', this.selectedSchool);
                }

                if (level === 'single') {
                    url.searchParams.set('subject_id', this.selectedSubject);
                }

                if (level === 'district') {
                    url.searchParams.set('district_id', this.selectedDistrict);
                }

                if (level === 'region') {
                    url.searchParams.set('region_id', this.selectedRegion);
                }

                this.scoresheetExporting = true;
                this.scoresheetMessage = '';

                try {
                    const response = await fetch(url.toString(), { headers: { Accept: 'application/octet-stream' } });
                    if (!response.ok) {
                        let message = 'Failed to generate the requested entered marks export.';
                        try {
                            const payload = await response.json();
                            message = payload.message || message;
                        } catch (_error) {
                            const text = await response.text();
                            if (text) {
                                message = text;
                            }
                        }
                        throw new Error(message);
                    }

                    const blob = await response.blob();
                    const objectUrl = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    const disposition = response.headers.get('content-disposition') || '';
                    const filenameMatch = disposition.match(/filename=\"?([^"]+)\"?/i);
                    link.href = objectUrl;
                    link.download = filenameMatch?.[1] || (level === 'single' ? 'psle_entered_marks_sheet.pdf' : 'psle_entered_marks_sheets.zip');
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(objectUrl);

                    this.scoresheetMessage = `${level.charAt(0).toUpperCase() + level.slice(1)} entered marks export is ready.`;
                    this.scoresheetMessageType = 'success';
                } catch (error) {
                    this.scoresheetMessage = error.message || 'Failed to export the requested entered marks sheet.';
                    this.scoresheetMessageType = 'error';
                    this.showMessage(this.scoresheetMessage, 'error');
                } finally {
                    this.scoresheetExporting = false;
                }
            },

            showMessage(message, type) {
                if (window.showToast) {
                    window.showToast(message, type);
                    return;
                }

                alert(message);
            },
        };
    }
</script>
@endsection
