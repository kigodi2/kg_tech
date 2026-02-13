<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">System Settings</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Configure system-wide settings for imports, caching, and maintenance.
            </p>
        </div>

        <form wire:submit="saveSettings" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end gap-3">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg border border-transparent bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                >
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
