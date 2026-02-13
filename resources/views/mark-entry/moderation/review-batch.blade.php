@extends('layouts.mark-entry')

@section('content')
<div class="w-full">
    <div class="bg-white border-b border-gray-200 px-8 py-6 sticky top-0 z-40 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Review Batch: {{ $batch->batch_code }}</h1>
                <p class="text-sm text-gray-600 mt-1">School: {{ $batch->school->name }} | Subject: {{ $batch->subject->code }}</p>
            </div>
            <span class="px-4 py-2 bg-blue-100 text-blue-700 font-semibold rounded-lg">
                {{ $batch->lifecycle_state }}
            </span>
        </div>
    </div>

    <div class="px-8 py-8 space-y-6" x-data="reviewManager()" @init="init()">
        <!-- Summary Stats -->
        <div class="grid grid-cols-4 gap-4">
            <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                <p class="text-sm font-semibold text-gray-700">Total Candidates</p>
                <p class="text-2xl font-bold text-green-600">{{ $batch->total_records }}</p>
            </div>
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                <p class="text-sm font-semibold text-gray-700">Valid Records</p>
                <p class="text-2xl font-bold text-blue-600">{{ $batch->valid_records }}</p>
            </div>
            <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                <p class="text-sm font-semibold text-gray-700">Error Records</p>
                <p class="text-2xl font-bold text-red-600">{{ $batch->error_records }}</p>
            </div>
            <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                <p class="text-sm font-semibold text-gray-700">Data Quality</p>
                <p class="text-2xl font-bold text-yellow-600">
                    {{ $batch->total_records > 0 ? round(($batch->valid_records / $batch->total_records) * 100) : 0 }}%
                </p>
            </div>
        </div>

        <!-- Candidates Table Preview -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Candidates Preview (first 10)</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">#</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Index Number</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Full Name</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Paper 1</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Paper 2</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-600">-</td>
                            <td class="px-4 py-2 text-sm text-gray-600 italic">No marks loaded yet</td>
                            <td class="px-4 py-2 text-sm text-gray-600 italic">-</td>
                            <td class="px-4 py-2 text-sm text-gray-600 italic">-</td>
                            <td class="px-4 py-2 text-sm text-gray-600 italic">-</td>
                            <td class="px-4 py-2 text-sm text-gray-600 italic">-</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Review Actions -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Moderation Decision</h2>
            
            <div class="space-y-4">
                <!-- Approve -->
                <div>
                    <button @click="approve()" class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-check"></i> Approve Batch
                    </button>
                    <p class="text-xs text-gray-600 mt-2">Mark this batch as reviewed and approved. Ready for submission.</p>
                </div>

                <!-- Reject -->
                <div>
                    <button @click="showRejectForm = !showRejectForm" class="w-full px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-times"></i> Reject Batch
                    </button>
                    <p class="text-xs text-gray-600 mt-2">Return this batch to the school for corrections.</p>
                </div>

                <!-- Reject Form (Hidden) -->
                <div x-show="showRejectForm" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Rejection Reason *</label>
                    <textarea x-model="rejectionReason" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" rows="4" placeholder="Explain what needs to be corrected..."></textarea>
                    <div class="mt-3 flex gap-2">
                        <button @click="submitReject()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition-colors">
                            Reject
                        </button>
                        <button @click="showRejectForm = false" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function reviewManager() {
    return {
        showRejectForm: false,
        rejectionReason: '',
        
        approve() {
            if (confirm('Are you sure you want to approve this batch?')) {
                // Send approval to server
                fetch(`{{ route('mark-entry.acsee.moderation.approve', $batch->id) }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ feedback: '' })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Batch approved successfully');
                        window.location.href = `{{ route('mark-entry.acsee.moderation.dashboard') }}`;
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        },

        submitReject() {
            if (!this.rejectionReason.trim()) {
                alert('Please provide a rejection reason');
                return;
            }
            
            fetch(`{{ route('mark-entry.acsee.moderation.reject', $batch->id) }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ reason: this.rejectionReason })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Batch rejected and school notified');
                    window.location.href = `{{ route('mark-entry.acsee.moderation.dashboard') }}`;
                } else {
                    alert('Error: ' + data.message);
                }
            });
        },

        init() {
            // Initialize on page load
        }
    }
}
</script>
@endsection
