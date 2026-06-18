@extends('layouts.mark-entry')

@section('title', 'Reopen Requests')

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Reopen Requests</h2>
            <nav class="text-sm text-gray-500 mt-1">
                <ol class="list-none p-0 inline-flex">
                    <li class="flex items-center">
                        <a href="#" class="hover:text-blue-600">Administration</a>
                        <i class="fas fa-chevron-right mx-2 text-xs"></i>
                    </li>
                    <li class="flex items-center text-gray-700 font-medium">
                        Reopen Requests
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow border border-gray-200">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-gray-50 rounded-t-lg">
            <div class="flex gap-3">
                <button class="px-3 py-1 bg-white border border-gray-300 rounded text-sm hover:bg-gray-50 font-medium">Pending</button>
                <button class="px-3 py-1 bg-white border border-transparent text-gray-500 rounded text-sm hover:bg-gray-100">History</button>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-white text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Date</th>
                        <th class="px-6 py-3 font-semibold">Requested By</th>
                        <th class="px-6 py-3 font-semibold">Scope</th>
                        <th class="px-6 py-3 font-semibold">Reason</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                        <th class="px-6 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-check-circle text-3xl mb-3 text-gray-300"></i>
                            <p>No pending reopen requests.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
