@extends('results.acsee.layout')

@section('page-title', 'Processing History')
@section('page-subtitle', 'Result processing runs and batch operations')
@section('breadcrumb-active', 'Processing History')

@section('results-content')
<div class="space-y-6">
    
    <!-- Summary -->
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Total Runs</div>
            <div class="text-3xl font-bold text-gray-900">{{ $history->total() ?? 0 }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Completed</div>
            <div class="text-3xl font-bold text-green-600">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Failed</div>
            <div class="text-3xl font-bold text-red-600">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">In Progress</div>
            <div class="text-3xl font-bold text-yellow-600">-</div>
        </div>
    </div>

    <!-- Processing History Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Batch ID</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Total</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Processed</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Errors</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Started By</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Started At</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($history ?? [] as $process)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-sm font-mono text-gray-700">{{ $process->id ?? '-' }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-block px-3 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded">
                                {{ ucfirst($process->type ?? 'unknown') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center text-sm text-gray-700">{{ $process->total_candidates ?? 0 }}</td>
                        <td class="px-6 py-4 text-center text-sm text-gray-700">{{ $process->processed_count ?? 0 }}</td>
                        <td class="px-6 py-4 text-center text-sm">
                            @if($process->error_count ?? 0)
                                <span class="text-red-600 font-semibold">{{ $process->error_count }}</span>
                            @else
                                <span class="text-green-600">0</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($process->status === 'completed')
                                <span class="inline-block px-3 py-1 bg-green-100 text-green-700 text-xs font-bold rounded">Completed</span>
                            @elseif($process->status === 'failed')
                                <span class="inline-block px-3 py-1 bg-red-100 text-red-700 text-xs font-bold rounded">Failed</span>
                            @elseif($process->status === 'in_progress')
                                <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-700 text-xs font-bold rounded">In Progress</span>
                            @else
                                <span class="inline-block px-3 py-1 bg-gray-100 text-gray-700 text-xs font-bold rounded">{{ ucfirst($process->status ?? 'unknown') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $process->user?->name ?? 'Unknown' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            <div>{{ $process->started_at?->format('M d, Y') ?? '-' }}</div>
                            <div class="text-xs text-gray-500">{{ $process->started_at?->format('H:i:s') ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <div class="flex items-center gap-2">
                                <a href="#" class="text-blue-600 hover:text-blue-800" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($process->status === 'completed')
                                    <form method="POST" action="#" class="inline" onclick="return confirm('Rollback this processing run?');">
                                        @csrf
                                        <button type="submit" class="text-orange-600 hover:text-orange-800" title="Rollback">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-3xl mb-2 block"></i>
                            <p>No processing history available</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($history && $history->hasPages())
        <div class="bg-white rounded-lg shadow p-4">
            {{ $history->links() }}
        </div>
    @endif

    <!-- Batch Error Details (if any) -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Error Details</h3>
        <p class="text-sm text-gray-600">
            Select a processing run from above to view detailed error logs.
        </p>
    </div>
</div>
@endsection
