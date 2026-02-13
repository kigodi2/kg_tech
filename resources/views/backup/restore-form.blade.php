@extends('layout')

@section('content')
<div class="w-full max-w-2xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('filament.admin.pages.manage-backups') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-flex items-center gap-1">
            <i class="fas fa-arrow-left"></i> Back to Backups
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Restore from Backup</h1>
        <p class="mt-2 text-gray-600">Restore your database from a backup point</p>
    </div>

    <!-- Flash Messages -->
    @if($message = session('success'))
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            {{ $message }}
        </div>
    @endif

    @if($message = session('error'))
        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            {{ $message }}
        </div>
    @endif

    <!-- Main Card -->
    <div class="bg-white rounded-lg shadow-lg p-8">
        <!-- Backup Info -->
        <div class="mb-8 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Backup Details</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Backup ID</p>
                    <p class="text-sm font-mono text-gray-900">{{ $backupId }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Size</p>
                    @php
                        $bytes = $backup->data['archive_size'] ?? 0;
                        if ($bytes >= 1073741824) {
                            $size = number_format($bytes / 1073741824, 2) . ' GB';
                        } elseif ($bytes >= 1048576) {
                            $size = number_format($bytes / 1048576, 2) . ' MB';
                        } elseif ($bytes >= 1024) {
                            $size = number_format($bytes / 1024, 2) . ' KB';
                        } else {
                            $size = $bytes . ' B';
                        }
                    @endphp
                    <p class="text-sm font-semibold text-gray-900">{{ $size }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Created</p>
                    <p class="text-sm text-gray-900">{{ $backup->created_at->format('M d, Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Status</p>
                    <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full {{ $backup->status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ ucfirst($backup->status) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Warning Alert -->
        <div class="mb-8 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">
                        ⚠️ Critical Warning
                    </h3>
                    <ul class="mt-2 text-sm text-yellow-700 space-y-1 list-disc list-inside">
                        <li>This action will <strong>permanently overwrite</strong> your current database</li>
                        <li>Your current database will be saved to the quarantine folder</li>
                        <li>The system will enter maintenance mode during the restore</li>
                        <li>This action <strong>CANNOT be undone</strong></li>
                        <li>Estimated restore time: 1-5 minutes depending on database size</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Confirmation Form -->
        <form method="POST" action="{{ route('backup.execute-restore', ['id' => $backup->id]) }}" class="space-y-6">
            @csrf
            @method('POST')

            <!-- Confirmation Input -->
            <div>
                <label for="confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                    To proceed, type <code class="bg-gray-200 px-2 py-1 rounded font-mono">RESTORE</code> below
                </label>
                <input
                    type="text"
                    id="confirmation"
                    name="confirmation"
                    placeholder="Type RESTORE to confirm"
                    maxlength="10"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 font-mono uppercase"
                    required
                >
                @error('confirmation')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Checkbox Agreement -->
            <div class="flex items-start gap-3">
                <input
                    type="checkbox"
                    id="agree"
                    required
                    class="mt-1 w-4 h-4 border-gray-300 rounded focus:ring-orange-500"
                >
                <label for="agree" class="text-sm text-gray-700">
                    I understand this will overwrite my current database and this cannot be undone
                </label>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3 pt-4 border-t">
                <a
                    href="{{ route('filament.admin.pages.manage-backups') }}"
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition text-center"
                >
                    Cancel
                </a>
                <button
                    type="submit"
                    class="flex-1 px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition"
                    onclick="return confirm('Are you absolutely sure? This cannot be undone.')"
                >
                    Yes, Restore Backup
                </button>
            </div>
        </form>

        <!-- Rollback Info -->
        <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded">
            <h3 class="text-sm font-semibold text-blue-900 mb-2">
                <i class="fas fa-info-circle mr-2"></i> What Happens During Restore
            </h3>
            <ol class="text-sm text-blue-800 space-y-1 list-decimal list-inside">
                <li>A snapshot of your current database is created (for rollback)</li>
                <li>System enters maintenance mode</li>
                <li>Backup is decrypted and validated</li>
                <li>Database files are replaced atomically</li>
                <li>System returns to normal</li>
                <li>You'll see a success message</li>
            </ol>
        </div>
    </div>
</div>

<style>
    code {
        background-color: #f3f4f6;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-family: 'Courier New', monospace;
    }
</style>
@endsection
