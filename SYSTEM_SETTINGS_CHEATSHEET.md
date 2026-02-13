# System Settings - Cheat Sheet

## 🎯 TL;DR

Settings are database-backed with caching. Update via admin panel or code.

---

## Admin Panel

**URL**: `/admin/system-settings`  
**Access**: Admin users only  
**Action**: Update fields → Save Settings

---

## Code Usage

```php
use App\Helpers\SystemSettingsHelper;

// Get settings
$size = SystemSettingsHelper::getImportChunkSize();      // 1000
$max = SystemSettingsHelper::getMaxZipSize();            // 104857600
$ttl = SystemSettingsHelper::getCacheTtl();              // 3600
$maint = SystemSettingsHelper::isMaintenanceMode();      // false
$notes = SystemSettingsHelper::getSystemNotes();         // ''

// Set settings
SystemSettingsHelper::setImportChunkSize(5000);
SystemSettingsHelper::setMaxZipSize(157286400);
SystemSettingsHelper::setCacheTtl(7200);
SystemSettingsHelper::setMaintenanceMode(true);
SystemSettingsHelper::setSystemNotes('Under maintenance');

// Generic get/set
$val = SystemSettingsHelper::get('key', 'default');
SystemSettingsHelper::set('key', 'value', 'type', 'description');

// Get all settings
$all = SystemSettingsHelper::all();

// Clear cache
SystemSettingsHelper::clearCache();
```

---

## Bulk Import Example

```php
use App\Helpers\SystemSettingsHelper;

class BulkImportProcessor {
    public function process($import) {
        $chunkSize = SystemSettingsHelper::getImportChunkSize();
        $maxZipSize = SystemSettingsHelper::getMaxZipSize();
        
        if ($import->file_size > $maxZipSize) {
            throw new Exception('File too large');
        }
        
        $records->chunk($chunkSize, function ($batch) {
            $this->processBatch($batch);
        });
    }
}
```

---

## Mark Entry Caching Example

```php
use App\Helpers\SystemSettingsHelper;
use Illuminate\Support\Facades\Cache;

class MarkEntryService {
    public function getSubjects($candidateId) {
        $ttl = SystemSettingsHelper::getCacheTtl();
        
        return Cache::remember(
            "subjects_$candidateId",
            $ttl,
            fn() => Subject::whereBelongsTo($candidateId)->get()
        );
    }
}
```

---

## Maintenance Mode Example

```php
use App\Helpers\SystemSettingsHelper;

class CheckMaintenanceMode {
    public function handle($request, $next) {
        if (SystemSettingsHelper::isMaintenanceMode()) {
            return response()->view('maintenance', [], 503);
        }
        return $next($request);
    }
}
```

---

## Direct Database Access

```php
use App\Models\SystemSetting;

// Get
$value = SystemSetting::getSetting('import_chunk_size', 1000);

// Set
SystemSetting::setSetting('import_chunk_size', 5000, 'integer', 'description');

// All
$all = SystemSetting::allSettings();
```

---

## Settings Reference

| Setting | Helper | Default | Min | Max |
|---------|--------|---------|-----|-----|
| `import_chunk_size` | `getImportChunkSize()` | 1000 | 100 | 10000 |
| `max_zip_size` | `getMaxZipSize()` | 104857600 | - | - |
| `cache_ttl` | `getCacheTtl()` | 3600 | 60 | - |
| `maintenance_mode` | `isMaintenanceMode()` | false | - | - |
| `system_notes` | `getSystemNotes()` | '' | - | - |

---

## Database Commands

```bash
# View all settings
php artisan tinker
DB::table('system_settings')->get()

# Update directly
DB::table('system_settings')->update(['value' => 5000])

# Clear cache
php artisan cache:clear
```

---

## Type Casting

| Type | Example | Usage |
|------|---------|-------|
| string | "hello" | Text values |
| integer | 1000 | Numeric values |
| boolean | true | Yes/No values |
| json | {"key":"value"} | Complex data |

---

## Cache Info

- **TTL**: 1 hour (3600 seconds)
- **Key**: `system_settings`
- **Auto-clear**: On update via helper
- **Manual clear**: `SystemSettingsHelper::clearCache()`

---

## Files

- **Model**: `app/Models/SystemSetting.php`
- **Helper**: `app/Helpers/SystemSettingsHelper.php`
- **Page**: `app/Filament/Admin/Pages/SystemSettings.php`
- **Table**: `system_settings` (database)
- **Migration**: `2026_02_01_212412_create_system_settings_table.php`

---

## Routes

- Admin Panel: `GET /admin/system-settings`
- Login: `GET /admin/login`

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| Not saving | Check admin role |
| Old values | `SystemSettingsHelper::clearCache()` |
| Table missing | `php artisan migrate` |
| Helper not found | `composer dump-autoload` |

---

## Testing

```php
// Quick test
SystemSettingsHelper::set('test', 'value');
$val = SystemSettingsHelper::get('test');
assert($val === 'value'); // ✓

// Type test
SystemSettingsHelper::set('num', 123, 'integer');
$num = SystemSettingsHelper::get('num');
assert(is_int($num)); // ✓

// Cache test
$a = SystemSettingsHelper::get('key'); // DB hit
$b = SystemSettingsHelper::get('key'); // Cache hit
assert($a === $b); // ✓
```

---

## Quick Setup

```bash
# Run migration
php artisan migrate

# Reload autoloader
composer dump-autoload

# Test in admin
open http://127.0.0.1:8000/admin/system-settings

# Test in code
php artisan tinker
SystemSettingsHelper::getImportChunkSize()
```

---

## Environment

- **Laravel**: 12.x
- **PHP**: 8.2+
- **Filament**: v3
- **Status**: Production Ready ✅

---

**Last Updated**: 2026-02-02
