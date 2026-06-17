<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingsService
{
    protected const CACHE_KEY = 'system_settings_array';

    /**
     * Get the cache TTL directly from DB to avoid recursion
     */
    protected function getCacheTtl(): int
    {
        try {
            $value = DB::table('system_settings')
                ->where('key', 'cache_ttl_seconds')
                ->value('value');
            return $value !== null ? (int)$value : 3600;
        } catch (\Throwable $e) {
            return 3600;
        }
    }

    /**
     * Get all settings (cached)
     */
    public function allSettings(): array
    {
        $ttl = $this->getCacheTtl();
        return Cache::remember(self::CACHE_KEY, $ttl, function () {
            $settings = Setting::all();
            $mapped = [];
            foreach ($settings as $setting) {
                $mapped[$setting->key] = [
                    'value' => $setting->value,
                    'type' => $setting->type,
                    'group' => $setting->group,
                    'description' => $setting->description,
                ];
            }
            return $mapped;
        });
    }

    /**
     * Get a setting value by key
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->allSettings();
        if (array_key_exists($key, $settings)) {
            return $this->castValue($settings[$key]['value'], $settings[$key]['type']);
        }
        return $default;
    }

    /**
     * Set a setting value by key
     */
    public function set(string $key, mixed $value, ?int $updatedBy = null): bool
    {
        $setting = Setting::where('key', $key)->first();
        if (!$setting) {
            return false;
        }

        $type = $setting->type;
        $strValue = '';

        if ($type === 'boolean') {
            $strValue = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        } elseif ($type === 'json' || is_array($value)) {
            $strValue = is_array($value) ? json_encode($value) : (string)$value;
        } else {
            $strValue = (string)$value;
        }

        $setting->update([
            'value' => $strValue,
            'updated_by' => $updatedBy,
        ]);

        $this->clearCache();
        return true;
    }

    /**
     * Clear settings cache
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get settings grouped by group
     */
    public function getGroupedSettings(): \Illuminate\Support\Collection
    {
        return Setting::all()->groupBy('group');
    }

    /**
     * Cast value to its correct type
     */
    protected function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int)$value,
            'decimal' => (float)$value,
            'json' => $this->decodeJson($value),
            default => $value,
        };
    }

    /**
     * Safely decode JSON
     */
    protected function decodeJson(string $value): mixed
    {
        if (empty($value)) {
            return [];
        }
        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : [];
    }
}
