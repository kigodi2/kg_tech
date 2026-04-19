<?php

namespace App\Policies;

use App\Models\CandidateResult;
use App\Models\User;

/**
 * Results Policy
 * 
 * Authorizes access to ACSEE results based on:
 * - User role
 * - User scope (region, district, school)
 * - Result publication status
 */
class ResultsPolicy
{
    private function roleCode(User $user): ?string
    {
        return $user->role->code ?? null;
    }

    private function isReadRole(User $user): bool
    {
        return in_array($this->roleCode($user), [
            'super_admin',
            'admin',
            'regional_admin',
            'regional_officer',
            'district_admin',
            'district_supervisor',
            'district_data_entry_officer',
            'school_user',
            'school_registrar',
        ], true);
    }

    /**
     * Allow viewing results if user has appropriate role
     */
    public function viewResults(User $user): bool
    {
        return $this->isReadRole($user);
    }

    /**
     * Allow viewing specific result based on scope
     */
    public function viewResult(User $user, CandidateResult $result): bool
    {
        // Result must be published
        if (!$result->is_published) {
            return false;
        }

        $roleCode = $this->roleCode($user);

        // Super admin can view all
        if ($roleCode === 'super_admin') {
            return true;
        }

        // Get result's school
        $school = $result->candidate->school;
        if (!$school) {
            return false;
        }

        // Regional admin can view their region only
        if (in_array($roleCode, ['regional_admin', 'regional_officer'], true)) {
            return $school->region_id === ($user->scope->scope_id ?? null);
        }

        // District admin can view their district only
        if (in_array($roleCode, ['district_admin', 'district_supervisor', 'district_data_entry_officer'], true)) {
            return $school->district_id === ($user->scope->scope_id ?? null);
        }

        // School user can view their school only
        if (in_array($roleCode, ['school_user', 'school_registrar'], true)) {
            return $school->id === ($user->school_id ?? $user->scope->scope_id ?? null);
        }

        return false;
    }

    /**
     * Allow exporting results if user has appropriate role
     */
    public function exportResults(User $user): bool
    {
        return $this->isReadRole($user);
    }

    /**
     * Publish/final-lock results within authorized scope.
     */
    public function publishLock(User $user): bool
    {
        return $this->isReadRole($user);
    }

    /**
     * Admin-only unpublish/unlock action.
     */
    public function adminUnlock(User $user): bool
    {
        return in_array($this->roleCode($user), ['admin', 'super_admin'], true);
    }
}
