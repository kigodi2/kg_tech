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
    
    // ACSEE Results Module
    Route::group(['prefix' => 'acsee'], function () {
        
        // Dashboard
        Route::get('/', [ResultsController::class, 'dashboard'])->name('results.acsee.dashboard');
        
        // SECTION A: CONFIGURATION - Grading System
        Route::group(['prefix' => 'grading'], function () {
            Route::get('/', [GradingController::class, 'index'])->name('results.acsee.grading.index');
            Route::get('/create', [GradingController::class, 'create'])->name('results.acsee.grading.create');
            Route::get('/{id}', [GradingController::class, 'show'])->name('results.acsee.grading.show');
            Route::get('/{id}/edit', [GradingController::class, 'edit'])->name('results.acsee.grading.edit');
            Route::post('/', [GradingController::class, 'store'])->name('results.acsee.grading.store');
            Route::patch('/{id}', [GradingController::class, 'update'])->name('results.acsee.grading.update');
            Route::post('/{id}/lock', [GradingController::class, 'lock'])->name('results.acsee.grading.lock');
            Route::delete('/{id}', [GradingController::class, 'destroy'])->name('results.acsee.grading.destroy');
            
            // API endpoints for grade calculation preview
            Route::post('/api/preview', [GradingController::class, 'previewGrade'])->name('results.acsee.grading.preview');
        });
        
        // SECTION B: RESULT PROCESSING
        Route::group(['prefix' => 'processing'], function () {
            Route::get('/', [ProcessingController::class, 'index'])->name('results.acsee.processing.index');
            Route::post('/validate', [ProcessingController::class, 'validate'])->name('results.acsee.processing.validate');
            Route::post('/draft-run', [ProcessingController::class, 'draftRun'])->name('results.acsee.processing.draft-run');
            Route::post('/final-run', [ProcessingController::class, 'finalRun'])->name('results.acsee.processing.final-run');
            Route::get('/status/{batchId}', [ProcessingController::class, 'status'])->name('results.acsee.processing.status');
            Route::post('/{batchId}/rollback', [ProcessingController::class, 'rollback'])->name('results.acsee.processing.rollback');
        });
        
        // SECTION C: RESULTS MANAGEMENT
        Route::group(['prefix' => 'results'], function () {
            Route::get('/', [ResultsManagementController::class, 'index'])->name('results.acsee.results.index');
            Route::get('/candidate/{candidateId}', [ResultsManagementController::class, 'candidateResult'])->name('results.acsee.results.candidate');
            Route::get('/school/{schoolId}', [ResultsManagementController::class, 'schoolResults'])->name('results.acsee.results.school');
            Route::get('/combination/{combinationId}', [ResultsManagementController::class, 'combinationResults'])->name('results.acsee.results.combination');
            Route::post('/{id}/publish', [ResultsManagementController::class, 'publish'])->name('results.acsee.results.publish');
            Route::post('/{id}/unpublish', [ResultsManagementController::class, 'unpublish'])->name('results.acsee.results.unpublish');
        });
        
        // Result Linking (Pre-processing Validation)
        Route::group(['prefix' => 'linking'], function () {
            Route::get('/', [LinkingController::class, 'index'])->name('results.acsee.linking.index');
            Route::post('/validate', [LinkingController::class, 'validate'])->name('results.acsee.linking.validate');
            Route::post('/fix-missing', [LinkingController::class, 'fixMissing'])->name('results.acsee.linking.fix-missing');
            Route::get('/report', [LinkingController::class, 'report'])->name('results.acsee.linking.report');
        });
        
        // SECTION D: OUTPUT & COMMUNICATION - Reports
        Route::group(['prefix' => 'reports'], function () {
            Route::get('/', [ReportsController::class, 'index'])->name('results.acsee.reports.index');
            Route::get('/school-summary', [ReportsController::class, 'schoolSummary'])->name('results.acsee.reports.school-summary');
            Route::get('/council-performance', [ReportsController::class, 'councilPerformance'])->name('results.acsee.reports.council-performance');
            Route::get('/subject-analysis', [ReportsController::class, 'subjectAnalysis'])->name('results.acsee.reports.subject-analysis');
            Route::get('/combination-performance', [ReportsController::class, 'combinationPerformance'])->name('results.acsee.reports.combination-performance');
            Route::get('/gpa-distribution', [ReportsController::class, 'gpaDistribution'])->name('results.acsee.reports.gpa-distribution');
            Route::get('/grade-distribution', [ReportsController::class, 'gradeDistribution'])->name('results.acsee.reports.grade-distribution');
            
            // Export routes
            Route::post('/school-summary/export', [ReportsController::class, 'exportSchoolSummary'])->name('results.acsee.reports.school-summary-export');
            Route::post('/council-performance/export', [ReportsController::class, 'exportCouncilPerformance'])->name('results.acsee.reports.council-performance-export');
            Route::post('/subject-analysis/export', [ReportsController::class, 'exportSubjectAnalysis'])->name('results.acsee.reports.subject-analysis-export');
        });
        
        // SECTION E: GOVERNANCE & AUDIT
        Route::group(['prefix' => 'audit'], function () {
            Route::get('/', [AuditController::class, 'index'])->name('results.acsee.audit.index');
            Route::get('/logs', [AuditController::class, 'logs'])->name('results.acsee.audit.logs');
            Route::get('/processing-history', [AuditController::class, 'processingHistory'])->name('results.acsee.audit.processing-history');
            Route::get('/publication-history', [AuditController::class, 'publicationHistory'])->name('results.acsee.audit.publication-history');
            Route::get('/export', [AuditController::class, 'exportLogs'])->name('results.acsee.audit.export');
        });
    });
    
    // CSEE Results Module (Future)
    // Route::group(['prefix' => 'csee'], function () { ... });
    
    // FTNA Results Module (Future)
    // Route::group(['prefix' => 'ftna'], function () { ... });
});
