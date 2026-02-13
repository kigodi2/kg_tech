@extends('layout')

@section('content')
<div class="w-full">
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
    <div class="px-8 py-8" x-data="candidateExtremityDashboard()" @init="init()">
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
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Flags</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Risk</th>
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
                            <td class="px-4 py-3">
                                <template x-for="flag in JSON.parse(report.flags)" :key="flag">
                                    <span class="inline-block bg-orange-100 text-orange-800 px-2 py-1 rounded text-xs mr-1 mb-1" x-text="flag"></span>
                                </template>
                            </td>
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

<script>
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
            this.examYears = await response.json();
        },

        async loadExamTypes() {
            const response = await fetch('/api/exam-types');
            this.examTypes = await response.json();
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
