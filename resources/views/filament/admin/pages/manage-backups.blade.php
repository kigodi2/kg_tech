<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Backups & Restore</h2>
                <p class="mt-1 text-gray-600">Manage SQLite database backups and restoration</p>
            </div>
            @isset($this->headerActions)
                <div class="flex gap-3">
                    @foreach ($this->headerActions as $action)
                        {{ $action }}
                    @endforeach
                </div>
            @endisset
        </div>

        <!-- Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Last Backup -->
            @php
                $lastBackup = App\Models\BackupLog::backupOperations()->successful()->latest()->first();
            @endphp
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-semibold text-gray-600 uppercase">Last Backup</h3>
                @if($lastBackup)
                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        {{ $lastBackup->created_at->format('M d, Y') }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ $lastBackup->created_at->diffForHumans() }}
                    </p>
                @else
                    <p class="mt-2 text-2xl font-bold text-gray-900">Never</p>
                    <p class="mt-1 text-xs text-gray-500">No backups created yet</p>
                @endif
            </div>

            <!-- Total Backups -->
            @php
                $totalBackups = App\Models\BackupLog::backupOperations()->count();
            @endphp
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-semibold text-gray-600 uppercase">Total Backups</h3>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ $totalBackups }}</p>
                <p class="mt-1 text-xs text-gray-500">Across all types</p>
            </div>

            <!-- Storage Used -->
            @php
                $totalSize = \App\Services\BackupStatisticsService::getTotalBackupSize();
                $formattedSize = \App\Services\BackupStatisticsService::formatBytes($totalSize);
            @endphp
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-semibold text-gray-600 uppercase">Storage Used</h3>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ $formattedSize }}</p>
                <p class="mt-1 text-xs text-gray-500">Encrypted backups (cached, updates hourly)</p>
            </div>
        </div>

        <!-- Backups Table -->
        <div class="bg-white rounded-lg shadow">
            {{ $this->table }}
        </div>

        <!-- Help Section -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-sm font-semibold text-blue-900 mb-3">
                <i class="fas fa-question-circle mr-2"></i> How to Use
            </h3>
            <ul class="text-sm text-blue-800 space-y-2">
                <li>
                    <strong>Create Backup:</strong> Click "Create Backup Now" button to manually create an encrypted backup of the entire database.
                </li>
                <li>
                    <strong>Automated Backups:</strong> Backups are automatically created daily at 2 AM, weekly on Sundays, and monthly on the 1st.
                </li>
                <li>
                    <strong>Download:</strong> Click the blue <i class="fas fa-arrow-down"></i> button to download an encrypted backup file (`.zip.enc`).
                </li>
                <li>
                    <strong>Restore:</strong> Click the orange <i class="fas fa-arrow-up-tray"></i> button to restore the database from a backup. The system will enter maintenance mode during the restore.
                </li>
                <li>
                    <strong>Delete:</strong> Click the red <i class="fas fa-trash"></i> button to permanently delete a backup and free up storage.
                </li>
                <li>
                    <strong>Encryption:</strong> All backups are encrypted with AES-256. Only the encrypted `.zip.enc` file is stored on the server.
                </li>
            </ul>
        </div>
    </div>
</x-filament-panels::page>
