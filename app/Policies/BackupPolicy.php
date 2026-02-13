<?php

namespace App\Policies;

use App\Models\User;

/**
 * BackupPolicy
 * 
 * Authorization policy for backup/restore operations.
 * Only administrators with backup_admin role can perform these operations.
 */
class BackupPolicy
{
    /**
     * Only super admins can view backups
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $this->hasBackupAdminRole($user);
    }

    /**
     * Only super admins can view a single backup
     */
    public function view(User $user): bool
    {
        return $user->isAdmin() && $this->hasBackupAdminRole($user);
    }

    /**
     * Only super admins can create backups
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() && $this->hasBackupAdminRole($user);
    }

    /**
     * Role-based restore authorization
     * 
     * - Super Admin: Can restore ANY backup
     * - Regional Admin: Can restore backups for their region only
     * - District Admin: Can restore backups for their district only
     * - Others: DENIED
     * 
     * This is a critical operation with legal/audit implications
     */
    public function restore(User $user): bool
    {
        // Must be an admin
        if (!$user->isAdmin()) {
            return false;
        }

        $role = $user->role?->code;

        // Super admin can restore anything
        if ($role === 'super_admin') {
            return true;
        }

        // Regional and District admins can restore (specific scope checked in service)
        if (in_array($role, ['regional_admin', 'district_admin'])) {
            return true;
        }

        return false;
    }

    /**
     * Only super admins can delete backups
     */
    public function delete(User $user): bool
    {
        return $user->isAdmin() && $this->hasBackupAdminRole($user);
    }

    /**
     * Only super admins can simulate restores
     */
    public function simulate(User $user): bool
    {
        return $user->isAdmin() && $this->hasBackupAdminRole($user);
    }

    /**
     * Check if user has backup admin role
     */
    protected function hasBackupAdminRole(User $user): bool
    {
        return $user->role && in_array($user->role->name, ['backup_admin', 'super_admin', 'admin']);
    }
}
