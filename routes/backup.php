<?php

use App\Http\Controllers\BackupController;
use Illuminate\Support\Facades\Route;

/**
 * Backup & Restore API Routes
 * 
 * All endpoints require authentication and super_admin authorization
 * Base URL: /api/backups
 * 
 * Use:
 *   Route::apiResource('backups', BackupController::class);
 *   require_once 'backup.php';
 */

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // Backup operations
    Route::get('/status', [BackupController::class, 'status'])
        ->name('backups.status');

    Route::post('/create', [BackupController::class, 'create'])
        ->name('backups.create');

    Route::get('/logs', [BackupController::class, 'logs'])
        ->name('backups.logs');

    // Restore operations
    Route::post('/validate', [BackupController::class, 'validateBackup'])
        ->name('backups.validate');

    Route::post('/simulate-restore', [BackupController::class, 'simulateRestore'])
        ->name('backups.simulate-restore');

    Route::post('/restore', [BackupController::class, 'restore'])
        ->name('backups.restore');

    // Metrics
    Route::get('/health-metrics', [BackupController::class, 'healthMetrics'])
        ->name('backups.health-metrics');
});
