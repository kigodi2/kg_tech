<?php

namespace App\Policies;

use App\Models\User;
use App\Models\District;

class DistrictPolicy
{
    /**
     * Determine if user can view any districts.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can view a district.
     */
    public function view(User $user, District $district): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine if user can create districts.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can update a district.
     */
    public function update(User $user, District $district): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can delete a district.
     * Cannot delete if it has schools or candidates.
     */
    public function delete(User $user, District $district): bool
    {
        if (!$user->isAdmin()) {
            return false;
        }

        // Cannot delete if has schools
        if ($district->schools()->exists()) {
            return false;
        }

        return true;
    }
}
