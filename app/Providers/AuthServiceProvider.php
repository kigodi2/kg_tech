<?php

namespace App\Providers;

use App\Models\BulkImport;
use App\Models\CandidateResult;
use App\Models\RestoreAuditLog;
use App\Models\MarkImportBatch;
use App\Models\User;
use App\Policies\BulkImportPolicy;
use App\Policies\MarkImportPolicy;
use App\Policies\RestoreAuditLogPolicy;
use App\Policies\ResultsPolicy;
use App\Policies\BulkCsvExportPolicy;
use App\Policies\MarkImportBatchPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        BulkImport::class => BulkImportPolicy::class,
        RestoreAuditLog::class => RestoreAuditLogPolicy::class,
        CandidateResult::class => ResultsPolicy::class,
        MarkImportBatch::class => MarkImportBatchPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Register mark import authorization gate
        Gate::define('uploadMarkForDistrict', function ($user, $districtId) {
            return (new MarkImportPolicy())->uploadForDistrict($user, BulkImport::class, $districtId);
        });

        // Register bulk CSV export gate
        Gate::define('downloadBulkCsv', function ($user, $schoolId) {
            return (new BulkCsvExportPolicy())->downloadBulkCsv($user, $schoolId);
        });

        // Mark Entry Lifecycle Permissions
        Gate::define('mark-entry.upload', function (User $user) {
            return in_array($user->role->code ?? null, ['teacher', 'school_registrar', 'admin']);
        });

        Gate::define('mark-entry.moderate', function (User $user) {
            return in_array($user->role->code ?? null, ['school_hod', 'district_supervisor', 'admin']);
        });

        Gate::define('mark-entry.lock', function (User $user) {
            return in_array($user->role->code ?? null, ['school_hod', 'district_supervisor', 'admin']);
        });

        Gate::define('mark-entry.unlock', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('mark-entry.audit', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('mark-entry.admin', function (User $user) {
            return $user->isAdmin();
        });
    }
}
