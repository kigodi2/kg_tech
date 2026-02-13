<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsCollection;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'description'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get setting by key with type casting
     */
    public static function getSetting($key, $default = null)
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value
     */
    public static function setSetting($key, $value, $type = 'string', $description = null)
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => self::serializeValue($value),
                'type' => $type,
                'description' => $description,
            ]
        );
    }

    /**
     * Get all settings as key-value array
     */
    public static function allSettings()
    {
        $settings = [];
        foreach (self::all() as $setting) {
            $settings[$setting->key] = self::castValue($setting->value, $setting->type);
        }
        return $settings;
    }

    /**
     * Cast value based on type
     */
    private static function castValue($value, $type)
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => (bool) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Serialize value for storage
     */
    private static function serializeValue($value)
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        return (string) $value;
    }
}
