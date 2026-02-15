@extends('layout')

@section('content')
<div class="w-full">
    <!-- Page Header -->
    <div class="bg-white border-b border-gray-200 px-8 py-6 sticky top-0 z-40 shadow-sm">
        <h1 class="text-2xl font-bold text-gray-800">ACSEE Management</h1>
    </div>

    <!-- Main Content -->
    <div class="px-8 py-8">

    <!-- ACSEE Component -->
    <div x-data="acseeManager()" @init="init()" class="space-y-6">
        <!-- Tabs Navigation -->
        <div class="bg-white rounded-lg shadow">
            <div class="flex border-b border-gray-200">
                <button @click="activeTab = 'subjects'" :class="activeTab === 'subjects' ? 'bg-blue-50 border-b-2 border-blue-600 text-blue-600' : 'text-gray-600'" class="flex-1 py-4 px-6 font-medium transition-colors">Subjects</button>
                <button @click="activeTab = 'combinations'" :class="activeTab === 'combinations' ? 'bg-blue-50 border-b-2 border-blue-600 text-blue-600' : 'text-gray-600'" class="flex-1 py-4 px-6 font-medium transition-colors">Combinations</button>
                <button @click="activeTab = 'candidates'" :class="activeTab === 'candidates' ? 'bg-blue-50 border-b-2 border-blue-600 text-blue-600' : 'text-gray-600'" class="flex-1 py-4 px-6 font-medium transition-colors">Candidates</button>
            </div>
        </div>

        <!-- SUBJECTS TAB -->
        <div x-show="activeTab === 'subjects'" class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex gap-4 items-center">
                    <input x-model="subjectSearch" @input="filterSubjects()" type="text" placeholder="Search subjects..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button @click="console.log('Button clicked'); console.log('subjectModalOpen:', subjectModalOpen); openSubjectModal(); console.log('After openSubjectModal, subjectModalOpen:', subjectModalOpen);" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors flex items-center gap-2 font-medium">
                        <i class="fas fa-plus"></i> Add Subject
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <div x-show="loadingSubjects" class="p-6 text-center text-gray-500">
                    <i class="fas fa-spinner animate-spin text-2xl"></i> Loading...
                </div>
                <table x-show="!loadingSubjects" class="w-full">
                    <thead class="bg-gray-100 border-b-2 border-gray-300">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Description</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template x-for="subject in filteredSubjects" :key="subject.id">
                            <tr class="hover:bg-blue-100 transition-colors">
                                 <td class="px-6 py-4 text-sm font-mono text-gray-800" x-text="subject.code"></td>
                                <td class="px-6 py-4 text-sm text-gray-800 font-medium" x-text="subject.name"></td>
                                <td class="px-6 py-4 text-sm text-gray-600" x-text="(subject.description || '-').substring(0, 30)"></td>
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
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                No subjects found. <button @click="openSubjectModal()" class="text-blue-600 hover:underline">Add one now</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- COMBINATIONS TAB -->
        <div x-show="activeTab === 'combinations'" class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex gap-4 items-center">
                    <input x-model="combinationSearch" @input="filterCombinations()" type="text" placeholder="Search combinations..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button @click="console.log('Combination button clicked'); openCombinationModal(); console.log('combinationModalOpen:', combinationModalOpen);" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors flex items-center gap-2 font-medium">
                        <i class="fas fa-plus"></i> Add Combination
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <div x-show="loadingCombinations" class="p-6 text-center text-gray-500">
                    <i class="fas fa-spinner animate-spin text-2xl"></i> Loading...
                </div>
                <table x-show="!loadingCombinations" class="w-full">
                    <thead class="bg-gray-100 border-b-2 border-gray-300">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Subjects</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template x-for="combination in filteredCombinations" :key="combination.id">
                            <tr class="hover:bg-blue-100 transition-colors">
                                 <td class="px-6 py-4 text-sm font-mono text-gray-800" x-text="combination.code"></td>
                                <td class="px-6 py-4 text-sm text-gray-600" x-text="combination.subjects"></td>
                                <td class="px-6 py-4 text-sm space-x-3">
                                    <button @click="openEditCombinationModal(combination)" class="inline-flex items-center justify-center w-8 h-8 text-blue-600 hover:text-blue-900 hover:bg-blue-50 rounded transition-colors">
                                        <i class="fas fa-edit text-sm"></i>
                                    </button>
                                    <button @click="deleteCombination(combination.id)" class="inline-flex items-center justify-center w-8 h-8 text-red-600 hover:text-red-900 hover:bg-red-50 rounded transition-colors">
                                        <i class="fas fa-trash text-sm"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredCombinations.length === 0 && !loadingCombinations">
                            <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                                No combinations found. <button @click="openCombinationModal()" class="text-blue-600 hover:underline">Add one now</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CANDIDATES TAB (READ-ONLY) -->
         <div x-show="activeTab === 'candidates'" class="space-y-6">
             <!-- Search Section -->
             <div class="bg-gray-50 rounded-lg px-6 py-4 flex gap-4 items-center">
                 <input x-model="candidateSearch" @input="filterAcseeCandicates()" type="text" placeholder="Search candidates..." class="flex-1 px-4 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm text-gray-700 placeholder-gray-400">
                 <button @click="exportAcseeCandicates()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium flex items-center gap-2 transition-colors text-sm whitespace-nowrap">
                     <i class="fas fa-download"></i> Export Excel
                 </button>
             </div>

            <!-- Candidates Table (Read-Only) -->
            <div class="bg-white rounded-lg shadow overflow-x-auto">
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
                                <td class="px-6 py-4 text-sm text-gray-600 text-center" x-text="candidate.gender === 'M' ? '♂ Male' : candidate.gender === 'F' ? '♀ Female' : '-'"></td>
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

            <!-- Pagination -->
            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-white rounded-lg shadow">
                <div class="text-sm text-gray-600">
                    Page <span x-text="acseeCurrentPage"></span> of <span x-text="acseetotalPages"></span>, showing <span x-text="acseeCandicates.length"></span> of <span x-text="acseetotalCount"></span> candidates
                </div>
                <div class="flex gap-2 items-center">
                    <button @click="acseeCurrentPage > 1 && (acseeCurrentPage--, loadAcseeCandicates())" :disabled="acseeCurrentPage <= 1" class="px-3 py-1 border border-gray-300 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 transition-colors">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <template x-for="page in Array.from({length: acseetotalPages}, (_, i) => i + 1)" :key="page">
                        <button @click="acseeCurrentPage = page; loadAcseeCandicates()" :class="acseeCurrentPage === page ? 'bg-blue-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-100'" class="px-3 py-1 rounded transition-colors font-medium" x-text="page"></button>
                    </template>
                    <button @click="acseeCurrentPage < acseetotalPages && (acseeCurrentPage++, loadAcseeCandicates())" :disabled="acseeCurrentPage >= acseetotalPages" class="px-3 py-1 border border-gray-300 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 transition-colors">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- SUBJECT MODAL -->
        <div x-show="subjectModalOpen || editingSubjectId" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[9999] p-4" @click.self="subjectModalOpen = false; editingSubjectId = null;" x-transition style="display: none;">
            <div class="bg-white rounded-lg shadow-2xl max-w-md w-full" x-transition>
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800" x-text="editingSubjectId ? 'Edit Subject' : 'Add Subject'"></h2>
                    <button @click="subjectModalOpen = false; editingSubjectId = null;" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
                </div>

                <form @submit.prevent="saveSubject()" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Code *</label>
                        <input x-model="subjectForm.code" type="text" placeholder="e.g., ENG" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Name *</label>
                        <input x-model="subjectForm.name" type="text" placeholder="e.g., English" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                        <textarea x-model="subjectForm.description" placeholder="Subject description" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 h-20 resize-none"></textarea>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="subjectModalOpen = false; editingSubjectId = null;" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 font-medium">Cancel</button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Save Subject</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- COMBINATION MODAL -->
        <div x-show="combinationModalOpen || editingCombinationId" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[9999] p-4" @click.self="combinationModalOpen = false; editingCombinationId = null;" x-transition style="display: none;">
            <div class="bg-white rounded-lg shadow-2xl max-w-md w-full" x-transition>
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800" x-text="editingCombinationId ? 'Edit Combination' : 'Add Combination'"></h2>
                    <button @click="combinationModalOpen = false; editingCombinationId = null;" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
                </div>

                <form @submit.prevent="saveCombination()" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Code *</label>
                        <input x-model="combinationForm.code" type="text" placeholder="e.g., SC1" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Subjects (Comma-separated) *</label>
                        <textarea x-model="combinationForm.subjects" placeholder="e.g., Physics, Chemistry, Biology" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 h-20 resize-none"></textarea>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="combinationModalOpen = false; editingCombinationId = null;" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 font-medium">Cancel</button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Save Combination</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- CANDIDATE MODAL -->
        <div x-show="candidateModalOpen || editingCandidateId" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[9999] p-4" @click.self="candidateModalOpen = false; editingCandidateId = null;" x-transition style="display: none;">
            <div class="bg-white rounded-lg shadow-2xl max-w-md w-full" x-transition>
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800" x-text="editingCandidateId ? 'Edit Candidate' : 'Register Candidate'"></h2>
                    <button @click="candidateModalOpen = false; editingCandidateId = null;" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
                </div>

                <form @submit.prevent="saveCandidate()" class="p-6 space-y-4">
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

                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="candidateModalOpen = false; editingCandidateId = null;" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 font-medium">Cancel</button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Save Candidate</button>
                    </div>
                </form>
            </div>
        <!-- ALLOCATION MODAL -->
        <div x-show="allocationModalOpen" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[9999] p-4" @click.self="closeAllocationModal()" x-transition style="display: none;">
            <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" x-transition>
                <div class="flex justify-between items-center p-6 border-b border-gray-200 sticky top-0 bg-white">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Allocate Subjects</h2>
                        <p class="text-sm text-gray-600 mt-1" x-text="allocationCandidate ? `${allocationCandidate.full_name} (${allocationCandidate.candidate_id})` : ''"></p>
                    </div>
                    <button @click="closeAllocationModal()" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
                </div>

                <div class="p-6 space-y-6">
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
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function acseeManager() {
    return {
        activeTab: 'subjects',
        
        // Subjects
        subjects: [],
        filteredSubjects: [],
        subjectSearch: '',
        loadingSubjects: false,
        subjectModalOpen: false,
        editingSubjectId: null,
        subjectForm: { code: '', name: '', description: '', is_active: true },
        
        // Combinations
        combinations: [],
        filteredCombinations: [],
        combinationSearch: '',
        loadingCombinations: false,
        combinationModalOpen: false,
        editingCombinationId: null,
        combinationForm: { code: '', subjects: '', is_active: true },
        
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
        acseeCurrentPage: 1,
        acseetotalPages: 1,
        acseetotalCount: 0,

        // Allocation Modal
        allocationModalOpen: false,
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

        async init() {
            console.log('=== ACSEE Manager Initialized ===');
            console.log('Data object keys:', Object.keys(this));
            await this.loadSubjects();
            await this.loadCombinations();
            await this.loadSchools();
            await this.loadCandidates();
            await this.loadAcseeCandicates();
            console.log('=== Initialization Complete ===');
            console.log('subjectModalOpen:', this.subjectModalOpen);
            console.log('combinationModalOpen:', this.combinationModalOpen);
            console.log('candidateModalOpen:', this.candidateModalOpen);
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
                const response = await fetch('/api/schools');
                const data = await response.json();
                this.schools = data.data || [];
            } catch (error) {
                console.error('Error loading schools:', error);
            }
        },

        async loadCandidates() {
            this.loadingCandidates = true;
            try {
                const response = await fetch('/api/candidates?exam_type=ACSEE');
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
        openSubjectModal() {
            this.editingSubjectId = null;
            this.subjectForm = { code: '', name: '', description: '', is_active: true };
            this.subjectModalOpen = true;
        },

        openEditSubjectModal(subject) {
            this.editingSubjectId = subject.id;
            this.subjectForm = { ...subject, is_active: subject.is_active ?? true };
            this.subjectModalOpen = true;
        },

        async saveSubject() {
            try {
                const url = this.editingSubjectId 
                    ? `/api/exam-types/ACSEE/subjects/${this.editingSubjectId}`
                    : `/api/exam-types/ACSEE/subjects`;
                const method = this.editingSubjectId ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.subjectForm),
                });

                if (response.ok) {
                    this.showMessage(this.editingSubjectId ? 'Subject updated' : 'Subject added', 'success');
                    this.subjectModalOpen = false;
                    this.editingSubjectId = null;
                    await this.loadSubjects();
                } else {
                    this.showMessage('Error saving subject', 'error');
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
            this.combinationForm = { code: '', subjects: '', is_active: true };
            this.combinationModalOpen = true;
        },

        openEditCombinationModal(combination) {
            this.editingCombinationId = combination.id;
            this.combinationForm = { ...combination, is_active: combination.is_active ?? true };
            this.combinationModalOpen = true;
        },

        async saveCombination() {
            try {
                const url = this.editingCombinationId 
                    ? `/api/exam-types/ACSEE/combinations/${this.editingCombinationId}`
                    : `/api/exam-types/ACSEE/combinations`;
                const method = this.editingCombinationId ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.combinationForm),
                });

                if (response.ok) {
                    this.showMessage(this.editingCombinationId ? 'Combination updated' : 'Combination added', 'success');
                    this.combinationModalOpen = false;
                    this.editingCombinationId = null;
                    await this.loadCombinations();
                } else {
                    this.showMessage('Error saving combination', 'error');
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
                    : `/api/candidates`;
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
            try {
                const params = new URLSearchParams({
                    page: this.acseeCurrentPage,
                    page_size: 15,
                    search: this.candidateSearch,
                });
                
                const response = await fetch(`/api/exam-types/acsee/candidates?${params}`);
                const data = await response.json();
                
                this.acseeCandicates = data.candidates;
                this.acseetotalPages = data.pagination.total_pages;
                this.acseetotalCount = data.pagination.total_count;
                this.acseeCurrentPage = data.pagination.page;
            } catch (error) {
                console.error('Error loading ACSEE candidates:', error);
                this.showMessage('Error loading candidates', 'error');
            } finally {
                this.loadingAcseeCandicates = false;
            }
        },

        filterAcseeCandicates() {
            this.acseeCurrentPage = 1;
            this.loadAcseeCandicates();
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
            this.allocationCandidate = null;
            this.allocationSubjectIds = [];
            this.allocationValidationMessages = { errors: [], warnings: [] };
        },

        setAllocationMode(mode) {
            this.allocationMode = mode;
            this.allocationSubjectIds = [];
            this.allocationCombinationId = '';
            this.allocationPreviewSubjects = [];
            this.allocationValidationMessages = { errors: [], warnings: [] };
        },

        async loadAllocationContexts() {
            try {
                // Load exam years
                const yearsResponse = await fetch('/api/exam-years');
                const yearsData = await yearsResponse.json();
                this.allocationExamYears = yearsData.data || [];

                // Load combinations for ACSEE
                const combosResponse = await fetch('/api/exam-types/ACSEE/combinations');
                const combosData = await combosResponse.json();
                this.allocationCombinations = combosData.data || [];

                // Load all subjects for ACSEE
                const subjectsResponse = await fetch('/api/exam-types/ACSEE/subjects');
                const subjectsData = await subjectsResponse.json();
                this.allocationAllSubjects = subjectsData.data || [];
            } catch (error) {
                console.error('Error loading allocation contexts:', error);
                this.showMessage('Error loading data for allocation', 'error');
            }
        },

        async loadCombinationSubjectsPreview() {
            if (!this.allocationCombinationId) {
                this.allocationPreviewSubjects = [];
                return;
            }

            try {
                const response = await fetch(`/api/combinations/${this.allocationCombinationId}/subjects`);
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
                // Prepare is_principal map (all except code 111 are principal)
                const isPrincipalMap = {};
                this.allocationAllSubjects.forEach(subject => {
                    if (subjectIds.includes(subject.id)) {
                        isPrincipalMap[subject.id] = subject.code !== '111';
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

        showMessage(message, type) {
            // Use the existing message system if available
            // This assumes there's a message display mechanism in the component
            console.log(`[${type.toUpperCase()}] ${message}`);
            // Add toast/alert if your system has one
            if (type === 'error') {
                alert(message); // Simple fallback
            }
        }
    };
}
</script>
    </div>
</div>
@endsection
