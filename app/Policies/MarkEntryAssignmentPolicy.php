<?php

namespace App\Policies;

use App\Models\MarkEntryAssignment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MarkEntryAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MarkEntryAssignment $markEntryAssignment): bool
    {
        if ($user->isAdmin()) return true;
        if ($user->isRegionalOfficer()) {
            return $markEntryAssignment->region_id === ($user->region_id ?? $user->getRegionId());
        }
        return $markEntryAssignment->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isRegionalOfficer();
    }

    public function update(User $user, MarkEntryAssignment $markEntryAssignment): bool
    {
        if ($user->isAdmin()) return true;
        if ($user->isRegionalOfficer()) {
            return $markEntryAssignment->region_id === ($user->region_id ?? $user->getRegionId());
        }
        return false;
    }

    public function delete(User $user, MarkEntryAssignment $markEntryAssignment): bool
    {
        if ($user->isAdmin()) return true;
        if ($user->isRegionalOfficer()) {
            return $markEntryAssignment->region_id === ($user->region_id ?? $user->getRegionId());
        }
        return false;
    }

    public function restore(User $user, MarkEntryAssignment $markEntryAssignment): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, MarkEntryAssignment $markEntryAssignment): bool
    {
        return $user->isAdmin();
    }
}
