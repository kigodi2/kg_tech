@extends('results.acsee.layout')

@section('page-title', 'Grade Distribution Report')
@section('page-subtitle', 'View how grades (A-F, S) are distributed')
@section('breadcrumb-active', 'Grade Distribution')

@section('results-content')
<div class="space-y-6">
    
    <!-- Summary Statistics -->
    <div class="grid grid-cols-5 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Total Candidates</div>
            <div class="text-3xl font-bold text-gray-900">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Pass Rate</div>
            <div class="text-3xl font-bold text-gray-900">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Most Common Grade</div>
            <div class="text-3xl font-bold text-gray-900 text-center">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Grade A %</div>
            <div class="text-3xl font-bold text-green-600">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Fail Rate</div>
            <div class="text-3xl font-bold text-red-600">-</div>
        </div>
    </div>

    <!-- Grade Distribution Chart and Breakdown -->
    <div class="grid grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Grade Distribution (Pie Chart)</h3>
            <div class="h-64 flex items-center justify-center text-gray-500 border border-gray-200 rounded-lg">
                <i class="fas fa-chart-pie text-4xl"></i>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Grade Breakdown</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 bg-green-500 rounded-full"></span>
                            <span class="text-sm font-semibold text-gray-900">Grade A (Excellent)</span>
                        </div>
                        <span class="text-sm font-bold text-gray-900">-</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2"></div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 bg-blue-500 rounded-full"></span>
                            <span class="text-sm font-semibold text-gray-900">Grade B (Very Good)</span>
                        </div>
                        <span class="text-sm font-bold text-gray-900">-</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2"></div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 bg-yellow-500 rounded-full"></span>
                            <span class="text-sm font-semibold text-gray-900">Grade C (Good)</span>
                        </div>
                        <span class="text-sm font-bold text-gray-900">-</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2"></div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 bg-orange-500 rounded-full"></span>
                            <span class="text-sm font-semibold text-gray-900">Grade D (Satisfactory)</span>
                        </div>
                        <span class="text-sm font-bold text-gray-900">-</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2"></div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 bg-red-500 rounded-full"></span>
                            <span class="text-sm font-semibold text-gray-900">Grade F (Fail)</span>
                        </div>
                        <span class="text-sm font-bold text-gray-900">-</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2"></div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 bg-purple-500 rounded-full"></span>
                            <span class="text-sm font-semibold text-gray-900">Special (S/ABS)</span>
                        </div>
                        <span class="text-sm font-bold text-gray-900">-</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Grade Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Grade</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Count</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Percentage</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Cumulative %</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Visual</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr class="hover:bg-gray-50 transition-colors">
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-3xl mb-2 block"></i>
                        <p>No data available. Generate report after publishing results.</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Comparison by School Type -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">Grade Distribution by School Type</h3>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">School Type</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Grade A %</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Grade B %</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Grade C %</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Pass Rate</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr class="hover:bg-gray-50 transition-colors">
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                        <p>No data available</p>
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
