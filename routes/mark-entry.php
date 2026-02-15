<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarkEntry\Entry\MarkEntryUploadController;
use App\Http\Controllers\MarkEntry\Entry\MarkEntryApiController;
use App\Http\Controllers\MarkEntry\Moderation\MarkEntryModerationController;
use App\Http\Controllers\MarkEntry\Submission\MarkEntrySubmissionController;
use App\Http\Controllers\MarkEntry\Reporting\MarkEntryReportController;
use App\Http\Controllers\MarkEntry\Audit\MarkEntryMonitoringController;
use App\Http\Controllers\MarkEntry\Admin\MarkEntryAdminController;

Route::middleware(['auth'])->prefix('mark-entry')->group(function () {

    Route::prefix('acsee')->name('mark-entry.acsee.')->group(function () {

        // ENTRY & VALIDATION PHASE
        Route::prefix('entry-validation')->name('entry-validation.')->group(function () {
            Route::get('/', [MarkEntryUploadController::class, 'index'])->name('index');
            Route::get('download-template', [MarkEntryUploadController::class, 'downloadTemplate'])->name('download-template')->middleware('can:mark-entry.upload');
            Route::post('upload', [MarkEntryUploadController::class, 'upload'])->name('upload')->middleware('can:mark-entry.upload');
            Route::get('batch/{batchId}', [MarkEntryUploadController::class, 'batchDetails'])->name('batch-details');
        });

        // MODERATION & REVIEW PHASE
        Route::prefix('moderation')->name('moderation.')
            ->middleware('can:mark-entry.moderate')->group(function () {
            Route::get('/', [MarkEntryModerationController::class, 'dashboard'])->name('dashboard');
            Route::get('batch/{batchId}', [MarkEntryModerationController::class, 'reviewBatch'])->name('review-batch');
            Route::post('batch/{batchId}/approve', [MarkEntryModerationController::class, 'approveBatch'])->name('approve');
            Route::post('batch/{batchId}/reject', [MarkEntryModerationController::class, 'rejectBatch'])->name('reject');
        });

        // SUBMISSION & LOCKING PHASE
        Route::prefix('submission')->name('submission.')
            ->middleware('can:mark-entry.lock')->group(function () {
            Route::get('/', [MarkEntrySubmissionController::class, 'dashboard'])->name('dashboard');
            Route::post('lock/{batchId}', [MarkEntrySubmissionController::class, 'lockBatch'])->name('lock');
        });

        // REPORTING PHASE
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('scoresheet/{batchId}', [MarkEntryReportController::class, 'scoresheet'])->name('scoresheet');
        });

        // MONITORING & AUDIT PHASE
        Route::prefix('monitoring')->name('monitoring.')
            ->middleware('can:mark-entry.audit')->group(function () {
            Route::get('/', [MarkEntryMonitoringController::class, 'lifecycleDashboard'])->name('dashboard');
            Route::get('audit-trail', [MarkEntryMonitoringController::class, 'auditTrail'])->name('audit-trail');
        });

        // SHARED API ENDPOINTS
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('regions', [MarkEntryApiController::class, 'regions'])->name('regions');
            Route::get('districts', [MarkEntryApiController::class, 'districts'])->name('districts');
            Route::get('schools', [MarkEntryApiController::class, 'schools'])->name('schools');
            Route::get('subjects', [MarkEntryApiController::class, 'subjects'])->name('subjects');
            Route::get('exam-years', [MarkEntryApiController::class, 'examYears'])->name('exam-years');
        });
    });
});

// ==================== LIFECYCLE API ENDPOINTS ====================
// These endpoints power the sidebar dashboard sections
Route::middleware(['web', 'auth'])->prefix('api/mark-entry')->group(function () {
    // Moderation endpoints
    Route::prefix('moderation')->name('moderation-api.')->middleware('can:mark-entry.moderate')->group(function () {
        Route::get('pending', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'getPendingBatches']);
        Route::get('batch/{batch}', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'getBatchModeration']);
        Route::get('search', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'searchPending']);
        Route::get('stats', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'getModeratorStats']);
        Route::post('batch/{batch}/approve', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'approveBatchAction']);
        Route::post('batch/{batch}/reject', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'rejectBatchAction']);
    });

    // Submission endpoints
    Route::prefix('submission')->name('submission-api.')->middleware('can:mark-entry.lock')->group(function () {
        Route::get('ready', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'getReadyForSubmission']);
        Route::get('submitted', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'getSubmitted']);
        Route::get('batch/{batch}/history', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'getSubmissionHistory']);
        Route::post('lock/{batchId}', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'lockBatchAction']);
        Route::post('unlock/{batchId}', [\App\Http\Controllers\MarkEntry\Api\UnlockBatchController::class, 'unlock']);
    });

    // Analytics endpoints
    Route::prefix('analytics')->name('analytics-api.')->group(function () {
        Route::get('overview', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'getAnalytics']);
        Route::get('by-year', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'getAnalyticsByYear']);
        Route::get('by-subject', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'getAnalyticsBySubject']);
        Route::get('by-school', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'getAnalyticsBySchool']);
        Route::get('errors', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'getErrorStats']);
        Route::get('batch/{batch}/timeline', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'getBatchTimeline']);
    });

    // Audit endpoints
    Route::prefix('audit')->name('audit-api.')->middleware('can:mark-entry.audit')->group(function () {
        Route::get('batch/{batch}', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'getBatchAuditTrail']);
        Route::get('user/{userId}', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'getUserActivity']);
        Route::get('batch/{batch}/summary', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'getBatchActivitySummary']);
        Route::get('batch/{batch}/modifications', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'getBatchModifications']);
    });
});
