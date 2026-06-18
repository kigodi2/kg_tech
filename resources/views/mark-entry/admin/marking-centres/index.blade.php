@extends('layouts.mark-entry')

@section('title', 'Marking Centres')

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Marking Centres</h2>
            <nav class="text-sm text-gray-500 mt-1">
                <ol class="list-none p-0 inline-flex">
                    <li class="flex items-center">
                        <a href="#" class="hover:text-blue-600">Administration</a>
                        <i class="fas fa-chevron-right mx-2 text-xs"></i>
                    </li>
                    <li class="flex items-center text-gray-700 font-medium">
                        Marking Centres
                    </li>
                </ol>
            </nav>
        </div>
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            <i class="fas fa-plus mr-2"></i> Add Centre
        </button>
    </div>

    <div class="bg-white rounded-lg shadow border border-gray-200">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-gray-50 rounded-t-lg">
            <h3 class="font-semibold text-gray-800">Regional Marking Centres</h3>
            <div class="flex gap-2">
                <input type="text" placeholder="Search centres..." class="border border-gray-300 rounded px-3 py-1 text-sm focus:outline-none focus:border-blue-500">
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-white text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Region</th>
                        <th class="px-6 py-3 font-semibold">Centre Code</th>
                        <th class="px-6 py-3 font-semibold">Name</th>
                        <th class="px-6 py-3 font-semibold">Location</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                        <th class="px-6 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <!-- Example empty state / placeholder -->
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-building text-3xl mb-3 text-gray-300"></i>
                            <p>No marking centres found. Create one to get started.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
