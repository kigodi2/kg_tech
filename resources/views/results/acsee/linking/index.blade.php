@extends('results.acsee.layout')

@section('page-title', 'Result Linking Validation')
@section('page-subtitle', 'Verify all candidates are properly linked before processing')
@section('breadcrumb-active', 'Result Linking')

@section('results-content')
<div class="space-y-6">

    <!-- Overall Status -->
    <div id="statusCard" class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
        <div class="flex items-start justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Validation Status</h3>
                <p id="statusMessage" class="text-gray-600">Checking data integrity...</p>
            </div>
            <button onclick="revalidate()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors font-medium flex items-center gap-2">
                <i class="fas fa-sync-alt"></i> Recheck
            </button>
        </div>
    </div>

    <!-- Missing School Links -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Missing School Links</h3>
            <span id="schoolCount" class="text-2xl font-bold text-red-600">0</span>
        </div>
        
        <div id="schoolLinks" class="space-y-2">
            <p class="text-sm text-gray-600">No missing school links</p>
        </div>

        @if(($report['missing_schools'] ?? 0) > 0)
            <button onclick="fixMissing('schools')" class="mt-4 bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg transition-colors font-medium flex items-center gap-2">
                <i class="fas fa-magic"></i> Auto-Fix Missing Schools
            </button>
        @endif
    </div>

    <!-- Missing Combinations -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Missing Combinations</h3>
            <span id="combinationCount" class="text-2xl font-bold text-red-600">0</span>
        </div>
        
        <div id="combinationLinks" class="space-y-2">
            <p class="text-sm text-gray-600">No missing combinations</p>
        </div>

        @if(($report['missing_combinations'] ?? 0) > 0)
            <button onclick="fixMissing('combinations')" class="mt-4 bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg transition-colors font-medium flex items-center gap-2">
                <i class="fas fa-magic"></i> Auto-Fix Missing Combinations
            </button>
        @endif
    </div>

    <!-- Invalid Combinations -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Invalid Combinations</h3>
            <span id="invalidCount" class="text-2xl font-bold text-red-600">0</span>
        </div>
        
        <div id="invalidLinks" class="space-y-2">
            <p class="text-sm text-gray-600">No invalid combinations found</p>
        </div>
    </div>

    <!-- Missing Subject Selections -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Missing Subject Selections</h3>
            <span id="subjectCount" class="text-2xl font-bold text-red-600">0</span>
        </div>
        
        <div id="subjectLinks" class="space-y-2">
            <p class="text-sm text-gray-600">No missing subject selections</p>
        </div>

        @if(($report['missing_subjects'] ?? 0) > 0)
            <button onclick="fixMissing('subjects')" class="mt-4 bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg transition-colors font-medium flex items-center gap-2">
                <i class="fas fa-magic"></i> Auto-Fix Missing Subjects
            </button>
        @endif
    </div>

    <!-- Validation Summary -->
    <div class="grid grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 font-medium mb-1">Issues Found</p>
            <p id="totalIssues" class="text-2xl font-bold text-red-600">0</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 font-medium mb-1">Total Candidates</p>
            <p class="text-2xl font-bold text-gray-900">{{ $report['total_candidates'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 font-medium mb-1">Linked Candidates</p>
            <p id="linkedCount" class="text-2xl font-bold text-green-600">0</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 font-medium mb-1">Validation Status</p>
            <p id="statusBadge" class="text-lg font-bold text-yellow-600">Pending</p>
        </div>
    </div>

    <!-- Ready to Process? -->
    <div id="readyCard" class="hidden bg-green-50 border border-green-200 rounded-lg p-6">
        <h3 class="text-lg font-bold text-green-900 mb-2">✓ Ready for Processing</h3>
        <p class="text-sm text-green-800 mb-4">All candidates are properly linked and ready for result processing.</p>
        <a href="{{ route('results.acsee.processing.index') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors font-medium inline-block">
            Proceed to Processing
        </a>
    </div>
</div>

<script>
function revalidate() {
    fetch('{{ route("results.acsee.linking.validate") }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
    })
    .then(r => r.json())
    .then(data => updateStatus(data));
}

function updateStatus(data) {
    const total = {{ $report['total_candidates'] ?? 0 }};
    const issues = (data.issues || []).length;
    
    document.getElementById('totalIssues').textContent = issues;
    document.getElementById('linkedCount').textContent = total - issues;
    
    if (issues === 0) {
        document.getElementById('statusCard').className = 'bg-white rounded-lg shadow p-6 border-l-4 border-green-500';
        document.getElementById('statusMessage').innerHTML = '<i class="fas fa-check-circle text-green-600"></i> All checks passed!';
        document.getElementById('statusBadge').textContent = '✓ Ready';
        document.getElementById('statusBadge').className = 'text-lg font-bold text-green-600';
        document.getElementById('readyCard').classList.remove('hidden');
    } else {
        document.getElementById('statusCard').className = 'bg-white rounded-lg shadow p-6 border-l-4 border-red-500';
        document.getElementById('statusMessage').innerHTML = `<i class="fas fa-times-circle text-red-600"></i> ${issues} issue(s) found`;
        document.getElementById('statusBadge').textContent = 'Issues';
        document.getElementById('statusBadge').className = 'text-lg font-bold text-red-600';
        document.getElementById('readyCard').classList.add('hidden');
    }
}

function fixMissing(type) {
    if (confirm(`Auto-fix ${type} issues? This will attempt to resolve missing links.`)) {
        fetch('{{ route("results.acsee.linking.fix-missing") }}', {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify({type: type})
        })
        .then(r => r.json())
        .then(data => {
            alert(`Fixed ${data.fixed_count} records`);
            revalidate();
        });
    }
}

// Auto-validate on page load
revalidate();
</script>
@endsection
