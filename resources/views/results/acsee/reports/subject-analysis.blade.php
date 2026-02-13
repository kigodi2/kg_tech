@extends('results.acsee.layout')

@section('page-title', 'Subject Analysis Report')
@section('page-subtitle', 'Subject-level performance and difficulty analysis')
@section('breadcrumb-active', 'Subject Analysis')

@section('results-content')
<div class="space-y-6">
    
    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Filters</h3>
        
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Subject</label>
                <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Subjects</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Sort By</label>
                <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="avg_score">Average Score (Highest)</option>
                    <option value="pass_rate">Pass Rate</option>
                    <option value="difficulty">Difficulty (Hardest)</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors font-medium">
                    <i class="fas fa-filter mr-2"></i> Filter
                </button>
            </div>
        </div>
    </div>

    <!-- Subject Statistics -->
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Total Subjects</div>
            <div class="text-3xl font-bold text-gray-900">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Highest Avg Score</div>
            <div class="text-3xl font-bold text-gray-900">-</div>
            <div class="text-xs text-gray-600">Subject: -</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Lowest Avg Score</div>
            <div class="text-3xl font-bold text-gray-900">-</div>
            <div class="text-xs text-gray-600">Subject: -</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Overall Pass Rate</div>
            <div class="text-3xl font-bold text-gray-900">-</div>
        </div>
    </div>

    <!-- Subject Performance Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Subject</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Candidates</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Avg Score</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Pass Rate</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Grade Distribution</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Difficulty</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr class="hover:bg-gray-50 transition-colors">
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-3xl mb-2 block"></i>
                        <p>No data available. Generate report after publishing results.</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Export Section -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Export Report</h3>
        <div class="flex gap-4">
            <button class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg transition-colors font-medium flex items-center gap-2">
                <i class="fas fa-file-pdf"></i> Export as PDF
            </button>
            <button class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors font-medium flex items-center gap-2">
                <i class="fas fa-file-excel"></i> Export as Excel
            </button>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors font-medium flex items-center gap-2">
                <i class="fas fa-file-csv"></i> Export as CSV
            </button>
        </div>
    </div>
</div>
@endsection
