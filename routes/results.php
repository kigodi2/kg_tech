<?php

use App\Http\Controllers\Results\ResultsController;
use App\Http\Controllers\Results\GradingController;
use App\Http\Controllers\Results\ProcessingController;
use App\Http\Controllers\Results\ResultsManagementController;
use App\Http\Controllers\Results\LinkingController;
use App\Http\Controllers\Results\ReportsController;
use App\Http\Controllers\Results\AuditController;
use App\Http\Controllers\Api\Results\AcseeLifecycleApiController;
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

    // Decoupled PSLE Results Portal Route Group pointing to a dedicated Admin controller.
    // Preserves the existing reports routes exactly bound to ReportsController as required.
    Route::group(['prefix' => 'psle'], function () {
        Route::get('/', [\App\Http\Controllers\Admin\AdminPsleResultsController::class, 'legacy'])->name('results.psle.legacy');
        Route::get('/reports/school/{school}/export', [ReportsController::class, 'exportSchoolPdf'])->name("results.psle.reports.school-export");

        Route::group(['prefix' => 'reports', 'middleware' => ['admin']], function () {
            Route::get('/', [ReportsController::class, 'index'])->name("results.psle.reports.index");
            Route::get('/district-options', [ReportsController::class, 'districtOptions'])->name("results.psle.reports.district-options");
            Route::post('/district-school-results/export', [ReportsController::class, 'exportDistrictSchoolResults'])->name("results.psle.reports.district-school-results-export");
        });

        // Define route aliases/placeholders for the side menu in PSLE reports to prevent RouteNotFound exceptions
        Route::get('/grading', function () {
            return redirect()->route('results.psle.legacy', ['view' => 'overview']);
        })->name('results.psle.grading.index');

        Route::get('/processing', function () {
            return redirect()->route('results.psle.legacy', ['view' => 'processing']);
        })->name('results.psle.processing.index');

        Route::get('/results', function () {
            return redirect()->route('results.psle.legacy', ['view' => 'candidate-results']);
        })->name('results.psle.results.index');

        Route::get('/linking', function () {
            return redirect()->route('results.psle.legacy', ['view' => 'overview']);
        })->name('results.psle.linking.index');

        Route::get('/audit', function () {
            return redirect()->route('results.psle.legacy', ['view' => 'audit']);
        })->name('results.psle.audit.index');

        // Safe results processing actions managed by Admin
        Route::post('/processing/validate', [\App\Http\Controllers\Admin\AdminPsleResultsController::class, 'validateData'])->name('results.psle.processing.validate');
        Route::post('/processing/submit-lock', [\App\Http\Controllers\Admin\AdminPsleResultsController::class, 'submitAndLockRawMarks'])->name('results.psle.processing.submit-lock');
        Route::post('/processing/draft-run', [\App\Http\Controllers\Admin\AdminPsleResultsController::class, 'draftRun'])->name('results.psle.processing.draft-run');
        Route::post('/processing/final-run', [\App\Http\Controllers\Admin\AdminPsleResultsController::class, 'finalRun'])->name('results.psle.processing.final-run');
        Route::post('/processing/publish', [\App\Http\Controllers\Admin\AdminPsleResultsController::class, 'publishSnapshot'])->name('results.psle.processing.publish');
        Route::post('/processing/rollback', [\App\Http\Controllers\Admin\AdminPsleResultsController::class, 'rollback'])->name('results.psle.processing.rollback');
    });

    // Main yearly route: /results/{year}/psle
    Route::get('/{year}/psle', [\App\Http\Controllers\Admin\AdminPsleResultsController::class, 'index'])
        ->name('results.psle.dashboard')
        ->where('year', '[0-9]{4}');
});

Route::group(['prefix' => 'api/results/acsee', 'middleware' => ['auth']], function () {
    Route::get('/summary', [AcseeLifecycleApiController::class, 'summary']);
    Route::get('/review-dashboard', [AcseeLifecycleApiController::class, 'reviewDashboard']);
    Route::get('/exports/history', [AcseeLifecycleApiController::class, 'exportsHistory']);
    Route::get('/exports/readiness', [AcseeLifecycleApiController::class, 'exportsReadiness']);
    Route::post('/exports/download', [AcseeLifecycleApiController::class, 'exportDownload']);
    Route::post('/compute-validate/readiness', [AcseeLifecycleApiController::class, 'computeValidateReadiness']);
    Route::post('/compute-validate/run', [AcseeLifecycleApiController::class, 'computeValidateRun']);
    Route::get('/compute/processes', [AcseeLifecycleApiController::class, 'computeProcesses']);
    Route::get('/compute/processes/{id}', [AcseeLifecycleApiController::class, 'computeProcessShow']);
    Route::get('/snapshots', [AcseeLifecycleApiController::class, 'snapshots']);
    Route::post('/publish', [AcseeLifecycleApiController::class, 'publishSnapshot']);
    Route::post('/lock', [AcseeLifecycleApiController::class, 'lockSnapshot']);
    Route::post('/unlock', [AcseeLifecycleApiController::class, 'adminUnlock']);
    Route::post('/admin-unlock', [AcseeLifecycleApiController::class, 'adminUnlock']);
});
