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
    <div class="rounded-xl border border-[#bba45e]/30 bg-[#bba45e]/10 p-5 text-sm text-[#f0e6c8] backdrop-blur-md">
        <p class="font-semibold flex items-center gap-2">
            <i class="fa-solid fa-circle-info text-[#bba45e]"></i> Reports Workspace Active
        </p>
        <p class="mt-1">
            @if($resultsModuleLabel === 'PSLE')
                This page now handles the PSLE district school-results PDF export. No stored marks or result data are edited from here.
            @else
                This page now handles only the bulk district school-results PDF export. No stored marks or result data are edited from here.
            @endif
        </p>
    </div>

    <div class="rounded-xl border border-[rgba(255,255,255,0.08)] bg-[#101518]/60 p-6 backdrop-blur-md shadow-2xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between sm:gap-6">
            <div>
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-file-pdf text-[#bba45e]"></i>
                    {{ $resultsModuleLabel === 'PSLE' ? 'PSLE District Reports Compiler' : 'Bulk District School-Results PDF Export' }}
                </h3>
                <p class="mt-2 max-w-3xl text-sm text-slate-400">
                    @if($resultsModuleLabel === 'PSLE')
                        Compile and download a single ZIP containing official print-ready PSLE school-results Statement Sheets for all centres in the selected district.
                    @else
                        Compile and download a single ZIP containing official A3 portrait school-results Statement Sheets for all centres in the selected district.
                    @endif
                </p>
            </div>
            <div class="rounded-lg border border-[#bba45e]/20 bg-[#bba45e]/10 px-4 py-3 text-sm text-white self-start">
                Format:
                <span class="font-semibold text-[#bba45e] ml-1">
                    {{ $resultsModuleLabel === 'PSLE' ? 'ZIP of FPDF-generated Statement Sheets' : 'ZIP of FPDF-generated school results' }}
                </span>
            </div>
        </div>

        @if ($errors->any())
            <div class="mt-5 rounded-lg border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-300">
                <i class="fa-solid fa-triangle-exclamation mr-2"></i> {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route($resultsRoutePrefix . '.reports.district-school-results-export') }}" class="mt-6 space-y-6">
            @csrf

            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-slate-300">Exam Year</span>
                    <select name="exam_year_id" x-model="examYearId" @change="refreshDistricts()" class="w-full rounded-lg border border-[rgba(255,255,255,0.1)] bg-[#161b22] px-3 py-2.5 text-sm text-white focus:border-[#bba45e] focus:outline-none transition">
                        @foreach ($examYears as $examYear)
                            <option value="{{ $examYear->id }}" @selected((string) $defaults['exam_year_id'] === (string) $examYear->id) class="bg-[#101518] text-white">
                                {{ $examYear->year_label }}@if($examYear->is_active) (Active)@endif
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-slate-300">Mode</span>
                    <select name="mode" class="w-full rounded-lg border border-[rgba(255,255,255,0.1)] bg-[#161b22] px-3 py-2.5 text-sm text-white focus:border-[#bba45e] focus:outline-none transition">
                        <option value="draft" @selected(($defaults['mode'] ?? 'draft') === 'draft') class="bg-[#101518] text-white">Draft</option>
                        <option value="published" @selected(($defaults['mode'] ?? 'draft') === 'published') class="bg-[#101518] text-white">Published</option>
                    </select>
                </label>

                <div class="block">
                    <span class="mb-2 block text-sm font-medium text-slate-300">Region</span>
                    <div class="relative" @click.outside="regionOpen = false">
                        <input type="hidden" name="region_id" :value="regionId">
                        <button
                            type="button"
                            @click="regionOpen = !regionOpen; districtOpen = false"
                            class="flex w-full items-center justify-between rounded-lg border border-[rgba(255,255,255,0.1)] bg-[#161b22] px-3 py-2.5 text-left text-sm text-white transition hover:bg-[#1f2937]"
                        >
                            <span class="truncate" x-text="selectedRegionLabel()"></span>
                            <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                        </button>
                        <div
                            x-show="regionOpen"
                            x-transition.origin.top.left
                            class="absolute left-0 right-0 top-full z-30 flex max-h-72 flex-col overflow-hidden rounded-lg border border-[rgba(255,255,255,0.1)] bg-[#101518] shadow-2xl mt-1"
                        >
                            <input
                                x-model="regionSearch"
                                type="text"
                                placeholder="Search regions..."
                                class="border-b border-[rgba(255,255,255,0.08)] bg-[#161b22] px-3 py-2 text-sm text-white focus:outline-none focus:ring-0"
                            >
                            <div class="max-h-60 overflow-y-auto">
                                <div
                                    @click="selectRegion('')"
                                    class="cursor-pointer px-3 py-2 text-sm transition-colors text-slate-300 hover:bg-[#bba45e]/20 hover:text-white"
                                >
                                    All Accessible Regions
                                </div>
                                <template x-for="region in filteredRegions()" :key="region.id">
                                    <div
                                        @click="selectRegion(region.id)"
                                        :class="String(regionId) === String(region.id) ? 'bg-[#bba45e]/30 text-white font-bold' : 'hover:bg-[#bba45e]/20 hover:text-white text-slate-300'"
                                        class="cursor-pointer px-3 py-2 text-sm uppercase transition-colors"
                                        x-text="region.name"
                                    ></div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="block">
                    <span class="mb-2 block text-sm font-medium text-slate-300">District</span>
                    <div class="relative" @click.outside="districtOpen = false">
                        <input type="hidden" name="district_id" :value="districtId">
                        <button
                            type="button"
                            @click="districtOpen = !districtOpen; regionOpen = false"
                            class="flex w-full items-center justify-between rounded-lg border border-[rgba(255,255,255,0.1)] bg-[#161b22] px-3 py-2.5 text-left text-sm text-white transition hover:bg-[#1f2937]"
                        >
                            <span class="truncate" x-text="selectedDistrictLabel()"></span>
                            <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                        </button>
                        <div
                            x-show="districtOpen"
                            x-transition.origin.top.left
                            class="absolute left-0 right-0 top-full z-30 flex max-h-72 flex-col overflow-hidden rounded-lg border border-[rgba(255,255,255,0.1)] bg-[#101518] shadow-2xl mt-1"
                        >
                            <input
                                x-model="districtSearch"
                                type="text"
                                placeholder="Search districts..."
                                class="border-b border-[rgba(255,255,255,0.08)] bg-[#161b22] px-3 py-2 text-sm text-white focus:outline-none focus:ring-0"
                            >
                            <div class="max-h-60 overflow-y-auto">
                                <div
                                    @click="selectDistrict('')"
                                    class="cursor-pointer px-3 py-2 text-sm transition-colors text-slate-300 hover:bg-[#bba45e]/20 hover:text-white"
                                >
                                    Select District
                                </div>
                                <template x-for="district in filteredDistrictOptions()" :key="district.id">
                                    <div
                                        @click="selectDistrict(district.id)"
                                        :class="String(districtId) === String(district.id) ? 'bg-[#bba45e]/30 text-white font-bold' : 'hover:bg-[#bba45e]/20 hover:text-white text-slate-300'"
                                        class="cursor-pointer px-3 py-2 text-sm uppercase transition-colors"
                                        x-text="district.name"
                                    ></div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-[rgba(255,255,255,0.06)] bg-[#161b22]/40 p-4 text-sm text-slate-300">
                <p class="font-semibold text-white flex items-center gap-2"><i class="fa-solid fa-list-check text-[#bba45e]"></i> What this export does</p>
                <ul class="mt-2 ml-5 list-disc space-y-1.5 text-slate-400">
                    @if($resultsModuleLabel === 'PSLE')
                        <li>Generates one professional PSLE school-results Statement Sheet PDF per primary school in the selected district.</li>
                        <li>Packs and compiles all generated school Statement Sheets into a single downloadable ZIP archive.</li>
                        <li>Performs read-only operations on data; strictly maintains database integrity without modifying registration or result tables.</li>
                    @else
                        <li>Generates one school-results PDF per examination centre in the selected district.</li>
                        <li>Packs all centre Statement Sheet PDFs into one downloadable ZIP archive.</li>
                        <li>Performs read-only operations on data; strictly maintains database integrity without modifying registration or result tables.</li>
                    @endif
                </ul>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <button type="submit" class="rounded-lg bg-[#bba45e] hover:bg-[#a38f4e] px-6 py-3 text-sm font-semibold text-[#0f1117] transition-all transform hover:scale-[1.02] active:scale-[0.98] shadow-[0_4px_20px_rgba(187,164,94,0.35)] flex items-center gap-2">
                    <i class="fa-solid fa-file-zipper text-base"></i> Compile & Download District ZIP
                </button>
                <p class="text-sm text-slate-500 flex items-center gap-1.5">
                    <i class="fa-solid fa-lock text-[#bba45e]/60"></i> Requires region & district filters.
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
