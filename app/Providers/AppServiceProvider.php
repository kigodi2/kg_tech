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
use App\Observers\CandidateResultObserver;
use App\Models\CandidateExamRegistration;
use App\Models\CandidateResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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
        \Illuminate\Support\Facades\Route::pattern('record', '[0-9a-fA-F\-]+');

        // Force HTTPS on the production server (irms.ac.tz)
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
            // $this->preventUnsupportedProductionDatabase();
            $this->registerSlowQueryMonitoring();
        }

        // Register policies
        \Illuminate\Support\Facades\Gate::policy(ExamYear::class, ExamYearPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(BulkImport::class, BulkImportPolicy::class);

        // Register model observers
        School::observe(SchoolObserver::class);
        CandidateExamRegistration::observe(CandidateExamRegistrationObserver::class);
        CandidateResult::observe(CandidateResultObserver::class);

        View::composer('results.acsee.*', function ($view) {
            $isPsle = request()->routeIs('results.psle.*');

            $view->with([
                'resultsRoutePrefix' => $isPsle ? 'results.psle' : 'results.acsee',
                'resultsModuleLabel' => $isPsle ? 'PSLE' : 'ACSEE',
                'resultsModuleTitle' => $isPsle ? 'PSLE Results' : 'ACSEE Results',
            ]);
        });
    }

    private function preventUnsupportedProductionDatabase(): void
    {
        $connection = (string) config('database.default');
        $driver = (string) config("database.connections.{$connection}.driver");

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            $envVal = env('DB_CONNECTION');
            throw new RuntimeException("Production database must be MySQL/MariaDB. Refusing to run with driver [{$driver}] on connection [{$connection}]. env('DB_CONNECTION') is [{$envVal}].");
        }

        if (config('cache.default') !== 'redis') {
            Log::warning('Production cache store is not Redis. Redis is recommended for concurrent mark entry.', [
                'cache_store' => config('cache.default'),
            ]);
        }

        if (config('session.driver') !== 'redis') {
            Log::warning('Production session driver is not Redis. Redis is recommended for concurrent mark entry.', [
                'session_driver' => config('session.driver'),
            ]);
        }

        if (config('queue.default') !== 'redis') {
            Log::warning('Production queue connection is not Redis. Redis is recommended for concurrent mark entry.', [
                'queue_connection' => config('queue.default'),
            ]);
        }
    }

    private function registerSlowQueryMonitoring(): void
    {
        if (! filter_var(env('LOG_SLOW_QUERIES', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $thresholdMs = max(1, (int) env('SLOW_QUERY_THRESHOLD_MS', 500));

        DB::listen(function ($query) use ($thresholdMs): void {
            if ($query->time < $thresholdMs) {
                return;
            }

            $request = request();
            $route = $request?->route();

            Log::warning('[slow-query]', [
                'duration_ms' => (float) $query->time,
                'connection' => $query->connectionName,
                'route' => $route?->getName(),
                'url' => $request?->fullUrl(),
                'user_id' => auth()->id(),
                'sql' => $query->sql,
            ]);
        });
    }
}
