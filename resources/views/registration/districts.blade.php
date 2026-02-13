@extends('layout')

@section('content')
<div class="w-full px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Districts Management</h1>
        <p class="text-gray-600">Manage districts within each region</p>
    </div>

    <!-- Districts Component -->
    <div x-data="districtsManager()" @init="init()" class="space-y-6">
        <!-- Toolbar -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex gap-4 items-end">
                <!-- Region Filter -->
                <div class="flex flex-col min-w-[180px]">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Region</label>
                    <div class="relative" @click.outside="regionOpen = false">
                        <button 
                            @click="regionOpen = !regionOpen"
                            class="w-full px-3 py-2 border border-gray-300 text-left bg-white hover:bg-gray-50 transition-colors flex justify-between items-center rounded-t"
                        >
                            <span x-text="filterRegion ? regions.find(r => r.id == filterRegion)?.name : 'All Regions'" class="text-gray-700 whitespace-nowrap"></span>
                            <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                        </button>
                        <div x-show="regionOpen" class="absolute top-full left-0 right-0 bg-white border border-t-0 border-gray-300 z-10 rounded-b flex flex-col">
                            <input 
                                x-model="regionSearch"
                                type="text"
                                placeholder="Search regions..."
                                class="px-3 py-2 border-b border-gray-200 focus:outline-none focus:ring-0 text-sm flex-shrink-0"
                            >
                            <div class="max-h-64 overflow-y-auto">
                                <div @click="filterRegion = ''; regionOpen = false; filterDistricts()" class="px-3 py-2 hover:bg-blue-500 hover:text-white cursor-pointer text-sm transition-colors">
                                    All Regions
                                </div>
                                <template x-for="region in regions.filter(r => r.name.toLowerCase().includes(regionSearch.toLowerCase()))" :key="region.id">
                                    <div 
                                        @click="filterRegion = region.id; regionOpen = false; filterDistricts()"
                                        :class="filterRegion == region.id ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'"
                                        class="px-3 py-2 cursor-pointer text-sm transition-colors"
                                        x-text="region.name"
                                    ></div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Search Input -->
                <div class="flex flex-col flex-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                    <input 
                        x-model="search" 
                        @input="filterDistricts()"
                        type="text" 
                        placeholder="Search districts by name or code..." 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
                
                <!-- Tools Dropdown -->
                <div class="relative" @click.outside="showToolsMenu = false">
                    <button @click="showToolsMenu = !showToolsMenu" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg transition-colors text-sm flex items-center gap-2 font-medium">
                        <i class="fas fa-wrench"></i> Tools
                        <i :class="showToolsMenu ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="text-xs"></i>
                    </button>
                    <div x-show="showToolsMenu" class="absolute top-full right-0 mt-2 bg-white border border-gray-300 rounded-lg shadow-lg z-10 min-w-48" @click="showToolsMenu = false">
                        <button @click="downloadTemplate()" class="w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100 transition-colors flex items-center gap-2 border-b border-gray-200">
                            <i class="fas fa-download text-blue-600"></i> CSV Template
                        </button>
                        <button @click="document.getElementById('importInput').click()" class="w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100 transition-colors flex items-center gap-2 border-b border-gray-200">
                            <i class="fas fa-upload text-blue-600"></i> Import CSV
                        </button>
                        <button @click="exportCSV()" class="w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100 transition-colors flex items-center gap-2">
                            <i class="fas fa-file-csv text-blue-600"></i> Export CSV
                        </button>
                    </div>
                    <input id="importInput" type="file" accept=".csv" @change="importCSV($event)" class="hidden">
                </div>
                
                <!-- Add District Button -->
                <button 
                    @click="openAddModal()"
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors flex items-center gap-2 font-medium"
                >
                    <i class="fas fa-plus"></i> Add District
                </button>
            </div>
            
            <!-- Bulk Actions (shown when items are selected) -->
            <div x-show="selectedItems.size > 0" class="mt-4 flex gap-2 items-center bg-blue-50 p-3 rounded-lg border border-blue-200">
                <span class="text-sm font-medium text-gray-700">
                    <span x-text="selectedItems.size"></span> district(s) selected
                </span>
                <button 
                    @click="bulkDeleteDistricts()"
                    class="ml-auto bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors text-sm font-medium flex items-center gap-2"
                >
                    <i class="fas fa-trash"></i> Delete Selected
                </button>
            </div>
        </div>

        <!-- Districts Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div x-show="loading" class="p-6 text-center text-gray-500">
                <i class="fas fa-spinner animate-spin text-2xl"></i> Loading...
            </div>
            <table x-show="!loading" class="w-full">
                <thead class="bg-gray-100 border-b-2 border-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <input 
                                type="checkbox" 
                                @change="toggleSelectAll()"
                                :checked="selectedItems.size === filteredDistricts.length && filteredDistricts.length > 0"
                                class="w-4 h-4 cursor-pointer"
                            >
                        </th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Code</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">District Name</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Region</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 uppercase">Schools</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 uppercase">Candidates</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <template x-for="district in filteredDistricts" :key="district.id">
                        <tr class="hover:bg-blue-100 transition-colors" :class="selectedItems.has(district.id) ? 'bg-blue-50' : ''">
                            <td class="px-4 py-4 text-left">
                                <input 
                                    type="checkbox" 
                                    :checked="selectedItems.has(district.id)"
                                    @change="toggleSelect(district.id)"
                                    class="w-4 h-4 cursor-pointer"
                                >
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-800 font-mono" x-text="district.code"></td>
                            <td class="px-6 py-4 text-sm text-gray-800 font-medium" x-text="district.name"></td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="district.region_name || '-'"></td>
                            <td class="px-6 py-4 text-sm text-gray-600 text-center" x-text="district.schools_count || 0"></td>
                            <td class="px-6 py-4 text-sm text-gray-600 text-center" x-text="district.candidates_count || 0"></td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Active</span>
                            </td>
                            <td class="px-6 py-4 text-sm space-x-3">
                                <button 
                                    @click="viewDistrict(district)"
                                    class="inline-flex items-center justify-center w-8 h-8 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded transition-colors"
                                    title="View District"
                                >
                                    <i class="fas fa-eye text-sm"></i>
                                </button>
                                <button 
                                    @click="openEditModal(district)"
                                    class="inline-flex items-center justify-center w-8 h-8 text-blue-600 hover:text-blue-900 hover:bg-blue-50 rounded transition-colors"
                                    title="Edit District"
                                >
                                    <i class="fas fa-edit text-sm"></i>
                                </button>
                                <button 
                                    @click="deleteDistrict(district.id)"
                                    class="inline-flex items-center justify-center w-8 h-8 text-red-600 hover:text-red-900 hover:bg-red-50 rounded transition-colors"
                                    title="Delete District"
                                >
                                    <i class="fas fa-trash text-sm"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <template x-if="filteredDistricts.length === 0 && !loading">
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                No districts found. <button @click="openAddModal()" class="text-blue-600 hover:underline font-medium">Add one now</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200">
                <div class="text-sm text-gray-600">
                    Page <span x-text="currentPage"></span> of <span x-text="totalPages"></span>, showing <span x-text="filteredDistricts.length"></span> record(s) out of <span x-text="totalCount"></span> total
                </div>
                <div class="flex items-center gap-1">
                    <button 
                        @click="currentPage > 1 && (currentPage--, loadDistricts())"
                        :disabled="currentPage <= 1"
                        class="px-2 py-1 text-gray-600 hover:text-gray-900 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                        title="Previous"
                    >
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <template x-for="page in Array.from({length: totalPages}, (_, i) => i + 1)" :key="page">
                        <button 
                            @click="currentPage = page; loadDistricts()"
                            :class="currentPage === page ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'"
                            class="px-3 py-1 rounded text-sm font-medium transition-colors"
                            x-text="page"
                        ></button>
                    </template>
                    <button 
                        @click="currentPage < totalPages && (currentPage++, loadDistricts())"
                        :disabled="currentPage >= totalPages"
                        class="px-2 py-1 text-gray-600 hover:text-gray-900 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                        title="Next"
                    >
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal (Add/Edit/View) -->
        <div 
            x-show="modalOpen || viewModalOpen" 
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-[9999] p-4"
            style="display: none;"
            @click.self="modalOpen = false; viewModalOpen = false;"
            x-transition
        >
            <div class="bg-white rounded-lg shadow-2xl max-w-md w-full" x-transition>
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <span x-show="viewModalOpen && !editingId">District Details</span>
                        <span x-show="!viewModalOpen && !editingId">Add New District</span>
                        <span x-show="!viewModalOpen && editingId">Edit District</span>
                    </h2>
                    <button 
                        @click="modalOpen = false; viewModalOpen = false;" 
                        class="text-gray-500 hover:text-gray-700 text-2xl leading-none"
                    >
                        &times;
                    </button>
                </div>

                <!-- View Mode -->
                <div x-show="viewModalOpen" class="p-4 space-y-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">District Code</label>
                        <input 
                            type="text" 
                            readonly
                            :value="viewingDistrict.code"
                            class="w-full px-3 py-1 border border-gray-300 rounded text-sm bg-gray-100 text-gray-600 font-mono text-center focus:outline-none"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">District Name</label>
                        <input 
                            type="text" 
                            readonly
                            :value="viewingDistrict.name"
                            class="w-full px-3 py-1 border border-gray-300 rounded text-sm bg-gray-50 text-gray-700 focus:outline-none"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Region</label>
                        <input 
                            type="text" 
                            readonly
                            :value="viewingDistrict.region_name || '-'"
                            class="w-full px-3 py-1 border border-gray-300 rounded text-sm bg-gray-50 text-gray-700 focus:outline-none"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Schools</label>
                        <input 
                            type="text" 
                            readonly
                            :value="viewingDistrict.schools_count || 0"
                            class="w-full px-3 py-1 border border-gray-300 rounded text-sm bg-gray-50 text-gray-700 focus:outline-none"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Candidates</label>
                        <input 
                            type="text" 
                            readonly
                            :value="viewingDistrict.candidates_count || 0"
                            class="w-full px-3 py-1 border border-gray-300 rounded text-sm bg-gray-50 text-gray-700 focus:outline-none"
                        >
                    </div>
                    <div class="flex gap-2 pt-3">
                        <button @click="viewModalOpen = false" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 px-3 py-1.5 rounded text-sm transition-colors font-medium">
                            Close
                        </button>
                        <button @click="openEditModal(viewingDistrict); viewModalOpen = false;" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-sm transition-colors font-medium">
                            Edit
                        </button>
                    </div>
                </div>

                <!-- Edit/Add Mode -->
                <form x-show="!viewModalOpen" @submit.prevent="saveDistrict()" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Region * <span class="text-xs text-gray-500">(Select first)</span></label>
                        <select 
                            x-model="formData.region_id"
                            @change="generateDistrictCode()"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Select Region</option>
                            <template x-for="region in regions" :key="region.id">
                                <option :value="region.id" x-text="region.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">District Name *</label>
                        <input 
                            x-model="formData.name"
                            @input="generateDistrictCode()"
                            type="text" 
                            placeholder="e.g., Arusha City"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">District Code <span class="text-xs text-gray-500">(Auto-generated)</span></label>
                        <input 
                            x-model="formData.code"
                            type="text" 
                            readonly
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 focus:outline-none text-gray-600 font-mono text-center"
                        >
                        <p class="text-xs text-gray-500 mt-1">Format: Region code + 2-digit sequence (e.g., DO01, TA02)</p>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button 
                            type="button" 
                            @click="modalOpen = false" 
                            class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition-colors font-medium"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors font-medium"
                        >
                            <span x-show="!editingId">Add District</span>
                            <span x-show="editingId">Update District</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function districtsManager() {
     return {
         districts: [],
         filteredDistricts: [],
         regions: [],
         search: '',
         filterRegion: '',
         regionSearch: '',
         regionOpen: false,
         editingId: null,
         loading: false,
         modalOpen: false,
         viewModalOpen: false,
         viewingDistrict: {},
         formData: { code: '', name: '', region_id: '' },
         selectedItems: new Set(),
         showToolsMenu: false,
         currentPage: 1,
         pageSize: 10,
         totalCount: 0,
         totalPages: 0,

        async init() {
            await this.loadRegions();
            await this.loadDistricts();
        },

        async loadRegions() {
            try {
                const response = await fetch('/api/regions');
                const data = await response.json();
                this.regions = data.data || [];
            } catch (error) {
                console.error('Error loading regions:', error);
                this.showMessage('Error loading regions', 'error');
            }
        },

        async loadDistricts() {
            this.loading = true;
            try {
                let url = `/api/districts?page=${this.currentPage}&page_size=${this.pageSize}&search=${this.search}`;
                if (this.filterRegion) {
                    url += `&region_id=${this.filterRegion}`;
                }
                const response = await fetch(url);
                const data = await response.json();
                this.districts = data.data || [];
                this.filteredDistricts = this.districts;
                this.totalCount = data.pagination.total_count;
                this.totalPages = data.pagination.total_pages;
            } catch (error) {
                console.error('Error loading districts:', error);
                this.showMessage('Error loading districts', 'error');
            } finally {
                this.loading = false;
            }
        },

        filterDistricts() {
            this.currentPage = 1;
            this.loadDistricts();
        },

        generateDistrictCode() {
            if (!this.formData.region_id) {
                this.formData.code = '';
                return;
            }

            const selectedRegion = this.regions.find(r => r.id == this.formData.region_id);
            if (!selectedRegion || !selectedRegion.code) {
                this.formData.code = '';
                return;
            }

            // Find districts for this region
            const districtsInRegion = this.districts.filter(d => d.region_id == this.formData.region_id);

            // Calculate next district number
            const existingSuffixes = districtsInRegion
                .map(d => {
                    if (!d.code) return 0;
                    const match = d.code.match(/\d{2}$/);
                    return match ? parseInt(match[0]) : 0;
                })
                .filter(num => num > 0);

            let districtNumber = 1;
            if (existingSuffixes.length > 0) {
                districtNumber = Math.max(...existingSuffixes) + 1;
            }

            this.formData.code = selectedRegion.code + String(districtNumber).padStart(2, '0');
        },

        openAddModal() {
            this.editingId = null;
            this.viewModalOpen = false;
            this.formData = { code: '', name: '', region_id: '' };
            this.modalOpen = true;
            this.$nextTick(() => {
                const regionSelect = document.querySelector('select');
                if (regionSelect) regionSelect.focus();
            });
        },

        viewDistrict(district) {
            this.viewingDistrict = { ...district };
            this.editingId = null;
            this.viewModalOpen = true;
        },

        openEditModal(district) {
            this.editingId = district.id;
            this.formData = { 
                code: district.code, 
                name: district.name, 
                region_id: district.region_id 
            };
            this.modalOpen = true;
        },

        async saveDistrict() {
            try {
                // Validate required fields
                if (!this.formData.region_id) {
                    this.showMessage('Please select a region', 'error');
                    return;
                }
                if (!this.formData.name) {
                    this.showMessage('Please enter district name', 'error');
                    return;
                }
                if (!this.formData.code) {
                    this.showMessage('District code is required', 'error');
                    return;
                }

                const url = this.editingId ? `/api/districts/${this.editingId}` : '/api/districts';
                const method = this.editingId ? 'PUT' : 'POST';
                
                // Ensure region_id is a number
                const payload = {
                    ...this.formData,
                    region_id: parseInt(this.formData.region_id)
                };

                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();
                
                if (response.ok) {
                    this.showMessage(
                        this.editingId ? 'District updated successfully' : 'District added successfully',
                        'success'
                    );
                    this.modalOpen = false;
                    await this.loadDistricts();
                } else {
                    console.error('Error response:', data);
                    this.showMessage(data.message || data.errors || 'Error saving district', 'error');
                }
            } catch (error) {
                console.error('Error saving district:', error);
                this.showMessage('Error saving district: ' + error.message, 'error');
            }
        },

        async deleteDistrict(id) {
            if (!confirm('Are you sure you want to delete this district?')) return;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                const token = csrfToken ? csrfToken.content : '';
                
                const response = await fetch(`/api/districts/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                });

                let data = {};
                try {
                    data = await response.json();
                } catch (e) {
                    console.log('Response text:', await response.text());
                }
                
                if (response.ok || response.status === 200) {
                    this.showMessage('District deleted successfully', 'success');
                    await this.loadDistricts();
                } else if (response.status === 400) {
                    const errorMsg = data.details ? `Cannot delete district with associated records (Schools: ${data.details.schools})` : (data.error || data.message || 'Error deleting district');
                    this.showMessage(errorMsg, 'error');
                } else {
                    this.showMessage(data.error || data.message || `Error deleting district (Status: ${response.status})`, 'error');
                }
            } catch (error) {
                console.error('Error deleting district:', error);
                this.showMessage('Error deleting district: ' + error.message, 'error');
            }
        },

        downloadTemplate() {
            const headers = ['Name', 'Region ID'].join(',');
            const csv = headers;
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `districts_template_${Date.now()}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            this.showMessage('Template downloaded', 'success');
        },

        exportCSV() {
            const headers = ['Code', 'Name', 'Region', 'Schools', 'Candidates'].join(',');
            const rows = this.filteredDistricts.map(d => 
                [d.code, d.name, d.region_name || '', d.schools_count || 0, d.candidates_count || 0].map(v => `"${v}"`).join(',')
            );
            const csv = [headers, ...rows].join('\n');
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `districts_${Date.now()}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            this.showMessage('Exported successfully', 'success');
        },

        async importCSV(event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            try {
                const response = await fetch('/api/districts/import', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData,
                });

                const data = await response.json();
                 
                 if (response.ok) {
                     const message = data.count > 0 
                         ? `${data.count} district(s) imported successfully${data.failed > 0 ? `, ${data.failed} failed` : ''}` 
                         : 'No districts imported';
                     this.showMessage(message, data.count > 0 ? 'success' : 'warning');
                     
                     if (data.errors && data.errors.length > 0) {
                         console.warn('Import errors:', data.errors);
                     }
                     
                     await this.loadDistricts();
                 } else {
                     this.showMessage(data.message || 'Error importing', 'error');
                 }
            } catch (error) {
                console.error('Error:', error);
                this.showMessage('Error importing', 'error');
            }
        },

        toggleSelect(id) {
            if (this.selectedItems.has(id)) {
                this.selectedItems.delete(id);
            } else {
                this.selectedItems.add(id);
            }
        },

        toggleSelectAll() {
            if (this.selectedItems.size === this.filteredDistricts.length) {
                this.selectedItems.clear();
            } else {
                this.filteredDistricts.forEach(district => this.selectedItems.add(district.id));
            }
        },

        async bulkDeleteDistricts() {
            if (this.selectedItems.size === 0) return;
            
            const count = this.selectedItems.size;
            if (!confirm(`Are you sure you want to delete ${count} district(s)? This action cannot be undone.`)) return;

            try {
                const ids = Array.from(this.selectedItems);
                const response = await fetch('/api/districts/bulk-delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ ids }),
                });

                const data = await response.json();
                
                if (response.ok) {
                    this.showMessage(`${data.deleted} district(s) deleted successfully`, 'success');
                    this.selectedItems.clear();
                    await this.loadDistricts();
                } else {
                    this.showMessage(data.message || 'Error deleting districts', 'error');
                }
            } catch (error) {
                console.error('Error deleting districts:', error);
                this.showMessage('Error deleting districts', 'error');
            }
        },

        showMessage(message, type) {
            const alertDiv = document.createElement('div');
            const bgClass = type === 'success' ? 'bg-green-100 text-green-700 border-green-300' : 'bg-red-100 text-red-700 border-red-300';
            
            alertDiv.className = `fixed top-24 right-8 ${bgClass} p-4 rounded-lg border max-w-sm z-50 shadow-lg`;
            alertDiv.textContent = message;
            alertDiv.style.wordWrap = 'break-word';
            
            document.body.appendChild(alertDiv);
            setTimeout(() => alertDiv.remove(), 4000);
        },
    };
}
</script>
@endsection
