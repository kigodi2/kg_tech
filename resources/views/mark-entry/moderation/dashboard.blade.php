@extends('layout')

@section('content')
<div class="w-full">
    <div class="bg-white border-b border-gray-200 px-8 py-6 sticky top-0 z-40 shadow-sm">
        <h1 class="text-2xl font-bold text-gray-800">ACSEE Mark Moderation Dashboard</h1>
    </div>

    <div class="px-8 py-8">
        <div class="grid grid-cols-4 gap-4 mb-8">
            <div class="bg-blue-50 rounded-lg p-6 border border-blue-200">
                <p class="text-gray-600 text-sm font-semibold mb-2">Awaiting Review</p>
                <p class="text-3xl font-bold text-blue-600">{{ $batches->count() }}</p>
            </div>
            <div class="bg-yellow-50 rounded-lg p-6 border border-yellow-200">
                <p class="text-gray-600 text-sm font-semibold mb-2">Total Batches</p>
                <p class="text-3xl font-bold text-yellow-600">{{ $batches->total() }}</p>
            </div>
            <div class="bg-green-50 rounded-lg p-6 border border-green-200">
                <p class="text-gray-600 text-sm font-semibold mb-2">Approved This Week</p>
                <p class="text-3xl font-bold text-green-600">0</p>
            </div>
            <div class="bg-red-50 rounded-lg p-6 border border-red-200">
                <p class="text-gray-600 text-sm font-semibold mb-2">Rejected This Week</p>
                <p class="text-3xl font-bold text-red-600">0</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-800">Batches Awaiting Moderation</h2>
            </div>

            @if($batches->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Batch Code</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">School</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Candidates</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Uploaded</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($batches as $batch)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ $batch->batch_code }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $batch->school->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $batch->subject->code ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $batch->total_records }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">
                                    {{ $batch->lifecycle_state ?? 'unknown' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $batch->imported_at?->format('M d, Y') }}</td>
                            <td class="px-6 py-4 text-sm">
                                <a href="{{ route('mark-entry.acsee.moderation.review-batch', $batch->id) }}" 
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-lg transition-colors">
                                    <i class="fas fa-eye mr-2"></i> Review
                                </a>
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
                <i class="fas fa-check-circle text-4xl text-green-500 mb-4"></i>
                <p class="text-gray-600 font-semibold">No batches awaiting moderation</p>
                <p class="text-sm text-gray-500">All submitted marks have been reviewed</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
