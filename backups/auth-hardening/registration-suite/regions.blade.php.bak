@extends('layout')

@section('content')
<div class="w-full px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Regions Management</h1>
        <p class="text-gray-600">Manage examination regions across all zones</p>
    </div>

    <!-- Regions Component -->
    <div x-data="regionsManager()" @init="init()" class="space-y-6">
        <!-- Toolbar -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex gap-4 items-center">
                <!-- Search Input -->
                <input 
                    x-model="search" 
                    @input="filterRegions()"
                    type="text" 
                    placeholder="Search regions by name or code..." 
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                
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
                
                <!-- Add Region Button -->
                <button 
                    @click="openAddModal()"
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors flex items-center gap-2 font-medium"
                >
                    <i class="fas fa-plus"></i> Add Region
                </button>
            </div>
            
            <!-- Bulk Actions (shown when items are selected) -->
            <div x-show="selectedItems.size > 0" class="mt-4 flex gap-2 items-center bg-blue-50 p-3 rounded-lg border border-blue-200">
                <span class="text-sm font-medium text-gray-700">
                    <span x-text="selectedItems.size"></span> region(s) selected
                </span>
                <button 
                    @click="bulkDeleteRegions()"
                    class="ml-auto bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors text-sm font-medium flex items-center gap-2"
                >
                    <i class="fas fa-trash"></i> Delete Selected
                </button>
            </div>
        </div>

        <!-- Regions Table -->
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
                                :checked="selectedItems.size === filteredRegions.length && filteredRegions.length > 0"
                                class="w-4 h-4 cursor-pointer"
                            >
                        </th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Code</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Region Name</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 uppercase">Districts</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 uppercase">Schools</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 uppercase">Candidates</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <template x-for="region in filteredRegions" :key="region.id">
                        <tr class="hover:bg-blue-100 transition-colors" :class="selectedItems.has(region.id) ? 'bg-blue-50' : ''">
                            <td class="px-4 py-4 text-left">
                                <input 
                                    type="checkbox" 
                                    :checked="selectedItems.has(region.id)"
                                    @change="toggleSelect(region.id)"
                                    class="w-4 h-4 cursor-pointer"
                                >
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-800 font-mono" x-text="region.code"></td>
                            <td class="px-6 py-4 text-sm text-gray-800 font-medium" x-text="region.name"></td>
                            <td class="px-6 py-4 text-sm text-gray-600 text-center" x-text="region.districts_count || 0"></td>
                            <td class="px-6 py-4 text-sm text-gray-600 text-center" x-text="region.schools_count || 0"></td>
                            <td class="px-6 py-4 text-sm text-gray-600 text-center" x-text="region.candidates_count || 0"></td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Active</span>
                            </td>
                            <td class="px-6 py-4 text-sm space-x-3">
                                <button 
                                    @click="viewRegion(region)"
                                    class="inline-flex items-center justify-center w-8 h-8 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded transition-colors"
                                    title="View Region"
                                >
                                    <i class="fas fa-eye text-sm"></i>
                                </button>
                                <button 
                                    @click="openEditModal(region)"
                                    class="inline-flex items-center justify-center w-8 h-8 text-blue-600 hover:text-blue-900 hover:bg-blue-50 rounded transition-colors"
                                    title="Edit Region"
                                >
                                    <i class="fas fa-edit text-sm"></i>
                                </button>
                                <button 
                                    @click="deleteRegion(region.id)"
                                    class="inline-flex items-center justify-center w-8 h-8 text-red-600 hover:text-red-900 hover:bg-red-50 rounded transition-colors"
                                    title="Delete Region"
                                >
                                    <i class="fas fa-trash text-sm"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <template x-if="filteredRegions.length === 0 && !loading">
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                No regions found. <button @click="openAddModal()" class="text-blue-600 hover:underline font-medium">Add one now</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
                </table>
                
                <!-- Pagination -->
                <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200">
                    <div class="text-sm text-gray-600">
                        Page <span x-text="currentPage"></span> of <span x-text="totalPages"></span>, showing <span x-text="filteredRegions.length"></span> record(s) out of <span x-text="totalCount"></span> total
                    </div>
                    <div class="flex items-center gap-1">
                        <button 
                            @click="currentPage > 1 && (currentPage--, loadRegions())"
                            :disabled="currentPage <= 1"
                            class="px-2 py-1 text-gray-600 hover:text-gray-900 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                            title="Previous"
                        >
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <template x-for="page in Array.from({length: totalPages}, (_, i) => i + 1)" :key="page">
                            <button 
                                @click="currentPage = page; loadRegions()"
                                :class="currentPage === page ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'"
                                class="px-3 py-1 rounded text-sm font-medium transition-colors"
                                x-text="page"
                            ></button>
                        </template>
                        <button 
                            @click="currentPage < totalPages && (currentPage++, loadRegions())"
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
                        <span x-show="viewModalOpen && !editingId">Region Details</span>
                        <span x-show="!viewModalOpen && !editingId">Add New Region</span>
                        <span x-show="!viewModalOpen && editingId">Edit Region</span>
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
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Region Code</label>
                        <input 
                            type="text" 
                            readonly
                            :value="viewingRegion.code"
                            class="w-full px-3 py-1 border border-gray-300 rounded text-sm bg-gray-100 text-gray-600 font-mono text-center focus:outline-none"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Region Name</label>
                        <input 
                            type="text" 
                            readonly
                            :value="viewingRegion.name"
                            class="w-full px-3 py-1 border border-gray-300 rounded text-sm bg-gray-50 text-gray-700 focus:outline-none"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Districts</label>
                        <input 
                            type="text" 
                            readonly
                            :value="viewingRegion.districts_count || 0"
                            class="w-full px-3 py-1 border border-gray-300 rounded text-sm bg-gray-50 text-gray-700 focus:outline-none"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Schools</label>
                        <input 
                            type="text" 
                            readonly
                            :value="viewingRegion.schools_count || 0"
                            class="w-full px-3 py-1 border border-gray-300 rounded text-sm bg-gray-50 text-gray-700 focus:outline-none"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Candidates</label>
                        <input 
                            type="text" 
                            readonly
                            :value="viewingRegion.candidates_count || 0"
                            class="w-full px-3 py-1 border border-gray-300 rounded text-sm bg-gray-50 text-gray-700 focus:outline-none"
                        >
                    </div>
                    <div class="flex gap-2 pt-3">
                        <button @click="viewModalOpen = false" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 px-3 py-1.5 rounded text-sm transition-colors font-medium">
                            Close
                        </button>
                        <button @click="openEditModal(viewingRegion); viewModalOpen = false;" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-sm transition-colors font-medium">
                            Edit
                        </button>
                    </div>
                </div>

                <!-- Edit/Add Mode -->
                <form x-show="!viewModalOpen" @submit.prevent="saveRegion()" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Region Code <span class="text-xs text-gray-500">(Auto-generated)</span></label>
                        <input 
                            x-model="formData.code"
                            type="text" 
                            readonly
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 focus:outline-none text-gray-600 font-mono text-center"
                        >
                        <p class="text-xs text-gray-500 mt-1">Format: 2-3 letters + 2 digits (e.g., RW05 or TAB10)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Region Name *</label>
                        <input 
                            x-model="formData.name"
                            @input="generateRegionCode()"
                            type="text" 
                            placeholder="e.g., Rwanda"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
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
                            <span x-show="!editingId">Add Region</span>
                            <span x-show="editingId">Update Region</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function regionsManager() {
    return {
        regions: [],
        filteredRegions: [],
        search: '',
        editingId: null,
        loading: false,
        modalOpen: false,
        viewModalOpen: false,
        viewingRegion: {},
        formData: { code: '', name: '' },
        selectedItems: new Set(),
        showToolsMenu: false,
        currentPage: 1,
        pageSize: 10,
        totalCount: 0,
        totalPages: 0,

        async init() {
            await this.loadRegions();
        },

        async loadRegions() {
             this.loading = true;
             try {
                 const response = await fetch(`/api/regions?page=${this.currentPage}&page_size=${this.pageSize}&search=${this.search}`);
                 const data = await response.json();
                 this.regions = data.data || [];
                 this.filteredRegions = this.regions;
                 this.totalCount = data.pagination.total_count;
                 this.totalPages = data.pagination.total_pages;
             } catch (error) {
                 console.error('Error loading regions:', error);
                 this.showMessage('Error loading regions', 'error');
             } finally {
                 this.loading = false;
             }
         },

        filterRegions() {
             this.currentPage = 1;
             this.loadRegions();
         },

        generateRegionCode() {
            if (!this.formData.name || this.formData.name.length < 2) {
                this.formData.code = '';
                return;
            }

            // Get first two letters (uppercase)
            let letters = this.formData.name.substring(0, 2).toUpperCase();

            // Check if there are other regions with the same 2-letter prefix
            const samePrefixRegions = this.regions.filter(r => 
                r.code && r.code.startsWith(letters) && r.id !== this.editingId
            );

            // If there's a conflict, use 3 letters instead
            if (samePrefixRegions.length > 0) {
                // Use first 3 letters if available
                if (this.formData.name.length >= 3) {
                    letters = this.formData.name.substring(0, 3).toUpperCase();
                }
            }

            // Calculate next region number based on MAXIMUM existing number
            // Extract all numeric suffixes from existing regions
            const allNumericSuffixes = this.regions
                .map(r => {
                    if (!r.code) return 0;
                    const match = r.code.match(/\d+$/);
                    return match ? parseInt(match[0]) : 0;
                })
                .filter(num => num > 0);

            // Find next number: max existing + 1, or 1 if none exist
            let nextNumber = 1;
            if (allNumericSuffixes.length > 0) {
                nextNumber = Math.max(...allNumericSuffixes) + 1;
            }

            // Format with leading zeros
            this.formData.code = letters + String(nextNumber).padStart(2, '0');
        },

        openAddModal() {
            this.editingId = null;
            this.formData = { code: '', name: '' };
            this.modalOpen = true;
            this.$nextTick(() => {
                const input = document.querySelector('input[placeholder="e.g., Rwanda"]');
                if (input) input.focus();
            });
        },

        viewRegion(region) {
            this.viewingRegion = { ...region };
            this.editingId = null;
            this.viewModalOpen = true;
        },

        openEditModal(region) {
            this.editingId = region.id;
            this.formData = { code: region.code, name: region.name };
            this.modalOpen = true;
        },

        async saveRegion() {
            try {
                const url = this.editingId ? `/api/regions/${this.editingId}` : '/api/regions';
                const method = this.editingId ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.formData),
                });

                const data = await response.json();
                
                if (response.ok) {
                    this.showMessage(
                        this.editingId ? 'Region updated successfully' : 'Region added successfully',
                        'success'
                    );
                    this.modalOpen = false;
                    await this.loadRegions();
                } else {
                    this.showMessage(data.message || 'Error saving region', 'error');
                }
            } catch (error) {
                console.error('Error saving region:', error);
                this.showMessage('Error saving region', 'error');
            }
        },

        async deleteRegion(id) {
            if (!confirm('Are you sure you want to delete this region?')) return;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                const token = csrfToken ? csrfToken.content : '';
                
                const response = await fetch(`/api/regions/${id}`, {
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
                    // Response might not be JSON
                    console.log('Response text:', await response.text());
                }
                
                if (response.ok || response.status === 200) {
                    this.showMessage('Region deleted successfully', 'success');
                    await this.loadRegions();
                } else if (response.status === 400) {
                    const errorMsg = data.details ? `Cannot delete region with associated records (Districts: ${data.details.districts}, Schools: ${data.details.schools})` : (data.error || data.message || 'Error deleting region');
                    this.showMessage(errorMsg, 'error');
                } else {
                    this.showMessage(data.error || data.message || `Error deleting region (Status: ${response.status})`, 'error');
                }
            } catch (error) {
                console.error('Error deleting region:', error);
                this.showMessage('Error deleting region: ' + error.message, 'error');
            }
        },

        downloadTemplate() {
            const headers = ['Name'].join(',');
            const csv = headers;
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `regions_template_${Date.now()}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            this.showMessage('Template downloaded', 'success');
        },

        exportCSV() {
            const headers = ['Code', 'Name', 'Districts', 'Schools', 'Candidates'].join(',');
            const rows = this.filteredRegions.map(r => 
                [r.code, r.name, r.districts_count || 0, r.schools_count || 0, r.candidates_count || 0].map(v => `"${v}"`).join(',')
            );
            const csv = [headers, ...rows].join('\n');
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `regions_${Date.now()}.csv`;
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
                const response = await fetch('/api/regions/import', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData,
                });

                const data = await response.json();
                
                if (response.ok || data.success) {
                    const imported = data.imported || data.count || 0;
                    const message = imported > 0 
                        ? `${imported} region(s) imported successfully${data.errors && data.errors.length > 0 ? `, ${data.errors.length} failed` : ''}` 
                        : 'No regions imported';
                    this.showMessage(message, imported > 0 ? 'success' : 'warning');
                    
                    if (data.errors && data.errors.length > 0) {
                        console.warn('Import errors:', data.errors);
                    }
                    
                    await this.loadRegions();
                } else {
                    this.showMessage(data.message || data.error || 'Error importing', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showMessage('Error importing', 'error');
            }
            
            // Reset file input
            event.target.value = '';
        },

        toggleSelect(id) {
            if (this.selectedItems.has(id)) {
                this.selectedItems.delete(id);
            } else {
                this.selectedItems.add(id);
            }
        },

        toggleSelectAll() {
            if (this.selectedItems.size === this.filteredRegions.length) {
                this.selectedItems.clear();
            } else {
                this.filteredRegions.forEach(region => this.selectedItems.add(region.id));
            }
        },

        async bulkDeleteRegions() {
            if (this.selectedItems.size === 0) return;
            
            const count = this.selectedItems.size;
            if (!confirm(`Are you sure you want to delete ${count} region(s)? This action cannot be undone.`)) return;

            try {
                const ids = Array.from(this.selectedItems);
                const response = await fetch('/api/regions/bulk-delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ ids }),
                });

                const data = await response.json();
                
                if (response.ok) {
                    this.showMessage(`${data.deleted} region(s) deleted successfully`, 'success');
                    this.selectedItems.clear();
                    await this.loadRegions();
                } else {
                    this.showMessage(data.message || 'Error deleting regions', 'error');
                }
            } catch (error) {
                console.error('Error deleting regions:', error);
                this.showMessage('Error deleting regions', 'error');
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
