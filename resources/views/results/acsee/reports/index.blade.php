@extends('results.acsee.layout')

@section('page-title', 'Reports')
@section('page-subtitle', $resultsModuleLabel === 'PSLE' ? 'PSLE district reports workspace' : 'District bulk school-results PDF export')
@section('breadcrumb-active', 'Reports')

@section('results-content')
<div class="space-y-6" x-data="districtResultsExportPage({
    districtOptionsUrl: @js(route($resultsRoutePrefix . '.reports.district-options')),
    initialRegionId: @js((string) ($defaults['region_id'] ?? '')),
    initialDistrictId: @js((string) ($defaults['district_id'] ?? '')),
    initialExamYearId: @js((string) ($defaults['exam_year_id'] ?? '')),
    regions: @js($regions->map(fn ($region) => [
        'id' => (string) $region->id,
        'name' => (string) strtoupper($region->name),
    ])->values()),
    districts: @js($districts->map(fn ($district) => [
        'id' => (string) $district->id,
        'region_id' => (string) $district->region_id,
        'name' => (string) $district->name,
    ])->values()),
})" x-init="init()">
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
        <p class="font-semibold">Reports area cleared</p>
                <p class="mt-1">
                    @if($resultsModuleLabel === 'PSLE')
                        This page now handles the PSLE district school-results PDF export. No stored marks or result data are edited from here.
                    @else
                        This page now handles only the bulk district school-results PDF export. No stored marks or result data are edited from here.
                    @endif
        </p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-6">
            <div>
                <h3 class="text-lg font-bold text-slate-900">
                    {{ $resultsModuleLabel === 'PSLE' ? 'PSLE District Reports' : 'Bulk District School-Results PDF Export' }}
                </h3>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">
                    @if($resultsModuleLabel === 'PSLE')
                        Download one ZIP containing one PSLE school-results PDF per school in the selected district.
                    @else
                        Download one ZIP containing one A3 portrait school-results PDF per examination centre in the selected district.
                    @endif
                </p>
            </div>
            <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                Format:
                <span class="font-semibold">
                    {{ $resultsModuleLabel === 'PSLE' ? 'ZIP of FPDF-generated PSLE school results' : 'ZIP of FPDF-generated school results' }}
                </span>
            </div>
        </div>

        @if ($errors->any())
            <div class="mt-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route($resultsRoutePrefix . '.reports.district-school-results-export') }}" class="mt-6 space-y-5">
            @csrf

            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-slate-700">Exam Year</span>
                    <select name="exam_year_id" x-model="examYearId" @change="refreshDistricts()" class="w-full rounded-none border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                        @foreach ($examYears as $examYear)
                            <option value="{{ $examYear->id }}" @selected((string) $defaults['exam_year_id'] === (string) $examYear->id)>
                                {{ $examYear->year_label }}@if($examYear->is_active) (Active)@endif
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-slate-700">Mode</span>
                    <select name="mode" class="w-full rounded-none border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                        <option value="draft" @selected(($defaults['mode'] ?? 'draft') === 'draft')>Draft</option>
                        <option value="published" @selected(($defaults['mode'] ?? 'draft') === 'published')>Published</option>
                    </select>
                </label>

                <div class="block">
                    <span class="mb-2 block text-sm font-medium text-slate-700">Region</span>
                    <div class="relative" @click.outside="regionOpen = false">
                        <input type="hidden" name="region_id" :value="regionId">
                        <button
                            type="button"
                            @click="regionOpen = !regionOpen; districtOpen = false"
                            class="flex w-full items-center justify-between rounded-none border border-slate-300 bg-white px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                        >
                            <span class="truncate" x-text="selectedRegionLabel()"></span>
                            <i class="fas fa-chevron-down text-xs text-slate-500"></i>
                        </button>
                        <div
                            x-show="regionOpen"
                            x-transition.origin.top.left
                            class="absolute left-0 right-0 top-full z-30 flex max-h-72 flex-col overflow-hidden rounded-none border border-t-0 border-slate-300 bg-white"
                        >
                            <input
                                x-model="regionSearch"
                                type="text"
                                placeholder="Search regions..."
                                class="border-b border-slate-200 px-3 py-2 text-sm rounded-none focus:outline-none focus:ring-0"
                            >
                            <div class="max-h-60 overflow-y-auto">
                                <div
                                    @click="selectRegion('')"
                                    class="cursor-pointer px-3 py-2 text-sm transition-colors hover:bg-blue-500 hover:text-white"
                                >
                                    All Accessible Regions
                                </div>
                                <template x-for="region in filteredRegions()" :key="region.id">
                                    <div
                                        @click="selectRegion(region.id)"
                                        :class="String(regionId) === String(region.id) ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'"
                                        class="cursor-pointer px-3 py-2 text-sm uppercase transition-colors"
                                        x-text="region.name"
                                    ></div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="block">
                    <span class="mb-2 block text-sm font-medium text-slate-700">District</span>
                    <div class="relative" @click.outside="districtOpen = false">
                        <input type="hidden" name="district_id" :value="districtId">
                        <button
                            type="button"
                            @click="districtOpen = !districtOpen; regionOpen = false"
                            class="flex w-full items-center justify-between rounded-none border border-slate-300 bg-white px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                        >
                            <span class="truncate" x-text="selectedDistrictLabel()"></span>
                            <i class="fas fa-chevron-down text-xs text-slate-500"></i>
                        </button>
                        <div
                            x-show="districtOpen"
                            x-transition.origin.top.left
                            class="absolute left-0 right-0 top-full z-30 flex max-h-72 flex-col overflow-hidden rounded-none border border-t-0 border-slate-300 bg-white"
                        >
                            <input
                                x-model="districtSearch"
                                type="text"
                                placeholder="Search districts..."
                                class="border-b border-slate-200 px-3 py-2 text-sm rounded-none focus:outline-none focus:ring-0"
                            >
                            <div class="max-h-60 overflow-y-auto">
                                <div
                                    @click="selectDistrict('')"
                                    class="cursor-pointer px-3 py-2 text-sm transition-colors hover:bg-blue-500 hover:text-white"
                                >
                                    Select District
                                </div>
                                <template x-for="district in filteredDistrictOptions()" :key="district.id">
                                    <div
                                        @click="selectDistrict(district.id)"
                                        :class="String(districtId) === String(district.id) ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'"
                                        class="cursor-pointer px-3 py-2 text-sm uppercase transition-colors"
                                        x-text="district.name"
                                    ></div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                <p class="font-semibold text-slate-900">What this export does</p>
                <ul class="mt-2 ml-5 list-disc space-y-1">
                    @if($resultsModuleLabel === 'PSLE')
                        <li>builds one PSLE school-results PDF per school in the selected district</li>
                        <li>packs all school PDFs into one ZIP download</li>
                        <li>reads result data only; it does not write or modify stored marks/results</li>
                    @else
                        <li>builds one school-results PDF per centre in the selected district</li>
                        <li>packs all centre PDFs into one ZIP download</li>
                        <li>reads results data only; it does not write or modify stored marks/results</li>
                    @endif
                </ul>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">
                    {{ $resultsModuleLabel === 'PSLE' ? 'Download District ZIP' : 'Download District ZIP' }}
                </button>
                <p class="text-sm text-slate-500">
                    A district selection is required.
                </p>
            </div>
        </form>
    </div>
</div>

<script>
function districtResultsExportPage(config) {
    return {
        districtOptionsUrl: config.districtOptionsUrl || '',
        examYearId: config.initialExamYearId || '',
        regionId: config.initialRegionId || '',
        districtId: config.initialDistrictId || '',
        districts: config.districts || [],
        regions: config.regions || [],
        regionOpen: false,
        districtOpen: false,
        regionSearch: '',
        districtSearch: '',
        get filteredDistricts() {
            if (!this.regionId) {
                return this.districts;
            }
            return this.districts.filter((district) => String(district.region_id) === String(this.regionId));
        },
        filteredRegions() {
            const term = String(this.regionSearch || '').toLowerCase();
            return this.regions.filter((region) => String(region.name || '').toLowerCase().includes(term));
        },
        filteredDistrictOptions() {
            const term = String(this.districtSearch || '').toLowerCase();
            return this.filteredDistricts.filter((district) => String(district.name || '').toLowerCase().includes(term));
        },
        selectedRegionLabel() {
            if (!this.regionId) {
                return 'All Accessible Regions';
            }
            return this.regions.find((region) => String(region.id) === String(this.regionId))?.name || 'All Accessible Regions';
        },
        selectedDistrictLabel() {
            if (!this.districtId) {
                return 'Select District';
            }
            return this.filteredDistricts.find((district) => String(district.id) === String(this.districtId))?.name || 'Select District';
        },
        async selectRegion(value) {
            this.regionId = String(value || '');
            this.regionOpen = false;
            this.regionSearch = '';
            this.districtId = '';
            this.districtSearch = '';
            await this.refreshDistricts();
        },
        selectDistrict(value) {
            this.districtId = String(value || '');
            this.districtOpen = false;
            this.districtSearch = '';
        },
        async refreshDistricts() {
            if (!this.examYearId || !this.districtOptionsUrl) {
                this.districts = [];
                this.districtId = '';
                return;
            }

            const params = new URLSearchParams({ exam_year_id: this.examYearId });
            if (this.regionId) {
                params.set('region_id', this.regionId);
            }

            try {
                const response = await fetch(`${this.districtOptionsUrl}?${params.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                const payload = await response.json();
                this.districts = Array.isArray(payload.data) ? payload.data : [];
            } catch (_) {
                this.districts = [];
            }

            const exists = this.districts.some((district) => String(district.id) === String(this.districtId));
            if (!exists) {
                this.districtId = '';
            }
        },
        async init() {
            await this.$nextTick();
            await this.refreshDistricts();
        },
    };
}
</script>
@endsection
