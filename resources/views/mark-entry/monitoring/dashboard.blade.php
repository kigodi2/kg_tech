@extends('layout')

@section('content')
<div class="w-full">
    <div class="bg-white border-b border-gray-200 px-8 py-6 sticky top-0 z-40 shadow-sm">
        <h1 class="text-2xl font-bold text-gray-800">ACSEE Marks Lifecycle Monitoring</h1>
    </div>

    <div class="px-8 py-8">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h2 class="text-lg font-bold text-blue-900 mb-4">Lifecycle Status Overview</h2>
            
            <div class="grid grid-cols-6 gap-4">
                <div class="bg-white rounded p-4 border border-blue-100 text-center">
                    <p class="text-2xl font-bold text-blue-600">{{ $batches->where('lifecycle_state', 'draft')->count() }}</p>
                    <p class="text-xs text-gray-600 font-semibold mt-1">Draft</p>
                </div>
                <div class="bg-white rounded p-4 border border-yellow-100 text-center">
                    <p class="text-2xl font-bold text-yellow-600">{{ $batches->where('lifecycle_state', 'validated')->count() }}</p>
                    <p class="text-xs text-gray-600 font-semibold mt-1">Validated</p>
                </div>
                <div class="bg-white rounded p-4 border border-purple-100 text-center">
                    <p class="text-2xl font-bold text-purple-600">{{ $batches->where('lifecycle_state', 'awaiting_moderation')->count() }}</p>
                    <p class="text-xs text-gray-600 font-semibold mt-1">Awaiting Review</p>
                </div>
                <div class="bg-white rounded p-4 border border-green-100 text-center">
                    <p class="text-2xl font-bold text-green-600">{{ $batches->where('lifecycle_state', 'approved')->count() }}</p>
                    <p class="text-xs text-gray-600 font-semibold mt-1">Approved</p>
                </div>
                <div class="bg-white rounded p-4 border border-red-100 text-center">
                    <p class="text-2xl font-bold text-red-600">{{ $batches->where('lifecycle_state', 'rejected')->count() }}</p>
                    <p class="text-xs text-gray-600 font-semibold mt-1">Rejected</p>
                </div>
                <div class="bg-white rounded p-4 border border-gray-100 text-center">
                    <p class="text-2xl font-bold text-gray-600">{{ $batches->where('lifecycle_state', 'submitted')->count() }}</p>
                    <p class="text-xs text-gray-600 font-semibold mt-1">Submitted</p>
                </div>
            </div>
        </div>

        <div class="mt-8 bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">All Batches</h2>
            <p class="text-gray-600">Total: {{ $batches->count() }} batches</p>
        </div>
    </div>
</div>
@endsection
