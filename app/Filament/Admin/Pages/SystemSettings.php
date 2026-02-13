<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use App\Models\SystemSetting;

class SystemSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 7;

    protected static string $view = 'filament.admin.pages.system-settings';

    protected static ?string $title = 'System Settings';

    public $importChunkSize = 1000;
    public $maxZipSize = 104857600; // 100MB
    public $cacheTtl = 3600;
    public $maintenanceMode = false;
    public $systemNotes = '';

    public function mount(): void
    {
        // Load from database, fallback to config
        $this->importChunkSize = SystemSetting::getSetting('import_chunk_size', config('irms.import_chunk_size', 1000));
        $this->maxZipSize = SystemSetting::getSetting('max_zip_size', config('irms.max_zip_size', 104857600));
        $this->cacheTtl = SystemSetting::getSetting('cache_ttl', config('irms.cache_ttl', 3600));
        $this->maintenanceMode = SystemSetting::getSetting('maintenance_mode', config('app.maintenance_mode', false));
        $this->systemNotes = SystemSetting::getSetting('system_notes', config('irms.system_notes', ''));

        $this->form->fill([
            'importChunkSize' => $this->importChunkSize,
            'maxZipSize' => $this->maxZipSize,
            'cacheTtl' => $this->cacheTtl,
            'maintenanceMode' => $this->maintenanceMode,
            'systemNotes' => $this->systemNotes,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Import Settings')
                    ->description('Configure bulk import behavior')
                    ->schema([
                        TextInput::make('importChunkSize')
                            ->label('Import Chunk Size')
                            ->numeric()
                            ->minValue(100)
                            ->maxValue(10000)
                            ->required()
                            ->helperText('Number of records to process per batch'),

                        TextInput::make('maxZipSize')
                            ->label('Max ZIP File Size (bytes)')
                            ->numeric()
                            ->required()
                            ->helperText('Maximum allowed ZIP file size in bytes'),
                    ])->columns(2),

                Section::make('Cache Settings')
                    ->description('Configure caching behavior')
                    ->schema([
                        TextInput::make('cacheTtl')
                            ->label('Cache TTL (seconds)')
                            ->numeric()
                            ->minValue(60)
                            ->required()
                            ->helperText('How long to cache queries'),
                    ]),

                Section::make('Maintenance')
                    ->description('System maintenance options')
                    ->schema([
                        Toggle::make('maintenanceMode')
                            ->label('Maintenance Mode')
                            ->helperText('Put system in maintenance mode'),

                        Textarea::make('systemNotes')
                            ->label('System Notes')
                            ->helperText('Internal notes for administrators')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->submit('saveSettings'),
        ];
    }

    public function saveSettings(): void
    {
        try {
            // Get form state from component properties
            $data = [
                'importChunkSize' => $this->importChunkSize,
                'maxZipSize' => $this->maxZipSize,
                'cacheTtl' => $this->cacheTtl,
                'maintenanceMode' => $this->maintenanceMode,
                'systemNotes' => $this->systemNotes,
            ];

            // Validate
            if (empty($data['importChunkSize']) || $data['importChunkSize'] < 100 || $data['importChunkSize'] > 10000) {
                throw new \Exception('Import chunk size must be between 100 and 10,000');
            }

            if (empty($data['maxZipSize']) || $data['maxZipSize'] < 0) {
                throw new \Exception('Max ZIP size must be a positive number');
            }

            if (empty($data['cacheTtl']) || $data['cacheTtl'] < 60) {
                throw new \Exception('Cache TTL must be at least 60 seconds');
            }

            // Save to database
            SystemSetting::setSetting('import_chunk_size', (int)$data['importChunkSize'], 'integer', 'Number of records to process per batch');
            SystemSetting::setSetting('max_zip_size', (int)$data['maxZipSize'], 'integer', 'Maximum allowed ZIP file size in bytes');
            SystemSetting::setSetting('cache_ttl', (int)$data['cacheTtl'], 'integer', 'How long to cache queries (in seconds)');
            SystemSetting::setSetting('maintenance_mode', (bool)$data['maintenanceMode'], 'boolean', 'Put system in maintenance mode');
            SystemSetting::setSetting('system_notes', (string)$data['systemNotes'], 'string', 'Internal notes for administrators');

            Notification::make()
                ->title('Settings Updated')
                ->body('All system settings have been saved successfully.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Saving Settings')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
