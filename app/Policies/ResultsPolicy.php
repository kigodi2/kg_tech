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
    /**
     * Allow viewing results if user has appropriate role
     */
    public function viewResults(User $user): bool
    {
        return $user->role && in_array($user->role->code, [
            'super_admin',
            'regional_admin',
            'district_admin',
            'school_user',
        ]);
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

        $roleCode = $user->role->code ?? null;

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
        if ($roleCode === 'regional_admin') {
            return $school->region_id === $user->scope->scope_id;
        }

        // District admin can view their district only
        if ($roleCode === 'district_admin') {
            return $school->district_id === $user->scope->scope_id;
        }

        // School user can view their school only
        if ($roleCode === 'school_user') {
            return $school->id === $user->school_id;
        }

        return false;
    }

    /**
     * Allow exporting results if user has appropriate role
     */
    public function exportResults(User $user): bool
    {
        return $user->role && in_array($user->role->code, [
            'super_admin',
            'regional_admin',
            'district_admin',
            'school_user',
        ]);
    }
}
