<?php

namespace App\Filament\Admin\Widgets;

use App\Models\GovernanceAuditLog;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SecurityAlertsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $todayFailedLogins = GovernanceAuditLog::query()
            ->where('action', 'login_failed')
            ->whereDate('created_at', today())
            ->count();

        $todayImports = GovernanceAuditLog::query()
            ->where('action', 'import_completed')
            ->whereDate('created_at', today())
            ->count();

        $suspendedUsers = User::where('status', 'suspended')->count();

        $recentFailedImports = GovernanceAuditLog::query()
            ->where('action', 'import_failed')
            ->whereDate('created_at', today())
            ->count();

        return [
            Stat::make('Failed Logins (Today)', $todayFailedLogins)
                ->description('Authentication failures')
                ->icon('heroicon-o-lock-closed')
                ->color($todayFailedLogins > 5 ? 'danger' : 'info'),

            Stat::make('Successful Imports (Today)', $todayImports)
                ->description('Mark imports completed')
                ->icon('heroicon-o-document-check')
                ->color('success'),

            Stat::make('Failed Imports (Today)', $recentFailedImports)
                ->description('Import errors')
                ->icon('heroicon-o-exclamation-circle')
                ->color($recentFailedImports > 0 ? 'warning' : 'gray'),

            Stat::make('Suspended Users', $suspendedUsers)
                ->description('Locked accounts')
                ->icon('heroicon-o-stop-circle')
                ->color($suspendedUsers > 0 ? 'danger' : 'gray'),
        ];
    }
}
