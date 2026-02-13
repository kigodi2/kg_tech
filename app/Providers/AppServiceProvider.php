<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\ExamYear;
use App\Models\BulkImport;
use App\Models\School;
use App\Policies\ExamYearPolicy;
use App\Policies\BulkImportPolicy;
use App\Services\MarkImport\DistrictBulkImportOrchestrator;
use App\Services\MarkImport\DistrictManifestValidator;
use App\Services\SQLiteBackupService;
use App\Services\HardenedRestoreService;
use App\Observers\SchoolObserver;
use App\Observers\CandidateExamRegistrationObserver;
use App\Models\CandidateExamRegistration;
use App\Http\View\Composers\ResultsComposer;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register district bulk import services
        $this->app->singleton(DistrictBulkImportOrchestrator::class, function ($app) {
            return new DistrictBulkImportOrchestrator(
                $app->make(\App\Services\MarkImport\ZipSignerService::class),
                $app->make(DistrictManifestValidator::class)
            );
        });

        $this->app->singleton(DistrictManifestValidator::class, function ($app) {
            return new DistrictManifestValidator();
        });

        // Register hardened restore service
        $this->app->singleton(HardenedRestoreService::class, function ($app) {
            return new HardenedRestoreService(
                $app->make(SQLiteBackupService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        \Illuminate\Support\Facades\Gate::policy(ExamYear::class, ExamYearPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(BulkImport::class, BulkImportPolicy::class);

        // Register model observers
        School::observe(SchoolObserver::class);
        CandidateExamRegistration::observe(CandidateExamRegistrationObserver::class);

        // Register view composers
        View::composer('hierarchy.school-results', ResultsComposer::class);
    }
}
