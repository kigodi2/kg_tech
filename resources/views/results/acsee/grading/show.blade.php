@extends('results.acsee.layout')

@section('page-title', 'Grading Profile')
@section('page-subtitle', $profile->name ?? 'View grading profile')
@section('breadcrumb-active', 'View Profile')

@section('results-content')
<div class="max-w-4xl space-y-6">
    
    <!-- Profile Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-start justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $profile->name ?? 'Unknown' }}</h2>
                <p class="text-gray-600 mt-2">{{ $profile->description ?? 'No description' }}</p>
            </div>
            <div class="text-right">
                <p class="text-gray-600"><strong>Exam Year:</strong> {{ $profile->examYear?->year_label ?? 'N/A' }}</p>
                <p class="text-gray-600 mt-2"><strong>Version:</strong> {{ $profile->version ?? 1 }}</p>
            </div>
        </div>

        <!-- Status Badges -->
        <div class="flex gap-2">
            @if($profile->is_locked)
                <span class="inline-block px-3 py-1 bg-red-100 text-red-700 text-xs font-bold rounded">
                    <i class="fas fa-lock mr-1"></i> LOCKED
                </span>
            @else
                @if($profile->is_active)
                    <span class="inline-block px-3 py-1 bg-green-100 text-green-700 text-xs font-bold rounded">
                        <i class="fas fa-check-circle mr-1"></i> ACTIVE
                    </span>
                @else
                    <span class="inline-block px-3 py-1 bg-gray-100 text-gray-700 text-xs font-bold rounded">
                        <i class="fas fa-times-circle mr-1"></i> INACTIVE
                    </span>
                @endif
            @endif
        </div>
    </div>

    <!-- Grade Boundaries -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Grade Boundaries</h3>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase">Grade</th>
                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase">Min Marks</th>
                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase">Max Marks</th>
                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase">Range</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @if($profile->grade_boundaries && is_array($profile->grade_boundaries))
                        @foreach($profile->grade_boundaries as $boundary)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3">
                                    <span class="inline-block px-3 py-1 bg-blue-100 text-blue-700 font-bold rounded">
                                        {{ $boundary['grade'] ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-center font-semibold">{{ $boundary['min'] ?? '-' }}</td>
                                <td class="px-6 py-3 text-center font-semibold">{{ $boundary['max'] ?? '-' }}</td>
                                <td class="px-6 py-3 text-center text-gray-600">
                                    {{ ($boundary['max'] ?? 0) - ($boundary['min'] ?? 0) + 1 }} marks
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">No grade boundaries defined</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- GPA Mapping -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">GPA Mapping</h3>
        
        <div class="grid grid-cols-3 gap-6">
            @php
                $gradePoints = collect($profile->gpa_mapping['grade_points'] ?? [])
                    ->mapWithKeys(fn ($item) => [strtoupper((string) ($item['grade'] ?? '')) => $item['gpa_point_value'] ?? '-']);
            @endphp
            @foreach($gradePoints as $label => $value)
                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <p class="text-gray-600 text-sm font-semibold mb-1">Grade {{ $label }}</p>
                    <p class="text-3xl font-bold text-purple-600">
                        {{ $value }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Point value</p>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Competence Levels -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Competence Levels</h3>
        
        @php $competenceRules = collect($profile->competence_levels['rules'] ?? []); @endphp
        <div class="grid grid-cols-2 gap-6">
            @foreach($competenceRules as $rule)
                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-gray-600 text-sm font-semibold mb-2">
                                {{ $rule['basis'] ?? 'GPA' }} {{ $rule['min_value'] ?? '-' }}@if(($rule['max_value'] ?? null) !== ($rule['min_value'] ?? null)) - {{ $rule['max_value'] ?? '-' }}@endif
                            </p>
                            <p class="text-lg font-semibold text-gray-900">
                                {{ $rule['level_label'] ?? 'Unknown' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex h-8 w-8 rounded-full border border-gray-300" style="background-color: {{ $rule['color_code'] ?? '#e5e7eb' }}"></span>
                            <p class="mt-2 text-xs font-mono text-gray-500">{{ $rule['color_code'] ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Lock Information -->
    @if($profile->is_locked)
        <div class="bg-red-50 border border-red-200 rounded-lg p-6">
            <h3 class="font-bold text-red-900 mb-2">
                <i class="fas fa-lock mr-2"></i> Profile Locked
            </h3>
            <p class="text-sm text-red-800 mb-3">
                This grading profile has been locked and cannot be edited. This ensures data integrity for published results.
            </p>
            <p class="text-sm text-red-800">
                <strong>Locked by:</strong> {{ $profile->lockedBy?->name ?? 'Unknown' }}<br>
                <strong>Locked at:</strong> {{ $profile->locked_at?->format('M d, Y H:i') ?? 'N/A' }}
            </p>
        </div>
    @endif

    <!-- Actions -->
    <div class="flex gap-4">
        @if(!$profile->is_locked)
            <a href="{{ route($resultsRoutePrefix . '.grading.edit', $profile->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors font-medium flex items-center gap-2">
                <i class="fas fa-edit"></i> Edit Profile
            </a>
            <form method="POST" action="{{ route($resultsRoutePrefix . '.grading.lock', $profile->id) }}" class="inline" onclick="return confirm('Lock this profile? It cannot be edited after locking.');">
                @csrf
                <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2 rounded-lg transition-colors font-medium flex items-center gap-2">
                    <i class="fas fa-lock"></i> Lock Profile
                </button>
            </form>
        @endif
        <a href="{{ route($resultsRoutePrefix . '.grading.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg transition-colors font-medium">
            Back to Profiles
        </a>
    </div>
</div>
@endsection
