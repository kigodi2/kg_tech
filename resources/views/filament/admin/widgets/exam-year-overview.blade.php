<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Exam Years</h3>
                <a href="/admin/exam-years" class="text-sm font-medium text-primary-600 hover:text-primary-700">
                    View All →
                </a>
            </div>

            @if($this->getActiveYear())
                <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-4 border border-green-200 dark:border-green-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-green-800 dark:text-green-200">Active Year</p>
                            <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ $this->getActiveYear()->year_label }}</p>
                        </div>
                        <div class="text-green-500">
                            <x-heroicon-o-check-circle class="w-8 h-8" />
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-lg bg-yellow-50 dark:bg-yellow-900/20 p-4 border border-yellow-200 dark:border-yellow-800">
                    <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">No active exam year</p>
                </div>
            @endif

            <div class="space-y-2">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Recent Years</p>
                <div class="space-y-1">
                    @forelse($this->getExamYears() as $year)
                        <div class="flex items-center justify-between py-2 px-3 rounded-md hover:bg-gray-50 dark:hover:bg-gray-800">
                            <div class="flex items-center gap-2">
                                @if($year->is_locked)
                                    <x-heroicon-o-lock-closed class="w-4 h-4 text-red-500" />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $year->year_label }}</span>
                                    <span class="text-xs bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 px-2 py-0.5 rounded">Locked</span>
                                @elseif($year->is_active)
                                    <x-heroicon-o-check class="w-4 h-4 text-green-500" />
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $year->year_label }}</span>
                                    <span class="text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 px-2 py-0.5 rounded">Active</span>
                                @else
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $year->year_label }}</span>
                                    <span class="text-xs bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-2 py-0.5 rounded">Inactive</span>
                                @endif
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $year->created_at->format('M d, Y') }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400 py-2">No exam years yet</p>
                    @endforelse
                </div>
            </div>

            <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-600 dark:text-gray-400">
                    {{ $this->getLockedCount() }} year(s) locked
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
