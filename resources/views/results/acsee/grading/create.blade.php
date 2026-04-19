@extends('results.acsee.layout')

@section('page-title', 'Create Grading Profile')
@section('page-subtitle', 'Define a new grading configuration')
@section('breadcrumb-active', 'Create Profile')

@section('results-content')
<div class="max-w-4xl">
    
    <form method="POST" action="{{ route($resultsRoutePrefix . '.grading.store') }}" class="space-y-6" id="gradingForm">
        @csrf

        <!-- Basic Information -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Basic Information</h3>
            
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Profile Name *</label>
                    <input type="text" name="name" placeholder="e.g., {{ $resultsModuleLabel }} 2026 Standard" value="{{ old('name') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Exam Year *</label>
                    <select name="exam_year_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('exam_year_id') border-red-500 @enderror">
                        <option value="">Select Exam Year</option>
                        @foreach($examYears ?? [] as $year)
                            <option value="{{ $year->id }}" {{ old('exam_year_id') == $year->id ? 'selected' : '' }}>
                                {{ $year->year_label }}
                            </option>
                        @endforeach
                    </select>
                    @error('exam_year_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2 mt-4">Description</label>
                <textarea name="description" rows="3" placeholder="Optional description..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description') }}</textarea>
            </div>
        </div>

        <!-- Grade Boundaries -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Grade Boundaries</h3>
            <p class="text-sm text-gray-600 mb-4">Define marks range for each grade</p>

            <div id="gradeBoundaries" class="space-y-3">
                <!-- Templates will be added here -->
                <div class="grade-boundary grid grid-cols-3 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Grade</label>
                        <input type="text" class="grade-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="A" maxlength="3" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Min Marks</label>
                        <input type="number" class="min-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0" min="0" max="100" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Max Marks</label>
                        <input type="number" class="max-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="100" min="0" max="100" required>
                    </div>
                </div>
            </div>

            <button type="button" id="addBoundary" class="mt-4 text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-2">
                <i class="fas fa-plus"></i> Add Grade Boundary
            </button>
        </div>

        <!-- GPA Mapping -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">GPA Mapping</h3>
            <p class="text-sm text-gray-600 mb-4">Map grades to GPA points (0.0 - 4.0)</p>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Grade A GPA</label>
                    <input type="number" name="gpa_a" placeholder="4.0" value="{{ old('gpa_a', '4.0') }}" min="0" max="4" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Grade B GPA</label>
                    <input type="number" name="gpa_b" placeholder="3.0" value="{{ old('gpa_b', '3.0') }}" min="0" max="4" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Grade C GPA</label>
                    <input type="number" name="gpa_c" placeholder="2.0" value="{{ old('gpa_c', '2.0') }}" min="0" max="4" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Grade D GPA</label>
                    <input type="number" name="gpa_d" placeholder="1.0" value="{{ old('gpa_d', '1.0') }}" min="0" max="4" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Grade F GPA</label>
                    <input type="number" name="gpa_f" placeholder="0.0" value="{{ old('gpa_f', '0.0') }}" min="0" max="4" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Special/Absent GPA</label>
                    <input type="number" name="gpa_special" placeholder="0.0" value="{{ old('gpa_special', '0.0') }}" min="0" max="4" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                    <input type="text" name="level_a" placeholder="Excellent" value="{{ old('level_a', 'Excellent') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Grade B Level</label>
                    <input type="text" name="level_b" placeholder="Very Good" value="{{ old('level_b', 'Very Good') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Grade C Level</label>
                    <input type="text" name="level_c" placeholder="Good" value="{{ old('level_c', 'Good') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Grade D Level</label>
                    <input type="text" name="level_d" placeholder="Satisfactory" value="{{ old('level_d', 'Satisfactory') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Grade F Level</label>
                    <input type="text" name="level_f" placeholder="Fail" value="{{ old('level_f', 'Fail') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Special Level</label>
                    <input type="text" name="level_special" placeholder="Special" value="{{ old('level_special', 'Special') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors font-medium flex items-center gap-2">
                <i class="fas fa-save"></i> Create Profile
            </button>
            <a href="{{ route($resultsRoutePrefix . '.grading.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg transition-colors font-medium">
                Cancel
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
