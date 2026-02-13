<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Audit Logs</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                View authentication and system audit logs. Logs are append-only and cannot be modified.
            </p>
        </div>

        @php
            $tableExists = \Illuminate\Support\Facades\Schema::hasTable('authentication_audit_logs');
        @endphp

        @if(!$tableExists)
            <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-4 border border-blue-200 dark:border-blue-800">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">No Audit Logs Yet</h3>
                <p class="text-sm text-blue-700 dark:text-blue-300">
                    The authentication audit log table will be created when you run the database migrations. 
                    Once created, all user authentication events will be logged here automatically.
                </p>
            </div>
        @else
            {{ $this->table }}
        @endif
    </div>
</x-filament-panels::page>
