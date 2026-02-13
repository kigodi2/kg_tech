@extends('results.acsee.layout')

@section('page-title', 'Candidate Result')
@section('page-subtitle', $candidate->fullname ?? 'View individual result')
@section('breadcrumb-active', 'Candidate Result')

@section('results-content')
<div class="space-y-6">
    
    <!-- Candidate Information Card -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-start justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $candidate->fullname ?? 'Unknown' }}</h2>
                <p class="text-gray-600 mt-1">Index Number: <span class="font-mono font-bold">{{ $candidate->index_number ?? 'N/A' }}</span></p>
            </div>
            <div class="text-right">
                <p class="text-gray-600"><strong>School:</strong> {{ $candidate->school?->name ?? 'N/A' }}</p>
                <p class="text-gray-600 mt-1"><strong>Combination:</strong> {{ $candidate->combination ?? 'N/A' }}</p>
            </div>
        </div>

        <!-- Result Summary -->
        <div class="grid grid-cols-4 gap-4">
            <div class="bg-blue-50 rounded-lg p-4">
                <p class="text-sm text-gray-600 mb-1">Total Score</p>
                <p class="text-2xl font-bold text-blue-600">{{ $registration->total_score ?? '-' }}/500</p>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
                <p class="text-sm text-gray-600 mb-1">Grade</p>
                <p class="text-2xl font-bold text-green-600">{{ $registration->grade ?? '-' }}</p>
            </div>
            <div class="bg-purple-50 rounded-lg p-4">
                <p class="text-sm text-gray-600 mb-1">GPA</p>
                <p class="text-2xl font-bold text-purple-600">{{ $registration->gpa ?? '-' }}/4.0</p>
            </div>
            <div class="bg-orange-50 rounded-lg p-4">
                <p class="text-sm text-gray-600 mb-1">Division</p>
                <p class="text-2xl font-bold text-orange-600">
                    @if($registration->division)
                        {{ ['1st', '2nd', '3rd', '4th'][$registration->division - 1] ?? 'Fail' }}
                    @else
                        -
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- Subject Results -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">Subject Marks</h3>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Subject Code</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Subject Name</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Marks</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Grade</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">GPA</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr class="hover:bg-gray-50 transition-colors">
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-2xl mb-2 block"></i>
                        <p>No subject marks recorded</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Result Status & Actions -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Result Status</h3>
        
        <div class="grid grid-cols-3 gap-6 mb-6">
            <div>
                <p class="text-gray-600 text-sm mb-1">Current Status</p>
                <p class="text-lg font-bold">
                    @if($registration->result_status === 'published')
                        <span class="inline-block px-3 py-1 bg-green-100 text-green-700 text-sm font-bold rounded">
                            PUBLISHED
                        </span>
                    @elseif($registration->result_status === 'final')
                        <span class="inline-block px-3 py-1 bg-blue-100 text-blue-700 text-sm font-bold rounded">
                            FINAL
                        </span>
                    @else
                        <span class="inline-block px-3 py-1 bg-gray-100 text-gray-700 text-sm font-bold rounded">
                            DRAFT
                        </span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-gray-600 text-sm mb-1">Published At</p>
                <p class="text-lg font-bold">{{ $registration->published_at?->format('M d, Y H:i') ?? 'Not Published' }}</p>
            </div>
            <div>
                <p class="text-gray-600 text-sm mb-1">Last Updated</p>
                <p class="text-lg font-bold">{{ $registration->updated_at?->format('M d, Y H:i') ?? '-' }}</p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-4">
            @if($registration->result_status === 'final' && $registration->result_status !== 'published')
                <button onclick="publishResult({{ $registration->id }})" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors font-medium flex items-center gap-2">
                    <i class="fas fa-check"></i> Publish Result
                </button>
            @elseif($registration->result_status === 'published')
                <button onclick="unpublishResult({{ $registration->id }})" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2 rounded-lg transition-colors font-medium flex items-center gap-2">
                    <i class="fas fa-undo"></i> Unpublish Result
                </button>
            @endif
            <a href="{{ route('results.acsee.results.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg transition-colors font-medium">
                Back to Results
            </a>
        </div>
    </div>
</div>

<script>
function publishResult(id) {
    if (confirm('Publish this result? It will become visible to the candidate and school.')) {
        // Implementation will post to publish endpoint
        alert('Result would be published');
    }
}

function unpublishResult(id) {
    if (confirm('Unpublish this result? It will no longer be visible to the candidate.')) {
        // Implementation will post to unpublish endpoint
        alert('Result would be unpublished');
    }
}
</script>
@endsection
