@extends('layout')

@section('content')
@include('registration.partials.theme')
<div class="registration-shell">
    <div class="registration-page-stack">
    @include('registration.partials.header', [
        'kicker' => 'Exam Setup Workspace',
        'title' => 'ACSEE Management',
        'subtitle' => 'Configure ACSEE subjects, combinations, and candidate-facing structures from one coordinated administration workspace.',
        'highlights' => [
            ['icon' => 'fas fa-book-open', 'text' => 'Subject administration'],
            ['icon' => 'fas fa-layer-group', 'text' => 'Combination management'],
            ['icon' => 'fas fa-users', 'text' => 'Candidate visibility'],
        ],
        'noteTitle' => 'Module Scope',
        'noteText' => 'ACSEE carries the most complex exam structure in the system, so this page is organized around subjects, combinations, and candidate operations.',
    ])

    <!-- ACSEE Component -->
    <div x-data="acseeManager()" x-init="init()" class="space-y-6">
        <!-- Tabs Navigation -->
        <div class="registration-surface overflow-hidden">
            <div class="flex border-b border-gray-200">
                <button @click="activeTab = 'subjects'" :class="activeTab === 'subjects' ? 'bg-blue-50 border-b-2 border-blue-600 text-blue-600' : 'text-gray-600'" class="flex-1 py-4 px-6 font-medium transition-colors">Subjects</button>
                <button @click="activeTab = 'combinations'" :class="activeTab === 'combinations' ? 'bg-blue-50 border-b-2 border-blue-600 text-blue-600' : 'text-gray-600'" class="flex-1 py-4 px-6 font-medium transition-colors">Combinations</button>
                <button @click="activeTab = 'candidates'" :class="activeTab === 'candidates' ? 'bg-blue-50 border-b-2 border-blue-600 text-blue-600' : 'text-gray-600'" class="flex-1 py-4 px-6 font-medium transition-colors" data-testid="candidates-tab">Candidates</button>
            </div>
        </div>

        <!-- SUBJECTS TAB -->
        <div x-show="activeTab === 'subjects'" class="space-y-6">
            <div class="registration-surface registration-toolbar-card">
                <div class="flex gap-4 items-center">
                    <input x-model="subjectSearch" @input="filterSubjects()" type="text" placeholder="Search subjects..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="button" @click="openSubjectModal()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors flex items-center gap-2 font-medium">
                        <i class="fas fa-plus"></i> Add Subject
                    </button>
                </div>
            </div>

            <div class="registration-surface registration-table-card overflow-x-auto">
                <div x-show="loadingSubjects" class="p-6 text-center text-gray-500">
                    <i class="fas fa-spinner animate-spin text-2xl"></i> Loading...
                </div>
                <table x-show="!loadingSubjects" class="w-full">
                    <thead class="bg-gray-100 border-b-2 border-gray-300">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Category</th>
                            <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 uppercase">Papers</th>
                            <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 uppercase">Practical</th>
                            <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 uppercase">Project</th>
                            <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 uppercase">Max Marks</th>
                            <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 uppercase">Active</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Description</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template x-for="subject in filteredSubjects" :key="subject.id">
                            <tr class="hover:bg-blue-100 transition-colors">
                                <td class="px-6 py-4 text-sm font-mono text-gray-800" x-text="subject.code"></td>
                                <td class="px-6 py-4 text-sm text-gray-800 font-medium" x-text="subject.name"></td>
                                <td class="px-6 py-4 text-sm text-gray-600" x-text="subject.category || '-'"></td>
                                <td class="px-6 py-4 text-sm text-gray-600 text-center" x-text="subject.written_papers || '-'"></td>
                                <td class="px-6 py-4 text-sm text-gray-600 text-center">
                                    <span x-show="subject.has_practical" class="inline-flex items-center justify-center w-6 h-6 bg-green-100 text-green-700 rounded-full text-xs font-bold">✓</span>
                                    <span x-show="!subject.has_practical" class="text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 text-center">
                                    <span x-show="subject.has_project" class="inline-flex items-center justify-center w-6 h-6 bg-green-100 text-green-700 rounded-full text-xs font-bold">✓</span>
                                    <span x-show="!subject.has_project" class="text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 text-center font-mono" x-text="subject.max_marks || '-'"></td>
                                <td class="px-6 py-4 text-sm text-gray-600 text-center">
                                    <span x-show="subject.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                    <span x-show="!subject.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600" x-text="(subject.description || '-').substring(0, 40)"></td>
                                <td class="px-6 py-4 text-sm space-x-3">
                                    <button @click="openEditSubjectModal(subject)" class="inline-flex items-center justify-center w-8 h-8 text-blue-600 hover:text-blue-900 hover:bg-blue-50 rounded transition-colors">
                                        <i class="fas fa-edit text-sm"></i>
                                    </button>
                                    <button @click="deleteSubject(subject.id)" class="inline-flex items-center justify-center w-8 h-8 text-red-600 hover:text-red-900 hover:bg-red-50 rounded transition-colors">
                                        <i class="fas fa-trash text-sm"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredSubjects.length === 0 && !loadingSubjects">
                            <td colspan="10" class="px-6 py-8 text-center text-gray-500">
                                No subjects found. <button @click="openSubjectModal()" class="text-blue-600 hover:underline">Add one now</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- COMBINATIONS TAB -->
        <div x-show="activeTab === 'combinations'" class="space-y-6">
            <div class="registration-surface registration-toolbar-card">
                <div class="flex gap-4 items-center">
                    <input x-model="combinationSearch" @input="filterCombinations()" type="text" placeholder="Search combinations..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="button" @click="openCombinationModal()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors flex items-center gap-2 font-medium">
                        <i class="fas fa-plus"></i> Add Combination
                    </button>
                </div>
            </div>

            <div class="registration-surface registration-table-card overflow-x-auto">
                <div x-show="loadingCombinations" class="p-6 text-center text-gray-500">
                    <i class="fas fa-spinner animate-spin text-2xl"></i> Loading...
                </div>
                <table x-show="!loadingCombinations" class="w-full">
                    <thead class="bg-gray-100 border-b-2 border-gray-300">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Category</th>
                            <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 uppercase">Subject Count</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Subjects</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Description</th>
                            <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 uppercase">Active</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template x-for="combination in filteredCombinations" :key="combination.id">
                            <tr class="hover:bg-blue-100 transition-colors">
                                <td class="px-6 py-4 text-sm font-mono text-gray-800" x-text="combination.code"></td>
                                <td class="px-6 py-4 text-sm text-gray-600" x-text="combination.category || '-'"></td>
                                <td class="px-6 py-4 text-sm text-gray-600 text-center font-semibold" x-text="combination.subject_count || 0"></td>
                                <td class="px-6 py-4 text-sm text-gray-600" x-text="combination.subject_codes || combination.subjects || '-'"></td>
                                <td class="px-6 py-4 text-sm text-gray-600" x-text="(combination.description || '-').substring(0, 40)"></td>
                                <td class="px-6 py-4 text-sm text-gray-600 text-center">
                                    <span x-show="combination.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                    <span x-show="!combination.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>
                                </td>
                                <td class="px-6 py-4 text-sm space-x-3">
                                    <button type="button" @click.prevent="openEditCombinationModal(combination)" class="inline-flex items-center justify-center w-8 h-8 text-blue-600 hover:text-blue-900 hover:bg-blue-50 rounded transition-colors">
                                        <i class="fas fa-edit text-sm"></i>
                                    </button>
                                    <button type="button" @click.prevent="deleteCombination(combination.id)" class="inline-flex items-center justify-center w-8 h-8 text-red-600 hover:text-red-900 hover:bg-red-50 rounded transition-colors">
                                        <i class="fas fa-trash text-sm"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredCombinations.length === 0 && !loadingCombinations">
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                No combinations found. <button type="button" @click="openCombinationModal()" class="text-blue-600 hover:underline">Add one now</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CANDIDATES TAB (READ-ONLY) -->
         <div x-show="activeTab === 'candidates'" class="space-y-6">
             <!-- Search and Filter Section -->
             <div class="registration-surface registration-toolbar-card flex gap-4 items-center flex-wrap">
                 <input x-model="candidateSearch" @input="filterAcseeCandicates()" type="text" placeholder="Search candidates..." class="flex-1 px-4 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm text-gray-700 placeholder-gray-400 min-w-64">
                 <div class="flex gap-2 items-center">
                     <label class="text-sm font-medium text-gray-700">Candidate Type:</label>
                     <select x-model="candidateTypeFilter" @change="applyCandidateTypeFilter()" class="px-3 py-2 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                         <option value="ALL">All</option>
                         <option value="SCHOOL">School</option>
                         <option value="PRIVATE">Private</option>
                     </select>
                 </div>
                 <button @click="openCandidateToolsModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium flex items-center gap-2 transition-colors text-sm whitespace-nowrap shadow-sm shadow-blue-200/80" data-testid="bulk-import-button">
                     <i class="fas fa-wrench"></i> Candidate Tools
                     <i class="fas fa-arrow-up-right-from-square text-xs opacity-80"></i>
                 </button>
             </div>

            <div
                x-show="candidateToolsModalOpen"
                class="fixed inset-0 z-[9998] flex items-center justify-center bg-slate-950/55 p-4"
                style="display: none;"
                @click.self="closeCandidateToolsModal()"
                @keydown.escape.window="closeCandidateToolsModal()"
                x-transition.opacity
            >
                <div class="w-full max-w-3xl overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-2xl shadow-slate-900/20" x-transition>
                    <div class="relative overflow-hidden border-b border-slate-200 bg-gradient-to-r from-slate-900 via-blue-900 to-emerald-800 px-6 py-6 text-white">
                        <div class="absolute inset-y-0 right-0 w-56 bg-[radial-gradient(circle_at_top_right,_rgba(255,255,255,0.16),_transparent_68%)]"></div>
                        <div class="relative flex items-start justify-between gap-4">
                            <div>
                                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-white/80">
                                    <i class="fas fa-user-graduate text-[0.7rem] text-amber-300"></i>
                                    ACSEE Candidate Tools
                                </span>
                                <h2 class="mt-4 text-2xl font-bold tracking-tight text-white">Candidate import and export workspace</h2>
                                <p class="mt-2 max-w-2xl text-sm leading-6 text-white/80">Open the existing bulk import workflow or export the ACSEE candidate list from one cleaner action panel.</p>
                            </div>
                            <button @click="closeCandidateToolsModal()" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/15 bg-white/10 text-lg text-white/80 transition hover:bg-white/15 hover:text-white" type="button" aria-label="Close candidate tools">&times;</button>
                        </div>
                    </div>
                    <div class="grid gap-4 bg-slate-50 p-6 md:grid-cols-2">
                        <button type="button" @click="launchAcseeCandidateExport()" class="group flex h-full flex-col rounded-3xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-100/70">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700"><i class="fas fa-file-excel text-lg"></i></span>
                            <h3 class="mt-5 text-base font-bold text-slate-900">Export Excel</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Export the current ACSEE candidate list in spreadsheet format for offline analysis.</p>
                        </button>
                        <button type="button" @click="launchAcseeBulkImport()" class="group flex h-full flex-col rounded-3xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-100/70">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-blue-700"><i class="fas fa-file-import text-lg"></i></span>
                            <h3 class="mt-5 text-base font-bold text-slate-900">Bulk Import CSV</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Open the existing ACSEE bulk import modal with school/private template options and review flow.</p>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Candidates Table (Read-Only) -->
            <div class="registration-surface registration-table-card overflow-x-auto" data-testid="candidates-table">
                <div x-show="loadingAcseeCandicates" class="p-6 text-center text-gray-500">
                    <i class="fas fa-spinner animate-spin text-2xl"></i> Loading candidates...
                </div>
                <table x-show="!loadingAcseeCandicates" class="w-full">
                    <thead class="bg-gray-100 border-b-2 border-gray-300">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Index Number</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Full Name</th>
                            <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 uppercase">Sex</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Combination</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Allocated Subjects</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">School</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template x-for="candidate in acseeCandicates" :key="candidate.id">
                            <tr class="hover:bg-blue-100 transition-colors">
                                 <td class="px-6 py-4 text-sm font-mono text-gray-800" x-text="candidate.candidate_id || candidate.id"></td>
                                <td class="px-6 py-4 text-sm text-gray-800 font-medium" x-text="candidate.full_name"></td>
                                <td class="px-6 py-4 text-sm text-gray-600 text-center" x-text="candidate.gender === 'M' ? 'M' : candidate.gender === 'F' ? 'F' : '-'"></td>
                                <td class="px-6 py-4 text-sm text-gray-600 font-mono" x-text="candidate.combination || '-'"></td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <span x-text="candidate.allocated_subjects.length > 0 ? candidate.allocated_subjects.map(s => s.code).join(', ') : '-'"></span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600" x-text="candidate.school_name || '-'"></td>
                                <td class="px-6 py-4 text-sm space-x-2">
                                    <button 
                                        @click="openAllocationModal(candidate)"
                                        class="inline-flex items-center justify-center w-8 h-8 text-green-600 hover:text-green-900 hover:bg-green-50 rounded transition-colors"
                                        title="Allocate Subjects"
                                    >
                                        <i class="fas fa-plus text-sm"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="acseeCandicates.length === 0 && !loadingAcseeCandicates">
                             <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                 No ACSEE candidates found. Candidates are managed in <a href="/registration/candidates" class="text-blue-600 hover:underline">registration/candidates</a>
                             </td>
                         </tr>
                    </tbody>
                </table>
            </div>

            <!-- Professional Pagination -->
             <div class="border-t border-slate-200 bg-gradient-to-r from-slate-50 via-white to-slate-50">
                 <div class="px-6 py-5 space-y-4">
                     <!-- Row 1: Info and Per-Page Selector -->
                     <div class="flex items-center justify-between gap-4 flex-wrap">
                         <div class="flex flex-wrap items-center gap-3 text-sm text-slate-600">
                             <span x-show="pagination.total > 0">
                                 <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5">
                                     <i class="fas fa-table-list text-xs text-slate-400"></i>
                                     <span>Showing <span class="font-semibold text-slate-800" x-text="pagination.from"></span> to <span class="font-semibold text-slate-800" x-text="pagination.to"></span> of <span class="font-semibold text-slate-800" x-text="pagination.total"></span> candidates</span>
                                 </span>
                             </span>
                             <span x-show="pagination.total === 0" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-gray-500"><i class="fas fa-circle-info text-xs"></i>No candidates found</span>
                         </div>
                         
                         <div class="flex items-center gap-3">
                             <label class="text-sm font-medium text-gray-700">Per page:</label>
                             <select x-model.number="pagination.perPage" @change="pagination.page = 1; loadAcseeCandicates()" class="px-3 py-1.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" :disabled="loadingAcseeCandicates">
                                 <template x-for="option in perPageOptions" :key="option">
                                     <option :value="option" x-text="`${option} rows`"></option>
                                 </template>
                             </select>
                         </div>
                     </div>

                     <!-- Row 2: Pagination Controls (Hidden on small screens) -->
                     <div class="hidden md:flex items-center justify-between gap-2">
                         <!-- Navigation Buttons -->
                         <div class="flex gap-1 items-center">
                             <button 
                                 @click="pagination.page > 1 && (pagination.page--, loadAcseeCandicates())" 
                                 :disabled="pagination.page <= 1 || loadingAcseeCandicates"
                                 class="inline-flex h-10 items-center rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40"
                                 title="Go to first page"
                                 aria-label="First page">
                                 <i class="fas fa-step-backward"></i>
                             </button>
                             <button 
                                 @click="pagination.page > 1 && (pagination.page--, loadAcseeCandicates())" 
                                 :disabled="pagination.page <= 1 || loadingAcseeCandicates"
                                 class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40"
                                 title="Previous page"
                                 aria-label="Previous page">
                                 <i class="fas fa-chevron-left"></i>
                             </button>
                         </div>

                         <!-- Page Numbers with Ellipsis -->
                         <div class="flex gap-2 items-center flex-wrap justify-center rounded-2xl border border-slate-200 bg-white px-3 py-3 shadow-sm">
                             <template x-for="page in (() => {
                                 const pages = [];
                                 const current = pagination.page;
                                 const last = pagination.lastPage;
                                 const delta = 2;
                                 const range = { start: Math.max(2, current - delta), end: Math.min(last - 1, current + delta) };
                                 
                                 // First page
                                 pages.push({ num: 1, type: 'number' });
                                 
                                 // Left ellipsis
                                 if (range.start > 2) pages.push({ type: 'ellipsis' });
                                 
                                 // Range pages
                                 for (let i = range.start; i <= range.end; i++) {
                                     pages.push({ num: i, type: 'number' });
                                 }
                                 
                                 // Right ellipsis
                                 if (range.end < last - 1) pages.push({ type: 'ellipsis' });
                                 
                                 // Last page
                                 if (last > 1) pages.push({ num: last, type: 'number' });
                                 
                                 return pages;
                             })()" :key="`${page.num}-${page.type}`">
                                 <template x-if="page.type === 'number'">
                                     <button 
                                         @click="pagination.page = page.num; loadAcseeCandicates()"
                                         :class="pagination.page === page.num ? 'bg-blue-600 text-white shadow-md shadow-blue-200/80' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                                         class="min-w-[2.5rem] rounded-xl px-3 py-2 text-sm font-semibold transition-colors"
                                         :disabled="loadingAcseeCandicates"
                                         :aria-current="pagination.page === page.num ? 'page' : undefined"
                                         x-text="page.num">
                                     </button>
                                 </template>
                                 <template x-if="page.type === 'ellipsis'">
                                     <span class="px-2 text-gray-400">…</span>
                                 </template>
                             </template>
                         </div>

                         <!-- Navigation Buttons (Right) -->
                         <div class="flex gap-1 items-center">
                             <button 
                                 @click="pagination.page < pagination.lastPage && (pagination.page++, loadAcseeCandicates())" 
                                 :disabled="pagination.page >= pagination.lastPage || loadingAcseeCandicates"
                                 class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40"
                                 title="Next page"
                                 aria-label="Next page">
                                 <i class="fas fa-chevron-right"></i>
                             </button>
                             <button 
                                 @click="pagination.page < pagination.lastPage && (pagination.page = pagination.lastPage, loadAcseeCandicates())" 
                                 :disabled="pagination.page >= pagination.lastPage || loadingAcseeCandicates"
                                 class="inline-flex h-10 items-center rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40"
                                 title="Go to last page"
                                 aria-label="Last page">
                                 <i class="fas fa-step-forward"></i>
                             </button>
                         </div>
                     </div>

                     <!-- Row 3: Jump to Page & Mobile Controls -->
                     <div class="flex items-center justify-between gap-4 flex-wrap">
                         <!-- Jump to Page Input -->
                         <div class="flex items-center gap-2">
                             <label class="text-sm font-medium text-gray-700">Go to page:</label>
                             <input 
                                 x-model.number="jumpToPageInput" 
                                 @keyup.enter="jumpToPageInput > 0 && jumpToPageInput <= pagination.lastPage && (pagination.page = jumpToPageInput, loadAcseeCandicates())"
                                 type="number" 
                                 min="1" 
                                 :max="pagination.lastPage"
                                 placeholder="1"
                                 class="w-16 px-2 py-1.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                                 :disabled="loadingAcseeCandicates || pagination.lastPage === 1">
                             <button 
                                 @click="jumpToPageInput > 0 && jumpToPageInput <= pagination.lastPage && (pagination.page = jumpToPageInput, loadAcseeCandicates())"
                                 :disabled="!jumpToPageInput || jumpToPageInput < 1 || jumpToPageInput > pagination.lastPage || loadingAcseeCandicates"
                                 class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors text-sm font-medium">
                                 Go
                             </button>
                         </div>

                         <!-- Mobile Page Display -->
                         <div class="md:hidden inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700">
                             <i class="fas fa-layer-group text-xs"></i>
                             <span>Page <span class="font-semibold" x-text="pagination.page"></span> of <span class="font-semibold" x-text="pagination.lastPage"></span></span>
                         </div>

                         <!-- Loading Indicator -->
                         <div x-show="loadingAcseeCandicates" class="text-sm text-gray-500">
                             <i class="fas fa-spinner animate-spin"></i> Loading...
                         </div>
                     </div>
                 </div>
             </div>
        </div>

        <!-- SUBJECT MODAL -->
        <div x-show="subjectModalOpen || editingSubjectId" class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/55 p-4" @mousedown.self="subjectModalOpen = false; editingSubjectId = null;" x-transition style="display: none;">
            <div class="registration-modal-shell max-w-2xl" x-transition>
                <div class="registration-modal-header">
                    <div class="registration-modal-header-content">
                        <div>
                            <span class="registration-modal-kicker"><i class="fas fa-book text-amber-300"></i>ACSEE Subject</span>
                            <h2 class="registration-modal-title" x-text="editingSubjectId ? 'Edit Subject' : 'Add Subject'"></h2>
                            <p class="registration-modal-subtitle">Configure subject identity, category, paper count, and optional practical or project components.</p>
                        </div>
                        <button @click="subjectModalOpen = false; editingSubjectId = null;" class="registration-modal-close" aria-label="Close subject modal">&times;</button>
                    </div>
                </div>

                <form @submit.prevent="saveSubject()">
                    <div class="registration-modal-body">
                    <div class="registration-modal-panel p-6 space-y-4 max-h-[60vh] overflow-y-auto">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Code *</label>
                            <input x-model="subjectForm.code" @input="syncSubjectDescription()" type="text" placeholder="e.g., ENG" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Name *</label>
                            <input x-model="subjectForm.name" @input="syncSubjectDescription()" type="text" placeholder="e.g., English" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Category *</label>
                            <select x-model="subjectForm.category" @change="syncSubjectDescription()" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Category</option>
                                <option value="SCIENCE">SCIENCE</option>
                                <option value="ARTS">ARTS</option>
                                <option value="BUSINESS">BUSINESS</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Written Papers</label>
                            <input x-model.number="subjectForm.written_papers" @input="syncSubjectDescription()" type="number" placeholder="e.g., 2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center gap-2">
                                <input x-model="subjectForm.has_practical" @change="syncSubjectDescription()" type="checkbox" class="w-4 h-4 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                                <span class="text-sm font-medium text-gray-700">Has Practical</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input x-model="subjectForm.has_project" @change="syncSubjectDescription()" type="checkbox" class="w-4 h-4 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                                <span class="text-sm font-medium text-gray-700">Has Project</span>
                            </label>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Max Marks</label>
                            <input x-model.number="subjectForm.max_marks" @input="syncSubjectDescription()" type="number" placeholder="e.g., 100" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                            <textarea x-model="subjectForm.description" @input="markSubjectDescriptionManual()" placeholder="Subject description" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 h-16 resize-none"></textarea>
                        </div>
                        <label class="flex items-center gap-2">
                            <input x-model="subjectForm.is_active" type="checkbox" class="w-4 h-4 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Active</span>
                        </label>
                    </div>

                    <div class="registration-modal-actions mt-5">
                        <button type="button" @click="subjectModalOpen = false; editingSubjectId = null;" class="registration-modal-button registration-modal-button-secondary">Cancel</button>
                        <button type="submit" class="registration-modal-button registration-modal-button-primary">Save Subject</button>
                    </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- COMBINATION MODAL -->
        <div x-show="combinationModalOpen || editingCombinationId" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/55 p-4" @mousedown.self="combinationModalOpen = false; editingCombinationId = null;" x-transition style="display: none;">
            <div class="registration-modal-shell max-w-2xl" x-transition>
                <div class="registration-modal-header">
                    <div class="registration-modal-header-content">
                        <div>
                            <span class="registration-modal-kicker"><i class="fas fa-layer-group text-amber-300"></i>ACSEE Combination</span>
                            <h2 class="registration-modal-title" x-text="editingCombinationId ? 'Edit Combination' : 'Add Combination'"></h2>
                            <p class="registration-modal-subtitle">Assemble allowed subject combinations and keep their categories and active state aligned.</p>
                        </div>
                        <button @click="combinationModalOpen = false; editingCombinationId = null;" class="registration-modal-close" aria-label="Close combination modal">&times;</button>
                    </div>
                </div>

                <form @submit.prevent="saveCombination()" class="registration-modal-body">
                    <div class="registration-modal-panel p-6 space-y-4 max-h-[80vh] overflow-y-auto">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Code *</label>
                        <input x-model="combinationForm.code" type="text" placeholder="e.g., SC1" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                        <select x-model="combinationForm.category" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Select Category --</option>
                            <option value="SCIENCE">SCIENCE</option>
                            <option value="ARTS">ARTS</option>
                            <option value="BUSINESS">BUSINESS</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Subjects *</label>
                        <div class="border border-gray-300 rounded-lg p-3 max-h-48 overflow-y-auto space-y-1">
                            <template x-for="subject in subjects" :key="subject.id">
                                <label class="flex items-center gap-2 py-1 px-2 rounded hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" 
                                        :value="subject.code" 
                                        :checked="combinationSelectedSubjects.includes(subject.code)"
                                        @change="
                                            if ($event.target.checked) {
                                                if (!combinationSelectedSubjects.includes(subject.code)) combinationSelectedSubjects.push(subject.code);
                                            } else {
                                                combinationSelectedSubjects = combinationSelectedSubjects.filter(c => c !== subject.code);
                                            }
                                            combinationForm.subjects = combinationSelectedSubjects.join(',');
                                        "
                                        class="w-4 h-4 border border-gray-300 rounded text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700" x-text="subject.code + ' - ' + subject.name"></span>
                                </label>
                            </template>
                            <template x-if="subjects.length === 0">
                                <p class="text-sm text-gray-400 italic">No subjects available. Add subjects first.</p>
                            </template>
                        </div>
                        <p class="text-xs text-gray-500 mt-1" x-text="'Selected: ' + combinationSelectedSubjects.length + ' subject(s)'"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                        <textarea x-model="combinationForm.description" placeholder="Combination description" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 h-16 resize-none"></textarea>
                    </div>
                    <label class="flex items-center gap-2">
                        <input x-model="combinationForm.is_active" type="checkbox" class="w-4 h-4 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">Active</span>
                    </label>

                    <div class="registration-modal-actions">
                        <button type="button" @click="combinationModalOpen = false; editingCombinationId = null;" class="registration-modal-button registration-modal-button-secondary">Cancel</button>
                        <button type="submit" class="registration-modal-button registration-modal-button-primary">Save Combination</button>
                    </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- CANDIDATE MODAL -->
        <div x-show="candidateModalOpen || editingCandidateId" class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/55 p-4" @mousedown.self="candidateModalOpen = false; editingCandidateId = null;" x-transition style="display: none;">
            <div class="registration-modal-shell max-w-2xl" x-transition>
                <div class="registration-modal-header">
                    <div class="registration-modal-header-content">
                        <div>
                            <span class="registration-modal-kicker"><i class="fas fa-user-graduate text-amber-300"></i>ACSEE Candidate</span>
                            <h2 class="registration-modal-title" x-text="editingCandidateId ? 'Edit Candidate' : 'Register Candidate'"></h2>
                            <p class="registration-modal-subtitle">Maintain candidate identity, exam assignment, and school linkage from a single workflow.</p>
                        </div>
                        <button @click="candidateModalOpen = false; editingCandidateId = null;" class="registration-modal-close" aria-label="Close candidate modal">&times;</button>
                    </div>
                </div>

                <form @submit.prevent="saveCandidate()" class="registration-modal-body">
                    <div class="registration-modal-panel p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name *</label>
                        <input x-model="candidateForm.full_name" type="text" placeholder="e.g., John Doe" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Sex *</label>
                        <select x-model="candidateForm.gender" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Sex</option>
                            <option value="M">Male</option>
                            <option value="F">Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Combination</label>
                        <input x-model="candidateForm.combination" type="text" placeholder="e.g., Physics, Chemistry, Biology" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">School *</label>
                        <select x-model="candidateForm.school_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select School</option>
                            <template x-for="school in schools" :key="school.id">
                                <option :value="school.id" x-text="school.name"></option>
                            </template>
                        </select>
                    </div>

                    <div class="registration-modal-actions">
                        <button type="button" @click="candidateModalOpen = false; editingCandidateId = null;" class="registration-modal-button registration-modal-button-secondary">Cancel</button>
                        <button type="submit" class="registration-modal-button registration-modal-button-primary">Save Candidate</button>
                    </div>
                    </div>
                </form>
            </div>
        <!-- ALLOCATION MODAL - TELEPORTED TO BODY TO ESCAPE HIDDEN TAB CONTAINER -->
        <template x-teleport="body">
          <div 
               x-cloak 
               @click.self="closeAllocationModal()" 
               :style="(allocationModalOpen || bulkImportModalOpen) 
                   ? 'display: flex; position: fixed; inset: 0; z-index: 9999;' 
                   : 'display: none;'"
               class="bg-slate-950/55 items-center justify-center p-4" 
               data-testid="bulk-import-modal"
          >
              <div class="registration-modal-shell max-w-5xl" x-transition>
                 <div class="registration-modal-header">
                     <div class="registration-modal-header-content">
                     <div>
                         <span class="registration-modal-kicker">
                             <i class="fas fa-layer-group text-amber-300"></i>
                             <span x-text="bulkImportModalOpen ? 'Bulk Import' : 'Allocation'"></span>
                         </span>
                         <h2 class="registration-modal-title" x-text="bulkImportModalOpen ? 'Bulk Import Allocations' : 'Allocate Subjects'"></h2>
                         <p class="registration-modal-subtitle" x-show="!bulkImportModalOpen" x-text="allocationCandidate ? `${allocationCandidate.full_name} (${allocationCandidate.candidate_id})` : ''"></p>
                         <p class="registration-modal-subtitle" x-show="bulkImportModalOpen">Validate ACSEE allocation CSV files, review issues, and commit subject allocations through one guided workflow.</p>
                     </div>
                     <button @click="closeAllocationModal()" class="registration-modal-close" data-testid="modal-close-button" aria-label="Close allocation modal">&times;</button>
                     </div>
                 </div>

                 <!-- Tab Switcher (for bulk import) -->
                 <div x-show="bulkImportModalOpen" class="flex border-b border-slate-200 px-6 pt-6 bg-white">
                     <button @click="bulkPhase = 'idle'" :class="bulkPhase === 'idle' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600'" class="pb-3 px-4 font-medium transition-colors">Import CSV</button>
                 </div>

                 <div class="registration-modal-body space-y-6">
                     <!-- PHASE 2b: Bulk CSV import UI integrated with Phase 2a backend endpoints:
                         - GET /api/exam-types/acsee/templates/school-allocation.csv
                         - GET /api/exam-types/acsee/templates/private-allocation.csv
                         - POST /api/exam-types/acsee/allocate-from-csv/validate
                         - POST /api/exam-types/acsee/allocate-from-csv/commit
                         - POST /api/exam-types/acsee/allocate-from-csv/download-errors
                     -->

                     <!-- Single Candidate Allocation (shown when allocationModalOpen) -->
                     <div x-show="!bulkImportModalOpen">
                     <!-- Mode Selector -->
                     <div class="flex gap-4 border-b border-gray-200 pb-4">
                        <button 
                            @click="setAllocationMode('template')"
                            :class="allocationMode === 'template' ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800'"
                            class="pb-2 transition-colors"
                        >
                            Apply Combination Template
                        </button>
                        <button 
                            @click="setAllocationMode('manual')"
                            :class="allocationMode === 'manual' ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800'"
                            class="pb-2 transition-colors"
                        >
                            Manual Subject Selection
                        </button>
                    </div>

                    <!-- Exam Year Selection (Common) -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Exam Year *</label>
                        <select x-model="allocationExamYearId" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Exam Year</option>
                            <template x-for="year in allocationExamYears" :key="year.id">
                                <option :value="year.id" x-text="year.year_label"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Mode A: Apply Combination Template -->
                    <div x-show="allocationMode === 'template'" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Combination Template *</label>
                            <select x-model="allocationCombinationId" @change="loadCombinationSubjectsPreview()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Combination</option>
                                <template x-for="combo in allocationCombinations" :key="combo.id">
                                    <option :value="combo.id" x-text="`${combo.code} - ${combo.subject_codes}`"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Preview Subjects -->
                        <div x-show="allocationPreviewSubjects.length > 0">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Subjects in Template:</label>
                            <div class="bg-gray-50 rounded-lg p-4 space-y-2 max-h-48 overflow-y-auto">
                                <template x-for="subject in allocationPreviewSubjects" :key="subject.id">
                                    <div class="flex items-center gap-2 p-2 bg-white rounded border border-gray-200">
                                        <span class="font-mono font-semibold text-blue-600" x-text="subject.code"></span>
                                        <span class="text-gray-800" x-text="subject.name"></span>
                                        <span x-show="subject.code === '111'" class="ml-auto text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">General Studies</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Mode B: Manual Subject Selection -->
                    <div x-show="allocationMode === 'manual'" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Select Subjects *</label>
                            <div class="text-sm text-gray-600 mb-3">
                                <p><strong>Required:</strong> General Studies + at least 3 other subjects</p>
                            </div>
                            <div class="border border-gray-300 rounded-lg p-3 max-h-48 overflow-y-auto space-y-2">
                                <template x-for="subject in allocationAllSubjects" :key="subject.id">
                                    <div class="flex items-center gap-2">
                                        <input 
                                            type="checkbox" 
                                            :id="`subject-${subject.id}`"
                                            :value="subject.id"
                                            x-model.number="allocationSubjectIds"
                                            class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        >
                                        <label :for="`subject-${subject.id}`" class="flex-1 cursor-pointer">
                                            <span class="font-mono font-semibold text-blue-600" x-text="subject.code"></span>
                                            <span class="text-gray-800" x-text="subject.name"></span>
                                            <span x-show="subject.code === '111'" class="ml-2 text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Mandatory</span>
                                        </label>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Allocation Options (Common) -->
                    <div class="border-t border-gray-200 pt-4 space-y-4">
                        <div class="flex items-center gap-2">
                            <input 
                                type="checkbox"
                                id="replaceAllocations"
                                x-model="allocationReplace"
                                class="w-4 h-4 rounded border-gray-300 text-blue-600"
                            >
                            <label for="replaceAllocations" class="text-sm font-medium text-gray-700 cursor-pointer">
                                Replace existing allocations (instead of adding missing only)
                            </label>
                        </div>
                        <div x-show="allocationReplace" class="bg-orange-50 border border-orange-200 rounded-lg p-3">
                            <p class="text-sm text-orange-800">
                                <strong>⚠ Warning:</strong> This will remove all existing subject allocations for this exam year and replace them with the selected subjects.
                            </p>
                        </div>
                    </div>

                    <!-- Validation Messages -->
                    <div x-show="allocationValidationMessages.errors.length > 0" class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="font-semibold text-red-800 mb-2">Validation Errors:</p>
                        <ul class="space-y-1">
                            <template x-for="error in allocationValidationMessages.errors" :key="error">
                                <li class="text-sm text-red-700" x-text="error"></li>
                            </template>
                        </ul>
                    </div>

                    <div x-show="allocationValidationMessages.warnings.length > 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="font-semibold text-yellow-800 mb-2">Warnings:</p>
                        <ul class="space-y-1">
                            <template x-for="warning in allocationValidationMessages.warnings" :key="warning">
                                <li class="text-sm text-yellow-700" x-text="warning"></li>
                            </template>
                        </ul>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-3 pt-4 border-t border-gray-200">
                        <button 
                            type="button" 
                            @click="closeAllocationModal()"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 font-medium transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            type="button"
                            @click="saveAllocation()"
                            :disabled="allocationProcessing || !allocationExamYearId"
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            <span x-show="!allocationProcessing">Save Allocation</span>
                            <span x-show="allocationProcessing" class="flex items-center gap-2">
                                <i class="fas fa-spinner animate-spin"></i> Processing...
                            </span>
                        </button>
                    </div>
                    </div> <!-- End of single-candidate allocation section -->

                    <!-- BULK IMPORT LOADING SPINNER -->
                     <div x-show="bulkLoadingContexts" class="registration-modal-note">
                         <div class="registration-modal-note-icon">
                         <i class="fas fa-spinner animate-spin text-blue-600 text-lg"></i>
                         </div>
                         <div>
                             <strong>Loading context</strong>
                             <p>Fetching exam years and allocation context before the import workflow can continue.</p>
                         </div>
                     </div>

                    <!-- BULK IMPORT ERROR MESSAGE -->
                     <div x-show="bulkErrorMessage && !bulkLoadingContexts" class="bg-red-50 border border-red-200 rounded-lg p-4">
                         <p class="text-sm text-red-800" x-text="bulkErrorMessage"></p>
                     </div>

                    <!-- BULK IMPORT SECTION (shown when bulkImportModalOpen) -->
                     <div x-show="bulkImportModalOpen" class="space-y-6">
                        <!-- Template Download Section -->
                        <div class="registration-modal-note">
                            <div class="registration-modal-note-icon">
                                <i class="fas fa-download"></i>
                            </div>
                            <div>
                            <h3 class="font-semibold text-blue-900 mb-3">1. Download Template</h3>
                            <div class="flex gap-3">
                                <button 
                                    @click="downloadTemplate('SCHOOL')"
                                    class="registration-modal-button registration-modal-button-primary text-sm flex-1"
                                    data-testid="download-school-template"
                                >
                                    <i class="fas fa-download"></i> School Template
                                </button>
                                <button 
                                    @click="downloadTemplate('PRIVATE')"
                                    class="registration-modal-button text-sm flex-1 !bg-gradient-to-br !from-purple-600 !to-violet-700 !text-white !shadow-[0_16px_28px_rgba(124,58,237,0.2)]"
                                    data-testid="download-private-template"
                                >
                                    <i class="fas fa-download"></i> Private Template
                                </button>
                            </div>
                            </div>
                        </div>

                        <!-- Import Mode Selection -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">2. Select Import Mode</label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" x-model="bulkImportMode" value="SCHOOL" class="w-4 h-4 rounded">
                                    <span class="text-sm text-gray-700">School (Combination-based)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" x-model="bulkImportMode" value="PRIVATE" class="w-4 h-4 rounded">
                                    <span class="text-sm text-gray-700">Private (Subject Codes)</span>
                                </label>
                            </div>
                        </div>

                        <!-- File Upload -->
                         <div>
                             <label class="block text-sm font-semibold text-gray-700 mb-2">3. Upload CSV File</label>
                             <div class="registration-dropzone">
                                 <input 
                                     type="file" 
                                     accept=".csv"
                                     @change="handleBulkFileUpload($event)"
                                     class="hidden"
                                     x-ref="bulkFileInput"
                                     id="bulkCsvFile"
                                     data-testid="bulk-csv-file"
                                     :disabled="bulkLoadingContexts"
                                 >
                                 <button 
                                     @click="$refs.bulkFileInput.click()"
                                     :disabled="bulkLoadingContexts"
                                     class="registration-modal-button registration-modal-button-secondary disabled:opacity-50 disabled:cursor-not-allowed"
                                 >
                                    <i class="fas fa-folder-open"></i> Select CSV File
                                </button>
                                <p class="text-sm text-slate-600 mt-3" x-text="bulkUploadedFileName ? `Selected: ${bulkUploadedFileName} (${(bulkUploadedFileSize / 1024).toFixed(2)} KB)` : 'No file selected'"></p>
                            </div>
                        </div>

                        <!-- Exam Year Selection -->
                         <div>
                             <label class="block text-sm font-semibold text-gray-700 mb-2">4. Select Exam Year *</label>
                             <select 
                                 x-model="bulkExamYearId" 
                                 @change.prevent="bulkExamYearId = String(bulkExamYearId)"
                                 :disabled="bulkLoadingContexts || !allocationExamYears || allocationExamYears.length === 0"
                                 class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed" 
                                 data-testid="bulk-exam-year-select"
                             >
                                 <option value="">-- Select Exam Year --</option>
                                 <template x-for="year in allocationExamYears || []" :key="year.id">
                                     <option :value="String(year.id)" x-text="year.year_label"></option>
                                 </template>
                             </select>
                             <p x-show="!allocationExamYears || allocationExamYears.length === 0" class="text-sm text-red-600 mt-2">
                                 No exam years found. Please create an exam year first.
                             </p>
                         </div>

                        <!-- Candidate Type Filter (for bulk import context) -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">5. Candidate Type Filter</label>
                            <select x-model="bulkCandidateTypeFilter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="ALL">All Candidates</option>
                                <option value="SCHOOL">School Only</option>
                                <option value="PRIVATE">Private Only</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Filter will be applied during import validation</p>
                        </div>

                        <!-- Replace Allocations Checkbox -->
                        <div class="registration-modal-panel bg-orange-50/90 border-orange-200 p-4">
                            <div class="flex items-start gap-3">
                                <input 
                                    type="checkbox" 
                                    id="bulkReplace"
                                    x-model="bulkReplaceAllocations"
                                    class="w-4 h-4 rounded mt-1"
                                    data-testid="bulk-replace-checkbox"
                                >
                                <div class="flex-1">
                                    <label for="bulkReplace" class="text-sm font-medium text-gray-700 cursor-pointer">Replace existing allocations</label>
                                    <p class="text-xs text-orange-700 mt-1" data-testid="replace-warning">
                                        <strong>⚠ Warning:</strong> If checked, ALL existing allocations for the selected exam year will be deleted and replaced with data from this import.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Validation Phase UI -->
                         <div x-show="bulkPhase !== 'idle'" class="space-y-4" data-testid="validation-phase">
                             <!-- Validation Report -->
                             <div x-show="bulkValidationReport" class="registration-modal-panel p-4" data-testid="validation-report">
                                <h3 class="font-semibold text-gray-800 mb-3">Validation Report</h3>
                                <div class="registration-modal-stats !grid-cols-3 mb-4">
                                    <div class="registration-modal-stat">
                                        <div class="registration-modal-stat-value text-blue-600" x-text="(bulkValidationReport && bulkValidationReport.total_rows) || 0"></div>
                                        <div class="registration-modal-stat-label">Total Rows</div>
                                    </div>
                                    <div class="registration-modal-stat">
                                        <div class="registration-modal-stat-value text-green-600" x-text="(bulkValidationReport && bulkValidationReport.valid_count) || 0"></div>
                                        <div class="registration-modal-stat-label">Valid</div>
                                    </div>
                                    <div class="registration-modal-stat">
                                        <div class="registration-modal-stat-value text-red-600" x-text="(bulkValidationReport && bulkValidationReport.invalid_count) || 0"></div>
                                        <div class="registration-modal-stat-label">Invalid</div>
                                    </div>
                                </div>

                                <!-- Error Table -->
                                <div x-show="bulkValidationReport && bulkValidationReport.invalid_count > 0" class="mt-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="font-semibold text-sm text-red-800">Errors Found</h4>
                                        <button
                                            @click="downloadBulkErrorReport()"
                                            class="inline-flex items-center gap-2 px-3 py-1 bg-red-100 text-red-700 rounded text-sm hover:bg-red-200 transition-colors"
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
                                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Index No.</th>
                                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Error(s)</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200">
                                                    <template x-for="(error, idx) in bulkLastErrors.slice(0, 10)" :key="idx">
                                                        <tr class="hover:bg-gray-50">
                                                            <td class="px-3 py-2 text-gray-600 text-xs" x-text="error.row_number"></td>
                                                            <td class="px-3 py-2 text-gray-600 font-mono text-xs" x-text="error.index_number || '-'"></td>
                                                            <td class="px-3 py-2">
                                                                <template x-for="msg in error.error_messages" :key="msg">
                                                                    <span class="inline-block bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-medium mr-1 mb-1" x-text="msg"></span>
                                                                </template>
                                                            </td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div x-show="bulkLastErrors.length > 10" class="bg-gray-50 px-4 py-2 text-xs text-gray-600 border-t border-gray-200">
                                            Showing 10 of <span x-text="bulkLastErrors.length"></span> errors (download file to see all)
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Commit Report (after commit) -->
                            <div x-show="bulkCommitReport" class="registration-modal-panel p-4" data-testid="commit-report">
                                <h3 class="font-semibold text-gray-800 mb-3">Import Complete</h3>
                                <div class="registration-modal-stats !grid-cols-3 mb-4">
                                    <div class="registration-modal-stat">
                                        <div class="registration-modal-stat-value text-green-600" x-text="(bulkCommitReport && bulkCommitReport.success_count) || 0"></div>
                                        <div class="registration-modal-stat-label">Successful</div>
                                    </div>
                                    <div class="registration-modal-stat">
                                        <div class="registration-modal-stat-value text-yellow-600" x-text="(bulkCommitReport && bulkCommitReport.skipped_count) || 0"></div>
                                        <div class="registration-modal-stat-label">Skipped</div>
                                    </div>
                                    <div class="registration-modal-stat">
                                        <div class="registration-modal-stat-value text-red-600" x-text="(bulkCommitReport && bulkCommitReport.failed_count) || 0"></div>
                                        <div class="registration-modal-stat-label">Failed</div>
                                    </div>
                                </div>

                                <!-- Affected Candidates -->
                                <div x-show="bulkCommitReport && bulkCommitReport.affected_candidates && bulkCommitReport.affected_candidates.length > 0" class="mt-4">
                                    <h4 class="font-semibold text-sm text-gray-800 mb-2">Affected Candidates</h4>
                                    <div class="max-h-48 overflow-y-auto">
                                        <template x-for="candidate in (bulkCommitReport && bulkCommitReport.affected_candidates) || []" :key="candidate.id">
                                            <div class="text-xs p-2 bg-white rounded border border-gray-200">
                                                <span class="font-mono text-blue-600" x-text="candidate.index_number"></span>
                                                <span class="text-gray-700" x-text="`(${candidate.full_name})`"></span>
                                                <span class="text-gray-600" x-text="`${candidate.allocation_count} subject(s)`"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Commit Errors -->
                                <div x-show="bulkCommitReport && bulkCommitReport.failed_count > 0" class="mt-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="font-semibold text-sm text-red-800">Errors</h4>
                                        <button
                                            @click="downloadBulkErrorReport()"
                                            class="inline-flex items-center gap-2 px-3 py-1 bg-red-100 text-red-700 rounded text-sm hover:bg-red-200 transition-colors"
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
                                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Index No.</th>
                                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Error(s)</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200">
                                                    <template x-for="(error, idx) in bulkLastErrors.slice(0, 5)" :key="idx">
                                                        <tr class="hover:bg-gray-50">
                                                            <td class="px-3 py-2 text-gray-600 text-xs" x-text="error.row_number"></td>
                                                            <td class="px-3 py-2 text-gray-600 font-mono text-xs" x-text="error.index_number || '-'"></td>
                                                            <td class="px-3 py-2">
                                                                <template x-for="msg in error.error_messages" :key="msg">
                                                                    <span class="inline-block bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-medium mr-1 mb-1" x-text="msg"></span>
                                                                </template>
                                                            </td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div x-show="bulkLastErrors.length > 5" class="bg-gray-50 px-4 py-2 text-xs text-gray-600 border-t border-gray-200">
                                            Showing 5 of <span x-text="bulkLastErrors.length"></span> errors (download file to see all)
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Error Messages -->
                            <div x-show="bulkErrorMessage" class="registration-modal-panel bg-red-50/90 border-red-200 p-4" data-testid="error-message">
                                <p class="text-sm text-red-800" x-text="bulkErrorMessage"></p>
                            </div>
                            <div x-show="bulkSuccessMessage" class="registration-modal-panel bg-green-50/90 border-green-200 p-4">
                                <p class="text-sm text-green-800" x-text="bulkSuccessMessage"></p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="registration-modal-actions">
                            <button 
                                type="button"
                                @click="closeAllocationModal()"
                                class="registration-modal-button registration-modal-button-secondary"
                            >
                                Close
                            </button>
                            <button 
                                type="button"
                                @click="validateBulkCSV()"
                                x-show="bulkPhase === 'idle'"
                                :disabled="!bulkUploadedFile || !bulkExamYearId || bulkProcessing || bulkLoadingContexts"
                                class="registration-modal-button registration-modal-button-primary disabled:opacity-50 disabled:cursor-not-allowed"
                                data-testid="validate-button"
                            >
                                <span x-show="!bulkProcessing">Validate CSV</span>
                                <span x-show="bulkProcessing" class="flex items-center gap-2">
                                    <i class="fas fa-spinner animate-spin"></i> Validating...
                                </span>
                            </button>
                            <button 
                                type="button"
                                @click="commitBulkCSV()"
                                x-show="bulkPhase === 'reviewing' && bulkValidationReport && bulkValidationReport.invalid_count === 0"
                                :disabled="bulkProcessing || bulkLoadingContexts"
                                class="registration-modal-button registration-modal-button-success disabled:opacity-50 disabled:cursor-not-allowed"
                                data-testid="commit-button"
                            >
                                <span x-show="!bulkProcessing">Commit Import</span>
                                <span x-show="bulkProcessing" class="flex items-center gap-2">
                                    <i class="fas fa-spinner animate-spin"></i> Committing...
                                </span>
                            </button>
                        </div>
                    </div> <!-- End of bulk import section -->
                </div>
            </div>
        </div>
        </template> <!-- End of x-teleport -->
    </div>
</div>

<script>
window.acseeManager = function () {
    return {
        activeTab: 'subjects',
        
        // Subjects
        subjects: [],
        filteredSubjects: [],
        subjectSearch: '',
        loadingSubjects: false,
        subjectModalOpen: false,
        editingSubjectId: null,
        subjectForm: { code: '', name: '', category: '', written_papers: null, has_practical: false, has_project: false, max_marks: null, description: '', is_active: true },
        subjectDescriptionManual: false,
        lastAutoSubjectDescription: '',
        
        // Combinations
        combinations: [],
        filteredCombinations: [],
        combinationSearch: '',
        loadingCombinations: false,
        combinationModalOpen: false,
        editingCombinationId: null,
        combinationForm: { code: '', category: '', subjects: '', description: '', is_active: true },
        combinationSelectedSubjects: [],
        
        // Candidates
        candidates: [],
        filteredCandidates: [],
        candidateSearch: '',
        loadingCandidates: false,
        candidateModalOpen: false,
        editingCandidateId: null,
        candidateForm: { full_name: '', gender: '', combination: '', school_id: '' },
        selectedItems: new Set(),
        schools: [],

        // ACSEE Candidates (Read-Only)
        acseeCandicates: [],
        candidateSearch: '',
        loadingAcseeCandicates: false,
        
        // Pagination state
        pagination: {
            page: 1,
            perPage: 15,
            total: 0,
            lastPage: 1,
            from: 0,
            to: 0,
        },
        
        // Available per-page options
        perPageOptions: [15, 25, 50, 100],
        
        // Jump to page input
        jumpToPageInput: '',

        // Allocation Modal
        allocationModalOpen: false,
        bulkImportModalOpen: false,
        candidateToolsModalOpen: false,
        allocationCandidate: null,
        allocationMode: 'template',
        allocationExamYearId: '',
        allocationCombinationId: '',
        allocationSubjectIds: [],
        allocationReplace: false,
        allocationProcessing: false,
        allocationExamYears: [],
        allocationCombinations: [],
        allocationAllSubjects: [],
        allocationPreviewSubjects: [],
        allocationValidationMessages: { errors: [], warnings: [] },

        // Candidate Type Filter
        candidateTypeFilter: 'ALL', // ALL|SCHOOL|PRIVATE

        // Bulk Import State
        bulkImportMode: 'SCHOOL', // SCHOOL|PRIVATE
        bulkExamYearId: '',
        bulkCandidateTypeFilter: 'ALL', // Filter for bulk import
        bulkReplaceAllocations: false,
        bulkProcessing: false,
        bulkLoadingContexts: false,  // Loading state while fetching exam years, combinations, subjects

        // File Upload
        bulkUploadedFile: null,
        bulkUploadedFileName: '',
        bulkUploadedFileSize: 0,

        // Two-phase Import State
        bulkPhase: 'idle', // idle|validating|reviewing|committing|complete
        bulkValidationReport: null,
        bulkCommitReport: null,
        bulkLastErrors: [], // Store errors for download

        // UI Messaging
        bulkErrorMessage: '',
        bulkSuccessMessage: '',

        async init() {
            console.log('=== ACSEE Manager Initialized ===');
            
            // Load pagination state from URL
            const params = new URLSearchParams(window.location.search);
            this.pagination.page = parseInt(params.get('page')) || 1;
            this.pagination.perPage = parseInt(params.get('per_page')) || 15;
            this.candidateSearch = params.get('q') || '';
            this.candidateTypeFilter = params.get('candidate_type') || 'ALL';
            
            console.log('Pagination state loaded from URL:', this.pagination);
            
            await this.loadSubjects();
            await this.loadCombinations();
            await this.loadSchools();
            await this.loadCandidates();
            await this.loadAcseeCandicates();
            console.log('=== Initialization Complete ===');
        },

        async loadSubjects() {
            this.loadingSubjects = true;
            try {
                const response = await fetch('/api/exam-types/ACSEE/subjects');
                const data = await response.json();
                this.subjects = data.data || [];
                this.filteredSubjects = this.subjects;
            } catch (error) {
                console.error('Error loading subjects:', error);
                this.showMessage('Error loading subjects', 'error');
            } finally {
                this.loadingSubjects = false;
            }
        },

        async loadCombinations() {
            this.loadingCombinations = true;
            try {
                const response = await fetch('/api/exam-types/ACSEE/combinations');
                const data = await response.json();
                this.combinations = data.data || [];
                this.filteredCombinations = this.combinations;
            } catch (error) {
                console.error('Error loading combinations:', error);
                this.showMessage('Error loading combinations', 'error');
            } finally {
                this.loadingCombinations = false;
            }
        },

        async loadSchools() {
            try {
                const response = await fetch('/admin/api/schools');
                const data = await response.json();
                this.schools = data.data || [];
            } catch (error) {
                console.error('Error loading schools:', error);
            }
        },

        async loadCandidates() {
            this.loadingCandidates = true;
            try {
                const response = await fetch('/admin/api/candidates?exam_type=ACSEE');
                const data = await response.json();
                this.candidates = data.data || [];
                this.filteredCandidates = this.candidates;
            } catch (error) {
                console.error('Error loading candidates:', error);
                this.showMessage('Error loading candidates', 'error');
            } finally {
                this.loadingCandidates = false;
            }
        },

        filterSubjects() {
            if (!this.subjectSearch) {
                this.filteredSubjects = this.subjects;
                return;
            }
            const query = this.subjectSearch.toLowerCase();
            this.filteredSubjects = this.subjects.filter(s => 
                s.code.toLowerCase().includes(query) || s.name.toLowerCase().includes(query)
            );
        },

        filterCombinations() {
            if (!this.combinationSearch) {
                this.filteredCombinations = this.combinations;
                return;
            }
            const query = this.combinationSearch.toLowerCase();
            this.filteredCombinations = this.combinations.filter(c => 
                c.code.toLowerCase().includes(query)
            );
        },

        filterCandidates() {
            if (!this.candidateSearch) {
                this.filteredCandidates = this.candidates;
                return;
            }
            const query = this.candidateSearch.toLowerCase();
            this.filteredCandidates = this.candidates.filter(c => 
                (c.full_name && c.full_name.toLowerCase().includes(query)) ||
                (c.candidate_id && c.candidate_id.toLowerCase().includes(query))
            );
        },

        // SUBJECT FUNCTIONS
        defaultSubjectForm() {
            return { code: '', name: '', category: '', written_papers: null, has_practical: false, has_project: false, max_marks: null, description: '', is_active: true };
        },

        buildSubjectDescription() {
            const code = String(this.subjectForm.code || '').trim().toUpperCase();
            const name = String(this.subjectForm.name || '').trim();
            const category = String(this.subjectForm.category || '').trim();
            const writtenPapers = Number(this.subjectForm.written_papers || 1);
            const maxMarks = this.subjectForm.max_marks ? ` Maximum marks: ${this.subjectForm.max_marks}.` : '';
            const extras = [];

            if (this.subjectForm.has_practical) extras.push('practical component');
            if (this.subjectForm.has_project) extras.push('project component');

            if (!name && !code) return '';

            const intro = code
                ? `${name || 'Subject'} (${code})`
                : name;

            const paperText = writtenPapers > 1
                ? `${writtenPapers} written papers`
                : '1 written paper';

            const extraText = extras.length
                ? ` Includes ${extras.join(' and ')}.`
                : '';

            const categoryText = category
                ? ` ${category} category subject with ${paperText}.`
                : ` Subject with ${paperText}.`;

            return `${intro}.${categoryText}${extraText}${maxMarks}`.replace(/\s+/g, ' ').trim();
        },

        syncSubjectDescription(force = false) {
            const autoDescription = this.buildSubjectDescription();

            if (
                force ||
                !this.subjectDescriptionManual ||
                !String(this.subjectForm.description || '').trim() ||
                this.subjectForm.description === this.lastAutoSubjectDescription
            ) {
                this.subjectForm.description = autoDescription;
                this.subjectDescriptionManual = false;
            }

            this.lastAutoSubjectDescription = autoDescription;
        },

        markSubjectDescriptionManual() {
            this.subjectDescriptionManual = this.subjectForm.description !== this.lastAutoSubjectDescription;
        },

        openSubjectModal() {
            this.editingSubjectId = null;
            this.subjectForm = this.defaultSubjectForm();
            this.subjectDescriptionManual = false;
            this.lastAutoSubjectDescription = '';
            this.syncSubjectDescription(true);
            this.subjectModalOpen = true;
        },

        openEditSubjectModal(subject) {
            this.editingSubjectId = subject.id;
            this.subjectForm = { 
                ...subject, 
                is_active: subject.is_active ?? true,
                has_practical: subject.has_practical ?? false,
                has_project: subject.has_project ?? false
            };
            this.lastAutoSubjectDescription = this.buildSubjectDescription();
            this.subjectDescriptionManual = !!String(subject.description || '').trim() && subject.description !== this.lastAutoSubjectDescription;
            if (!String(this.subjectForm.description || '').trim()) {
                this.syncSubjectDescription(true);
            }
            this.subjectModalOpen = true;
        },

        async saveSubject() {
            try {
                if (!String(this.subjectForm.description || '').trim()) {
                    this.syncSubjectDescription(true);
                }

                const url = this.editingSubjectId 
                    ? `/api/exam-types/ACSEE/subjects/${this.editingSubjectId}`
                    : `/api/exam-types/ACSEE/subjects`;
                const method = this.editingSubjectId ? 'PUT' : 'POST';

                const payload = {
                    code: this.subjectForm.code,
                    name: this.subjectForm.name,
                    category: this.subjectForm.category,
                    writtenPapers: this.subjectForm.written_papers || 1,
                    hasPractical: this.subjectForm.has_practical || false,
                    hasProject: this.subjectForm.has_project || false,
                    max_marks: this.subjectForm.max_marks,
                    description: this.subjectForm.description,
                    is_active: this.subjectForm.is_active,
                };

                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(payload),
                });

                if (response.ok) {
                    this.showMessage(this.editingSubjectId ? 'Subject updated' : 'Subject added', 'success');
                    this.subjectModalOpen = false;
                    this.editingSubjectId = null;
                    await this.loadSubjects();
                } else {
                    const data = await response.json().catch(() => ({}));
                    const errors = data.errors ? Object.values(data.errors).flat().join(', ') : (data.message || 'Error saving subject');
                    this.showMessage(errors, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showMessage('Error saving subject', 'error');
            }
        },

        async deleteSubject(id) {
            if (!confirm('Delete this subject?')) return;

            try {
                const response = await fetch(`/api/exam-types/ACSEE/subjects/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                if (response.ok) {
                    this.showMessage('Subject deleted', 'success');
                    await this.loadSubjects();
                } else {
                    this.showMessage('Error deleting subject', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showMessage('Error deleting subject', 'error');
            }
        },

        // COMBINATION FUNCTIONS
        openCombinationModal() {
            this.editingCombinationId = null;
            this.combinationForm = { code: '', category: '', subjects: '', description: '', is_active: true };
            this.combinationSelectedSubjects = [];
            this.combinationModalOpen = true;
        },

        normalizeCombinationSubjects(value) {
            if (Array.isArray(value)) {
                return value
                    .map(item => {
                        if (typeof item === 'string') return item.trim();
                        if (item && typeof item === 'object') return String(item.code || item.subject_code || item.name || '').trim();
                        return '';
                    })
                    .filter(Boolean);
            }

            return String(value || '')
                .split(',')
                .map(s => s.trim())
                .filter(Boolean);
        },

        openEditCombinationModal(combination) {
            this.editingCombinationId = combination.id;
            this.combinationForm = { 
                ...combination, 
                is_active: combination.is_active ?? true
            };
            this.combinationSelectedSubjects = this.normalizeCombinationSubjects(
                combination.subject_codes || combination.subjects || combination.combination_subjects || ''
            );
            this.combinationForm.subjects = this.combinationSelectedSubjects.join(',');
            this.combinationModalOpen = true;
        },

        async saveCombination() {
            try {
                const url = this.editingCombinationId 
                    ? `/api/exam-types/ACSEE/combinations/${this.editingCombinationId}`
                    : `/api/exam-types/ACSEE/combinations`;
                const method = this.editingCombinationId ? 'PUT' : 'POST';

                // Convert selected subject codes to subject IDs for the backend
                const subjectIds = this.combinationSelectedSubjects
                    .map(code => {
                        const subject = this.subjects.find(s => s.code == code);
                        return subject ? subject.id : null;
                    })
                    .filter(id => id !== null);

                const payload = {
                    code: this.combinationForm.code,
                    category: this.combinationForm.category,
                    description: this.combinationForm.description,
                    subject_ids: subjectIds,
                };

                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                if (response.ok) {
                    this.showMessage(this.editingCombinationId ? 'Combination updated' : 'Combination added', 'success');
                    this.combinationModalOpen = false;
                    this.editingCombinationId = null;
                    await this.loadCombinations();
                } else {
                    const errorData = await response.json().catch(() => null);
                    const msg = errorData?.message || 'Error saving combination';
                    this.showMessage(msg, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showMessage('Error saving combination', 'error');
            }
        },

        async deleteCombination(id) {
            if (!confirm('Delete this combination?')) return;

            try {
                const response = await fetch(`/api/exam-types/ACSEE/combinations/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                if (response.ok) {
                    this.showMessage('Combination deleted', 'success');
                    await this.loadCombinations();
                } else {
                    this.showMessage('Error deleting combination', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showMessage('Error deleting combination', 'error');
            }
        },

        // CANDIDATE FUNCTIONS
        openAddCandidateModal() {
            this.editingCandidateId = null;
            this.candidateForm = { full_name: '', gender: '', combination: '', school_id: '' };
            this.candidateModalOpen = true;
        },

        openEditCandidateModal(candidate) {
            this.editingCandidateId = candidate.id;
            this.candidateForm = {
                full_name: candidate.full_name,
                gender: candidate.gender || '',
                combination: candidate.combination || '',
                school_id: candidate.school_id,
            };
            this.candidateModalOpen = true;
        },

        async saveCandidate() {
            try {
                const url = this.editingCandidateId 
                    ? `/api/candidates/${this.editingCandidateId}`
                    : `/admin/api/candidates`;
                const method = this.editingCandidateId ? 'PUT' : 'POST';

                const payload = {
                    ...this.candidateForm,
                    school_id: parseInt(this.candidateForm.school_id),
                    exam_type: 'ACSEE',
                };

                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(payload),
                });

                if (response.ok) {
                    this.showMessage(this.editingCandidateId ? 'Candidate updated' : 'Candidate registered', 'success');
                    this.candidateModalOpen = false;
                    this.editingCandidateId = null;
                    await this.loadCandidates();
                } else {
                    this.showMessage('Error saving candidate', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showMessage('Error saving candidate', 'error');
            }
        },

        async deleteCandidate(id) {
            if (!confirm('Delete this candidate?')) return;

            try {
                const response = await fetch(`/api/candidates/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                if (response.ok) {
                    this.showMessage('Candidate deleted', 'success');
                    await this.loadCandidates();
                } else {
                    this.showMessage('Error deleting candidate', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showMessage('Error deleting candidate', 'error');
            }
        },

        toggleSelect(id) {
            if (this.selectedItems.has(id)) {
                this.selectedItems.delete(id);
            } else {
                this.selectedItems.add(id);
            }
        },

        toggleSelectAll() {
            if (this.selectedItems.size === this.filteredCandidates.length) {
                this.selectedItems.clear();
            } else {
                this.filteredCandidates.forEach(c => this.selectedItems.add(c.id));
            }
        },

        async bulkDeleteCandidates() {
            if (this.selectedItems.size === 0) return;
            const count = this.selectedItems.size;
            if (!confirm(`Delete ${count} candidate(s)?`)) return;

            try {
                const ids = Array.from(this.selectedItems);
                const response = await fetch('/api/candidates/bulk-delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ ids }),
                });

                if (response.ok) {
                    this.showMessage(`${count} candidate(s) deleted`, 'success');
                    this.selectedItems.clear();
                    await this.loadCandidates();
                } else {
                    this.showMessage('Error deleting candidates', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showMessage('Error deleting candidates', 'error');
            }
        },

        showMessage(message, type) {
            const alertDiv = document.createElement('div');
            let bgClass = 'bg-gray-100 text-gray-700 border-gray-300';
            if (type === 'success') bgClass = 'bg-green-100 text-green-700 border-green-300';
            if (type === 'error') bgClass = 'bg-red-100 text-red-700 border-red-300';
            
            alertDiv.className = `fixed top-24 right-8 ${bgClass} p-4 rounded-lg border max-w-sm z-50 shadow-lg`;
            alertDiv.textContent = message;
            document.body.appendChild(alertDiv);
            setTimeout(() => alertDiv.remove(), 4000);
        },

        // ==================== ACSEE CANDIDATES (READ-ONLY) ====================

        async loadAcseeCandicates() {
            this.loadingAcseeCandicates = true;
            this.jumpToPageInput = '';
            try {
                const params = new URLSearchParams({
                    page: this.pagination.page,
                    per_page: this.pagination.perPage,
                    q: this.candidateSearch,
                    candidate_type: this.candidateTypeFilter,
                });
                
                // Sync URL
                const newUrl = `${window.location.pathname}?${params.toString()}#candidates`;
                window.history.replaceState({}, '', newUrl);
                
                const response = await fetch(`/api/exam-types/acsee/candidates?${params}`);
                const data = await response.json();
                
                this.acseeCandicates = data.data || [];
                this.pagination = {
                    page: data.meta.current_page,
                    perPage: data.meta.per_page,
                    total: data.meta.total,
                    lastPage: data.meta.last_page,
                    from: data.meta.from,
                    to: data.meta.to,
                };
            } catch (error) {
                console.error('Error loading ACSEE candidates:', error);
                this.showMessage('Error loading candidates', 'error');
            } finally {
                this.loadingAcseeCandicates = false;
            }
        },

        filterAcseeCandicates() {
            this.pagination.page = 1;
            this.loadAcseeCandicatesDebounced();
        },
        
        // Debounced search (300ms delay)
        loadAcseeCandicatesDebounced() {
            clearTimeout(this.searchDebounceTimer);
            this.searchDebounceTimer = setTimeout(() => {
                this.loadAcseeCandicates();
            }, 300);
        },

        exportAcseeCandicates() {
            const headers = ['Index Number', 'Full Name', 'Sex', 'Combination', 'Allocated Subjects', 'School'];
            const rows = this.acseeCandicates.map(c => [
                c.candidate_id || '',
                c.full_name || '',
                c.gender === 'M' ? 'Male' : c.gender === 'F' ? 'Female' : '',
                c.combination || '',
                c.allocated_subjects.map(s => s.code).join(', ') || '',
                c.school_name || '',
            ]);
            
            const csv = [headers, ...rows].map(row => 
                row.map(v => `"${v}"`).join(',')
            ).join('\n');
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `acsee_candidates_${Date.now()}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            this.showMessage('Exported to Excel successfully', 'success');
        },

        openCandidateToolsModal() {
            this.candidateToolsModalOpen = true;
        },

        closeCandidateToolsModal() {
            this.candidateToolsModalOpen = false;
        },

        launchAcseeCandidateExport() {
            this.closeCandidateToolsModal();
            this.exportAcseeCandicates();
        },

        launchAcseeBulkImport() {
            this.closeCandidateToolsModal();
            this.openBulkImportModal();
        },

        // ==================== ALLOCATION FUNCTIONS ====================

        async openAllocationModal(candidate) {
            this.allocationCandidate = candidate;
            this.allocationModalOpen = true;
            this.allocationMode = 'template';
            this.allocationExamYearId = '';
            this.allocationCombinationId = '';
            this.allocationSubjectIds = [];
            this.allocationReplace = false;
            this.allocationValidationMessages = { errors: [], warnings: [] };
            
            await this.loadAllocationContexts();
        },

        closeAllocationModal() {
            this.allocationModalOpen = false;
            this.bulkImportModalOpen = false;
            this.allocationCandidate = null;
            this.allocationSubjectIds = [];
            this.allocationValidationMessages = { errors: [], warnings: [] };
            this.resetBulkState();
        },

        setAllocationMode(mode) {
            this.allocationMode = mode;
            this.allocationSubjectIds = [];
            this.allocationCombinationId = '';
            this.allocationPreviewSubjects = [];
            this.allocationValidationMessages = { errors: [], warnings: [] };
        },

        async loadAllocationContexts() {
            this.bulkErrorMessage = '';
            this.bulkLoadingContexts = true;
            try {
                // Load exam years
                const yearsResponse = await fetch('/admin/api/exam-years', {
                    headers: { 'Accept': 'application/json' }
                });
                if (!yearsResponse.ok) {
                    throw new Error(`Failed to load exam years (HTTP ${yearsResponse.status})`);
                }
                const yearsData = await yearsResponse.json();
                // API returns { exam_years: [...], active_year: ... }
                this.allocationExamYears = Array.isArray(yearsData) ? yearsData : (yearsData.exam_years || yearsData.data || []);

                // Load combinations for ACSEE
                const combosResponse = await fetch('/api/exam-types/ACSEE/combinations', {
                    headers: { 'Accept': 'application/json' }
                });
                if (!combosResponse.ok) {
                    throw new Error(`Failed to load combinations (HTTP ${combosResponse.status})`);
                }
                const combosData = await combosResponse.json();
                this.allocationCombinations = Array.isArray(combosData) ? combosData : (combosData.data || []);

                // Load all subjects for ACSEE
                const subjectsResponse = await fetch('/api/exam-types/ACSEE/subjects', {
                    headers: { 'Accept': 'application/json' }
                });
                if (!subjectsResponse.ok) {
                    throw new Error(`Failed to load subjects (HTTP ${subjectsResponse.status})`);
                }
                const subjectsData = await subjectsResponse.json();
                this.allocationAllSubjects = Array.isArray(subjectsData) ? subjectsData : (subjectsData.data || []);
            } catch (error) {
                console.error('Error loading allocation contexts:', error);
                this.bulkErrorMessage = 'Unable to load exam years. Please refresh the page or try again.';
                // Keep stable - preserve existing data or set to empty
                this.allocationExamYears = this.allocationExamYears || [];
                this.allocationCombinations = this.allocationCombinations || [];
                this.allocationAllSubjects = this.allocationAllSubjects || [];
            } finally {
                this.bulkLoadingContexts = false;
            }
        },

        async loadCombinationSubjectsPreview() {
            if (!this.allocationCombinationId) {
                this.allocationPreviewSubjects = [];
                return;
            }

            try {
                const response = await fetch(`/admin/api/combinations/${this.allocationCombinationId}/subjects`);
                const data = await response.json();
                this.allocationPreviewSubjects = data.data || [];

                // Auto-select subjects from combination
                this.allocationSubjectIds = this.allocationPreviewSubjects.map(s => s.id);
            } catch (error) {
                console.error('Error loading combination subjects:', error);
                this.showMessage('Error loading combination subjects', 'error');
            }
        },

        async saveAllocation() {
            if (!this.allocationExamYearId) {
                this.showMessage('Please select an exam year', 'error');
                return;
            }

            if (!this.allocationCandidate) {
                this.showMessage('No candidate selected', 'error');
                return;
            }

            let subjectIds = [];

            if (this.allocationMode === 'template') {
                if (!this.allocationCombinationId) {
                    this.showMessage('Please select a combination template', 'error');
                    return;
                }
                subjectIds = this.allocationSubjectIds;
            } else {
                // Manual mode
                subjectIds = this.allocationSubjectIds;
                if (subjectIds.length === 0) {
                    this.showMessage('Please select at least one subject', 'error');
                    return;
                }
            }

            // Confirmation dialog for destructive operation
            if (this.allocationReplace) {
                const candidateName = this.allocationCandidate?.full_name || 'Unknown';
                const examYearLabel = this.allocationExamYears.find(y => y.id == this.allocationExamYearId)?.year_label || this.allocationExamYearId;
                
                const confirmed = confirm(
                    `CONFIRM DELETE & REPLACE\n\n` +
                    `Candidate: ${candidateName}\n` +
                    `Exam Year: ${examYearLabel}\n\n` +
                    `This will PERMANENTLY DELETE all existing subject allocations ` +
                    `for this exam year and replace them with the selected subjects.\n\n` +
                    `This action CANNOT be undone.\n\n` +
                    `Continue?`
                );
                
                if (!confirmed) {
                    this.showMessage('Operation cancelled', 'info');
                    return;
                }
            }

            this.allocationProcessing = true;

            try {
                // Prepare is_principal map (all except code 111 and 141 are principal)
                const isPrincipalMap = {};
                this.allocationAllSubjects.forEach(subject => {
                    if (subjectIds.includes(subject.id)) {
                        isPrincipalMap[subject.id] = subject.code !== '111' && subject.code !== '141';
                    }
                });

                const response = await fetch('/api/exam-types/acsee/allocate-subjects', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        candidate_id: this.allocationCandidate.id,
                        exam_year_id: parseInt(this.allocationExamYearId),
                        subject_ids: subjectIds.map(id => parseInt(id)),
                        is_principal_map: isPrincipalMap,
                        replace_allocations: this.allocationReplace,
                        source: this.allocationMode === 'template' ? 'template' : 'manual',
                    }),
                });

                const data = await response.json();

                if (response.ok && data.ok) {
                    this.showMessage(data.message || 'Subjects allocated successfully', 'success');
                    this.closeAllocationModal();
                    await this.loadAcseeCandicates();
                } else {
                    this.allocationValidationMessages = {
                        errors: data.errors || [],
                        warnings: data.warnings || [],
                    };
                    if (data.errors && data.errors.length > 0) {
                        this.showMessage('Allocation validation failed', 'error');
                    }
                }
            } catch (error) {
                console.error('Error saving allocation:', error);
                this.allocationValidationMessages = {
                    errors: ['An error occurred while saving allocation: ' + error.message],
                    warnings: [],
                };
                this.showMessage('Error saving allocation', 'error');
            } finally {
                this.allocationProcessing = false;
            }
        },

        // ==================== BULK CSV IMPORT FUNCTIONS ====================

        applyCandidateTypeFilter() {
            // Server-side filtering - reset to page 1 and reload
            this.pagination.page = 1;
            this.loadAcseeCandicates();
        },

        downloadTemplate(type) {
            const filename = type === 'SCHOOL' 
                ? 'school_allocation.csv' 
                : 'private_allocation.csv';
            
            const endpoint = type === 'SCHOOL'
                ? '/api/exam-types/acsee/templates/school-allocation.csv'
                : '/api/exam-types/acsee/templates/private-allocation.csv';
            
            try {
                fetch(endpoint)
                    .then(response => {
                        if (!response.ok) throw new Error('Template download failed');
                        return response.blob();
                    })
                    .then(blob => {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                        this.showMessage(`Downloaded ${type} template`, 'success');
                    });
            } catch (error) {
                console.error('Error downloading template:', error);
                this.showMessage(`Error downloading ${type} template`, 'error');
            }
        },

        handleBulkFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            // Validate file type
            if (!file.name.endsWith('.csv')) {
                this.showMessage('Please select a CSV file', 'error');
                event.target.value = '';
                return;
            }
            
            this.bulkUploadedFile = file;
            this.bulkUploadedFileName = file.name;
            this.bulkUploadedFileSize = file.size;
            this.bulkPhase = 'idle';
            this.bulkValidationReport = null;
            this.bulkCommitReport = null;
            this.bulkLastErrors = [];
            this.bulkErrorMessage = '';
            this.bulkSuccessMessage = '';
            
            this.showMessage(`File "${file.name}" selected`, 'success');
        },

        async validateBulkCSV() {
            if (!this.bulkUploadedFile) {
                this.bulkErrorMessage = 'Please select a CSV file';
                return;
            }
            
            if (!this.bulkExamYearId) {
                this.bulkErrorMessage = 'Please select an exam year';
                return;
            }
            
            this.bulkPhase = 'validating';
            this.bulkErrorMessage = '';
            this.bulkSuccessMessage = '';
            
            try {
                const formData = new FormData();
                formData.append('file', this.bulkUploadedFile);
                formData.append('exam_year_id', this.bulkExamYearId);
                formData.append('candidate_type_filter', this.bulkImportMode === 'private' ? 'PRIVATE' : (this.bulkImportMode === 'school' ? 'SCHOOL' : 'ALL'));
                formData.append('replace_allocations', this.bulkReplaceAllocations ? 'true' : 'false');
                
                const response = await fetch('/api/exam-types/acsee/allocate-from-csv/validate', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData,
                });
                
                const data = await response.json();
                
                if (data.total_rows !== undefined && data.total_rows > 0) {
                    // Validation ran and produced results (may have errors)
                    this.bulkValidationReport = {
                        total_rows: data.total_rows || 0,
                        valid_count: data.valid_count || 0,
                        invalid_count: data.invalid_count || 0,
                        summary: data.summary || [],
                    };
                    this.bulkLastErrors = data.errors || [];
                    this.bulkPhase = 'reviewing';
                    if (data.invalid_count > 0) {
                        this.bulkErrorMessage = `${data.invalid_count} row(s) have errors. Please fix and re-upload, or commit the ${data.valid_count} valid rows.`;
                        this.showMessage(this.bulkErrorMessage, 'error');
                    } else {
                        this.bulkSuccessMessage = `Validation complete: ${data.total_rows} rows scanned, ${data.valid_count} valid`;
                        this.showMessage(this.bulkSuccessMessage, 'success');
                    }
                } else if (!response.ok) {
                    this.bulkPhase = 'idle';
                    this.bulkErrorMessage = data.message || 'Validation failed';
                    this.bulkLastErrors = data.errors || [];
                    this.showMessage(this.bulkErrorMessage, 'error');
                }
            } catch (error) {
                console.error('Error validating CSV:', error);
                this.bulkPhase = 'idle';
                this.bulkErrorMessage = 'Error validating CSV: ' + error.message;
                this.showMessage(this.bulkErrorMessage, 'error');
            }
        },

        async commitBulkCSV() {
            if (!this.bulkValidationReport) {
                this.bulkErrorMessage = 'Please validate the CSV first';
                return;
            }
            
            if (!confirm('Are you sure you want to commit this import? This action cannot be undone.')) {
                return;
            }
            
            this.bulkPhase = 'committing';
            this.bulkErrorMessage = '';
            this.bulkSuccessMessage = '';
            
            try {
                const formData = new FormData();
                formData.append('file', this.bulkUploadedFile);
                formData.append('exam_year_id', this.bulkExamYearId);
                formData.append('candidate_type_filter', this.bulkImportMode === 'private' ? 'PRIVATE' : (this.bulkImportMode === 'school' ? 'SCHOOL' : 'ALL'));
                formData.append('replace_allocations_default', this.bulkReplaceAllocations ? '1' : '0');
                
                const response = await fetch('/api/exam-types/acsee/allocate-from-csv/commit', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData,
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    this.bulkCommitReport = {
                        success_count: data.success_count || 0,
                        skipped_count: data.skipped_count || 0,
                        failed_count: data.failed_count || 0,
                        affected_candidates: data.affected_candidates || [],
                    };
                    this.bulkLastErrors = data.errors || [];
                    this.bulkPhase = 'complete';
                    this.bulkSuccessMessage = `Import completed: ${this.bulkCommitReport.success_count} rows imported`;
                    this.showMessage(this.bulkSuccessMessage, 'success');
                    
                    // Reload ACSEE candidates after successful import
                    await this.loadAcseeCandicates();
                } else {
                    this.bulkPhase = 'reviewing';
                    this.bulkErrorMessage = data.message || 'Commit failed';
                    this.bulkLastErrors = data.errors || [];
                    this.showMessage(this.bulkErrorMessage, 'error');
                }
            } catch (error) {
                console.error('Error committing CSV:', error);
                this.bulkPhase = 'reviewing';
                this.bulkErrorMessage = 'Error committing CSV: ' + error.message;
                this.showMessage(this.bulkErrorMessage, 'error');
            }
        },

        async downloadBulkErrorReport() {
            if (!this.bulkLastErrors || this.bulkLastErrors.length === 0) {
                this.showMessage('No errors to download', 'info');
                return;
            }
            
            try {
                const response = await fetch('/api/exam-types/acsee/allocate-from-csv/download-errors', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ errors: this.bulkLastErrors }),
                });
                
                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `acsee_import_errors_${Date.now()}.csv`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    this.showMessage('Error report downloaded', 'success');
                } else {
                    this.showMessage('Error downloading error report', 'error');
                }
            } catch (error) {
                console.error('Error downloading error report:', error);
                this.showMessage('Error downloading error report: ' + error.message, 'error');
            }
        },

        resetBulkState() {
            this.bulkPhase = 'idle';
            this.bulkValidationReport = null;
            this.bulkCommitReport = null;
            this.bulkLastErrors = [];
            this.bulkErrorMessage = '';
            this.bulkSuccessMessage = '';
            this.bulkUploadedFile = null;
            this.bulkExamYearId = '';
            this.bulkImportMode = 'SCHOOL';
            this.bulkReplaceAllocations = false;
            
            // Reset file input
            const fileInput = document.querySelector('input[type="file"]#bulkCsvFile');
            if (fileInput) {
                fileInput.value = '';
            }
        },

        async openBulkImportModal() {
            this.bulkImportModalOpen = true;
            this.resetBulkState();
            this.bulkErrorMessage = '';
            
            // Always load exam years to ensure data is fresh
            await this.loadAllocationContexts();
            
            // Auto-select the active exam year if available and not already selected
            // Only if data loaded successfully (no error)
            if (!this.bulkErrorMessage && !this.bulkExamYearId && this.allocationExamYears.length > 0) {
                const activeYear = this.allocationExamYears.find(y => y.is_active);
                if (activeYear) {
                    this.bulkExamYearId = String(activeYear.id);
                } else {
                    // Fallback to first exam year if no active year
                    this.bulkExamYearId = String(this.allocationExamYears[0].id);
                }
            }
        },

        closeBulkImportModal() {
            this.bulkImportModalOpen = false;
            this.resetBulkState();
        },

    };
};
</script>
    </div>
</div>
@endsection
