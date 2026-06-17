<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\ExamTypeController;
use App\Http\Controllers\MarkEntryController;
use App\Http\Controllers\ExamYearController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\BackupManagementController;
use App\Http\Controllers\BackupRestoreController;
use App\Http\Controllers\PublicPsleResultsController;
use App\Http\Controllers\PsleEvaluationsController;
use App\Http\Controllers\Results\AcseeResultsController;
use App\Http\Controllers\PublicResultsController;
use App\Http\Controllers\PublicResultsPortalController;
use App\Http\Controllers\DistrictCandidateImportController;
use App\Http\Controllers\CandidateImportController;
use App\Http\Controllers\AcseeAllocationController;
use App\Http\Controllers\Admin\SubjectPaperWeightController;
use App\Services\Candidates\CseeCandidateSubjectService;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GithubAuthController;

Route::get('/', function () {
    return auth()->check()
        ? redirect('/admin/dashboard')
        : redirect()->route('public.home');
});

Route::get('/dev-login', function () {
    $user = \App\Models\User::where('email', 'agreykigodi@gmail.com')->first();
    auth()->login($user);
    return redirect('/admin/dashboard');
});

Route::get('/session-heartbeat', function () {
    return response()->noContent();
})->name('session.heartbeat');

// Auth routes
Route::middleware('guest')->group(function () {
    Route::view('/home', 'auth.home')->name('public.home');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/check-admin-email', [AuthController::class, 'checkAdminEmail'])->name('auth.check-admin-email');
    Route::get('/auth/github', [GithubAuthController::class, 'redirect'])->name('auth.github.redirect');
    Route::get('/auth/github/callback', [GithubAuthController::class, 'callback'])->name('auth.github.callback');
});

Route::get('/r/{token}', [PublicResultsPortalController::class, 'index'])
    ->name('public.results.portal');

Route::get('/r/{token}/file/{item}', [PublicResultsPortalController::class, 'download'])
    ->name('public.results.portal.file');

Route::get('/r/{tokenPath}', [PublicResultsPortalController::class, 'indexFromPath'])
    ->where('tokenPath', '.*')
    ->name('public.results.portal.normalized');

Route::get('/results/{examYear}/psle', [PublicPsleResultsController::class, 'regions'])
    ->where(['examYear' => '[0-9]{4}'])
    ->name('public.results.psle.regions');

Route::get('/results/{examYear}/psle/regions/{region}', [PublicPsleResultsController::class, 'districts'])
    ->where(['examYear' => '[0-9]{4}'])
    ->name('public.results.psle.districts');

Route::get('/results/{examYear}/psle/regions/{region}/districts/{district}', [PublicPsleResultsController::class, 'schools'])
    ->where(['examYear' => '[0-9]{4}'])
    ->name('public.results.psle.schools');

Route::get('/results/{examYear}/psle/regions/{region}/districts/{district}/schools/{school}', [PublicPsleResultsController::class, 'schoolResults'])
    ->where(['examYear' => '[0-9]{4}'])
    ->name('public.results.psle.school');

Route::get('/results/{examYear}/{examType}', [PublicResultsPortalController::class, 'landing'])
    ->where(['examYear' => '[0-9]{4}', 'examType' => '[A-Za-z0-9_-]+'])
    ->name('public.results');

Route::post('/api/public-results', [PublicResultsController::class, 'search'])->name('public.results.search');
Route::get('/results/{examYear}/{examType}/candidate/{candidateId}', [PublicResultsController::class, 'candidate'])
    ->where(['examYear' => '[0-9]{4}', 'examType' => '[A-Za-z0-9_-]+'])
    ->name('public.results.candidate');
Route::get('/results/{examYear}/{examType}/school/{schoolId}', [PublicResultsController::class, 'school'])
    ->where(['examYear' => '[0-9]{4}', 'examType' => '[A-Za-z0-9_-]+'])
    ->name('public.results.school');

// Forced password change on first login
Route::get('/password/change-required', [PasswordChangeController::class, 'showChangeRequired'])->name('password.change-required')->middleware('auth');
Route::post('/password/update-required', [PasswordChangeController::class, 'updateRequired'])->name('password.update-required')->middleware('auth');

// Backup Management Routes (Admin only)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/backups', [BackupManagementController::class, 'index'])->name('backups.index');
    Route::post('/admin/backups/create', [BackupManagementController::class, 'create'])->name('backups.create');
    Route::delete('/admin/backups/{id}', [BackupManagementController::class, 'delete'])->name('backups.delete');
    Route::get('/admin/subject-paper-weights', [SubjectPaperWeightController::class, 'page'])->name('admin.subject-paper-weights');
    
    // Backup Restore Routes
    Route::get('/backups/{id}/restore', [BackupRestoreController::class, 'showRestoreForm'])->name('backup.restore-form');
    Route::post('/backups/{id}/restore', [BackupRestoreController::class, 'executeRestore'])->name('backup.execute-restore');
});

// Protected routes
// Test Seeding API (for E2E tests only - disabled in production)
if (config('app.env') === 'testing' || config('app.debug')) {
    Route::post('/api/test-seed/user', function (\Illuminate\Http\Request $request) {
        $email = $request->input('email', 'admin@test.com');
        $password = $request->input('password', 'password');
        
        \App\Models\User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Test User',
                'password' => \Illuminate\Support\Facades\Hash::make($password),
                'status' => 'active',
            ]
        );
        
        return response()->json(['message' => 'User seeded']);
    });
}

Route::middleware(['auth', 'main-system', 'single-device', 'geofence'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/exam/ACSEE', [DashboardController::class, 'acseeExam'])->name('dashboard.exam.acsee');
        
        // Exam Submission Routes
        Route::middleware('exam-admin-access')->group(function () {
            Route::get('/exam-submissions/final-report', [\App\Http\Controllers\ExamSubmissionController::class, 'finalReport'])
                ->name('exam-submissions.final-report');
            Route::resource('exam-submissions', \App\Http\Controllers\ExamSubmissionController::class)->except(['edit', 'update', 'destroy']);
            Route::get('/exam-submissions/{exam_submission}/download', [\App\Http\Controllers\ExamSubmissionController::class, 'download'])->name('exam-submissions.download');
            Route::post('/exam-submissions/{exam_submission}/approve', [\App\Http\Controllers\ExamSubmissionController::class, 'approve'])
                ->name('exam-submissions.approve')
                ->middleware('admin');
            Route::post('/exam-submissions/{exam_submission}/reject', [\App\Http\Controllers\ExamSubmissionController::class, 'reject'])
                ->name('exam-submissions.reject')
                ->middleware('admin');
            Route::post('/exam-submissions/validate-format', [\App\Http\Controllers\ExamSubmissionController::class, 'validateFormat'])->name('exam-submissions.validate-format');
            Route::get('/exam-submissions/subjects/{exam_type_id}', [\App\Http\Controllers\ExamSubmissionController::class, 'getSubjects'])->name('exam-submissions.subjects');
        });
        
        // Format PDF Routes for ACSEE and FTNA (similar to CSEE)
        Route::get('/exam-types/acsee/formats/pdf', function (\Illuminate\Http\Request $request) {
            $pdfPath = (string) config('acsee.formats_pdf_path', base_path('ACSEE_FORMATS_2026.pdf'));
            $filename = (string) config('acsee.formats_pdf_filename', 'acsee_formats_2026.pdf');
            $nectaPublications = 'https://www.necta.go.tz/publications/all';

            if (!is_file($pdfPath)) {
                return redirect()->away($nectaPublications);
            }

            $response = response()->file($pdfPath, [
                'Content-Type' => 'application/pdf',
            ]);

            if (strtolower((string) $request->query('disposition', 'inline')) !== 'inline') {
                $response->setContentDisposition(
                    \Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    $filename
                );
            }

            return $response;
        })->name('exam-types.acsee.formats.pdf');
        
        Route::get('/exam-types/ftna/formats/pdf', function (\Illuminate\Http\Request $request) {
            $stream = strtolower((string) $request->query('stream', 'general'));
            $paths = config('ftna.formats_pdf_paths', []);
            $nectaPublications = 'https://www.necta.go.tz/publications/all';

            if (!in_array($stream, ['general', 'vocational'])) {
                $stream = 'general';
            }

            $pdfPath = $paths[$stream] ?? config('ftna.formats_pdf_path', base_path('FTNA_FORMATS_VOCATIONAL_STREAM_2025.pdf'));
            $filename = $stream === 'vocational'
                ? 'ftna_formats_vocational_stream_2025.pdf'
                : (string) config('ftna.formats_pdf_filename', 'ftna_formats_vocational_stream_2025.pdf');

            if (!is_file($pdfPath)) {
                // If the selected stream is missing, attempt to fall back to the general format first.
                if ($stream === 'vocational' && isset($paths['general']) && is_file($paths['general'])) {
                    $pdfPath = $paths['general'];
                    $filename = (string) config('ftna.formats_pdf_filename', 'ftna_formats_vocational_stream_2025.pdf');
                } else {
                    return redirect()->away($nectaPublications);
                }
            }

            $response = response()->file($pdfPath, [
                'Content-Type' => 'application/pdf',
            ]);

            if (strtolower((string) $request->query('disposition', 'inline')) !== 'inline') {
                $response->setContentDisposition(
                    \Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    $filename
                );
            }

            return $response;
        })->name('exam-types.ftna.formats.pdf');

    // Exam Types moved to Admin group
    Route::get('/exam-types/csee/formats/pdf', function (\Illuminate\Http\Request $request) {
        $pdfPath = (string) config('csee.formats_pdf_path', base_path('CSEE_FORMATS_2022.pdf'));
        $filename = (string) config('csee.formats_pdf_filename', 'csee_formats_2022.pdf');
        $nectaPublications = 'https://www.necta.go.tz/publications/all';

        if (!is_file($pdfPath)) {
            return redirect()->away($nectaPublications);
        }

        $response = response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
        ]);

        if (strtolower((string) $request->query('disposition', 'inline')) !== 'inline') {
            $response->setContentDisposition(
                \Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename
            );
        }

        return $response;
    })->name('exam-types.csee.formats.pdf');
    Route::get('/exam-types/psle/timetable/pdf', function (\Illuminate\Http\Request $request) {
        $timetableConfig = config('psle.timetable', []);
        $filename = (string) ($timetableConfig['download_filename'] ?? 'psle_zonal_timetable_may_2026_a3_portrait.pdf');
        $sourceDir = rtrim((string) ($timetableConfig['source_dir'] ?? ''), DIRECTORY_SEPARATOR);
        $texFile = $sourceDir . DIRECTORY_SEPARATOR . (string) ($timetableConfig['source_tex'] ?? '');
        $pdfFile = $sourceDir . DIRECTORY_SEPARATOR . (string) ($timetableConfig['source_pdf'] ?? '');

        if ($sourceDir === '' || !is_dir($sourceDir)) {
            abort(500, 'PSLE timetable source directory is not configured correctly.');
        }

        if (!is_file($texFile)) {
            abort(404, 'PSLE timetable LaTeX source file was not found.');
        }

        $needsCompile = !is_file($pdfFile) || filemtime($pdfFile) < filemtime($texFile);

        if ($needsCompile) {
            $command = 'cd ' . escapeshellarg($sourceDir)
                . ' && pdflatex -interaction=nonstopmode -halt-on-error '
                . escapeshellarg(basename($texFile));

            exec($command . ' 2>&1', $output, $exitCode);

            if ($exitCode !== 0 || !is_file($pdfFile)) {
                abort(500, 'Failed to compile the PSLE timetable LaTeX PDF.');
            }
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'psle_timetable_');
        if ($tempPath === false) {
            abort(500, 'Unable to prepare PDF export file.');
        }

        $pdfPath = $tempPath . '.pdf';
        @rename($tempPath, $pdfPath);

        if (!copy($pdfFile, $pdfPath)) {
            abort(500, 'Unable to stage the PSLE timetable PDF.');
        }

        $response = response()->download($pdfPath, $filename, [
            'Content-Type' => 'application/pdf',
        ]);

        if (strtolower((string) $request->query('disposition', 'inline')) === 'inline') {
            $response->setContentDisposition(
                \Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_INLINE,
                $filename
            );
        }

        return $response->deleteFileAfterSend(true);
    });
    Route::get('/exam-types/{code}', function ($code) { 
        return view('exam-types.show', ['code' => $code]); 
    });
    
    // Extremity Analysis API Routes
    Route::prefix('api/extremity')->middleware(['auth', 'admin'])->group(function () {
        Route::post('analyze', [\App\Http\Controllers\Admin\CandidateExtremityController::class, 'analyze']);
        Route::get('dashboard', [\App\Http\Controllers\Admin\CandidateExtremityController::class, 'dashboard']);
        Route::get('report/{report}', [\App\Http\Controllers\Admin\CandidateExtremityController::class, 'show']);
        Route::post('report/{report}/mark-reviewed', [\App\Http\Controllers\Admin\CandidateExtremityController::class, 'markReviewed']);
        Route::get('export', [\App\Http\Controllers\Admin\CandidateExtremityController::class, 'export']);
    });

    Route::prefix('api/admin/subject-paper-weights')->middleware(['auth', 'admin'])->group(function () {
        Route::get('/subjects', [SubjectPaperWeightController::class, 'subjects']);
        Route::get('/', [SubjectPaperWeightController::class, 'index']);
        Route::post('/', [SubjectPaperWeightController::class, 'store']);
        Route::put('/{id}', [SubjectPaperWeightController::class, 'update']);
        Route::delete('/{id}', [SubjectPaperWeightController::class, 'destroy']);
    });
    
    // Evaluations Routes
    Route::get('/evaluations', function () { 
        return view('evaluations.index'); 
    });
    Route::get('/evaluations/acsee', function () { 
        return view('evaluations.acsee'); 
    });
    Route::get('/evaluations/psle', [PsleEvaluationsController::class, 'index'])
        ->name('evaluations.psle.index');
    Route::get('/evaluations/psle/zonalwise', [PsleEvaluationsController::class, 'zonalwise'])
        ->name('evaluations.psle.zonalwise');
    Route::get('/evaluations/psle/regionalwise', [PsleEvaluationsController::class, 'regionalwise'])
        ->name('evaluations.psle.regionalwise');
    Route::get('/evaluations/psle/regionalwise/{region}', [PsleEvaluationsController::class, 'regionalwiseRegion'])
        ->name('evaluations.psle.regionalwise.region');
    Route::get('/evaluations/psle/regionalwise/{region}/evaluation/{evaluation}', [PsleEvaluationsController::class, 'regionalwiseEvaluation'])
        ->name('evaluations.psle.regionalwise.region.evaluation');
    Route::get('/evaluations/psle/regionalwise/{region}/evaluation/{evaluation}/export/{format}', [PsleEvaluationsController::class, 'regionalwiseEvaluationExport'])
        ->name('evaluations.psle.regionalwise.region.evaluation.export');
    Route::get('/evaluations/acsee/zonalwise', function () {
        $activeYear = \App\Models\ExamYear::query()->where('is_active', true)->first();
        $examYearValue = (int) ($activeYear->year_label ?? now()->year);

        $entries = collect([
            'ZONAL GENERAL EVALUATION',
            'ZONAL COUNCILWISE EVALUATION',
            'ZONAL SCHOOLWISE EVALUATION',
            'ZONAL DISTRICTWISE EVALUATION',
            'ZONAL BEST TEN (10) COUNCILS',
            'ZONAL LEAST TEN (10) COUNCILS',
            'ZONAL BEST TEN (10) SCHOOLS',
            'ZONAL LEAST TEN (10) SCHOOLS',
            'ZONAL BEST TEN (10) GIRLS',
            'ZONAL LEAST TEN (10) GIRLS',
            'ZONAL BEST TEN (10) BOYS',
            'ZONAL LEAST TEN (10) BOYS',
            'ZONAL OVERALL TEN (10) BEST STUDENTS',
            'ZONAL OVERALL TEN (10) LEAST STUDENTS',
            'ZONAL GOVERNMENT SCHOOLS',
            'ZONAL NON-GOVERNMENT SCHOOLS',
            'ZONAL OWNERSHIP RESULT EVALUATION',
            'ZONAL SUBJECTWISE RESULT EVALUATION',
            'ZONAL MARK ENTRY STATUS REPORT',
            'ZONAL SUBJECT SUMMARY EVALUATION',
        ])->map(fn ($label) => [
            'label' => $label,
            'url' => '#',
        ]);

        $meta = [
            'title' => 'Zonal IRMS Portal',
            'description' => 'Examination Results',
            'keywords' => 'results, mock, NECTA',
            'author' => 'Examination Board',
            'portal_variant' => 'professional-evaluation',
            'eyebrow' => 'ACSEE Zonal Evaluation Workspace',
            'header_top' => "PRIME MINISTER'S OFFICE",
            'header_subtitle' => 'REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT',
            'header_places' => 'TANGA, IRINGA, SINGIDA, MOROGORO, DODOMA, TABORA, LINDI AND MTWARA',
            'header_title' => 'FORM SIX ZONAL JOINT MOCK EVALUATION RESULTS - FEBRUARY, ' . $examYearValue,
            'announcement' => 'Results have been officially published. Please use the search facility below to locate your school or examination centre.',
            'hero_badge' => 'Zonal Reporting Centre',
            'hero_title' => 'Browse zonal ACSEE evaluation reports in one premium workspace.',
            'hero_copy' => 'Review zonal general performance, rankings, ownership summaries, subject reports, and status reports from a cleaner, more professional interface.',
            'stats_label' => 'Zonal Reports',
            'stats_copy' => 'Every zonal evaluation entry is presented in a modern card layout for quick review and access.',
            'support_label' => 'Navigation',
            'support_value' => 'Focused',
            'support_copy' => 'Search for a specific evaluation instantly or use the alphabet filter to narrow the list.',
            'stats_title_two' => 'Columns',
            'stats_title_three' => 'Experience',
            'stats_title_four' => 'Access',
            'stats_value_three' => 'Refined',
            'stats_value_four' => 'One Click',
            'stats_card_one' => 'The full collection of zonal evaluation entries available for ACSEE review.',
            'stats_card_two' => 'Balanced grid spacing keeps the report list easier to scan.',
            'stats_card_three' => 'Modern visual hierarchy improves confidence and readability.',
            'stats_card_four' => 'Open any zonal report directly from its dedicated card.',
            'toolbar_title' => 'Available zonal evaluation entries',
            'toolbar_copy' => 'Use the search box to find the exact zonal report you need, then open it directly from the cards below.',
            'entry_copy' => 'Open this zonal evaluation report to review detailed ACSEE performance data.',
            'search_placeholder' => 'Search Evaluation from the list',
            'alpha_label' => 'CLICK ANY LETTER BELOW TO FILTER EVALUATIONS BY ALPHABET',
            'alpha_all_label' => 'ALL EVALUATIONS',
            'back_url' => '/evaluations/acsee',
            'back_label' => 'Back to ACSEE Evaluations',
            'primary_action_url' => '/evaluations/acsee/regionalwise',
            'primary_action_label' => 'Open Regionalwise',
        ];

        return view('public.results-portal.index', [
            'link' => null,
            'entries' => $entries,
            'meta' => $meta,
            'token' => 'internal-evaluations-zonalwise',
        ]);
    })->name('evaluations.acsee.zonalwise');
    Route::get('/evaluations/acsee/regionalwise', function () {
        $activeYear = \App\Models\ExamYear::query()->where('is_active', true)->first();
        $examYearValue = (int) ($activeYear->year_label ?? now()->year);

        $entries = \App\Models\Region::query()
            ->where('name', 'NOT LIKE', '%UNASSIGNED%')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($region) => [
                'label' => $region->name,
                'url' => route('evaluations.acsee.regionalwise.region', ['region' => $region->id]),
            ]);

        $meta = [
            'title' => 'Zonal IRMS Portal',
            'description' => 'Examination Results',
            'keywords' => 'results, mock, NECTA',
            'author' => 'Examination Board',
            'portal_variant' => 'professional-evaluation',
            'eyebrow' => 'ACSEE Regional Evaluation Workspace',
            'header_top' => "PRIME MINISTER'S OFFICE",
            'header_subtitle' => 'REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT',
            'header_places' => 'TANGA, IRINGA, SINGIDA, MOROGORO, DODOMA, TABORA, LINDI AND MTWARA',
            'header_title' => 'FORM SIX REGIONAL JOINT MOCK EVALUATION RESULTS - FEBRUARY, ' . $examYearValue,
            'announcement' => 'Results have been officially published. Please use the search facility below to locate your school or examination centre.',
            'hero_badge' => 'Regional Reporting Centre',
            'hero_title' => 'Access regional ACSEE evaluation reports through a cleaner, executive-style portal.',
            'hero_copy' => 'Browse the region list with improved readability, faster search, and clearer calls to action before opening the selected evaluation path.',
            'stats_label' => 'Regions',
            'stats_copy' => 'Each region entry opens the next evaluation level from a more professional browsing experience.',
            'support_label' => 'Selection Mode',
            'support_value' => 'Region First',
            'support_copy' => 'Choose a region, then continue into its detailed evaluation categories with fewer distractions.',
            'stats_title_two' => 'Columns',
            'stats_title_three' => 'Experience',
            'stats_title_four' => 'Flow',
            'stats_value_three' => 'Premium',
            'stats_value_four' => 'Structured',
            'stats_card_one' => 'Total region entries available for ACSEE regional evaluation browsing.',
            'stats_card_two' => 'A wide but balanced layout supports quicker scanning across all regions.',
            'stats_card_three' => 'Improved visual quality aligns this page with the polished ACSEE section.',
            'stats_card_four' => 'Move from region selection to evaluation detail in a clear sequence.',
            'toolbar_title' => 'Available regional entries',
            'toolbar_copy' => 'Search by region name or use the alphabet shortcuts below to open the correct regional evaluation path.',
            'entry_copy' => 'Open this region to view its available ACSEE evaluation categories.',
            'search_placeholder' => 'Search Region from the list',
            'alpha_label' => 'CLICK ANY LETTER BELOW TO FILTER REGIONS BY ALPHABET',
            'alpha_all_label' => 'ALL REGIONS',
            'columns' => 4,
            'back_url' => '/evaluations/acsee',
            'back_label' => 'Back to ACSEE Evaluations',
            'primary_action_url' => route('evaluations.acsee.zonalwise'),
            'primary_action_label' => 'Open Zonalwise',
        ];

        return view('public.results-portal.index', [
            'link' => null,
            'entries' => $entries,
            'meta' => $meta,
            'token' => 'internal-evaluations-regionalwise',
        ]);
    })->name('evaluations.acsee.regionalwise');
    Route::get('/evaluations/acsee/regionalwise/{region}', function (\App\Models\Region $region) {
        $activeYear = \App\Models\ExamYear::query()->where('is_active', true)->first();
        $examYearValue = (int) ($activeYear->year_label ?? now()->year);

        $evaluations = collect([
            ['key' => 'general', 'label' => 'GENERAL EVALUATION'],
            ['key' => 'councilwise', 'label' => 'COUNCILWISE EVALUATION'],
            ['key' => 'schoolwise', 'label' => 'SCHOOLWISE EVALUATION'],
            ['key' => 'districtwise', 'label' => 'DISTRICTWISE EVALUATION'],
            ['key' => 'best-ten-councils', 'label' => 'BEST TEN (10) COUNCILS'],
            ['key' => 'least-ten-councils', 'label' => 'LEAST TEN (10) COUNCILS'],
            ['key' => 'best-ten-schools', 'label' => 'BEST TEN (10) SCHOOLS'],
            ['key' => 'least-ten-schools', 'label' => 'LEAST TEN (10) SCHOOLS'],
            ['key' => 'best-ten-girls', 'label' => 'BEST TEN (10) GIRLS'],
            ['key' => 'least-ten-girls', 'label' => 'LEAST TEN (10) GIRLS'],
            ['key' => 'best-ten-boys', 'label' => 'BEST TEN (10) BOYS'],
            ['key' => 'least-ten-boys', 'label' => 'LEAST TEN (10) BOYS'],
            ['key' => 'overall-best-ten-students', 'label' => 'OVERALL TEN (10) BEST STUDENTS'],
            ['key' => 'overall-least-ten-students', 'label' => 'OVERALL TEN (10) LEAST STUDENTS'],
            ['key' => 'government-schools', 'label' => 'GOVERNMENT SCHOOLS'],
            ['key' => 'non-government-schools', 'label' => 'NON-GOVERNMENT SCHOOLS'],
            ['key' => 'ownership-result-evaluation', 'label' => 'OWNERSHIP RESULT EVALUATION'],
            ['key' => 'subjectwise-result-evaluation', 'label' => 'SUBJECTWISE RESULT EVALUATION'],
            ['key' => 'mark-entry-status-report', 'label' => 'MARK ENTRY STATUS REPORT'],
            ['key' => 'subject-summary-evaluation', 'label' => 'SUBJECT SUMMARY EVALUATION'],
        ]);

        $entries = $evaluations->map(fn ($evaluation) => [
            'label' => $evaluation['label'],
            'url' => route('evaluations.acsee.regionalwise.region.evaluation', [
                'region' => $region->id,
                'evaluation' => $evaluation['key'],
            ]),
        ]);

        $meta = [
            'title' => 'Zonal IRMS Portal',
            'description' => 'Examination Results',
            'keywords' => 'results, mock, NECTA',
            'author' => 'Examination Board',
            'portal_variant' => 'professional-evaluation',
            'eyebrow' => strtoupper($region->name) . ' Regional Workspace',
            'header_top' => "PRIME MINISTER'S OFFICE",
            'header_subtitle' => 'REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT',
            'header_places' => 'TANGA, IRINGA, SINGIDA, MOROGORO, DODOMA, TABORA, LINDI AND MTWARA',
            'header_title' => 'FORM SIX ZONAL JOINT MOCK EVALUATION RESULTS - FEBRUARY, ' . $examYearValue . ' - ' . strtoupper($region->name),
            'announcement' => 'Results have been officially published. Please use the search facility below to locate your school or examination centre.',
            'hero_badge' => strtoupper($region->name) . ' Evaluation Centre',
            'hero_title' => 'Open detailed regional evaluation reports for ' . strtoupper($region->name) . '.',
            'hero_copy' => 'Choose the exact report category for this region, from general evaluation and rankings to ownership summaries, subject reports, and status views.',
            'stats_label' => 'Evaluation Types',
            'stats_copy' => 'All report categories for the selected region are gathered in one premium workspace.',
            'support_label' => 'Region',
            'support_value' => strtoupper($region->name),
            'support_copy' => 'Search within this region’s evaluation categories and move directly into the selected report.',
            'stats_title_two' => 'Columns',
            'stats_title_three' => 'Mode',
            'stats_title_four' => 'Navigation',
            'stats_value_three' => 'Detailed',
            'stats_value_four' => 'Direct',
            'stats_card_one' => 'The full set of regional evaluation categories for the selected region.',
            'stats_card_two' => 'Three-column layout keeps report cards organized and readable.',
            'stats_card_three' => 'Focused presentation for committee and analyst workflows.',
            'stats_card_four' => 'Open the required evaluation category without scanning through dense lists.',
            'toolbar_title' => strtoupper($region->name) . ' evaluation categories',
            'toolbar_copy' => 'Search the report list or filter alphabetically to open the correct evaluation category for this region.',
            'entry_copy' => 'Open this evaluation category to review detailed regional ACSEE results.',
            'search_placeholder' => 'Search Evaluation from the list',
            'alpha_label' => 'CLICK ANY LETTER BELOW TO FILTER EVALUATIONS BY ALPHABET',
            'alpha_all_label' => 'ALL EVALUATIONS',
            'columns' => 3,
            'back_url' => route('evaluations.acsee.regionalwise'),
            'back_label' => 'Back to Regions',
            'primary_action_url' => route('evaluations.acsee.regionalwise'),
            'primary_action_label' => 'All Regions',
        ];

        return view('public.results-portal.index', [
            'link' => null,
            'entries' => $entries,
            'meta' => $meta,
            'token' => 'internal-evaluations-regionalwise-' . $region->id,
        ]);
    })->name('evaluations.acsee.regionalwise.region');
    Route::get('/evaluations/acsee/regionalwise/{region}/evaluation/{evaluation}', function (\App\Models\Region $region, string $evaluation) {
        $evaluationMap = collect([
            ['key' => 'general', 'label' => 'GENERAL EVALUATION'],
            ['key' => 'councilwise', 'label' => 'COUNCILWISE EVALUATION'],
            ['key' => 'schoolwise', 'label' => 'SCHOOLWISE EVALUATION'],
            ['key' => 'districtwise', 'label' => 'DISTRICTWISE EVALUATION'],
            ['key' => 'best-ten-councils', 'label' => 'BEST TEN (10) COUNCILS'],
            ['key' => 'least-ten-councils', 'label' => 'LEAST TEN (10) COUNCILS'],
            ['key' => 'best-ten-schools', 'label' => 'BEST TEN (10) SCHOOLS'],
            ['key' => 'least-ten-schools', 'label' => 'LEAST TEN (10) SCHOOLS'],
            ['key' => 'best-ten-girls', 'label' => 'BEST TEN (10) GIRLS'],
            ['key' => 'least-ten-girls', 'label' => 'LEAST TEN (10) GIRLS'],
            ['key' => 'best-ten-boys', 'label' => 'BEST TEN (10) BOYS'],
            ['key' => 'least-ten-boys', 'label' => 'LEAST TEN (10) BOYS'],
            ['key' => 'overall-best-ten-students', 'label' => 'OVERALL TEN (10) BEST STUDENTS'],
            ['key' => 'overall-least-ten-students', 'label' => 'OVERALL TEN (10) LEAST STUDENTS'],
            ['key' => 'government-schools', 'label' => 'GOVERNMENT SCHOOLS'],
            ['key' => 'non-government-schools', 'label' => 'NON-GOVERNMENT SCHOOLS'],
            ['key' => 'ownership-result-evaluation', 'label' => 'OWNERSHIP RESULT EVALUATION'],
            ['key' => 'subjectwise-result-evaluation', 'label' => 'SUBJECTWISE RESULT EVALUATION'],
            ['key' => 'mark-entry-status-report', 'label' => 'MARK ENTRY STATUS REPORT'],
            ['key' => 'subject-summary-evaluation', 'label' => 'SUBJECT SUMMARY EVALUATION'],
        ])->keyBy('key');

        abort_unless($evaluationMap->has($evaluation), 404);

        $activeYear = \App\Models\ExamYear::query()->where('is_active', true)->first();
        $examYearValue = (int) ($activeYear->year_label ?? now()->year);
        $examType = \App\Models\ExamType::query()->where('code', 'ACSEE')->firstOrFail();

        $applyYearFilter = function ($query) use ($activeYear, $examYearValue) {
            $query->where(function ($q) use ($activeYear, $examYearValue) {
                $q->where('cer.year', $examYearValue);
                if ($activeYear) {
                    $q->orWhere('cer.exam_year_id', $activeYear->id);
                }
            });
        };

        $baseRegistrations = \Illuminate\Support\Facades\DB::table('candidate_exam_registrations as cer')
            ->join('candidates as c', 'c.id', '=', 'cer.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->leftJoin('district_councils as dc', 'dc.id', '=', 's.council_id')
            ->leftJoin('districts as d', 'd.id', '=', 's.district_id')
            ->where('s.region_id', $region->id)
            ->where('cer.exam_type_id', $examType->id);
        $applyYearFilter($baseRegistrations);

        $registrationRows = (clone $baseRegistrations)
            ->selectRaw('cer.candidate_id as candidate_id')
            ->selectRaw('c.candidate_id as index_number')
            ->selectRaw('COALESCE(c.full_name, c.candidate_id) as full_name')
            ->selectRaw('c.gender as gender')
            ->selectRaw('c.combination as combination')
            ->selectRaw('s.id as school_id, s.code as school_code, s.name as school_name, COALESCE(s.ownership, "UNKNOWN") as ownership')
            ->selectRaw('d.id as district_id, d.code as district_code, d.name as district_name')
            ->selectRaw('COALESCE(dc.id, d.id) as council_id')
            ->selectRaw('COALESCE(dc.code, d.code) as council_code')
            ->selectRaw('COALESCE(dc.name, d.name, "-") as council_name')
            ->get();
        $regionCandidateIds = $registrationRows->pluck('candidate_id')->unique()->values();

        $hasStoredResultStatus = \Illuminate\Support\Facades\Schema::hasColumn('candidate_results', 'result_status');
        $activeSnapshot = \App\Models\ResultSnapshot::query()
            ->where('exam_year_id', $activeYear?->id)
            ->where('is_active', true)
            ->first();
        $latestProcessId = \App\Models\ResultProcess::query()
            ->where('exam_type_id', $examType->id)
            ->where('exam_year_id', $activeYear?->id)
            ->where('status', 'completed')
            ->latest('id')
            ->value('id');

        $useSnapshotForFinalGrades = false;
        $hasFinalOverallGrade = \Illuminate\Support\Facades\Schema::hasColumn('final_grades', 'overall_grade');
        if ($activeSnapshot && \Illuminate\Support\Facades\Schema::hasColumn('final_grades', 'snapshot_id')) {
            $useSnapshotForFinalGrades = \Illuminate\Support\Facades\DB::table('final_grades')
                ->where('exam_type_id', $examType->id)
                ->where('year', $examYearValue)
                ->where('snapshot_id', $activeSnapshot->id)
                ->exists();
        }

        $resultsBase = \Illuminate\Support\Facades\DB::table('final_grades as fg')
            ->join('candidates as c', 'c.id', '=', 'fg.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->leftJoin('district_councils as dc', 'dc.id', '=', 's.council_id')
            ->leftJoin('districts as d', 'd.id', '=', 's.district_id')
            ->where('fg.exam_type_id', $examType->id)
            ->where('fg.year', $examYearValue)
            ->where('s.region_id', $region->id);

        if ($useSnapshotForFinalGrades) {
            $resultsBase->where('fg.snapshot_id', $activeSnapshot->id);
        } elseif ($latestProcessId) {
            $resultsBase->where(function ($q) use ($latestProcessId) {
                $q->where('fg.process_id', $latestProcessId)
                    ->whereNull('fg.snapshot_id');
            });
        }

        $resultStatusByCandidate = collect();
        if ($hasStoredResultStatus) {
            $statusBase = \Illuminate\Support\Facades\DB::table('candidate_results as cr')
                ->where('cr.exam_type_id', $examType->id)
                ->where('cr.year', $examYearValue)
                ->whereIn('cr.candidate_id', $regionCandidateIds);

            if ($useSnapshotForFinalGrades && $activeSnapshot && \Illuminate\Support\Facades\Schema::hasColumn('candidate_results', 'snapshot_id')) {
                $statusBase->where('cr.snapshot_id', $activeSnapshot->id);
            } elseif ($latestProcessId) {
                $statusBase->where(function ($q) use ($latestProcessId) {
                    $q->where('cr.process_id', $latestProcessId)
                        ->whereNull('cr.snapshot_id');
                });
            }

            $resultStatusByCandidate = $statusBase
                ->get(['cr.candidate_id', 'cr.result_status'])
                ->keyBy('candidate_id');

            $missingStatusCandidateIds = $regionCandidateIds
                ->diff($resultStatusByCandidate->keys())
                ->values();

            if ($missingStatusCandidateIds->isNotEmpty()) {
                $fallbackStatusRows = \Illuminate\Support\Facades\DB::table('candidate_results as cr')
                    ->where('cr.exam_type_id', $examType->id)
                    ->where('cr.year', $examYearValue)
                    ->whereIn('cr.candidate_id', $missingStatusCandidateIds)
                    ->orderByDesc('cr.id')
                    ->get(['cr.id', 'cr.candidate_id', 'cr.result_status'])
                    ->unique('candidate_id')
                    ->keyBy('candidate_id');

                $resultStatusByCandidate = $resultStatusByCandidate->union($fallbackStatusRows);
            }
        }

        $scopedFinalRows = $resultsBase
            ->selectRaw('fg.candidate_id as candidate_id')
            ->selectRaw('fg.gpa as resolved_gpa_source')
            ->selectRaw('fg.division as resolved_division_source')
            ->selectRaw('fg.grading_breakdown as resolved_breakdown_source')
            ->selectRaw($hasFinalOverallGrade ? 'fg.overall_grade as resolved_overall_grade_source' : 'NULL as resolved_overall_grade_source')
            ->get();

        $finalByCandidate = $scopedFinalRows->keyBy('candidate_id');
        $missingFinalCandidateIds = $regionCandidateIds
            ->diff($finalByCandidate->keys())
            ->values();

        if ($missingFinalCandidateIds->isNotEmpty()) {
            $fallbackFinalRows = \Illuminate\Support\Facades\DB::table('final_grades as fg')
                ->where('fg.exam_type_id', $examType->id)
                ->where('fg.year', $examYearValue)
                ->whereIn('fg.candidate_id', $missingFinalCandidateIds)
                ->orderByDesc('fg.id')
                ->get([
                    'fg.id',
                    'fg.candidate_id',
                    'fg.gpa as resolved_gpa_source',
                    'fg.division as resolved_division_source',
                    'fg.grading_breakdown as resolved_breakdown_source',
                    \Illuminate\Support\Facades\DB::raw($hasFinalOverallGrade ? 'fg.overall_grade as resolved_overall_grade_source' : 'NULL as resolved_overall_grade_source'),
                ])
                ->unique('candidate_id')
                ->keyBy('candidate_id');

            $finalByCandidate = $finalByCandidate->union($fallbackFinalRows);
        }

        $resultRows = $registrationRows->map(function ($row) use ($finalByCandidate) {
            $final = $finalByCandidate->get($row->candidate_id);
            $row->resolved_gpa_source = $final->resolved_gpa_source ?? null;
            $row->resolved_division_source = $final->resolved_division_source ?? null;
            $row->resolved_breakdown_source = $final->resolved_breakdown_source ?? null;
            $row->resolved_overall_grade_source = $final->resolved_overall_grade_source ?? null;
            $row->result_status = null;
            return $row;
        })->values();

        $storedFinalRowsForMetrics = $finalByCandidate->map(function ($final) {
            return (object) [
                'gpa' => $final->resolved_gpa_source ?? null,
                'division' => $final->resolved_division_source ?? null,
                'grading_breakdown' => $final->resolved_breakdown_source ?? null,
            ];
        });

        $needsComputedMetrics = $resultRows
            ->filter(function ($row) use ($finalByCandidate, $resultStatusByCandidate) {
                $storedStatus = strtoupper(trim((string) ($resultStatusByCandidate->get($row->candidate_id)->result_status ?? '')));
                $final = $finalByCandidate->get($row->candidate_id);
                $hasStoredDivision = !is_null($final?->resolved_division_source) && $final->resolved_division_source !== '';
                $hasStoredGpa = !is_null($final?->resolved_gpa_source) && $final->resolved_gpa_source !== '';

                if (in_array($storedStatus, ['ABS', 'INC'], true)) {
                    return false;
                }

                if ($storedStatus === 'COMPLETE' && $hasStoredDivision && $hasStoredGpa) {
                    return false;
                }

                return true;
            })
            ->pluck('candidate_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $computedMetrics = $needsComputedMetrics->isNotEmpty()
            ? app(\App\Services\Results\PublicAcseeCandidateMetricsService::class)
                ->computeForCandidateIds(
                    $needsComputedMetrics,
                    $examType,
                    $examYearValue,
                    $storedFinalRowsForMetrics,
                    $resultStatusByCandidate
                )
            : collect();

        $enrichedRows = $resultRows->map(function ($row) use ($computedMetrics, $finalByCandidate, $resultStatusByCandidate) {
            $final = $finalByCandidate->get($row->candidate_id);
            $storedStatus = strtoupper(trim((string) ($resultStatusByCandidate->get($row->candidate_id)->result_status ?? '')));
            $hasStoredDivision = !is_null($final?->resolved_division_source) && $final->resolved_division_source !== '';
            $hasStoredGpa = !is_null($final?->resolved_gpa_source) && $final->resolved_gpa_source !== '';

            if ($storedStatus === 'COMPLETE' && $hasStoredDivision && $hasStoredGpa) {
                $row->resolved_result_status = 'COMPLETE';
                $row->resolved_division = (int) $final->resolved_division_source;
                $row->resolved_gpa = (float) $final->resolved_gpa_source;
                return $row;
            }

            if (in_array($storedStatus, ['ABS', 'INC'], true)) {
                $row->resolved_result_status = $storedStatus;
                $row->resolved_division = 0;
                $row->resolved_gpa = null;
                return $row;
            }

            $metrics = $computedMetrics->get($row->candidate_id, []);
            $row->resolved_result_status = (string) ($metrics['candidateStatus'] ?? ($storedStatus !== '' ? $storedStatus : 'ABS'));
            $row->resolved_division = (int) ($metrics['division_numeric'] ?? ($hasStoredDivision ? (int) $final->resolved_division_source : 0));
            $row->resolved_gpa = array_key_exists('gpa', $metrics)
                ? (float) $metrics['gpa']
                : ($hasStoredGpa ? (float) $final->resolved_gpa_source : null);
            return $row;
        })->values();

        $completeRows = $enrichedRows->filter(fn ($row) => $row->resolved_result_status === 'COMPLETE')->values();

        $summary = [
            'Exam Year' => (string) $examYearValue,
            'Region' => strtoupper((string) $region->name),
            'Candidates' => number_format((int) $enrichedRows->count()),
            'Schools' => number_format((int) $enrichedRows->pluck('school_id')->filter()->unique()->count()),
            'Pass' => number_format((int) $completeRows->filter(fn ($row) => in_array($row->resolved_division, [1, 2, 3, 4], true))->count()),
            'Fail' => number_format((int) $completeRows->where('resolved_division', 0)->count()),
            'Avg GPA' => number_format((float) ($completeRows->pluck('resolved_gpa')->filter(fn ($v) => $v !== null)->avg() ?? 0), 2),
        ];

        if (in_array($evaluation, ['schoolwise', 'best-ten-schools', 'least-ten-schools', 'government-schools', 'non-government-schools'], true)) {
            $hasStoredResultStatus = \Illuminate\Support\Facades\Schema::hasColumn('candidate_results', 'result_status');
            $filteredRows = match ($evaluation) {
                'government-schools' => $enrichedRows->filter(fn ($row) => strtoupper((string) ($row->ownership ?? '')) === 'GOVERNMENT')->values(),
                'non-government-schools' => $enrichedRows->filter(fn ($row) => strtoupper((string) ($row->ownership ?? '')) === 'NON-GOVERNMENT')->values(),
                default => $enrichedRows,
            };

            $candidateRows = $filteredRows->map(function ($row) {
                return (object) [
                    'candidate_id' => $row->candidate_id,
                    'result_status' => $row->resolved_result_status,
                    'resolved_division' => $row->resolved_division,
                    'resolved_gpa' => $row->resolved_gpa,
                    'gender' => $row->gender,
                    'school_id' => $row->school_id,
                    'school_code' => $row->school_code,
                    'school_name' => $row->school_name,
                    'council_name' => $row->council_name,
                ];
            })->values();

            $schoolBuckets = [];
            foreach ($candidateRows as $row) {
                if (empty($row->school_id)) {
                    continue;
                }

                $schoolKey = (string) ((int) $row->school_id);
                if (!isset($schoolBuckets[$schoolKey])) {
                    $schoolName = strtoupper((string) ($row->school_name ?? '-'));
                    $schoolCode = strtoupper(trim((string) ($row->school_code ?? '')));
                    $schoolBuckets[$schoolKey] = [
                        'council' => strtoupper((string) ($row->council_name ?? '-')),
                        'school' => $schoolCode !== '' ? "{$schoolCode} - {$schoolName}" : $schoolName,
                        'registered' => ['m' => 0, 'f' => 0, 't' => 0],
                        'absent' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                        'sat' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                        'inc' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                        'division' => [
                            'i' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            'ii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            'iii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            'i_iii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            'iv' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            'i_iv' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            'zero' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                        ],
                        'gpa_sum' => 0.0,
                        'gpa_count' => 0,
                        'gpa' => null,
                    ];
                }

                $genderValue = strtoupper(trim((string) $row->gender));
                $gender = match ($genderValue) {
                    'F' => 'f',
                    'M' => 'm',
                    default => null,
                };
                $bucket = &$schoolBuckets[$schoolKey];
                $bucket['registered']['t']++;
                if ($gender !== null) {
                    $bucket['registered'][$gender]++;
                }

                $resultStatus = strtoupper(trim((string) ($row->result_status ?? 'COMPLETE')));

                if ($resultStatus === 'ABS') {
                    $bucket['absent']['t']++;
                    if ($gender !== null) {
                        $bucket['absent'][$gender]++;
                    }
                    unset($bucket);
                    continue;
                }

                $bucket['sat']['t']++;
                if ($gender !== null) {
                    $bucket['sat'][$gender]++;
                }

                if ($resultStatus === 'INC') {
                    $bucket['inc']['t']++;
                    if ($gender !== null) {
                        $bucket['inc'][$gender]++;
                    }
                    unset($bucket);
                    continue;
                }

                $divisionValue = (int) ($row->resolved_division ?? 0);
                $group = match ($divisionValue) {
                    1 => 'i',
                    2 => 'ii',
                    3 => 'iii',
                    4 => 'iv',
                    default => 'zero',
                };

                $bucket['division'][$group]['t']++;
                if ($gender !== null) {
                    $bucket['division'][$group][$gender]++;
                }

                $gpaValue = $row->resolved_gpa ?? null;
                if (!is_null($gpaValue) && $gpaValue !== '') {
                    $bucket['gpa_sum'] += (float) $gpaValue;
                    $bucket['gpa_count']++;
                }
                unset($bucket);
            }

            $schoolStats = collect($schoolBuckets)->map(function ($bucket) {
                $regT = max((int) $bucket['registered']['t'], 0);
                $absT = (int) $bucket['absent']['t'];
                $incT = (int) $bucket['inc']['t'];

                // SAT follows NECTA-style rollup: SAT = REGISTERED - ABSENT (INC is included in SAT).
                $bucket['sat']['m'] = max((int) $bucket['registered']['m'] - (int) $bucket['absent']['m'], 0);
                $bucket['sat']['f'] = max((int) $bucket['registered']['f'] - (int) $bucket['absent']['f'], 0);
                $bucket['sat']['t'] = $bucket['sat']['m'] + $bucket['sat']['f'];
                $satT = max((int) $bucket['sat']['t'], 0);

                $bucket['absent']['pct'] = $regT > 0 ? ($absT / $regT) * 100 : 0.0;
                $bucket['sat']['pct'] = $regT > 0 ? ($satT / $regT) * 100 : 0.0;
                $bucket['inc']['pct'] = $regT > 0 ? ($incT / $regT) * 100 : 0.0;

                $bucket['division']['i_iii']['m'] = $bucket['division']['i']['m'] + $bucket['division']['ii']['m'] + $bucket['division']['iii']['m'];
                $bucket['division']['i_iii']['f'] = $bucket['division']['i']['f'] + $bucket['division']['ii']['f'] + $bucket['division']['iii']['f'];
                $bucket['division']['i_iii']['t'] = $bucket['division']['i_iii']['m'] + $bucket['division']['i_iii']['f'];

                $bucket['division']['i_iv']['m'] = $bucket['division']['i_iii']['m'] + $bucket['division']['iv']['m'];
                $bucket['division']['i_iv']['f'] = $bucket['division']['i_iii']['f'] + $bucket['division']['iv']['f'];
                $bucket['division']['i_iv']['t'] = $bucket['division']['i_iv']['m'] + $bucket['division']['i_iv']['f'];

                foreach (['i', 'ii', 'iii', 'i_iii', 'iv', 'i_iv', 'zero'] as $grp) {
                    $bucket['division'][$grp]['pct'] = $satT > 0
                        ? ((int) $bucket['division'][$grp]['t'] / $satT) * 100
                        : 0.0;
                }

                $bucket['gpa'] = $bucket['gpa_count'] > 0
                    ? round($bucket['gpa_sum'] / $bucket['gpa_count'], 4)
                    : null;

                unset($bucket['gpa_sum'], $bucket['gpa_count']);
                return $bucket;
            })->values();

            $rankedRows = $schoolStats
                ->sortBy(fn ($r) => is_null($r['gpa']) ? INF : $r['gpa'])
                ->values()
                ->map(function ($row, $idx) {
                    $row['pos'] = $idx + 1;
                    return $row;
                });

            $displayRows = match ($evaluation) {
                'best-ten-schools' => $rankedRows->take(10)->values(),
                'least-ten-schools' => $rankedRows->reverse()->take(10)->values()->values(),
                default => $rankedRows,
            };

            $total = [
                'registered' => ['m' => 0, 'f' => 0, 't' => 0],
                'absent' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                'sat' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                'inc' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                'division' => [
                    'i' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                    'ii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                    'iii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                    'i_iii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                    'iv' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                    'i_iv' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                    'zero' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                ],
            ];

            foreach ($displayRows as $row) {
                foreach (['m', 'f', 't'] as $k) {
                    $total['registered'][$k] += $row['registered'][$k];
                    $total['absent'][$k] += $row['absent'][$k];
                    $total['sat'][$k] += $row['sat'][$k];
                    $total['inc'][$k] += $row['inc'][$k];
                }
                foreach (['i', 'ii', 'iii', 'i_iii', 'iv', 'i_iv', 'zero'] as $grp) {
                    foreach (['m', 'f', 't'] as $k) {
                        $total['division'][$grp][$k] += $row['division'][$grp][$k];
                    }
                }
            }

            $total['absent']['pct'] = $total['registered']['t'] > 0 ? ($total['absent']['t'] / $total['registered']['t']) * 100 : 0;
            $total['sat']['pct'] = $total['registered']['t'] > 0 ? ($total['sat']['t'] / $total['registered']['t']) * 100 : 0;
            $total['inc']['pct'] = $total['registered']['t'] > 0 ? ($total['inc']['t'] / $total['registered']['t']) * 100 : 0;
            foreach (['i', 'ii', 'iii', 'i_iii', 'iv', 'i_iv', 'zero'] as $grp) {
                $total['division'][$grp]['pct'] = $total['sat']['t'] > 0
                    ? ($total['division'][$grp]['t'] / $total['sat']['t']) * 100
                    : 0;
            }

            if (strtolower((string) request()->query('export')) === 'pdf') {
                $safeRegion = \Illuminate\Support\Str::slug((string) $region->name);
                $exportKey = match ($evaluation) {
                    'best-ten-schools' => 'best_ten_schools',
                    'least-ten-schools' => 'least_ten_schools',
                    'government-schools' => 'government_schools',
                    'non-government-schools' => 'non_government_schools',
                    default => 'schoolwise',
                };
                $filename = "acsee_regional_{$exportKey}_{$safeRegion}_{$examYearValue}.pdf";
                $tempPath = tempnam(sys_get_temp_dir(), 'acsee_schoolwise_');
                if ($tempPath === false) {
                    abort(500, 'Unable to prepare PDF export file.');
                }
                $pdfPath = $tempPath . '.pdf';
                @rename($tempPath, $pdfPath);

                app(\App\Services\Results\AcseeRegionalSchoolwiseFpdfService::class)
                    ->generate(
                        $region,
                        $examYearValue,
                        $displayRows->all(),
                        $total,
                        $pdfPath,
                        (string) data_get($evaluationMap->get($evaluation), 'label', 'SCHOOLWISE')
                    );

                return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
            }

            return view('evaluations.acsee-regionalwise-schoolwise', [
                'region' => $region,
                'evaluationLabel' => data_get($evaluationMap->get($evaluation), 'label'),
                'examYearValue' => $examYearValue,
                'rows' => $displayRows,
                'total' => $total,
            ]);
        }

        $columns = [];
        $rows = [];

        $buildInstitutionRows = function ($items, ?int $limit = null, ?string $orderDirection = null) use ($examYearValue) {
            $rows = collect($items);
            if ($orderDirection === 'ASC') {
                $rows = $rows->sortBy(fn ($row) => is_null($row->avg_gpa) ? INF : $row->avg_gpa)->values();
            } elseif ($orderDirection === 'DESC') {
                $rows = $rows->sortByDesc(fn ($row) => is_null($row->avg_gpa) ? -INF : $row->avg_gpa)->values();
            }
            if ($limit) {
                $rows = $rows->take($limit)->values();
            }
            return $rows->map(function ($row) use ($examYearValue) {
                $passCount = (int) ($row->pass_count ?? 0);
                $candidates = max((int) ($row->candidates ?? 0), 1);
                $passRate = ($passCount / $candidates) * 100;

                $firstCell = trim(($row->code ? $row->code . ' - ' : '') . ($row->name ?? 'N/A'));
                $firstCellUrl = isset($row->school_id) && $row->school_id
                    ? route('public.results.school', [
                        'examYear' => $examYearValue,
                        'examType' => 'acsee',
                        'schoolId' => $row->school_id,
                    ])
                    : null;

                return [
                    'cells' => [
                        $firstCell,
                        number_format((int) ($row->candidates ?? 0)),
                        number_format($passCount),
                        number_format((int) ($row->fail_count ?? 0)),
                        number_format($passRate, 1) . '%',
                        number_format((float) ($row->avg_gpa ?? 0), 2),
                    ],
                    'first_cell_url' => $firstCellUrl,
                ];
            })->values();
        };

        switch ($evaluation) {
            case 'general':
                $generalRows = collect(['F', 'M'])->map(function ($gender) use ($enrichedRows, $finalByCandidate) {
                    $label = $gender === 'F' ? 'FEMALE' : 'MALE';
                    $bucket = [
                        'council' => $label,
                        'registered' => ['m' => 0, 'f' => 0, 't' => 0],
                        'absent' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                        'sat' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                        'inc' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                        'division' => [
                            'i' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            'ii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            'iii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            'i_iii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            'iv' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            'i_iv' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            'zero' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                        ],
                        'gpa_sum' => 0.0,
                        'gpa_count' => 0,
                        'gpa' => null,
                    ];

                    foreach ($enrichedRows->filter(fn ($row) => strtoupper((string) ($row->gender ?? '')) === $gender) as $row) {
                        $genderKey = $gender === 'F' ? 'f' : 'm';
                        $bucket['registered']['t']++;
                        $bucket['registered'][$genderKey]++;

                        $resultStatus = strtoupper(trim((string) ($row->resolved_result_status ?? 'COMPLETE')));
                        if ($resultStatus === 'ABS') {
                            $bucket['absent']['t']++;
                            $bucket['absent'][$genderKey]++;
                            continue;
                        }

                        if ($resultStatus === 'INC') {
                            $bucket['inc']['t']++;
                            $bucket['inc'][$genderKey]++;
                        }

                        $divisionValue = (int) ($row->resolved_division ?? 0);
                        if ($resultStatus !== 'INC') {
                            $group = match ($divisionValue) {
                                1 => 'i',
                                2 => 'ii',
                                3 => 'iii',
                                4 => 'iv',
                                default => 'zero',
                            };
                            $bucket['division'][$group]['t']++;
                            $bucket['division'][$group][$genderKey]++;
                        }

                        $gpaValue = $row->resolved_gpa ?? null;
                        if ($resultStatus !== 'INC' && !is_null($gpaValue) && $gpaValue !== '') {
                            $bucket['gpa_sum'] += (float) $gpaValue;
                            $bucket['gpa_count']++;
                        }
                    }

                    $regT = max((int) $bucket['registered']['t'], 0);
                    $absT = (int) $bucket['absent']['t'];
                    $incT = (int) $bucket['inc']['t'];
                    $bucket['sat']['m'] = max((int) $bucket['registered']['m'] - (int) $bucket['absent']['m'], 0);
                    $bucket['sat']['f'] = max((int) $bucket['registered']['f'] - (int) $bucket['absent']['f'], 0);
                    $bucket['sat']['t'] = $bucket['sat']['m'] + $bucket['sat']['f'];
                    $satT = max((int) $bucket['sat']['t'], 0);

                    $bucket['absent']['pct'] = $regT > 0 ? ($absT / $regT) * 100 : 0.0;
                    $bucket['sat']['pct'] = $regT > 0 ? ($satT / $regT) * 100 : 0.0;
                    $bucket['inc']['pct'] = $regT > 0 ? ($incT / $regT) * 100 : 0.0;
                    $bucket['division']['i_iii']['m'] = $bucket['division']['i']['m'] + $bucket['division']['ii']['m'] + $bucket['division']['iii']['m'];
                    $bucket['division']['i_iii']['f'] = $bucket['division']['i']['f'] + $bucket['division']['ii']['f'] + $bucket['division']['iii']['f'];
                    $bucket['division']['i_iii']['t'] = $bucket['division']['i_iii']['m'] + $bucket['division']['i_iii']['f'];
                    $bucket['division']['i_iv']['m'] = $bucket['division']['i_iii']['m'] + $bucket['division']['iv']['m'];
                    $bucket['division']['i_iv']['f'] = $bucket['division']['i_iii']['f'] + $bucket['division']['iv']['f'];
                    $bucket['division']['i_iv']['t'] = $bucket['division']['i_iv']['m'] + $bucket['division']['i_iv']['f'];

                    foreach (['i', 'ii', 'iii', 'i_iii', 'iv', 'i_iv', 'zero'] as $group) {
                        $bucket['division'][$group]['pct'] = $satT > 0
                            ? ((int) $bucket['division'][$group]['t'] / $satT) * 100
                            : 0.0;
                    }

                    $bucket['gpa'] = $bucket['gpa_count'] > 0
                        ? round($bucket['gpa_sum'] / $bucket['gpa_count'], 4)
                        : null;

                    unset($bucket['gpa_sum'], $bucket['gpa_count']);
                    return $bucket;
                })->sortBy(fn ($row) => is_null($row['gpa']) ? INF : $row['gpa'])->values()->map(function ($row, $idx) {
                    $row['pos'] = $idx + 1;
                    return $row;
                });

                $total = [
                    'registered' => ['m' => 0, 'f' => 0, 't' => 0],
                    'absent' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                    'sat' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                    'inc' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                    'division' => [
                        'i' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'ii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'iii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'i_iii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'iv' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'i_iv' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'zero' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                    ],
                ];

                foreach ($generalRows as $row) {
                    foreach (['m', 'f', 't'] as $key) {
                        $total['registered'][$key] += $row['registered'][$key];
                        $total['absent'][$key] += $row['absent'][$key];
                        $total['sat'][$key] += $row['sat'][$key];
                        $total['inc'][$key] += $row['inc'][$key];
                    }
                    foreach (['i', 'ii', 'iii', 'i_iii', 'iv', 'i_iv', 'zero'] as $group) {
                        foreach (['m', 'f', 't'] as $key) {
                            $total['division'][$group][$key] += $row['division'][$group][$key];
                        }
                    }
                }

                $total['absent']['pct'] = $total['registered']['t'] > 0 ? ($total['absent']['t'] / $total['registered']['t']) * 100 : 0;
                $total['sat']['pct'] = $total['registered']['t'] > 0 ? ($total['sat']['t'] / $total['registered']['t']) * 100 : 0;
                $total['inc']['pct'] = $total['registered']['t'] > 0 ? ($total['inc']['t'] / $total['registered']['t']) * 100 : 0;
                foreach (['i', 'ii', 'iii', 'i_iii', 'iv', 'i_iv', 'zero'] as $group) {
                    $total['division'][$group]['pct'] = $total['sat']['t'] > 0
                        ? ($total['division'][$group]['t'] / $total['sat']['t']) * 100
                        : 0;
                }

                if (strtolower((string) request()->query('export')) === 'pdf') {
                    $safeRegion = \Illuminate\Support\Str::slug((string) $region->name);
                    $filename = "acsee_regional_general_{$safeRegion}_{$examYearValue}.pdf";
                    $tempPath = tempnam(sys_get_temp_dir(), 'acsee_general_');
                    if ($tempPath === false) {
                        abort(500, 'Unable to prepare PDF export file.');
                    }
                    $pdfPath = $tempPath . '.pdf';
                    @rename($tempPath, $pdfPath);

                    app(\App\Services\Results\AcseeRegionalSchoolwiseFpdfService::class)
                        ->generate(
                            $region,
                            $examYearValue,
                            $generalRows->all(),
                            $total,
                            $pdfPath,
                            (string) data_get($evaluationMap->get($evaluation), 'label', 'GENERAL'),
                            [
                                'first_column_label' => 'SEX',
                                'first_column_key' => 'council',
                                'first_column_width' => 18,
                                'hide_second_column' => true,
                                'metric3_width' => 12,
                                'metric4_width' => 11.7,
                                'gpa_width' => 27,
                                'pos_width' => 20,
                            ]
                        );

                    return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
                }

                return view('evaluations.acsee-regionalwise-schoolwise', [
                    'region' => $region,
                    'evaluationLabel' => data_get($evaluationMap->get($evaluation), 'label'),
                    'examYearValue' => $examYearValue,
                    'rows' => $generalRows,
                    'total' => $total,
                    'tableMode' => 'general',
                ]);

            case 'councilwise':
            case 'best-ten-councils':
            case 'least-ten-councils':
                $councilBuckets = [];
                foreach ($enrichedRows->filter(fn ($row) => !empty($row->council_id)) as $row) {
                    $councilKey = (string) ((int) $row->council_id);
                    if (!isset($councilBuckets[$councilKey])) {
                        $councilName = strtoupper(trim((string) ($row->council_name ?? 'N/A'))) ?: 'N/A';
                        $councilBuckets[$councilKey] = [
                            'council' => $councilName,
                            'registered' => ['m' => 0, 'f' => 0, 't' => 0],
                            'absent' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            'sat' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            'inc' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            'division' => [
                                'i' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                                'ii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                                'iii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                                'i_iii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                                'iv' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                                'i_iv' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                                'zero' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            ],
                            'gpa_sum' => 0.0,
                            'gpa_count' => 0,
                            'gpa' => null,
                        ];
                    }

                    $genderValue = strtoupper(trim((string) ($row->gender ?? '')));
                    $gender = match ($genderValue) {
                        'F' => 'f',
                        'M' => 'm',
                        default => null,
                    };

                    $bucket = &$councilBuckets[$councilKey];
                    $bucket['registered']['t']++;
                    if ($gender !== null) {
                        $bucket['registered'][$gender]++;
                    }

                    $resultStatus = strtoupper(trim((string) ($row->resolved_result_status ?? 'COMPLETE')));

                    if ($resultStatus === 'ABS') {
                        $bucket['absent']['t']++;
                        if ($gender !== null) {
                            $bucket['absent'][$gender]++;
                        }
                        unset($bucket);
                        continue;
                    }

                    if ($resultStatus === 'INC') {
                        $bucket['inc']['t']++;
                        if ($gender !== null) {
                            $bucket['inc'][$gender]++;
                        }
                    }

                    $divisionValue = (int) ($row->resolved_division ?? 0);
                    if ($resultStatus !== 'INC') {
                        $group = match ($divisionValue) {
                            1 => 'i',
                            2 => 'ii',
                            3 => 'iii',
                            4 => 'iv',
                            default => 'zero',
                        };

                        $bucket['division'][$group]['t']++;
                        if ($gender !== null) {
                            $bucket['division'][$group][$gender]++;
                        }
                    }

                    $gpaValue = $row->resolved_gpa ?? null;
                    if ($resultStatus !== 'INC' && !is_null($gpaValue) && $gpaValue !== '') {
                        $bucket['gpa_sum'] += (float) $gpaValue;
                        $bucket['gpa_count']++;
                    }

                    unset($bucket);
                }

                $rankedCouncils = collect($councilBuckets)
                    ->map(function ($bucket) {
                        $regT = max((int) $bucket['registered']['t'], 0);
                        $absT = (int) $bucket['absent']['t'];
                        $incT = (int) $bucket['inc']['t'];

                        $bucket['sat']['m'] = max((int) $bucket['registered']['m'] - (int) $bucket['absent']['m'], 0);
                        $bucket['sat']['f'] = max((int) $bucket['registered']['f'] - (int) $bucket['absent']['f'], 0);
                        $bucket['sat']['t'] = $bucket['sat']['m'] + $bucket['sat']['f'];
                        $satT = max((int) $bucket['sat']['t'], 0);

                        $bucket['absent']['pct'] = $regT > 0 ? ($absT / $regT) * 100 : 0.0;
                        $bucket['sat']['pct'] = $regT > 0 ? ($satT / $regT) * 100 : 0.0;
                        $bucket['inc']['pct'] = $regT > 0 ? ($incT / $regT) * 100 : 0.0;

                        $bucket['division']['i_iii']['m'] = $bucket['division']['i']['m'] + $bucket['division']['ii']['m'] + $bucket['division']['iii']['m'];
                        $bucket['division']['i_iii']['f'] = $bucket['division']['i']['f'] + $bucket['division']['ii']['f'] + $bucket['division']['iii']['f'];
                        $bucket['division']['i_iii']['t'] = $bucket['division']['i_iii']['m'] + $bucket['division']['i_iii']['f'];

                        $bucket['division']['i_iv']['m'] = $bucket['division']['i_iii']['m'] + $bucket['division']['iv']['m'];
                        $bucket['division']['i_iv']['f'] = $bucket['division']['i_iii']['f'] + $bucket['division']['iv']['f'];
                        $bucket['division']['i_iv']['t'] = $bucket['division']['i_iv']['m'] + $bucket['division']['i_iv']['f'];

                        foreach (['i', 'ii', 'iii', 'i_iii', 'iv', 'i_iv', 'zero'] as $group) {
                            $bucket['division'][$group]['pct'] = $satT > 0
                                ? ((int) $bucket['division'][$group]['t'] / $satT) * 100
                                : 0.0;
                        }

                        $bucket['gpa'] = $bucket['gpa_count'] > 0
                            ? round($bucket['gpa_sum'] / $bucket['gpa_count'], 4)
                            : null;

                        unset($bucket['gpa_sum'], $bucket['gpa_count']);
                        return $bucket;
                    })
                    ->sortBy(fn ($row) => is_null($row['gpa']) ? INF : $row['gpa'])
                    ->values()
                    ->map(function ($row, $idx) {
                        $row['pos'] = $idx + 1;
                        return $row;
                    });

                $displayRows = match ($evaluation) {
                    'best-ten-councils' => $rankedCouncils->take(10)->values(),
                    'least-ten-councils' => $rankedCouncils->reverse()->take(10)->values()->values(),
                    default => $rankedCouncils,
                };

                $total = [
                    'registered' => ['m' => 0, 'f' => 0, 't' => 0],
                    'absent' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                    'sat' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                    'inc' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                    'division' => [
                        'i' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'ii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'iii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'i_iii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'iv' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'i_iv' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'zero' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                    ],
                ];

                foreach ($displayRows as $row) {
                    foreach (['m', 'f', 't'] as $key) {
                        $total['registered'][$key] += $row['registered'][$key];
                        $total['absent'][$key] += $row['absent'][$key];
                        $total['sat'][$key] += $row['sat'][$key];
                        $total['inc'][$key] += $row['inc'][$key];
                    }
                    foreach (['i', 'ii', 'iii', 'i_iii', 'iv', 'i_iv', 'zero'] as $group) {
                        foreach (['m', 'f', 't'] as $key) {
                            $total['division'][$group][$key] += $row['division'][$group][$key];
                        }
                    }
                }

                $total['absent']['pct'] = $total['registered']['t'] > 0 ? ($total['absent']['t'] / $total['registered']['t']) * 100 : 0;
                $total['sat']['pct'] = $total['registered']['t'] > 0 ? ($total['sat']['t'] / $total['registered']['t']) * 100 : 0;
                $total['inc']['pct'] = $total['registered']['t'] > 0 ? ($total['inc']['t'] / $total['registered']['t']) * 100 : 0;
                foreach (['i', 'ii', 'iii', 'i_iii', 'iv', 'i_iv', 'zero'] as $group) {
                    $total['division'][$group]['pct'] = $total['sat']['t'] > 0
                        ? ($total['division'][$group]['t'] / $total['sat']['t']) * 100
                        : 0;
                }

                if (strtolower((string) request()->query('export')) === 'pdf') {
                    $safeRegion = \Illuminate\Support\Str::slug((string) $region->name);
                    $exportKey = match ($evaluation) {
                        'best-ten-councils' => 'best_ten_councils',
                        'least-ten-councils' => 'least_ten_councils',
                        default => 'councilwise',
                    };
                    $filename = "acsee_regional_{$exportKey}_{$safeRegion}_{$examYearValue}.pdf";
                    $tempPath = tempnam(sys_get_temp_dir(), 'acsee_council_');
                    if ($tempPath === false) {
                        abort(500, 'Unable to prepare PDF export file.');
                    }
                    $pdfPath = $tempPath . '.pdf';
                    @rename($tempPath, $pdfPath);

                    app(\App\Services\Results\AcseeRegionalSchoolwiseFpdfService::class)
                        ->generate(
                            $region,
                            $examYearValue,
                            $displayRows->all(),
                            $total,
                            $pdfPath,
                            (string) data_get($evaluationMap->get($evaluation), 'label', 'COUNCILWISE'),
                            [
                                'first_column_label' => 'COUNCIL',
                                'first_column_key' => 'council',
                                'first_column_width' => 32,
                                'hide_second_column' => true,
                                'metric3_width' => 12,
                                'metric4_width' => 11.2,
                                'gpa_width' => 26,
                                'pos_width' => 20,
                            ]
                        );

                    return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
                }

                return view('evaluations.acsee-regionalwise-schoolwise', [
                    'region' => $region,
                    'evaluationLabel' => data_get($evaluationMap->get($evaluation), 'label'),
                    'examYearValue' => $examYearValue,
                    'rows' => $displayRows,
                    'total' => $total,
                    'tableMode' => 'councilwise',
                ]);
                break;

            case 'schoolwise':
            case 'best-ten-schools':
            case 'least-ten-schools':
            case 'government-schools':
            case 'non-government-schools':
                $columns = ['SCHOOL', 'CANDIDATES', 'PASS', 'FAIL', 'PASS RATE', 'AVG GPA'];
                $query = $enrichedRows;
                if ($evaluation === 'government-schools') {
                    $query = $query->filter(fn ($row) => strtoupper((string) $row->ownership) === 'GOVERNMENT');
                }
                if ($evaluation === 'non-government-schools') {
                    $query = $query->filter(fn ($row) => strtoupper((string) $row->ownership) === 'NON-GOVERNMENT');
                }
                $query = $query->groupBy('school_id')->map(function ($items) use ($completeRows) {
                    $first = $items->first();
                    $ids = $items->pluck('candidate_id')->all();
                    $complete = $completeRows->filter(fn ($row) => in_array($row->candidate_id, $ids, true));
                    return (object) [
                        'school_id' => $first->school_id,
                        'code' => $first->school_code,
                        'name' => $first->school_name,
                        'candidates' => $items->count(),
                        'pass_count' => $complete->filter(fn ($row) => in_array($row->resolved_division, [1, 2, 3, 4], true))->count(),
                        'fail_count' => $complete->where('resolved_division', 0)->count(),
                        'avg_gpa' => round((float) ($complete->pluck('resolved_gpa')->filter(fn ($v) => $v !== null)->avg() ?? 0), 2),
                    ];
                })->values();

                if ($evaluation === 'best-ten-schools') {
                    $rows = $buildInstitutionRows($query, 10, 'ASC');
                } elseif ($evaluation === 'least-ten-schools') {
                    $rows = $buildInstitutionRows($query, 10, 'DESC');
                } else {
                    $rows = $buildInstitutionRows($query->sortBy('name')->values());
                }
                break;

            case 'districtwise':
                $districtBuckets = [];
                foreach ($enrichedRows->filter(fn ($row) => !empty($row->district_id)) as $row) {
                    $districtKey = (string) ((int) $row->district_id);
                    if (!isset($districtBuckets[$districtKey])) {
                        $districtName = trim(((string) ($row->district_code ?? '')) . ' - ' . ((string) ($row->district_name ?? 'N/A')));
                        $districtBuckets[$districtKey] = [
                            'district' => $districtName,
                            'registered' => ['m' => 0, 'f' => 0, 't' => 0],
                            'absent' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            'sat' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            'inc' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            'division' => [
                                'i' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                                'ii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                                'iii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                                'i_iii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                                'iv' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                                'i_iv' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                                'zero' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            ],
                            'gpa_sum' => 0.0,
                            'gpa_count' => 0,
                            'gpa' => null,
                        ];
                    }

                    $genderValue = strtoupper(trim((string) ($row->gender ?? '')));
                    $gender = match ($genderValue) {
                        'F' => 'f',
                        'M' => 'm',
                        default => null,
                    };

                    $bucket = &$districtBuckets[$districtKey];
                    $bucket['registered']['t']++;
                    if ($gender !== null) {
                        $bucket['registered'][$gender]++;
                    }

                    $resultStatus = strtoupper(trim((string) ($row->resolved_result_status ?? 'COMPLETE')));
                    if ($resultStatus === 'ABS') {
                        $bucket['absent']['t']++;
                        if ($gender !== null) {
                            $bucket['absent'][$gender]++;
                        }
                        unset($bucket);
                        continue;
                    }

                    if ($resultStatus === 'INC') {
                        $bucket['inc']['t']++;
                        if ($gender !== null) {
                            $bucket['inc'][$gender]++;
                        }
                    }

                    $divisionValue = (int) ($row->resolved_division ?? 0);
                    if ($resultStatus !== 'INC') {
                        $group = match ($divisionValue) {
                            1 => 'i',
                            2 => 'ii',
                            3 => 'iii',
                            4 => 'iv',
                            default => 'zero',
                        };
                        $bucket['division'][$group]['t']++;
                        if ($gender !== null) {
                            $bucket['division'][$group][$gender]++;
                        }
                    }

                    $gpaValue = $row->resolved_gpa ?? null;
                    if ($resultStatus !== 'INC' && !is_null($gpaValue) && $gpaValue !== '') {
                        $bucket['gpa_sum'] += (float) $gpaValue;
                        $bucket['gpa_count']++;
                    }
                    unset($bucket);
                }

                $displayRows = collect($districtBuckets)
                    ->map(function ($bucket) {
                        $regT = max((int) $bucket['registered']['t'], 0);
                        $absT = (int) $bucket['absent']['t'];
                        $incT = (int) $bucket['inc']['t'];
                        $bucket['sat']['m'] = max((int) $bucket['registered']['m'] - (int) $bucket['absent']['m'], 0);
                        $bucket['sat']['f'] = max((int) $bucket['registered']['f'] - (int) $bucket['absent']['f'], 0);
                        $bucket['sat']['t'] = $bucket['sat']['m'] + $bucket['sat']['f'];
                        $satT = max((int) $bucket['sat']['t'], 0);

                        $bucket['absent']['pct'] = $regT > 0 ? ($absT / $regT) * 100 : 0.0;
                        $bucket['sat']['pct'] = $regT > 0 ? ($satT / $regT) * 100 : 0.0;
                        $bucket['inc']['pct'] = $regT > 0 ? ($incT / $regT) * 100 : 0.0;
                        $bucket['division']['i_iii']['m'] = $bucket['division']['i']['m'] + $bucket['division']['ii']['m'] + $bucket['division']['iii']['m'];
                        $bucket['division']['i_iii']['f'] = $bucket['division']['i']['f'] + $bucket['division']['ii']['f'] + $bucket['division']['iii']['f'];
                        $bucket['division']['i_iii']['t'] = $bucket['division']['i_iii']['m'] + $bucket['division']['i_iii']['f'];
                        $bucket['division']['i_iv']['m'] = $bucket['division']['i_iii']['m'] + $bucket['division']['iv']['m'];
                        $bucket['division']['i_iv']['f'] = $bucket['division']['i_iii']['f'] + $bucket['division']['iv']['f'];
                        $bucket['division']['i_iv']['t'] = $bucket['division']['i_iv']['m'] + $bucket['division']['i_iv']['f'];

                        foreach (['i', 'ii', 'iii', 'i_iii', 'iv', 'i_iv', 'zero'] as $group) {
                            $bucket['division'][$group]['pct'] = $satT > 0
                                ? ((int) $bucket['division'][$group]['t'] / $satT) * 100
                                : 0.0;
                        }

                        $bucket['gpa'] = $bucket['gpa_count'] > 0
                            ? round($bucket['gpa_sum'] / $bucket['gpa_count'], 4)
                            : null;

                        unset($bucket['gpa_sum'], $bucket['gpa_count']);
                        return $bucket;
                    })
                    ->sortBy(fn ($row) => is_null($row['gpa']) ? INF : $row['gpa'])
                    ->values()
                    ->map(function ($row, $idx) {
                        $row['pos'] = $idx + 1;
                        return $row;
                    });

                $total = [
                    'registered' => ['m' => 0, 'f' => 0, 't' => 0],
                    'absent' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                    'sat' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                    'inc' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                    'division' => [
                        'i' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'ii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'iii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'i_iii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'iv' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'i_iv' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'zero' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                    ],
                ];

                foreach ($displayRows as $row) {
                    foreach (['m', 'f', 't'] as $key) {
                        $total['registered'][$key] += $row['registered'][$key];
                        $total['absent'][$key] += $row['absent'][$key];
                        $total['sat'][$key] += $row['sat'][$key];
                        $total['inc'][$key] += $row['inc'][$key];
                    }
                    foreach (['i', 'ii', 'iii', 'i_iii', 'iv', 'i_iv', 'zero'] as $group) {
                        foreach (['m', 'f', 't'] as $key) {
                            $total['division'][$group][$key] += $row['division'][$group][$key];
                        }
                    }
                }

                $total['absent']['pct'] = $total['registered']['t'] > 0 ? ($total['absent']['t'] / $total['registered']['t']) * 100 : 0;
                $total['sat']['pct'] = $total['registered']['t'] > 0 ? ($total['sat']['t'] / $total['registered']['t']) * 100 : 0;
                $total['inc']['pct'] = $total['registered']['t'] > 0 ? ($total['inc']['t'] / $total['registered']['t']) * 100 : 0;
                foreach (['i', 'ii', 'iii', 'i_iii', 'iv', 'i_iv', 'zero'] as $group) {
                    $total['division'][$group]['pct'] = $total['sat']['t'] > 0
                        ? ($total['division'][$group]['t'] / $total['sat']['t']) * 100
                        : 0;
                }

                if (strtolower((string) request()->query('export')) === 'pdf') {
                    $safeRegion = \Illuminate\Support\Str::slug((string) $region->name);
                    $filename = "acsee_regional_districtwise_{$safeRegion}_{$examYearValue}.pdf";
                    $tempPath = tempnam(sys_get_temp_dir(), 'acsee_district_');
                    if ($tempPath === false) {
                        abort(500, 'Unable to prepare PDF export file.');
                    }
                    $pdfPath = $tempPath . '.pdf';
                    @rename($tempPath, $pdfPath);

                    app(\App\Services\Results\AcseeRegionalSchoolwiseFpdfService::class)
                        ->generate(
                            $region,
                            $examYearValue,
                            $displayRows->all(),
                            $total,
                            $pdfPath,
                            (string) data_get($evaluationMap->get($evaluation), 'label', 'DISTRICTWISE'),
                            [
                                'first_column_label' => 'DISTRICT',
                                'first_column_key' => 'district',
                                'first_column_width' => 92,
                                'hide_second_column' => true,
                                'metric3_width' => 10,
                                'metric4_width' => 9.2,
                                'gpa_width' => 24,
                                'pos_width' => 18,
                            ]
                        );

                    return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
                }

                return view('evaluations.acsee-regionalwise-schoolwise', [
                    'region' => $region,
                    'evaluationLabel' => data_get($evaluationMap->get($evaluation), 'label'),
                    'examYearValue' => $examYearValue,
                    'rows' => $displayRows,
                    'total' => $total,
                    'tableMode' => 'districtwise',
                ]);
                break;

            case 'ownership-result-evaluation':
                $ownershipBuckets = [];
                foreach ($enrichedRows as $row) {
                    $ownership = strtoupper(trim((string) ($row->ownership ?? 'UNKNOWN')));
                    if ($ownership === '') {
                        $ownership = 'UNKNOWN';
                    }

                    if (!isset($ownershipBuckets[$ownership])) {
                        $ownershipBuckets[$ownership] = [
                            'ownership' => $ownership,
                            'schools_count' => 0,
                            'school_ids' => [],
                            'registered' => ['m' => 0, 'f' => 0, 't' => 0],
                            'absent' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            'sat' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            'inc' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            'division' => [
                                'i' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                                'ii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                                'iii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                                'i_iii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                                'iv' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                                'i_iv' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                                'zero' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                            ],
                            'gpa_sum' => 0.0,
                            'gpa_count' => 0,
                            'gpa' => null,
                        ];
                    }

                    $bucket = &$ownershipBuckets[$ownership];

                    if (!empty($row->school_id)) {
                        $bucket['school_ids'][(string) ((int) $row->school_id)] = true;
                    }

                    $genderValue = strtoupper(trim((string) ($row->gender ?? '')));
                    $gender = match ($genderValue) {
                        'F' => 'f',
                        'M' => 'm',
                        default => null,
                    };

                    $bucket['registered']['t']++;
                    if ($gender !== null) {
                        $bucket['registered'][$gender]++;
                    }

                    $resultStatus = strtoupper(trim((string) ($row->resolved_result_status ?? 'COMPLETE')));

                    if ($resultStatus === 'ABS') {
                        $bucket['absent']['t']++;
                        if ($gender !== null) {
                            $bucket['absent'][$gender]++;
                        }
                        unset($bucket);
                        continue;
                    }

                    if ($resultStatus === 'INC') {
                        $bucket['inc']['t']++;
                        if ($gender !== null) {
                            $bucket['inc'][$gender]++;
                        }
                    }

                    $divisionValue = (int) ($row->resolved_division ?? 0);
                    if ($resultStatus !== 'INC') {
                        $group = match ($divisionValue) {
                            1 => 'i',
                            2 => 'ii',
                            3 => 'iii',
                            4 => 'iv',
                            default => 'zero',
                        };

                        $bucket['division'][$group]['t']++;
                        if ($gender !== null) {
                            $bucket['division'][$group][$gender]++;
                        }
                    }

                    $gpaValue = $row->resolved_gpa ?? null;
                    if ($resultStatus !== 'INC' && !is_null($gpaValue) && $gpaValue !== '') {
                        $bucket['gpa_sum'] += (float) $gpaValue;
                        $bucket['gpa_count']++;
                    }

                    unset($bucket);
                }

                $displayRows = collect($ownershipBuckets)
                    ->map(function ($bucket) {
                        $bucket['schools_count'] = count($bucket['school_ids']);
                        unset($bucket['school_ids']);

                        $regT = max((int) $bucket['registered']['t'], 0);
                        $absT = (int) $bucket['absent']['t'];
                        $incT = (int) $bucket['inc']['t'];

                        $bucket['sat']['m'] = max((int) $bucket['registered']['m'] - (int) $bucket['absent']['m'], 0);
                        $bucket['sat']['f'] = max((int) $bucket['registered']['f'] - (int) $bucket['absent']['f'], 0);
                        $bucket['sat']['t'] = $bucket['sat']['m'] + $bucket['sat']['f'];
                        $satT = max((int) $bucket['sat']['t'], 0);

                        $bucket['absent']['pct'] = $regT > 0 ? ($absT / $regT) * 100 : 0.0;
                        $bucket['sat']['pct'] = $regT > 0 ? ($satT / $regT) * 100 : 0.0;
                        $bucket['inc']['pct'] = $regT > 0 ? ($incT / $regT) * 100 : 0.0;

                        $bucket['division']['i_iii']['m'] = $bucket['division']['i']['m'] + $bucket['division']['ii']['m'] + $bucket['division']['iii']['m'];
                        $bucket['division']['i_iii']['f'] = $bucket['division']['i']['f'] + $bucket['division']['ii']['f'] + $bucket['division']['iii']['f'];
                        $bucket['division']['i_iii']['t'] = $bucket['division']['i_iii']['m'] + $bucket['division']['i_iii']['f'];

                        $bucket['division']['i_iv']['m'] = $bucket['division']['i_iii']['m'] + $bucket['division']['iv']['m'];
                        $bucket['division']['i_iv']['f'] = $bucket['division']['i_iii']['f'] + $bucket['division']['iv']['f'];
                        $bucket['division']['i_iv']['t'] = $bucket['division']['i_iv']['m'] + $bucket['division']['i_iv']['f'];

                        foreach (['i', 'ii', 'iii', 'i_iii', 'iv', 'i_iv', 'zero'] as $grp) {
                            $bucket['division'][$grp]['pct'] = $satT > 0
                                ? ((int) $bucket['division'][$grp]['t'] / $satT) * 100
                                : 0.0;
                        }

                        $bucket['gpa'] = $bucket['gpa_count'] > 0
                            ? round($bucket['gpa_sum'] / $bucket['gpa_count'], 4)
                            : null;

                        unset($bucket['gpa_sum'], $bucket['gpa_count']);
                        return $bucket;
                    })
                    ->sortBy(fn ($row) => is_null($row['gpa']) ? INF : $row['gpa'])
                    ->values()
                    ->map(function ($row, $idx) {
                        $row['pos'] = $idx + 1;
                        return $row;
                    });

                $total = [
                    'registered' => ['m' => 0, 'f' => 0, 't' => 0],
                    'absent' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                    'sat' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                    'inc' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                    'division' => [
                        'i' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'ii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'iii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'i_iii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'iv' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'i_iv' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                        'zero' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0],
                    ],
                ];

                foreach ($displayRows as $row) {
                    foreach (['m', 'f', 't'] as $key) {
                        $total['registered'][$key] += $row['registered'][$key];
                        $total['absent'][$key] += $row['absent'][$key];
                        $total['sat'][$key] += $row['sat'][$key];
                        $total['inc'][$key] += $row['inc'][$key];
                    }

                    foreach (['i', 'ii', 'iii', 'i_iii', 'iv', 'i_iv', 'zero'] as $group) {
                        foreach (['m', 'f', 't'] as $key) {
                            $total['division'][$group][$key] += $row['division'][$group][$key];
                        }
                    }
                }

                $total['absent']['pct'] = $total['registered']['t'] > 0 ? ($total['absent']['t'] / $total['registered']['t']) * 100 : 0;
                $total['sat']['pct'] = $total['registered']['t'] > 0 ? ($total['sat']['t'] / $total['registered']['t']) * 100 : 0;
                $total['inc']['pct'] = $total['registered']['t'] > 0 ? ($total['inc']['t'] / $total['registered']['t']) * 100 : 0;
                foreach (['i', 'ii', 'iii', 'i_iii', 'iv', 'i_iv', 'zero'] as $group) {
                    $total['division'][$group]['pct'] = $total['sat']['t'] > 0
                        ? ($total['division'][$group]['t'] / $total['sat']['t']) * 100
                        : 0;
                }

                if (strtolower((string) request()->query('export')) === 'pdf') {
                    $safeRegion = \Illuminate\Support\Str::slug((string) $region->name);
                    $filename = "acsee_regional_ownership_{$safeRegion}_{$examYearValue}.pdf";
                    $tempPath = tempnam(sys_get_temp_dir(), 'acsee_ownership_');
                    if ($tempPath === false) {
                        abort(500, 'Unable to prepare PDF export file.');
                    }
                    $pdfPath = $tempPath . '.pdf';
                    @rename($tempPath, $pdfPath);

                    app(\App\Services\Results\AcseeRegionalSchoolwiseFpdfService::class)
                        ->generate(
                            $region,
                            $examYearValue,
                            $displayRows->all(),
                            $total,
                            $pdfPath,
                            (string) data_get($evaluationMap->get($evaluation), 'label', 'OWNERSHIP'),
                            [
                                'first_column_label' => 'OWNERSHIP',
                                'second_column_label' => 'SCHOOLS',
                                'first_column_key' => 'ownership',
                                'second_column_key' => 'schools_count',
                                'second_column_align' => 'C',
                                'first_column_width' => 78,
                                'second_column_width' => 56,
                            ]
                        );

                    return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
                }

                return view('evaluations.acsee-regionalwise-schoolwise', [
                    'region' => $region,
                    'evaluationLabel' => data_get($evaluationMap->get($evaluation), 'label'),
                    'examYearValue' => $examYearValue,
                    'rows' => $displayRows,
                    'total' => $total,
                    'tableMode' => 'ownership',
                ]);
                break;

            case 'best-ten-girls':
            case 'least-ten-girls':
            case 'best-ten-boys':
            case 'least-ten-boys':
            case 'overall-best-ten-students':
            case 'overall-least-ten-students':
                $candidateQuery = $completeRows->filter(fn ($row) => $row->resolved_gpa !== null);

                if ($evaluation === 'best-ten-girls' || $evaluation === 'least-ten-girls') {
                    $candidateQuery = $candidateQuery->filter(fn ($row) => strtoupper((string) $row->gender) === 'F');
                }
                if ($evaluation === 'best-ten-boys' || $evaluation === 'least-ten-boys') {
                    $candidateQuery = $candidateQuery->filter(fn ($row) => strtoupper((string) $row->gender) === 'M');
                }

                $isBest = in_array($evaluation, ['best-ten-girls', 'best-ten-boys', 'overall-best-ten-students'], true);
                $candidateRows = $isBest
                    ? $candidateQuery->sortBy(fn ($row) => [$row->resolved_gpa ?? INF, $row->resolved_division])->take(10)->values()
                    : $candidateQuery->sortByDesc(fn ($row) => [$row->resolved_gpa ?? -INF, -1 * $row->resolved_division])->take(10)->values();

                $candidateIds = $candidateRows
                    ->pluck('candidate_id')
                    ->filter(fn ($value) => !is_null($value))
                    ->map(fn ($value) => (int) $value)
                    ->values();

                $subjectResultsByCandidate = \Illuminate\Support\Facades\DB::table('subject_marks as sm')
                    ->join('subjects as sb', 'sb.id', '=', 'sm.subject_id')
                    ->where('sm.exam_type_id', $examType->id)
                    ->where('sm.year', $examYearValue)
                    ->whereIn('sm.candidate_id', $candidateIds->all())
                    ->get([
                        'sm.id',
                        'sm.candidate_id',
                        'sm.subject_id',
                        'sb.code as subject_code',
                        'sb.name as subject_name',
                        'sb.written_papers',
                        'sb.has_practical',
                        'sm.marks_obtained',
                        'sm.paper_1',
                        'sm.paper_2',
                        'sm.paper_3',
                        'sm.grade',
                        'sm.subject_status',
                    ])
                    ->groupBy('candidate_id')
                    ->map(function ($items) {
                        $aliases = (array) config('necta_subject_aliases.acsee', []);

                        $requiredPaperCodesForSubject = function ($item): array {
                            $codes = [];
                            $written = max(1, min(2, (int) ($item->written_papers ?? 1)));
                            for ($i = 1; $i <= $written; $i++) {
                                $codes[] = "paper_{$i}";
                            }
                            if (!empty($item->has_practical)) {
                                $codes[] = 'paper_3';
                            }
                            return array_values(array_unique($codes));
                        };

                        return $items
                            ->groupBy('subject_id')
                            ->map(function ($subjectRows) use ($requiredPaperCodesForSubject) {
                                $rows = collect($subjectRows)->sortByDesc('id')->values();
                                $subject = $rows->first();
                                $required = $requiredPaperCodesForSubject($subject);
                                $positiveByPaper = [];
                                foreach ($required as $paperCode) {
                                    $positiveByPaper[$paperCode] = $rows->contains(function ($mark) use ($paperCode) {
                                        $value = $mark->{$paperCode} ?? null;
                                        return $value !== null && (float) $value > 0;
                                    });
                                }

                                $preferred = $rows->first(function ($mark) use ($required, $positiveByPaper) {
                                    $status = strtoupper((string) ($mark->subject_status ?? ''));
                                    if ($status === 'INC') {
                                        return false;
                                    }
                                    foreach ($required as $paperCode) {
                                        $value = $mark->{$paperCode} ?? null;
                                        if ($value === null) {
                                            return false;
                                        }
                                        if (($positiveByPaper[$paperCode] ?? false) && (float) $value <= 0) {
                                            return false;
                                        }
                                    }
                                    return true;
                                });

                                return $preferred ?: $rows->first();
                            })
                            ->filter()
                            ->sortBy(function ($item) use ($aliases) {
                                $subjectCode = strtoupper(trim((string) ($item->subject_code ?? '')));
                                $subjectName = strtoupper(trim((string) ($item->subject_name ?? '')));
                                $subjectLabel = $subjectCode !== '' && isset($aliases[$subjectCode])
                                    ? strtoupper((string) $aliases[$subjectCode])
                                    : $subjectName;
                                $isGeneralStudies = $subjectCode === '111'
                                    || $subjectName === 'GENERAL STUDIES'
                                    || $subjectLabel === 'G/STUDIES';

                                return sprintf(
                                    '%d_%s_%s',
                                    $isGeneralStudies ? 0 : 1,
                                    $subjectCode !== '' ? $subjectCode : 'ZZZ',
                                    $subjectName
                                );
                            })
                            ->values()
                            ->map(function ($item) use ($aliases) {
                                $subjectCode = strtoupper(trim((string) ($item->subject_code ?? '')));
                                $subjectName = strtoupper(trim((string) ($item->subject_name ?? '')));
                                $subjectLabel = $subjectCode !== '' && isset($aliases[$subjectCode])
                                    ? strtoupper((string) $aliases[$subjectCode])
                                    : $subjectName;
                                $status = strtoupper(trim((string) ($item->subject_status ?? '')));
                                $grade = strtoupper(trim((string) ($item->grade ?? '')));
                                $marks = is_null($item->marks_obtained)
                                    ? null
                                    : rtrim(rtrim(number_format((float) $item->marks_obtained, 2, '.', ''), '0'), '.');

                                if (in_array($status, ['ABS', 'X'], true)) {
                                    $resultText = $status . " '" . $status . "'";
                                } elseif ($status === 'INC' || ($marks === null && $grade === '')) {
                                    $resultText = "INC 'INC'";
                                } else {
                                    $resultText = ($marks ?? '-') . ($grade !== '' ? " '" . $grade . "'" : '');
                                }

                                return [
                                    'marks' => is_null($item->marks_obtained) ? null : (float) $item->marks_obtained,
                                    'text' => ($subjectLabel !== '' ? $subjectLabel : 'SUBJECT') . '=' . $resultText,
                                ];
                            })
                            ->all();
                    });

                $gradingService = app(\App\Services\Results\NectaGradingService::class);

                $displayRows = $candidateRows->values()->map(function ($row) use ($subjectResultsByCandidate, $gradingService, $finalByCandidate) {
                    $fullName = trim((string) ($row->full_name ?? ''));
                    $divisionValue = (int) ($row->resolved_division ?? -1);
                    $divisionLabelMap = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 0 => '0'];
                    $division = array_key_exists($divisionValue, $divisionLabelMap)
                        ? $divisionLabelMap[$divisionValue]
                        : '-';
                    $candidateId = !is_null($row->candidate_id ?? null) ? (int) $row->candidate_id : null;
                    $subjectRows = collect($candidateId ? ($subjectResultsByCandidate->get($candidateId) ?? []) : []);
                    $scoredSubjects = $subjectRows
                        ->filter(fn ($item) => is_numeric(data_get($item, 'marks')));
                    $totalMarks = (float) $scoredSubjects->sum(fn ($item) => (float) data_get($item, 'marks', 0));
                    $avgMarks = $scoredSubjects->count() > 0 ? round($totalMarks / $scoredSubjects->count(), 2) : null;
                    $overallGrade = trim((string) ($row->resolved_overall_grade_source ?? data_get($finalByCandidate->get($candidateId), 'resolved_overall_grade_source', '')));
                    if ($overallGrade === '' && !is_null($avgMarks)) {
                        $overallGrade = $gradingService->calculateGrade((float) $avgMarks);
                    }
                    $decodedBreakdown = is_array($row->resolved_breakdown_source ?? null)
                        ? $row->resolved_breakdown_source
                        : json_decode((string) ($row->resolved_breakdown_source ?? ''), true);
                    $aggt = data_get($decodedBreakdown, 'aggt_points');

                    return [
                        'candidate_id' => $candidateId,
                        'index_number' => (string) ($row->index_number ?? '-'),
                        'candidate' => strtoupper($fullName !== '' ? $fullName : 'N/A'),
                        'school' => strtoupper(trim((string) ($row->school_name ?? '-'))) ?: '-',
                        'council' => strtoupper(trim((string) ($row->council_name ?? '-'))) ?: '-',
                        'combination' => strtoupper(trim((string) ($row->combination ?? '-'))) ?: '-',
                        'sex' => strtoupper((string) ($row->gender ?? '-')),
                        'total_marks' => $totalMarks > 0 ? round($totalMarks, 0) : null,
                        'avg_marks' => $avgMarks,
                        'overall_grade' => $overallGrade !== '' ? strtoupper($overallGrade) : '-',
                        'aggt' => is_null($aggt) ? null : (int) round((float) $aggt),
                        'gpa' => (float) ($row->resolved_gpa ?? 0),
                        'division' => $division,
                        'subject_results_text' => collect($candidateId ? ($subjectResultsByCandidate->get($candidateId) ?? []) : [])
                            ->pluck('text')
                            ->implode(', '),
                    ];
                })
                    ->sort(function (array $left, array $right) {
                        $leftTotal = $left['total_marks'] ?? -INF;
                        $rightTotal = $right['total_marks'] ?? -INF;
                        if ($leftTotal !== $rightTotal) {
                            return $rightTotal <=> $leftTotal;
                        }

                        $leftGpa = $left['gpa'] ?? INF;
                        $rightGpa = $right['gpa'] ?? INF;
                        if ($leftGpa !== $rightGpa) {
                            return $leftGpa <=> $rightGpa;
                        }

                        return strcmp((string) ($left['index_number'] ?? ''), (string) ($right['index_number'] ?? ''));
                    })
                    ->values()
                    ->map(function (array $row, int $position) {
                        $row['position'] = $position + 1;

                        return $row;
                    })
                    ->values();

                $summary = [
                    'students' => number_format($displayRows->count()),
                    'avg_gpa' => number_format((float) ($displayRows->pluck('gpa')->avg() ?? 0), 2),
                    'best_gpa' => number_format((float) ($isBest ? ($displayRows->pluck('gpa')->min() ?? 0) : ($displayRows->pluck('gpa')->max() ?? 0)), 2),
                    'sex' => match ($evaluation) {
                        'best-ten-girls', 'least-ten-girls' => 'FEMALE',
                        'best-ten-boys', 'least-ten-boys' => 'MALE',
                        default => 'MIXED',
                    },
                ];

                if (strtolower((string) request()->query('export')) === 'pdf') {
                    $safeRegion = \Illuminate\Support\Str::slug((string) $region->name);
                    $exportKey = \Illuminate\Support\Str::slug((string) $evaluation, '_');
                    $filename = "acsee_regional_{$exportKey}_{$safeRegion}_{$examYearValue}.pdf";
                    $tempPath = tempnam(sys_get_temp_dir(), 'acsee_students_');
                    if ($tempPath === false) {
                        abort(500, 'Unable to prepare PDF export file.');
                    }
                    $pdfPath = $tempPath . '.pdf';
                    @rename($tempPath, $pdfPath);

                    app(\App\Services\Results\AcseeRegionalStudentRankingFpdfService::class)
                        ->generate(
                            $region,
                            $examYearValue,
                            $displayRows->all(),
                            $summary,
                            $pdfPath,
                            (string) data_get($evaluationMap->get($evaluation), 'label', 'STUDENT RANKING')
                        );

                    return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
                }

                return view('evaluations.acsee-regionalwise-student-ranking', [
                    'region' => $region,
                    'evaluationKey' => $evaluation,
                    'evaluationLabel' => data_get($evaluationMap->get($evaluation), 'label'),
                    'examYearValue' => $examYearValue,
                    'rows' => $displayRows,
                    'summary' => $summary,
                ]);
                break;

            case 'subjectwise-result-evaluation':
            case 'subject-summary-evaluation':
                $gradingService = app(\App\Services\Results\NectaGradingService::class);
                $subjectRows = \Illuminate\Support\Facades\DB::table('subject_marks as sm')
                    ->join('candidates as c', 'c.id', '=', 'sm.candidate_id')
                    ->join('schools as s', 's.id', '=', 'c.school_id')
                    ->join('subjects as sb', 'sb.id', '=', 'sm.subject_id')
                    ->where('s.region_id', $region->id)
                    ->where('sm.exam_type_id', $examType->id)
                    ->where('sm.year', $examYearValue)
                    ->selectRaw('sb.code as subject_code, sb.name as subject_name')
                    ->selectRaw('COUNT(*) as entries')
                    ->selectRaw('ROUND(AVG(sm.marks_obtained), 2) as avg_marks')
                    ->selectRaw("SUM(CASE WHEN UPPER(sm.grade) = 'A' THEN 1 ELSE 0 END) as grade_a")
                    ->selectRaw("SUM(CASE WHEN UPPER(sm.grade) = 'B' THEN 1 ELSE 0 END) as grade_b")
                    ->selectRaw("SUM(CASE WHEN UPPER(sm.grade) = 'C' THEN 1 ELSE 0 END) as grade_c")
                    ->selectRaw("SUM(CASE WHEN UPPER(sm.grade) = 'D' THEN 1 ELSE 0 END) as grade_d")
                    ->selectRaw("SUM(CASE WHEN UPPER(sm.grade) = 'E' THEN 1 ELSE 0 END) as grade_e")
                    ->selectRaw("SUM(CASE WHEN UPPER(sm.grade) = 'S' THEN 1 ELSE 0 END) as grade_s")
                    ->selectRaw("SUM(CASE WHEN UPPER(sm.grade) = 'F' THEN 1 ELSE 0 END) as grade_f")
                    ->groupBy('sb.id', 'sb.code', 'sb.name')
                    ->orderByDesc('avg_marks')
                    ->orderBy('sb.name')
                    ->get()
                    ->map(function ($row, $idx) use ($gradingService) {
                        $graded = (int) ($row->grade_a ?? 0)
                            + (int) ($row->grade_b ?? 0)
                            + (int) ($row->grade_c ?? 0)
                            + (int) ($row->grade_d ?? 0)
                            + (int) ($row->grade_e ?? 0)
                            + (int) ($row->grade_s ?? 0)
                            + (int) ($row->grade_f ?? 0);
                        $total = $graded;
                        $gpa = $graded > 0
                            ? round((
                                ((int) ($row->grade_a ?? 0) * 1)
                                + ((int) ($row->grade_b ?? 0) * 2)
                                + ((int) ($row->grade_c ?? 0) * 3)
                                + ((int) ($row->grade_d ?? 0) * 4)
                                + ((int) ($row->grade_e ?? 0) * 5)
                                + ((int) ($row->grade_s ?? 0) * 6)
                                + ((int) ($row->grade_f ?? 0) * 7)
                            ) / $graded, 4)
                            : null;
                        $competence = $gpa !== null ? $gradingService->getGpaCompetence($gpa) : null;

                        return [
                            'code' => (string) ($row->subject_code ?? ''),
                            'name' => (string) ($row->subject_name ?? ''),
                            'subject' => trim(((string) ($row->subject_code ?? '')) . ' - ' . ((string) ($row->subject_name ?? ''))),
                            'entries' => (int) ($row->entries ?? 0),
                            'avg_marks' => (float) ($row->avg_marks ?? 0),
                            'grade_a' => (int) ($row->grade_a ?? 0),
                            'grade_b' => (int) ($row->grade_b ?? 0),
                            'grade_c' => (int) ($row->grade_c ?? 0),
                            'grade_d' => (int) ($row->grade_d ?? 0),
                            'grade_e' => (int) ($row->grade_e ?? 0),
                            'grade_s' => (int) ($row->grade_s ?? 0),
                            'grade_f' => (int) ($row->grade_f ?? 0),
                            'total' => $total,
                            'gpa' => $gpa,
                            'competence' => $competence,
                            'pos' => $idx + 1,
                        ];
                    })
                    ->values();

                $divisionSummary = [
                    'F' => ['I' => 0, 'II' => 0, 'III' => 0, 'IV' => 0, '0' => 0, 'INC' => 0, 'ABS' => 0],
                    'M' => ['I' => 0, 'II' => 0, 'III' => 0, 'IV' => 0, '0' => 0, 'INC' => 0, 'ABS' => 0],
                ];

                foreach ($enrichedRows as $row) {
                    $sex = strtoupper(trim((string) ($row->gender ?? '')));
                    if (!in_array($sex, ['F', 'M'], true)) {
                        continue;
                    }

                    $status = strtoupper(trim((string) ($row->resolved_result_status ?? 'COMPLETE')));
                    if ($status === 'ABS') {
                        $divisionSummary[$sex]['ABS']++;
                        continue;
                    }

                    if ($status === 'INC') {
                        $divisionSummary[$sex]['INC']++;
                        continue;
                    }

                    $division = (int) ($row->resolved_division ?? 0);
                    $key = match ($division) {
                        1 => 'I',
                        2 => 'II',
                        3 => 'III',
                        4 => 'IV',
                        default => '0',
                    };
                    $divisionSummary[$sex][$key]++;
                }

                $divisionSummary['T'] = [
                    'I' => $divisionSummary['F']['I'] + $divisionSummary['M']['I'],
                    'II' => $divisionSummary['F']['II'] + $divisionSummary['M']['II'],
                    'III' => $divisionSummary['F']['III'] + $divisionSummary['M']['III'],
                    'IV' => $divisionSummary['F']['IV'] + $divisionSummary['M']['IV'],
                    '0' => $divisionSummary['F']['0'] + $divisionSummary['M']['0'],
                    'INC' => $divisionSummary['F']['INC'] + $divisionSummary['M']['INC'],
                    'ABS' => $divisionSummary['F']['ABS'] + $divisionSummary['M']['ABS'],
                ];

                $overallGpa = round((float) ($completeRows->pluck('resolved_gpa')->filter(fn ($v) => $v !== null)->avg() ?? 0), 4);
                $summary = [
                    'subjects' => number_format($subjectRows->count()),
                    'entries' => number_format($subjectRows->sum('entries')),
                    'avg_marks' => number_format((float) ($subjectRows->pluck('avg_marks')->avg() ?? 0), 2),
                    'grade_a' => number_format($subjectRows->sum('grade_a')),
                    'grade_b' => number_format($subjectRows->sum('grade_b')),
                    'grade_c' => number_format($subjectRows->sum('grade_c')),
                    'grade_d' => number_format($subjectRows->sum('grade_d')),
                    'grade_e' => number_format($subjectRows->sum('grade_e')),
                    'grade_s' => number_format($subjectRows->sum('grade_s')),
                    'grade_f' => number_format($subjectRows->sum('grade_f')),
                    'division_summary' => $divisionSummary,
                    'overall' => [
                        'region' => strtoupper((string) $region->name),
                        'passed' => $divisionSummary['T']['I'] + $divisionSummary['T']['II'] + $divisionSummary['T']['III'] + $divisionSummary['T']['IV'],
                        'gpa' => $overallGpa,
                        'gpa_info' => $overallGpa > 0 ? $gradingService->getGpaCompetence($overallGpa) : null,
                    ],
                ];

                if (strtolower((string) request()->query('export')) === 'pdf') {
                    $safeRegion = \Illuminate\Support\Str::slug((string) $region->name);
                    $exportKey = $evaluation === 'subject-summary-evaluation' ? 'subject_summary' : 'subjectwise';
                    $filename = "acsee_regional_{$exportKey}_{$safeRegion}_{$examYearValue}.pdf";
                    $tempPath = tempnam(sys_get_temp_dir(), 'acsee_subjectwise_');
                    if ($tempPath === false) {
                        abort(500, 'Unable to prepare PDF export file.');
                    }
                    $pdfPath = $tempPath . '.pdf';
                    @rename($tempPath, $pdfPath);

                    app(\App\Services\Results\AcseeRegionalSubjectwiseSummaryFpdfService::class)
                        ->generate(
                            $region,
                            $examYearValue,
                            $subjectRows->all(),
                            $summary,
                            $pdfPath,
                            (string) data_get($evaluationMap->get($evaluation), 'label', 'SUBJECTWISE RESULT EVALUATION')
                        );

                    return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
                }

                return view('evaluations.acsee-regionalwise-subjectwise', [
                    'region' => $region,
                    'evaluationKey' => $evaluation,
                    'evaluationLabel' => data_get($evaluationMap->get($evaluation), 'label'),
                    'examYearValue' => $examYearValue,
                    'rows' => $subjectRows,
                    'summary' => $summary,
                ]);
                break;

            case 'mark-entry-status-report':
                $markRows = \Illuminate\Support\Facades\DB::table('candidate_subject_selections as css')
                    ->join('candidates as c', 'c.id', '=', 'css.candidate_id')
                    ->join('schools as s', 's.id', '=', 'c.school_id')
                    ->join('subjects as sb', 'sb.id', '=', 'css.subject_id')
                    ->leftJoin('subject_marks as sm', function ($join) use ($examType, $examYearValue) {
                        $join->on('sm.candidate_id', '=', 'css.candidate_id')
                            ->on('sm.subject_id', '=', 'css.subject_id')
                            ->where('sm.exam_type_id', '=', $examType->id)
                            ->where('sm.year', '=', $examYearValue);
                    })
                    ->where('s.region_id', $region->id)
                    ->where('css.exam_type_id', $examType->id)
                    ->where('css.year', $examYearValue)
                    ->where('css.is_active', true)
                    ->selectRaw('sb.code as subject_code, sb.name as subject_name')
                    ->selectRaw('COUNT(*) as expected_entries')
                    ->selectRaw('SUM(CASE WHEN sm.id IS NOT NULL THEN 1 ELSE 0 END) as marked_entries')
                    ->groupBy('sb.id', 'sb.code', 'sb.name')
                    ->orderBy('sb.name')
                    ->get()
                    ->map(function ($row, $idx) {
                    $expected = (int) ($row->expected_entries ?? 0);
                    $marked = (int) ($row->marked_entries ?? 0);
                    $pending = max($expected - $marked, 0);
                    $completion = $expected > 0 ? ($marked / $expected) * 100 : 0;
                    $status = match (true) {
                        $completion >= 100 => 'Complete',
                        $completion >= 80 => 'Near Complete',
                        $completion > 0 => 'In Progress',
                        default => 'Not Started',
                    };
                    return [
                        'code' => (string) ($row->subject_code ?? ''),
                        'name' => (string) ($row->subject_name ?? ''),
                        'subject' => trim(((string) ($row->subject_code ?? '')) . ' - ' . ((string) ($row->subject_name ?? ''))),
                        'expected_entries' => $expected,
                        'marked_entries' => $marked,
                        'pending_entries' => $pending,
                        'completion' => round($completion, 1),
                        'status' => $status,
                        'pos' => $idx + 1,
                    ];
                })->values();

                $summary = [
                    'subjects' => number_format($markRows->count()),
                    'expected_entries' => number_format($markRows->sum('expected_entries')),
                    'marked_entries' => number_format($markRows->sum('marked_entries')),
                    'pending_entries' => number_format($markRows->sum('pending_entries')),
                    'completion' => number_format($markRows->sum('expected_entries') > 0 ? ($markRows->sum('marked_entries') / $markRows->sum('expected_entries')) * 100 : 0, 1),
                ];

                if (strtolower((string) request()->query('export')) === 'pdf') {
                    $safeRegion = \Illuminate\Support\Str::slug((string) $region->name);
                    $filename = "acsee_regional_mark_entry_status_{$safeRegion}_{$examYearValue}.pdf";
                    $tempPath = tempnam(sys_get_temp_dir(), 'acsee_mark_status_');
                    if ($tempPath === false) {
                        abort(500, 'Unable to prepare PDF export file.');
                    }
                    $pdfPath = $tempPath . '.pdf';
                    @rename($tempPath, $pdfPath);

                    app(\App\Services\Results\AcseeRegionalMarkEntryStatusFpdfService::class)
                        ->generate(
                            $region,
                            $examYearValue,
                            $markRows->all(),
                            $summary,
                            $pdfPath,
                            (string) data_get($evaluationMap->get($evaluation), 'label', 'MARK ENTRY STATUS REPORT')
                        );

                    return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
                }

                return view('evaluations.acsee-regionalwise-mark-entry-status', [
                    'region' => $region,
                    'evaluationLabel' => data_get($evaluationMap->get($evaluation), 'label'),
                    'examYearValue' => $examYearValue,
                    'rows' => $markRows,
                    'summary' => $summary,
                ]);
        }

        return view('evaluations.acsee-regionalwise-evaluation-detail', [
            'region' => $region,
            'evaluationKey' => $evaluation,
            'evaluationLabel' => data_get($evaluationMap->get($evaluation), 'label'),
            'summary' => $summary,
            'columns' => $columns,
            'rows' => $rows,
        ]);
    })->name('evaluations.acsee.regionalwise.region.evaluation');
    Route::get('/evaluations/acsee/daily-marks-entry-report', function () { 
        return view('evaluations.daily-marks-entry-report'); 
    })->name('evaluations.daily-marks-entry-report');
    Route::get('/evaluations/extremity-analysis', function () { 
        return view('evaluations.extremity-analysis'); 
    })->name('evaluations.extremity-analysis');
    
    // Candidate Extremity Analysis Routes
    Route::get('/admin/candidate-extremity', function () {
        return redirect('/evaluations/extremity-analysis');
    });
    
    Route::get('/admin/candidate-extremity/{report}', function ($report) {
        return view('admin.candidate-extremity-detail', ['reportId' => $report]);
    })->name('admin.candidate-extremity.show');
    
    Route::resource('regions', RegionController::class);
    Route::resource('schools', SchoolController::class);
    Route::resource('candidates', CandidateController::class);
    Route::resource('exam-types', ExamTypeController::class);
    Route::resource('exam-years', ExamYearController::class);
    Route::post('exam-years/{examYear}/activate', [ExamYearController::class, 'activate'])->name('exam-years.activate');
    Route::post('exam-years/{examYear}/publish', [ExamYearController::class, 'publish'])->name('exam-years.publish');
    
    // Registration APIs moved to Admin group
    Route::post('/api/districts/import', function (\Illuminate\Http\Request $request) {
        $file = $request->file('file');
        if (!$file) {
            return response()->json(['message' => 'No file provided'], 400);
        }

        try {
            // Read file content directly without storing
            $content = file_get_contents($file->getRealPath());
            $lines = explode("\n", $content);
            
            $imported = 0;
            $failed = 0;
            $errors = [];
            
            // Skip header row
            foreach ($lines as $index => $line) {
                if ($index === 0) continue; // Skip header
                
                $line = trim($line);
                if (empty($line)) continue; // Skip empty lines
                
                try {
                    // Parse CSV line
                    $row = str_getcsv($line);
                    
                    if (count($row) < 2) continue;
                    
                    $name = trim($row[0] ?? '');
                    $region_id = trim($row[1] ?? '');
                    
                    if (empty($name) || empty($region_id)) continue;
                    
                    // Find region by ID (code like MT08, IR07, etc)
                    $region = \App\Models\Region::where('code', $region_id)->first();
                    
                    if (!$region) {
                        $failed++;
                        $errors[] = "District '$name': Region ID '$region_id' not found";
                        continue;
                    }
                    
                    // Generate district code: Region Code + 2-digit sequential number
                    // Find highest number for this region
                    $lastDistrict = \App\Models\District::where('region_id', $region->id)
                        ->orderByDesc('code')
                        ->first();
                    
                    $nextNumber = 1;
                    if ($lastDistrict) {
                        $lastNumber = (int) substr($lastDistrict->code, -2);
                        $nextNumber = $lastNumber + 1;
                    }
                    
                    $code = $region_id . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);
                    
                    // Check if district already exists by name in this region
                    $existing = \App\Models\District::where('region_id', $region->id)
                        ->where('name', $name)
                        ->first();
                    
                    if ($existing) {
                        // Update existing district
                        $existing->update([
                            'code' => $code,
                            'region_id' => $region->id,
                        ]);
                    } else {
                        // Create new district
                        \App\Models\District::create([
                            'code' => $code,
                            'name' => $name,
                            'region_id' => $region->id,
                            'status' => 'active',
                        ]);
                    }
                    
                    $imported++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Row error: " . $e->getMessage();
                }
            }
            
            $message = "Imported $imported district(s)";
            if ($failed > 0) {
                $message .= ", $failed failed";
            }
            
            return response()->json([
                'message' => $message,
                'count' => $imported,
                'failed' => $failed,
                'errors' => $errors
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Import error: ' . $e->getMessage()], 500);
        }
    });
    Route::post('/api/districts/bulk-delete', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:districts,id'
        ]);
        
        $deleted = \App\Models\District::whereIn('id', $validated['ids'])->delete();
        return response()->json(['deleted' => $deleted, 'message' => 'Districts deleted successfully']);
    });

    // Schools API Endpoints
    Route::post('/api/exam-types/psle/schools/sync-necta-2025', function (\Illuminate\Http\Request $request, \App\Services\Schools\NectaPsle2025SchoolSyncService $service) {
        $validated = $request->validate([
            'region_id' => 'nullable|integer|exists:regions,id',
        ]);

        $regions = \App\Models\Region::query()
            ->when($validated['region_id'] ?? null, fn ($query, $regionId) => $query->where('id', $regionId))
            ->orderBy('name')
            ->get();

        if ($regions->isEmpty()) {
            return response()->json(['message' => 'No matching registered regions found in IRMS.'], 422);
        }

        $summary = $service->syncRegisteredRegions($regions);
        \App\Services\PsleCacheService::incrementVersion();

        return response()->json([
            'message' => 'NECTA PSLE 2025 school sync completed.',
            'summary' => $summary,
        ]);
    });

    Route::get('/api/exam-types/psle/schools', function () {
        $pageSize = request('page_size');
        $search = request('search', '');
        $regionId = request('region_id', '');
        $districtId = request('district_id', '');
        $user = auth()->user();

        $examType = \App\Models\ExamType::where('code', 'PSLE')->first();
        $examYear = \App\Models\ExamYear::where('is_active', true)->first();
        $examTypeId = $examType ? $examType->id : 0;
        $examYearId = $examYear ? $examYear->id : 0;

        $query = \App\Models\School::select([
            'id', 
            'code', 
            'registration_number', 
            'source_system', 
            'name', 
            'ownership', 
            'council_id', 
            'district_id', 
            'region_id'
        ])
        ->with([
            'council:id,name,region_id', 
            'district:id,name,region_id', 
            'region:id,name'
        ])
        ->withCount(['candidates as candidates_count' => function ($q) use ($examTypeId, $examYearId) {
            $q->where('exam_type', 'PSLE')
              ->whereHas('examRegistrations', function ($r) use ($examTypeId, $examYearId) {
                  $r->where('exam_type_id', $examTypeId)
                    ->where('exam_year_id', $examYearId);
              });
        }])
        ->where(function ($q) {
            $q->where('source_system', \App\Services\Schools\NectaPsle2025SchoolSyncService::SOURCE_SYSTEM)
              ->orWhereIn('school_type', ['PRIMARY', 'BOTH']);
        });

        if ($user) {
            \App\Support\PsleUserScope::applyToSchools($query, $user);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($regionId) {
            $query->where('region_id', $regionId);
        }

        if ($districtId) {
            $query->where('council_id', $districtId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $query->orderBy('name');

        if ($pageSize) {
            $query->take((int) $pageSize);
        }

        $scopeHash = \App\Services\PsleCacheService::scopeHash($user);
        $cacheKey = \App\Services\PsleCacheService::schoolsKey($examYearId, ($districtId ?: 'all') . '_' . ($regionId ?: 'all') . '_' . md5($search) . '_' . ($pageSize ?: 'all'), $scopeHash);

        $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(15), function () use ($query, $user, $regionId, $districtId, $search) {
            $schools = $query->get();

            \Illuminate\Support\Facades\Log::info('[PSLE_SCHOOL_SEARCH_DEBUG] school search database query executed', [
                'user_id' => $user?->id,
                'selected_region_id' => $regionId ?: null,
                'selected_council_id' => $districtId ?: null,
                'search' => $search ?: null,
                'schools_returned' => $schools->count(),
            ]);

            return $schools->map(function ($school) {
                $candidatesCount = (int) ($school->candidates_count ?? 0);
                return [
                    'id' => $school->id,
                    'code' => $school->code,
                    'registration_number' => $school->registration_number,
                    'source_system' => $school->source_system,
                    'name' => $school->name,
                    'ownership' => $school->ownership,
                    'district_id' => $school->council_id ?? $school->district_id,
                    'region_id' => $school->region_id,
                    'district_name' => $school->council?->name ?? $school->district?->name,
                    'region_name' => $school->region?->name ?? $school->council?->region?->name ?? $school->district?->region?->name,
                    'candidates_count' => $candidatesCount,
                    'registered_pupils' => $candidatesCount,
                    'pupils_count' => $candidatesCount,
                ];
            })->values()->toArray();
        });

        return response()->json(['data' => $data]);
    });

    Route::get('/api/exam-types/psle/summary', [\App\Http\Controllers\ExamTypeController::class, 'getPsleSummary']);

    Route::get('/api/exam-types/psle/councils', function () {
        $regionId = request('region_id', '');
        $search = request('search', '');
        $user = auth()->user();

        $query = \App\Models\DistrictCouncil::query()
            ->where('is_active', true);

        if ($user && !\App\Support\PsleUserScope::hasGlobalAccess($user)) {
            if ($councilId = \App\Support\PsleUserScope::councilId($user)) {
                $query->where('id', $councilId);
            } elseif ($scopeRegionId = \App\Support\PsleUserScope::regionId($user)) {
                $query->where('region_id', $scopeRegionId);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($regionId) {
            $query->where('region_id', $regionId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $councils = $query->with('region')->orderBy('name')->get();

        return response()->json([
            'data' => $councils->map(fn ($council) => [
                'id' => $council->id,
                'code' => $council->code,
                'name' => $council->name,
                'region_id' => $council->region_id,
                'region_name' => $council->region?->name,
                'status' => $council->is_active ? 'active' : 'inactive',
            ])->values(),
        ]);
    });

    Route::post('/api/exam-types/psle/schools', function (\Illuminate\Http\Request $request) {
        if (!auth()->user() || !auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized action. Only system administrators can manually add primary schools.'], 403);
        }

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'unique:schools,code',
                function ($attribute, $value, $fail) {
                    $cleaned = strtoupper(trim($value));
                    if (empty($cleaned)) {
                        $fail('School code cannot be empty.');
                    }
                }
            ],
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                function ($attribute, $value, $fail) {
                    $cleaned = strtoupper(trim($value));
                    if (empty($cleaned)) {
                        $fail('School name cannot be empty.');
                    }
                }
            ],
            'ownership' => 'required|string|in:GOVERNMENT,NON-GOVERNMENT',
            'region_id' => 'required|integer|exists:regions,id',
            'district_id' => 'required|integer|exists:district_councils,id',
            'exam_year' => 'required',
        ]);

        $normalizedCode = strtoupper(trim($validated['code']));
        $normalizedName = strtoupper(trim($validated['name']));

        // Prevent duplicate school code (redundant but double safe)
        $existsCode = \App\Models\School::where('code', $normalizedCode)->exists();
        if ($existsCode) {
            return response()->json([
                'errors' => [
                    'code' => ['A school with this code already exists.']
                ]
            ], 422);
        }

        // Prevent duplicate school name in the same council
        $existsName = \App\Models\School::where('name', $normalizedName)
            ->where('council_id', $validated['district_id'])
            ->exists();
        if ($existsName) {
            return response()->json([
                'errors' => [
                    'name' => ['A school with this name already exists in the selected council.']
                ]
            ], 422);
        }

        // Resolve District and Council relation
        $council = \App\Models\DistrictCouncil::findOrFail($validated['district_id']);

        // Assert that the selected council belongs to the selected region
        if ((string) $council->region_id !== (string) $validated['region_id']) {
            return response()->json([
                'message' => 'The selected council does not belong to the selected region.',
                'errors' => [
                    'district_id' => ['The selected council does not belong to the selected region.']
                ]
            ], 422);
        }

        // Find matching geographical District if it exists
        $district = \App\Models\District::where('region_id', $council->region_id)
            ->where('name', 'like', '%' . $council->name . '%')
            ->first();
        $districtId = $district ? $district->id : null;

        // Create the school
        $school = \App\Models\School::create([
            'code' => $normalizedCode,
            'registration_number' => $normalizedCode,
            'name' => $normalizedName,
            'ownership' => $validated['ownership'],
            'region_id' => $validated['region_id'],
            'district_id' => $districtId,
            'council_id' => $council->id,
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'source_system' => 'MANUAL',
            'is_active' => true,
        ]);

        // Audit Log if possible
        try {
            \App\Models\GovernanceAuditLog::log(
                'psle_school_created_manually',
                null,
                auth()->id(),
                [
                    'exam_year' => $validated['exam_year'],
                    'school_code' => $school->code,
                    'school_name' => $school->name,
                    'region_id' => $school->region_id,
                    'district_id' => $school->district_id,
                    'timestamp' => now()->toIso8601String(),
                ]
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Manual school creation audit log failed: ' . $e->getMessage());
        }

        \App\Services\PsleCacheService::incrementVersion();

        return response()->json([
            'success' => true,
            'message' => 'School added successfully.',
            'school' => [
                'id' => $school->id,
                'code' => $school->code,
                'registration_number' => $school->registration_number,
                'source_system' => $school->source_system,
                'name' => $school->name,
                'ownership' => $school->ownership,
                'district_id' => $school->council_id ?? $school->district_id,
                'region_id' => $school->region_id,
                'district_name' => $school->council?->name ?? $school->district?->name,
                'region_name' => $school->region?->name ?? $school->council?->region?->name ?? $school->district?->region?->name,
                'candidates_count' => 0,
                'registered_pupils' => 0,
                'pupils_count' => 0,
            ]
        ], 201);
    });

    Route::post('/api/exam-types/csee/schools/sync-necta-2025', function (\App\Services\Schools\NectaCsee2025CentreSyncService $service) {
        $summary = $service->syncCentres();

        return response()->json([
            'message' => 'NECTA CSEE 2025 centres sync completed.',
            'summary' => $summary,
        ]);
    });

    Route::post('/api/exam-types/csee/schools/import-particulars', function (\Illuminate\Http\Request $request, \App\Services\Schools\CseeSchoolParticularsImportService $service) {
        $summary = $request->hasFile('file')
            ? $service->importFromCsv($request->file('file'))
            : $service->importMissingParticulars();

        $hasFailures = (int) ($summary['rows_failed'] ?? 0) > 0;

        return response()->json([
            'message' => $request->hasFile('file')
                ? 'CSEE school particulars CSV imported successfully.'
                : 'CSEE school particulars imported successfully.',
            'summary' => $summary,
        ], $hasFailures ? 422 : 200);
    });

    Route::get('/api/exam-types/csee/schools/import-particulars/template', function () {
        $filename = 'csee-school-particulars-template.csv';
        $csv = fopen('php://memory', 'w');

        fputcsv($csv, ['code', 'name', 'ownership', 'region', 'district']);
        fputcsv($csv, ['S0101', 'AZANIA SECONDARY SCHOOL', 'NON-GOVERNMENT', 'Dar es Salaam', 'Ilala']);
        fputcsv($csv, ['P0104', 'BWIRU BOYS SECONDARY SCHOOL', 'GOVERNMENT', 'Mwanza', '']);

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    });

    Route::get('/api/exam-types/csee/schools', function () {
        $page = max((int) request('page', 1), 1);
        $pageSize = min(max((int) request('page_size', 100), 1), 500);
        $search = request('search', '');
        $regionId = request('region_id', '');
        $districtId = request('district_id', '');

        $query = \App\Models\School::query()
            ->with(['district.region', 'council.region', 'region'])
            ->where('source_system', \App\Services\Schools\NectaCsee2025CentreSyncService::SOURCE_SYSTEM)
            ->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($regionId) {
            $query->where(function ($q) use ($regionId) {
                $q->where('region_id', $regionId)
                    ->orWhereHas('district', fn ($districtQuery) => $districtQuery->where('region_id', $regionId))
                    ->orWhereHas('council', fn ($councilQuery) => $councilQuery->where('region_id', $regionId));
            });
        }

        if ($districtId) {
            $query->where(function ($q) use ($districtId) {
                $q->where('district_id', $districtId)
                    ->orWhere('council_id', $districtId);
            });
        }

        $total = $query->count();
        $schools = $query
            ->skip(($page - 1) * $pageSize)
            ->take($pageSize)
            ->get();

        return response()->json([
            'data' => $schools->map(function ($school) {
                $ownership = strtoupper((string) ($school->ownership ?? ''));
                $regionName = $school->region?->name
                    ?? $school->council?->region?->name
                    ?? $school->district?->region?->name;
                $isFallbackRegion = $school->region?->code === 'CSEE-UNK'
                    || str_contains(strtoupper((string) $regionName), 'UNASSIGNED');

                if ($isFallbackRegion) {
                    $regionName = null;
                }

                return [
                    'id' => $school->id,
                    'code' => $school->code,
                    'registration_number' => $school->registration_number,
                    'source_system' => $school->source_system,
                    'name' => $school->name,
                    'ownership' => $school->ownership,
                    'ownership_label' => match ($ownership) {
                        'NON-GOVERNMENT' => 'NON-GOVERNMENT',
                        'GOVERNMENT' => 'GOVERNMENT',
                        default => $school->ownership ?: 'UNKNOWN',
                    },
                    'district_id' => $school->district_id,
                    'council_id' => $school->council_id,
                    'region_id' => $school->region_id,
                    'district_name' => $school->district?->name ?? $school->council?->name,
                    'council_name' => $school->council?->name ?? $school->district?->name,
                    'region_name' => $regionName,
                    'school_type' => $school->school_type,
                    'education_level' => $school->education_level,
                ];
            })->values(),
            'meta' => [
                'current_page' => $page,
                'per_page' => $pageSize,
                'total' => $total,
                'last_page' => max((int) ceil($total / $pageSize), 1),
            ],
        ]);
    });

    Route::get('/api/schools', function () {
        $page = request('page', 1);
        $pageSize = request('page_size', 10);
        $search = request('search', '');
        $regionId = request('region_id', '');
        $districtId = request('district_id', '');
        
        $query = \App\Models\School::with('district', 'district.region');
        
        // Filter by region if specified
        if ($regionId) {
            $query->whereHas('district', function($q) use ($regionId) {
                $q->where('region_id', $regionId);
            });
        }
        
        // Filter by district if specified (takes precedence over region)
        if ($districtId) {
            $query->where('district_id', $districtId);
        }
        
        // Search by code or name
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%$search%")
                  ->orWhere('name', 'like', "%$search%");
            });
        }
        
        $total = $query->count();
        $schools = $query->skip(($page - 1) * $pageSize)
                         ->take($pageSize)
                         ->get();
        
        $data = $schools->map(function($s) {
            return [
                'id' => $s->id,
                'code' => $s->code,
                'name' => $s->name,
                'ownership' => $s->ownership,
                'district_id' => $s->district_id,
                'region_id' => $s->district->region_id ?? null,
                'district_name' => $s->district->name ?? null,
                'region_name' => $s->district->region->name ?? null,
                'candidates_count' => $s->candidates()->count(),
                'status' => 'active'
            ];
        });
        
        return response()->json([
            'data' => $data,
            'pagination' => [
                'total_count' => $total,
                'total_pages' => ceil($total / $pageSize),
                'current_page' => $page,
                'page_size' => $pageSize
            ]
        ]);
    });
    Route::post('/api/schools', function (\Illuminate\Http\Request $request) {
        try {
            $validated = $request->validate([
                'code' => 'required|unique:schools',
                'name' => 'required',
                'ownership' => 'required|in:GOVERNMENT,NON-GOVERNMENT',
                'region_id' => 'required|exists:regions,id',
                'district_id' => 'required|exists:districts,id'
            ]);

            $district = \App\Models\District::findOrFail($validated['district_id']);
            if ((string) $district->region_id !== (string) $validated['region_id']) {
                return response()->json([
                    'message' => 'The selected district does not belong to the selected region.',
                    'errors' => [
                        'district_id' => ['The selected district does not belong to the selected region.']
                    ]
                ], 422);
            }

            $council = \App\Models\DistrictCouncil::where('region_id', $district->region_id)
                ->where('name', 'like', '%' . $district->name . '%')
                ->first();
            $councilId = $council ? $council->id : null;

            $school = \App\Models\School::create(array_merge($validated, [
                'registration_number' => $validated['code'],
                'council_id' => $councilId,
                'school_type' => 'PRIMARY',
                'education_level' => 'PRIMARY',
                'source_system' => 'MANUAL',
                'is_active' => true,
            ]));
            return response()->json(['message' => 'School added', 'data' => $school], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error creating school: ' . $e->getMessage()], 500);
        }
    });
    Route::put('/api/schools/{id}', function (\Illuminate\Http\Request $request, $id) {
        try {
            $school = \App\Models\School::find($id);
            if (!$school) return response()->json(['message' => 'School not found'], 404);
            
            $validated = $request->validate([
                'code' => 'required|string|max:50',
                'name' => 'required|string|max:255',
                'ownership' => 'required|in:GOVERNMENT,NON-GOVERNMENT',
                'region_id' => 'required|exists:regions,id',
                'district_id' => 'required|exists:districts,id'
            ]);

            // Clean inputs
            $normalizedCode = strtoupper(trim($validated['code']));
            $normalizedName = strtoupper(trim($validated['name']));

            // Double check code uniqueness
            $existsCode = \App\Models\School::where('code', $normalizedCode)->where('id', '!=', $school->id)->exists();
            if ($existsCode) {
                return response()->json([
                    'errors' => [
                        'code' => ['A school with this code already exists.']
                    ]
                ], 422);
            }

            // Check candidate safety rules
            $candidatesCount = \App\Models\Candidate::where('school_id', $school->id)->count();
            if ($candidatesCount > 0) {
                // If school has registered candidates, block changes to region or district unless they remain identical
                if ((string) $school->region_id !== (string) $validated['region_id'] || 
                    ((string) $school->district_id !== (string) $validated['district_id'] && 
                     (string) $school->council_id !== (string) $validated['district_id'])) {
                    return response()->json([
                        'message' => 'This school has registered candidates. Council/region changes require administrator approval.',
                        'errors' => [
                            'district_id' => ['This school has registered candidates. Council/region changes are restricted.']
                        ]
                    ], 422);
                }
            }

            // Resolve District and Council relation
            $district = \App\Models\District::findOrFail($validated['district_id']);
            if ((string) $district->region_id !== (string) $validated['region_id']) {
                return response()->json([
                    'message' => 'The selected district does not belong to the selected region.',
                    'errors' => [
                        'district_id' => ['The selected district does not belong to the selected region.']
                    ]
                ], 422);
            }

            $council = \App\Models\DistrictCouncil::where('region_id', $district->region_id)
                ->where('name', 'like', '%' . $district->name . '%')
                ->first();
            $councilId = $council ? $council->id : null;

            // Perform update inside database transaction
            \DB::transaction(function () use ($school, $normalizedCode, $normalizedName, $validated, $councilId) {
                $school->update([
                    'code' => $normalizedCode,
                    'registration_number' => $normalizedCode,
                    'name' => $normalizedName,
                    'ownership' => $validated['ownership'],
                    'region_id' => $validated['region_id'],
                    'district_id' => $validated['district_id'],
                    'council_id' => $councilId,
                ]);
            });

            // Target Cache Invalidation
            \App\Services\PsleCacheService::incrementVersion();

            return response()->json(['message' => 'School updated successfully', 'data' => $school]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error updating school: ' . $e->getMessage()], 500);
        }
    });
    Route::delete('/api/schools/{id}', function ($id) {
        $school = \App\Models\School::find($id);
        if (!$school) return response()->json(['message' => 'Not found'], 404);
        
        // Check if school has candidates registered
        $candidateCount = $school->candidates()->count();
        if ($candidateCount > 0) {
            return response()->json([
                'message' => "Cannot delete school with registered candidates",
                'details' => "This school has $candidateCount candidate(s) registered. Please remove all candidates first.",
                'count' => $candidateCount
            ], 409);
        }
        
        $school->delete();

        // Invalidate PSLE schools and summary cache
        \App\Services\PsleCacheService::incrementVersion();

        return response()->json(['message' => 'School deleted']);
    });
    Route::post('/api/schools/import', function (\Illuminate\Http\Request $request) {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);
        
        $file = $request->file('file');
        $path = $file->getRealPath();
        $csv = array_map('str_getcsv', file($path));
        
        if (empty($csv) || count($csv) < 2) {
            return response()->json(['message' => 'CSV file is empty'], 400);
        }
        
        // Get headers and normalize them
        $rawHeaders = $csv[0];
        $headers = [];
        foreach ($rawHeaders as $header) {
            $normalized = strtolower(trim($header));
            $normalized = str_replace(' ', '_', $normalized);
            $headers[] = $normalized;
        }
        
        $imported = 0;
        $errors = [];
        
        // Process each row
        for ($i = 1; $i < count($csv); $i++) {
            $rowData = $csv[$i];
            
            // Skip empty rows
            if (empty(array_filter($rowData))) {
                continue;
            }
            
            // Combine headers with data
            $row = [];
            foreach ($headers as $idx => $header) {
                $row[$header] = $rowData[$idx] ?? '';
            }
            
            try {
                // Validate required fields
                if (empty($row['code'])) {
                    $errors[] = "Row " . ($i + 1) . ": Code is required";
                    continue;
                }
                if (empty($row['name'])) {
                    $errors[] = "Row " . ($i + 1) . ": Name is required";
                    continue;
                }
                
                // Get district_id - try numeric ID first, then code, then name
                 $districtId = null;
                 if (!empty($row['district_id'])) {
                     $districtVal = trim($row['district_id']);
                     
                     // Try as numeric ID first
                     if (is_numeric($districtVal)) {
                         $districtExists = \App\Models\District::find((int)$districtVal);
                         $districtId = $districtExists ? (int)$districtVal : null;
                     }
                     
                     // If not found, try as code
                     if (!$districtId && $districtVal) {
                         $district = \App\Models\District::where('code', $districtVal)->first();
                         $districtId = $district ? $district->id : null;
                     }
                 } elseif (!empty($row['district'])) {
                     // Try to find by name
                     $district = \App\Models\District::where('name', trim($row['district']))->first();
                     $districtId = $district ? $district->id : null;
                 }
                 
                 // Get region_id - try numeric ID first, then code, then name
                 $regionId = null;
                 if (!empty($row['region_id'])) {
                     $regionVal = trim($row['region_id']);
                     
                     // Try as numeric ID first
                     if (is_numeric($regionVal)) {
                         $regionExists = \App\Models\Region::find((int)$regionVal);
                         $regionId = $regionExists ? (int)$regionVal : null;
                     }
                     
                     // If not found, try as code
                     if (!$regionId && $regionVal) {
                         $region = \App\Models\Region::where('code', $regionVal)->first();
                         $regionId = $region ? $region->id : null;
                     }
                 } elseif (!empty($row['region'])) {
                     // Try to find by name
                     $region = \App\Models\Region::where('name', trim($row['region']))->first();
                     $regionId = $region ? $region->id : null;
                 }
                
                // Check if school already exists by code
                $exists = \App\Models\School::where('code', trim($row['code']))->first();
                if ($exists) {
                    continue; // Skip duplicates silently
                }
                
                // Create school
                \App\Models\School::create([
                    'code' => trim($row['code']),
                    'name' => trim($row['name']),
                    'ownership' => trim($row['ownership']) ?? 'PUBLIC',
                    'district_id' => $districtId,
                    'region_id' => $regionId,
                    'is_active' => true,
                ]);
                
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($i + 1) . ": " . $e->getMessage();
            }
        }
        
        return response()->json([
            'message' => "Imported $imported school(s)",
            'count' => $imported,
            'errors' => $errors
        ]);
    });
    Route::post('/api/schools/bulk-delete', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:schools,id'
        ]);
        
        // Safety check: verify no registered candidates exist under these schools
        $candidatesCount = \App\Models\Candidate::whereIn('school_id', $validated['ids'])->count();
        if ($candidatesCount > 0) {
            return response()->json([
                'message' => "Cannot bulk delete schools. One or more selected schools have registered candidates.",
                'details' => "There are total $candidatesCount candidate(s) registered under the selected schools.",
                'count' => $candidatesCount
            ], 409);
        }
        
        $deleted = \App\Models\School::whereIn('id', $validated['ids'])->delete();
        \App\Services\PsleCacheService::incrementVersion();
        return response()->json(['deleted' => $deleted, 'message' => 'Schools deleted successfully']);
    });

    // Candidates API Endpoints
    Route::get('/api/candidates', function () {
         $page = request('page', 1);
         $pageSize = request('page_size', 10);
         $search = request('search', '');
         $schoolId = request('school_id', '');
         $districtId = request('district_id', '');
         $regionId = request('region_id', '');
         $examType = request('exam_type', '');
         
         $query = \App\Models\Candidate::with('school', 'school.district', 'school.district.region', 'examRegistrations.examYear');
        
        // Filter by school if specified
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        // Filter by district (through school relationship)
        if ($districtId) {
            $query->whereHas('school', function($q) use ($districtId) {
                $q->where('district_id', $districtId)
                  ->whereNotNull('district_id');
            });
        }

        // Filter by region (through school -> district relationship)
        if ($regionId) {
            $query->whereHas('school.district', function($q) use ($regionId) {
                $q->where('region_id', $regionId)
                  ->whereNotNull('region_id');
            });
        }

        // Filter by exam_type if specified
        if ($examType) {
            $query->where('exam_type', strtoupper($examType));
        }
        
        // Search by candidate_id, full_name, combination, school name, exam_type
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('candidate_id', 'like', "%$search%")
                  ->orWhere('full_name', 'like', "%$search%")
                  ->orWhere('combination', 'like', "%$search%")
                  ->orWhere('exam_type', 'like', "%$search%")
                  ->orWhereHas('school', function($q) use ($search) {
                       $q->where('name', 'like', "%$search%");
                   });
            });
        }
        
        $total = $query->count();
        $candidates = $query->skip(($page - 1) * $pageSize)
                             ->take($pageSize)
                             ->get();
        
        $data = $candidates->map(function($c) {
            return [
                'id' => $c->id,
                'candidate_id' => $c->candidate_id,
                'full_name' => $c->full_name,
                'gender' => $c->gender,
                'combination' => $c->combination ?? null,
                'school_id' => $c->school_id,
                'school_name' => $c->school->name ?? null,
                'exam_type' => $c->exam_type,
                'exam_year' => $c->exam_year,
                'candidate_type' => $c->candidate_type ?? 'SCHOOL',
                'status' => $c->status ?? 'registered'
            ];
        });
        
        return response()->json([
            'data' => $data,
            'pagination' => [
                'total_count' => $total,
                'total_pages' => ceil($total / $pageSize),
                'current_page' => $page,
                'page_size' => $pageSize
            ]
        ]);
    });

    Route::post('/api/candidates', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'candidate_id' => 'nullable|string|max:255',
            'prem_no' => 'nullable|string|max:100',
            'full_name' => 'required|string|max:255',
            'gender' => 'required|in:M,F',
            'combination' => 'nullable|string|max:255',
            'combination_id' => 'nullable|exists:combinations,id',
            'exam_type' => 'required|in:PSLE,CSEE,ACSEE',
            'candidate_type' => 'nullable|in:SCHOOL,PRIVATE',
            'status' => 'nullable|string|max:255',
        ]);
        
        $examTypeCode = strtoupper((string) $validated['exam_type']);
        $examType = \App\Models\ExamType::where('code', $examTypeCode)->first();
        $activeExamYear = \App\Models\ExamYear::where('is_active', true)->first();
        
        if (!$examType || !$activeExamYear) {
            return response()->json(['message' => 'Active exam year or exam type not found.'], 422);
        }

        // Auto-generate candidate_id if not provided
        if (empty($validated['candidate_id'])) {
            $count = \App\Models\Candidate::count() + 1;
            $validated['candidate_id'] = 'CAND-' . str_pad($count, 6, '0', STR_PAD_LEFT);
        }

        $user = $request->user();
        if ($examTypeCode === 'PSLE') {
            if (!$user) {
                return response()->json([
                    'message' => 'You are not authorized to register PSLE candidates for this school.',
                ], 403);
            }
            if (!\App\Support\PsleUserScope::hasGlobalAccess($user)) {
                $schoolQuery = \App\Models\School::query()->whereKey($validated['school_id']);
                \App\Support\PsleUserScope::applyToSchools($schoolQuery, $user);
                if (!$schoolQuery->exists()) {
                    return response()->json([
                        'message' => 'You are not authorized to register PSLE candidates for this school.',
                    ], 403);
                }
            }
        }
        
        // Custom Check for duplicate candidate globally using 3-tier matching priority
        $service = app(\App\Services\MarkEntry\PsleCandidateRegistrationService::class);
        $existingCandidate = $service->findExistingCandidate([
            'candidate_id' => $validated['candidate_id'] ?? null,
            'prem_no' => $validated['prem_no'] ?? null,
            'school_id' => $validated['school_id'] ?? null,
            'full_name' => $validated['full_name'] ?? null,
            'gender' => $validated['gender'] ?? null,
        ], $activeExamYear->id, $examType->id);
        
        if ($existingCandidate) {
            // Check if already registered for this active year and exam type
            $isAlreadyRegistered = \App\Models\CandidateExamRegistration::where([
                'candidate_id' => $existingCandidate->id,
                'exam_type_id' => $examType->id,
                'exam_year_id' => $activeExamYear->id,
            ])->exists();
            
            if ($isAlreadyRegistered && empty($validated['prem_no'])) {
                return response()->json([
                    'message' => "Candidate number is already registered for the active {$examTypeCode} year.",
                    'errors' => [
                        'candidate_id' => ["Candidate number is already registered for the active {$examTypeCode} year."]
                    ]
                ], 422);
            }
        }
        
        // Wrap registration operations inside a DB::transaction
        $candidate = \DB::transaction(function () use ($validated, $existingCandidate, $examType, $activeExamYear, $examTypeCode) {
            if ($existingCandidate) {
                // If candidate exists globally but not registered for active year, update basic info
                $existingCandidate->update([
                    'school_id' => $validated['school_id'],
                    'prem_no' => $validated['prem_no'] ? $validated['prem_no'] : null, // Cast empty to NULL
                    'full_name' => $validated['full_name'],
                    'gender' => $validated['gender'],
                    'exam_type' => $validated['exam_type'],
                    'combination' => $validated['combination'] ?? null,
                    'combination_id' => $validated['combination_id'] ?? null,
                    'candidate_type' => $validated['candidate_type'] ?? $existingCandidate->candidate_type,
                    'status' => $validated['status'] ?? 'registered',
                ]);
                $candidate = $existingCandidate;
            } else {
                $candidate = \App\Models\Candidate::create(array_merge($validated, [
                    'prem_no' => $validated['prem_no'] ? $validated['prem_no'] : null
                ]));
            }
            
            \App\Models\CandidateExamRegistration::updateOrCreate(
                [
                    'candidate_id' => $candidate->id,
                    'exam_type_id' => $examType->id,
                    'exam_year_id' => $activeExamYear->id,
                ],
                [
                    'year' => (int) $activeExamYear->year_label,
                    'registration_number' => 'REG-' . uniqid(),
                    'is_active' => true,
                    'is_verified' => false,
                ]
            );

            if ($examTypeCode === 'CSEE') {
                app(CseeCandidateSubjectService::class)->ensureCoreSubjects($candidate, $activeExamYear);
            }
            
            return $candidate;
        });

        // Version invalidation using PsleCacheService (targeted/versioned)
        if ($examTypeCode === 'PSLE') {
            \App\Services\PsleCacheService::incrementVersion();
        }
        
        return response()->json(['message' => 'Candidate registered successfully', 'data' => $candidate->load('school')], 201);
    });
 
    Route::put('/api/candidates/{id}', function (\Illuminate\Http\Request $request, $id) {
        $candidate = \App\Models\Candidate::findOrFail($id);
        
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'candidate_id' => 'nullable|string|max:255',
            'prem_no' => 'nullable|string|max:100',
            'full_name' => 'required|string|max:255',
            'gender' => 'required|in:M,F',
            'combination' => 'nullable|string|max:255',
            'combination_id' => 'nullable|exists:combinations,id',
            'exam_type' => 'required|in:PSLE,CSEE,ACSEE',
            'candidate_type' => 'nullable|in:SCHOOL,PRIVATE',
            'status' => 'nullable|string|max:255',
        ]);

        $examTypeCode = strtoupper((string) $validated['exam_type']);
        $examType = \App\Models\ExamType::where('code', $examTypeCode)->first();
        $activeExamYear = \App\Models\ExamYear::where('is_active', true)->first();

        if (!$examType || !$activeExamYear) {
            return response()->json(['message' => 'Active exam year or exam type not found.'], 422);
        }

        // Custom validation: if candidate_id is changing, check if another candidate already has it
        if (!empty($validated['candidate_id']) && $validated['candidate_id'] !== $candidate->candidate_id) {
            $duplicate = \App\Models\Candidate::where('candidate_id', $validated['candidate_id'])->where('id', '!=', $candidate->id)->first();
            if ($duplicate) {
                return response()->json([
                    'message' => "Candidate number is already taken by another student.",
                    'errors' => [
                        'candidate_id' => ["Candidate number is already taken by another student."]
                    ]
                ], 422);
            }
        }

        // Custom validation: if prem_no is changing, check if another candidate already has it
        if (!empty($validated['prem_no']) && $validated['prem_no'] !== $candidate->prem_no) {
            $duplicatePrem = \App\Models\Candidate::where('prem_no', $validated['prem_no'])->where('id', '!=', $candidate->id)->first();
            if ($duplicatePrem) {
                return response()->json([
                    'message' => "PReM number is already taken by another student.",
                    'errors' => [
                        'prem_no' => ["PReM number is already taken by another student."]
                    ]
                ], 422);
            }
        }

        $user = $request->user();
        if ($examTypeCode === 'PSLE') {
            if (!$user) {
                return response()->json([
                    'message' => 'You are not authorized to update PSLE candidates for this school.',
                ], 403);
            }
            if (!\App\Support\PsleUserScope::hasGlobalAccess($user)) {
                $schoolQuery = \App\Models\School::query()->whereKey($validated['school_id']);
                \App\Support\PsleUserScope::applyToSchools($schoolQuery, $user);
                if (!$schoolQuery->exists()) {
                    return response()->json([
                        'message' => 'You are not authorized to update PSLE candidates for this school.',
                    ], 403);
                }
            }
        }
        
        \DB::transaction(function () use ($candidate, $validated, $examType, $activeExamYear, $examTypeCode) {
            $candidate->update(array_merge($validated, [
                'prem_no' => $validated['prem_no'] ? $validated['prem_no'] : null
            ]));

            \App\Models\CandidateExamRegistration::updateOrCreate(
                [
                    'candidate_id' => $candidate->id,
                    'exam_type_id' => $examType->id,
                    'exam_year_id' => $activeExamYear->id,
                ],
                [
                    'year' => (int) $activeExamYear->year_label,
                    'registration_number' => 'REG-' . uniqid(),
                    'is_active' => true,
                    'is_verified' => false,
                ]
            );

            if ($examTypeCode === 'CSEE') {
                app(CseeCandidateSubjectService::class)->ensureCoreSubjects($candidate, $activeExamYear);
            }
        });
        
        if ($examTypeCode === 'PSLE') {
            \App\Services\PsleCacheService::incrementVersion();
        }
        
        return response()->json(['message' => 'Candidate updated successfully', 'data' => $candidate->load('school')]);
    });

    Route::post('/api/exam-types/csee/candidates/{candidate}/subjects', function (\Illuminate\Http\Request $request, \App\Models\Candidate $candidate, CseeCandidateSubjectService $service) {
        $validated = $request->validate([
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'integer|exists:subjects,id',
            'exam_year' => 'nullable|string|regex:/^\d{4}$/',
        ]);

        $examYear = null;
        if (!empty($validated['exam_year'])) {
            $examYear = \App\Models\ExamYear::where('year_label', $validated['exam_year'])->first();
        }

        $result = $service->syncSubjects($candidate, $validated['subject_ids'], $examYear);

        return response()->json([
            'message' => 'CSEE candidate subjects updated successfully.',
            'data' => $result,
        ]);
    });

    Route::delete('/api/candidates/{id}', function ($id) {
        $candidate = \App\Models\Candidate::findOrFail($id);
        
        // Check if candidate has marks/results entered
        $subjectMarksCount = \App\Models\SubjectMarks::where('candidate_id', $candidate->id)->count();
        $resultsCount = \App\Models\CandidateResult::where('candidate_id', $candidate->id)->count();
        
        if ($subjectMarksCount > 0 || $resultsCount > 0) {
            return response()->json([
                'message' => "Cannot delete candidate with marks or results",
                'details' => "This candidate has " . $subjectMarksCount . " subject mark(s) and " . $resultsCount . " result(s) entered. Please remove marks/results first.",
                'subject_marks_count' => $subjectMarksCount,
                'results_count' => $resultsCount
            ], 409);
        }
        
        $candidate->delete();
        \App\Services\PsleCacheService::incrementVersion();
        return response()->json(['message' => 'Candidate deleted successfully']);
    });

    Route::post('/api/candidates/import/check', function (\Illuminate\Http\Request $request) {
         // Pre-import validation endpoint
         $request->validate([
             'file' => 'required|file|mimes:csv,txt',
             'exam_year' => 'nullable|string|regex:/^\d{4}$/',
             'exam_type' => 'nullable|in:PSLE,CSEE,ACSEE'
         ]);
         
         $file = $request->file('file');
         $handle = fopen($file->getRealPath(), 'r');
         
         $conflicts = [];
         $rowNumber = 0;
         $header = fgetcsv($handle);
         
         while (($row = fgetcsv($handle)) !== false) {
             $rowNumber++;
             
             if (empty(array_filter($row))) {
                 continue;
             }
             
             if (count($row) < 5) {
                 continue;
             }
             
             // CSV format: candidate_id, full_name, gender, combination, school_code, exam_type, exam_year
             $candidateId = trim($row[0] ?? '') ?: null;
             if (!empty($candidateId)) {
                 $existing = \App\Models\Candidate::where('candidate_id', $candidateId)->first();
                 if ($existing) {
                     $conflicts[] = [
                         'row' => $rowNumber,
                         'candidate_id' => $candidateId,
                         'full_name' => trim($row[1] ?? ''),
                         'action' => 'update'
                     ];
                 }
             }
         }
         
         fclose($handle);
         
         return response()->json([
             'has_conflicts' => count($conflicts) > 0,
             'conflicts' => $conflicts,
             'conflict_count' => count($conflicts)
         ]);
     });

    Route::post('/api/candidates/import', function (\Illuminate\Http\Request $request) {
         $request->validate([
             'file' => 'required|file|mimes:csv,txt',
             'exam_year' => 'nullable|string|regex:/^\d{4}$/',
             'exam_type' => 'nullable|in:PSLE,CSEE,ACSEE',
             'mode' => 'nullable|in:skip,replace,replace-all'
         ]);
         
         $file = $request->file('file');
         $handle = fopen($file->getRealPath(), 'r');
         
         $count = 0;
         $skipped = 0;
         $replaced = 0;
         $errors = [];
         $rowNumber = 0;
         $header = fgetcsv($handle);
         
         // Get exam year and type from request
         $examYearValue = $request->input('exam_year');
         $examTypeOverride = $request->input('exam_type');
         $importMode = $request->input('mode', 'skip');
         
         \Log::info('Candidate import started', ['header' => $header, 'exam_year' => $examYearValue]);
         
         while (($row = fgetcsv($handle)) !== false) {
         $rowNumber++;
         
         // Skip empty rows
         if (empty(array_filter($row))) {
         continue;
         }
         
         if (count($row) < 5) {
         $errors[] = "Row $rowNumber: Insufficient columns (expected minimum 5, got " . count($row) . ")";
         continue;
         }
         
         try {
         // CSV format: candidate_id, full_name, gender, combination, school_code, exam_type, exam_year
         $candidateId = trim($row[0] ?? '') ?: null;
         $fullName = trim($row[1] ?? '');
         $gender = trim($row[2] ?? '');
         $combination = trim($row[3] ?? '') ?: null;
         $schoolCode = trim($row[4] ?? '');
         $examType = trim($row[5] ?? '') ?: $examTypeOverride;
         $csvExamYear = trim($row[6] ?? '') ?: null;
                 
                 \Log::debug('Processing row', [
                     'rowNumber' => $rowNumber,
                     'candidateId' => $candidateId,
                     'fullName' => $fullName,
                     'schoolCode' => $schoolCode,
                     'examType' => $examType,
                     'csvExamYear' => $csvExamYear,
                     'columnsCount' => count($row)
                 ]);
                 
                 // Validate required fields
                 if (empty($fullName)) {
                     $errors[] = "Row $rowNumber: Missing Full Name";
                     continue;
                 }
                 if (empty($gender)) {
                     $errors[] = "Row $rowNumber: Missing Sex";
                     continue;
                 }
                 if (empty($schoolCode)) {
                     $errors[] = "Row $rowNumber: Missing School Code";
                     continue;
                 }
                 if (empty($examType)) {
                     $errors[] = "Row $rowNumber: Missing Exam Type";
                     continue;
                 }
                 
                 // Look up school by registration_number or code
                 $school = \App\Models\School::where('registration_number', $schoolCode)
                     ->orWhere('code', $schoolCode)
                     ->first();
                 if (!$school) {
                     $errors[] = "Row $rowNumber: School code '$schoolCode' does not exist";
                     continue;
                 }
                 $schoolId = $school->id;
                 
                 // Auto-generate candidate_id if not provided
                 if (empty($candidateId)) {
                     $candidateCount = \App\Models\Candidate::count() + $count + 1;
                     $candidateId = 'CAND-' . str_pad($candidateCount, 6, '0', STR_PAD_LEFT);
                 }
                 
                 // Check if candidate already exists
                 $existingCandidate = \App\Models\Candidate::where('candidate_id', $candidateId)->first();
                 
                 // Handle import mode
                 if ($existingCandidate) {
                     if ($importMode === 'skip') {
                         $skipped++;
                         continue;
                     } elseif ($importMode === 'replace') {
                         // Replace mode - ask which ones to replace (handled at frontend)
                         $replaced++;
                     } elseif ($importMode === 'replace-all') {
                         // Replace all - update without asking
                         $replaced++;
                     }
                 }
                 
                 $candidate = \App\Models\Candidate::updateOrCreate(
                     ['candidate_id' => $candidateId],
                     [
                         'full_name' => $fullName,
                         'gender' => strtoupper($gender),
                         'combination' => $combination,
                         'school_id' => $schoolId,
                         'exam_type' => strtoupper($examType),
                         'status' => 'registered'
                     ]
                 );
                 
                 // Register for ACSEE if applicable and exam year provided (from CSV or modal)
                 // IMPORTANT: Prefer CSV exam year if provided, otherwise use modal exam year
                 // For proper ACSEE registration, exam year MUST come from one of these sources
                 $yearForRegistration = !empty($csvExamYear) ? $csvExamYear : $examYearValue;
                 
                 \Log::debug('ACSEE registration check', [
                     'examType' => strtoupper($examType),
                     'csvExamYear' => $csvExamYear,
                     'examYearValue' => $examYearValue,
                     'yearForRegistration' => $yearForRegistration,
                     'combination' => $combination,
                     'isACSEE' => (strtoupper($examType) === 'ACSEE'),
                     'willRegister' => (strtoupper($examType) === 'ACSEE' && !empty($yearForRegistration) && !empty($combination))
                 ]);
                 
                 if (strtoupper($examType) === 'ACSEE' && !empty($yearForRegistration) && !empty($combination)) {
                     try {
                         // Use the CandidateController's registerForACSEE method
                         $controller = app(\App\Http\Controllers\CandidateController::class);
                         $reflection = new ReflectionMethod($controller, 'registerForACSEE');
                         $reflection->setAccessible(true);
                         $reflection->invoke($controller, $candidate, $combination, $yearForRegistration);
                     } catch (\Exception $e) {
                         \Log::warning('ACSEE registration failed during import', [
                             'candidate_id' => $candidateId,
                             'exam_year' => $yearForRegistration,
                             'error' => $e->getMessage()
                         ]);
                     }
                 }
                 
                 $count++;
             } catch (\Exception $e) {
                 $errors[] = "Row $rowNumber error: " . $e->getMessage();
                 \Log::error('Row import error', ['rowNumber' => $rowNumber, 'error' => $e->getMessage()]);
                 continue;
             }
         }
         
         fclose($handle);
         
         \Log::info('Candidate import completed', [
             'count' => $count,
             'skipped' => $skipped,
             'replaced' => $replaced,
             'errors_count' => count($errors),
             'errors' => $errors
         ]);
         
         return response()->json([
             'message' => $count . ' candidate(s) imported successfully' . (count($errors) > 0 ? ' with ' . count($errors) . ' error(s)' : ''),
             'count' => $count,
             'skipped' => $skipped,
             'replaced' => $replaced,
             'errors' => $errors
         ]);
     });

     // School CSV Import Endpoint
     Route::post('/api/schools/import', function (\Illuminate\Http\Request $request) {
         $request->validate([
             'file' => 'required|file|mimes:csv,txt'
         ]);
         
         $file = $request->file('file');
         $handle = fopen($file->getRealPath(), 'r');
         
         $count = 0;
         $errors = [];
         $rowNumber = 0;
         $header = fgetcsv($handle);
         
         \Log::info('School import started', ['header' => $header]);
         
         while (($row = fgetcsv($handle)) !== false) {
             $rowNumber++;
             
             // Skip empty rows
             if (empty(array_filter($row))) {
                 continue;
             }
             
             if (count($row) < 5) {
                 $errors[] = "Row $rowNumber: Insufficient columns (expected 5, got " . count($row) . ")";
                 continue;
             }
             
             try {
                 $registrationCode = trim($row[0] ?? '');
                 $name = trim($row[1] ?? '');
                 $ownership = trim($row[2] ?? '');
                 $regionCode = trim($row[3] ?? '');
                 $districtCode = trim($row[4] ?? '');
                 
                 // Validate required fields
                 if (empty($registrationCode)) {
                     $errors[] = "Row $rowNumber: Missing School Code";
                     continue;
                 }
                 
                 if (empty($name)) {
                     $errors[] = "Row $rowNumber: Missing School Name";
                     continue;
                 }
                 
                 if (empty($regionCode)) {
                     $errors[] = "Row $rowNumber: Missing Region Code";
                     continue;
                 }
                 
                 if (empty($districtCode)) {
                     $errors[] = "Row $rowNumber: Missing District Code";
                     continue;
                 }
                 
                 // Look up region by code
                 $region = \App\Models\Region::where('code', $regionCode)->first();
                 if (!$region) {
                     $errors[] = "Row $rowNumber: Region code '$regionCode' does not exist";
                     continue;
                 }
                 
                 // Look up district by code
                 $district = \App\Models\District::where('code', $districtCode)->first();
                 if (!$district) {
                     $errors[] = "Row $rowNumber: District code '$districtCode' does not exist";
                     continue;
                 }
                 
                 // Verify district belongs to region
                 if ($district->region_id != $region->id) {
                     $errors[] = "Row $rowNumber: District code '$districtCode' does not belong to region '$regionCode'";
                     continue;
                 }
                 
                 // Create or update school
                 \App\Models\School::updateOrCreate(
                     ['registration_number' => $registrationCode],
                     [
                         'code' => $registrationCode,
                         'name' => $name,
                         'registration_number' => $registrationCode,
                         'ownership' => $ownership,
                         'region_id' => $region->id,
                         'district_id' => $district->id,
                         'school_type' => 'SECONDARY',
                         'is_active' => true
                     ]
                 );
                 
                 $count++;
                 
             } catch (\Exception $e) {
                 $errors[] = "Row $rowNumber error: " . $e->getMessage();
                 \Log::error('Row import error', ['rowNumber' => $rowNumber, 'error' => $e->getMessage()]);
                 continue;
             }
         }
         
         fclose($handle);
         
         \Log::info('School import completed', [
             'count' => $count,
             'errors_count' => count($errors),
             'errors' => $errors
         ]);
         
         return response()->json([
             'message' => $count . ' school(s) imported successfully' . (count($errors) > 0 ? ' with ' . count($errors) . ' error(s)' : ''),
             'count' => $count,
             'errors' => $errors
         ]);
     });

    // New Structured Candidate Import API (two-phase)
    Route::post('/api/candidates/import/validate', [CandidateImportController::class, 'validateImport']);
    Route::post('/api/candidates/import/commit', [CandidateImportController::class, 'commitImport']);
    Route::post('/api/candidates/import/csee-registration-pdf/validate', [CandidateImportController::class, 'validateCseeRegistrationPdf']);
    Route::post('/api/candidates/import/csee-registration-pdf/commit', [CandidateImportController::class, 'commitCseeRegistrationPdf']);
    Route::post('/api/candidates/import/async', [CandidateImportController::class, 'asyncBulkImport']);
    Route::get('/api/candidates/import/template', [CandidateImportController::class, 'downloadTemplate']);
    Route::post('/api/candidates/import/download-errors', [CandidateImportController::class, 'downloadErrorReport']);

    // Bulk delete
    Route::post('/api/candidates/bulk-delete', function (\Illuminate\Http\Request $request) {
         $validated = $request->validate([
             'ids' => 'required|array',
             'ids.*' => 'integer|exists:candidates,id'
         ]);
         
         $deleted = \App\Models\Candidate::whereIn('id', $validated['ids'])->delete();
         \App\Services\PsleCacheService::incrementVersion();
         return response()->json(['deleted' => $deleted, 'message' => 'Candidates deleted successfully']);
     });

    // Data Audit Endpoints
    Route::get('/api/audit/candidates', function () {
         $totalSchools = \App\Models\School::count();
         $totalCandidates = \App\Models\Candidate::count();
         $schoolsWithoutDistrict = \App\Models\School::whereNull('district_id')->count();
         
         // Find school-district mismatches
         $mismatches = \App\Models\Candidate::whereHas('school', function($q) {
             $q->whereNull('district_id');
         })->with('school')->get()->map(function($candidate) {
             return [
                 'candidate_id' => $candidate->candidate_id,
                 'candidate_name' => $candidate->full_name,
                 'school_id' => $candidate->school_id,
                 'school_code' => $candidate->school?->code,
                 'school_name' => $candidate->school?->name,
                 'expected_district_id' => null,
                 'actual_district_id' => $candidate->school?->district_id
             ];
         })->toArray();
         
         return response()->json([
             'total_schools' => $totalSchools,
             'total_candidates' => $totalCandidates,
             'schools_without_district' => $schoolsWithoutDistrict,
             'mismatches' => $mismatches
         ]);
     });

    Route::post('/api/audit/candidates/fix', function (\Illuminate\Http\Request $request) {
         $validated = $request->validate([
             'mismatches' => 'required|array'
         ]);
         
         $fixed = 0;
         foreach ($validated['mismatches'] as $mismatch) {
             $candidate = \App\Models\Candidate::find($mismatch['candidate_id'] ?? null);
             if ($candidate && $candidate->school && $candidate->school->district_id) {
                 $candidate->update(['school_id' => $candidate->school_id]);
                 $fixed++;
             }
         }
         
         return response()->json([
             'fixed' => $fixed,
             'message' => "$fixed candidate(s) fixed"
         ]);
     });

     // Exam Types API Endpoints
     Route::get('/api/exam-types', function () {
         $examTypes = \App\Models\ExamType::withCount('candidates')->get();
         
         $data = $examTypes->map(function($e) {
             return [
                 'id' => $e->id,
                 'name' => $e->name,
                 'code' => $e->code,
                 'level' => $e->level,
                 'description' => $e->description,
                 'candidates_count' => $e->candidates_count ?? 0
             ];
         });
         
         return response()->json(['data' => $data]);
     });

     Route::get('/api/exam-types/{code}', function ($code) {


         $normalizedCode = strtoupper($code);


         if (in_array($normalizedCode, ['PSLE_MY', 'PSLE_G', 'PSLE'])) {


             $normalizedCode = 'PSLE';


         }


         $examType = \App\Models\ExamType::where('code', $normalizedCode)


             ->withCount('candidates')


             ->firstOrFail();


         


         return response()->json([


             'data' => [


                 'id' => $examType->id,


                 'name' => $examType->name,


                 'code' => $examType->code,


                 'level' => $examType->level,


                 'description' => $examType->description,


                 'candidates_count' => $examType->candidates_count ?? 0


             ]


         ]);


     });

     Route::post('/api/exam-types', function (\Illuminate\Http\Request $request) {
         $validated = $request->validate([
             'name' => 'required|unique:exam_types,name',
             'code' => 'required|unique:exam_types,code',
             'level' => 'nullable|string|max:255',
             'description' => 'nullable|string'
         ]);
         
         $examType = \App\Models\ExamType::create($validated);
         return response()->json(['message' => 'Exam type created successfully', 'data' => $examType], 201);
     });

     Route::put('/api/exam-types/{id}', function (\Illuminate\Http\Request $request, $id) {
         $examType = \App\Models\ExamType::findOrFail($id);
         
         $validated = $request->validate([
             'name' => 'required|unique:exam_types,name,' . $examType->id,
             'code' => 'required|unique:exam_types,code,' . $examType->id,
             'level' => 'nullable|string|max:255',
             'description' => 'nullable|string'
         ]);
         
         $examType->update($validated);
         return response()->json(['message' => 'Exam type updated successfully', 'data' => $examType]);
     });

     Route::delete('/api/exam-types/{id}', function ($id) {
         $examType = \App\Models\ExamType::findOrFail($id);
         $examType->delete();
         return response()->json(['message' => 'Exam type deleted successfully']);
     });

     Route::post('/api/exam-types/psle/subjects/sync-official', [ExamTypeController::class, 'syncOfficialPsleSubjects']);
     Route::post('/api/exam-types/csee/subjects/sync-official', [ExamTypeController::class, 'syncOfficialCseeSubjects']);

     // Subjects API Endpoints for exam types
     Route::get('/api/exam-types/{code}/subjects', [ExamTypeController::class, 'getSubjects']);
     Route::post('/api/exam-types/{code}/subjects', [ExamTypeController::class, 'createSubject']);
     Route::put('/api/exam-types/{code}/subjects/{id}', [ExamTypeController::class, 'updateSubject']);
     Route::delete('/api/exam-types/{code}/subjects/{id}', [ExamTypeController::class, 'deleteSubject']);

     // Global Subjects API
     Route::get('/api/subjects', function () {
         try {
             $subjects = \App\Models\Subject::orderBy('name')->get(['id', 'code', 'name']);
             return response()->json(['data' => $subjects]);
         } catch (\Exception $e) {
             \Log::error('Subjects API error:', ['error' => $e->getMessage()]);
             return response()->json(['data' => []], 200);
         }
     });

     // Global Exam Years API for Mark Entry
     Route::get('/api/exam-years/with-acsee', function () {
         try {
             $acseeType = \App\Models\ExamType::where('code', 'ACSEE')->first();
             $yearsQuery = \App\Models\ExamYear::query()->orderBy('year_label', 'desc');
             
             if ($acseeType) {
                 $yearsQuery->whereHas('candidateExamRegistrations', function($q) use ($acseeType) { 
                     $q->where('exam_type_id', $acseeType->id); 
                 });
             }
             
             $years = $yearsQuery->get();
             
             // Fallback: If no years have ACSEE registrations yet, just return all years so the UI doesn't break
             if ($years->isEmpty()) {
                 $years = \App\Models\ExamYear::orderBy('year_label', 'desc')->get();
             }

             $data = $years->map(function($year) { return ['id' => $year->id, 'year_label' => $year->year_label, 'is_locked' => $year->is_locked]; });
             return response()->json(['years' => $data]);
         } catch (\Exception $e) {
             return response()->json(['years' => [], 'error' => 'Unable to load exam years'], 500);
         }
     });

     Route::get('/api/exam-years/active', function () {
         try {
             $activeYear = \App\Models\ExamYear::active()->first();
             if (!$activeYear) return response()->json(['active_year' => null, 'message' => 'No active exam year set']);
             return response()->json(['active_year' => ['id' => $activeYear->id, 'year_label' => $activeYear->year_label, 'is_locked' => $activeYear->is_locked]]);
         } catch (\Exception $e) {
             return response()->json(['active_year' => null, 'error' => 'Unable to load active exam year'], 500);
         }
     });

     // Daily Marks Entry Report API
     Route::get('/api/daily-marks-entry-report', function (Request $request) {
         try {
             $examYearId = $request->get('exam_year_id', '');
             $regionId = $request->get('region_id', '');
             $subjectId = $request->get('subject_id', '');
             $entryDate = $request->get('entry_date', '');

             // Get all subjects first
             $subjects = \App\Models\Subject::orderBy('name')->get(['id', 'name', 'code']);

             // If specific subject is selected, filter to that subject only
             if ($subjectId) {
                 $subjects = $subjects->where('id', $subjectId);
             }

             // Build report data by aggregating marks entries
             $reportData = [];
             $sn = 1;

             foreach ($subjects as $subject) {
                 // Keep this query lightweight to avoid memory/timeouts on large datasets.
                 $marksQuery = \App\Models\SubjectMarks::query()
                     ->select(['subject_id', 'created_at', 'updated_at', 'year', 'candidate_id'])
                     ->where('subject_id', $subject->id);

                 // Filter by exam year if provided
                 if ($examYearId) {
                     $examYear = \App\Models\ExamYear::find($examYearId);
                     if ($examYear) {
                         // Extract year from year_label (e.g., "2026" from "2026")
                         $yearValue = preg_replace('/[^0-9]/', '', $examYear->year_label);
                         if ($yearValue) {
                             $marksQuery->where('year', $yearValue);
                         }
                     }
                 }

                 // Filter by region if provided
                 if ($regionId) {
                     $marksQuery->whereHas('candidate.school', function($sq) use ($regionId) {
                         $sq->where('region_id', $regionId);
                     });
                 }

                 // Filter by entry date (accepts YYYY-MM-DD and MM/DD/YYYY)
                 if ($entryDate) {
                     try {
                         $parsedDate = \Carbon\Carbon::parse($entryDate)->toDateString();
                         $marksQuery->where(function ($q) use ($parsedDate) {
                             $q->whereDate('created_at', $parsedDate)
                               ->orWhereDate('updated_at', $parsedDate);
                         });
                     } catch (\Throwable $e) {
                         // Ignore invalid date format and proceed without date filter
                     }
                 }

                 $marks = $marksQuery->get();

                 // Count total expected scripts (candidates)
                 $totalCandidates = $marks->count();
                 if ($totalCandidates === 0) {
                     continue;
                 }

                 // Get entry dates from created_at and group by day
                 $dayGroups = [
                     'day1' => 0,
                     'day2' => 0,
                     'day3' => 0,
                     'day4' => 0,
                     'day5' => 0,
                     'remainder' => 0,
                 ];

                 // Simple logic: count entries by creation date
                 $now = now();
                 foreach ($marks as $mark) {
                     $createdAt = $mark->created_at ?? $mark->updated_at ?? null;
                     if (!$createdAt) {
                         $dayGroups['remainder']++;
                         continue;
                     }
                     $daysOld = $createdAt->diffInDays($now);
                     
                     if ($daysOld <= 1) {
                         $dayGroups['day1']++;
                     } elseif ($daysOld <= 2) {
                         $dayGroups['day2']++;
                     } elseif ($daysOld <= 3) {
                         $dayGroups['day3']++;
                     } elseif ($daysOld <= 4) {
                         $dayGroups['day4']++;
                     } elseif ($daysOld <= 5) {
                         $dayGroups['day5']++;
                     } else {
                         $dayGroups['remainder']++;
                     }
                 }

                 // Calculate percentages
                 $day1Pct = $totalCandidates > 0 ? ($dayGroups['day1'] / $totalCandidates) * 100 : 0;
                 $day2Pct = $totalCandidates > 0 ? ($dayGroups['day2'] / $totalCandidates) * 100 : 0;
                 $day3Pct = $totalCandidates > 0 ? ($dayGroups['day3'] / $totalCandidates) * 100 : 0;
                 $day4Pct = $totalCandidates > 0 ? ($dayGroups['day4'] / $totalCandidates) * 100 : 0;
                 $day5Pct = $totalCandidates > 0 ? ($dayGroups['day5'] / $totalCandidates) * 100 : 0;
                 $remainderPct = $totalCandidates > 0 ? ($dayGroups['remainder'] / $totalCandidates) * 100 : 0;

                 $reportData[] = [
                     's_n' => $sn++,
                     'subject_id' => $subject->id,
                     'subject_code' => $subject->code,
                     'subject_name' => $subject->name,
                     'expected_scripts' => $totalCandidates,
                     'day1_count' => $dayGroups['day1'],
                     'day1_percentage' => $day1Pct,
                     'day2_count' => $dayGroups['day2'],
                     'day2_percentage' => $day2Pct,
                     'day3_count' => $dayGroups['day3'],
                     'day3_percentage' => $day3Pct,
                     'day4_count' => $dayGroups['day4'],
                     'day4_percentage' => $day4Pct,
                     'day5_count' => $dayGroups['day5'],
                     'day5_percentage' => $day5Pct,
                     'remainder_count' => $dayGroups['remainder'],
                     'remainder_percentage' => $remainderPct,
                     'remarks' => ''
                 ];
             }

             return response()->json($reportData);
         } catch (\Exception $e) {
             \Log::error('Daily Marks Entry Report API error:', [
                 'error' => $e->getMessage(),
                 'trace' => $e->getTraceAsString()
             ]);
             return response()->json([], 200);
         }
     });

     // Candidates API Endpoints for ACSEE
     Route::get('/api/exam-types/{examTypeCode}/candidates', [ExamTypeController::class, 'getAcseeCandicates']);

     // Combinations API Endpoints for ACSEE
      Route::get('/api/exam-types/{code}/combinations', [ExamTypeController::class, 'getCombinations']);
      Route::post('/api/exam-types/{code}/combinations', [ExamTypeController::class, 'createCombination']);
      Route::put('/api/exam-types/{code}/combinations/{id}', [ExamTypeController::class, 'updateCombination']);
      Route::delete('/api/exam-types/{code}/combinations/{id}', [ExamTypeController::class, 'deleteCombination']);

      // Subject Allocation for ACSEE
      Route::post('/api/exam-types/acsee/allocate-subjects', function (\Illuminate\Http\Request $request) {
          // Validate input
          $validated = $request->validate([
              'candidate_id' => 'required|exists:candidates,id',
              'exam_year_id' => 'required|exists:exam_years,id',
              'subject_ids' => 'required|array|min:1',
              'subject_ids.*' => 'integer|exists:subjects,id',
              'is_principal_map' => 'required|array',
              'replace_allocations' => 'boolean|default:false',
              'source' => 'required|in:manual,template',
          ]);

          try {
              // Load candidate with exam registration context
              $candidate = \App\Models\Candidate::findOrFail($validated['candidate_id']);
              
              // Get exam_type_id from candidate
              $examRegistration = $candidate->examRegistrations()->first();
              if (!$examRegistration) {
                  return response()->json([
                      'ok' => false,
                      'errors' => ['Candidate does not have an exam registration'],
                      'warnings' => [],
                  ], 422);
              }

              // Run validator
              $validator = new \App\Services\AcseeAllocationValidator();
              $validation = $validator->validate(
                  $candidate,
                  $examRegistration->exam_type_id,
                  $validated['exam_year_id'],
                  $validated['subject_ids']
              );

              if (!$validation['ok']) {
                  return response()->json([
                      'ok' => false,
                      'errors' => $validation['errors'],
                      'warnings' => $validation['warnings'],
                      'allocated_subjects' => [],
                  ], 422);
              }

              // Transactional allocation commit
              \Illuminate\Support\Facades\DB::transaction(function () use ($candidate, $validated, $validation, $examRegistration) {
                  if ($validated['replace_allocations'] ?? false) {
                      // Delete existing allocations for this exam_year
                      $candidate->subjectSelections()
                          ->where('exam_year_id', $validated['exam_year_id'])
                          ->delete();
                  }

                  // Create new allocations
                  foreach ($validation['all_subject_ids'] as $subjectId) {
                      $isPrincipal = in_array($subjectId, $validation['principal_subject_ids']);
                      
                      \App\Models\CandidateSubjectSelection::updateOrCreate(
                          [
                              'candidate_id' => $candidate->id,
                              'exam_type_id' => $examRegistration->exam_type_id,
                              'exam_year_id' => $validated['exam_year_id'],
                              'subject_id' => $subjectId,
                              'year' => \App\Models\ExamYear::find($validated['exam_year_id'])->year ?? date('Y'),
                          ],
                          [
                              'is_principal' => $isPrincipal,
                              'source' => $validated['source'],
                              'created_by' => auth()->id(),
                              'is_active' => true,
                          ]
                      );
                  }
              });

              // Return allocated subjects
              $allocated = $candidate->subjectSelections()
                  ->with('subject')
                  ->where('exam_year_id', $validated['exam_year_id'])
                  ->get();

              return response()->json([
                  'ok' => true,
                  'message' => 'Subjects allocated successfully',
                  'allocated_subjects' => $allocated->map(fn($s) => [
                      'id' => $s->subject_id,
                      'code' => $s->subject->code,
                      'name' => $s->subject->name,
                      'is_principal' => $s->is_principal,
                  ]),
                  'created_count' => count($validation['all_subject_ids']),
                  'skipped_count' => 0,
              ]);
          } catch (\Exception $e) {
              \Log::error('Allocation error: ' . $e->getMessage(), ['exception' => $e]);
              
              // Sanitize error message for production
              $errorMessage = env('APP_ENV') === 'production'
                  ? 'An error occurred while allocating subjects. Please try again.'
                  : 'Database error: ' . $e->getMessage();
              
              return response()->json([
                  'ok' => false,
                  'errors' => [$errorMessage],
                  'warnings' => [],
                  'allocated_subjects' => [],
              ], 500);
          }
      });

      // Get combination subjects for preview
      Route::get('/api/combinations/{id}/subjects', function ($id) {
          try {
              $combination = \App\Models\Combination::findOrFail($id);
              $subjects = $combination->subjects()->select('subjects.id', 'subjects.code', 'subjects.name')->get();
              
              return response()->json([
                  'ok' => true,
                  'data' => $subjects,
              ]);
          } catch (\Exception $e) {
              return response()->json([
                  'ok' => false,
                  'errors' => ['Combination not found'],
              ], 404);
          }
      });

      // ACSEE Allocation CSV Import Routes
      Route::get('/api/exam-types/acsee/templates/school-allocation.csv', [AcseeAllocationController::class, 'getSchoolTemplate']);
      Route::get('/api/exam-types/acsee/templates/private-allocation.csv', [AcseeAllocationController::class, 'getPrivateTemplate']);
      Route::post('/api/exam-types/acsee/allocate-from-csv/validate', [AcseeAllocationController::class, 'validateAllocationImport']);
      Route::post('/api/exam-types/acsee/allocate-from-csv/commit', [AcseeAllocationController::class, 'commitAllocationImport']);
      Route::post('/api/exam-types/acsee/allocate-from-csv', [AcseeAllocationController::class, 'importAllocations']);
      Route::post('/api/exam-types/acsee/allocate-from-csv/download-errors', [AcseeAllocationController::class, 'downloadErrorReport']);

      // MEO Geofence Location Verification Routes
      Route::get('/mark-entry/location/verify', [\App\Http\Controllers\MarkEntryLocationController::class, 'showVerificationPage'])->name('mark-entry.location.verify.page');
      Route::post('/mark-entry/location/verify', [\App\Http\Controllers\MarkEntryLocationController::class, 'verifyLocation'])->name('mark-entry.location.verify.submit');

      // Mark Entry Module Routes (ACSEE)
      Route::get('/mark-entry/acsee', [MarkEntryController::class, 'index']);
      Route::get('/mark-entry/csee', [\App\Http\Controllers\CseeMarkEntryController::class, 'index']);
      Route::get('/api/mark-entry/csee/bootstrap', [\App\Http\Controllers\CseeMarkEntryController::class, 'bootstrap']);
      Route::get('/api/mark-entry/csee/districts', [\App\Http\Controllers\CseeMarkEntryController::class, 'districts']);
      Route::get('/api/mark-entry/csee/schools', [\App\Http\Controllers\CseeMarkEntryController::class, 'schools']);
      Route::get('/api/mark-entry/csee/subjects', [\App\Http\Controllers\CseeMarkEntryController::class, 'subjects']);
      Route::get('/api/mark-entry/csee/dashboard', [\App\Http\Controllers\CseeMarkEntryController::class, 'dashboard']);
      Route::get('/mark-entry/psle', [\App\Http\Controllers\PsleMarkEntryController::class, 'index'])->name('mark-entry.psle.index');
      Route::post('/mark-entry/psle/users/create', [\App\Http\Controllers\PsleMarkEntryController::class, 'createUser']);
      Route::get('/mark-entry/psle/users/template', [\App\Http\Controllers\PsleMarkEntryController::class, 'downloadUserImportTemplate']);
      Route::post('/mark-entry/psle/users/import', [\App\Http\Controllers\PsleMarkEntryController::class, 'importUsers']);
      Route::get('/mark-entry/psle/users/import-errors/{filename}', [\App\Http\Controllers\PsleMarkEntryController::class, 'downloadUserImportErrors']);
      Route::post('/mark-entry/psle/users/{id}/toggle-status', [\App\Http\Controllers\PsleMarkEntryController::class, 'toggleUserStatus']);
      Route::post('/mark-entry/psle/marking-centres/create', [\App\Http\Controllers\PsleMarkEntryController::class, 'createMarkingCentre']);
      Route::post('/mark-entry/psle/marking-centres/{id}/toggle-status', [\App\Http\Controllers\PsleMarkEntryController::class, 'toggleMarkingCentreStatus']);
      Route::post('/mark-entry/psle/marking-centres/{id}/update', [\App\Http\Controllers\PsleMarkEntryController::class, 'updateMarkingCentre']);
      Route::post('/mark-entry/psle/marking-centres/{id}/delete', [\App\Http\Controllers\PsleMarkEntryController::class, 'deleteMarkingCentre']);
      Route::post('/mark-entry/psle/marking-centres/geofence-toggle', [\App\Http\Controllers\PsleMarkEntryController::class, 'toggleGeofence']);
      Route::post('/mark-entry/psle/assignments/create', [\App\Http\Controllers\PsleMarkEntryController::class, 'createAssignment']);
      Route::post('/mark-entry/psle/assignments/{id}/revoke', [\App\Http\Controllers\PsleMarkEntryController::class, 'revokeAssignment']);
      Route::get('/mark-entry/psle/subject-panel-assignments', function () {
          return redirect('/mark-entry/psle?view=subject-panel-assignments');
      })->name('mark-entry.psle.subject-panel-assignments.index');

      Route::prefix('/mark-entry/psle/subject-panel-assignments')
          ->name('mark-entry.psle.subject-panel-assignments.')
          ->middleware(['admin'])
          ->group(function () {
              Route::post('/', [\App\Http\Controllers\Admin\SubjectPanelAssignmentController::class, 'store'])->name('store');
              Route::delete('{subjectPanelAssignment}', [\App\Http\Controllers\Admin\SubjectPanelAssignmentController::class, 'destroy'])->name('destroy');
              Route::patch('{subjectPanelAssignment}/toggle', [\App\Http\Controllers\Admin\SubjectPanelAssignmentController::class, 'toggleActive'])->name('toggle');
          });
      Route::get('/mark-entry/psle/health', [\App\Http\Controllers\PsleMarkEntryController::class, 'health']);
      Route::post('/api/mark-entry/psle/marks/save', [\App\Http\Controllers\PsleMarkEntryController::class, 'saveMark']);
      Route::post('/mark-entry/psle/single/validate', [\App\Http\Controllers\PsleMarkEntryController::class, 'singleValidate']);
      Route::post('/mark-entry/psle/single/commit', [\App\Http\Controllers\PsleMarkEntryController::class, 'singleCommit']);
      Route::post('/mark-entry/psle/bulk/school/validate-zip', [\App\Http\Controllers\PsleMarkEntryController::class, 'schoolValidateZip']);
      Route::post('/mark-entry/psle/bulk/school/commit-zip', [\App\Http\Controllers\PsleMarkEntryController::class, 'schoolCommitZip']);
      Route::post('/mark-entry/psle/bulk/district/validate-zip', [\App\Http\Controllers\PsleMarkEntryController::class, 'districtValidateZip']);
      Route::post('/mark-entry/psle/bulk/district/commit-zip', [\App\Http\Controllers\PsleMarkEntryController::class, 'districtCommitZip']);
      Route::get('/api/mark-entry/psle/recent-batches', [\App\Http\Controllers\PsleMarkEntryController::class, 'recentBatches']);
      Route::get('/api/mark-entry/psle/lifecycle/dashboard', [\App\Http\Controllers\PsleMarkEntryController::class, 'lifecycleDashboard']);
      Route::get('/api/mark-entry/psle/reports/summary', [\App\Http\Controllers\PsleMarkEntryController::class, 'reportsSummary']);
      Route::get('/api/mark-entry/psle/reports/export', [\App\Http\Controllers\PsleMarkEntryController::class, 'reportsExport']);
      Route::post('/mark-entry/psle/batches/{id}/submit', [\App\Http\Controllers\PsleMarkEntryController::class, 'submitBatch']);
      Route::post('/mark-entry/psle/batches/{id}/approve', [\App\Http\Controllers\PsleMarkEntryController::class, 'approveBatch']);
      Route::post('/mark-entry/psle/batches/{id}/reject', [\App\Http\Controllers\PsleMarkEntryController::class, 'rejectBatch']);
      Route::post('/mark-entry/psle/batches/{id}/lock', [\App\Http\Controllers\PsleMarkEntryController::class, 'lockBatch']);
      Route::post('/mark-entry/psle/batches/{id}/unlock', [\App\Http\Controllers\PsleMarkEntryController::class, 'unlockBatch']);
      Route::post('/mark-entry/psle/outliers/{id}/verify', [\App\Http\Controllers\PsleMarkEntryController::class, 'verifyOutlier']);
      Route::post('/mark-entry/psle/outliers/{id}/resolve', [\App\Http\Controllers\PsleMarkEntryController::class, 'resolveOutlier']);
      Route::post('/mark-entry/psle/outliers/{id}/escalate', [\App\Http\Controllers\PsleMarkEntryController::class, 'escalateOutlier']);
      Route::get('/api/mark-entry/psle/reports/scoresheet-subjects', [\App\Http\Controllers\PsleMarkEntryController::class, 'scoresheetSubjects']);
      
      // PSLE Bulk Import Workflow Routes
      Route::get('/mark-entry/psle/bulk/filters/regions', [\App\Http\Controllers\PsleMarkEntryController::class, 'bulkFilterRegions'])->name('mark-entry.psle.bulk.filters.regions');
      Route::get('/mark-entry/psle/bulk/filters/districts', [\App\Http\Controllers\PsleMarkEntryController::class, 'bulkFilterDistricts'])->name('mark-entry.psle.bulk.filters.districts');
      Route::get('/mark-entry/psle/bulk/filters/schools', [\App\Http\Controllers\PsleMarkEntryController::class, 'bulkFilterSchools'])->name('mark-entry.psle.bulk.filters.schools');
      Route::get('/mark-entry/psle/bulk/filters/subjects', [\App\Http\Controllers\PsleMarkEntryController::class, 'bulkFilterSubjects'])->name('mark-entry.psle.bulk.filters.subjects');
      Route::get('/mark-entry/psle/bulk-import/template', [\App\Http\Controllers\PsleMarkEntryController::class, 'bulkImportTemplate'])->name('mark-entry.psle.bulk-import.template');
      Route::post('/mark-entry/psle/bulk-import/preview', [\App\Http\Controllers\PsleMarkEntryController::class, 'bulkImportPreview'])->name('mark-entry.psle.bulk-import.preview');
      Route::post('/mark-entry/psle/bulk-import/confirm', [\App\Http\Controllers\PsleMarkEntryController::class, 'bulkImportConfirm'])->name('mark-entry.psle.bulk-import.confirm');
      Route::get('/api/mark-entry/psle/bulk-import/history', [\App\Http\Controllers\PsleMarkEntryController::class, 'bulkImportHistory'])->name('mark-entry.psle.bulk-import.history');
      Route::get('/mark-entry/psle/bulk-import/errors/{batchId}', [\App\Http\Controllers\PsleMarkEntryController::class, 'bulkImportDownloadErrors'])->name('mark-entry.psle.bulk-import.errors');

      // PSLE Candidate Registration Workflow Routes
      Route::get('/mark-entry/psle/candidates/filters/regions', [\App\Http\Controllers\MarkEntry\PsleCandidateRegistrationController::class, 'regions'])->name('mark-entry.psle.candidates.filters.regions');
      Route::get('/mark-entry/psle/candidates/filters/councils', [\App\Http\Controllers\MarkEntry\PsleCandidateRegistrationController::class, 'councils'])->name('mark-entry.psle.candidates.filters.councils');
      Route::get('/mark-entry/psle/candidates/filters/schools', [\App\Http\Controllers\MarkEntry\PsleCandidateRegistrationController::class, 'schools'])->name('mark-entry.psle.candidates.filters.schools');
      Route::get('/mark-entry/psle/candidates/list', [\App\Http\Controllers\MarkEntry\PsleCandidateRegistrationController::class, 'list'])->name('mark-entry.psle.candidates.list');
      Route::post('/mark-entry/psle/candidates', [\App\Http\Controllers\MarkEntry\PsleCandidateRegistrationController::class, 'store'])->name('mark-entry.psle.candidates.store');
      Route::put('/mark-entry/psle/candidates/{candidate}', [\App\Http\Controllers\MarkEntry\PsleCandidateRegistrationController::class, 'update'])->name('mark-entry.psle.candidates.update');
      Route::delete('/mark-entry/psle/candidates/{candidate}', [\App\Http\Controllers\MarkEntry\PsleCandidateRegistrationController::class, 'destroy'])->name('mark-entry.psle.candidates.destroy');
      Route::get('/mark-entry/psle/candidates/template', [\App\Http\Controllers\MarkEntry\PsleCandidateRegistrationController::class, 'template'])->name('mark-entry.psle.candidates.template');
      Route::post('/mark-entry/psle/candidates/bulk/preview', [\App\Http\Controllers\MarkEntry\PsleCandidateRegistrationController::class, 'preview'])->name('mark-entry.psle.candidates.bulk.preview');
      Route::post('/mark-entry/psle/candidates/bulk/import', [\App\Http\Controllers\MarkEntry\PsleCandidateRegistrationController::class, 'import'])->name('mark-entry.psle.candidates.bulk.import');
      Route::post('/mark-entry/psle/candidates/import/validate', [\App\Http\Controllers\MarkEntry\PsleCandidateRegistrationController::class, 'preview'])->name('mark-entry.psle.candidates.import.validate');
      Route::post('/mark-entry/psle/candidates/import/commit', [\App\Http\Controllers\MarkEntry\PsleCandidateRegistrationController::class, 'import'])->name('mark-entry.psle.candidates.import.commit');
      Route::get('/api/mark-entry/psle/reports/scoresheet-pdf', [\App\Http\Controllers\PsleMarkEntryController::class, 'scoresheetPdf']);
      Route::get('/api/mark-entry/psle/reports/scoresheet-pdf/school-zip', [\App\Http\Controllers\PsleMarkEntryController::class, 'scoresheetSchoolZip']);
      Route::get('/api/mark-entry/psle/reports/scoresheet-pdf/district-zip', [\App\Http\Controllers\PsleMarkEntryController::class, 'scoresheetDistrictZip']);
      Route::get('/api/mark-entry/psle/reports/scoresheet-pdf/region-zip', [\App\Http\Controllers\PsleMarkEntryController::class, 'scoresheetRegionZip']);
      Route::get('/api/mark-entry/psle/reports/entered-marks-pdf', [\App\Http\Controllers\PsleMarkEntryController::class, 'enteredMarksPdf']);
      Route::get('/api/mark-entry/psle/reports/entered-marks-pdf/school-zip', [\App\Http\Controllers\PsleMarkEntryController::class, 'enteredMarksSchoolZip']);
      Route::get('/api/mark-entry/psle/reports/entered-marks-pdf/district-zip', [\App\Http\Controllers\PsleMarkEntryController::class, 'enteredMarksDistrictZip']);
      Route::get('/api/mark-entry/psle/reports/entered-marks-pdf/region-zip', [\App\Http\Controllers\PsleMarkEntryController::class, 'enteredMarksRegionZip']);
      Route::get('/api/mark-entry/psle/reports/progress/excel', [\App\Http\Controllers\PsleMarkEntryController::class, 'exportRegionalProgressExcel']);
      Route::get('/api/mark-entry/psle/reports/productivity/excel', [\App\Http\Controllers\PsleMarkEntryController::class, 'exportOfficerProductivityExcel']);
      Route::get('/api/mark-entry/psle/reports/missing-marks/excel', [\App\Http\Controllers\PsleMarkEntryController::class, 'exportMissingMarksExcel']);
      Route::get('/api/mark-entry/psle/reports/outliers/excel', [\App\Http\Controllers\PsleMarkEntryController::class, 'exportOutliersExcel']);
      
      // Validation Workflow Routes
      Route::get('/api/mark-entry/psle/performance-rankings', [\App\Http\Controllers\PsleMarkEntryController::class, 'performanceRankings'])->name('mark-entry.psle.performance-rankings');
      Route::post('/api/mark-entry/psle/validation/run', [\App\Http\Controllers\PsleMarkEntryController::class, 'runValidation']);
      Route::post('/api/mark-entry/psle/validation/correct', [\App\Http\Controllers\PsleMarkEntryController::class, 'correctValidationError']);
      Route::post('/api/mark-entry/psle/validation/resolve', [\App\Http\Controllers\PsleMarkEntryController::class, 'resolveValidationError']);
      Route::get('/api/mark-entry/psle/reports/validation-errors/csv', [\App\Http\Controllers\PsleMarkEntryController::class, 'exportValidationErrorsCsv']);
      Route::get('/api/mark-entry/psle/audit/summary', [\App\Http\Controllers\PsleMarkEntryController::class, 'auditSummary']);
      Route::get('/api/mark-entry/psle/admin/summary', [\App\Http\Controllers\PsleMarkEntryController::class, 'administrationSummary']);
      Route::post('/api/mark-entry/psle/batches/bulk-validate', [\App\Http\Controllers\PsleMarkEntryController::class, 'bulkValidate']);
      Route::post('/api/mark-entry/psle/batches/bulk-submit', [\App\Http\Controllers\PsleMarkEntryController::class, 'bulkSubmit']);
      Route::post('/api/mark-entry/psle/batches/{batchId}/submit', [\App\Http\Controllers\PsleMarkEntryController::class, 'submitBatch']);
      Route::post('/api/mark-entry/psle/batches/{batchId}/approve', [\App\Http\Controllers\PsleMarkEntryController::class, 'approveBatch']);
      Route::post('/api/mark-entry/psle/batches/{batchId}/reject', [\App\Http\Controllers\PsleMarkEntryController::class, 'rejectBatch']);
      Route::post('/api/mark-entry/psle/batches/{batchId}/lock', [\App\Http\Controllers\PsleMarkEntryController::class, 'lockBatch']);
      Route::post('/api/mark-entry/psle/batches/{batchId}/unlock', [\App\Http\Controllers\PsleMarkEntryController::class, 'unlockBatch']);

      // PSLE Missing Marks Approval, Reject & Commit Routes
      Route::post('/mark-entry/psle/missing-marks/approve', [\App\Http\Controllers\PsleMarkEntryController::class, 'approveMissingMarks'])->name('mark-entry.psle.missing-marks.approve');
      Route::post('/mark-entry/psle/missing-marks/reject', [\App\Http\Controllers\PsleMarkEntryController::class, 'rejectMissingMarks'])->name('mark-entry.psle.missing-marks.reject');
      Route::post('/mark-entry/psle/missing-marks/commit', [\App\Http\Controllers\PsleMarkEntryController::class, 'commitApprovedABS'])->name('mark-entry.psle.missing-marks.commit');
      Route::post('/mark-entry/psle/missing-marks/bulk-approve', [\App\Http\Controllers\PsleMarkEntryController::class, 'bulkApproveMissingMarks'])->name('mark-entry.psle.missing-marks.bulk-approve');
      Route::post('/mark-entry/psle/missing-marks/bulk-commit-preview', [\App\Http\Controllers\PsleMarkEntryController::class, 'bulkCommitPreview'])->name('mark-entry.psle.missing-marks.bulk-commit-preview');
      Route::post('/mark-entry/psle/missing-marks/bulk-commit', [\App\Http\Controllers\PsleMarkEntryController::class, 'bulkCommitApprovedABS'])->name('mark-entry.psle.missing-marks.bulk-commit');
      Route::post('/mark-entry/psle/missing-marks/inc/save', [\App\Http\Controllers\PsleMarkEntryController::class, 'saveIncMissingMark'])->name('mark-entry.psle.missing-marks.inc.save');
      Route::get('/mark-entry/acsee/download-template', [MarkEntryController::class, 'downloadTemplate']);
      // DEPRECATED: legacy upload endpoint (use validate/commit)
      Route::post('/mark-entry/acsee/upload', [MarkEntryController::class, 'uploadMarks']);
      
      // New Two-Phase Single Subject CSV Endpoints
      Route::post('/mark-entry/acsee/single/validate', [MarkEntryController::class, 'singleValidate']);
      Route::post('/mark-entry/acsee/single/commit', [MarkEntryController::class, 'singleCommit']);

      // New Bulk ZIP Validate/Commit + Progress
      Route::post('/mark-entry/acsee/bulk/school/validate-zip', [MarkEntryController::class, 'schoolValidateZip']);
      Route::post('/mark-entry/acsee/bulk/district/validate-zip', [MarkEntryController::class, 'districtValidateZip']);
      Route::post('/mark-entry/acsee/bulk/school/commit-zip', [MarkEntryController::class, 'schoolCommitZip']);
      Route::post('/mark-entry/acsee/bulk/district/commit-zip', [MarkEntryController::class, 'districtCommitZip']);
      Route::post('/api/mark-entry/acsee/district-zip/commit', [MarkEntryController::class, 'districtCommitZip']);
      Route::get('/mark-entry/acsee/bulk/{id}/progress', [MarkEntryController::class, 'bulkProgress']);

      Route::get('/test-upload', function() { return response()->json(['status' => 'ok']); });
      Route::get('/mark-entry/acsee/batch/{batchId}', [MarkEntryController::class, 'getBatchDetails']);
      Route::get('/mark-entry/acsee/batch/{batchId}/error-report', [MarkEntryController::class, 'downloadErrorReport']);
      Route::post('/mark-entry/acsee/batch/{batchId}/lock', [MarkEntryController::class, 'lockBatch']);
      
      // Scoresheet PDF Routes
      Route::get('/mark-entry/acsee/scoresheet/print', [MarkEntryController::class, 'printScoresheet']);
      Route::get('/mark-entry/acsee/scoresheet/bulk-export', [MarkEntryController::class, 'bulkExportScoresheets']);

      // Bulk CSV Export Route (School)
      Route::get('/mark-entry/acsee/bulk-csv-download', [MarkEntryController::class, 'downloadBulkCsvExport']);

      // District Bulk CSV Export Route
      Route::get('/mark-entry/acsee/district-bulk-csv-download', [MarkEntryController::class, 'downloadDistrictBulkCsvExport']);

      // District Bulk Scoresheet Export Route
      Route::get('/mark-entry/acsee/district-bulk-scoresheet-download', [MarkEntryController::class, 'downloadDistrictBulkScoresheetExport']);

      // Bulk Import Routes (must be in web.php for session/CSRF support)
      Route::post('/api/bulk-import/preview', [\App\Http\Controllers\BulkImportController::class, 'preview']);
      Route::post('/api/bulk-import/start', [\App\Http\Controllers\BulkImportController::class, 'startImport']);
      Route::post('/api/bulk-import/district/start', [\App\Http\Controllers\BulkImportController::class, 'startDistrictImport']);
      Route::get('/api/bulk-import/{id}/progress', [\App\Http\Controllers\BulkImportController::class, 'getProgress']);
      Route::get('/api/bulk-import/{id}/recovery-status', [\App\Http\Controllers\BulkImportController::class, 'getRecoveryStatus']);
      Route::post('/api/bulk-import/{id}/retry-school', [\App\Http\Controllers\BulkImportController::class, 'retrySchool']);
      Route::post('/api/bulk-import/{id}/retry-all', [\App\Http\Controllers\BulkImportController::class, 'retryAll']);
      Route::get('/api/bulk-import/{id}', [\App\Http\Controllers\BulkImportController::class, 'getDetails']);

      // Mark Entry API Endpoints
      Route::get('/api/mark-entry/acsee/regions', [MarkEntryController::class, 'getRegions']);
      Route::get('/api/mark-entry/acsee/districts', [MarkEntryController::class, 'getDistricts']);
      Route::get('/api/mark-entry/acsee/schools', [MarkEntryController::class, 'getSchools']);
      Route::get('/api/mark-entry/acsee/schools-by-year', [MarkEntryController::class, 'getSchoolsByYear']);
      Route::get('/api/mark-entry/acsee/districts-by-year', [MarkEntryController::class, 'getDistrictsByYear']);
      Route::get('/api/mark-entry/acsee/subjects', [MarkEntryController::class, 'getSubjects']);
      Route::get('/api/mark-entry/acsee/subjects-by-school', [MarkEntryController::class, 'getSubjectsBySchoolAndYear']);
      
      // Exam Years API Endpoints moved to /admin/api/exam-years (admin group below)

      // ==================== ACSEE RESULTS ROUTES ====================
      require base_path('routes/results.php');

      // ==================== MARK ENTRY LIFECYCLE ROUTES ====================
      require base_path('routes/mark-entry.php');

      // ==================== EXAM DEVELOPMENT ROUTES ====================
      Route::middleware('exam-admin-access')->group(function () {
          require base_path('routes/exam-development.php');
      });
      });

// Temporary debug endpoint
Route::post('/api/test-upload', function (\Illuminate\Http\Request $request) {
    try {
        \Log::info('Test upload called', ['method' => $request->method()]);
        
        // Validate
        $validated = $request->validate([
            'zip_file' => 'required|file',
        ]);
        
        $file = $request->file('zip_file');
        \Log::info('File received', [
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'extension' => $file->getClientOriginalExtension(),
        ]);
        
        return response()->json(['success' => true, 'message' => 'File received', 'file' => $file->getClientOriginalName()]);
    } catch (\Exception $e) {
        \Log::error('Test upload failed: ' . $e->getMessage());
        return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
    }
})->middleware('auth');


// Custom Role-Based Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/zonal-control-centre', [\App\Http\Controllers\ZonalControlCentreController::class, 'index'])->name('admin.zonal-control-centre');

    // Registration Management
    Route::prefix('registration')->group(function () {
        Route::get('regions', function () { return view('registration.regions'); })->name('admin.registration.regions');
        Route::get('districts', function () { return view('registration.districts', ['regions' => \App\Models\Region::all()]); })->name('admin.registration.districts');
        Route::get('schools', function () { return view('registration.schools', ['regions' => \App\Models\Region::all()]); })->name('admin.registration.schools');
        Route::get('candidates', function () { return view('registration.candidates'); })->name('admin.registration.candidates');
        Route::get('candidates-by-district', function () { return view('registration.candidates-by-district'); })->name('admin.registration.candidates-by-district');
    });

    // Exam Types — use /manage/ prefix to avoid shadowing Filament's exam-types resource routes
    Route::get('exam-types/psle', function () {
        return view('exam-types.psle');
    })->name('admin.exam-types.psle');
    Route::get('exam-types/csee', function () {
        if (!in_array('CSEE', config('irms.active_exam_types', ['PSLE']))) {
            return redirect()->route('admin.exam-types.psle')->with('error', 'Only PSLE is currently enabled in this workspace.');
        }
        return view('exam-types.csee');
    })->name('admin.exam-types.csee');
    Route::get('exam-types/acsee', function () { 
        if (!in_array('ACSEE', config('irms.active_exam_types', ['PSLE']))) {
            return redirect()->route('admin.exam-types.psle')->with('error', 'Only PSLE is currently enabled in this workspace.');
        }
        return view('exam-types.acsee'); 
    })->name('admin.exam-types.acsee');

    // User Management
    Route::get('manage-users', [\App\Http\Controllers\AdminUserController::class, 'index'])->name('admin.manage-users');

    // System Settings Custom Routes
    Route::get('system-settings', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'index'])->name('admin.system-settings');
    Route::put('system-settings', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'update'])->name('admin.system-settings.update');
    Route::post('system-settings/clear-cache', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'clearCache'])->name('admin.system-settings.clear-cache');

    // API Endpoints for Registration (moved for admin-only protection)
    Route::prefix('api')->group(function () {

        // Users & Roles API
        Route::get('users', [\App\Http\Controllers\AdminUserController::class, 'apiList']);
        Route::post('users', [\App\Http\Controllers\AdminUserController::class, 'apiStore']);
        Route::put('users/{id}', [\App\Http\Controllers\AdminUserController::class, 'apiUpdate']);
        Route::delete('users/{id}', [\App\Http\Controllers\AdminUserController::class, 'apiDestroy']);
        Route::get('roles', [\App\Http\Controllers\AdminUserController::class, 'apiRoles']);
        // Regions
        Route::get('regions', [RegionController::class, 'apiGetRegions']);
        Route::post('regions', [RegionController::class, 'apiAddRegion']);
        Route::put('regions/{id}', [RegionController::class, 'apiUpdateRegion']);
        Route::delete('regions/{id}', [RegionController::class, 'apiDeleteRegion']);
        Route::get('regions/export-pdf', [RegionController::class, 'apiExportRegionsPdf']);
        Route::get('regions/export-excel', [RegionController::class, 'apiExportRegionsExcel']);
        Route::post('regions/import', [RegionController::class, 'apiImportRegions']);
        Route::post('regions/bulk-delete', function (\Illuminate\Http\Request $request) {
            $validated = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:regions,id']);
            $deleted = \App\Models\Region::whereIn('id', $validated['ids'])->delete();
            return response()->json(['deleted' => $deleted, 'message' => 'Regions deleted successfully']);
        });

        // Districts
        Route::get('districts', function () {
            $page = request('page', 1);
            $pageSize = request('page_size', 10);
            $search = request('search', '');
            $regionId = request('region_id', '');
            $query = \App\Models\District::with('region');
            if ($regionId) $query->where('region_id', $regionId);
            if ($search) $query->where(function($q) use ($search) { $q->where('code', 'like', "%$search%")->orWhere('name', 'like', "%$search%"); });
            $total = $query->count();
            $districts = $query->skip(($page - 1) * $pageSize)->take($pageSize)->get();
            $data = $districts->map(function($d) {
                return ['id' => $d->id, 'code' => $d->code, 'name' => $d->name, 'region_id' => $d->region_id, 'region_name' => $d->region->name ?? null, 'schools_count' => $d->schools()->count(), 'candidates_count' => \App\Models\Candidate::whereIn('school_id', $d->schools()->pluck('id'))->count(), 'status' => 'active'];
            });
            return response()->json(['data' => $data, 'pagination' => ['total_count' => $total, 'total_pages' => ceil($total / $pageSize), 'current_page' => $page, 'page_size' => $pageSize]]);
        });
        Route::post('districts', function (\Illuminate\Http\Request $request) {
            $validated = $request->validate(['code' => 'required|unique:districts', 'name' => 'required', 'region_id' => 'required|exists:regions,id']);
            $district = \App\Models\District::create($validated);
            return response()->json(['message' => 'District added', 'data' => $district], 201);
        });
        Route::put('districts/{id}', function (\Illuminate\Http\Request $request, $id) {
            $district = \App\Models\District::find($id);
            if (!$district) return response()->json(['message' => 'Not found'], 404);
            $validated = $request->validate(['code' => 'required|unique:districts,code,'.$id, 'name' => 'required', 'region_id' => 'required|exists:regions,id']);
            $district->update($validated);
            return response()->json(['message' => 'District updated', 'data' => $district]);
        });
        Route::delete('districts/{id}', function ($id) {
            $district = \App\Models\District::find($id);
            if (!$district) return response()->json(['message' => 'Not found'], 404);
            if ($district->schools()->count() > 0) return response()->json(['message' => 'Cannot delete district with schools'], 400);
            $district->delete();
            return response()->json(['message' => 'District deleted']);
        });

        // Schools
        Route::get('schools', function () {
             $page = request('page', 1);
             $pageSize = request('page_size', 10);
             $search = request('search', '');
             $regionId = request('region_id', '');
             $districtId = request('district_id', '');
             $query = \App\Models\School::with('region', 'district');
             if ($regionId) $query->where('region_id', $regionId);
             if ($districtId) $query->where('district_id', $districtId);
             if ($search) $query->where(function($q) use ($search) { $q->where('code', 'like', "%$search%")->orWhere('name', 'like', "%$search%"); });
             $total = $query->count();
             $schools = $query->skip(($page - 1) * $pageSize)->take($pageSize)->get();
             $data = $schools->map(function($s) {
                 return ['id' => $s->id, 'code' => $s->code, 'name' => $s->name, 'region_name' => $s->region->name ?? null, 'district_name' => $s->district->name ?? null, 'candidates_count' => \App\Models\Candidate::where('school_id', $s->id)->count(), 'status' => 'active'];
             });
             return response()->json(['data' => $data, 'pagination' => ['total_count' => $total, 'total_pages' => ceil($total / $pageSize), 'current_page' => $page, 'page_size' => $pageSize]]);
        });

        // Candidates
        Route::get('candidates', function () {
             $page = request('page', 1);
             $pageSize = request('page_size', 10);
             $search = request('search', '');
             $schoolId = request('school_id', '');
             $query = \App\Models\Candidate::with('school');
             if ($schoolId) $query->where('school_id', $schoolId);
             if ($search) $query->where(function($q) use ($search) { $q->where('full_name', 'like', "%$search%")->orWhere('candidate_id', 'like', "%$search%"); });
             $total = $query->count();
             $candidates = $query->skip(($page - 1) * $pageSize)->take($pageSize)->get();
             $data = $candidates->map(function($c) {
                 return ['id' => $c->id, 'candidate_id' => $c->candidate_id, 'full_name' => $c->full_name, 'gender' => $c->gender, 'school_name' => $c->school->name ?? null, 'status' => 'registered'];
             });
             return response()->json(['data' => $data, 'pagination' => ['total_count' => $total, 'total_pages' => ceil($total / $pageSize), 'current_page' => $page, 'page_size' => $pageSize]]);
        });

        Route::post('candidates', function (\Illuminate\Http\Request $request) {
            $validated = $request->validate([
                'school_id' => 'required|exists:schools,id',
                'candidate_id' => 'nullable|unique:candidates,candidate_id',
                'full_name' => 'required|string|max:255',
                'gender' => 'required|in:M,F',
                'combination' => 'nullable|string|max:255',
                'combination_id' => 'nullable|exists:combinations,id',
                'exam_type' => 'required|in:PSLE,CSEE,ACSEE',
                'candidate_type' => 'nullable|in:SCHOOL,PRIVATE',
                'status' => 'nullable|string|max:255',
            ]);
            
            if (empty($validated['candidate_id'])) {
                $count = \App\Models\Candidate::count() + 1;
                $validated['candidate_id'] = 'CAND-' . str_pad($count, 6, '0', STR_PAD_LEFT);
            }
            
            $candidate = \App\Models\Candidate::create($validated);
            $examType = \App\Models\ExamType::where('code', strtoupper((string) $validated['exam_type']))->first();
            $activeExamYear = \App\Models\ExamYear::where('is_active', true)->first();

            if ($examType && $activeExamYear) {
                \App\Models\CandidateExamRegistration::updateOrCreate(
                    ['candidate_id' => $candidate->id, 'exam_type_id' => $examType->id, 'exam_year_id' => $activeExamYear->id],
                    ['year' => (int) $activeExamYear->year_label, 'registration_number' => 'REG-' . uniqid(), 'is_active' => true, 'is_verified' => false]
                );
            }
            
            return response()->json(['message' => 'Candidate registered successfully', 'data' => $candidate->load('school')], 201);
        });

        Route::put('candidates/{id}', function (\Illuminate\Http\Request $request, $id) {
            $candidate = \App\Models\Candidate::findOrFail($id);
            $validated = $request->validate([
                'school_id' => 'required|exists:schools,id',
                'candidate_id' => 'nullable|unique:candidates,candidate_id,' . $candidate->id,
                'full_name' => 'required|string|max:255',
                'gender' => 'required|in:M,F',
                'combination' => 'nullable|string|max:255',
                'combination_id' => 'nullable|exists:combinations,id',
                'exam_type' => 'required|in:PSLE,CSEE,ACSEE',
                'candidate_type' => 'nullable|in:SCHOOL,PRIVATE',
                'status' => 'nullable|string|max:255',
            ]);
            
            $candidate->update($validated);
            return response()->json(['message' => 'Candidate updated successfully', 'data' => $candidate->load('school')]);
        });

        Route::delete('candidates/{id}', function ($id) {
            $candidate = \App\Models\Candidate::findOrFail($id);
            if (\App\Models\SubjectMarks::where('candidate_id', $candidate->id)->exists()) {
                return response()->json(['message' => "Cannot delete candidate with marks"], 409);
            }
            $candidate->delete();
            return response()->json(['message' => 'Candidate deleted successfully']);
        });

        // Exam Types APIs
        Route::get('exam-types', function () {

            $activeCodes = config('irms.active_exam_types', ['PSLE']);

            $examTypes = \App\Models\ExamType::whereIn('code', $activeCodes)->withCount('candidates')->get();

            

            $data = $examTypes->map(function($e) {

                return [

                    'id' => $e->id,

                    'name' => $e->name,

                    'code' => $e->code,

                    'level' => $e->level,

                    'description' => $e->description,

                    'candidates_count' => $e->candidates_count ?? 0

                ];

            });

            

            return response()->json(['data' => $data]);

        });

        Route::get('exam-types/{code}', function ($code) {

            $normalizedCode = strtoupper($code);

            if (in_array($normalizedCode, ['PSLE_MY', 'PSLE_G', 'PSLE'])) {

                $normalizedCode = 'PSLE';

            }

            $activeCodes = config('irms.active_exam_types', ['PSLE']);

            if (!in_array($normalizedCode, $activeCodes)) {

                return response()->json(['message' => 'Only PSLE is currently enabled.'], 403);

            }

            $examType = \App\Models\ExamType::where('code', $normalizedCode)->withCount('candidates')->firstOrFail();

            return response()->json(['data' => ['id' => $examType->id, 'name' => $examType->name, 'code' => $examType->code, 'level' => $examType->level, 'description' => $examType->description, 'candidates_count' => $examType->candidates_count ?? 0]]);

        });
        Route::get('exam-types/{code}/subjects', [ExamTypeController::class, 'getSubjects']);
        Route::get('exam-types/{code}/combinations', [ExamTypeController::class, 'getCombinations']);

        // Exam Years API
        Route::get('exam-years', function () {
            try {
                $years = \App\Models\ExamYear::orderByDesc('year_label')->get()->map(function($year) {
                    return ['id' => $year->id, 'year_label' => $year->year_label, 'is_active' => $year->is_active, 'is_locked' => $year->is_locked];
                });
                $activeYear = \App\Models\ExamYear::where('is_active', true)->first();
                return response()->json(['exam_years' => $years, 'active_year' => $activeYear ? ['id' => $activeYear->id, 'year_label' => $activeYear->year_label] : null]);
            } catch (\Exception $e) {
                return response()->json(['exam_years' => [], 'active_year' => null, 'error' => 'Unable to load exam years'], 200);
            }
        });
        Route::get('exam-years/with-acsee', function () {
            try {
                $acseeType = \App\Models\ExamType::where('code', 'ACSEE')->first();
                if (!$acseeType) return response()->json(['years' => []]);
                $years = \App\Models\ExamYear::query()->whereHas('candidateExamRegistrations', function($q) use ($acseeType) { $q->where('exam_type_id', $acseeType->id); })->orderBy('year_label', 'desc')->get();
                $data = $years->map(function($year) { return ['id' => $year->id, 'year_label' => $year->year_label, 'is_locked' => $year->is_locked]; });
                return response()->json(['years' => $data]);
            } catch (\Exception $e) {
                return response()->json(['years' => [], 'error' => 'Unable to load exam years'], 500);
            }
        });
        Route::get('exam-years/active', function () {
            try {
                $activeYear = \App\Models\ExamYear::active()->first();
                if (!$activeYear) return response()->json(['active_year' => null, 'message' => 'No active exam year set']);
                return response()->json(['active_year' => ['id' => $activeYear->id, 'year_label' => $activeYear->year_label, 'is_locked' => $activeYear->is_locked]]);
            } catch (\Exception $e) {
                return response()->json(['active_year' => null, 'error' => 'Unable to load active exam year'], 500);
            }
        });

    });
});

Route::middleware(['auth', 'user'])->prefix('user')->group(function () {
    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('user.dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/report-jobs', [\App\Http\Controllers\ReportJobController::class, 'store'])->name('report-jobs.store');
    Route::get('/report-jobs/{reportJob}', [\App\Http\Controllers\ReportJobController::class, 'show'])->name('report-jobs.show');
    Route::get('/report-jobs/{reportJob}/download', [\App\Http\Controllers\ReportJobController::class, 'download'])->name('report-jobs.download');
});

// Mock Portal Routes
Route::prefix('mock-portal')->name('mock-portal.')->group(function () {
    // Single entry point — the sharable link: https://irms.ac.tz/mock-portal
    Route::get('/', [App\Http\Controllers\MockPortalAuthController::class, 'welcome'])->name('welcome');
    Route::get('check-school/{code}', [App\Http\Controllers\MockPortalAuthController::class, 'checkSchool'])->name('check-school');
    Route::get('districts/{region}', [App\Http\Controllers\MockPortalAuthController::class, 'getDistricts'])->name('districts');
    Route::get('expired', [App\Http\Controllers\MockPortalAuthController::class, 'expired'])->name('expired');

    Route::middleware('guest')->group(function () {
        Route::get('login', [App\Http\Controllers\MockPortalAuthController::class, 'showLogin'])->name('login');
        Route::post('login', [App\Http\Controllers\MockPortalAuthController::class, 'login'])->name('login.submit');
        Route::get('register', [App\Http\Controllers\MockPortalAuthController::class, 'showRegister'])->name('register');
        Route::post('register', [App\Http\Controllers\MockPortalAuthController::class, 'register'])->name('register.submit');
        
        // Forgot Password
        Route::post('password/email', [App\Http\Controllers\MockPortalAuthController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::get('password/reset/{token}', [App\Http\Controllers\MockPortalAuthController::class, 'showResetForm'])->name('password.reset');
        Route::post('password/reset', [App\Http\Controllers\MockPortalAuthController::class, 'resetPassword'])->name('password.update');
    });
    Route::middleware('auth')->group(function () {
        Route::get('secretariat', [\App\Http\Controllers\ZonalControlCentreController::class, 'index'])->name('secretariat.dashboard');
        Route::get('rao', [App\Http\Controllers\MockPortalRaoController::class, 'index'])->name('rao.dashboard');
        Route::put('rao/candidate/{candidate}', [App\Http\Controllers\MockPortalRaoController::class, 'updateCandidate'])->name('rao.candidate.update');
        Route::post('rao/candidate/reject', [App\Http\Controllers\MockPortalRaoController::class, 'rejectCandidate'])->name('rao.candidate.reject');
        Route::delete('rao/candidate/{candidate}', [App\Http\Controllers\MockPortalRaoController::class, 'destroyCandidate'])->name('rao.candidate.destroy');
        Route::get('dao', [App\Http\Controllers\MockPortalDaoController::class, 'index'])->name('dao.dashboard');
        Route::get('dao/schools/report/pdf', [App\Http\Controllers\MockPortalDaoController::class, 'schoolsPdfReport'])->name('dao.schools.report.pdf');
        Route::get('dao/cal-zip', [App\Http\Controllers\MockPortalDaoController::class, 'downloadCalZip'])->name('dao.download-cal-zip');
        Route::post('dao/schools', [App\Http\Controllers\MockPortalDaoController::class, 'storeSchool'])->name('dao.schools.store');
        Route::put('dao/schools/{school}', [App\Http\Controllers\MockPortalDaoController::class, 'updateSchool'])->name('dao.schools.update');
        Route::delete('dao/schools/{school}', [App\Http\Controllers\MockPortalDaoController::class, 'destroySchool'])->name('dao.schools.destroy');
        Route::post('dao/reject', [App\Http\Controllers\MockPortalDaoController::class, 'rejectError'])->name('dao.reject');
        Route::get('school', [App\Http\Controllers\MockPortalHeadteacherController::class, 'index'])->name('school.dashboard');
        Route::get('school/candidate', [App\Http\Controllers\MockPortalHeadteacherController::class, 'candidate'])->name('school.candidate');
        Route::post('school/candidate/upload', [App\Http\Controllers\MockPortalHeadteacherController::class, 'uploadCandidates'])->name('school.candidate.upload');
        Route::post('school/candidate', [App\Http\Controllers\MockPortalHeadteacherController::class, 'storeCandidate'])->name('school.candidate.store');
        Route::put('school/candidate/{candidate}', [App\Http\Controllers\MockPortalHeadteacherController::class, 'updateCandidate'])->name('school.candidate.update');
        Route::delete('school/candidate/{candidate}', [App\Http\Controllers\MockPortalHeadteacherController::class, 'destroyCandidate'])->name('school.candidate.destroy');
        Route::get('school/candidate/template', [App\Http\Controllers\MockPortalHeadteacherController::class, 'downloadTemplate'])->name('school.candidate.template');
        Route::post('school/update-ownership', [App\Http\Controllers\MockPortalHeadteacherController::class, 'updateOwnership'])->name('school.update-ownership');
        Route::get('school/candidate/cal-report', [App\Http\Controllers\MockPortalHeadteacherController::class, 'calPdfReport'])->name('school.candidate.cal-report');
    });
});

// ==================== SUBJECT PANEL VERIFICATION PORTAL ====================
Route::prefix('subject-panel')->name('subject-panel.')->middleware(['auth', 'main-system'])->group(function () {
    Route::get('verification', [App\Http\Controllers\SubjectPanelVerificationController::class, 'index'])
        ->name('verification.index');
    Route::get('verification/{rawMark}', [App\Http\Controllers\SubjectPanelVerificationController::class, 'show'])
        ->name('verification.show');
    Route::post('verification/{rawMark}/verify', [App\Http\Controllers\SubjectPanelVerificationController::class, 'verify'])
        ->name('verification.verify');
    Route::post('verification/{rawMark}/return', [App\Http\Controllers\SubjectPanelVerificationController::class, 'returnForCorrection'])
        ->name('verification.return');
});

// Admin: Subject Panel Assignments (admin-only)
Route::prefix('admin/subject-panel-assignments')->name('admin.subject-panel-assignments.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\SubjectPanelAssignmentController::class, 'index'])
        ->name('index');
    Route::post('/', [App\Http\Controllers\Admin\SubjectPanelAssignmentController::class, 'store'])
        ->name('store');
    Route::delete('{subjectPanelAssignment}', [App\Http\Controllers\Admin\SubjectPanelAssignmentController::class, 'destroy'])
        ->name('destroy');
    Route::patch('{subjectPanelAssignment}/toggle', [App\Http\Controllers\Admin\SubjectPanelAssignmentController::class, 'toggleActive'])
        ->name('toggle');
});
