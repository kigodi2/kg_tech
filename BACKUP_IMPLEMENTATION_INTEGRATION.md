# SQLite Backup System - Integration Guide

## Integration Steps

### Step 1: Register Routes

**File:** `routes/api.php`

Add before the closing brace:

```php
// Backup & Restore API
require_once 'backup.php';

// Or manually register:
Route::middleware(['auth:sanctum', 'admin'])->prefix('backups')->group(function () {
    Route::get('/status', 'App\Http\Controllers\BackupController@status');
    Route::post('/create', 'App\Http\Controllers\BackupController@create');
    Route::get('/logs', 'App\Http\Controllers\BackupController@logs');
    Route::post('/validate', 'App\Http\Controllers\BackupController@validate');
    Route::post('/simulate-restore', 'App\Http\Controllers\BackupController@simulateRestore');
    Route::post('/restore', 'App\Http\Controllers\BackupController@restore');
    Route::get('/health-metrics', 'App\Http\Controllers\BackupController@healthMetrics');
});
```

### Step 2: Register Model & Policy

**File:** `app/Providers/AppServiceProvider.php` or `AuthServiceProvider.php`

Add to `boot()` method:

```php
use App\Models\Backup;
use App\Policies\BackupPolicy;

public function boot(): void
{
    // Register backup policy
    Gate::policy(Backup::class, BackupPolicy::class);
}
```

### Step 3: Environment Configuration

**File:** `.env`

Add:

```env
# Backup encryption
BACKUP_ENCRYPTION_KEY=base64:your-strong-encryption-key-here-minimum-32-chars

# Backup options
AUTOMATED_BACKUPS_ENABLED=true
BACKUP_QUEUE=backups

# Backup retention (days)
BACKUP_RETENTION_DAYS=90

# Max concurrent backups
BACKUP_MAX_CONCURRENT=1
```

Generate strong key:

```bash
php artisan key:generate --show
# Use the generated key for BACKUP_ENCRYPTION_KEY
```

### Step 4: Queue Configuration

**File:** `config/queue.php`

Add to `connections` array:

```php
'backups' => [
    'driver' => 'database',
    'table' => 'jobs',
    'queue' => 'backups',
    'retry_after' => 300,
    'block_for' => null,
],
```

Or use Redis for better performance:

```php
'backups' => [
    'driver' => 'redis',
    'connection' => 'default',
    'queue' => 'backups',
    'retry_after' => 300,
    'block_for' => null,
],
```

### Step 5: Run Migration

```bash
# Create backup_logs table
php artisan migrate
```

### Step 6: Start Queue Worker

```bash
# Development
php artisan queue:work --queue=backups

# Production (persistent)
php artisan queue:work --queue=backups --timeout=3600 &

# Using supervisor (recommended for production)
# See supervisor-backup-worker.conf below
```

### Step 7: Configure Scheduler

**File:** `app/Console/Kernel.php`

Already created with backup jobs. Verify crontab entry:

```bash
# Add to crontab
crontab -e

# Add this line to run schedule every minute
* * * * * cd /path/to/irms && php artisan schedule:run >> /dev/null 2>&1
```

### Step 8: Verify Installation

```bash
# Check migration
php artisan migrate:status | grep backup_logs

# Test backup creation
php artisan tinker
> $admin = App\Models\User::where('is_admin', true)->first();
> app('App\Services\SQLiteBackupService')->createFullBackup($admin, 'Test backup');

# Check queue
php artisan queue:listen --queue=backups

# View backup logs
> App\Models\BackupLog::latest()->first();
```

---

## Production Deployment

### Supervisor Configuration

**File:** `/etc/supervisor/conf.d/irms-backup-worker.conf`

```ini
[program:irms-backup-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/irms/artisan queue:work --queue=backups --timeout=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
stdout_logfile=/var/log/irms/backup-worker.log
stderr_logfile=/var/log/irms/backup-worker-error.log
stdout_logfile_maxbytes=10M
stdout_logfile_backups=10
```

Load and start:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start irms-backup-worker:*
```

### Nginx Configuration

Add to Nginx config if exposing backup endpoints:

```nginx
location /api/backups {
    # Require authentication
    auth_request /api/auth/check;
    
    # Rate limiting for restore operations
    limit_req zone=api burst=5 nodelay;
    
    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    proxy_pass http://127.0.0.1:8000;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
}
```

### Cron Job

Add to crontab (via cPanel or directly):

```bash
# Run Laravel scheduler every minute
* * * * * cd /path/to/irms && php artisan schedule:run >> storage/logs/schedule.log 2>&1
```

### Systemd Service (Optional)

**File:** `/etc/systemd/system/irms-scheduler.service`

```ini
[Unit]
Description=IRMS Scheduler
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/irms
ExecStart=/usr/bin/php artisan schedule:work
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl enable irms-scheduler
sudo systemctl start irms-scheduler
sudo systemctl status irms-scheduler
```

---

## Admin Panel Integration (Filament)

### Add Backup Resource

**File:** `app/Filament/Admin/Resources/BackupLogResource.php`

Create using:

```bash
php artisan make:filament-resource BackupLog
```

Content:

```php
<?php

namespace App\Filament\Admin\Resources;

use App\Models\BackupLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;

class BackupLogResource extends Resource
{
    protected static ?string $model = BackupLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 1;

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('operation')
                    ->label('Operation')
                    ->formatStateUsing(fn($state) => match($state) {
                        'backup_created' => 'Backup Created',
                        'backup_failed' => 'Backup Failed',
                        'restore_completed' => 'Restore Completed',
                        'restore_failed' => 'Restore Failed',
                        default => $state,
                    }),
                TextColumn::make('user.name')->label('User'),
                BadgeColumn::make('status')
                    ->colors(['success' => 'success', 'danger' => 'failed']),
                TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('operation'),
                SelectFilter::make('status'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([50, 100, 200]);
    }

    public static function canCreate(): bool
    {
        return false; // Logs are immutable
    }

    public static function canDelete(BackupLog $record): bool
    {
        return false; // Cannot delete audit logs
    }
}
```

### Add Dashboard Widget

**File:** `app/Filament/Admin/Widgets/BackupHealthWidget.php`

```php
<?php

namespace App\Filament\Admin\Widgets;

use App\Models\BackupLog;
use Filament\Widgets\Widget;

class BackupHealthWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.backup-health';

    public function getLastBackup()
    {
        return BackupLog::backupOperations()
            ->successful()
            ->latest()
            ->first();
    }

    public function getLastRestore()
    {
        return BackupLog::restoreOperations()
            ->latest()
            ->first();
    }

    public function getFailedCount()
    {
        return BackupLog::backupOperations()
            ->failed()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
    }
}
```

View: `resources/views/filament/admin/widgets/backup-health.blade.php`

```blade
<div class="p-6 bg-white rounded-lg shadow">
    <h3 class="text-lg font-bold mb-4">Database Backup Status</h3>
    
    <div class="grid grid-cols-3 gap-4">
        <!-- Last Backup -->
        <div class="border rounded p-4">
            <p class="text-sm text-gray-600">Last Backup</p>
            @if($lastBackup)
                <p class="text-lg font-bold text-green-600">✓ Success</p>
                <p class="text-xs text-gray-500">{{ $lastBackup->created_at->diffForHumans() }}</p>
            @else
                <p class="text-lg font-bold text-yellow-600">⚠ Pending</p>
            @endif
        </div>
        
        <!-- Last Restore -->
        <div class="border rounded p-4">
            <p class="text-sm text-gray-600">Last Restore</p>
            @if($lastRestore)
                <p class="text-lg font-bold">
                    @if($lastRestore->status === 'success')
                        <span class="text-green-600">✓ {{ $lastRestore->status }}</span>
                    @else
                        <span class="text-red-600">✗ {{ $lastRestore->status }}</span>
                    @endif
                </p>
                <p class="text-xs text-gray-500">{{ $lastRestore->created_at->diffForHumans() }}</p>
            @else
                <p class="text-lg font-bold text-gray-400">- Never</p>
            @endif
        </div>
        
        <!-- Failed Backups (7d) -->
        <div class="border rounded p-4">
            <p class="text-sm text-gray-600">Failed (7 days)</p>
            <p class="text-lg font-bold @if($failedCount > 0) text-red-600 @else text-green-600 @endif">
                {{ $failedCount }}
            </p>
        </div>
    </div>
</div>
```

### Navigation Menu

Add to Filament sidebar in `config/filament.php` or via Panel configuration:

```php
->navigation([
    'System' => [
        'Backups' => 'backup-logs',
        'Audit Logs' => 'governance-audit-logs',
    ],
])
```

---

## Testing

### Unit Tests

**File:** `tests/Unit/Services/SQLiteBackupServiceTest.php`

```php
<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\SQLiteBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SQLiteBackupServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SQLiteBackupService $backupService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupService = app(SQLiteBackupService::class);
    }

    public function test_can_create_full_backup()
    {
        $admin = User::factory()->admin()->create();

        $result = $this->backupService->createFullBackup($admin, 'Test backup');

        $this->assertTrue($result['success']);
        $this->assertFileExists($result['path']);
        $this->assertGreaterThan(0, $result['size']);
    }

    public function test_backup_files_are_encrypted()
    {
        $admin = User::factory()->admin()->create();

        $result = $this->backupService->createFullBackup($admin);

        $this->assertTrue(str_ends_with($result['path'], '.enc'));
    }

    public function test_non_admin_cannot_backup()
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->expectException(\Exception::class);
        $this->backupService->createFullBackup($user);
    }
}
```

### Feature Tests

**File:** `tests/Feature/BackupControllerTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_can_check_backup_status()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/backups/status');

        $response->assertOk();
        $response->assertJsonStructure(['status', 'last_backup', 'failed_backups_7d']);
    }

    public function test_can_create_manual_backup()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/backups/create', [
            'notes' => 'Test backup',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['backup' => ['id', 'size']]);
    }

    public function test_unauthorized_user_cannot_create_backup()
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->postJson('/api/backups/create');

        $response->assertForbidden();
    }
}
```

Run tests:

```bash
php artisan test tests/Unit/Services/SQLiteBackupServiceTest.php
php artisan test tests/Feature/BackupControllerTest.php
```

---

## Monitoring & Logging

### Application Logging

Backups are logged to standard Laravel log:

```bash
tail -f storage/logs/laravel.log | grep -i backup
```

### Structured Logging

For production, configure structured logging (JSON):

**File:** `config/logging.php`

```php
'channels' => [
    'backups' => [
        'driver' => 'single',
        'path' => storage_path('logs/backups.log'),
        'level' => env('LOG_LEVEL', 'debug'),
    ],
],
```

Use in service:

```php
\Log::channel('backups')->info('Backup completed', [
    'backup_id' => $result['backup_id'],
    'size' => $result['size'],
    'duration_seconds' => $endTime - $startTime,
]);
```

### Metrics & Monitoring

Export metrics for monitoring systems:

```php
// Prometheus format
$backupCount = BackupLog::backupOperations()->count();
$failureRate = BackupLog::backupOperations()
    ->where('created_at', '>=', now()->subDays(30))
    ->where('status', 'failed')
    ->count();

echo "irms_backups_total{status=\"success\"} " . BackupLog::backupOperations()->successful()->count() . "\n";
echo "irms_backups_total{status=\"failed\"} " . $failureRate . "\n";
echo "irms_backups_last_timestamp " . BackupLog::latest()->first()->created_at->timestamp . "\n";
```

---

## Checklist

### Pre-Production
- [ ] All files created
- [ ] Migration ran successfully
- [ ] Routes registered in `routes/api.php`
- [ ] Policy registered in AuthServiceProvider
- [ ] `.env` configured with encryption key
- [ ] Queue configuration set up
- [ ] Queue worker tested: `php artisan queue:work --queue=backups`
- [ ] Test backup created: `POST /api/backups/create`
- [ ] Test restore simulation: `POST /api/backups/simulate-restore`
- [ ] Crontab entry verified
- [ ] Supervisor config deployed (if using)
- [ ] Logs monitored: `tail -f storage/logs/laravel.log`

### Post-Production
- [ ] Monitor first daily backup runs
- [ ] Verify first weekly backup
- [ ] Test restore simulation monthly
- [ ] Review audit logs weekly
- [ ] Check storage utilization
- [ ] Monitor queue worker health
- [ ] Verify encryption key security

---

## Files Summary

| File | Purpose |
|------|---------|
| `app/Services/SQLiteBackupService.php` | Core backup logic |
| `app/Services/SQLiteRestoreService.php` | Core restore logic |
| `app/Models/BackupLog.php` | Audit log model |
| `app/Jobs/Scheduled*Backup.php` | Queue jobs |
| `app/Console/Kernel.php` | Scheduler registration |
| `app/Console/Commands/ScheduleBackups.php` | CLI command |
| `app/Http/Controllers/BackupController.php` | API endpoints |
| `app/Policies/BackupPolicy.php` | Authorization |
| `routes/backup.php` | Route definitions |
| `database/migrations/*_create_backup_logs_table.php` | Database schema |
| `SQLITE_BACKUP_RESTORE_SYSTEM.md` | Full documentation |
| `BACKUP_QUICK_REFERENCE.md` | Quick start guide |

---

**Status:** Ready for Integration  
**Last Updated:** 2025-02-02
