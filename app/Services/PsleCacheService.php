<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class PsleCacheService
{
    /**
     * Get the versioned cache key for dashboard summary stats
     */
    public static function summaryKey($examYearId, $userId, $scopeHash): string
    {
        $version = self::getVersion();
        return "psle:dashboard:stats:v{$version}:{$examYearId}:{$userId}:{$scopeHash}";
    }

    /**
     * Get the versioned cache key for school lists per council
     */
    public static function schoolsKey($examYearId, $districtId, $scopeHash): string
    {
        $version = self::getVersion();
        return "psle:schools:v{$version}:{$examYearId}:{$districtId}:{$scopeHash}";
    }

    /**
     * Get the current cache version number
     */
    public static function getVersion(): int
    {
        return (int) Cache::get('psle:dashboard:stats:version', 1);
    }

    /**
     * Invalidate the cache by incrementing the cache version number
     */
    public static function incrementVersion(): void
    {
        $version = self::getVersion();
        Cache::put('psle:dashboard:stats:version', $version + 1, now()->addDays(30));
    }

    /**
     * Generate a unique MD5 hash for the user scope constraints
     */
    public static function scopeHash($user): string
    {
        if (!$user) {
            return 'guest';
        }
        return md5(json_encode([
            'role' => $user->role?->code ?? $user->portal_role ?? null,
            'region_id' => $user->region_id ?? null,
            'council_id' => $user->district_council_id ?? null,
            'school_id' => $user->school_id ?? null,
        ]));
    }
}
