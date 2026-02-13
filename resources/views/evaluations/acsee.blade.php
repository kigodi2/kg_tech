@extends('layout')

@section('content')
<div class="w-full" style="font-family: 'Maiandra GD', sans-serif;">
    <!-- Page Header -->
    <div class="bg-white border-b border-gray-200 px-8 py-6 sticky top-0 z-40 shadow-sm">
        <h1 class="text-2xl font-bold text-gray-800" style="font-family: 'Maiandra GD', sans-serif;">ACSEE Evaluations</h1>
    </div>

    <!-- Main Content with Side Menu -->
    <div class="flex h-full">
        <!-- Side Menu Bar -->
        <div class="w-64 bg-gray-800 border-r border-gray-700 shadow-sm overflow-y-auto" style="height: calc(100vh - 140px); font-family: 'Maiandra GD', sans-serif;" x-data="evaluationsManager()">
            <nav class="p-6 space-y-2">
                <!-- Zonalwise Section -->
                <div>
                    <button @click="expandedSection = expandedSection === 'zonal' ? null : 'zonal'; if (expandedSection === 'zonal') activeTab = 'zonal-overall'" class="w-full text-left px-4 py-2 font-semibold text-white hover:bg-gray-700 rounded-lg transition-colors flex items-center justify-between">
                        <span class="flex items-center gap-2">
                            <i class="fas fa-globe text-blue-600 w-5"></i>ZONALWISE
                        </span>
                        <i :class="expandedSection === 'zonal' ? 'fas fa-chevron-down' : 'fas fa-chevron-right'" class="text-xs"></i>
                    </button>
                </div>

                <!-- Regionalwise Section -->
                <div>
                    <button disabled class="w-full text-left px-4 py-2 font-semibold text-gray-600 hover:bg-gray-700 rounded-lg transition-colors flex items-center gap-2 opacity-50 cursor-not-allowed">
                        <i class="fas fa-map text-gray-400 w-5"></i>REGIONALWISE
                     </button>
                </div>

                <!-- Districtwise Section -->
                <div>
                    <button disabled class="w-full text-left px-4 py-2 font-semibold text-gray-600 hover:bg-gray-700 rounded-lg transition-colors flex items-center gap-2 opacity-50 cursor-not-allowed">
                        <i class="fas fa-location-dot text-gray-400 w-5"></i>DISTRICTWISE
                     </button>
                </div>

                <!-- Candidate Extremity Analysis Section -->
                <div>
                    <a href="/evaluations/extremity-analysis" class="w-full text-left px-4 py-2 font-semibold text-white hover:bg-gray-700 rounded-lg transition-colors flex items-center gap-2 block">
                        <i class="fas fa-chart-scatter text-orange-500 w-5 flex-shrink-0"></i><span>EXTREMITY ANALYSIS</span>
                    </a>
                </div>

                <!-- Entry Report Section -->
                <div>
                    <button @click="expandedSection = expandedSection === 'entry' ? null : 'entry'" class="w-full text-left px-4 py-2 font-semibold text-white hover:bg-gray-700 rounded-lg transition-colors flex items-center justify-between">
                        <span class="flex items-center gap-2"><i class="fas fa-file-alt text-purple-600 w-5"></i>ENTRY REPORT</span>
                        <i :class="expandedSection === 'entry' ? 'fas fa-chevron-down' : 'fas fa-chevron-right'" class="text-xs"></i>
                    </button>
                    <template x-if="expandedSection === 'entry'">
                        <div class="ml-4 space-y-1 mt-2">
                            <div>
                                <button @click="expandedSubSection = expandedSubSection === 'entry-zonal' ? null : 'entry-zonal'" class="w-full text-left px-4 py-2 text-gray-400 hover:bg-gray-700 rounded-lg transition-colors text-sm flex items-center justify-between">
                                    <span class="flex items-center gap-2"><i class="fas fa-layer-group text-blue-500 w-4"></i>ZONAL LEVEL</span>
                                    <i :class="expandedSubSection === 'entry-zonal' ? 'fas fa-chevron-down' : 'fas fa-chevron-right'" class="text-xs"></i>
                                </button>
                                <template x-if="expandedSubSection === 'entry-zonal'">
                                    <div class="ml-4 space-y-1 mt-1">
                                        <button @click="activeTab = 'entry-zonal-subjects'" :class="activeTab === 'entry-zonal-subjects' ? 'bg-blue-700 text-blue-200' : 'text-gray-400 hover:bg-gray-700'" class="w-full text-left px-4 py-2 rounded-lg transition-colors text-xs flex items-center gap-2"><i class="fas fa-book w-3"></i>SUBJECTS</button>
                                        <button @click="activeTab = 'entry-zonal-regions'" :class="activeTab === 'entry-zonal-regions' ? 'bg-blue-700 text-blue-200' : 'text-gray-400 hover:bg-gray-700'" class="w-full text-left px-4 py-2 rounded-lg transition-colors text-xs flex items-center gap-2"><i class="fas fa-compass w-3"></i>REGIONS</button>
                                        <button @click="activeTab = 'entry-zonal-districts'" :class="activeTab === 'entry-zonal-districts' ? 'bg-blue-700 text-blue-200' : 'text-gray-400 hover:bg-gray-700'" class="w-full text-left px-4 py-2 rounded-lg transition-colors text-xs flex items-center gap-2"><i class="fas fa-square w-3"></i>DISTRICTS</button>
                                        <button @click="activeTab = 'entry-zonal-schools'" :class="activeTab === 'entry-zonal-schools' ? 'bg-blue-700 text-blue-200' : 'text-gray-400 hover:bg-gray-700'" class="w-full text-left px-4 py-2 rounded-lg transition-colors text-xs flex items-center gap-2"><i class="fas fa-building w-3"></i>SCHOOLS</button>
                                    </div>
                                </template>
                            </div>
                            <div>
                                <button @click="expandedSubSection = expandedSubSection === 'entry-regional' ? null : 'entry-regional'" class="w-full text-left px-4 py-2 text-gray-400 hover:bg-gray-700 rounded-lg transition-colors text-sm flex items-center justify-between">
                                    <span class="flex items-center gap-2"><i class="fas fa-layer-group text-green-500 w-4"></i>REGIONAL LEVEL</span>
                                    <i :class="expandedSubSection === 'entry-regional' ? 'fas fa-chevron-down' : 'fas fa-chevron-right'" class="text-xs"></i>
                                </button>
                                <template x-if="expandedSubSection === 'entry-regional'">
                                    <div class="ml-4 space-y-1 mt-1">
                                        <a href="/evaluations/acsee/daily-marks-entry-report" class="w-full text-left px-4 py-2 rounded-lg transition-colors text-xs flex items-center gap-2 text-gray-400 hover:bg-gray-700 block"><i class="fas fa-book w-3"></i>SUBJECTS</a>
                                        <button @click="activeTab = 'entry-regional-districts'" :class="activeTab === 'entry-regional-districts' ? 'bg-blue-700 text-blue-200' : 'text-gray-400 hover:bg-gray-700'" class="w-full text-left px-4 py-2 rounded-lg transition-colors text-xs flex items-center gap-2"><i class="fas fa-square w-3"></i>DISTRICTS</button>
                                        <button @click="activeTab = 'entry-regional-schools'" :class="activeTab === 'entry-regional-schools' ? 'bg-blue-700 text-blue-200' : 'text-gray-400 hover:bg-gray-700'" class="w-full text-left px-4 py-2 rounded-lg transition-colors text-xs flex items-center gap-2"><i class="fas fa-building w-3"></i>SCHOOLS</button>
                                    </div>
                                </template>
                            </div>
                            <div>
                                <button @click="expandedSubSection = expandedSubSection === 'entry-district' ? null : 'entry-district'" class="w-full text-left px-4 py-2 text-gray-400 hover:bg-gray-700 rounded-lg transition-colors text-sm flex items-center justify-between">
                                    <span class="flex items-center gap-2"><i class="fas fa-layer-group text-red-500 w-4"></i>DISTRICT LEVEL</span>
                                    <i :class="expandedSubSection === 'entry-district' ? 'fas fa-chevron-down' : 'fas fa-chevron-right'" class="text-xs"></i>
                                </button>
                                <template x-if="expandedSubSection === 'entry-district'">
                                    <div class="ml-4 space-y-1 mt-1">
                                        <button @click="activeTab = 'entry-district-data'" :class="activeTab === 'entry-district-data' ? 'bg-blue-700 text-blue-200' : 'text-gray-400 hover:bg-gray-700'" class="w-full text-left px-4 py-2 rounded-lg transition-colors text-xs flex items-center gap-2"><i class="fas fa-database w-3"></i>ENTRY DATA</button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 overflow-y-auto bg-gray-50">
            <!-- CANDIDATE EXTREMITY ANALYSIS PAGE -->
            <div x-show="activeTab === 'candidate-extremity'" class="w-full" style="font-family: 'Maiandra GD', sans-serif;" x-data="candidateExtremityDashboard()" @init="init()">
                <!-- Header -->
                <div class="bg-white border-b border-gray-200 px-8 py-6 sticky top-0 z-40 shadow-sm">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Candidate Performance Anomalies</h1>
                            <p class="text-sm text-gray-600 mt-1">Identify candidates with suspicious cross-subject score patterns</p>
                        </div>
                        <button @click="openAnalysisModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                            <i class="fas fa-sync mr-2"></i> Run Analysis
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-8 py-8">
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-5 gap-4 mb-8">
                        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
                            <p class="text-xs text-gray-600 font-semibold uppercase">High Risk</p>
                            <p class="text-3xl font-bold text-red-600" x-text="summary.high_risk || 0"></p>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
                            <p class="text-xs text-gray-600 font-semibold uppercase">Moderate Risk</p>
                            <p class="text-3xl font-bold text-yellow-600" x-text="summary.moderate_risk || 0"></p>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                            <p class="text-xs text-gray-600 font-semibold uppercase">Low Risk</p>
                            <p class="text-3xl font-bold text-green-600" x-text="summary.low_risk || 0"></p>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                            <p class="text-xs text-gray-600 font-semibold uppercase">Total Flagged</p>
                            <p class="text-3xl font-bold text-blue-600" x-text="summary.total_flagged || 0"></p>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
                            <p class="text-xs text-gray-600 font-semibold uppercase">Pending Review</p>
                            <p class="text-3xl font-bold text-purple-600" x-text="summary.pending_review || 0"></p>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="bg-white rounded-lg shadow p-6 mb-8">
                        <div class="grid grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Exam Year</label>
                                <select x-model="filters.exam_year_id" @change="loadReports()" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="">All Years</option>
                                    <template x-for="year in examYears" :key="year.id">
                                        <option :value="year.id" x-text="year.year_label"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Risk Level</label>
                                <select x-model="filters.risk_level" @change="loadReports()" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="">All Levels</option>
                                    <option value="High">High</option>
                                    <option value="Moderate">Moderate</option>
                                    <option value="Low">Low</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                                <select x-model="filters.reviewed_only" @change="loadReports()" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="">Pending Review</option>
                                    <option value="true">All</option>
                                </select>
                            </div>
                            <div class="flex items-end gap-2">
                                <button @click="exportCandidates()" class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium text-sm">
                                    <i class="fas fa-download mr-2"></i> Export
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Candidates Table -->
                    <div class="bg-white rounded-lg shadow overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 border-b-2 border-gray-300">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Index</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Candidate Name</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">School</th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Combo</th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Avg</th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Std Dev</th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Outliers</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Risk</th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="report in reports" :key="report.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-gray-800 font-mono" x-text="report.candidate.candidate_id"></td>
                                        <td class="px-4 py-3 text-gray-800 font-medium" x-text="report.candidate.full_name"></td>
                                        <td class="px-4 py-3 text-gray-600 text-xs" x-text="report.candidate.school.name"></td>
                                        <td class="px-4 py-3 text-center font-mono text-gray-800" x-text="report.combination"></td>
                                        <td class="px-4 py-3 text-center font-mono text-gray-800" x-text="parseFloat(report.average_score).toFixed(1)"></td>
                                        <td class="px-4 py-3 text-center font-mono text-gray-800" x-text="parseFloat(report.std_dev_across_subjects).toFixed(2)"></td>
                                        <td class="px-4 py-3 text-center font-bold" :class="report.outlier_subject_count > 0 ? 'text-red-600' : 'text-gray-600'" x-text="report.outlier_subject_count"></td>
                                        <td class="px-4 py-3 text-center">
                                            <span :class="{
                                                'bg-red-100 text-red-800': report.risk_level === 'High',
                                                'bg-yellow-100 text-yellow-800': report.risk_level === 'Moderate',
                                                'bg-green-100 text-green-800': report.risk_level === 'Low',
                                            }" class="inline-block px-3 py-1 rounded-full font-semibold" x-text="report.risk_level"></span>
                                        </td>
                                        <td class="px-4 py-3 text-center space-x-2">
                                            <button @click="viewCandidate(report)" class="text-blue-600 hover:text-blue-800 font-medium text-xs">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <template x-if="!report.reviewed">
                                                <button @click="reviewCandidate(report)" class="text-green-600 hover:text-green-800 font-medium text-xs">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Analysis Modal -->
                <template x-teleport="body">
                    <div x-show="showAnalysisModal" class="fixed inset-0 bg-black/50 z-[9998] flex items-center justify-center" @click="showAnalysisModal = false">
                        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6" @click.stop>
                            <h2 class="text-xl font-bold text-gray-800 mb-4">Analyze Cross-Subject Performance</h2>
                            <div class="space-y-4 mb-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Exam Year *</label>
                                    <select x-model="analysisForm.exam_year_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                        <option value="">Select Year</option>
                                        <template x-for="year in examYears" :key="year.id">
                                            <option :value="year.id" x-text="year.year_label || year"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Exam Type *</label>
                                    <select x-model="analysisForm.exam_type_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                        <option value="">Select Type</option>
                                        <template x-for="type in examTypes" :key="type.id">
                                            <option :value="type.id" x-text="type.code || type.name || type"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button @click="showAnalysisModal = false" class="flex-1 px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-medium">
                                    Cancel
                                </button>
                                <button @click="runAnalysis()" :disabled="!analysisForm.exam_year_id || !analysisForm.exam_type_id" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white rounded-lg font-medium">
                                    <i class="fas fa-sync" :class="analysisLoading && 'animate-spin'"></i> Analyze
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- ZONALWISE PAGE -->
            <div x-show="activeTab === 'zonal-overall'" class="px-8 py-8">
                <div>
                    <div class="mb-8">
                        <h1 class="text-3xl font-bold text-gray-900" style="font-family: 'Maiandra GD', sans-serif;">ZONALWISE EVALUATIONS</h1>
                        <p class="mt-2 text-gray-600" style="font-family: 'Maiandra GD', sans-serif;">Choose an evaluation type to view detailed data</p>
                    </div>

                    <!-- 4-Column Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <!-- General Evaluation -->
                         <div class="group relative overflow-hidden shadow-md transition-all duration-300 hover:shadow-xl hover:scale-105 transform cursor-pointer" style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);">
                             <div class="absolute inset-0 opacity-0 group-hover:opacity-20 bg-white transition-opacity duration-300"></div>
                             <div class="relative p-1 h-11 flex flex-col items-start justify-center text-white" style="font-family: 'Maiandra GD', sans-serif;">
                                  <div class="text-left">
                                      <p class="text-base font-bold uppercase tracking-wider">Zonal General Evaluation</p>
                                 </div>
                             </div>
                         </div>

                        <!-- Councilwise Evaluation -->
                        <div class="group relative overflow-hidden shadow-md transition-all duration-300 hover:shadow-xl hover:scale-105 transform cursor-pointer" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-20 bg-white transition-opacity duration-300"></div>
                            <div class="relative p-1 h-11 flex flex-col items-start justify-center text-white" style="font-family: 'Maiandra GD', sans-serif;">
                                <div class="text-left">
                                    <p class="text-base font-bold uppercase tracking-wider">Zonal Councilwise Evaluation</p>
                                </div>
                            </div>
                        </div>

                        <!-- Schoolwise Evaluation -->
                        <div class="group relative overflow-hidden shadow-md transition-all duration-300 hover:shadow-xl hover:scale-105 transform cursor-pointer" style="background: linear-gradient(135deg, #1F2937 0%, #111827 100%);">
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-20 bg-white transition-opacity duration-300"></div>
                            <div class="relative p-1 h-11 flex flex-col items-start justify-center text-white" style="font-family: 'Maiandra GD', sans-serif;">
                                <div class="text-left">
                                    <p class="text-base font-bold uppercase tracking-wider">Zonal Schoolwise Evaluation</p>
                                </div>
                            </div>
                        </div>

                        <!-- Regional Councilwise Evaluation -->
                         <div class="group relative overflow-hidden shadow-md transition-all duration-300 hover:shadow-xl hover:scale-105 transform cursor-pointer" style="background: linear-gradient(135deg, #6B7280 0%, #4B5563 100%);">
                             <div class="absolute inset-0 opacity-0 group-hover:opacity-20 bg-white transition-opacity duration-300"></div>
                             <div class="relative p-1 h-11 flex flex-col items-start justify-center text-white" style="font-family: 'Maiandra GD', sans-serif;">
                                  <div class="text-left">
                                      <p class="text-base font-bold uppercase tracking-wider">Zonal Regionalwise Evaluation</p>
                                 </div>
                             </div>
                         </div>

                         <!-- Best Ten (10) Councils -->
                         <div class="group relative overflow-hidden shadow-md transition-all duration-300 hover:shadow-xl hover:scale-105 transform cursor-pointer" style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);">
                             <div class="absolute inset-0 opacity-0 group-hover:opacity-20 bg-white transition-opacity duration-300"></div>
                             <div class="relative p-1 h-11 flex flex-col items-start justify-center text-white" style="font-family: 'Maiandra GD', sans-serif;">
                                 <div class="text-left">
                                     <p class="text-base font-bold uppercase tracking-wider">Zonal Best Ten (10) Councils</p>
                                 </div>
                             </div>
                         </div>

                         <!-- Least Ten (10) Councils -->
                         <div class="group relative overflow-hidden shadow-md transition-all duration-300 hover:shadow-xl hover:scale-105 transform cursor-pointer" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                             <div class="absolute inset-0 opacity-0 group-hover:opacity-20 bg-white transition-opacity duration-300"></div>
                             <div class="relative p-1 h-11 flex flex-col items-start justify-center text-white" style="font-family: 'Maiandra GD', sans-serif;">
                                 <div class="text-left">
                                     <p class="text-base font-bold uppercase tracking-wider">Zonal Least Ten (10) Councils</p>
                                 </div>
                             </div>
                         </div>

                         <!-- Best Ten (10) Schools -->
                         <div class="group relative overflow-hidden shadow-md transition-all duration-300 hover:shadow-xl hover:scale-105 transform cursor-pointer" style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);">
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-20 bg-white transition-opacity duration-300"></div>
                            <div class="relative p-1 h-11 flex flex-col items-start justify-center text-white" style="font-family: 'Maiandra GD', sans-serif;">
                                <div class="text-left">
                                    <p class="text-base font-bold uppercase tracking-wider">Zonal Best Ten (10) Schools</p>
                                </div>
                            </div>
                        </div>

                        <!-- Least Ten (10) Schools -->
                        <div class="group relative overflow-hidden shadow-md transition-all duration-300 hover:shadow-xl hover:scale-105 transform cursor-pointer" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-20 bg-white transition-opacity duration-300"></div>
                            <div class="relative p-1 h-11 flex flex-col items-start justify-center text-white" style="font-family: 'Maiandra GD', sans-serif;">
                                <div class="text-left">
                                    <p class="text-base font-bold uppercase tracking-wider">Zonal Least Ten (10) Schools</p>
                                </div>
                            </div>
                        </div>

                        <!-- Best Ten (10) Girls -->
                        <div class="group relative overflow-hidden shadow-md transition-all duration-300 hover:shadow-xl hover:scale-105 transform cursor-pointer" style="background: linear-gradient(135deg, #1F2937 0%, #111827 100%);">
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-20 bg-white transition-opacity duration-300"></div>
                            <div class="relative p-1 h-11 flex flex-col items-start justify-center text-white" style="font-family: 'Maiandra GD', sans-serif;">
                                <div class="text-left">
                                    <p class="text-base font-bold uppercase tracking-wider">Zonal Best Ten (10) Girls</p>
                                </div>
                            </div>
                        </div>

                        <!-- Least Ten (10) Girls -->
                        <div class="group relative overflow-hidden shadow-md transition-all duration-300 hover:shadow-xl hover:scale-105 transform cursor-pointer" style="background: linear-gradient(135deg, #6B7280 0%, #4B5563 100%);">
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-20 bg-white transition-opacity duration-300"></div>
                            <div class="relative p-1 h-11 flex flex-col items-start justify-center text-white" style="font-family: 'Maiandra GD', sans-serif;">
                                <div class="text-left">
                                    <p class="text-base font-bold uppercase tracking-wider">Zonal Least Ten (10) Girls</p>
                                </div>
                            </div>
                        </div>

                        <!-- Best Ten (10) Boys -->
                        <div class="group relative overflow-hidden shadow-md transition-all duration-300 hover:shadow-xl hover:scale-105 transform cursor-pointer" style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);">
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-20 bg-white transition-opacity duration-300"></div>
                            <div class="relative p-1 h-11 flex flex-col items-start justify-center text-white" style="font-family: 'Maiandra GD', sans-serif;">
                                <div class="text-left">
                                    <p class="text-base font-bold uppercase tracking-wider">Zonal Best Ten (10) Boys</p>
                                </div>
                            </div>
                        </div>

                        <!-- Least Ten (10) Boys -->
                        <div class="group relative overflow-hidden shadow-md transition-all duration-300 hover:shadow-xl hover:scale-105 transform cursor-pointer" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-20 bg-white transition-opacity duration-300"></div>
                            <div class="relative p-1 h-11 flex flex-col items-start justify-center text-white" style="font-family: 'Maiandra GD', sans-serif;">
                                <div class="text-left">
                                    <p class="text-base font-bold uppercase tracking-wider">Zonal Least Ten (10) Boys</p>
                                </div>
                            </div>
                        </div>

                        <!-- Overall Ten (10) Best Students -->
                        <div class="group relative overflow-hidden shadow-md transition-all duration-300 hover:shadow-xl hover:scale-105 transform cursor-pointer" style="background: linear-gradient(135deg, #1F2937 0%, #111827 100%);">
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-20 bg-white transition-opacity duration-300"></div>
                            <div class="relative p-1 h-11 flex flex-col items-start justify-center text-white" style="font-family: 'Maiandra GD', sans-serif;">
                                <div class="text-left">
                                    <p class="text-base font-bold uppercase tracking-wider">Zonal Overall Ten (10) Best Students</p>
                                </div>
                            </div>
                        </div>

                        <!-- Overall Ten (10) Least Students -->
                        <div class="group relative overflow-hidden shadow-md transition-all duration-300 hover:shadow-xl hover:scale-105 transform cursor-pointer" style="background: linear-gradient(135deg, #6B7280 0%, #4B5563 100%);">
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-20 bg-white transition-opacity duration-300"></div>
                            <div class="relative p-1 h-11 flex flex-col items-start justify-center text-white" style="font-family: 'Maiandra GD', sans-serif;">
                                <div class="text-left">
                                    <p class="text-base font-bold uppercase tracking-wider">Zonal Overall Ten (10) Least Students</p>
                                </div>
                            </div>
                        </div>

                        <!-- Government Schools -->
                        <div class="group relative overflow-hidden shadow-md transition-all duration-300 hover:shadow-xl hover:scale-105 transform cursor-pointer" style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);">
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-20 bg-white transition-opacity duration-300"></div>
                            <div class="relative p-1 h-11 flex flex-col items-start justify-center text-white" style="font-family: 'Maiandra GD', sans-serif;">
                                <div class="text-left">
                                    <p class="text-base font-bold uppercase tracking-wider">Zonal Government Schools</p>
                                </div>
                            </div>
                        </div>

                        <!-- Non-Government Schools -->
                        <div class="group relative overflow-hidden shadow-md transition-all duration-300 hover:shadow-xl hover:scale-105 transform cursor-pointer" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-20 bg-white transition-opacity duration-300"></div>
                            <div class="relative p-1 h-11 flex flex-col items-start justify-center text-white" style="font-family: 'Maiandra GD', sans-serif;">
                                <div class="text-left">
                                    <p class="text-base font-bold uppercase tracking-wider">Zonal Non-Government Schools</p>
                                </div>
                            </div>
                        </div>

                        <!-- Ownership Result Evaluation -->
                        <div class="group relative overflow-hidden shadow-md transition-all duration-300 hover:shadow-xl hover:scale-105 transform cursor-pointer" style="background: linear-gradient(135deg, #1F2937 0%, #111827 100%);">
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-20 bg-white transition-opacity duration-300"></div>
                            <div class="relative p-1 h-11 flex flex-col items-start justify-center text-white" style="font-family: 'Maiandra GD', sans-serif;">
                                <div class="text-left">
                                    <p class="text-base font-bold uppercase tracking-wider">Zonal Ownership Result Evaluation</p>
                                </div>
                            </div>
                        </div>

                        <!-- Subjectwise Result Evaluation -->
                        <div class="group relative overflow-hidden shadow-md transition-all duration-300 hover:shadow-xl hover:scale-105 transform cursor-pointer" style="background: linear-gradient(135deg, #6B7280 0%, #4B5563 100%);">
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-20 bg-white transition-opacity duration-300"></div>
                            <div class="relative p-1 h-11 flex flex-col items-start justify-center text-white" style="font-family: 'Maiandra GD', sans-serif;">
                                <div class="text-left">
                                    <p class="text-base font-bold uppercase tracking-wider">Zonal Subjectwise Result Evaluation</p>
                                </div>
                            </div>
                        </div>

                        <!-- Mark Entry Status Report -->
                        <div class="group relative overflow-hidden shadow-md transition-all duration-300 hover:shadow-xl hover:scale-105 transform cursor-pointer" style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);">
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-20 bg-white transition-opacity duration-300"></div>
                            <div class="relative p-1 h-11 flex flex-col items-start justify-center text-white" style="font-family: 'Maiandra GD', sans-serif;">
                                <div class="text-left">
                                    <p class="text-base font-bold uppercase tracking-wider">Zonal Mark Entry Status Report</p>
                                </div>
                            </div>
                        </div>

                        <!-- Subject Summary Evaluation -->
                        <div class="group relative overflow-hidden shadow-md transition-all duration-300 hover:shadow-xl hover:scale-105 transform cursor-pointer" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-20 bg-white transition-opacity duration-300"></div>
                            <div class="relative p-1 h-11 flex flex-col items-start justify-center text-white" style="font-family: 'Maiandra GD', sans-serif;">
                                <div class="text-left">
                                    <p class="text-base font-bold uppercase tracking-wider">Zonal Subject Summary Evaluation</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    function evaluationsManager() {
        return {
            activeTab: 'zonal-overall',
            expandedSection: null,
            expandedSubSection: null,

            init() {
                // Initialize if needed
            }
        };
    }

    function candidateExtremityDashboard() {
        return {
            summary: {},
            reports: [],
            examYears: [],
            examTypes: [],
            filters: {
                exam_year_id: '',
                risk_level: '',
                reviewed_only: '',
            },
            showAnalysisModal: false,
            analysisForm: {
                exam_year_id: '',
                exam_type_id: '',
            },
            analysisLoading: false,

            async init() {
                await this.loadExamYears();
                await this.loadExamTypes();
                await this.loadReports();
            },

            async loadReports() {
                try {
                    const params = new URLSearchParams(this.filters);
                    const response = await fetch(`/api/admin/candidate-extremity/dashboard?${params}`, {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        }
                    });

                    const data = await response.json();
                    this.summary = data.summary;
                    this.reports = data.reports.data;
                } catch (error) {
                    console.error('Error loading reports:', error);
                }
            },

            async loadExamYears() {
                const response = await fetch('/api/exam-years');
                const data = await response.json();
                this.examYears = data.exam_years || data;
            },

            async loadExamTypes() {
                const response = await fetch('/api/exam-types');
                const data = await response.json();
                this.examTypes = data.data || data;
            },

            openAnalysisModal() {
                this.showAnalysisModal = true;
            },

            async runAnalysis() {
                this.analysisLoading = true;
                try {
                    const response = await fetch('/api/admin/candidate-extremity/analyze', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(this.analysisForm),
                    });

                    if (response.ok) {
                        this.showAlert('Analysis completed', 'success');
                        this.showAnalysisModal = false;
                        await this.loadReports();
                    } else {
                        this.showAlert('Analysis failed', 'error');
                    }
                } finally {
                    this.analysisLoading = false;
                }
            },

            viewCandidate(report) {
                window.location.href = `/admin/candidate-extremity/${report.id}`;
            },

            async reviewCandidate(report) {
                const action = prompt('Select action:\n1: Investigation\n2: No Action\n3: Corrected', '1');
                if (!action) return;

                const actionMap = { '1': 'marked_for_investigation', '2': 'no_action_needed', '3': 'data_corrected' };

                const response = await fetch(`/api/admin/candidate-extremity/${report.id}/mark-reviewed`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action: actionMap[action] }),
                });

                if (response.ok) {
                    this.showAlert('Marked as reviewed', 'success');
                    await this.loadReports();
                }
            },

            async exportCandidates() {
                const params = new URLSearchParams(this.filters);
                window.location.href = `/api/admin/candidate-extremity/export?${params}`;
            },

            showAlert(message, type) {
                const alert = document.createElement('div');
                alert.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white font-medium ${type === 'success' ? 'bg-green-600' : 'bg-red-600'} shadow-lg`;
                alert.textContent = message;
                document.body.appendChild(alert);
                setTimeout(() => alert.remove(), 3000);
            }
        };
    }


</script>
@endsection
