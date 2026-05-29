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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use App\Helpers\SystemSettingsHelper;
use App\Models\GovernanceAuditLog;

class SystemSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 7;

    protected static string $view = 'filament.admin.pages.system-settings';

    protected static ?string $title = 'System Settings';

    public $applicationName = 'IRMS';
    public $systemTagline = 'Integrated Results Management System';
    public $dashboardIdentity = 'IRMS Admin Panel';
    public $systemLogo = null;
    public $primaryColor = '#00A3DD';
    public $sidebarBg = '#050a0d';
    public $topbarBg = '#0b1014';
    public $fontFamily = 'Ubuntu';
    public $timezone = 'Africa/Dar_es_Salaam';
    public $dateFormat = 'Y-m-d';
    public $currency = 'TZS';
    public $enabledModules = [];
    public $importChunkSize = 1000;
    public $maxZipSize = 104857600; // 100MB
    public $cacheTtl = 3600;
    public $maintenanceMode = false;
    public $systemNotes = '';

    public function mount(): void
    {
        $this->applicationName = SystemSettingsHelper::getSetting('application_name', 'IRMS');
        $this->systemTagline = SystemSettingsHelper::getSetting('system_tagline', 'Integrated Results Management System');
        $this->dashboardIdentity = SystemSettingsHelper::getSetting('dashboard_identity', 'IRMS Admin Panel');
        $this->systemLogo = SystemSettingsHelper::getSetting('system_logo', null);
        $this->primaryColor = SystemSettingsHelper::getSetting('primary_color', '#00A3DD');
        $this->sidebarBg = SystemSettingsHelper::getSetting('sidebar_bg', '#050a0d');
        $this->topbarBg = SystemSettingsHelper::getSetting('topbar_bg', '#0b1014');
        $this->fontFamily = SystemSettingsHelper::getSetting('font_family', 'Ubuntu');
        $this->timezone = SystemSettingsHelper::getSetting('timezone', config('app.timezone', 'Africa/Dar_es_Salaam'));
        $this->dateFormat = SystemSettingsHelper::getSetting('date_format', 'Y-m-d');
        $this->currency = SystemSettingsHelper::getSetting('currency', 'TZS');
        $this->enabledModules = collect(SystemSettingsHelper::moduleOptions())
            ->keys()
            ->filter(fn (string $key) => SystemSettingsHelper::isModuleEnabled($key, true))
            ->values()
            ->all();
        $this->importChunkSize = SystemSettingsHelper::getSetting('import_chunk_size', config('irms.import_chunk_size', 1000));
        $this->maxZipSize = SystemSettingsHelper::getSetting('max_zip_size', config('irms.max_zip_size', 104857600));
        $this->cacheTtl = SystemSettingsHelper::getSetting('cache_ttl', config('irms.cache_ttl', 3600));
        $this->maintenanceMode = SystemSettingsHelper::getSetting('maintenance_mode', config('app.maintenance_mode', false));
        $this->systemNotes = SystemSettingsHelper::getSetting('system_notes', config('irms.system_notes', ''));

        $this->form->fill([
            'applicationName' => $this->applicationName,
            'systemTagline' => $this->systemTagline,
            'dashboardIdentity' => $this->dashboardIdentity,
            'systemLogo' => $this->systemLogo,
            'primaryColor' => $this->primaryColor,
            'sidebarBg' => $this->sidebarBg,
            'topbarBg' => $this->topbarBg,
            'fontFamily' => $this->fontFamily,
            'timezone' => $this->timezone,
            'dateFormat' => $this->dateFormat,
            'currency' => $this->currency,
            'enabledModules' => $this->enabledModules,
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
                Section::make('Branding')
                    ->description('Core IRMS identity used by admin screens and reports where supported.')
                    ->schema([
                        TextInput::make('applicationName')
                            ->label('Application Name')
                            ->required()
                            ->maxLength(80),

                        TextInput::make('systemTagline')
                            ->label('System Tagline')
                            ->maxLength(160),

                        TextInput::make('dashboardIdentity')
                            ->label('Dashboard Identity')
                            ->required()
                            ->maxLength(120),

                        FileUpload::make('systemLogo')
                            ->label('System Logo')
                            ->image()
                            ->disk('public')
                            ->directory('system-branding')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->helperText('PNG, JPG, or SVG-style image upload supported by the server. Maximum 2MB.'),
                    ])->columns(2),

                Section::make('Appearance and Typography')
                    ->description('Preserves the IRMS dark/gold style while allowing controlled theme adjustments.')
                    ->schema([
                        ColorPicker::make('primaryColor')
                            ->label('Primary Color')
                            ->required()
                            ->regex('/^#[0-9A-Fa-f]{6}$/'),

                        ColorPicker::make('sidebarBg')
                            ->label('Sidebar Background')
                            ->required()
                            ->regex('/^#[0-9A-Fa-f]{6}$/'),

                        ColorPicker::make('topbarBg')
                            ->label('Topbar Background')
                            ->required()
                            ->regex('/^#[0-9A-Fa-f]{6}$/'),

                        Select::make('fontFamily')
                            ->label('Global Font')
                            ->options(SystemSettingsHelper::allowedFonts())
                            ->required(),
                    ])->columns(4),

                Section::make('Module Control')
                    ->description('Controls module visibility hints. Existing route permissions remain the final security layer.')
                    ->schema([
                        CheckboxList::make('enabledModules')
                            ->label('Enabled IRMS Modules')
                            ->options(SystemSettingsHelper::moduleOptions())
                            ->columns(3)
                            ->bulkToggleable()
                            ->helperText('Only existing IRMS modules are listed. Disabling a module should be paired with backend permission checks where routes exist.'),
                    ]),

                Section::make('Localization and Defaults')
                    ->description('Shared display defaults for supported IRMS pages.')
                    ->schema([
                        Select::make('timezone')
                            ->label('Timezone')
                            ->options(collect(\DateTimeZone::listIdentifiers())->mapWithKeys(fn ($timezone) => [$timezone => $timezone])->all())
                            ->searchable()
                            ->required(),

                        Select::make('dateFormat')
                            ->label('Date Format')
                            ->options(SystemSettingsHelper::allowedDateFormats())
                            ->required(),

                        Select::make('currency')
                            ->label('Currency')
                            ->options(SystemSettingsHelper::allowedCurrencies())
                            ->required(),
                    ])->columns(3),

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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshSettingsCache')
                ->label('Refresh Settings Cache')
                ->icon('heroicon-o-arrow-path')
                ->action('refreshSettingsCache'),
        ];
    }

    public function saveSettings(): void
    {
        try {
            $formState = $this->form->getState();

            $enabledModules = array_values($formState['enabledModules'] ?? []);

            $data = [
                'applicationName' => trim((string) ($formState['applicationName'] ?? '')),
                'systemTagline' => trim((string) ($formState['systemTagline'] ?? '')),
                'dashboardIdentity' => trim((string) ($formState['dashboardIdentity'] ?? '')),
                'systemLogo' => $formState['systemLogo'] ?? null,
                'primaryColor' => (string) ($formState['primaryColor'] ?? ''),
                'sidebarBg' => (string) ($formState['sidebarBg'] ?? ''),
                'topbarBg' => (string) ($formState['topbarBg'] ?? ''),
                'fontFamily' => (string) ($formState['fontFamily'] ?? ''),
                'timezone' => (string) ($formState['timezone'] ?? ''),
                'dateFormat' => (string) ($formState['dateFormat'] ?? ''),
                'currency' => (string) ($formState['currency'] ?? ''),
                'enabledModules' => $enabledModules,
                'importChunkSize' => (int) ($formState['importChunkSize'] ?? 1000),
                'maxZipSize' => (int) ($formState['maxZipSize'] ?? 104857600),
                'cacheTtl' => (int) ($formState['cacheTtl'] ?? 3600),
                'maintenanceMode' => (bool) ($formState['maintenanceMode'] ?? false),
                'systemNotes' => (string) ($formState['systemNotes'] ?? ''),
            ];

            if ($data['applicationName'] === '') {
                throw new \Exception('Application name is required.');
            }

            foreach (['primaryColor', 'sidebarBg', 'topbarBg'] as $colorKey) {
                if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $data[$colorKey])) {
                    throw new \Exception('Theme colors must be valid HEX colors.');
                }
            }

            if (! array_key_exists($data['fontFamily'], SystemSettingsHelper::allowedFonts())) {
                throw new \Exception('Selected font is not allowed.');
            }

            if (! in_array($data['timezone'], \DateTimeZone::listIdentifiers(), true)) {
                throw new \Exception('Selected timezone is invalid.');
            }

            if (! array_key_exists($data['dateFormat'], SystemSettingsHelper::allowedDateFormats())) {
                throw new \Exception('Selected date format is invalid.');
            }

            if (! array_key_exists($data['currency'], SystemSettingsHelper::allowedCurrencies())) {
                throw new \Exception('Selected currency is invalid.');
            }

            $unknownModules = array_diff($data['enabledModules'], array_keys(SystemSettingsHelper::moduleOptions()));
            if ($unknownModules !== []) {
                throw new \Exception('One or more selected modules are not registered IRMS modules.');
            }

            if (empty($data['importChunkSize']) || $data['importChunkSize'] < 100 || $data['importChunkSize'] > 10000) {
                throw new \Exception('Import chunk size must be between 100 and 10,000');
            }

            if (empty($data['maxZipSize']) || $data['maxZipSize'] < 0) {
                throw new \Exception('Max ZIP size must be a positive number');
            }

            if (empty($data['cacheTtl']) || $data['cacheTtl'] < 60) {
                throw new \Exception('Cache TTL must be at least 60 seconds');
            }

            $oldMaintenanceMode = (bool) SystemSettingsHelper::getSetting('maintenance_mode', false);
            $newMaintenanceMode = (bool) $data['maintenanceMode'];
            $oldSystemNotes = (string) SystemSettingsHelper::getSetting('system_notes', '');
            $newSystemNotes = (string) $data['systemNotes'];

            $settingsToSave = [
                'application_name' => [$data['applicationName'], 'string', 'IRMS application display name'],
                'system_tagline' => [$data['systemTagline'], 'string', 'IRMS system tagline'],
                'dashboard_identity' => [$data['dashboardIdentity'], 'string', 'Admin dashboard identity label'],
                'system_logo' => [$data['systemLogo'], 'string', 'System logo path on public storage'],
                'primary_color' => [$data['primaryColor'], 'string', 'Primary IRMS theme color'],
                'sidebar_bg' => [$data['sidebarBg'], 'string', 'Sidebar background color'],
                'topbar_bg' => [$data['topbarBg'], 'string', 'Topbar background color'],
                'font_family' => [$data['fontFamily'], 'string', 'Global font family'],
                'timezone' => [$data['timezone'], 'string', 'Default system timezone'],
                'date_format' => [$data['dateFormat'], 'string', 'Default date display format'],
                'currency' => [$data['currency'], 'string', 'Default currency code'],
                'import_chunk_size' => [(int) $data['importChunkSize'], 'integer', 'Number of records to process per batch'],
                'max_zip_size' => [(int) $data['maxZipSize'], 'integer', 'Maximum allowed ZIP file size in bytes'],
                'cache_ttl' => [(int) $data['cacheTtl'], 'integer', 'How long to cache queries (in seconds)'],
                'maintenance_mode' => [(bool) $data['maintenanceMode'], 'boolean', 'Put system in maintenance mode'],
                'system_notes' => [(string) $data['systemNotes'], 'string', 'Internal notes for administrators'],
            ];

            foreach (SystemSettingsHelper::moduleOptions() as $moduleKey => $moduleLabel) {
                $settingsToSave["module_{$moduleKey}_enabled"] = [
                    in_array($moduleKey, $data['enabledModules'], true),
                    'boolean',
                    "Enable {$moduleLabel} module visibility",
                ];
            }

            foreach ($settingsToSave as $key => [$value, $type, $description]) {
                $this->saveSettingWithAudit($key, $value, $type, $description);
            }

            // Sync component properties
            $this->applicationName = $data['applicationName'];
            $this->systemTagline = $data['systemTagline'];
            $this->dashboardIdentity = $data['dashboardIdentity'];
            $this->systemLogo = $data['systemLogo'];
            $this->primaryColor = $data['primaryColor'];
            $this->sidebarBg = $data['sidebarBg'];
            $this->topbarBg = $data['topbarBg'];
            $this->fontFamily = $data['fontFamily'];
            $this->timezone = $data['timezone'];
            $this->dateFormat = $data['dateFormat'];
            $this->currency = $data['currency'];
            $this->enabledModules = $data['enabledModules'];
            $this->importChunkSize = $data['importChunkSize'];
            $this->maxZipSize = $data['maxZipSize'];
            $this->cacheTtl = $data['cacheTtl'];
            $this->maintenanceMode = $data['maintenanceMode'];
            $this->systemNotes = $data['systemNotes'];

            SystemSettingsHelper::refreshSettingsCache();

            // Distinct Audit Logging for Maintenance Mode Transitions and Notes
            $user = auth()->user();
            if ($oldMaintenanceMode !== $newMaintenanceMode) {
                $actionName = $newMaintenanceMode ? 'system_maintenance_enabled' : 'system_maintenance_disabled';
                GovernanceAuditLog::log(
                    $actionName,
                    userId: $user?->id,
                    adminId: $user?->id,
                    data: [
                        'action' => $actionName,
                        'old_value' => $oldMaintenanceMode ? 'enabled' : 'disabled',
                        'new_value' => $newMaintenanceMode ? 'enabled' : 'disabled',
                        'changed_by' => $user?->email ?? 'unknown',
                        'changed_at' => now()->toDateTimeString(),
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'system_notes' => $newSystemNotes,
                        'system_notes_changed' => $oldSystemNotes !== $newSystemNotes,
                    ]
                );
            } elseif ($oldSystemNotes !== $newSystemNotes) {
                GovernanceAuditLog::log(
                    'system_maintenance_notes_updated',
                    userId: $user?->id,
                    adminId: $user?->id,
                    data: [
                        'action' => 'system_maintenance_notes_updated',
                        'old_value' => $oldSystemNotes,
                        'new_value' => $newSystemNotes,
                        'changed_by' => $user?->email ?? 'unknown',
                        'changed_at' => now()->toDateTimeString(),
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]
                );
            }

            // Success notifications
            if ($oldMaintenanceMode !== $newMaintenanceMode) {
                $bodyMessage = $newMaintenanceMode 
                    ? 'Maintenance Mode enabled successfully. Non-authorized users will now see the maintenance notice.' 
                    : 'Maintenance Mode disabled successfully. The system is now accessible.';
            } else {
                $bodyMessage = 'All system settings have been saved successfully.';
            }

            Notification::make()
                ->title('Settings Updated')
                ->body($bodyMessage)
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

    protected function saveSettingWithAudit(string $key, mixed $value, string $type, string $description): void
    {
        $oldValue = SystemSettingsHelper::getSetting($key);

        SystemSettingsHelper::setSetting($key, $value, $type, $description);

        if ($oldValue === $value) {
            return;
        }

        $user = auth()->user();

        GovernanceAuditLog::log(
            'system_setting_updated',
            userId: $user?->id,
            adminId: $user?->id,
            data: [
                'setting_key' => $key,
                'old_value' => $oldValue,
                'new_value' => $value,
                'source' => 'app/Filament/Admin/Pages/SystemSettings.php',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'updated_at' => now()->toDateTimeString(),
            ]
        );
    }

    public function refreshSettingsCache(): void
    {
        SystemSettingsHelper::refreshSettingsCache();

        $user = auth()->user();

        GovernanceAuditLog::log(
            'system_settings_cache_refreshed',
            userId: $user?->id,
            adminId: $user?->id,
            data: [
                'source' => 'app/Filament/Admin/Pages/SystemSettings.php',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'refreshed_at' => now()->toDateTimeString(),
            ]
        );

        Notification::make()
            ->title('Settings Cache Refreshed')
            ->body('The settings cache has been cleared and reloaded.')
            ->success()
            ->send();
    }
}
