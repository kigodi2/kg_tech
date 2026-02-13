<?php

namespace App\Filament\Admin\Widgets;

use App\Models\GovernanceAuditLog;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentAuditLogsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Recent Activity (Last 24 Hours)';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                GovernanceAuditLog::query()
                    ->where('created_at', '>=', now()->subDay())
                    ->latest('created_at')
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('H:i:s')
                    ->sortable(),

                TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'login_successful' => 'success',
                        'login_failed' => 'danger',
                        'import_completed' => 'success',
                        'import_failed' => 'danger',
                        'import_initiated' => 'info',
                        'user_created' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('user.name')
                    ->label('User')
                    ->default('System'),

                TextColumn::make('data_summary')
                    ->label('Details')
                    ->formatStateUsing(function (GovernanceAuditLog $record): string {
                        return match ($record->action) {
                            'login_successful' => 'Logged in',
                            'login_failed' => 'Login failed: ' . ($record->data['reason'] ?? 'unknown'),
                            'import_completed' => $record->data['records_imported'] . ' records imported',
                            'import_failed' => 'Import failed',
                            'import_initiated' => 'Import initiated',
                            'user_created' => 'User created',
                            default => 'Action recorded',
                        };
                    }),
            ])
            ->paginated(false)
            ->defaultPaginationPageOption(10);
    }
}
