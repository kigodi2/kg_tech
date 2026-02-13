@extends('results.acsee.layout')

@section('page-title', 'Edit Grading Profile')
@section('page-subtitle', 'Update grading configuration')
@section('breadcrumb-active', 'Edit Profile')

@section('results-content')
<div class="max-w-4xl">
    
    @if($profile->is_locked)
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg mb-6">
            <p class="font-semibold"><i class="fas fa-lock"></i> This profile is locked and cannot be edited.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('results.acsee.grading.update', $profile) }}" class="space-y-6" id="gradingForm" @if($profile->is_locked) onsubmit="return false" @endif>
        @csrf
        @method('PATCH')

        <!-- Basic Information -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Basic Information</h3>
            
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Profile Name *</label>
                    <input type="text" name="name" placeholder="e.g., ACSEE 2026 Standard" value="{{ old('name', $profile->name) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror" @if($profile->is_locked) disabled @endif>
                    @error('name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Exam Year</label>
                    <p class="text-gray-700 font-medium">{{ $profile->examYear?->year_label ?? 'N/A' }}</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2 mt-4">Description</label>
                <textarea name="description" rows="3" placeholder="Optional description..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" @if($profile->is_locked) disabled @endif>{{ old('description', $profile->description) }}</textarea>
            </div>
        </div>

        <!-- Grade Boundaries -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Grade Boundaries</h3>
            <p class="text-sm text-gray-600 mb-4">Define marks range for each grade</p>

            <div id="gradeBoundaries" class="space-y-3">
                @if(is_array($profile->grade_boundaries))
                    @foreach($profile->grade_boundaries as $boundary)
                        <div class="grade-boundary grid grid-cols-3 gap-4 items-end">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Grade</label>
                                <input type="text" class="grade-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="A" value="{{ $boundary['grade'] ?? '' }}" maxlength="3" required @if($profile->is_locked) disabled @endif>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Min Marks</label>
                                <input type="number" class="min-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0" value="{{ $boundary['min'] ?? '' }}" min="0" max="100" required @if($profile->is_locked) disabled @endif>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Max Marks</label>
                                <input type="number" class="max-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="100" value="{{ $boundary['max'] ?? '' }}" min="0" max="100" required @if($profile->is_locked) disabled @endif>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            @if(!$profile->is_locked)
                <button type="button" id="addBoundary" class="mt-4 text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-2">
                    <i class="fas fa-plus"></i> Add Grade Boundary
                </button>
            @endif
        </div>

        <!-- GPA Mapping -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">GPA Mapping</h3>
            <p class="text-sm text-gray-600 mb-4">Map grades to GPA points (0.0 - 4.0)</p>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Grade A GPA</label>
                    <input type="number" name="gpa_a" placeholder="4.0" value="{{ old('gpa_a', $profile->gpa_mapping['a'] ?? '4.0') }}" min="0" max="4" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" @if($profile->is_locked) disabled @endif>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Grade B GPA</label>
                    <input type="number" name="gpa_b" placeholder="3.0" value="{{ old('gpa_b', $profile->gpa_mapping['b'] ?? '3.0') }}" min="0" max="4" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" @if($profile->is_locked) disabled @endif>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Grade C GPA</label>
                    <input type="number" name="gpa_c" placeholder="2.0" value="{{ old('gpa_c', $profile->gpa_mapping['c'] ?? '2.0') }}" min="0" max="4" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" @if($profile->is_locked) disabled @endif>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Grade D GPA</label>
                    <input type="number" name="gpa_d" placeholder="1.0" value="{{ old('gpa_d', $profile->gpa_mapping['d'] ?? '1.0') }}" min="0" max="4" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" @if($profile->is_locked) disabled @endif>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Grade F GPA</label>
                    <input type="number" name="gpa_f" placeholder="0.0" value="{{ old('gpa_f', $profile->gpa_mapping['f'] ?? '0.0') }}" min="0" max="4" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" @if($profile->is_locked) disabled @endif>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Special/Absent GPA</label>
                    <input type="number" name="gpa_special" placeholder="0.0" value="{{ old('gpa_special', $profile->gpa_mapping['special'] ?? '0.0') }}" min="0" max="4" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" @if($profile->is_locked) disabled @endif>
                </div>
            </div>
        </div>

        <!-- Competence Levels -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Competence Levels</h3>
            <p class="text-sm text-gray-600 mb-4">Define competence description for each grade</p>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Grade A Level</label>
                    <input type="text" name="level_a" placeholder="Excellent" value="{{ old('level_a', $profile->competence_levels['a'] ?? 'Excellent') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" @if($profile->is_locked) disabled @endif>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Grade B Level</label>
                    <input type="text" name="level_b" placeholder="Very Good" value="{{ old('level_b', $profile->competence_levels['b'] ?? 'Very Good') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" @if($profile->is_locked) disabled @endif>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Grade C Level</label>
                    <input type="text" name="level_c" placeholder="Good" value="{{ old('level_c', $profile->competence_levels['c'] ?? 'Good') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" @if($profile->is_locked) disabled @endif>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Grade D Level</label>
                    <input type="text" name="level_d" placeholder="Satisfactory" value="{{ old('level_d', $profile->competence_levels['d'] ?? 'Satisfactory') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" @if($profile->is_locked) disabled @endif>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Grade F Level</label>
                    <input type="text" name="level_f" placeholder="Fail" value="{{ old('level_f', $profile->competence_levels['f'] ?? 'Fail') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" @if($profile->is_locked) disabled @endif>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Special Level</label>
                    <input type="text" name="level_special" placeholder="Special" value="{{ old('level_special', $profile->competence_levels['special'] ?? 'Special') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" @if($profile->is_locked) disabled @endif>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-4">
            @if(!$profile->is_locked)
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors font-medium flex items-center gap-2">
                    <i class="fas fa-save"></i> Update Profile
                </button>
                <form method="POST" action="{{ route('results.acsee.grading.lock', $profile) }}" class="inline">
                    @csrf
                    <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2 rounded-lg transition-colors font-medium flex items-center gap-2">
                        <i class="fas fa-lock"></i> Lock Profile
                    </button>
                </form>
            @endif
            <a href="{{ route('results.acsee.grading.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg transition-colors font-medium">
                Back
            </a>
        </div>
    </form>
</div>

<script>
document.getElementById('addBoundary')?.addEventListener('click', function() {
    const container = document.getElementById('gradeBoundaries');
    const newBoundary = document.querySelector('.grade-boundary').cloneNode(true);
    newBoundary.querySelectorAll('input').forEach(input => input.value = '');
    container.appendChild(newBoundary);
});
</script>
@endsection
