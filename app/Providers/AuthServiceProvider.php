<?php

namespace App\Providers;

use App\Models\BulkImport;
use App\Models\CandidateResult;
use App\Models\Combination;
use App\Models\DistrictCouncil;
use App\Models\GradingProfile;
use App\Models\GradingRule;
use App\Models\RestoreAuditLog;
use App\Models\MarkImportBatch;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use App\Policies\BulkImportPolicy;
use App\Policies\CombinationPolicy;
use App\Policies\DistrictCouncilPolicy;
use App\Policies\GradingProfilePolicy;
use App\Policies\GradingRulePolicy;
use App\Policies\MarkImportPolicy;
use App\Policies\RestoreAuditLogPolicy;
use App\Policies\ResultsPolicy;
use App\Policies\RolePolicy;
use App\Policies\SubjectPolicy;
use App\Policies\UserPolicy;
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
        Subject::class => SubjectPolicy::class,
        Combination::class => CombinationPolicy::class,
        Role::class => RolePolicy::class,
        DistrictCouncil::class => DistrictCouncilPolicy::class,
        GradingProfile::class => GradingProfilePolicy::class,
        GradingRule::class => GradingRulePolicy::class,
        User::class => UserPolicy::class,
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
            return in_array($user->roleCode(), ['teacher', 'school_registrar', 'admin']);
        });

        Gate::define('mark-entry.moderate', function (User $user) {
            return in_array($user->roleCode(), ['school_hod', 'district_supervisor', 'admin']);
        });

        Gate::define('mark-entry.lock', function (User $user) {
            return in_array($user->roleCode(), ['school_hod', 'district_supervisor', 'admin']);
        });

        Gate::define('mark-entry.unlock', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('mark-entry.audit', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('mark-entry.report', function (User $user) {
            return in_array($user->roleCode(), ['school_hod', 'district_supervisor', 'admin']);
        });

        Gate::define('mark-entry.admin', function (User $user) {
            return $user->isAdmin();
        });

        // ACSEE granular permissions (checked against acsee_role_permissions table)
        $acseePermissions = [
            'acsee.upload_marks', 'acsee.view_moderation', 'acsee.approve_marks',
            'acsee.reject_marks', 'acsee.submit_marks', 'acsee.lock_marks',
            'acsee.export_reports', 'acsee.view_analytics',
            'acsee.admin.configuration', 'acsee.admin.permissions',
            'acsee.admin.batch_management', 'acsee.admin.system_logs',
            'acsee.admin.unlock',
        ];

        foreach ($acseePermissions as $perm) {
            Gate::define($perm, function (User $user) use ($perm) {
                // Admin always has access
                if ($user->isAdmin()) {
                    return true;
                }
                // Check granular permission table
                return \App\Models\AcseeRolePermission::roleHas($user->role_id, $perm);
            });
        }
    }
}
