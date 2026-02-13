@extends('layouts.mark-entry')

@section('content')
<div class="w-full">
    <!-- Page Header -->
    <div class="bg-white border-b border-gray-200 px-8 py-6 sticky top-0 z-40 shadow-sm">
        <h1 class="text-3xl font-bold text-gray-800">ACSEE Mark Entry Lifecycle</h1>
        <p class="text-gray-600 mt-2">Complete overview of mark processing workflow across all stages</p>
    </div>

    <div class="px-8 py-8">
        <!-- Lifecycle Flow Visualization -->
        <div class="bg-white rounded-lg shadow p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-8">Lifecycle Progression</h2>
            
            <div class="flex items-center justify-between mb-12">
                <!-- Stage 1: Entry -->
                <div class="flex flex-col items-center flex-1">
                    <div class="w-16 h-16 bg-blue-100 border-2 border-blue-500 rounded-full flex items-center justify-center mb-3">
                        <i class="fas fa-upload text-blue-600 text-xl"></i>
                    </div>
                    <p class="text-sm font-semibold text-gray-800">Entry</p>
                    <p class="text-xs text-gray-600 mt-1">Upload marks</p>
                </div>

                <div class="flex-1 h-1 bg-gray-300 mx-2"></div>

                <!-- Stage 2: Validation -->
                <div class="flex flex-col items-center flex-1">
                    <div class="w-16 h-16 bg-yellow-100 border-2 border-yellow-500 rounded-full flex items-center justify-center mb-3">
                        <i class="fas fa-check text-yellow-600 text-xl"></i>
                    </div>
                    <p class="text-sm font-semibold text-gray-800">Validation</p>
                    <p class="text-xs text-gray-600 mt-1">Check data</p>
                </div>

                <div class="flex-1 h-1 bg-gray-300 mx-2"></div>

                <!-- Stage 3: Moderation -->
                <div class="flex flex-col items-center flex-1">
                    <div class="w-16 h-16 bg-purple-100 border-2 border-purple-500 rounded-full flex items-center justify-center mb-3">
                        <i class="fas fa-eye text-purple-600 text-xl"></i>
                    </div>
                    <p class="text-sm font-semibold text-gray-800">Moderation</p>
                    <p class="text-xs text-gray-600 mt-1">HOD review</p>
                </div>

                <div class="flex-1 h-1 bg-gray-300 mx-2"></div>

                <!-- Stage 4: Submission -->
                <div class="flex flex-col items-center flex-1">
                    <div class="w-16 h-16 bg-green-100 border-2 border-green-500 rounded-full flex items-center justify-center mb-3">
                        <i class="fas fa-lock text-green-600 text-xl"></i>
                    </div>
                    <p class="text-sm font-semibold text-gray-800">Submission</p>
                    <p class="text-xs text-gray-600 mt-1">Lock & submit</p>
                </div>

                <div class="flex-1 h-1 bg-gray-300 mx-2"></div>

                <!-- Stage 5: Complete -->
                <div class="flex flex-col items-center flex-1">
                    <div class="w-16 h-16 bg-gray-100 border-2 border-gray-500 rounded-full flex items-center justify-center mb-3">
                        <i class="fas fa-archive text-gray-600 text-xl"></i>
                    </div>
                    <p class="text-sm font-semibold text-gray-800">Archived</p>
                    <p class="text-xs text-gray-600 mt-1">Complete</p>
                </div>
            </div>
        </div>

        <!-- Status Distribution -->
        <div class="grid grid-cols-6 gap-4 mb-8">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-6 border border-blue-200">
                <p class="text-sm font-semibold text-gray-700">Draft</p>
                <p class="text-3xl font-bold text-blue-600 mt-2">0</p>
                <div class="w-full bg-blue-200 rounded-full h-1 mt-4"></div>
            </div>

            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg p-6 border border-yellow-200">
                <p class="text-sm font-semibold text-gray-700">Validated</p>
                <p class="text-3xl font-bold text-yellow-600 mt-2">0</p>
                <div class="w-full bg-yellow-200 rounded-full h-1 mt-4"></div>
            </div>

            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-6 border border-purple-200">
                <p class="text-sm font-semibold text-gray-700">Awaiting Review</p>
                <p class="text-3xl font-bold text-purple-600 mt-2">0</p>
                <div class="w-full bg-purple-200 rounded-full h-1 mt-4"></div>
            </div>

            <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-lg p-6 border border-indigo-200">
                <p class="text-sm font-semibold text-gray-700">Approved</p>
                <p class="text-3xl font-bold text-indigo-600 mt-2">0</p>
                <div class="w-full bg-indigo-200 rounded-full h-1 mt-4"></div>
            </div>

            <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-6 border border-red-200">
                <p class="text-sm font-semibold text-gray-700">Rejected</p>
                <p class="text-3xl font-bold text-red-600 mt-2">0</p>
                <div class="w-full bg-red-200 rounded-full h-1 mt-4"></div>
            </div>

            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-6 border border-green-200">
                <p class="text-sm font-semibold text-gray-700">Submitted</p>
                <p class="text-3xl font-bold text-green-600 mt-2">0</p>
                <div class="w-full bg-green-200 rounded-full h-1 mt-4"></div>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="bg-white rounded-lg shadow p-8">
            <h2 class="text-xl font-bold text-gray-800 mb-6">Recent Activity</h2>
            
            <div class="space-y-4">
                <div class="flex items-start gap-4 pb-4 border-b border-gray-200">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                        <i class="fas fa-upload text-blue-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800">Marks uploaded for submission</p>
                        <p class="text-sm text-gray-600 mt-1">No recent uploads yet</p>
                    </div>
                </div>

                <div class="flex items-start gap-4 pb-4 border-b border-gray-200">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                        <i class="fas fa-check text-green-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800">Batches approved by moderators</p>
                        <p class="text-sm text-gray-600 mt-1">No recent approvals</p>
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                        <i class="fas fa-times text-red-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800">Batches returned for revision</p>
                        <p class="text-sm text-gray-600 mt-1">No recent rejections</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
