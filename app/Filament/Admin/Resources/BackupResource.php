<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BackupResource\Pages;
use App\Models\Backup;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class BackupResource extends Resource
{
    protected static ?string $model = Backup::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationLabel = 'Backups';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $modelLabel = 'Backup';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Backup metadata is read-only
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('filename')
                    ->label('Filename')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('type')
                    ->label('Type')
                    ->color(fn(string $state): string => match ($state) {
                        'full_system' => 'danger',
                        'exam_year' => 'success',
                        'metadata_only' => 'warning',
                    })
                    ->sortable(),

                TextColumn::make('examYear.year_label')
                    ->label('Exam Year')
                    ->sortable(),

                TextColumn::make('size_bytes')
                    ->label('Size')
                    ->formatStateUsing(fn(Backup $record) => $record->getSizeFormatted())
                    ->sortable(),

                BadgeColumn::make('verified')
                    ->label('Status')
                    ->formatStateUsing(fn(Backup $record) => $record->getStatusLabel())
                    ->color(fn(Backup $record) => $record->getStatusBadge())
                    ->sortable(),

                TextColumn::make('admin.name')
                    ->label('Created By')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'full_system' => 'Full System',
                        'exam_year' => 'Exam Year',
                        'metadata_only' => 'Metadata Only',
                    ]),
                Tables\Filters\SelectFilter::make('verified')
                    ->options([
                        '1' => 'Verified',
                        '0' => 'Unverified',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-arrow-down')
                    ->action(fn(Backup $record) => static::downloadBackup($record))
                    ->requiresConfirmation(),
                Tables\Actions\DeleteAction::make()
                    ->action(function (Backup $record) {
                        static::deleteBackup($record);
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBackups::route('/'),
            'view' => Pages\ViewBackup::route('/{record}'),
            'restore' => Pages\RestoreBackup::route('/{record}/restore'),
        ];
    }

    public static function downloadBackup(Backup $backup)
    {
        if (!$backup->exists()) {
            throw new \Exception('Backup file not found');
        }

        return response()->download($backup->getFullPath(), $backup->filename);
    }

    public static function deleteBackup(Backup $backup)
    {
        try {
            $admin = auth()->user();
            
            // Delete the physical file if it exists
            if ($backup->exists()) {
                unlink($backup->getFullPath());
            }

            // Log the deletion
            \App\Models\GovernanceAuditLog::log(
                'backup_deleted',
                userId: $admin->id,
                adminId: $admin->id,
                data: [
                    'backup_id' => $backup->id,
                    'filename' => $backup->filename,
                    'type' => $backup->type,
                    'size_bytes' => $backup->size_bytes,
                ]
            );

            // Soft delete the record
            $backup->delete();

            \Filament\Notifications\Notification::make()
                ->title('Backup Deleted')
                ->body('The backup has been deleted successfully.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Delete Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
