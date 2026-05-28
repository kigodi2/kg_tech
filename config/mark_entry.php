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
];

