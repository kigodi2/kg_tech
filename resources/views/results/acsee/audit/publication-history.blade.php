@extends('results.acsee.layout')

@section('page-title', 'Publication History')
@section('page-subtitle', 'Publish and unpublish event tracking')
@section('breadcrumb-active', 'Publication History')

@section('results-content')
<div class="space-y-6">
    
    <!-- Summary -->
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Total Events</div>
            <div class="text-3xl font-bold text-gray-900">{{ $history->total() ?? 0 }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Publications</div>
            <div class="text-3xl font-bold text-green-600">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Unpublications</div>
            <div class="text-3xl font-bold text-orange-600">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Last Event</div>
            <div class="text-sm font-bold text-gray-900">
                @if($history && $history->first())
                    {{ $history->first()->created_at?->diffForHumans() ?? '-' }}
                @else
                    -
                @endif
            </div>
        </div>
    </div>

    <!-- Publication Timeline -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">Publication Timeline</h3>
        </div>

        <div class="relative">
            @forelse($history ?? [] as $index => $event)
                <div class="px-6 py-6 border-b border-gray-200 hover:bg-gray-50 transition-colors" @if($index === 0) style="border-bottom: none" @endif>
                    <div class="flex items-start gap-6">
                        <!-- Timeline marker -->
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-white {{ $event->action === 'publish_result' ? 'bg-green-600' : 'bg-orange-600' }}">
                                @if($event->action === 'publish_result')
                                    <i class="fas fa-check text-xs"></i>
                                @else
                                    <i class="fas fa-times text-xs"></i>
                                @endif
                            </div>
                            @if(!$loop->last)
                                <div class="w-1 h-12 bg-gray-300"></div>
                            @endif
                        </div>

                        <!-- Event details -->
                        <div class="flex-1 pt-1">
                            <div class="flex items-center gap-4 mb-2">
                                <h4 class="font-bold text-gray-900">
                                    @if($event->action === 'publish_result')
                                        <span class="inline-block px-3 py-1 bg-green-100 text-green-700 text-xs font-bold rounded mr-2">PUBLISHED</span>
                                        Results Published
                                    @else
                                        <span class="inline-block px-3 py-1 bg-orange-100 text-orange-700 text-xs font-bold rounded mr-2">UNPUBLISHED</span>
                                        Results Unpublished
                                    @endif
                                </h4>
                            </div>
                            
                            <div class="grid grid-cols-3 gap-4 text-sm mb-2">
                                <div>
                                    <p class="text-gray-600"><strong>User:</strong> {{ $event->user?->name ?? 'Unknown' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-600"><strong>IP Address:</strong> <span class="font-mono text-xs">{{ $event->ip_address ?? '-' }}</span></p>
                                </div>
                                <div>
                                    <p class="text-gray-600"><strong>Timestamp:</strong> {{ $event->created_at?->format('M d, Y H:i:s') ?? '-' }}</p>
                                </div>
                            </div>

                            @if($event->metadata)
                                <div class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <p class="text-xs text-gray-600"><strong>Details:</strong></p>
                                    <p class="text-sm text-gray-700 mt-1">
                                        @php
                                            $metadata = is_array($event->metadata) ? $event->metadata : json_decode($event->metadata, true);
                                        @endphp
                                        {{ implode(', ', (array)$metadata) ?? 'No additional details' }}
                                    </p>
                                </div>
                            @endif
                        </div>

                        <!-- Status -->
                        <div class="text-right">
                            <span class="inline-block px-3 py-1 text-xs font-bold rounded {{ $event->status === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ ucfirst($event->status ?? 'unknown') }}
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-3xl mb-2 block"></i>
                    <p>No publication events recorded</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Pagination -->
    @if($history && $history->hasPages())
        <div class="bg-white rounded-lg shadow p-4">
            {{ $history->links() }}
        </div>
    @endif

    <!-- Publication Guidelines -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h3 class="font-bold text-blue-900 mb-2">Publication Guidelines</h3>
        <p class="text-sm text-blue-800 mb-3">
            Results must go through a complete workflow before final publication:
        </p>
        <ol class="text-sm text-blue-800 space-y-2 ml-4 list-decimal">
            <li>Marks are imported and assigned to candidates</li>
            <li>Grades are calculated using active grading profile</li>
            <li>Results are processed through Draft and Final runs</li>
            <li>All validation checks must pass</li>
            <li>Results are approved by authorized personnel</li>
            <li>Results are published to candidates and schools</li>
            <li>Publication events are logged in this audit trail</li>
        </ol>
    </div>
</div>
@endsection
