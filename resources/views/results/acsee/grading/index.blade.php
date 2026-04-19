@extends('results.acsee.layout')

@section('page-title', 'Grading System')
@section('page-subtitle', 'Manage grade boundaries, GPA mapping, and competence levels')
@section('breadcrumb-active', 'Grading System')

@section('results-content')
<div class="space-y-6">
    
    <!-- Header with Action Button -->
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Grading Profiles</h3>
            <p class="text-sm text-gray-600 mt-1">Define and manage grading configurations for {{ $resultsModuleLabel }}</p>
        </div>
        <a href="{{ route($resultsRoutePrefix . '.grading.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
            <i class="fas fa-plus"></i> New Profile
        </a>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <input type="text" placeholder="Search grading profiles..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Years</option>
                @foreach($examYears ?? [] as $year)
                    <option value="{{ $year->id }}">{{ $year->year_label }}</option>
                @endforeach
            </select>
            <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="locked">Locked</option>
            </select>
        </div>
    </div>

    <!-- Grading Profiles Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Profile Name</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Exam Year</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Version</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Grades</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Created</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($profiles ?? [] as $profile)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $profile->name }}</p>
                                <p class="text-sm text-gray-600">Code: {{ $profile->code }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-700">{{ $profile->examYear?->year_label ?? '-' }}</td>
                        <td class="px-6 py-4 text-gray-700">{{ $profile->version ?? 1 }}</td>
                        <td class="px-6 py-4">
                            @if($profile->grade_boundaries)
                                <div class="flex gap-1 flex-wrap">
                                    @foreach($profile->grade_boundaries as $boundary)
                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded font-semibold">
                                            {{ $boundary['grade'] }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-gray-500 text-sm">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($profile->is_locked)
                                <span class="inline-block px-3 py-1 bg-red-100 text-red-700 text-xs font-bold rounded">
                                    <i class="fas fa-lock text-xs mr-1"></i> LOCKED
                                </span>
                            @elseif($profile->is_active)
                                <span class="inline-block px-3 py-1 bg-green-100 text-green-700 text-xs font-bold rounded">
                                    <i class="fas fa-check-circle text-xs mr-1"></i> ACTIVE
                                </span>
                            @else
                                <span class="inline-block px-3 py-1 bg-gray-100 text-gray-700 text-xs font-bold rounded">
                                    <i class="fas fa-times-circle text-xs mr-1"></i> INACTIVE
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $profile->created_at?->format('M d, Y') ?? '-' }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <a href="{{ route($resultsRoutePrefix . '.grading.show', $profile->id) }}" class="text-blue-600 hover:text-blue-800 transition-colors" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if(!$profile->is_locked)
                                    <a href="{{ route($resultsRoutePrefix . '.grading.edit', $profile->id) }}" class="text-blue-600 hover:text-blue-800 transition-colors" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route($resultsRoutePrefix . '.grading.lock', $profile->id) }}" class="inline" onclick="return confirm('Lock this profile? It cannot be edited after locking.');">
                                        @csrf
                                        <button type="submit" class="text-yellow-600 hover:text-yellow-800 transition-colors" title="Lock">
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    </form>
                                @endif
                                @if(!$profile->is_locked && !$profile->is_active)
                                    <form method="POST" action="{{ route($resultsRoutePrefix . '.grading.destroy', $profile->id) }}" class="inline" onclick="return confirm('Delete this profile? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 transition-colors" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-3xl mb-2"></i>
                            <p class="mt-2">No grading profiles found</p>
                            <a href="{{ route($resultsRoutePrefix . '.grading.create') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium mt-2 inline-block">
                                Create your first profile
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Info Card -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <div class="flex gap-4">
            <i class="fas fa-info-circle text-blue-600 text-xl flex-shrink-0 mt-1"></i>
            <div>
                <h4 class="font-semibold text-blue-900 mb-2">About Grading Profiles</h4>
                <p class="text-sm text-blue-800 mb-2">
                    Grading profiles define how marks are converted to grades. Each profile includes:
                </p>
                @if($resultsModuleLabel === 'PSLE')
                    <ul class="text-sm text-blue-800 space-y-1 ml-4">
                        <li>✓ Grade boundaries (A-E)</li>
                        <li>✓ Point mapping (1.0-5.0 scale)</li>
                        <li>✓ Competence levels (Excellent to Unsatisfactory)</li>
                    </ul>
                @else
                    <ul class="text-sm text-blue-800 space-y-1 ml-4">
                        <li>✓ Grade boundaries (A-F, S)</li>
                        <li>✓ GPA mapping (1.0-7.0 scale)</li>
                        <li>✓ Competence levels (Excellent, Very Good, etc.)</li>
                    </ul>
                @endif
                <p class="text-sm text-blue-800 mt-2">
                    <strong>Important:</strong> Once locked, a profile cannot be edited. Lock profiles after results are published.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
