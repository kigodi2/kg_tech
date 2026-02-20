<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\GovernanceAuditLogResource\Pages;
use App\Models\GovernanceAuditLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GovernanceAuditLogResource extends Resource
{
    protected static ?string $model = GovernanceAuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Audit Logs';

    protected static ?string $modelLabel = 'Audit Log';

    protected static ?int $navigationSort = 50;

    protected static ?string $navigationGroup = 'Security & Compliance';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Audit Details')
                    ->description('Immutable log entry')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('id')
                                    ->label('Log ID')
                                    ->disabled(),

                                Forms\Components\TextInput::make('action')
                                    ->label('Action')
                                    ->disabled(),

                                Forms\Components\TextInput::make('user.name')
                                    ->label('User Affected')
                                    ->disabled(),

                                Forms\Components\TextInput::make('admin.name')
                                    ->label('Admin (if applicable)')
                                    ->disabled(),

                                Forms\Components\TextInput::make('created_at')
                                    ->label('Timestamp')
                                    ->disabled()
                                    ->formatStateUsing(fn($state) => is_string($state) ? $state : $state?->format('Y-m-d H:i:s')),

                                Forms\Components\TextInput::make('user_id')
                                    ->label('User ID')
                                    ->disabled(),
                            ]),

                        Forms\Components\Textarea::make('data_display')
                            ->label('Context Data (JSON)')
                            ->disabled()
                            ->rows(10)
                            ->formatStateUsing(fn($record) => json_encode($record?->data ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->sortable()
                    ->dateTime('Y-m-d H:i:s')
                    ->searchable(),

                TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'login_successful' => 'success',
                        'login_failed' => 'danger',
                        'import_initiated' => 'info',
                        'import_completed' => 'success',
                        'import_failed' => 'danger',
                        'user_created' => 'info',
                        'user_suspended' => 'warning',
                        'user_activated' => 'success',
                        'password_reset' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('admin.name')
                    ->label('Admin')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('data_summary')
                    ->label('Details')
                    ->formatStateUsing(fn($record) => self::summarizeData($record->action, $record->data))
                    ->limit(60)
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Action Type')
                    ->options([
                        'login_successful' => 'Login Successful',
                        'login_failed' => 'Login Failed',
                        'import_initiated' => 'Import Initiated',
                        'import_completed' => 'Import Completed',
                        'import_failed' => 'Import Failed',
                        'user_created' => 'User Created',
                        'user_suspended' => 'User Suspended',
                        'user_activated' => 'User Activated',
                        'password_reset' => 'Password Reset',
                        'password_changed' => 'Password Changed',
                    ])
                    ->multiple(),

                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $q, $date) => $q->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $q, $date) => $q->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if ($data['created_from'] ?? null) {
                            $from = $data['created_from']->format('Y-m-d');
                            $until = $data['created_until']?->format('Y-m-d') ?? 'now';
                            return "From {$from} to {$until}";
                        }
                        return null;
                    }),

                SelectFilter::make('user_id')
                    ->label('Affected User')
                    ->relationship('user', 'name')
                    ->preload()
                    ->searchable(),

                SelectFilter::make('admin_id')
                    ->label('Admin User')
                    ->relationship('admin', 'name')
                    ->preload()
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for audit logs (immutable)
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100])
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGovernanceAuditLogs::route('/'),
            'view' => Pages\ViewGovernanceAuditLog::route('/{record}'),
        ];
    }

    /**
     * Generate a human-readable summary of log data
     */
    private static function summarizeData(string $action, ?array $data): string
    {
        if (!$data) {
            return '—';
        }

        return match ($action) {
            'login_successful' => sprintf(
                'IP: %s',
                $data['ip_address'] ?? 'unknown'
            ),
            'login_failed' => sprintf(
                'Reason: %s',
                ucfirst(str_replace('_', ' ', $data['reason'] ?? 'unknown'))
            ),
            'import_initiated' => sprintf(
                'Import #%s (School %s)',
                $data['import_id'] ?? '?',
                $data['school_id'] ?? '?'
            ),
            'import_completed' => sprintf(
                '%d records (%d valid, %d errors)',
                $data['records_imported'] ?? 0,
                $data['valid_records'] ?? 0,
                $data['error_records'] ?? 0
            ),
            'import_failed' => sprintf(
                'Error: %s',
                substr($data['error'] ?? 'unknown', 0, 40)
            ),
            'user_created' => sprintf(
                'Role: %s',
                $data['role_code'] ?? 'unknown'
            ),
            'password_reset' => sprintf(
                'By admin'
            ),
            default => json_encode($data, JSON_UNESCAPED_SLASHES),
        };
    }
}
