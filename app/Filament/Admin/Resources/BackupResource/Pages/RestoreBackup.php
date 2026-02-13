<?php

namespace App\Filament\Admin\Resources\BackupResource\Pages;

use App\Filament\Admin\Resources\BackupResource;
use App\Services\BackupService;
use App\Services\RestoreService;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class RestoreBackup extends ViewRecord
{
    protected static string $resource = BackupResource::class;

    protected static string $view = 'filament.admin.resources.backup-resource.pages.restore-backup';

    public $confirmation = '';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('restore')
                ->label('Execute Restore')
                ->icon('heroicon-o-arrow-up')
                ->color('danger')
                ->action(fn() => $this->restoreBackupAction()),
        ];
    }

    public function restoreBackupAction(): void
    {
        if ($this->confirmation !== 'RESTORE') {
            Notification::make()
                ->title('Restore Failed')
                ->body('You must type "RESTORE" exactly to proceed.')
                ->danger()
                ->send();
            return;
        }

        $this->restoreBackup();
    }

    private function restoreBackup(): void
    {
        try {
            $backup = $this->record;
            $admin = auth()->user();

            if (!$admin || !$admin->isAdmin()) {
                throw new \Exception('Only administrators can restore backups');
            }

            $backupService = new BackupService();
            $service = new RestoreService($backupService);

            // Verify backup integrity
            if (!$service->verifyIntegrity($backup)) {
                Notification::make()
                    ->title('Restore Failed')
                    ->body('Backup verification failed. The backup may be corrupted.')
                    ->danger()
                    ->send();
                return;
            }

            // Execute restore
            $service->restore($backup, $admin);

            Notification::make()
                ->title('Restore Completed Successfully')
                ->body('The system has been restored from the backup.')
                ->success()
                ->send();

            // Redirect to backups list
            $this->redirect(BackupResource::getUrl('index'));
        } catch (\Exception $e) {
            Notification::make()
                ->title('Restore Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
