@extends('layout')

@section('content')
@include('registration.partials.theme')
<style>
    .exam-type-search-input {
        width: 100%;
        min-height: 46px;
        padding: 0 14px;
        border: 1px solid #cbd5e1 !important;
        border-radius: 0 !important;
        background: #ffffff;
        color: #0f172a;
        font-size: 14px;
    }

    .exam-type-search-input:focus {
        outline: 2px solid rgba(59, 130, 246, 0.15);
        outline-offset: 0;
        border-color: #3b82f6 !important;
    }
</style>
<div class="registration-shell">
    <div class="registration-page-stack">
    @include('registration.partials.header', [
        'kicker' => 'Exam Setup Workspace',
        'title' => 'Exam Types Management',
        'subtitle' => 'Manage examination types, codes, levels, and high-level configurations for PSLE, CSEE, ACSEE, and related exam structures.',
        'highlights' => [
            ['icon' => 'fas fa-file-lines', 'text' => 'Exam type registry'],
            ['icon' => 'fas fa-sliders', 'text' => 'Configuration control'],
            ['icon' => 'fas fa-user-graduate', 'text' => 'Candidate-linked totals'],
        ],
        'noteTitle' => 'Setup Scope',
        'noteText' => 'Exam types sit at the top of exam configuration, so this workspace is designed for clear maintenance and low-friction updates.',
        'noteItems' => [
            ['icon' => 'fas fa-magnifying-glass', 'title' => 'Search', 'text' => 'Filter the current exam type list quickly.'],
            ['icon' => 'fas fa-plus', 'title' => 'Create', 'text' => 'Add new exam types from the same management surface.'],
        ],
    ])

    <!-- Exam Types Component -->
    <div x-data="examTypesManager()" @init="init()" class="space-y-6">
        <!-- Toolbar -->
        <div class="registration-surface registration-toolbar-card">
            <div class="registration-toolbar-grid">
                <!-- Search Input -->
                <input 
                    x-model="search" 
                    @input="filterExamTypes()"
                    type="text" 
                    placeholder="Search exam types..." 
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                
                <!-- Add Exam Type Button -->
                <button 
                    @click="openAddModal()"
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors flex items-center gap-2 font-medium"
                >
                    <i class="fas fa-plus"></i> Add Exam Type
                </button>
            </div>
        </div>

        <!-- Exam Types Table -->
        <div class="registration-surface registration-table-card overflow-hidden">
            <div x-show="loading" class="p-6 text-center text-gray-500">
                <i class="fas fa-spinner animate-spin text-2xl"></i> Loading...
            </div>
            <table x-show="!loading" class="w-full">
                <thead class="bg-gray-100 border-b-2 border-gray-300">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Code</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 uppercase">Level</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Description</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 uppercase">Candidates</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <template x-for="examType in filteredExamTypes" :key="examType.id">
                        <tr class="hover:bg-blue-100 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-800 font-medium" x-text="examType.name"></td>
                            <td class="px-6 py-4 text-sm font-mono text-gray-600" x-text="examType.code"></td>
                            <td class="px-6 py-4 text-sm text-gray-600 text-center" x-text="examType.level || '-'"></td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="examType.description || '-'"></td>
                            <td class="px-6 py-4 text-sm text-gray-600 text-center" x-text="examType.candidates_count || 0"></td>
                            <td class="px-6 py-4 text-sm space-x-3">
                                <button 
                                    @click="openEditModal(examType)"
                                    class="inline-flex items-center justify-center w-8 h-8 text-blue-600 hover:text-blue-900 hover:bg-blue-50 rounded transition-colors"
                                    title="Edit"
                                >
                                    <i class="fas fa-edit text-sm"></i>
                                </button>
                                <button 
                                    @click="deleteExamType(examType.id)"
                                    class="inline-flex items-center justify-center w-8 h-8 text-red-600 hover:text-red-900 hover:bg-red-50 rounded transition-colors"
                                    title="Delete"
                                >
                                    <i class="fas fa-trash text-sm"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <template x-if="filteredExamTypes.length === 0 && !loading">
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                No exam types found.
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Modal (Add/Edit) -->
        <div 
            x-show="modalOpen" 
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/55 p-4"
            @click.self="modalOpen = false;"
            x-transition
            style="display: none;"
        >
            <div class="registration-modal-shell max-w-2xl" x-transition>
                <div class="registration-modal-header">
                    <div class="registration-modal-header-content">
                        <div>
                            <span class="registration-modal-kicker">
                                <i class="fas fa-layer-group text-amber-300"></i>
                                Exam Type Record
                            </span>
                            <h2 class="registration-modal-title">
                                <span x-show="!editingId">Add New Exam Type</span>
                                <span x-show="editingId">Edit Exam Type</span>
                            </h2>
                            <p class="registration-modal-subtitle">Keep the top-level exam catalog structured before configuring subjects, papers, and candidates.</p>
                        </div>
                        <button 
                            @click="modalOpen = false;" 
                            class="registration-modal-close"
                            aria-label="Close exam type modal"
                        >
                            &times;
                        </button>
                    </div>
                </div>

                <form @submit.prevent="saveExamType()" class="registration-modal-body space-y-5">
                    <div class="registration-modal-note">
                        <span class="registration-modal-note-icon"><i class="fas fa-circle-info"></i></span>
                        <div>
                            <strong>Form Guidance</strong>
                            <p>Use a unique code and a clear level so downstream exam configuration screens remain predictable.</p>
                        </div>
                    </div>
                    <div class="registration-modal-panel p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Name *</label>
                        <input 
                            x-model="formData.name"
                            type="text" 
                            placeholder="e.g., ACSEE"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Code *</label>
                        <input 
                            x-model="formData.code"
                            type="text" 
                            placeholder="e.g., ACSEE"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Level</label>
                        <input
                            x-model="formData.level"
                            list="exam-type-level-options"
                            type="text"
                            placeholder="Search level"
                            autocomplete="off"
                            class="exam-type-search-input"
                        >
                        <datalist id="exam-type-level-options">
                            <option value="Primary"></option>
                            <option value="Secondary"></option>
                            <option value="Advanced Secondary"></option>
                        </datalist>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                        <textarea 
                            x-model="formData.description"
                            placeholder="Brief description..."
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        ></textarea>
                    </div>

                    <div class="registration-modal-actions">
                        <button 
                            type="button" 
                            @click="modalOpen = false" 
                            class="registration-modal-button registration-modal-button-secondary"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="registration-modal-button registration-modal-button-primary"
                        >
                            <span x-show="!editingId">Add</span>
                            <span x-show="editingId">Update</span>
                        </button>
                    </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

<script>
function examTypesManager() {
    return {
        examTypes: [],
        filteredExamTypes: [],
        search: '',
        loading: false,
        modalOpen: false,
        editingId: null,
        formData: { name: '', code: '', level: '', description: '' },
        allowedLevels: ['Primary', 'Secondary', 'Advanced Secondary'],

        async init() {
            await this.loadExamTypes();
        },

        async loadExamTypes() {
            this.loading = true;
            try {
                const response = await fetch('/admin/api/exam-types');
                const data = await response.json();
                this.examTypes = data.data || [];
                this.filteredExamTypes = this.examTypes;
            } catch (error) {
                console.error('Error loading exam types:', error);
                this.showMessage('Error loading exam types', 'error');
            } finally {
                this.loading = false;
            }
        },

        filterExamTypes() {
            if (!this.search) {
                this.filteredExamTypes = this.examTypes;
                return;
            }
            
            const query = this.search.toLowerCase();
            this.filteredExamTypes = this.examTypes.filter(e => 
                e.name.toLowerCase().includes(query) ||
                e.code.toLowerCase().includes(query) ||
                (e.description && e.description.toLowerCase().includes(query))
            );
        },

        openAddModal() {
            this.editingId = null;
            this.formData = { name: '', code: '', level: '', description: '' };
            this.modalOpen = true;
        },

        openEditModal(examType) {
            this.editingId = examType.id;
            this.formData = { 
                name: examType.name,
                code: examType.code,
                level: examType.level || '',
                description: examType.description || ''
            };
            this.modalOpen = true;
        },

        async saveExamType() {
            if (!this.resolveLevelChoice()) {
                this.showMessage('Please choose Level from the searchable list.', 'error');
                return;
            }

            try {
                const url = this.editingId ? `/admin/api/exam-types/${this.editingId}` : '/admin/api/exam-types';
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
                        this.editingId ? 'Exam type updated successfully' : 'Exam type added successfully',
                        'success'
                    );
                    this.modalOpen = false;
                    await this.loadExamTypes();
                } else {
                    this.showMessage(data.message || 'Error saving exam type', 'error');
                }
            } catch (error) {
                console.error('Error saving exam type:', error);
                this.showMessage('Error saving exam type', 'error');
            }
        },

        async deleteExamType(id) {
            if (!confirm('Are you sure you want to delete this exam type?')) return;

            try {
                const response = await fetch(`/admin/api/exam-types/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                const data = await response.json();
                
                if (response.ok) {
                    this.showMessage('Exam type deleted successfully', 'success');
                    await this.loadExamTypes();
                } else {
                    this.showMessage(data.message || 'Error deleting exam type', 'error');
                }
            } catch (error) {
                console.error('Error deleting exam type:', error);
                this.showMessage('Error deleting exam type', 'error');
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

        resolveLevelChoice() {
            const value = (this.formData.level || '').trim();

            if (value === '') {
                this.formData.level = '';
                return true;
            }

            const matched = this.allowedLevels.find(level => level === value);
            this.formData.level = matched || '';

            return Boolean(matched);
        },
    };
}
</script>
@endsection
