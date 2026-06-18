<?php

namespace App\Policies;

use App\Models\MarkingCentre;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MarkingCentrePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isRegionalOfficer();
    }

    public function view(User $user, MarkingCentre $markingCentre): bool
    {
        if ($user->isAdmin()) return true;
        if ($user->isRegionalOfficer()) {
            return $markingCentre->region_id === ($user->region_id ?? $user->getRegionId());
        }
        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, MarkingCentre $markingCentre): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, MarkingCentre $markingCentre): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, MarkingCentre $markingCentre): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, MarkingCentre $markingCentre): bool
    {
        return $user->isAdmin();
    }
}
