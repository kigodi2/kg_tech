<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Single Device Login Inactivity Timeout
    |--------------------------------------------------------------------------
    |
    | Define the timeout in minutes after which an inactive Mark Entry Officer
    | session can be taken over or replaced by a new login session.
    |
    */
    'single_device_timeout_minutes' => (int) env('MARK_ENTRY_SINGLE_DEVICE_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Enable Single Device Login Restriction
    |--------------------------------------------------------------------------
    |
    | Set to true to restrict a Mark Entry Officer to a single active device.
    | By default, this is disabled in testing environments to prevent session
    | isolation issues in standard integration tests.
    |
    */
    'enable_single_device_restriction' => (bool) env('MARK_ENTRY_SINGLE_DEVICE_RESTRICTION', env('APP_ENV') !== 'testing'),

    /*
    |--------------------------------------------------------------------------
    | Location Geofencing Restriction
    |--------------------------------------------------------------------------
    |
    | Define whether geofencing is enabled, the default allowable radius, the
    | maximum allowed browser location accuracy, and the recheck frequency.
    |
    */
    'geofence_enabled' => (bool) env('MARK_ENTRY_GEOFENCE_ENABLED', true),
    'default_radius_meters' => (int) env('MARK_ENTRY_GEOFENCE_RADIUS_METERS', 50),
    'max_location_accuracy_meters' => (int) env('MARK_ENTRY_MAX_LOCATION_ACCURACY_METERS', 100),
    'location_recheck_minutes' => (int) env('MARK_ENTRY_LOCATION_RECHECK_MINUTES', 10),
];

