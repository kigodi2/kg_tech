<?php

namespace App\Policies;

use App\Models\User;
use App\Models\School;
use App\Models\ExamYear;
use Illuminate\Support\Facades\Log;

/**
 * BulkCsvExportPolicy
 *
 * Authorization rules for bulk CSV export operations.
 *
 * Access Matrix:
 * - Admin: any school
 * - Regional Officer: schools in their region
 * - School User: own school only
 */
class BulkCsvExportPolicy
{
    /**
     * Determine if user can download bulk CSV for a school
     *
     * @param User $user
     * @param int $schoolId
     * @return bool
     */
    public function downloadBulkCsv(User $user, int $schoolId): bool
    {
        Log::info('BulkCsvExportPolicy.downloadBulkCsv check', [
            'user_id' => $user->id,
            'user_role' => $user->role?->code,
            'user_school_id' => $user->school_id,
            'user_region_id' => $user->region_id,
            'requested_school_id' => $schoolId,
        ]);

        // Admin can download for any school
        if ($user->isAdmin()) {
            Log::info('Access granted: user is admin');
            return true;
        }

        $school = School::findOrFail($schoolId);

        // Regional officer can download for schools in their region
        if ($user->isRegionalOfficer() && $user->region_id) {
            $schoolRegionId = $school->district?->region_id;
            Log::info('Regional officer check', [
                'school_region_id' => $schoolRegionId,
                'user_region_id' => $user->region_id,
                'match' => $schoolRegionId == $user->region_id,
            ]);
            return $schoolRegionId == $user->region_id;
        }

        // School registrar can download for their own school
        if ($user->hasRole(\App\Models\Role::CODE_SCHOOL_REGISTRAR) && $user->school_id) {
            $allowed = $user->school_id == $schoolId;
            Log::info('School registrar check', [
                'user_school_id' => $user->school_id,
                'requested_school_id' => $schoolId,
                'allowed' => $allowed,
            ]);
            return $allowed;
        }

        Log::info('Access denied: user role not authorized');
        return false;
    }
}
