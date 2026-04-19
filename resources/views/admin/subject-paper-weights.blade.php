@extends('layout')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="paperWeightsAdmin()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Subject Paper Weights</h1>
            <p class="text-sm text-gray-600">Manage per-subject paper weights used in ACSEE normalization.</p>
        </div>
        <a href="/results/acsee?view=entry-validation" class="px-3 py-2 rounded border border-blue-300 text-blue-700 text-sm hover:bg-blue-50">Back to Results</a>
    </div>

    <div class="bg-white border rounded-lg p-4 mb-4 grid grid-cols-1 md:grid-cols-4 gap-3">
        <input x-model="filters.q" @input.debounce.350ms="loadRows(1)" class="px-3 py-2 border rounded text-sm" placeholder="Search subject code/name">
        <select x-model="filters.subject_id" @change="loadRows(1)" class="px-3 py-2 border rounded text-sm">
            <option value="">All subjects</option>
            <template x-for="s in subjects" :key="'s-' + s.id">
                <option :value="s.id" x-text="`${s.code} - ${s.name}`"></option>
            </template>
        </select>
        <button @click="openCreate()" class="px-3 py-2 bg-emerald-600 text-white rounded text-sm hover:bg-emerald-700">Add Weight</button>
        <button @click="loadRows(1)" class="px-3 py-2 border border-gray-300 rounded text-sm hover:bg-gray-50">Refresh</button>
    </div>

    <div x-show="message" class="mb-4 px-3 py-2 rounded text-sm" :class="error ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'" x-text="message"></div>

    <div class="bg-white border rounded-lg overflow-hidden">
        <div x-show="loading" class="p-6 text-center"><i class="fas fa-spinner animate-spin text-blue-500"></i></div>
        <div x-show="!loading" class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300 sticky top-0 z-10">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <i class="fas fa-book mr-1 text-blue-600"></i>Subject
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <i class="fas fa-file-alt mr-1 text-indigo-600"></i>Paper
                        </th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <i class="fas fa-balance-scale mr-1 text-emerald-600"></i>Weight
                        </th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <i class="fas fa-hashtag mr-1 text-purple-600"></i>Max Mark
                        </th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <i class="fas fa-check-circle mr-1 text-green-600"></i>Required
                        </th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <i class="fas fa-toggle-on mr-1 text-blue-500"></i>Active
                        </th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <i class="fas fa-cogs mr-1 text-gray-600"></i>Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <template x-for="row in rows.data" :key="'row-' + row.id">
                        <tr class="hover:bg-blue-50 transition-colors">
                            <td class="px-3 py-3 text-sm text-gray-800 font-medium" x-text="`${row.subject?.code || ''} - ${row.subject?.name || ''}`"></td>
                            <td class="px-3 py-3 text-sm">
                                <div class="flex items-center gap-2">
                                    <span x-text="row.paper_code"></span>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold"
                                          :class="row.paper_code === 'paper_3' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800'"
                                          x-text="row.paper_code === 'paper_3' ? 'Practical (50)' : 'Written (100)'"></span>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-sm text-center font-medium text-gray-700" x-text="row.weight"></td>
                            <td class="px-3 py-3 text-sm text-center font-medium text-gray-700" x-text="row.max_mark"></td>
                            <td class="px-3 py-3 text-sm text-center"><span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="row.is_required ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600'" x-text="row.is_required ? 'YES' : 'NO'"></span></td>
                            <td class="px-3 py-3 text-sm text-center"><span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="row.is_active ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'" x-text="row.is_active ? 'YES' : 'NO'"></span></td>
                            <td class="px-3 py-3 text-right space-x-2">
                                <button @click="openEdit(row)" class="px-2 py-1 border rounded text-xs hover:bg-gray-50">Edit</button>
                                <button @click="removeRow(row.id)" class="px-2 py-1 border border-red-300 text-red-700 rounded text-xs hover:bg-red-50">Delete</button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!rows.data.length">
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500 text-sm">No paper weights found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="form.open" class="fixed inset-0 bg-black/30 flex items-center justify-center p-4 z-50">
        <div class="bg-white w-full max-w-lg rounded-lg shadow p-4 space-y-3">
            <h2 class="text-lg font-semibold" x-text="form.id ? 'Edit Paper Weight' : 'Add Paper Weight'"></h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="md:col-span-2">
                    <label class="text-xs text-gray-600">Subject</label>
                    <select x-model="form.subject_id" class="w-full px-3 py-2 border rounded text-sm" :disabled="!!form.id">
                        <option value="">Select subject</option>
                        <template x-for="s in subjects" :key="'fs-' + s.id">
                            <option :value="s.id" x-text="`${s.code} - ${s.name}`"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-600">Paper Code</label>
                    <select x-model="form.paper_code" @change="syncMaxMarkFromPaperCode()" class="w-full px-3 py-2 border rounded text-sm">
                        <option value="paper_1">paper_1 (Theory)</option>
                        <option value="paper_2">paper_2 (Second Written)</option>
                        <option value="paper_3">paper_3 (Practical)</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-600">Weight</label>
                    <input x-model="form.weight" type="number" step="0.0001" min="0.0001" class="w-full px-3 py-2 border rounded text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-600">Max Mark</label>
                    <input x-model="form.max_mark" type="number" step="0.01" min="0.01" readonly class="w-full px-3 py-2 border rounded text-sm bg-gray-50 cursor-not-allowed">
                    <p class="text-[11px] text-gray-500 mt-1">Auto-set: paper_1/paper_2 = 100, paper_3 (Practical) = 50.</p>
                </div>
                <div class="flex items-center gap-3">
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" x-model="form.is_required"> Required</label>
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" x-model="form.is_active"> Active</label>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button @click="form.open = false" class="px-3 py-2 border rounded text-sm">Cancel</button>
                <button @click="saveForm()" class="px-3 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
function paperWeightsAdmin() {
    return {
        subjects: [],
        rows: { data: [] },
        loading: false,
        message: '',
        error: false,
        filters: { q: '', subject_id: '' },
        form: {
            open: false,
            id: null,
            subject_id: '',
            paper_code: 'paper_1',
            weight: 1,
            max_mark: 100,
            is_required: true,
            is_active: true,
        },
        async init() {
            await this.loadSubjects();
            await this.loadRows(1);
        },
        csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },
        async loadSubjects() {
            const res = await fetch('/api/admin/subject-paper-weights/subjects');
            const json = await res.json();
            this.subjects = json.data || [];
        },
        async loadRows(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({ page: String(page), per_page: '50' });
                if (this.filters.q) params.set('q', this.filters.q);
                if (this.filters.subject_id) params.set('subject_id', this.filters.subject_id);
                const res = await fetch(`/api/admin/subject-paper-weights?${params.toString()}`);
                const json = await res.json();
                this.rows = json.data || { data: [] };
            } finally {
                this.loading = false;
            }
        },
        openCreate() {
            this.form = { open: true, id: null, subject_id: '', paper_code: 'paper_1', weight: 1, max_mark: 100, is_required: true, is_active: true };
            this.syncMaxMarkFromPaperCode();
        },
        openEdit(row) {
            this.form = {
                open: true,
                id: row.id,
                subject_id: row.subject_id,
                paper_code: row.paper_code,
                weight: row.weight,
                max_mark: row.max_mark,
                is_required: !!row.is_required,
                is_active: !!row.is_active,
            };
            this.syncMaxMarkFromPaperCode();
        },
        syncMaxMarkFromPaperCode() {
            this.form.max_mark = this.form.paper_code === 'paper_3' ? 50 : 100;
        },
        async saveForm() {
            this.message = '';
            this.error = false;
            const payload = {
                subject_id: this.form.subject_id,
                paper_code: this.form.paper_code,
                weight: this.form.weight,
                max_mark: this.form.max_mark,
                is_required: !!this.form.is_required,
                is_active: !!this.form.is_active,
            };
            const isEdit = !!this.form.id;
            const res = await fetch(isEdit ? `/api/admin/subject-paper-weights/${this.form.id}` : '/api/admin/subject-paper-weights', {
                method: isEdit ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                body: JSON.stringify(payload),
            });
            const json = await res.json();
            this.error = !res.ok || !json.success;
            this.message = json.message || (this.error ? 'Failed to save.' : 'Saved.');
            if (!this.error) {
                this.form.open = false;
                await this.loadRows(1);
            }
        },
        async removeRow(id) {
            if (!window.confirm('Delete this paper weight?')) return;
            this.message = '';
            this.error = false;
            const res = await fetch(`/api/admin/subject-paper-weights/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrf() },
            });
            const json = await res.json();
            this.error = !res.ok || !json.success;
            this.message = json.message || (this.error ? 'Failed to delete.' : 'Deleted.');
            if (!this.error) await this.loadRows(1);
        },
    };
}
</script>
@endsection
