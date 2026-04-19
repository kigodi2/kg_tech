@extends('results.acsee.layout')

@section('page-title', 'Results Management')
@section('page-subtitle', 'View, filter, and manage ' . strtolower($resultsModuleLabel) . ' results')
@section('breadcrumb-active', 'Results')

@section('results-content')
@php
    $selectedSchoolId = (string) request('school_id', '');
    $selectedStatus = (string) request('status', '');
    $selectedSchoolLabel = 'All Schools';
    if ($selectedSchoolId !== '') {
        $selectedSchool = $schools->firstWhere('id', (int) $selectedSchoolId);
        if ($selectedSchool) {
            $selectedSchoolLabel = trim(($selectedSchool->code ? $selectedSchool->code . ' - ' : '') . $selectedSchool->name);
        }
    }

    $statusOptions = collect([
        ['value' => '', 'label' => 'All Statuses'],
        ['value' => 'draft', 'label' => 'Draft'],
        ['value' => 'pending', 'label' => 'Pending'],
        ['value' => 'final', 'label' => 'Final'],
        ['value' => 'published', 'label' => 'Published'],
    ]);
    $selectedStatusLabel = $statusOptions->firstWhere('value', $selectedStatus)['label'] ?? 'All Statuses';
@endphp

<div class="space-y-6" x-data="resultsFilters()">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
            <p class="text-sm font-semibold text-gray-500">Total Results</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($results->total()) }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ $resultsModuleLabel }} {{ $examYear->year_label ?? '' }}</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
            <p class="text-sm font-semibold text-gray-500">Published</p>
            <p class="mt-2 text-3xl font-bold text-green-600">{{ number_format((int) ($statusCounts['published'] ?? 0)) }}</p>
            <p class="mt-1 text-xs text-gray-500">Visible results</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
            <p class="text-sm font-semibold text-gray-500">Final</p>
            <p class="mt-2 text-3xl font-bold text-blue-600">{{ number_format((int) ($statusCounts['final'] ?? 0)) }}</p>
            <p class="mt-1 text-xs text-gray-500">Ready for release</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
            <p class="text-sm font-semibold text-gray-500">Draft / Other</p>
            <p class="mt-2 text-3xl font-bold text-amber-600">{{ number_format((int) ($statusCounts['draft'] ?? 0) + (int) ($statusCounts['pending'] ?? 0)) }}</p>
            <p class="mt-1 text-xs text-gray-500">Not yet published</p>
        </div>
    </div>

    <form method="GET" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <label for="school_id" class="mb-2 block text-sm font-semibold text-gray-700">School</label>
                <input type="hidden" name="school_id" x-model="schoolId">
                <div class="relative" @click.outside="schoolOpen = false">
                    <button
                        type="button"
                        @click="schoolOpen = !schoolOpen"
                        class="flex w-full items-center justify-between border border-gray-300 bg-white px-3 py-2.5 text-left text-sm text-gray-700 shadow-sm transition-colors hover:bg-gray-50 rounded-none"
                    >
                        <span class="truncate" x-text="selectedSchoolLabel"></span>
                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                    </button>
                    <div x-show="schoolOpen" x-transition class="absolute left-0 right-0 top-full z-30 border border-t-0 border-gray-300 bg-white rounded-none">
                        <input
                            x-model="schoolSearch"
                            type="text"
                            placeholder="Search schools..."
                            class="filter-search-input w-full border-b border-gray-200 px-3 py-2 text-sm rounded-none focus:outline-none focus:ring-0"
                        >
                        <div class="max-h-64 overflow-y-auto">
                            <div @click="selectSchool('', 'All Schools')" class="cursor-pointer px-3 py-2 text-sm transition-colors hover:bg-blue-500 hover:text-white">
                                All Schools
                            </div>
                            @foreach($schools as $school)
                                @php $optionLabel = trim(($school->code ? $school->code . ' - ' : '') . $school->name); @endphp
                                <div
                                    x-show="matchesSchool(@js($optionLabel))"
                                    @click="selectSchool(@js((string) $school->id), @js($optionLabel))"
                                    :class="schoolId === @js((string) $school->id) ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'"
                                    class="cursor-pointer px-3 py-2 text-sm transition-colors"
                                >
                                    {{ $optionLabel }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            @if($resultsModuleLabel !== 'PSLE' && $combinations->isNotEmpty())
                <div>
                    <label for="combination_id" class="mb-2 block text-sm font-semibold text-gray-700">Combination</label>
                    <select id="combination_id" name="combination_id" class="acsee-square-select w-full border border-gray-300 px-3 py-2.5 text-sm text-gray-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="">All Combinations</option>
                        @foreach($combinations as $combination)
                            <option value="{{ $combination->id }}" @selected((string) request('combination_id') === (string) $combination->id)>
                                {{ $combination->code }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label for="status" class="mb-2 block text-sm font-semibold text-gray-700">Status</label>
                <input type="hidden" name="status" x-model="statusValue">
                <div class="relative" @click.outside="statusOpen = false">
                    <button
                        type="button"
                        @click="statusOpen = !statusOpen"
                        class="flex w-full items-center justify-between border border-gray-300 bg-white px-3 py-2.5 text-left text-sm text-gray-700 shadow-sm transition-colors hover:bg-gray-50 rounded-none"
                    >
                        <span class="truncate" x-text="selectedStatusLabel"></span>
                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                    </button>
                    <div x-show="statusOpen" x-transition class="absolute left-0 right-0 top-full z-30 border border-t-0 border-gray-300 bg-white rounded-none">
                        <input
                            x-model="statusSearch"
                            type="text"
                            placeholder="Search statuses..."
                            class="filter-search-input w-full border-b border-gray-200 px-3 py-2 text-sm rounded-none focus:outline-none focus:ring-0"
                        >
                        <div class="max-h-64 overflow-y-auto">
                            @foreach($statusOptions as $option)
                                <div
                                    x-show="matchesStatus(@js($option['label']))"
                                    @click="selectStatus(@js($option['value']), @js($option['label']))"
                                    :class="statusValue === @js($option['value']) ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'"
                                    class="cursor-pointer px-3 py-2 text-sm transition-colors"
                                >
                                    {{ $option['label'] }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-end gap-3">
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    Apply Filters
                </button>
                <a href="{{ route($resultsRoutePrefix . '.results.index') }}" class="inline-flex items-center justify-center rounded-lg bg-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-200">
                    Reset
                </a>
            </div>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
        <div class="border-b border-gray-200 px-5 py-4">
            <h3 class="text-lg font-bold text-gray-900">{{ $resultsModuleLabel }} Results</h3>
            <p class="mt-1 text-sm text-gray-500">Showing {{ $results->firstItem() ?? 0 }}-{{ $results->lastItem() ?? 0 }} of {{ $results->total() }} results.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Candidate No.</th>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Candidate Name</th>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">School</th>
                        @if($resultsModuleLabel !== 'PSLE')
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Combination</th>
                        @endif
                        <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-wider text-gray-600">Grade</th>
                        <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-wider text-gray-600">GPA</th>
                        <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-wider text-gray-600">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($results as $result)
                        @php
                            $candidate = $result->candidate;
                            $candidateName = $candidate->full_name ?? $candidate->fullname ?? 'Unknown';
                            $candidateNumber = $candidate->candidate_id ?? $candidate->index_number ?? '-';
                            $combinationValue = $candidate->combination?->code ?? $candidate->combination ?? '-';
                            $status = strtolower((string) ($result->result_status ?? 'draft'));
                            $statusClasses = match($status) {
                                'published' => 'bg-green-100 text-green-700',
                                'final' => 'bg-blue-100 text-blue-700',
                                'pending' => 'bg-amber-100 text-amber-700',
                                default => 'bg-gray-100 text-gray-700',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $candidateNumber }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $candidateName }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $candidate?->school?->name ?? '-' }}</td>
                            @if($resultsModuleLabel !== 'PSLE')
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $combinationValue }}</td>
                            @endif
                            <td class="px-6 py-4 text-center text-sm font-bold text-gray-900">{{ $result->grade ?? '-' }}</td>
                            <td class="px-6 py-4 text-center text-sm text-gray-700">{{ $result->gpa ?? '-' }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $statusClasses }}">
                                    {{ strtoupper($status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <a href="{{ route($resultsRoutePrefix . '.results.candidate', $result->candidate_id) }}" class="font-semibold text-blue-600 hover:text-blue-800">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $resultsModuleLabel === 'PSLE' ? 7 : 8 }}" class="px-6 py-10 text-center text-sm text-gray-500">
                                No {{ strtolower($resultsModuleLabel) }} results found for the current filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($results->hasPages())
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
            {{ $results->withQueryString()->links() }}
        </div>
    @endif
</div>

<script>
function resultsFilters() {
    return {
        schoolOpen: false,
        schoolSearch: '',
        schoolId: @js($selectedSchoolId),
        selectedSchoolLabel: @js($selectedSchoolLabel),
        statusOpen: false,
        statusSearch: '',
        statusValue: @js($selectedStatus),
        selectedStatusLabel: @js($selectedStatusLabel),
        matchesSchool(label) {
            return label.toLowerCase().includes(this.schoolSearch.toLowerCase());
        },
        matchesStatus(label) {
            return label.toLowerCase().includes(this.statusSearch.toLowerCase());
        },
        selectSchool(value, label) {
            this.schoolId = value;
            this.selectedSchoolLabel = label;
            this.schoolOpen = false;
        },
        selectStatus(value, label) {
            this.statusValue = value;
            this.selectedStatusLabel = label;
            this.statusOpen = false;
        },
    };
}
</script>
@endsection
