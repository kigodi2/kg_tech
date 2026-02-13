<?php

namespace App\Filament\Admin\Resources\GovernanceAuditLogResource\Pages;

use App\Filament\Admin\Resources\GovernanceAuditLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGovernanceAuditLogs extends ListRecords
{
    protected static string $resource = GovernanceAuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Export route not yet implemented
            // Actions\Action::make('export')
            //     ->label('Export Audit Logs')
            //     ->icon('heroicon-o-arrow-down-tray')
            //     ->url(route('audit-logs.export'))
            //     ->openUrlInNewTab(),
        ];
    }
}
