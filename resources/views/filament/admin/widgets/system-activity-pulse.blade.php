<x-filament-widgets::widget>
    <x-filament::section>
        @php
            $summary = $this->getActivitySummary();
            $modules = $this->getModuleBreakdown();
            $activities = $this->getRecentActivities();

            $moduleToneClasses = [
                'blue' => 'bg-blue-50 border-blue-200 text-blue-900 dark:bg-blue-950/30 dark:border-blue-900/60 dark:text-blue-100',
                'emerald' => 'bg-emerald-50 border-emerald-200 text-emerald-900 dark:bg-emerald-950/30 dark:border-emerald-900/60 dark:text-emerald-100',
                'amber' => 'bg-amber-50 border-amber-200 text-amber-900 dark:bg-amber-950/30 dark:border-amber-900/60 dark:text-amber-100',
                'rose' => 'bg-rose-50 border-rose-200 text-rose-900 dark:bg-rose-950/30 dark:border-rose-900/60 dark:text-rose-100',
                'gray' => 'bg-gray-50 border-gray-200 text-gray-900 dark:bg-gray-900/40 dark:border-gray-800 dark:text-gray-100',
            ];

            $activityToneClasses = [
                'danger' => 'bg-danger-50 text-danger-700 border-danger-200 dark:bg-danger-950/30 dark:text-danger-200 dark:border-danger-900/60',
                'success' => 'bg-success-50 text-success-700 border-success-200 dark:bg-success-950/30 dark:text-success-200 dark:border-success-900/60',
                'warning' => 'bg-warning-50 text-warning-700 border-warning-200 dark:bg-warning-950/30 dark:text-warning-200 dark:border-warning-900/60',
                'info' => 'bg-info-50 text-info-700 border-info-200 dark:bg-info-950/30 dark:text-info-200 dark:border-info-900/60',
                'gray' => 'bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-900/40 dark:text-gray-200 dark:border-gray-800',
            ];
        @endphp

        <div class="space-y-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-primary-700 dark:bg-primary-950/30 dark:text-primary-200">
                        <span class="h-2 w-2 rounded-full bg-primary-500"></span>
                        System Activity
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-950 dark:text-white">Operations Pulse</h3>
                        <p class="mt-1 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                            Track live user activity across authentication, imports, user administration, and backup workflows from one admin dashboard card.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm dark:border-gray-800 dark:bg-gray-900/40">
                        <div class="text-gray-500 dark:text-gray-400">Latest activity</div>
                        <div class="font-semibold text-gray-900 dark:text-white">
                            {{ $summary['latest_activity_at'] ? $summary['latest_activity_at']->diffForHumans() : 'No activity yet' }}
                        </div>
                    </div>
                    <a href="/admin/audit-logs" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-primary-500">
                        <x-heroicon-o-shield-check class="h-4 w-4" />
                        <span>View All Activity</span>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-primary-200 bg-primary-50 p-4 dark:border-primary-900/60 dark:bg-primary-950/30">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary-700 dark:text-primary-200">Events In 24h</p>
                    <p class="mt-3 text-3xl font-black text-primary-950 dark:text-white">{{ number_format($summary['events_last_24h']) }}</p>
                    <p class="mt-2 text-sm text-primary-700 dark:text-primary-200">Tracked actions recorded throughout the system.</p>
                </div>

                <div class="rounded-xl border border-success-200 bg-success-50 p-4 dark:border-success-900/60 dark:bg-success-950/30">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-success-700 dark:text-success-200">Active Users In 24h</p>
                    <p class="mt-3 text-3xl font-black text-success-950 dark:text-white">{{ number_format($summary['active_users_last_24h']) }}</p>
                    <p class="mt-2 text-sm text-success-700 dark:text-success-200">Distinct users or admins with recent recorded work.</p>
                </div>

                <div class="rounded-xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-900/60 dark:bg-warning-950/30">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-warning-700 dark:text-warning-200">Busiest Workflow</p>
                    <p class="mt-3 text-xl font-black text-warning-950 dark:text-white">{{ $summary['busiest_workflow'] }}</p>
                    <p class="mt-2 text-sm text-warning-700 dark:text-warning-200">
                        {{ number_format($summary['busiest_workflow_count']) }} event(s) in the last 24 hours.
                    </p>
                </div>

                <div class="rounded-xl border border-info-200 bg-info-50 p-4 dark:border-info-900/60 dark:bg-info-950/30">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-info-700 dark:text-info-200">Today’s Focus</p>
                    <div class="mt-3 flex items-center gap-6">
                        <div>
                            <p class="text-2xl font-black text-info-950 dark:text-white">{{ number_format($summary['today_logins']) }}</p>
                            <p class="text-xs font-medium uppercase tracking-[0.12em] text-info-700 dark:text-info-200">Logins</p>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-info-950 dark:text-white">{{ number_format($summary['today_imports']) }}</p>
                            <p class="text-xs font-medium uppercase tracking-[0.12em] text-info-700 dark:text-info-200">Imports</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.15fr,1.85fr]">
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900/40">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h4 class="text-base font-bold text-gray-950 dark:text-white">Module Breakdown</h4>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Where most user activity is happening in the last 24 hours.</p>
                        </div>
                        <x-heroicon-o-squares-2x2 class="h-5 w-5 text-gray-400" />
                    </div>

                    <div class="mt-4 space-y-3">
                        @foreach($modules as $module)
                            <div class="rounded-xl border p-4 {{ $moduleToneClasses[$module['tone']] ?? $moduleToneClasses['gray'] }}">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold">{{ $module['label'] }}</p>
                                        <p class="mt-1 text-xs opacity-80">Tracked actions from this workflow family in the last 24 hours.</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-black">{{ number_format($module['count']) }}</p>
                                        <p class="text-xs font-semibold uppercase tracking-[0.12em] opacity-80">Events</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900/40">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h4 class="text-base font-bold text-gray-950 dark:text-white">Recent User Activity</h4>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Latest governance-tracked actions across the platform.</p>
                        </div>
                        <x-heroicon-o-clock class="h-5 w-5 text-gray-400" />
                    </div>

                    <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                                <thead class="bg-gray-50 dark:bg-gray-950/60">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">Time</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">Actor</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">Action</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">Details</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-transparent">
                                    @forelse($activities as $activity)
                                        <tr class="align-top">
                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                                <div class="font-medium text-gray-900 dark:text-white">{{ $activity['time']->format('H:i') }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $activity['time']->diffForHumans() }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $activity['actor'] }}</td>
                                            <td class="px-4 py-3 text-sm">
                                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $activityToneClasses[$activity['tone']] ?? $activityToneClasses['gray'] }}">
                                                    {{ $activity['action'] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $activity['summary'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                                No tracked user activity is available yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
