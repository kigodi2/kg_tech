@extends('layout')

@section('content')
<div class="w-full px-4 md:px-8 py-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Backups & Restore</h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">Manage SQLite database backups and restoration</p>
        </div>
        <form method="POST" action="{{ route('backups.create') }}" class="w-full md:w-auto">
            @csrf
            <button type="submit" class="w-full md:w-auto inline-flex items-center justify-center md:justify-start gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-plus"></i> <span>Create Backup</span>
            </button>
        </form>
    </div>

    <!-- Flash Messages -->
    @if($message = session('success'))
        <div class="mb-6 p-3 md:p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg text-sm md:text-base">
            {{ $message }}
        </div>
    @endif

    @if($message = session('error'))
        <div class="mb-6 p-3 md:p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm md:text-base">
            {{ $message }}
        </div>
    @endif

    <!-- Backups Table - Responsive -->
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <!-- Desktop Table -->
        <div class="hidden md:block">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 md:px-6 py-3 text-left text-sm font-semibold text-gray-900">Backup ID</th>
                        <th class="px-4 md:px-6 py-3 text-left text-sm font-semibold text-gray-900">Type</th>
                        <th class="px-4 md:px-6 py-3 text-left text-sm font-semibold text-gray-900">Size</th>
                        <th class="px-4 md:px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                        <th class="px-4 md:px-6 py-3 text-left text-sm font-semibold text-gray-900">Created By</th>
                        <th class="px-4 md:px-6 py-3 text-left text-sm font-semibold text-gray-900">Date</th>
                        <th class="px-4 md:px-6 py-3 text-left text-sm font-semibold text-gray-900">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($backups as $backup)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 md:px-6 py-4 text-sm text-gray-900 font-mono truncate">{{ $backup->data['backup_id'] ?? 'N/A' }}</td>
                            <td class="px-4 md:px-6 py-4 text-sm">
                                <span class="inline-flex items-center rounded-full px-2 md:px-3 py-1 text-xs md:text-sm font-medium {{ strpos($backup->operation, 'incremental') !== false ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                    {{ strpos($backup->operation, 'incremental') !== false ? 'Incremental' : 'Full' }}
                                </span>
                            </td>
                            <td class="px-4 md:px-6 py-4 text-sm text-gray-600">
                                @php
                                    $bytes = $backup->data['archive_size'] ?? 0;
                                    if ($bytes >= 1073741824) {
                                        echo number_format($bytes / 1073741824, 2) . ' GB';
                                    } elseif ($bytes >= 1048576) {
                                        echo number_format($bytes / 1048576, 2) . ' MB';
                                    } elseif ($bytes >= 1024) {
                                        echo number_format($bytes / 1024, 2) . ' KB';
                                    } else {
                                        echo $bytes . ' B';
                                    }
                                @endphp
                            </td>
                            <td class="px-4 md:px-6 py-4 text-sm">
                                <span class="inline-flex items-center rounded-full px-2 md:px-3 py-1 text-xs md:text-sm font-medium {{ $backup->status === 'success' ? 'bg-green-100 text-green-800' : ($backup->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                    {{ ucfirst($backup->status) }}
                                </span>
                            </td>
                            <td class="px-4 md:px-6 py-4 text-sm text-gray-600">{{ $backup->user->name ?? 'System' }}</td>
                            <td class="px-4 md:px-6 py-4 text-sm text-gray-600">{{ $backup->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-4 md:px-6 py-4 text-sm">
                                @if($backup->status === 'success')
                                    <form method="POST" action="{{ route('backups.delete', $backup->id) }}" class="inline" onsubmit="return confirm('Are you sure?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 px-2 md:px-3 py-1 bg-red-600 text-white text-xs md:text-sm rounded hover:bg-red-700 transition">
                                            <i class="fas fa-trash"></i> <span class="hidden md:inline">Delete</span>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 md:px-6 py-8 text-center text-gray-500">
                                No backups found. Click "Create Backup" to create your first backup.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="md:hidden divide-y divide-gray-200">
            @forelse($backups as $backup)
                <div class="p-4 space-y-3">
                    <div class="flex justify-between items-start gap-2">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs text-gray-500 uppercase font-semibold">Backup ID</p>
                            <p class="text-sm font-mono truncate text-gray-900">{{ $backup->data['backup_id'] ?? 'N/A' }}</p>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ strpos($backup->operation, 'incremental') !== false ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }} flex-shrink-0">
                            {{ strpos($backup->operation, 'incremental') !== false ? 'Incremental' : 'Full' }}
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold">Size</p>
                            <p class="text-sm text-gray-600">
                                @php
                                    $bytes = $backup->data['archive_size'] ?? 0;
                                    if ($bytes >= 1073741824) {
                                        echo number_format($bytes / 1073741824, 2) . ' GB';
                                    } elseif ($bytes >= 1048576) {
                                        echo number_format($bytes / 1048576, 2) . ' MB';
                                    } elseif ($bytes >= 1024) {
                                        echo number_format($bytes / 1024, 2) . ' KB';
                                    } else {
                                        echo $bytes . ' B';
                                    }
                                @endphp
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold">Status</p>
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $backup->status === 'success' ? 'bg-green-100 text-green-800' : ($backup->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ ucfirst($backup->status) }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold">Created By</p>
                            <p class="text-sm text-gray-600">{{ $backup->user->name ?? 'System' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold">Date</p>
                            <p class="text-sm text-gray-600">{{ $backup->created_at->format('Y-m-d H:i') }}</p>
                        </div>
                    </div>
                    
                    @if($backup->status === 'success')
                        <form method="POST" action="{{ route('backups.delete', $backup->id) }}" class="pt-2 border-t" onsubmit="return confirm('Are you sure?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2 bg-red-600 text-white text-sm rounded hover:bg-red-700 transition">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="p-8 text-center text-gray-500">
                    No backups found. Click "Create Backup" to create your first backup.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Pagination -->
    @if($backups->hasPages())
        <div class="mt-6 flex flex-col md:flex-row items-center justify-between gap-4">
            <p class="text-xs md:text-sm text-gray-600 text-center md:text-left">
                Showing <span class="font-semibold">{{ $backups->firstItem() }}</span> to 
                <span class="font-semibold">{{ $backups->lastItem() }}</span> of 
                <span class="font-semibold">{{ $backups->total() }}</span> backups
            </p>
            <div class="w-full md:w-auto overflow-x-auto">
                {{ $backups->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
