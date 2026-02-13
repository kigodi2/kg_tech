@extends('filament::layouts.app')

@section('content')
<div x-data="userFormManager()" @init="init()" class="p-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Create User</h1>
        <p class="text-gray-600">Add a new user to the system</p>
    </div>

    <!-- Form -->
    <form @submit.prevent="submitForm()" class="bg-white rounded-lg shadow p-8 max-w-2xl">
        @csrf

        <!-- Identity Section -->
        <div class="mb-8 pb-8 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800 mb-6">Identity</h2>
            
            <div class="space-y-6">
                <!-- Full Name -->
                <div class="flex flex-col">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name <span class="text-red-500">*</span></label>
                    <input 
                        x-model="form.name"
                        type="text"
                        name="name"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter full name"
                    >
                </div>

                <!-- Email -->
                <div class="flex flex-col">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                    <input 
                        x-model="form.email"
                        type="email"
                        name="email"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="user@example.com"
                    >
                </div>

                <!-- Phone -->
                <div class="flex flex-col">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Phone Number</label>
                    <input 
                        x-model="form.phone"
                        type="tel"
                        name="phone"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="+255..."
                    >
                </div>
            </div>
        </div>

        <!-- Authorization Section -->
        <div class="mb-8 pb-8 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800 mb-6">Authorization</h2>

            <div class="space-y-6">
                <!-- Role Selection -->
                <x-searchable-select 
                    label="Role"
                    name="role_id"
                    :options="$roleOptions"
                    placeholder="Select an option"
                    searchPlaceholder="Start typing to search..."
                    required
                />

                <!-- Scope Type (conditional) -->
                <div x-show="isScopeRequired()" x-cloak class="transition-all">
                    <x-searchable-select 
                        label="Scope Type"
                        name="scope_type"
                        :options="[
                            ['value' => 'region', 'label' => 'Region'],
                            ['value' => 'district', 'label' => 'District'],
                            ['value' => 'school', 'label' => 'School'],
                        ]"
                        placeholder="Select an option"
                    />
                </div>

                <!-- Scope (conditional) -->
                <div x-show="isScopeRequired()" x-cloak class="transition-all">
                    <x-searchable-select 
                        label="Scope"
                        name="scope_id"
                        :options="$scopeOptions"
                        placeholder="Select an option"
                    />
                </div>
            </div>
        </div>

        <!-- Account Status Section -->
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-800 mb-6">Account Status</h2>

            <div class="space-y-6">
                <!-- Status -->
                <div class="flex flex-col">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Status <span class="text-red-500">*</span></label>
                    <select 
                        x-model="form.status"
                        name="status"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Select status</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>

                <!-- Suspension Reason (conditional) -->
                <div x-show="form.status === 'suspended'" x-cloak>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Suspension Reason</label>
                    <textarea 
                        x-model="form.suspension_reason"
                        name="suspension_reason"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter reason for suspension"
                        rows="3"
                    ></textarea>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-4">
            <button 
                type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors font-medium"
            >
                <i class="fas fa-check mr-2"></i> Create User
            </button>
            <a 
                href="{{ route('filament.admin.resources.users.index') }}"
                class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2 rounded-lg transition-colors font-medium"
            >
                <i class="fas fa-times mr-2"></i> Cancel
            </a>
        </div>
    </form>
</div>

<script>
function userFormManager() {
    return {
        form: {
            name: '',
            email: '',
            phone: '',
            role_id: '',
            scope_type: '',
            scope_id: '',
            status: 'active',
            suspension_reason: '',
        },
        adminRoles: @js(\App\Models\Role::where('code', 'admin')->pluck('id')),
        
        isScopeRequired() {
            if (!this.form.role_id) return false;
            // Only admin doesn't need scope
            return this.form.role_id != @js(\App\Models\Role::where('code', 'admin')->first()?->id);
        },

        async submitForm() {
            // Submit form via standard POST
            const form = document.querySelector('form');
            form.submit();
        }
    }
}
</script>
@endsection
