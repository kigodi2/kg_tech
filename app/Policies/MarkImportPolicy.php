<?php

namespace App\Policies;

use App\Models\BulkImport;
use App\Models\District;
use App\Models\User;
use App\Models\UserScope;

class MarkImportPolicy
{
    /**
     * Only district data entry officers can import marks
     */
    public function create(User $user): bool
    {
        // Must be active
        if (!$user->isActive()) {
            return false;
        }

        // Must be district data entry officer or admin
        if (!$user->isDistrictDataEntryOfficer() && !$user->isAdmin()) {
            return false;
        }

        // Admin doesn't need scope, but data entry officer does
        if (!$user->isAdmin()) {
            if ($user->getScopeType() !== UserScope::SCOPE_DISTRICT) {
                return false;
            }
        }

        return true;
    }

    /**
     * Can only import to own district
     * 
     * @param User $user
     * @param mixed $model - Ignored, used for Laravel policy routing
     * @param int $districtId - The district being imported to
     */
    public function uploadForDistrict(User $user, $model, int $districtId): bool
    {
        // Admin can import to any district (no active check needed for admin)
        if ($user->isAdmin()) {
            return true;
        }

        // Data entry officer can only import to own district
        if (!$this->create($user)) {
            return false;
        }

        // Verify the district exists
        $district = District::find($districtId);
        if (!$district) {
            return false;
        }

        // Must be importing for their own district
        return $user->getDistrictId() === $districtId;
    }

    /**
     * Can view import history for own district
     */
    public function viewForDistrict(User $user, int $districtId): bool
    {
        if (!$user->isActive()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true; // Admin can see all
        }

        // Regional officer can see all districts in region
        if ($user->isRegionalOfficer()) {
            $region = \App\Models\Region::find($user->getRegionId());
            return $region && $region->districts()->where('id', $districtId)->exists();
        }

        // District supervisor can only see own district
        if ($user->isDistrictSupervisor()) {
            return $user->getDistrictId() === $districtId;
        }

        // District data entry officer can see own district
        if ($user->isDistrictDataEntryOfficer()) {
            return $user->getDistrictId() === $districtId;
        }

        return false;
    }
}
