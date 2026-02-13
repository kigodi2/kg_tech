<?php

namespace App\Filament\Resources;

use App\Models\RestoreAuditLog;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Infolist\Infolist;
use Filament\Infolist\Components\Section;
use Filament\Infolist\Components\TextEntry;
use Filament\Infolist\Components\BadgeEntry;
use Filament\Pages\Page;

class RestoreAuditLogResource extends Resource
{
    protected static ?string $model = RestoreAuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'System Administration';
    protected static ?int $navigationSort = 50;
    protected static ?string $label = 'Restore Audit Log';
    protected static ?string $pluralLabel = 'Restore Audit Logs';

    public static function canCreate(): bool
    {
        return false; // Audit logs created by system only
    }

    public static function canEdit(Page $livewire): bool
    {
        return false; // Audit logs immutable
    }

    public static function canDelete(Page $livewire): bool
    {
        return false; // Audit logs immutable
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('initiated_at')
                    ->label('Date & Time')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('Operator')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('user.role.name')
                    ->label('Role')
                    ->badge()
                    ->sortable(),

                TextColumn::make('scope_type')
                    ->label('Scope')
                    ->badge()
                    ->color(function (string $state): string {
                        return match($state) {
                            'full' => 'danger',
                            'region' => 'warning',
                            'district' => 'info',
                            default => 'secondary',
                        };
                    })
                    ->formatStateUsing(function (string $state): string {
                        return match($state) {
                            'full' => 'Full System',
                            'region' => 'Regional',
                            'district' => 'District',
                            default => $state,
                        };
                    }),

                TextColumn::make('backup_filename')
                    ->label('Backup Restored')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(function (string $state): string {
                        return match($state) {
                            'completed' => 'success',
                            'failed' => 'danger',
                            'rolled_back' => 'danger',
                            'in_progress' => 'warning',
                            'confirmed', 'initiated' => 'info',
                            default => 'secondary',
                        };
                    })
                    ->formatStateUsing(function (string $state): string {
                        return ucfirst(str_replace('_', ' ', $state));
                    }),

                TextColumn::make('legal_acknowledged')
                    ->label('Legal')
                    ->badge()
                    ->formatStateUsing(function (bool $state): string {
                        return $state ? 'Confirmed' : 'Not Confirmed';
                    })
                    ->color(function (bool $state): string {
                        return $state ? 'success' : 'danger';
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'initiated' => 'Initiated',
                        'confirmed' => 'Confirmed',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'rolled_back' => 'Rolled Back',
                    ]),

                SelectFilter::make('scope_type')
                    ->options([
                        'full' => 'Full System',
                        'region' => 'Regional',
                        'district' => 'District',
                    ]),

                SelectFilter::make('legal_acknowledged')
                    ->options([
                        '1' => 'Confirmed',
                        '0' => 'Not Confirmed',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Restore Operation')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('initiated_at')
                            ->label('Initiated')
                            ->dateTime('Y-m-d H:i:s'),
                        TextEntry::make('executed_at')
                            ->label('Executed')
                            ->dateTime('Y-m-d H:i:s')
                            ->placeholder('Not yet executed'),
                        TextEntry::make('completed_at')
                            ->label('Completed')
                            ->dateTime('Y-m-d H:i:s')
                            ->placeholder('Not yet completed'),
                    ]),

                Section::make('Operator Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Operator'),
                        TextEntry::make('user.email')
                            ->label('Email'),
                        TextEntry::make('user.role.name')
                            ->label('Role')
                            ->badge(),
                        TextEntry::make('ip_address')
                            ->label('IP Address'),
                    ]),

                Section::make('Authorization')
                    ->columns(2)
                    ->schema([
                        BadgeEntry::make('status')
                            ->color(function (string $state): string {
                                return match($state) {
                                    'completed' => 'success',
                                    'failed' => 'danger',
                                    'rolled_back' => 'danger',
                                    'in_progress' => 'warning',
                                    'confirmed', 'initiated' => 'info',
                                    default => 'secondary',
                                };
                            })
                            ->formatStateUsing(function (string $state): string {
                                return ucfirst(str_replace('_', ' ', $state));
                            }),
                        TextEntry::make('authorizedBy.name')
                            ->label('Authorized By')
                            ->placeholder('Self-Authorized'),
                        BadgeEntry::make('legal_acknowledged')
                            ->label('Legal Acknowledgment')
                            ->formatStateUsing(function (bool $state): string {
                                return $state ? 'Confirmed' : 'Not Confirmed';
                            })
                            ->color(function (bool $state): string {
                                return $state ? 'success' : 'danger';
                            }),
                        TextEntry::make('scope_type')
                            ->label('Scope')
                            ->badge(),
                    ]),

                Section::make('Backup Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('backup_filename')
                            ->label('Filename'),
                        TextEntry::make('backup_hash')
                            ->label('Archive Hash')
                            ->copyable()
                            ->copyMessage('Hash copied')
                            ->copyableState(fn ($state) => $state),
                        TextEntry::make('restore_reason')
                            ->columnSpan(2)
                            ->label('Reason for Restore'),
                    ]),

                Section::make('Legal Acknowledgment')
                    ->schema([
                        TextEntry::make('legal_acknowledgment')
                            ->formatStateUsing(fn ($state) => $state),
                    ]),

                Section::make('Error Information')
                    ->schema([
                        TextEntry::make('error_message')
                            ->label('Error')
                            ->placeholder('No errors'),
                    ])
                    ->visible(fn (RestoreAuditLog $record) => !empty($record->error_message)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \Filament\Resources\Pages\ListRecords::class,
            'view' => \Filament\Resources\Pages\ViewRecord::class,
        ];
    }

    public static function getEloquentQuery(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        $user = auth()->user();

        // Super admin sees all
        if ($user->role?->code === 'super_admin') {
            return $query;
        }

        // Regional admin sees only their region's restores
        if ($user->role?->code === 'regional_admin') {
            return $query->where('region_id', $user->getRegionId());
        }

        // District admin sees only their district's restores
        if ($user->role?->code === 'district_admin') {
            return $query->where('district_id', $user->getDistrictId());
        }

        // Others see nothing
        return $query->whereRaw('1=0');
    }
}
