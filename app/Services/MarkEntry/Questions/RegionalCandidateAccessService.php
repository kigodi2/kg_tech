<?php

namespace App\Services\MarkEntry\Questions;

use App\Models\Candidate;
use App\Models\User;
use App\Models\UserScope;

class RegionalCandidateAccessService
{
    public function canAccessCandidate(User $user, Candidate $candidate): bool
    {
        if ($this->hasGlobalAccess($user)) {
            return true;
        }

        $scope = $this->resolveScope($user);
        $school = $candidate->school;

        if (!$scope || !$school) {
            return false;
        }

        return match ($scope['type']) {
            UserScope::SCOPE_REGION => (int) ($school->region_id ?? 0) === (int) $scope['id'],
            UserScope::SCOPE_DISTRICT => (int) ($school->district_id ?? 0) === (int) $scope['id'],
            UserScope::SCOPE_SCHOOL => (int) ($candidate->school_id ?? 0) === (int) $scope['id'],
            default => false,
        };
    }

    public function hasGlobalAccess(User $user): bool
    {
        return in_array($user->roleCode(), ['admin', 'super_admin'], true);
    }

    public function canEditSubmitted(User $user): bool
    {
        return $this->hasGlobalAccess($user);
    }

    public function resolveScope(User $user): ?array
    {
        $scopeType = $user->getScopeType();
        $scopeId = $user->getScopeId();

        if ($scopeType && $scopeId) {
            return ['type' => $scopeType, 'id' => (int) $scopeId];
        }

        if (!empty($user->region_id)) {
            return ['type' => UserScope::SCOPE_REGION, 'id' => (int) $user->region_id];
        }

        if (!empty($user->district_id)) {
            return ['type' => UserScope::SCOPE_DISTRICT, 'id' => (int) $user->district_id];
        }

        if (!empty($user->school_id)) {
            return ['type' => UserScope::SCOPE_SCHOOL, 'id' => (int) $user->school_id];
        }

        return null;
    }
}
