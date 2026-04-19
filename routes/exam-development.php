<?php

use App\Http\Controllers\ExamDevelopment\DashboardController;
use App\Http\Controllers\ExamDevelopment\ExportController;
use App\Http\Controllers\ExamDevelopment\FormatController;
use App\Http\Controllers\ExamDevelopment\MarkingSchemeController;
use App\Http\Controllers\ExamDevelopment\PracticalController;
use App\Http\Controllers\ExamDevelopment\ProjectController;
use App\Http\Controllers\ExamDevelopment\QuestionController;
use App\Http\Controllers\ExamDevelopment\ReviewApprovalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:exam-development.view'])
    ->prefix('exam-development')
    ->name('exam-development.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/formats', [FormatController::class, 'index'])->name('formats.index')->middleware('can:exam-development.manage-formats');
        Route::post('/formats', [FormatController::class, 'store'])->name('formats.store')->middleware('can:exam-development.manage-formats');
        Route::get('/formats/{format}', [FormatController::class, 'show'])->name('formats.show')->middleware('can:exam-development.manage-formats');
        Route::put('/formats/{format}', [FormatController::class, 'update'])->name('formats.update')->middleware('can:exam-development.manage-formats');
        Route::post('/formats/{format}/papers', [FormatController::class, 'storePaper'])->name('formats.papers.store')->middleware('can:exam-development.manage-formats');
        Route::post('/format-papers/{paper}/sections', [FormatController::class, 'storeSection'])->name('formats.sections.store')->middleware('can:exam-development.manage-formats');
        Route::post('/format-sections/{section}/rules', [FormatController::class, 'storeRule'])->name('formats.rules.store')->middleware('can:exam-development.manage-formats');
        Route::post('/formats/{format}/notes', [FormatController::class, 'storeNote'])->name('formats.notes.store')->middleware('can:exam-development.manage-formats');
        Route::post('/format-papers/{paper}/blueprints', [FormatController::class, 'storeBlueprint'])->name('formats.blueprints.store')->middleware('can:exam-development.manage-blueprints');
        Route::post('/blueprints/{blueprint}/topics', [FormatController::class, 'storeBlueprintTopic'])->name('formats.blueprint-topics.store')->middleware('can:exam-development.manage-blueprints');

        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index')->middleware('can:exam-development.edit-project');
        Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create')->middleware('can:exam-development.create-project');
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store')->middleware('can:exam-development.create-project');
        Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show')->middleware('can:exam-development.edit-project');
        Route::post('/projects/{project}/validate', [ProjectController::class, 'validateProject'])->name('projects.validate')->middleware('can:exam-development.edit-project');
        Route::get('/project-papers/{paper}/builder', [ProjectController::class, 'paperBuilder'])->name('projects.papers.builder')->middleware('can:exam-development.assign-questions');
        Route::post('/project-slots/{slot}/assign', [ProjectController::class, 'assignQuestion'])->name('projects.slots.assign')->middleware('can:exam-development.assign-questions');

        Route::get('/questions', [QuestionController::class, 'index'])->name('questions.index')->middleware('can:exam-development.manage-questions');
        Route::get('/questions/create', [QuestionController::class, 'create'])->name('questions.create')->middleware('can:exam-development.manage-questions');
        Route::post('/questions', [QuestionController::class, 'store'])->name('questions.store')->middleware('can:exam-development.manage-questions');
        Route::get('/questions/{question}/edit', [QuestionController::class, 'edit'])->name('questions.edit')->middleware('can:exam-development.manage-questions');
        Route::put('/questions/{question}', [QuestionController::class, 'update'])->name('questions.update')->middleware('can:exam-development.manage-questions');

        Route::get('/marking-schemes/{question}', [MarkingSchemeController::class, 'show'])->name('marking-schemes.show')->middleware('can:exam-development.review-questions');
        Route::post('/marking-schemes/{question}', [MarkingSchemeController::class, 'store'])->name('marking-schemes.store')->middleware('can:exam-development.review-questions');

        Route::get('/practical/{paper}', [PracticalController::class, 'show'])->name('practical.show')->middleware('can:exam-development.manage-practical');
        Route::post('/practical/{paper}', [PracticalController::class, 'update'])->name('practical.update')->middleware('can:exam-development.manage-practical');

        Route::get('/review/{project}', [ReviewApprovalController::class, 'show'])->name('review.show')->middleware('can:exam-development.review-questions');
        Route::post('/review/{project}/comments', [ReviewApprovalController::class, 'storeComment'])->name('review.comments.store')->middleware('can:exam-development.review-questions');
        Route::post('/review/projects/{project}/transition', [ReviewApprovalController::class, 'transitionProject'])->name('review.projects.transition')->middleware('can:exam-development.approve-paper');
        Route::post('/review/papers/{paper}/transition', [ReviewApprovalController::class, 'transitionPaper'])->name('review.papers.transition')->middleware('can:exam-development.approve-paper');
        Route::post('/review/questions/{question}/transition', [ReviewApprovalController::class, 'transitionQuestion'])->name('review.questions.transition')->middleware('can:exam-development.approve-questions');

        Route::get('/exports/{project}', [ExportController::class, 'show'])->name('exports.show')->middleware('can:exam-development.export-paper');
        Route::get('/exports/papers/{paper}/{variant}', [ExportController::class, 'download'])->name('exports.download')->middleware('can:exam-development.export-paper');
    });
