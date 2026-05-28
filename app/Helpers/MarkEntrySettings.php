<?php

namespace App\Helpers;

class MarkEntrySettings
{
    /**
     * Check if geofencing location restriction is enabled.
     *
     * @return bool
     */
    public static function geofenceEnabled(): bool
    {
        return (bool) SystemSettingsHelper::get('mark_entry_geofence_enabled', config('mark_entry.geofence_enabled', true));
    }

    /**
     * Enable or disable geofencing location restriction.
     *
     * @param bool $enabled
     * @return void
     */
    public static function setGeofenceEnabled(bool $enabled): void
    {
        SystemSettingsHelper::set('mark_entry_geofence_enabled', $enabled, 'boolean', 'Enable or disable geofencing location restriction for Mark Entry Officers');
    }
}
