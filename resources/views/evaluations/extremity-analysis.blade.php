@extends('layout')

@section('content')
<div class="w-full bg-gray-50" style="font-family: 'Maiandra GD', sans-serif;">
    <!-- Page Header -->
    <div class="bg-white border-b border-gray-200 px-8 py-6 sticky top-0 z-40 shadow-sm">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-wave-square mr-3"></i>EXTREMITY ANALYSIS</h1>
                <p class="text-sm text-gray-600 mt-1">Identify candidates with suspicious cross-subject score patterns</p>
            </div>
            <a href="/evaluations/acsee" class="text-blue-600 hover:text-blue-800 font-medium">
                <i class="fas fa-arrow-left mr-2"></i> Back to ACSEE
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="px-8 py-8" x-data="extremityAnalysisDashboard()" @init.window="init()">
        <!-- Loading State -->
        <template x-if="loading">
            <div class="bg-white rounded-lg shadow p-8 text-center">
                <div class="animate-spin inline-block w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full"></div>
                <p class="text-gray-600 mt-4">Loading analysis data...</p>
            </div>
        </template>

        <!-- Summary Cards -->
        <template x-if="!loading">
            <div>
                <!-- Analysis Modal (moved inside) -->
                <template x-if="showAnalysisModal">
                    <div class="fixed inset-0 bg-black/50 z-[9998] flex items-center justify-center" @click="showAnalysisModal = false">
                        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6" @click.stop>
                            <h2 class="text-xl font-bold text-gray-800 mb-4">Run Extremity Analysis</h2>
                            <div class="space-y-4 mb-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Exam Year *</label>
                                    <select x-model="analysisForm.exam_year_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                        <option value="">Select Year</option>
                                        <template x-for="year in examYears" :key="year.id">
                                            <option :value="year.id" x-text="year.year_label"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Exam Type *</label>
                                    <select x-model="analysisForm.exam_type_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                        <option value="">Select Type</option>
                                        <template x-for="type in examTypes" :key="type.id">
                                            <option :value="type.id" x-text="type.code"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Subject (Optional - leave blank for all subjects)</label>
                                    <select x-model="analysisForm.subject_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                        <option value="">All Subjects</option>
                                        <template x-for="subject in availableSubjects" :key="subject.id">
                                            <option :value="subject.id" x-text="subject.name"></option>
                                        </template>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Select a subject to analyze it relative to other subjects</p>
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

                <!-- Filters and Controls -->
                <div class="bg-white rounded-lg shadow p-6 mb-8">
                    <div class="grid grid-cols-5 gap-4">
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
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Subject</label>
                            <select x-model="filters.subject_id" @change="loadReports()" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="">All Subjects</option>
                                <template x-for="subject in availableSubjects" :key="subject.id">
                                    <option :value="subject.id" x-text="subject.name"></option>
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
                        <div class="flex flex-col gap-2">
                            <button @click="openAnalysisModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-sm">
                                <i class="fas fa-sync mr-2"></i> Run Analysis
                            </button>
                            <button @click="exportCandidates()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium text-sm">
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
                                <th class="px-4 py-3 text-center font-semibold text-gray-700">Comb</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-700">Avg</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-700">Std Dev</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-700">Outliers</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-700">Risk</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-if="reports.length === 0">
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                        <i class="fas fa-inbox text-4xl mb-2"></i>
                                        <p>No flagged candidates found. Run analysis to get started.</p>
                                    </td>
                                </tr>
                            </template>
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
        </template>
    </div>
</div>

<script>
function extremityAnalysisDashboard() {
    return {
        loading: true,
        summary: {},
        reports: [],
        examYears: [],
        examTypes: [],
        availableSubjects: [],
        filters: {
            exam_year_id: '',
            subject_id: '',
            risk_level: '',
            reviewed_only: '',
        },
        showAnalysisModal: false,
        analysisForm: {
            exam_year_id: '',
            exam_type_id: '',
            subject_id: '',
        },
        analysisLoading: false,

        async init() {
            try {
                console.log('Initializing extremity analysis dashboard...');
                await this.loadExamYears();
                await this.loadExamTypes();
                await this.loadAvailableSubjects();
                await this.loadReports();
                console.log('Dashboard initialized successfully');
            } catch (error) {
                console.error('Error initializing dashboard:', error);
                // Don't show alert on init, just log
            } finally {
                this.loading = false;
            }
        },

        async loadReports() {
            try {
                const params = new URLSearchParams(this.filters);
                const response = await fetch(`/api/extremity/dashboard?${params}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });

                if (!response.ok) {
                    const error = await response.text();
                    console.error('API error:', response.status, error);
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const data = await response.json();
                this.summary = data.summary || {};
                this.reports = data.reports?.data || [];
            } catch (error) {
                console.error('Error loading reports:', error);
                // Don't show error on initial load, just log it
                if (this.reports.length > 0) {
                    this.showAlert('Failed to load reports', 'error');
                }
            }
        },

        async loadExamYears() {
            try {
                const response = await fetch('/api/exam-years');
                const data = await response.json();
                this.examYears = data.exam_years || data || [];
            } catch (error) {
                console.error('Error loading exam years:', error);
            }
        },

        async loadExamTypes() {
            try {
                const response = await fetch('/api/exam-types');
                const data = await response.json();
                this.examTypes = data.data || data || [];
            } catch (error) {
                console.error('Error loading exam types:', error);
            }
        },

        async loadAvailableSubjects() {
            try {
                const response = await fetch('/api/subjects');
                const data = await response.json();
                this.availableSubjects = data.data || data || [];
            } catch (error) {
                console.error('Error loading subjects:', error);
            }
        },

        openAnalysisModal() {
            this.showAnalysisModal = true;
        },

        async runAnalysis() {
            if (!this.analysisForm.exam_year_id || !this.analysisForm.exam_type_id) {
                this.showAlert('Please select both exam year and type', 'error');
                return;
            }
            
            // Clear subject_id if it's empty string
            if (!this.analysisForm.subject_id) {
                delete this.analysisForm.subject_id;
            }

            this.analysisLoading = true;
            try {
                const response = await fetch('/api/extremity/analyze', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(this.analysisForm),
                });

                if (response.ok) {
                    this.showAlert('Analysis completed successfully', 'success');
                    this.showAnalysisModal = false;
                    this.analysisForm = { exam_year_id: '', exam_type_id: '' };
                    await this.loadReports();
                } else {
                    const error = await response.text();
                    this.showAlert('Analysis failed: ' + error, 'error');
                }
            } catch (error) {
                console.error('Error running analysis:', error);
                this.showAlert('Error running analysis', 'error');
            } finally {
                this.analysisLoading = false;
            }
        },

        viewCandidate(report) {
            window.location.href = `/admin/candidate-extremity/${report.id}`;
        },

        async reviewCandidate(report) {
            const action = prompt('Select action:\n1 = Investigation\n2 = No Action\n3 = Corrected', '1');
            if (!action) return;

            const actionMap = { '1': 'marked_for_investigation', '2': 'no_action_needed', '3': 'data_corrected' };
            
            if (!actionMap[action]) {
                this.showAlert('Invalid action selected', 'error');
                return;
            }

            try {
                const response = await fetch(`/api/extremity/report/${report.id}/mark-reviewed`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action: actionMap[action] }),
                });

                if (response.ok) {
                    this.showAlert('Review recorded successfully', 'success');
                    await this.loadReports();
                } else {
                    this.showAlert('Failed to record review', 'error');
                }
            } catch (error) {
                console.error('Error recording review:', error);
                this.showAlert('Error recording review', 'error');
            }
        },

        async exportCandidates() {
            try {
                const params = new URLSearchParams(this.filters);
                const response = await fetch(`/api/extremity/export?${params}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });

                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `extremity-analysis-${new Date().toISOString().split('T')[0]}.csv`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    this.showAlert('Export successful', 'success');
                } else {
                    this.showAlert('Export failed', 'error');
                }
            } catch (error) {
                console.error('Error exporting:', error);
                this.showAlert('Error exporting data', 'error');
            }
        },

        showAlert(message, type) {
            const alert = document.createElement('div');
            alert.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white font-medium ${type === 'success' ? 'bg-green-600' : 'bg-red-600'} shadow-lg z-[10000]`;
            alert.textContent = message;
            document.body.appendChild(alert);
            setTimeout(() => alert.remove(), 3000);
        }
    };
}
</script>
@endsection
