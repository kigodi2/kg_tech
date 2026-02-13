@extends('results.acsee.layout')

@section('page-title', 'Result Processing')
@section('page-subtitle', 'Grade candidates, compute GPA, and assign divisions')
@section('breadcrumb-active', 'Result Processing')

@section('results-content')
<div class="space-y-6">
    
    <!-- Pre-Processing Validation -->
    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
        <div class="flex items-start justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Pre-Processing Validation</h3>
                <p class="text-sm text-gray-600 mb-3">Verify all data is complete before processing</p>
                <div id="validationStatus" class="space-y-2">
                    <p class="text-sm"><span class="inline-block px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs font-semibold">⏳ Checking</span></p>
                </div>
            </div>
            <button onclick="validateData()" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg transition-colors font-medium flex items-center gap-2">
                <i class="fas fa-check-circle"></i> Run Validation
            </button>
        </div>
    </div>

    <!-- Processing Options -->
    <div class="grid grid-cols-2 gap-6">
        
        <!-- Draft Run -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Draft Run</h3>
                    <p class="text-sm text-gray-600 mt-1">Safe testing - results not locked</p>
                </div>
                <i class="fas fa-flask text-blue-500 text-2xl"></i>
            </div>

            <div class="space-y-3 mb-4">
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>Test grade calculations</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>View preview results</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>Rollback anytime</span>
                </div>
            </div>

            <button onclick="startDraftRun()" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors font-medium flex items-center justify-center gap-2">
                <i class="fas fa-play"></i> Start Draft Run
            </button>
        </div>

        <!-- Final Run -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Final Run</h3>
                    <p class="text-sm text-gray-600 mt-1">Permanent processing - results locked</p>
                </div>
                <i class="fas fa-lock text-green-500 text-2xl"></i>
            </div>

            <div class="space-y-3 mb-4">
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>Process all candidates</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>Lock results</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-check text-green-600"></i>
                    <span>Ready for publication</span>
                </div>
            </div>

            <button onclick="confirmFinalRun()" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors font-medium flex items-center justify-center gap-2">
                <i class="fas fa-check-double"></i> Start Final Run
            </button>
        </div>
    </div>

    <!-- Processing History -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                <i class="fas fa-history text-blue-600"></i>
                Processing History
            </h3>
        </div>

        <div class="divide-y">
            @forelse($processes ?? [] as $process)
                <div class="px-6 py-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <p class="font-semibold text-gray-900">
                                <span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded mr-2">
                                    {{ strtoupper($process->type) }}
                                </span>
                                Run
                            </p>
                            <p class="text-sm text-gray-600">{{ $process->processed_at?->diffForHumans() }}</p>
                        </div>
                        
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900">{{ $process->processed_count }} / {{ $process->total_candidates }}</p>
                            <span class="inline-block px-3 py-1 text-xs font-bold rounded {{ $process->status === 'completed' ? 'bg-green-100 text-green-700' : ($process->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                {{ ucfirst($process->status) }}
                            </span>
                        </div>
                    </div>

                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ ($process->processed_count / $process->total_candidates * 100) ?? 0 }}%"></div>
                    </div>

                    <div class="mt-2 flex items-center justify-between text-xs text-gray-600">
                        <span>{{ $process->user->name }}</span>
                        <span>{{ $process->completed_at?->format('M d, H:i') ?? '-' }}</span>
                    </div>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-2xl mb-2"></i>
                    <p class="text-sm">No processing history yet</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Important Notes -->
    <div class="bg-red-50 border border-red-200 rounded-lg p-6">
        <h4 class="font-bold text-red-900 mb-2">⚠️ Important</h4>
        <ul class="text-sm text-red-800 space-y-1">
            <li>✓ Run validation before processing</li>
            <li>✓ Draft run can be rolled back anytime</li>
            <li>✓ Final run results are locked permanently</li>
            <li>✓ Processing cannot be reversed once final</li>
        </ul>
    </div>
</div>

<script>
function validateData() {
    document.getElementById('validationStatus').innerHTML = '<p class="text-sm"><span class="inline-block px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs font-semibold">⏳ Validating...</span></p>';
    
    fetch('{{ route("results.acsee.processing.validate") }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
    })
    .then(r => r.json())
    .then(data => {
        if (data.valid) {
            document.getElementById('validationStatus').innerHTML = '<p class="text-sm"><span class="inline-block px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-semibold">✓ All checks passed</span></p>';
        } else {
            const issues = data.issues.map(i => `<li class="text-sm text-red-700">• ${i}</li>`).join('');
            document.getElementById('validationStatus').innerHTML = `<ul class="space-y-1">${issues}</ul>`;
        }
    });
}

function startDraftRun() {
    if (confirm('Start draft processing run? This will grade all candidates.')) {
        fetch('{{ route("results.acsee.processing.draft-run") }}', {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Draft processing started. Refresh to see progress.');
                location.reload();
            }
        });
    }
}

function confirmFinalRun() {
    if (confirm('⚠️ Start FINAL processing? This will lock all results permanently and cannot be undone.')) {
        if (confirm('Are you absolutely sure? This action is irreversible.')) {
            fetch('{{ route("results.acsee.processing.final-run") }}', {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify({confirm: true})
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Final processing started. Results are now locked.');
                    location.reload();
                }
            });
        }
    }
}

// Auto-validate on page load
validateData();
</script>
@endsection
