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
];
