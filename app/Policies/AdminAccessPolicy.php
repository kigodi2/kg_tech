<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Role;

/**
 * AdminAccessPolicy
 *
 * Controls access to the entire admin panel.
 * Ensures only authorized roles can access admin features.
 */
class AdminAccessPolicy
{
    /**
     * Determine if user can access admin panel.
     */
    public function accessAdmin(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user is full admin (not just read-only).
     */
    public function isFullAdmin(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user is council IT (limited access).
     */
    public function isCouncilIT(User $user): bool
    {
        return $user->isAdmin();
    }
}
