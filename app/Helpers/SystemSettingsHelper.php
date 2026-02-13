<?php

namespace App\Helpers;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class SystemSettingsHelper
{
    const CACHE_KEY = 'system_settings';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Get a system setting with caching
     */
    public static function get($key, $default = null)
    {
        // Try to get from cache first
        $settings = Cache::get(self::CACHE_KEY, []);

        if (isset($settings[$key])) {
            return $settings[$key];
        }

        // Get from database
        $value = SystemSetting::getSetting($key, $default);

        // Update cache
        $settings[$key] = $value;
        Cache::put(self::CACHE_KEY, $settings, self::CACHE_TTL);

        return $value;
    }

    /**
     * Set a system setting and clear cache
     */
    public static function set($key, $value, $type = 'string', $description = null)
    {
        SystemSetting::setSetting($key, $value, $type, $description);
        self::clearCache();
    }

    /**
     * Get all settings with caching
     */
    public static function all()
    {
        $cached = Cache::get(self::CACHE_KEY);

        if ($cached !== null) {
            return $cached;
        }

        $settings = SystemSetting::allSettings();
        Cache::put(self::CACHE_KEY, $settings, self::CACHE_TTL);

        return $settings;
    }

    /**
     * Clear the settings cache
     */
    public static function clearCache()
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Specific getters for common settings
     */

    public static function getImportChunkSize($default = 1000)
    {
        return self::get('import_chunk_size', $default);
    }

    public static function getMaxZipSize($default = 104857600)
    {
        return self::get('max_zip_size', $default);
    }

    public static function getCacheTtl($default = 3600)
    {
        return self::get('cache_ttl', $default);
    }

    public static function isMaintenanceMode($default = false)
    {
        return self::get('maintenance_mode', $default);
    }

    public static function getSystemNotes($default = '')
    {
        return self::get('system_notes', $default);
    }

    /**
     * Specific setters
     */

    public static function setImportChunkSize($value)
    {
        self::set('import_chunk_size', $value, 'integer', 'Number of records to process per batch');
    }

    public static function setMaxZipSize($value)
    {
        self::set('max_zip_size', $value, 'integer', 'Maximum allowed ZIP file size in bytes');
    }

    public static function setCacheTtl($value)
    {
        self::set('cache_ttl', $value, 'integer', 'How long to cache queries (in seconds)');
    }

    public static function setMaintenanceMode($value)
    {
        self::set('maintenance_mode', $value, 'boolean', 'Put system in maintenance mode');
    }

    public static function setSystemNotes($value)
    {
        self::set('system_notes', $value, 'string', 'Internal notes for administrators');
    }
}
