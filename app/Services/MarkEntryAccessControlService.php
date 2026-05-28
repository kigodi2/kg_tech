<?php

namespace App\Services;

use App\Models\User;

class MarkEntryAccessControlService
{
    /**
     * Determine if a user is a Mark Entry Officer.
     *
     * @param User|null $user
     * @return bool
     */
    public static function isMarkEntryOfficer(?User $user): bool
    {
        return $user !== null && $user->isMarkEntryOfficer();
    }
}
