<?php

namespace App\Filament\Admin\Resources;

use App\Models\BulkImport;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry as BadgeEntry;

class BulkImportResource extends Resource
{
    protected static ?string $model = BulkImport::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 5;

    protected static bool $shouldRegisterNavigation = true;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('scope_type')
                    ->label('Scope')
                    ->badge()
                    ->colors([
                        'info' => 'school',
                        'warning' => 'district',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('examYear.year_label')
                    ->label('Exam Year')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'processing',
                        'danger' => 'failed',
                        'secondary' => 'pending',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('processed_files')
                    ->label('Progress')
                    ->formatStateUsing(fn (BulkImport $record) => 
                        $record->total_files > 0 
                            ? "{$record->processed_files}/{$record->total_files} files"
                            : ($record->total_schools > 0 
                                ? "{$record->processed_schools}/{$record->total_schools} schools"
                                : 'N/A')
                    ),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Uploaded By')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('scope_type')
                    ->options([
                        'school' => 'School',
                        'district' => 'District',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100])
            ->persistSearchInSession()
            ->persistFiltersInSession();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Import Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('id')
                            ->label('Import ID'),
                        BadgeEntry::make('scope_type')
                            ->label('Scope')
                            ->formatStateUsing(fn ($state) => ucfirst($state))
                            ->colors([
                                'info' => 'school',
                                'warning' => 'district',
                            ]),
                        BadgeEntry::make('status')
                            ->label('Status')
                            ->colors([
                                'success' => 'completed',
                                'warning' => 'processing',
                                'danger' => 'failed',
                                'secondary' => 'pending',
                            ])
                            ->formatStateUsing(fn ($state) => ucfirst($state)),
                    ]),

                Section::make('Exam Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('examYear.year_label')
                            ->label('Exam Year'),
                        TextEntry::make('createdBy.name')
                            ->label('Uploaded By'),
                    ]),

                Section::make('Progress')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('processed_files')
                            ->label('Files Processed')
                            ->formatStateUsing(fn (BulkImport $record) => 
                                "{$record->processed_files} of {$record->total_files}"
                            ),
                        TextEntry::make('processed_schools')
                            ->label('Schools Processed')
                            ->formatStateUsing(fn (BulkImport $record) => 
                                "{$record->processed_schools} of {$record->total_schools}"
                            ),
                    ]),

                Section::make('Metadata')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('zip_hash')
                            ->label('ZIP Checksum')
                            ->copyable(),
                        TextEntry::make('signature')
                            ->label('Signature')
                            ->copyable(),
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                        TextEntry::make('started_at')
                            ->label('Started At')
                            ->dateTime(),
                        TextEntry::make('completed_at')
                            ->label('Completed At')
                            ->dateTime(),
                    ]),

                Section::make('Error Summary')
                    ->schema([
                        TextEntry::make('error_summary')
                            ->label('Errors')
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state) => $state ? nl2br(e($state)) : 'No errors'),
                    ])
                    ->visible(fn (BulkImport $record) => !empty($record->error_summary)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\BulkImportResource\Pages\ListBulkImports::route('/'),
            'view' => \App\Filament\Admin\Resources\BulkImportResource\Pages\ViewBulkImport::route('/{record}'),
        ];
    }
}
