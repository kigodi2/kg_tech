<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Bulk Imports</h3>
                <a href="/admin/bulk-imports" class="text-sm font-medium text-primary-600 hover:text-primary-700">
                    View All →
                </a>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 gap-3">
                @php
                    $stats = $this->getImportStats();
                @endphp

                <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-3 border border-blue-200 dark:border-blue-800">
                    <p class="text-xs font-medium text-blue-700 dark:text-blue-300">Pending</p>
                    <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ $stats['pending'] }}</p>
                </div>

                <div class="rounded-lg bg-yellow-50 dark:bg-yellow-900/20 p-3 border border-yellow-200 dark:border-yellow-800">
                    <p class="text-xs font-medium text-yellow-700 dark:text-yellow-300">Processing</p>
                    <p class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">{{ $stats['processing'] }}</p>
                </div>

                <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-3 border border-green-200 dark:border-green-800">
                    <p class="text-xs font-medium text-green-700 dark:text-green-300">Completed</p>
                    <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ $stats['completed'] }}</p>
                </div>

                <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-3 border border-red-200 dark:border-red-800">
                    <p class="text-xs font-medium text-red-700 dark:text-red-300">Failed</p>
                    <p class="text-2xl font-bold text-red-900 dark:text-red-100">{{ $stats['failed'] }}</p>
                </div>
            </div>

            <!-- Recent Imports -->
            <div class="space-y-2">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Recent Imports</p>
                <div class="space-y-1 max-h-48 overflow-y-auto">
                    @forelse($this->getRecentImports() as $import)
                        <div class="flex items-center justify-between py-2 px-3 rounded-md hover:bg-gray-50 dark:hover:bg-gray-800 text-sm">
                            <div class="flex items-center gap-2 flex-1 min-w-0">
                                @switch($import->status)
                                    @case('completed')
                                        <x-heroicon-o-check-circle class="w-4 h-4 text-green-500 flex-shrink-0" />
                                        @break
                                    @case('failed')
                                        <x-heroicon-o-x-circle class="w-4 h-4 text-red-500 flex-shrink-0" />
                                        @break
                                    @case('processing')
                                        <x-heroicon-o-arrow-path class="w-4 h-4 text-yellow-500 animate-spin flex-shrink-0" />
                                        @break
                                    @default
                                        <x-heroicon-o-clock class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                @endswitch
                                <div class="min-w-0">
                                    <p class="text-gray-700 dark:text-gray-300 truncate">
                                        {{ $import->examYear?->year_label ?? 'N/A' }} 
                                        ({{ ucfirst($import->scope_type) }})
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        by {{ $import->createdBy?->name ?? 'Unknown' }}
                                    </p>
                                </div>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-2 whitespace-nowrap">
                                {{ $import->created_at->format('M d') }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400 py-2">No recent imports</p>
                    @endforelse
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
