@extends('results.acsee.layout')

@section('page-title', 'Reports')
@section('page-subtitle', 'Generate performance analysis and export reports')
@section('breadcrumb-active', 'Reports')

@section('results-content')
<div class="space-y-6">
    
    <!-- Report Selection Grid -->
    <div class="grid grid-cols-2 gap-6">
        
        <!-- School Summary Report -->
        <a href="{{ route('results.acsee.reports.school-summary') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer border-l-4 border-blue-500">
            <div class="flex items-start justify-between mb-3">
                <h3 class="text-lg font-bold text-gray-900">School Summary Report</h3>
                <i class="fas fa-school text-blue-600 text-2xl"></i>
            </div>
            <p class="text-sm text-gray-600 mb-4">School-level performance metrics and statistics</p>
            <div class="space-y-2">
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>Average GPA per school</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>Grade distribution</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>Pass rates</span>
                </div>
            </div>
        </a>

        <!-- Council Performance Report -->
        <a href="{{ route('results.acsee.reports.council-performance') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer border-l-4 border-purple-500">
            <div class="flex items-start justify-between mb-3">
                <h3 class="text-lg font-bold text-gray-900">Council Performance Report</h3>
                <i class="fas fa-chart-bar text-purple-600 text-2xl"></i>
            </div>
            <p class="text-sm text-gray-600 mb-4">Compare performance across schools and regions</p>
            <div class="space-y-2">
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>School comparison</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>Regional analysis</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>Top performers</span>
                </div>
            </div>
        </a>

        <!-- Subject Analysis Report -->
        <a href="{{ route('results.acsee.reports.subject-analysis') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer border-l-4 border-green-500">
            <div class="flex items-start justify-between mb-3">
                <h3 class="text-lg font-bold text-gray-900">Subject Analysis Report</h3>
                <i class="fas fa-flask text-green-600 text-2xl"></i>
            </div>
            <p class="text-sm text-gray-600 mb-4">Subject-level performance and difficulty analysis</p>
            <div class="space-y-2">
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>Pass rates by subject</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>Grade distribution</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>Difficulty ranking</span>
                </div>
            </div>
        </a>

        <!-- Combination Performance Report -->
        <a href="{{ route('results.acsee.reports.combination-performance') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer border-l-4 border-orange-500">
            <div class="flex items-start justify-between mb-3">
                <h3 class="text-lg font-bold text-gray-900">Combination Performance Report</h3>
                <i class="fas fa-layer-group text-orange-600 text-2xl"></i>
            </div>
            <p class="text-sm text-gray-600 mb-4">Compare results across subject combinations</p>
            <div class="space-y-2">
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>PCM vs PCB vs CSH</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>Average performance</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>Candidate counts</span>
                </div>
            </div>
        </a>

        <!-- GPA Distribution Report -->
        <a href="{{ route('results.acsee.reports.gpa-distribution') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer border-l-4 border-red-500">
            <div class="flex items-start justify-between mb-3">
                <h3 class="text-lg font-bold text-gray-900">GPA Distribution Report</h3>
                <i class="fas fa-chart-line text-red-600 text-2xl"></i>
            </div>
            <p class="text-sm text-gray-600 mb-4">Analyze GPA distribution across all candidates</p>
            <div class="space-y-2">
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>Distribution curves</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>Average GPA</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>Statistics</span>
                </div>
            </div>
        </a>

        <!-- Grade Distribution Report -->
        <a href="{{ route('results.acsee.reports.grade-distribution') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer border-l-4 border-cyan-500">
            <div class="flex items-start justify-between mb-3">
                <h3 class="text-lg font-bold text-gray-900">Grade Distribution Report</h3>
                <i class="fas fa-pie-chart text-cyan-600 text-2xl"></i>
            </div>
            <p class="text-sm text-gray-600 mb-4">View how grades (A-F, S) are distributed</p>
            <div class="space-y-2">
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>Grade percentages</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>Grade counts</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>Visual charts</span>
                </div>
            </div>
        </a>
    </div>

    <!-- Export Options -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Export Results</h3>
        
        <div class="grid grid-cols-3 gap-4">
            <button onclick="exportReport('pdf')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-lg transition-colors font-medium flex items-center justify-center gap-2">
                <i class="fas fa-file-pdf"></i> Export as PDF
            </button>
            <button onclick="exportReport('excel')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg transition-colors font-medium flex items-center justify-center gap-2">
                <i class="fas fa-file-excel"></i> Export as Excel
            </button>
            <button onclick="exportReport('csv')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg transition-colors font-medium flex items-center justify-center gap-2">
                <i class="fas fa-file-csv"></i> Export as CSV
            </button>
        </div>
    </div>

    <!-- Available Reports Info -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h4 class="font-semibold text-blue-900 mb-2">About Reports</h4>
        <p class="text-sm text-blue-800 mb-2">
            Reports are generated from published results only. All metrics are calculated in real-time based on the latest data.
        </p>
        <ul class="text-sm text-blue-800 space-y-1 ml-4">
            <li>✓ All reports can be exported in PDF, Excel, or CSV format</li>
            <li>✓ Reports are read-only and reflect final published results</li>
            <li>✓ Data is updated automatically when new results are published</li>
            <li>✓ Historical reports can be archived for future reference</li>
        </ul>
    </div>
</div>

<script>
function exportReport(format) {
    const filename = `ACSEE-Report-${new Date().toISOString().split('T')[0]}.${format}`;
    alert(`Report would be exported as ${format.toUpperCase()}`);
    // Implementation will handle actual export
}
</script>
@endsection
