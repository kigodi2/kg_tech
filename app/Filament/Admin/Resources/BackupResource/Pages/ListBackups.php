<?php

namespace App\Filament\Admin\Resources\BackupResource\Pages;

use App\Filament\Admin\Resources\BackupResource;
use App\Models\ExamYear;
use App\Services\BackupService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListBackups extends ListRecords
{
    protected static string $resource = BackupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('createBackup')
                ->label('Create Backup')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->form([
                    Forms\Components\Select::make('type')
                        ->label('Backup Type')
                        ->options([
                            'full_system' => 'Full System (entire database)',
                            'exam_year' => 'Exam Year (default)',
                            'metadata_only' => 'Metadata Only (users, roles, settings)',
                        ])
                        ->default('exam_year')
                        ->required()
                        ->reactive(),

                    Forms\Components\Select::make('exam_year_id')
                        ->label('Exam Year')
                        ->options(ExamYear::all()->pluck('year_label', 'id'))
                        ->visible(fn(Forms\Get $get) => $get('type') === 'exam_year')
                        ->required(fn(Forms\Get $get) => $get('type') === 'exam_year'),

                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->placeholder('Optional: Add notes about why this backup is being created')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    try {
                        $backupService = app(BackupService::class);
                        $examYearId = isset($data['exam_year_id']) ? $data['exam_year_id'] : null;
                        $backup = $backupService->createBackup(
                            admin: auth()->user(),
                            type: $data['type'],
                            examYear: $examYearId ? ExamYear::find($examYearId) : null,
                            notes: $data['notes'] ?? null
                        );

                        Notification::make()
                            ->success()
                            ->title('Backup Created Successfully')
                            ->body("Backup: {$backup->filename}\nSize: {$backup->getSizeFormatted()}")
                            ->send();

                        $this->dispatch('refreshTable');
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Backup Failed')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
