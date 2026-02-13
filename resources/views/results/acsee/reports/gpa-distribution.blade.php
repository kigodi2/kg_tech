@extends('results.acsee.layout')

@section('page-title', 'GPA Distribution Report')
@section('page-subtitle', 'Analyze GPA distribution across all candidates')
@section('breadcrumb-active', 'GPA Distribution')

@section('results-content')
<div class="space-y-6">
    
    <!-- Summary Statistics -->
    <div class="grid grid-cols-5 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Total Candidates</div>
            <div class="text-3xl font-bold text-gray-900">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Average GPA</div>
            <div class="text-3xl font-bold text-gray-900">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Median GPA</div>
            <div class="text-3xl font-bold text-gray-900">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Highest GPA</div>
            <div class="text-3xl font-bold text-gray-900">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Lowest GPA</div>
            <div class="text-3xl font-bold text-gray-900">-</div>
        </div>
    </div>

    <!-- GPA Range Distribution -->
    <div class="grid grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">GPA Distribution Chart</h3>
            <div class="h-64 flex items-center justify-center text-gray-500 border border-gray-200 rounded-lg">
                <i class="fas fa-chart-bar text-4xl"></i>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">GPA Range Breakdown</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">4.0 (Perfect)</span>
                    <div class="flex-1 mx-4 bg-gray-200 rounded-full h-2"></div>
                    <span class="text-sm font-semibold text-gray-900">-</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">3.5 - 3.99</span>
                    <div class="flex-1 mx-4 bg-gray-200 rounded-full h-2"></div>
                    <span class="text-sm font-semibold text-gray-900">-</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">3.0 - 3.49</span>
                    <div class="flex-1 mx-4 bg-gray-200 rounded-full h-2"></div>
                    <span class="text-sm font-semibold text-gray-900">-</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">2.5 - 2.99</span>
                    <div class="flex-1 mx-4 bg-gray-200 rounded-full h-2"></div>
                    <span class="text-sm font-semibold text-gray-900">-</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">2.0 - 2.49</span>
                    <div class="flex-1 mx-4 bg-gray-200 rounded-full h-2"></div>
                    <span class="text-sm font-semibold text-gray-900">-</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">Below 2.0</span>
                    <div class="flex-1 mx-4 bg-gray-200 rounded-full h-2"></div>
                    <span class="text-sm font-semibold text-gray-900">-</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed GPA Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">GPA Range Statistics</h3>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">GPA Range</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Candidates</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Percentage</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Visual</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr class="hover:bg-gray-50 transition-colors">
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-3xl mb-2 block"></i>
                        <p>No data available. Generate report after publishing results.</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Statistics Summary -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h3 class="font-semibold text-blue-900 mb-3">Statistical Analysis</h3>
        <div class="grid grid-cols-3 gap-6">
            <div>
                <p class="text-sm text-blue-800 mb-2"><strong>Standard Deviation:</strong> <span class="font-bold">-</span></p>
                <p class="text-sm text-blue-800"><strong>Mode (Most Common):</strong> <span class="font-bold">-</span></p>
            </div>
            <div>
                <p class="text-sm text-blue-800 mb-2"><strong>Skewness:</strong> <span class="font-bold">-</span></p>
                <p class="text-sm text-blue-800"><strong>Kurtosis:</strong> <span class="font-bold">-</span></p>
            </div>
            <div>
                <p class="text-sm text-blue-800 mb-2"><strong>Range:</strong> <span class="font-bold">-</span></p>
                <p class="text-sm text-blue-800"><strong>Percentile 90:</strong> <span class="font-bold">-</span></p>
            </div>
        </div>
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
