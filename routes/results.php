<?php

use App\Http\Controllers\Results\ResultsController;
use App\Http\Controllers\Results\GradingController;
use App\Http\Controllers\Results\ProcessingController;
use App\Http\Controllers\Results\ResultsManagementController;
use App\Http\Controllers\Results\LinkingController;
use App\Http\Controllers\Results\ReportsController;
use App\Http\Controllers\Results\AuditController;
use Illuminate\Support\Facades\Route;

/**
 * Results Module Routes
 * 
 * Complete ACSEE results management system with processing,
 * grading, reporting, and audit capabilities.
 */

Route::group(['prefix' => 'results', 'middleware' => ['auth']], function () {
    $registerResultsModule = function (string $prefix, string $namePrefix): void {
        Route::group(['prefix' => $prefix], function () use ($namePrefix) {
            Route::get('/', [ResultsController::class, 'dashboard'])->name("{$namePrefix}.dashboard");

            Route::group(['prefix' => 'grading'], function () use ($namePrefix) {
                Route::get('/', [GradingController::class, 'index'])->name("{$namePrefix}.grading.index");
                Route::get('/create', [GradingController::class, 'create'])->name("{$namePrefix}.grading.create");
                Route::get('/{id}', [GradingController::class, 'show'])->name("{$namePrefix}.grading.show");
                Route::get('/{id}/edit', [GradingController::class, 'edit'])->name("{$namePrefix}.grading.edit");
                Route::post('/', [GradingController::class, 'store'])->name("{$namePrefix}.grading.store");
                Route::patch('/{id}', [GradingController::class, 'update'])->name("{$namePrefix}.grading.update");
                Route::post('/{id}/lock', [GradingController::class, 'lock'])->name("{$namePrefix}.grading.lock");
                Route::delete('/{id}', [GradingController::class, 'destroy'])->name("{$namePrefix}.grading.destroy");
                Route::post('/api/preview', [GradingController::class, 'previewGrade'])->name("{$namePrefix}.grading.preview");
            });

            Route::group(['prefix' => 'processing'], function () use ($namePrefix) {
                Route::get('/', [ProcessingController::class, 'index'])->name("{$namePrefix}.processing.index");
                Route::post('/validate', [ProcessingController::class, 'validate'])->name("{$namePrefix}.processing.validate");
                Route::post('/draft-run', [ProcessingController::class, 'draftRun'])->name("{$namePrefix}.processing.draft-run");
                Route::post('/final-run', [ProcessingController::class, 'finalRun'])->name("{$namePrefix}.processing.final-run");
                Route::get('/status/{batchId}', [ProcessingController::class, 'status'])->name("{$namePrefix}.processing.status");
                Route::post('/{batchId}/rollback', [ProcessingController::class, 'rollback'])->name("{$namePrefix}.processing.rollback");
            });

            Route::group(['prefix' => 'results'], function () use ($namePrefix) {
                Route::get('/', [ResultsManagementController::class, 'index'])->name("{$namePrefix}.results.index");
                Route::get('/candidate/{candidateId}', [ResultsManagementController::class, 'candidateResult'])->name("{$namePrefix}.results.candidate");
                Route::get('/school/{schoolId}', [ResultsManagementController::class, 'schoolResults'])->name("{$namePrefix}.results.school");
                Route::get('/combination/{combinationId}', [ResultsManagementController::class, 'combinationResults'])->name("{$namePrefix}.results.combination");
                Route::post('/{id}/publish', [ResultsManagementController::class, 'publish'])->name("{$namePrefix}.results.publish");
                Route::post('/{id}/unpublish', [ResultsManagementController::class, 'unpublish'])->name("{$namePrefix}.results.unpublish");
            });

            Route::group(['prefix' => 'linking'], function () use ($namePrefix) {
                Route::get('/', [LinkingController::class, 'index'])->name("{$namePrefix}.linking.index");
                Route::post('/validate', [LinkingController::class, 'validate'])->name("{$namePrefix}.linking.validate");
                Route::post('/fix-missing', [LinkingController::class, 'fixMissing'])->name("{$namePrefix}.linking.fix-missing");
                Route::get('/report', [LinkingController::class, 'report'])->name("{$namePrefix}.linking.report");
            });

            Route::group(['prefix' => 'reports'], function () use ($namePrefix) {
                Route::get('/', [ReportsController::class, 'index'])->name("{$namePrefix}.reports.index");
                Route::get('/district-options', [ReportsController::class, 'districtOptions'])->name("{$namePrefix}.reports.district-options");
                Route::post('/district-school-results/export', [ReportsController::class, 'exportDistrictSchoolResults'])->name("{$namePrefix}.reports.district-school-results-export");
            });

            Route::group(['prefix' => 'audit'], function () use ($namePrefix) {
                Route::get('/', [AuditController::class, 'index'])->name("{$namePrefix}.audit.index");
                Route::get('/logs', [AuditController::class, 'logs'])->name("{$namePrefix}.audit.logs");
                Route::get('/processing-history', [AuditController::class, 'processingHistory'])->name("{$namePrefix}.audit.processing-history");
                Route::get('/publication-history', [AuditController::class, 'publicationHistory'])->name("{$namePrefix}.audit.publication-history");
                Route::get('/export', [AuditController::class, 'exportLogs'])->name("{$namePrefix}.audit.export");
            });
        });
    };

    $registerResultsModule('acsee', 'results.acsee');
    $registerResultsModule('psle', 'results.psle');

    // CSEE Results Module (Future)
    // Route::group(['prefix' => 'csee'], function () { ... });
    
    // FTNA Results Module (Future)
    // Route::group(['prefix' => 'ftna'], function () { ... });
});
