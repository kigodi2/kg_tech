<?php

namespace App\Policies;

use App\Models\RestoreAuditLog;
use App\Models\User;

/**
 * RestoreAuditLogPolicy
 * 
 * Authorization policy for viewing restore audit logs.
 * Based on role hierarchy and scope (regional/district).
 * 
 * This policy ensures operators can only see audit logs relevant to their jurisdiction.
 */
class RestoreAuditLogPolicy
{
    /**
     * Super Admin: See all audit logs
     * Regional Admin: See only their region's restores
     * District Admin: See only their district's restores
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * View individual audit log entry
     */
    public function view(User $user, RestoreAuditLog $auditLog): bool
    {
        // Super admin can view all
        if ($user->role?->code === 'super_admin') {
            return true;
        }

        // Regional admin can view their region's logs
        if ($user->role?->code === 'regional_admin') {
            return $auditLog->region_id === $user->getRegionId();
        }

        // District admin can view their district's logs
        if ($user->role?->code === 'district_admin') {
            return $auditLog->district_id === $user->getDistrictId();
        }

        return false;
    }

    /**
     * Audit logs are immutable - no creation allowed
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Audit logs are immutable - no updates allowed
     */
    public function update(User $user, RestoreAuditLog $auditLog): bool
    {
        return false;
    }

    /**
     * Audit logs are immutable - no deletion allowed
     */
    public function delete(User $user, RestoreAuditLog $auditLog): bool
    {
        return false;
    }
}
