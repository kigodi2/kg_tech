@extends('layout')

@section('content')
<div class="w-full" style="font-family: 'Maiandra GD', sans-serif;">
    <!-- Page Header -->
    <div class="bg-white border-b border-gray-200 px-8 py-6 sticky top-0 z-40 shadow-sm">
        <h1 class="text-2xl font-bold text-gray-800" style="font-family: 'Maiandra GD', sans-serif;">Evaluations</h1>
    </div>

    <!-- Main Content -->
    <div class="px-8 py-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- ACSEE Evaluation Card -->
            <a href="/evaluations/acsee" class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6 border border-gray-200 hover:border-blue-500">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-bar text-blue-600 text-lg"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800" style="font-family: 'Maiandra GD', sans-serif;">ACSEE Evaluations</h2>
                </div>
                <p class="text-gray-600 text-sm mb-4" style="font-family: 'Maiandra GD', sans-serif;">View and analyze ACSEE examination evaluations including zonalwise, regionalwise, and districtwise performance analysis.</p>
                <div class="flex items-center text-blue-600 font-medium text-sm" style="font-family: 'Maiandra GD', sans-serif;">
                    <span>View Evaluations</span>
                    <i class="fas fa-arrow-right ml-2"></i>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection
