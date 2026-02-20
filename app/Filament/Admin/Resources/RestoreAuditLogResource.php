<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RestoreAuditLogResource\Pages;
use App\Models\RestoreAuditLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RestoreAuditLogResource extends Resource
{
    protected static ?string $model = RestoreAuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Restore Audit Logs';

    protected static ?string $modelLabel = 'Restore Audit Log';

    protected static ?int $navigationSort = 51;

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
                Forms\Components\Section::make('Restore Details')
                    ->description('Immutable restore audit log entry')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('id')
                                    ->label('Log ID')
                                    ->disabled(),

                                Forms\Components\TextInput::make('status')
                                    ->label('Status')
                                    ->disabled(),

                                Forms\Components\TextInput::make('user.name')
                                    ->label('Initiated By')
                                    ->disabled(),

                                Forms\Components\TextInput::make('scope_type')
                                    ->label('Scope')
                                    ->disabled(),

                                Forms\Components\TextInput::make('backup_filename')
                                    ->label('Backup Filename')
                                    ->disabled(),

                                Forms\Components\TextInput::make('backup_hash')
                                    ->label('Backup Hash')
                                    ->disabled(),

                                Forms\Components\TextInput::make('ip_address')
                                    ->label('IP Address')
                                    ->disabled(),

                                Forms\Components\TextInput::make('created_at')
                                    ->label('Timestamp')
                                    ->disabled()
                                    ->formatStateUsing(fn($state) => is_string($state) ? $state : $state?->format('Y-m-d H:i:s')),
                            ]),

                        Forms\Components\Textarea::make('restore_reason')
                            ->label('Restore Reason')
                            ->disabled()
                            ->rows(4),

                        Forms\Components\Textarea::make('error_message')
                            ->label('Error Message')
                            ->disabled()
                            ->rows(4)
                            ->visible(fn($record) => !empty($record?->error_message)),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Action')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'completed' => 'success',
                        'failed', 'rolled_back' => 'danger',
                        'in_progress' => 'warning',
                        'confirmed', 'initiated' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('scope_type')
                    ->label('Restore Details')
                    ->formatStateUsing(fn($record) => $record->getScopeLabel())
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->sortable()
                    ->dateTime('Y-m-d H:i:s'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'initiated' => 'Initiated',
                        'confirmed' => 'Confirmed',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'rolled_back' => 'Rolled Back',
                    ])
                    ->multiple(),

                SelectFilter::make('scope_type')
                    ->label('Scope')
                    ->options([
                        'full' => 'Full System',
                        'region' => 'Regional',
                        'district' => 'District',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for audit logs (immutable)
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRestoreAuditLogs::route('/'),
            'view' => Pages\ViewRestoreAuditLog::route('/{record}'),
        ];
    }
}
