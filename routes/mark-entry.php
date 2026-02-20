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
        Route::prefix('reports')->name('reports.')
            ->middleware('can:mark-entry.report')->group(function () {
            Route::get('scoresheet/{batchId}', [MarkEntryReportController::class, 'scoresheet'])->name('scoresheet');

            // Scoresheet PDF (filled marks)
            Route::get('scoresheet-pdf', [\App\Http\Controllers\MarkEntry\Reporting\ReportsController::class, 'scoresheetPdf'])->name('scoresheet-pdf');
            Route::get('scoresheet-pdf/school-zip', [\App\Http\Controllers\MarkEntry\Reporting\ReportsController::class, 'scoresheetSchoolZip'])->name('scoresheet-pdf.school-zip');
            Route::get('scoresheet-pdf/district-zip', [\App\Http\Controllers\MarkEntry\Reporting\ReportsController::class, 'scoresheetDistrictZip'])->name('scoresheet-pdf.district-zip');
            Route::get('scoresheet-pdf/region-zip', [\App\Http\Controllers\MarkEntry\Reporting\ReportsController::class, 'scoresheetRegionZip'])->name('scoresheet-pdf.region-zip');
            Route::get('scoresheet-subjects', [\App\Http\Controllers\MarkEntry\Reporting\ReportsController::class, 'scoresheetSubjects'])->name('scoresheet-subjects');

            // CSV Export
            Route::get('csv-export/school-subject', [\App\Http\Controllers\MarkEntry\Reporting\ReportsController::class, 'csvExportSchoolSubject'])->name('csv-export.school-subject');
            Route::get('csv-export/school-zip', [\App\Http\Controllers\MarkEntry\Reporting\ReportsController::class, 'csvExportSchoolZip'])->name('csv-export.school-zip');
            Route::get('csv-export/district-zip', [\App\Http\Controllers\MarkEntry\Reporting\ReportsController::class, 'csvExportDistrictZip'])->name('csv-export.district-zip');

            // Analytics API
            Route::get('analytics/completion', [\App\Http\Controllers\MarkEntry\Reporting\ReportsController::class, 'analyticsCompletion'])->name('analytics.completion');
            Route::get('analytics/distribution', [\App\Http\Controllers\MarkEntry\Reporting\ReportsController::class, 'analyticsDistribution'])->name('analytics.distribution');
            Route::get('analytics/anomalies', [\App\Http\Controllers\MarkEntry\Reporting\ReportsController::class, 'analyticsAnomalies'])->name('analytics.anomalies');
            Route::get('analytics/heatmap', [\App\Http\Controllers\MarkEntry\Reporting\ReportsController::class, 'analyticsHeatmap'])->name('analytics.heatmap');

            // Summary Report
            Route::get('summary', [\App\Http\Controllers\MarkEntry\Reporting\ReportsController::class, 'summaryReport'])->name('summary');
            Route::get('summary/pdf', [\App\Http\Controllers\MarkEntry\Reporting\ReportsController::class, 'summaryReportPdf'])->name('summary.pdf');

            // Diagnostics
            Route::get('diagnostics/marks-visibility', [\App\Http\Controllers\MarkEntry\Reporting\ReportsController::class, 'diagnostics'])->name('diagnostics.marks-visibility')->middleware('can:mark-entry.admin');
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

// ==================== MODERATION & REVIEW API ====================
Route::middleware(['web', 'auth'])->prefix('api/mark-entry/acsee/moderation')->group(function () {
    $ctrl = \App\Http\Controllers\MarkEntry\Api\ModerationApiController::class;

    Route::get('dashboard',       [$ctrl, 'dashboard']);
    Route::get('pending',         [$ctrl, 'pending']);
    Route::get('errors',          [$ctrl, 'errors']);
    Route::get('errors/csv',      [$ctrl, 'errorsCsv']);
    Route::post('approve',        [$ctrl, 'approve'])->middleware('can:mark-entry.moderate');
    Route::post('reject',         [$ctrl, 'reject'])->middleware('can:mark-entry.moderate');
    Route::get('rejections',      [$ctrl, 'rejections']);

    // INC Resolution endpoints (actionable MISSING_REQUIRED_PAPER_MARK issues)
    $incCtrl = \App\Http\Controllers\MarkEntry\Api\IncResolutionApiController::class;
    Route::post('issues/{issueId}/accept-inc', [$incCtrl, 'acceptInc'])->middleware('can:mark-entry.moderate');
    Route::post('issues/{issueId}/reject',     [$incCtrl, 'reject'])->middleware('can:mark-entry.moderate');
});

// ==================== LIFECYCLE API ENDPOINTS ====================
// These endpoints power the sidebar dashboard sections
Route::middleware(['web', 'auth'])->prefix('api/mark-entry')->group(function () {
    // Moderation endpoints
    Route::prefix('moderation')->name('moderation-api.')->middleware('can:mark-entry.moderate')->group(function () {
        Route::get('pending', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'getPendingBatches']);
        Route::get('batch/{batch}', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'getBatchModeration']);
        Route::get('batch/{batch}/raw-marks', [\App\Http\Controllers\MarkEntry\Api\MarkLifecycleApiController::class, 'getBatchRawMarks']);
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

// ==================== SUBMISSION & LOCKING API (state machine) ====================
Route::middleware(['web', 'auth'])->prefix('api/mark-entry/acsee')->group(function () {
    $ctrl = \App\Http\Controllers\MarkEntry\Api\MarkSubmissionLockingApiController::class;

    // Batch state transitions
    Route::post('batches/{batch}/submit',  [$ctrl, 'submit']);
    Route::post('batches/{batch}/approve', [$ctrl, 'approve']);
    Route::post('batches/{batch}/reject',  [$ctrl, 'reject']);
    Route::post('batches/{batch}/lock',    [$ctrl, 'lock']);
    Route::post('batches/{batch}/unlock',  [$ctrl, 'unlock']);

    // List / dashboard data
    Route::get('submission/batches',        [$ctrl, 'submissionBatches']);
    Route::get('locking/status',            [$ctrl, 'lockingStatus']);
    Route::get('history',                   [$ctrl, 'history']);
    Route::get('batches/{batch}/history',   [$ctrl, 'batchHistory']);
    Route::get('admin/locked-batches',      [$ctrl, 'lockedBatchesForAdmin'])->middleware('can:mark-entry.admin');
});

// ==================== ADMINISTRATION API ====================
Route::middleware(['web', 'auth'])->prefix('api/acsee/admin')->group(function () {
    $ctrl = \App\Http\Controllers\MarkEntry\Admin\MarkEntryAdminController::class;

    // Configuration
    Route::get('settings',                          [$ctrl, 'getSettings']);
    Route::post('settings/{key}',                   [$ctrl, 'updateSetting']);
    Route::get('settings/{key}/history',            [$ctrl, 'getSettingHistory']);
    Route::post('settings/restore/{historyId}',     [$ctrl, 'restoreSetting']);

    // Permissions
    Route::get('roles',                             [$ctrl, 'getRoles']);
    Route::post('roles/{role}/permissions',         [$ctrl, 'updateRolePermissions']);

    // Batch Management
    Route::get('batches',                           [$ctrl, 'getBatches']);
    Route::get('batches/{id}',                      [$ctrl, 'getBatchDetail']);
    Route::post('batches/{id}/unlock',              [$ctrl, 'unlockBatch']);
    Route::post('batches/{id}/recompute',           [$ctrl, 'recomputeBatchStats']);

    // System Logs
    Route::get('logs',                              [$ctrl, 'getLogs']);
    Route::get('logs/export.csv',                   [$ctrl, 'exportLogsCsv']);
});

// ==================== IMPORT RUNS API ====================
Route::middleware(['web', 'auth'])->prefix('api/mark-entry/acsee/import')->group(function () {
    $ctrl = \App\Http\Controllers\MarkEntry\Api\ImportRunApiController::class;

    Route::get('runs',                    [$ctrl, 'index']);
    Route::get('runs/{run}',              [$ctrl, 'show']);
    Route::get('runs/{run}/errors',       [$ctrl, 'errors']);
    Route::get('runs/{run}/errors.csv',   [$ctrl, 'errorsCsv']);
    Route::get('runs/{run}/preview',      [$ctrl, 'preview']);
});
