<?php

namespace App\Filament\Admin\Resources\BackupResource\Pages;

use App\Filament\Admin\Resources\BackupResource;
use App\Models\ExamYear;
use App\Services\BackupService;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBackup extends CreateRecord
{
    protected static string $resource = BackupResource::class;

    protected static bool $canCreateAnother = false;

    protected BackupService $backupService;

    public function mount(): void
    {
        parent::mount();
        $this->backupService = app(BackupService::class);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Backup Configuration')
                    ->description('Configure what to backup')
                    ->schema([
                        Select::make('type')
                            ->label('Backup Type')
                            ->options([
                                'full_system' => 'Full System (entire database)',
                                'exam_year' => 'Exam Year (default)',
                                'metadata_only' => 'Metadata Only (users, roles, settings)',
                            ])
                            ->default('exam_year')
                            ->required()
                            ->live(),

                        Select::make('exam_year_id')
                            ->label('Exam Year')
                            ->options(ExamYear::all()->pluck('year_label', 'id'))
                            ->visible(fn($get) => $get('type') === 'exam_year')
                            ->required(fn($get) => $get('type') === 'exam_year'),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->placeholder('Optional: Add notes about why this backup is being created')
                            ->rows(3),
                    ]),

                Section::make('Pre-Backup Checklist')
                    ->description('Please review before creating backup')
                    ->schema([
                        \Filament\Forms\Components\Checkbox::make('confirm_no_locks')
                            ->label('I understand this backup will capture current state')
                            ->required(),

                        \Filament\Forms\Components\Checkbox::make('confirm_storage')
                            ->label('I confirm sufficient storage space is available')
                            ->required(),

                        \Filament\Forms\Components\Checkbox::make('confirm_purpose')
                            ->label('I understand the purpose and scope of this backup')
                            ->required(),
                    ]),
            ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            $backup = $this->backupService->createBackup(
                admin: auth()->user(),
                type: $data['type'],
                examYear: $data['exam_year_id'] ? ExamYear::find($data['exam_year_id']) : null,
                notes: $data['notes'] ?? null
            );

            $this->record = $backup;

            Notification::make()
                ->success()
                ->title('Backup Created Successfully')
                ->body("Backup file: {$backup->filename}\nSize: {$backup->getSizeFormatted()}\nChecksum: " . substr($backup->checksum, 0, 16) . "...")
                ->duration(10)
                ->send();

            return $backup;
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Backup Failed')
                ->body($e->getMessage())
                ->send();

            throw $e;
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
