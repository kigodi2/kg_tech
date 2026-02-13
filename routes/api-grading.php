<?php

use App\Http\Controllers\Grading\NectaGradingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| NECTA Grading API Routes
|--------------------------------------------------------------------------
|
| These routes provide API endpoints for grading operations
|
*/

Route::middleware(['auth:sanctum'])->prefix('grading')->group(function () {
    
    // Reference data endpoints
    Route::get('/reference', [NectaGradingController::class, 'apiGradeReference'])
        ->name('grading.reference');

    // Calculate single grade
    Route::post('/calculate-grade', [NectaGradingController::class, 'apiCalculateGrade'])
        ->name('grading.calculate-grade');

    // Candidate grades
    Route::get('/candidate/{candidate}/grades', [NectaGradingController::class, 'apiCandidateGrades'])
        ->name('grading.candidate-grades');

    Route::post('/candidate/{candidate}/store-grades', [NectaGradingController::class, 'storeGrades'])
        ->name('grading.store-grades');

    // School statistics
    Route::get('/school/statistics', [NectaGradingController::class, 'schoolGradingStats'])
        ->name('grading.school-stats');

    // Batch operations
    Route::post('/batch-process', [NectaGradingController::class, 'batchProcessGrades'])
        ->name('grading.batch-process');

    Route::post('/publish', [NectaGradingController::class, 'publishGrades'])
        ->name('grading.publish');

    Route::post('/lock', [NectaGradingController::class, 'lockGrades'])
        ->name('grading.lock');
});
