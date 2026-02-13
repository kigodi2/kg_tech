@extends('layout')

@section('content')
<div class="container mx-auto px-4 py-8">
    
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">ACSEE Results</h1>
        <p class="mt-2 text-gray-600">View published examination results by year and filters</p>
    </div>

    <!-- Error Messages -->
    @if ($errors->isNotEmpty())
        <div class="mb-6 rounded-lg bg-red-50 p-4 border border-red-200">
            <div class="flex">
                <svg class="h-5 w-5 text-red-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <div class="ml-3">
                    @foreach ($errors as $error)
                        <p class="text-sm text-red-700">{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Filters Section -->
    <div class="mb-8 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Filters</h2>
        
        <form action="{{ route('results.acsee.index') }}" method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            
            <!-- Exam Year (Required) -->
            <div>
                <label for="year" class="block text-sm font-medium text-gray-700 mb-1">
                    Exam Year <span class="text-red-500">*</span>
                </label>
                <select name="year" id="year" required class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Select Year --</option>
                    @foreach ($examYears as $examYear)
                        <option value="{{ $examYear['label'] }}" 
                            {{ request('year') === $examYear['label'] ? 'selected' : '' }}>
                            {{ $examYear['label'] }} (Published {{ $examYear['published_at'] ?? 'N/A' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Region Filter -->
            @if ($userRole === 'super_admin' && count($availableFilters['regions'] ?? []) > 0)
                <div>
                    <label for="region_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Region
                    </label>
                    <select name="region_id" id="region_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- All Regions --</option>
                        @foreach ($availableFilters['regions'] as $region)
                            <option value="{{ $region['id'] }}" 
                                {{ request('region_id') == $region['id'] ? 'selected' : '' }}>
                                {{ $region['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- District Filter -->
            @if (in_array($userRole, ['super_admin', 'regional_admin']) && count($availableFilters['districts'] ?? []) > 0)
                <div>
                    <label for="district_id" class="block text-sm font-medium text-gray-700 mb-1">
                        District
                    </label>
                    <select name="district_id" id="district_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- All Districts --</option>
                        @foreach ($availableFilters['districts'] as $district)
                            <option value="{{ $district['id'] }}" 
                                {{ request('district_id') == $district['id'] ? 'selected' : '' }}>
                                {{ $district['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- School Filter -->
            @if (in_array($userRole, ['super_admin', 'regional_admin', 'district_admin']) && count($availableFilters['schools'] ?? []) > 0)
                <div>
                    <label for="school_id" class="block text-sm font-medium text-gray-700 mb-1">
                        School
                    </label>
                    <select name="school_id" id="school_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- All Schools --</option>
                        @foreach ($availableFilters['schools'] as $school)
                            <option value="{{ $school['id'] }}" 
                                {{ request('school_id') == $school['id'] ? 'selected' : '' }}>
                                {{ $school['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- Search -->
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                    Search
                </label>
                <input type="text" name="search" id="search" placeholder="Index # or Name" 
                    value="{{ request('search') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                    Apply Filters
                </button>
                <a href="{{ route('results.acsee.index') }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition font-medium">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- No Results State -->
    @if (!$results || $results->isEmpty())
        <div class="rounded-lg bg-blue-50 p-8 text-center border border-blue-200">
            <svg class="mx-auto h-12 w-12 text-blue-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-blue-900">No Results Found</h3>
            <p class="mt-1 text-blue-700">Adjust your filters or select a different exam year</p>
        </div>
    @endif
</div>

<style>
    /* Sticky header for large tables */
    @media (min-width: 1024px) {
        thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }
    }
</style>
@endsection
