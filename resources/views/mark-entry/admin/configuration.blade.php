@extends('layouts.mark-entry')

@section('content')
<div class="w-full">
    <div class="bg-white border-b border-gray-200 px-8 py-6 sticky top-0 z-40 shadow-sm">
        <h1 class="text-2xl font-bold text-gray-800">ACSEE Mark Entry Configuration</h1>
    </div>

    <div class="px-8 py-8">
        <div class="grid grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Subject Configuration</h2>
                <p class="text-gray-600 text-sm">Configure ACSEE subject parameters and paper structures</p>
                <button class="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg">
                    Manage Subjects
                </button>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Validation Rules</h2>
                <p class="text-gray-600 text-sm">Configure mark validation rules and constraints</p>
                <button class="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg">
                    Edit Rules
                </button>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Permission Matrix</h2>
                <p class="text-gray-600 text-sm">Configure role-based access control and permissions</p>
                <button class="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg">
                    Manage Permissions
                </button>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">System Settings</h2>
                <p class="text-gray-600 text-sm">Configure global system settings and preferences</p>
                <button class="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg">
                    Manage Settings
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
