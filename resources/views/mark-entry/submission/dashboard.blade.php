@extends('layouts.mark-entry')

@section('content')
<div class="w-full">
    <div class="bg-white border-b border-gray-200 px-8 py-6 sticky top-0 z-40 shadow-sm">
        <h1 class="text-2xl font-bold text-gray-800">ACSEE Marks Submission & Locking</h1>
    </div>

    <div class="px-8 py-8">
        <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="bg-purple-50 rounded-lg p-6 border border-purple-200">
                <p class="text-gray-600 text-sm font-semibold mb-2">Ready to Lock</p>
                <p class="text-3xl font-bold text-purple-600">{{ $batches->count() }}</p>
            </div>
            <div class="bg-blue-50 rounded-lg p-6 border border-blue-200">
                <p class="text-gray-600 text-sm font-semibold mb-2">Locked Batches</p>
                <p class="text-3xl font-bold text-blue-600">0</p>
            </div>
            <div class="bg-green-50 rounded-lg p-6 border border-green-200">
                <p class="text-gray-600 text-sm font-semibold mb-2">Submitted</p>
                <p class="text-3xl font-bold text-green-600">0</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-800">Approved Batches Ready for Submission</h2>
            </div>

            @if($batches->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Batch Code</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">School</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Approved Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($batches as $batch)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ $batch->batch_code }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $batch->school->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $batch->subject->code }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-purple-100 text-purple-700 text-xs font-semibold rounded-full">
                                    {{ $batch->lifecycle_state }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $batch->updated_at?->format('M d, Y') }}</td>
                            <td class="px-6 py-4 text-sm">
                                <button onclick="lockBatch({{ $batch->id }})" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium text-sm rounded-lg transition-colors">
                                    <i class="fas fa-lock mr-2"></i> Lock & Submit
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200">
                {{ $batches->links() }}
            </div>
            @else
            <div class="px-6 py-12 text-center">
                <i class="fas fa-inbox text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-600 font-semibold">No batches awaiting submission</p>
                <p class="text-sm text-gray-500">Waiting for approved batches from moderation</p>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function lockBatch(batchId) {
    if (confirm('Lock this batch for final submission? This action cannot be undone.')) {
        fetch(`/mark-entry/acsee/submission/lock/${batchId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Batch locked successfully');
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}
</script>
@endsection
