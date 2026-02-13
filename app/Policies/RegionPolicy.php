<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Region;

class RegionPolicy
{
    /**
     * Determine if user can view any regions.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can view a region.
     */
    public function view(User $user, Region $region): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine if user can create regions.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can update a region.
     */
    public function update(User $user, Region $region): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can delete a region.
     * Cannot delete if it has schools or candidates.
     */
    public function delete(User $user, Region $region): bool
    {
        if (!$user->isAdmin()) {
            return false;
        }

        // Cannot delete if has schools
        if ($region->schools()->exists()) {
            return false;
        }

        return true;
    }
}
