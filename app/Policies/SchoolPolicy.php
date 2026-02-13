<?php

namespace App\Policies;

use App\Models\User;
use App\Models\School;

class SchoolPolicy
{
    /**
     * Determine if user can view any schools.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can view a school.
     */
    public function view(User $user, School $school): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine if user can create schools.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can update a school.
     */
    public function update(User $user, School $school): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can delete a school.
     * Cannot delete if it has candidates.
     */
    public function delete(User $user, School $school): bool
    {
        if (!$user->isAdmin()) {
            return false;
        }

        // Cannot delete if has candidates
        if ($school->candidates()->exists()) {
            return false;
        }

        return true;
    }
}
