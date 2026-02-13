<?php

namespace App\Policies;

use App\Models\Candidate;
use App\Models\School;
use App\Models\User;
use App\Models\UserScope;

class CandidateRegistrationPolicy
{
    /**
     * Only school registrars can register candidates
     */
    public function register(User $user): bool
    {
        // Must be active
        if (!$user->isActive()) {
            return false;
        }

        // Must be school registrar or admin
        if (!$user->isSchoolRegistrar() && !$user->isAdmin()) {
            return false;
        }

        // Admin doesn't need scope, but registrar does
        if (!$user->isAdmin()) {
            if ($user->getScopeType() !== UserScope::SCOPE_SCHOOL) {
                return false;
            }
        }

        return true;
    }

    /**
     * Can only register candidates at own school
     * 
     * @param User $user
     * @param mixed $model - Ignored, used for Laravel policy routing
     * @param int $schoolId - The school where candidates are being registered
     */
    public function registerForSchool(User $user, $model, int $schoolId): bool
    {
        // Admin can register at any school
        if ($user->isAdmin()) {
            return $user->isActive();
        }

        // Registrar can only register at own school
        if (!$this->register($user)) {
            return false;
        }

        // Verify the school exists
        $school = School::find($schoolId);
        if (!$school) {
            return false;
        }

        // Must be registering for their own school
        return $user->getSchoolId() === $schoolId;
    }
}
