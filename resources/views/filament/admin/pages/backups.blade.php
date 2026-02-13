<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Backups & Restore</h1>
                <p class="mt-2 text-gray-600">Manage SQLite database backups and restoration</p>
            </div>
            <x-filament::button 
                wire:click="createNewBackup" 
                icon="heroicon-o-plus" 
                color="success"
                size="lg"
            >
                Create Backup
            </x-filament::button>
        </div>

        <!-- Backups Table -->
        <div class="overflow-x-auto bg-white rounded-lg shadow">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Backup ID</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Type</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Size</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Created By</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Date</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($backupLogs as $backup)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 text-sm text-gray-900 font-mono">{{ $backup->data['backup_id'] ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium bg-green-100 text-green-800">
                                    Full
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                @php
                                    $bytes = $backup->data['archive_size'] ?? 0;
                                    if ($bytes >= 1073741824) { // 1 GB
                                        echo number_format($bytes / 1073741824, 2) . ' GB';
                                    } elseif ($bytes >= 1048576) { // 1 MB
                                        echo number_format($bytes / 1048576, 2) . ' MB';
                                    } else {
                                        echo number_format($bytes / 1024, 2) . ' KB';
                                    }
                                @endphp
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium {{ $backup->status === 'success' ? 'bg-green-100 text-green-800' : ($backup->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                    {{ ucfirst($backup->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $backup->user->name ?? 'System' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $backup->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-6 py-4 text-sm space-x-2">
                                @if($backup->status === 'success')
                                    <x-filament::button 
                                        size="sm" 
                                        color="danger"
                                        wire:click="deleteBackup({{ $backup->id }})"
                                        icon="heroicon-o-trash"
                                        onclick="return confirm('Are you sure?')"
                                    >
                                        Delete
                                    </x-filament::button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                No backups found. Click "Create Backup" to create your first backup.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($backupLogs->hasPages())
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-600">
                    Showing <span class="font-semibold">{{ $backupLogs->firstItem() }}</span> to 
                    <span class="font-semibold">{{ $backupLogs->lastItem() }}</span> of 
                    <span class="font-semibold">{{ $backupLogs->total() }}</span> backups
                </p>
                <div class="space-x-2">
                    {{ $backupLogs->links() }}
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
