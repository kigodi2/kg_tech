<?php

namespace App\Filament\Admin\Pages;

use App\Models\BackupLog;
use App\Services\SQLiteBackupService;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Columns\Column;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Redirect;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action as TableAction;

class ManageBackups extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Backups & Restore';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.admin.pages.manage-backups';

    public function mount(): void
    {
        // Display flash messages as Filament notifications
        if ($message = session('success')) {
            Notification::make()
                ->success()
                ->title('Backup Restored Successfully')
                ->body($message)
                ->send();
        }

        if ($message = session('error')) {
            Notification::make()
                ->danger()
                ->title('Restore Failed')
                ->body($message)
                ->send();
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(BackupLog::where('operation', 'like', '%backup%')->orderByDesc('created_at'))
            ->columns([
                TextColumn::make('data.backup_id')
                    ->label('Backup ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('font-mono'),

                TextColumn::make('operation')
                    ->label('Type')
                    ->formatStateUsing(fn(string $state) => str_contains($state, 'incremental') ? 'Incremental' : 'Full')
                    ->badge()
                    ->color(fn(string $state) => str_contains($state, 'incremental') ? 'info' : 'success'),

                TextColumn::make('data.archive_size')
                    ->label('Size')
                    ->formatStateUsing(function ($state) {
                        $bytes = $state ?? 0;
                        if ($bytes >= 1073741824) {
                            return number_format($bytes / 1073741824, 2) . ' GB';
                        } elseif ($bytes >= 1048576) {
                            return number_format($bytes / 1048576, 2) . ' MB';
                        } elseif ($bytes >= 1024) {
                            return number_format($bytes / 1024, 2) . ' KB';
                        }
                        return $bytes . ' B';
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    }),

                TextColumn::make('user.name')
                    ->label('Created By')
                    ->default('System'),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->actions([
                TableAction::make('restore')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Restore from Backup')
                    ->modalDescription('This will restore your database from this backup. This action cannot be undone. Your current database will be saved as a backup.')
                    ->modalSubmitActionLabel('Proceed with Restore')
                    ->action(fn(BackupLog $record) => Redirect::route('backup.restore-form', ['id' => $record->id])),

                TableAction::make('download')
                    ->icon('heroicon-o-arrow-down')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(function (BackupLog $record) {
                        try {
                            $backupId = $record->data['backup_id'] ?? null;
                            if (!$backupId) {
                                throw new \Exception('Backup ID not found');
                            }

                            $filePath = storage_path('backups/sqlite/' . $backupId . '.zip.enc');
                            if (!file_exists($filePath)) {
                                throw new \Exception('Backup file not found: ' . $filePath);
                            }

                            return response()->download($filePath, $backupId . '.zip.enc');
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Download Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                TableAction::make('delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (BackupLog $record) {
                        try {
                            // Delete physical file if exists
                            $backupId = $record->data['backup_id'] ?? null;
                            if ($backupId) {
                                $filePath = storage_path('backups/sqlite/' . $backupId . '.zip.enc');
                                if (file_exists($filePath)) {
                                    unlink($filePath);
                                }
                            }
                            
                            $record->delete();

                            Notification::make()
                                ->title('Backup Deleted')
                                ->body('The backup has been deleted successfully.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Delete Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create Backup Now')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->action(fn() => $this->createBackup()),
        ];
    }

    public function createBackup(): void
    {
        try {
            $service = app(SQLiteBackupService::class);
            $result = $service->createFullBackup(auth()->user());

            if ($result['success']) {
                Notification::make()
                    ->title('Backup Created')
                    ->body('Backup ' . $result['backup_id'] . ' created successfully.')
                    ->success()
                    ->send();

                $this->table()->resetPage();
            } else {
                Notification::make()
                    ->title('Backup Failed')
                    ->body($result['error'] ?? 'Unknown error')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
