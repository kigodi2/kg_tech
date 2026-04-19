@extends('layout')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h1 class="text-2xl font-bold mb-4">User Dashboard</h1>
                <p class="mb-6">Hello, <strong>{{ Auth::user()->name }}</strong>! Welcome to your personal portal.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-orange-50 p-6 rounded-lg border border-orange-100">
                        <h3 class="font-bold text-orange-800 mb-2"><i class="fas fa-id-card mr-2"></i> My Profile</h3>
                        <p class="text-sm text-orange-600 mb-4">View and update your personal information.</p>
                        <a href="#" class="inline-block bg-orange-600 text-white px-4 py-2 rounded text-sm hover:bg-orange-700">View Profile</a>
                    </div>
                    
                    <div class="bg-indigo-50 p-6 rounded-lg border border-indigo-100">
                        <h3 class="font-bold text-indigo-800 mb-2"><i class="fas fa-database mr-2"></i> My Data</h3>
                        <p class="text-sm text-indigo-600 mb-4">Manage your own data entries and history.</p>
                        <a href="#" class="inline-block bg-indigo-600 text-white px-4 py-2 rounded text-sm hover:bg-indigo-700">Manage Data</a>
                    </div>
                    
                    <div class="bg-teal-50 p-6 rounded-lg border border-teal-100">
                        <h3 class="font-bold text-teal-800 mb-2"><i class="fas fa-tasks mr-2"></i> Assigned Tasks</h3>
                        <p class="text-sm text-teal-600 mb-4">Check your current assignments and deadlines.</p>
                        <a href="#" class="inline-block bg-teal-600 text-white px-4 py-2 rounded text-sm hover:bg-teal-700">View Tasks</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
