<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Region;
use App\Models\District;
use App\Models\BackupLog;

/**
 * HardenedRestorePolicy
 * 
 * Role-aware, audit-compliant authorization for restore operations.
 * Implements NECTA-style examination data governance.
 * 
 * Role hierarchy and permissions:
 * - Super Admin: Can restore ANY backup (full system, regions, districts)
 * - Regional Admin: Can restore backups for their region ONLY
 * - District Admin: Can restore backups for their district ONLY
 * - Other roles: Cannot restore
 */
class HardenedRestorePolicy
{
    /**
     * Can user initiate ANY full system restore?
     * Only Super Admins
     */
    public function restoreFullSystem(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Can user restore a specific region?
     * Super Admins can restore any region.
     * Regional Admins can restore their own region only.
     */
    public function restoreRegion(User $user, Region $region): bool
    {
        // Super admin: yes
        if ($user->isAdmin()) {
            return true;
        }

        // Regional officer or regional admin: check if scoped to this region
        if ($user->isRegionalOfficer() || $this->isRegionalAdmin($user)) {
            return $user->getScopeType() === 'region' && $user->getScopeId() === $region->id;
        }

        return false;
    }

    /**
     * Can user restore a specific district?
     * Super Admins can restore any district.
     * Regional Admins can restore districts in their region.
     * District Admins can restore their own district only.
     */
    public function restoreDistrict(User $user, District $district): bool
    {
        // Super admin: yes
        if ($user->isAdmin()) {
            return true;
        }

        // Regional admin: can restore if district is in their region
        if ($this->isRegionalAdmin($user)) {
            return $user->getScopeType() === 'region' 
                && $district->region_id === $user->getScopeId();
        }

        // District admin: can restore only their district
        if ($this->isDistrictAdmin($user)) {
            return $user->getScopeType() === 'district' 
                && $user->getScopeId() === $district->id;
        }

        return false;
    }

    /**
     * Can user view restore audit logs?
     * Super Admins: see all
     * Regional Admins: see only their region
     * District Admins: see only their district
     * Others: no
     */
    public function viewRestoreAuditLogs(User $user): bool
    {
        return $user->isAdmin() 
            || $this->isRegionalAdmin($user) 
            || $this->isDistrictAdmin($user);
    }

    /**
     * Can user download restore audit reports?
     * Same as viewing audit logs
     */
    public function downloadRestoreAuditReport(User $user): bool
    {
        return $this->viewRestoreAuditLogs($user);
    }

    /**
     * Can user create a pre-restore snapshot?
     * Same permissions as restore
     */
    public function createSnapshot(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Can user recover from quarantine?
     * Only Super Admins (critical operation)
     */
    public function recoverFromQuarantine(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Is user a regional administrator?
     * Check for regional officer role with regional scope
     */
    protected function isRegionalAdmin(User $user): bool
    {
        return $user->isRegionalOfficer() 
            && $user->getScopeType() === 'region';
    }

    /**
     * Is user a district administrator?
     * Check for district supervisor role with district scope
     */
    protected function isDistrictAdmin(User $user): bool
    {
        return ($user->isDistrictSupervisor() || $user->isDistrictDataEntryOfficer())
            && $user->getScopeType() === 'district';
    }
}
