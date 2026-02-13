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
