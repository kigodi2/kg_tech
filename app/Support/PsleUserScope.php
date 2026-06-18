<?php

namespace App\Support;

use App\Models\District;
use App\Models\DistrictCouncil;
use App\Models\School;
use App\Models\User;
use App\Models\UserScope;

class PsleUserScope
{
    public static function roleLabels(User $user): array
    {
        return array_values(array_filter([
            $user->role?->code,
            $user->role?->name,
            $user->portal_role,
            $user->is_admin ? 'is_admin' : null,
        ]));
    }

    public static function hasGlobalAccess(User $user): bool
    {
        $roleSignals = array_map(
            fn ($value) => strtolower(trim((string) $value)),
            array_filter([$user->role?->code, $user->role?->name, $user->portal_role])
        );

        if (array_intersect($roleSignals, ['admin', 'administrator', 'super_admin', 'super admin', 'system_admin', 'system admin'])) {
            return true;
        }

        $hasAssignedScope = $user->region_id || $user->district_council_id || $user->school_id || $user->scope;

        return (bool) $user->is_admin && !$hasAssignedScope;
    }

    public static function regionId(User $user): ?int
    {
        if ($user->region_id) {
            return (int) $user->region_id;
        }

        if ($user->scope?->scope_type === UserScope::SCOPE_REGION) {
            return (int) $user->scope->scope_id;
        }

        if ($user->district_council_id) {
            return DistrictCouncil::whereKey($user->district_council_id)->value('region_id');
        }

        if ($user->school_id) {
            $school = School::with('council')->find($user->school_id);
            return $school ? (int) ($school->region_id ?: $school->council?->region_id) : null;
        }

        if ($user->scope?->scope_type === UserScope::SCOPE_DISTRICT) {
            $scopeId = (int) $user->scope->scope_id;
            return DistrictCouncil::whereKey($scopeId)->value('region_id')
                ?: District::whereKey($scopeId)->value('region_id');
        }

        return null;
    }

    public static function councilId(User $user): ?int
    {
        if ($user->district_council_id) {
            return (int) $user->district_council_id;
        }

        if ($user->school_id) {
            return School::whereKey($user->school_id)->value('council_id');
        }

        if ($user->scope?->scope_type === UserScope::SCOPE_DISTRICT) {
            $scopeId = (int) $user->scope->scope_id;
            if (DistrictCouncil::whereKey($scopeId)->exists()) {
                return $scopeId;
            }
        }

        return null;
    }

    public static function schoolId(User $user): ?int
    {
        if ($user->school_id) {
            return (int) $user->school_id;
        }

        if ($user->scope?->scope_type === UserScope::SCOPE_SCHOOL) {
            return (int) $user->scope->scope_id;
        }

        return null;
    }

    public static function constraints(User $user): array
    {
        if (self::hasGlobalAccess($user)) {
            return ['scope' => 'all'];
        }

        if ($schoolId = self::schoolId($user)) {
            return ['scope' => 'school', 'school_id' => $schoolId];
        }

        if ($councilId = self::councilId($user)) {
            return ['scope' => 'council', 'council_id' => $councilId];
        }

        if ($regionId = self::regionId($user)) {
            return ['scope' => 'region', 'region_id' => $regionId];
        }

        return ['scope' => 'none'];
    }

    public static function applyToSchools($query, User $user)
    {
        if (self::hasGlobalAccess($user)) {
            return $query;
        }

        if ($schoolId = self::schoolId($user)) {
            return $query->where('id', $schoolId);
        }

        if ($councilId = self::councilId($user)) {
            return $query->where('council_id', $councilId);
        }

        if ($regionId = self::regionId($user)) {
            return $query->where(function ($scopeQuery) use ($regionId) {
                $scopeQuery->where('region_id', $regionId)
                    ->orWhereHas('council', fn ($councilQuery) => $councilQuery->where('region_id', $regionId));
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public static function applyToCandidateSchools($query, User $user)
    {
        if (self::hasGlobalAccess($user)) {
            return $query;
        }

        if ($schoolId = self::schoolId($user)) {
            return $query->where('school_id', $schoolId);
        }

        return $query->whereHas('school', function ($schoolQuery) use ($user) {
            self::applyToSchools($schoolQuery, $user);
        });
    }
}
