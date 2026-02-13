@extends('layout')

@section('content')
<div class="w-full px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">ACSEE Dashboard</h1>
        <p class="text-gray-600">View registered ACSEE candidates</p>
    </div>

    <!-- Main Component -->
    <div x-data="dashboardAcseeManager()" @init="init()" class="space-y-6">
        
        <!-- Filters Section -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="grid grid-cols-4 gap-4">
                <!-- Region Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Region</label>
                    <select 
                        x-model="selectedRegion"
                        @change="onRegionChange()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">All Regions</option>
                        <template x-for="region in regions" :key="region.id">
                            <option :value="region.id" x-text="region.name"></option>
                        </template>
                    </select>
                </div>
                
                <!-- District Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">District</label>
                    <select 
                        x-model="selectedDistrict"
                        @change="onDistrictChange()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">All Districts</option>
                        <template x-for="district in filteredDistricts" :key="district.id">
                            <option :value="district.id" x-text="district.name"></option>
                        </template>
                    </select>
                </div>
                
                <!-- School Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">School</label>
                    <select 
                        x-model="selectedSchool"
                        @change="onSchoolChange()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">All Schools</option>
                        <template x-for="school in filteredSchools" :key="school.id">
                            <option :value="school.id" x-text="school.name"></option>
                        </template>
                    </select>
                </div>
                
                <!-- Reset Button -->
                <div class="flex items-end">
                    <button 
                        @click="resetFilters()"
                        class="w-full px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-medium transition-colors"
                    >
                        Reset Filters
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Search and Actions -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex gap-4 items-center">
                <input 
                    x-model="searchText"
                    @input="onSearch()"
                    type="text" 
                    placeholder="Search by Index Number or Full Name..."
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <button 
                    @click="exportToExcel()"
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium flex items-center gap-2 transition-colors"
                >
                    <i class="fas fa-download"></i> Export Excel
                </button>
            </div>
        </div>
        
        <!-- Candidates Table -->
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <div x-show="loading" class="p-6 text-center text-gray-500">
                <i class="fas fa-spinner animate-spin text-2xl"></i> Loading...
            </div>
            
            <table x-show="!loading" class="w-full">
                <thead class="bg-gray-100 border-b-2 border-gray-300">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Index Number</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Full Name</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 uppercase">Sex</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Combination</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Allocated Subjects</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">School</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">District</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Region</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <template x-for="candidate in filteredCandidates" :key="candidate.id">
                        <tr class="hover:bg-blue-100 transition-colors">
                            <td class="px-6 py-4 text-sm font-mono text-gray-800" x-text="candidate.candidate_id"></td>
                            <td class="px-6 py-4 text-sm text-gray-800 font-medium" x-text="candidate.full_name"></td>
                            <td class="px-6 py-4 text-sm text-gray-600 text-center" x-text="candidate.gender === 'M' ? '♂ Male' : '♀ Female'"></td>
                            <td class="px-6 py-4 text-sm text-gray-600 font-mono" x-text="candidate.combination || '-'"></td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <span x-text="candidate.allocated_subjects.length > 0 ? candidate.allocated_subjects.map(s => s.code).join(', ') : '-'"></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="candidate.school_name"></td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="candidate.district_name"></td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="candidate.region_name"></td>
                        </tr>
                    </template>
                    <tr x-show="filteredCandidates.length === 0 && !loading">
                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                            No candidates found
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-white rounded-lg shadow">
            <div class="text-sm text-gray-600">
                Page <span x-text="currentPage"></span> of <span x-text="totalPages"></span>, showing <span x-text="filteredCandidates.length"></span> of <span x-text="totalCount"></span> records
            </div>
            <div class="flex gap-2 items-center">
                <button 
                    @click="currentPage > 1 && (currentPage--, loadCandidates())"
                    :disabled="currentPage <= 1"
                    class="px-3 py-1 border border-gray-300 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 transition-colors"
                >
                    <i class="fas fa-chevron-left"></i>
                </button>
                <template x-for="page in Array.from({length: Math.min(totalPages, 5)}, (_, i) => i + 1)" :key="page">
                    <button 
                        @click="currentPage = page; loadCandidates()"
                        :class="currentPage === page ? 'bg-blue-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-100'"
                        class="px-3 py-1 rounded transition-colors font-medium"
                        x-text="page"
                    ></button>
                </template>
                <button 
                    @click="currentPage < totalPages && (currentPage++, loadCandidates())"
                    :disabled="currentPage >= totalPages"
                    class="px-3 py-1 border border-gray-300 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 transition-colors"
                >
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
        
    </div>
</div>

<script>
function dashboardAcseeManager() {
    return {
        // State - Candidates
        candidates: [],
        filteredCandidates: [],
        
        // State - Filter Options
        regions: [],
        districts: [],
        schools: [],
        
        // State - Filters
        searchText: '',
        selectedRegion: '',
        selectedDistrict: '',
        selectedSchool: '',
        
        // State - Pagination
        currentPage: 1,
        pageSize: 15,
        totalPages: 1,
        totalCount: 0,
        
        // State - UI
        loading: false,
        
        // Computed - Filtered Districts
        get filteredDistricts() {
            if (!this.selectedRegion) return this.districts;
            return this.districts.filter(d => d.region_id == this.selectedRegion);
        },
        
        // Computed - Filtered Schools
        get filteredSchools() {
            if (!this.selectedDistrict) return this.schools;
            return this.schools.filter(s => s.district_id == this.selectedDistrict);
        },
        
        // Initialize
        async init() {
            await this.loadFilterData();
            await this.loadCandidates();
        },
        
        // Load filter options (regions, districts, schools)
        async loadFilterData() {
            try {
                const response = await fetch('/api/dashboard/candidates/filter-data');
                const data = await response.json();
                this.regions = data.regions;
                this.districts = data.districts;
                this.schools = data.schools;
            } catch (error) {
                console.error('Error loading filter data:', error);
                this.showMessage('Error loading filter data', 'error');
            }
        },
        
        // Load candidates from API
        async loadCandidates() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.currentPage,
                    page_size: this.pageSize,
                    search: this.searchText,
                    region_id: this.selectedRegion,
                    district_id: this.selectedDistrict,
                    school_id: this.selectedSchool,
                });
                
                const response = await fetch(`/api/dashboard/candidates/acsee?${params}`);
                const data = await response.json();
                
                this.filteredCandidates = data.candidates;
                this.totalPages = data.pagination.total_pages;
                this.totalCount = data.pagination.total_count;
                this.currentPage = data.pagination.page;
            } catch (error) {
                console.error('Error loading candidates:', error);
                this.showMessage('Error loading candidates', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        // Region filter changed
        onRegionChange() {
            this.selectedDistrict = '';
            this.selectedSchool = '';
            this.currentPage = 1;
            this.loadCandidates();
        },
        
        // District filter changed
        onDistrictChange() {
            this.selectedSchool = '';
            this.currentPage = 1;
            this.loadCandidates();
        },
        
        // School filter changed
        onSchoolChange() {
            this.currentPage = 1;
            this.loadCandidates();
        },
        
        // Search input changed
        onSearch() {
            this.currentPage = 1;
            this.loadCandidates();
        },
        
        // Reset all filters
        resetFilters() {
            this.searchText = '';
            this.selectedRegion = '';
            this.selectedDistrict = '';
            this.selectedSchool = '';
            this.currentPage = 1;
            this.loadCandidates();
        },
        
        // Export to Excel
        exportToExcel() {
            const headers = ['Index Number', 'Full Name', 'Sex', 'Combination', 'Subjects', 'School', 'District', 'Region'];
            const rows = this.filteredCandidates.map(c => [
                c.candidate_id || '',
                c.full_name || '',
                c.gender || '',
                c.combination || '',
                c.allocated_subjects.map(s => s.code).join(', ') || '',
                c.school_name || '',
                c.district_name || '',
                c.region_name || '',
            ]);
            
            const csv = [headers, ...rows].map(row => 
                row.map(v => `"${v}"`).join(',')
            ).join('\n');
            
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `acsee_candidates_${new Date().getTime()}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            this.showMessage('Exported to Excel successfully', 'success');
        },
        
        // Show notification message
        showMessage(message, type) {
            const div = document.createElement('div');
            const bgClass = type === 'success' ? 'bg-green-100 text-green-700 border-green-300' : 'bg-red-100 text-red-700 border-red-300';
            
            div.className = `fixed top-24 right-8 ${bgClass} p-4 rounded-lg border max-w-sm z-50 shadow-lg`;
            div.textContent = message;
            div.style.wordWrap = 'break-word';
            
            document.body.appendChild(div);
            setTimeout(() => div.remove(), 3000);
        }
    };
}
</script>
@endsection
