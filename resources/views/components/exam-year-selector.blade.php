@props(['examYears' => [], 'selected' => null])

<div 
    class="exam-year-selector"
    x-data="{
        selected: {{ $selected ? $selected->id : 'null' }},
        examYears: {{ $examYears->toJson() }},
        isLocked: false,
        
        init() {
            this.updateLockStatus();
        },
        
        updateLockStatus() {
            const year = this.examYears.find(y => y.id == this.selected);
            this.isLocked = year ? year.is_locked : false;
        },
        
        async changeYear(yearId) {
            // Send request to update session
            const response = await fetch('/exam-years/set', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content,
                },
                body: JSON.stringify({ exam_year_id: yearId })
            });
            
            if (response.ok) {
                this.selected = yearId;
                this.updateLockStatus();
                window.location.reload(); // Reload to refresh all views
            }
        }
    }"
>
    <div class="flex items-center gap-3">
        <label for="exam_year_select" class="text-sm font-medium text-gray-700">
            Academic Year:
        </label>
        
        <select
            id="exam_year_select"
            x-model="selected"
            @change="changeYear($el.value)"
            class="block w-48 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
        >
            <option value="">-- Select Year --</option>
            @forelse($examYears as $year)
                <option value="{{ $year->id }}">
                    {{ $year->year_label }}
                    @if($year->is_active) (Active) @endif
                    @if($year->is_locked) 🔒 @endif
                </option>
            @empty
                <option disabled>No exam years available</option>
            @endforelse
        </select>
        
        <!-- Status Badge -->
        <div
            x-show="selected"
            class="inline-flex items-center gap-2"
        >
            <span
                x-show="!isLocked"
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800"
            >
                ✓ Editable
            </span>
            <span
                x-show="isLocked"
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800"
            >
                🔒 Read-Only
            </span>
        </div>
    </div>
</div>

<style>
    .exam-year-selector select {
        @apply px-4 py-2 border border-gray-300 rounded-md;
    }
    
    .exam-year-selector select:focus {
        @apply outline-none ring-2 ring-blue-500;
    }
</style>
