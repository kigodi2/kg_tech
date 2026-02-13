<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Restore Warning Section -->
        <div class="rounded-lg border border-gray-300 bg-white p-6">
            <h3 class="text-lg font-semibold text-gray-900">Restore Warning</h3>
            <p class="mt-2 text-sm text-gray-600">This operation will restore the system from the selected backup. This action cannot be undone.</p>
            
            <div class="mt-6">
                <label for="confirmation" class="block text-sm font-medium text-gray-900">
                    Type "RESTORE" to confirm
                </label>
                <input 
                    type="text" 
                    id="confirmation"
                    wire:model.live="confirmation"
                    placeholder="RESTORE"
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 placeholder-gray-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
            </div>
        </div>

        <!-- Backup Details Section -->
        <div class="rounded-lg border border-gray-300 bg-white p-6">
            <h3 class="text-lg font-semibold text-gray-900">Backup Details</h3>
            
            <div class="mt-6 grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-900">Filename</label>
                    <p class="mt-2 text-sm text-gray-700">{{ $this->record->filename }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-900">Backup Type</label>
                    <p class="mt-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $this->record->type)) }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-900">Exam Year</label>
                    <p class="mt-2 text-sm text-gray-700">{{ $this->record->examYear?->year_label ?? '-' }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-900">Created At</label>
                    <p class="mt-2 text-sm text-gray-700">{{ $this->record->created_at?->format('Y-m-d H:i:s') }}</p>
                </div>
            </div>
        </div>
    </div>


</x-filament-panels::page>
