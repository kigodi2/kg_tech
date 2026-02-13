@extends('layout')

@section('content')
<div class="w-full bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 px-8 py-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Candidate Analysis Detail</h1>
                <p class="text-sm text-gray-600 mt-1">Review performance anomalies and cross-subject patterns</p>
            </div>
            <a href="/evaluations/extremity-analysis" class="text-blue-600 hover:text-blue-800 font-medium">
                <i class="fas fa-arrow-left mr-2"></i> Back to Analysis
            </a>
        </div>
    </div>

    <!-- Content -->
    <div class="px-8 py-8" x-data="candidateExtremityDetail()" @init="init()">
        <!-- Loading state -->
        <div x-show="loading" class="bg-white rounded-lg shadow p-8 text-center">
            <div class="animate-spin inline-block w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full"></div>
            <p class="text-gray-600 mt-4">Loading analysis...</p>
        </div>

        <!-- Content -->
        <template x-if="!loading">
            <div>
                <!-- Candidate Info -->
                <div class="bg-white rounded-lg shadow p-6 mb-8">
                    <div class="grid grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs font-semibold text-gray-600 uppercase">Index Number</p>
                            <p class="text-lg font-mono font-bold text-gray-800" x-text="candidate?.index_number"></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-600 uppercase">Name</p>
                            <p class="text-lg font-bold text-gray-800" x-text="candidate?.name"></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-600 uppercase">School</p>
                            <p class="text-lg font-bold text-gray-800" x-text="candidate?.school?.name"></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-600 uppercase">Combination</p>
                            <p class="text-lg font-mono font-bold text-gray-800" x-text="analysis?.combination"></p>
                        </div>
                    </div>
                </div>

                <!-- Analysis Summary -->
                <div class="grid grid-cols-5 gap-4 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <p class="text-xs text-gray-600 font-semibold uppercase">Avg Score</p>
                        <p class="text-3xl font-bold text-blue-600" x-text="parseFloat(analysis?.average_score).toFixed(1)"></p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <p class="text-xs text-gray-600 font-semibold uppercase">Std Dev</p>
                        <p class="text-3xl font-bold text-purple-600" x-text="parseFloat(analysis?.std_dev).toFixed(2)"></p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-orange-500">
                        <p class="text-xs text-gray-600 font-semibold uppercase">Outliers</p>
                        <p class="text-3xl font-bold text-orange-600" x-text="analysis?.outlier_subjects?.length || 0"></p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <p class="text-xs text-gray-600 font-semibold uppercase">Subjects</p>
                        <p class="text-3xl font-bold text-gray-800" x-text="analysis?.subjects_count"></p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6" :class="{
                        'border-l-4 border-red-500': analysis?.risk_level === 'High',
                        'border-l-4 border-yellow-500': analysis?.risk_level === 'Moderate',
                        'border-l-4 border-green-500': analysis?.risk_level === 'Low'
                    }">
                        <p class="text-xs text-gray-600 font-semibold uppercase">Risk Level</p>
                        <p :class="{
                            'text-red-600': analysis?.risk_level === 'High',
                            'text-yellow-600': analysis?.risk_level === 'Moderate',
                            'text-green-600': analysis?.risk_level === 'Low'
                        }" class="text-2xl font-bold" x-text="analysis?.risk_level"></p>
                    </div>
                </div>

                <!-- Flags -->
                <template x-if="analysis?.flags && analysis.flags.length > 0">
                    <div class="bg-white rounded-lg shadow p-6 mb-8">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Flags</h3>
                        <div class="space-y-2">
                            <template x-for="flag in analysis.flags" :key="flag">
                                <div class="bg-orange-50 border-l-4 border-orange-500 p-3 rounded">
                                    <p class="text-orange-800 font-medium" x-text="flag"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Subject Analysis -->
                <template x-if="analysis?.outlier_subjects && analysis.outlier_subjects.length > 0">
                    <div class="bg-white rounded-lg shadow p-6 mb-8">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Outlier Subjects</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100 border-b-2 border-gray-300">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Subject</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Score</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Avg</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Deviation</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Dev %</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Z-Score</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Type</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <template x-for="subject in analysis.outlier_subjects" :key="subject.subject_id">
                                        <tr class="hover:bg-gray-50" :class="subject.type === 'high' ? 'bg-red-50' : 'bg-blue-50'">
                                            <td class="px-4 py-3 font-medium text-gray-800">
                                                <span x-text="subject.subject_code"></span> - <span x-text="subject.subject_name"></span>
                                            </td>
                                            <td class="px-4 py-3 text-center font-mono font-bold text-gray-800" x-text="parseFloat(subject.score).toFixed(1)"></td>
                                            <td class="px-4 py-3 text-center font-mono text-gray-600" x-text="parseFloat(subject.candidate_average).toFixed(1)"></td>
                                            <td class="px-4 py-3 text-center font-mono" :class="subject.deviation > 0 ? 'text-red-600' : 'text-blue-600'" x-text="parseFloat(subject.deviation).toFixed(1)"></td>
                                            <td class="px-4 py-3 text-center font-mono font-bold" :class="Math.abs(subject.deviation_percentage) > 20 ? 'text-red-600' : 'text-gray-600'" x-text="parseFloat(subject.deviation_percentage).toFixed(1) + '%'"></td>
                                            <td class="px-4 py-3 text-center font-mono font-bold" :class="Math.abs(subject.zscore) > 2 ? 'text-red-600' : 'text-gray-600'" x-text="parseFloat(subject.zscore).toFixed(2)"></td>
                                            <td class="px-4 py-3 text-center">
                                                <span :class="subject.type === 'high' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'" class="px-2 py-1 rounded text-xs font-semibold" x-text="subject.type === 'high' ? 'HIGH' : 'LOW'"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                <!-- Review Section -->
                <template x-if="!review?.reviewed">
                    <div class="bg-blue-50 border-l-4 border-blue-500 rounded-lg shadow p-6 mb-8">
                        <h3 class="text-lg font-bold text-blue-900 mb-4">Review Analysis</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Action</label>
                                <select x-model="reviewForm.action" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="">Select Action</option>
                                    <option value="marked_for_investigation">Mark for Investigation</option>
                                    <option value="no_action_needed">No Action Needed</option>
                                    <option value="data_corrected">Data Corrected</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Review Notes (Optional)</label>
                                <textarea x-model="reviewForm.notes" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Add any notes about this review..."></textarea>
                            </div>
                            <div class="flex gap-2">
                                <button @click="submitReview()" :disabled="!reviewForm.action" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white rounded-lg font-medium">
                                    <i class="fas fa-check mr-2"></i> Submit Review
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Review History -->
                <template x-if="review?.reviewed">
                    <div class="bg-green-50 border-l-4 border-green-500 rounded-lg shadow p-6 mb-8">
                        <h3 class="text-lg font-bold text-green-900">Review Complete</h3>
                        <p class="text-green-800 mt-2">
                            Reviewed by <span class="font-semibold" x-text="review?.reviewed_by?.name"></span> on 
                            <span x-text="new Date(review?.reviewed_at).toLocaleString()"></span>
                        </p>
                        <template x-if="review?.notes">
                            <div class="mt-4 p-4 bg-white rounded border border-green-300">
                                <p class="text-sm text-gray-700" x-text="JSON.parse(review.notes)?.notes || ''"></p>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>

<script>
function candidateExtremityDetail() {
    const reportId = window.location.pathname.split('/').pop();
    
    return {
        reportId: reportId,
        loading: true,
        candidate: null,
        analysis: null,
        review: null,
        reviewForm: {
            action: '',
            notes: '',
        },

        async init() {
            await this.loadReport();
        },

        async loadReport() {
            try {
                const response = await fetch(`/api/extremity/report/${this.reportId}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });

                const data = await response.json();
                this.candidate = data.candidate;
                this.analysis = data.analysis;
                this.review = data.review;
            } catch (error) {
                console.error('Error loading report:', error);
                this.showAlert('Failed to load report', 'error');
            } finally {
                this.loading = false;
            }
        },

        async submitReview() {
            if (!this.reviewForm.action) {
                this.showAlert('Please select an action', 'error');
                return;
            }

            try {
                const response = await fetch(`/api/extremity/report/${this.reportId}/mark-reviewed`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(this.reviewForm),
                });

                if (response.ok) {
                    this.showAlert('Review submitted successfully', 'success');
                    await this.loadReport();
                } else {
                    this.showAlert('Failed to submit review', 'error');
                }
            } catch (error) {
                console.error('Error submitting review:', error);
                this.showAlert('Error submitting review', 'error');
            }
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
