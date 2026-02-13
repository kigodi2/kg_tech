# System Settings - Quick Reference

## 🎯 One-Minute Summary

System settings are now **database-backed with caching**. Update them in the admin panel or code.

---

## Using Settings

### In Admin Panel
```
/admin/system-settings → Update fields → Click "Save Settings"
```

### In Controllers/Services
```php
use App\Helpers\SystemSettingsHelper;

// Get
$size = SystemSettingsHelper::getImportChunkSize();  // Returns: 1000
$maxZip = SystemSettingsHelper::getMaxZipSize();      // Returns: 104857600
$ttl = SystemSettingsHelper::getCacheTtl();           // Returns: 3600
$maintenance = SystemSettingsHelper::isMaintenanceMode(); // Returns: false
$notes = SystemSettingsHelper::getSystemNotes();      // Returns: ''

// Set
SystemSettingsHelper::setImportChunkSize(5000);
SystemSettingsHelper::setMaxZipSize(157286400);
SystemSettingsHelper::setCacheTtl(7200);
SystemSettingsHelper::setMaintenanceMode(true);
SystemSettingsHelper::setSystemNotes('System under maintenance');
```

### Generic Get/Set
```php
// Get any setting
SystemSettingsHelper::get('import_chunk_size', 1000);

// Set any setting
SystemSettingsHelper::set('my_custom_setting', 'value', 'string', 'Description');

// Get all settings
$all = SystemSettingsHelper::all();
```

---

## Available Settings

| Key | Getter | Default | Notes |
|-----|--------|---------|-------|
| `import_chunk_size` | `getImportChunkSize()` | 1000 | Records per batch (100-10k) |
| `max_zip_size` | `getMaxZipSize()` | 104857600 | Max ZIP bytes (100MB) |
| `cache_ttl` | `getCacheTtl()` | 3600 | Cache duration (60+ sec) |
| `maintenance_mode` | `isMaintenanceMode()` | false | Toggle maintenance |
| `system_notes` | `getSystemNotes()` | '' | Admin notes |

---

## Database Table

```sql
SELECT * FROM system_settings;
```

| id | key | value | type | created_at | updated_at |
|----|-----|-------|------|------------|------------|
| 1 | import_chunk_size | 1000 | integer | ... | ... |
| 2 | max_zip_size | 104857600 | integer | ... | ... |

---

## Common Usage Examples

### Bulk Import Processing
```php
use App\Helpers\SystemSettingsHelper;

$chunkSize = SystemSettingsHelper::getImportChunkSize();
$records->chunk($chunkSize, function ($batch) {
    // Process batch
});
```

### Mark Entry Caching
```php
use App\Helpers\SystemSettingsHelper;
use Illuminate\Support\Facades\Cache;

$ttl = SystemSettingsHelper::getCacheTtl();
Cache::remember('subjects', $ttl, function () {
    return Subject::all();
});
```

### Maintenance Mode
```php
use App\Helpers\SystemSettingsHelper;

if (SystemSettingsHelper::isMaintenanceMode()) {
    return redirect('/maintenance');
}
```

---

## Cache Management

```php
use App\Helpers\SystemSettingsHelper;

// Clear cache (done automatically on save)
SystemSettingsHelper::clearCache();

// Cache is auto-cleared when you:
// - Save settings via admin panel
// - Call SystemSettingsHelper::set()
// - Call SystemSetting::setSetting()
```

---

## Files

| File | Purpose |
|------|---------|
| `app/Models/SystemSetting.php` | Database model |
| `app/Helpers/SystemSettingsHelper.php` | Easy access helper |
| `app/Filament/Admin/Pages/SystemSettings.php` | Admin form |
| `database/migrations/2026_02_01_212412_create_system_settings_table.php` | Database table |

---

## Testing

```php
// Get from database
$value = SystemSetting::getSetting('import_chunk_size');

// Get from helper (cached)
$value = SystemSettingsHelper::getImportChunkSize();

// Verify persistence
$this->assertEquals(1000, SystemSettingsHelper::getImportChunkSize());
```

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Settings not saving | Check admin user role: `ROLE_ADMINISTRATOR` |
| Old values cached | `SystemSettingsHelper::clearCache()` |
| Table doesn't exist | `php artisan migrate` |
| Helper not found | `composer dump-autoload` |

---

## Status

✅ Production Ready  
✅ Database Backed  
✅ Cached  
✅ Admin Panel Integrated  
✅ Type Safe  

---

**Last Updated**: 2026-02-02
