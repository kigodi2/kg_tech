@extends('results.acsee.layout')

@section('page-title', 'Audit & Governance')
@section('page-subtitle', 'Complete audit trail of all results processing actions')
@section('breadcrumb-active', 'Audit & Logs')

@section('results-content')
<div class="space-y-6">

    <!-- Quick Links -->
    <div class="grid grid-cols-3 gap-6">
        <a href="{{ route('results.acsee.audit.logs') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900">Audit Logs</h3>
                    <p class="text-sm text-gray-600 mt-1">All system actions</p>
                </div>
                <i class="fas fa-list text-blue-600 text-2xl"></i>
            </div>
        </a>

        <a href="{{ route('results.acsee.audit.processing-history') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900">Processing History</h3>
                    <p class="text-sm text-gray-600 mt-1">Result processing runs</p>
                </div>
                <i class="fas fa-history text-green-600 text-2xl"></i>
            </div>
        </a>

        <a href="{{ route('results.acsee.audit.publication-history') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900">Publication History</h3>
                    <p class="text-sm text-gray-600 mt-1">Publish/unpublish events</p>
                </div>
                <i class="fas fa-book text-purple-600 text-2xl"></i>
            </div>
        </a>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                <i class="fas fa-clock text-blue-600"></i>
                Recent Activity
            </h3>
        </div>

        <div class="divide-y max-h-96 overflow-y-auto">
            @forelse($recentLogs ?? [] as $log)
                <div class="px-6 py-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900">
                                <span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded mr-2">
                                    {{ strtoupper($log->action) }}
                                </span>
                            </p>
                            <p class="text-sm text-gray-600 mt-1">
                                <strong>User:</strong> {{ $log->user->name }} | 
                                <strong>IP:</strong> {{ $log->ip_address }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">{{ $log->created_at?->diffForHumans() }}</p>
                        </div>
                        <span class="text-xs font-bold rounded px-2 py-1 {{ $log->status === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ ucfirst($log->status) }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-2xl mb-2"></i>
                    <p class="text-sm">No activity logged</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Export & Documentation -->
    <div class="grid grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Export Logs</h3>
            <p class="text-sm text-gray-600 mb-4">Download audit logs for external archiving or compliance</p>
            
            <div class="space-y-2">
                <button onclick="exportLogs('pdf')" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors font-medium flex items-center justify-center gap-2 text-sm">
                    <i class="fas fa-file-pdf"></i> Export as PDF
                </button>
                <button onclick="exportLogs('excel')" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors font-medium flex items-center justify-center gap-2 text-sm">
                    <i class="fas fa-file-excel"></i> Export as Excel
                </button>
                <button onclick="exportLogs('csv')" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors font-medium flex items-center justify-center gap-2 text-sm">
                    <i class="fas fa-file-csv"></i> Export as CSV
                </button>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Audit Statistics</h3>
            
            <div class="space-y-3">
                <div>
                    <p class="text-xs text-gray-600 font-medium">Total Actions Logged</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalActions ?? 0 }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-600 font-medium">Last 30 Days</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $lastMonthActions ?? 0 }} actions</p>
                </div>
                <div>
                    <p class="text-xs text-gray-600 font-medium">Data Retention</p>
                    <p class="text-sm text-gray-700">Audit logs are retained indefinitely</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Governance Information -->
    <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
        <h4 class="font-bold text-purple-900 mb-2">Governance & Compliance</h4>
        <p class="text-sm text-purple-800 mb-2">
            This system maintains complete audit trails for all results processing activities to ensure transparency and accountability.
        </p>
        <ul class="text-sm text-purple-800 space-y-1 ml-4">
            <li>✓ Every action is logged with user, timestamp, IP address, and status</li>
            <li>✓ Audit logs are immutable - they cannot be edited or deleted</li>
            <li>✓ Complete history of grading profile changes</li>
            <li>✓ Processing history with detailed metrics</li>
            <li>✓ Publication and unpublication tracking</li>
            <li>✓ Compliance-ready for institutional audits</li>
        </ul>
    </div>
</div>

<script>
function exportLogs(format) {
    const filename = `Audit-Logs-${new Date().toISOString().split('T')[0]}.${format}`;
    alert(`Logs would be exported as ${format.toUpperCase()}`);
    // Implementation will handle actual export
}
</script>
@endsection
