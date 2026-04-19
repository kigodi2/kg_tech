<?php

namespace App\Services\Results;

use App\Models\CandidateResult;
use App\Models\District;
use App\Models\ExamYear;
use App\Models\ExportAuditLog;
use App\Models\Region;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * ACSEE Results Service
 * 
 * Handles:
 * - Role-based data scoping
 * - Filter retrieval and caching
 * - Query building
 * - Export preparation
 * - Audit logging
 */
class AcseeResultsService
{
    const CACHE_TTL = 3600; // 1 hour
    const CACHE_PREFIX = 'acsee_results_';

    /**
     * Get available exam years with published ACSEE results
     * Returns: Collection of year data (always wrapped for view safety)
     */
    public function getAvailableExamYears(User $user): array
    {
        $cacheKey = "exam_years_with_acsee_published";

        $cached = Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return ExamYear::query()
                ->where('is_locked', true)
                ->whereNotNull('published_at')
                ->orderByDesc('year_label')
                ->get(['id', 'year_label', 'published_at'])
                ->map(fn ($year) => [
                    'id' => $year->id,
                    'label' => $year->year_label,
                    'published_at' => $year->published_at?->format('Y-m-d'),
                ])
                ->toArray();
        });

        // Always return array for controller to wrap in Collection
        return $cached ?? [];
    }

    /**
     * Get a specific published exam year
     */
    public function getPublishedExamYear($year): ?ExamYear
    {
        return ExamYear::query()
            ->where('year_label', $year)
            ->where('is_locked', true)
            ->whereNotNull('published_at')
            ->first();
    }

    /**
     * Get subjects for a specific year and exam type
     */
    public function getSubjectsForYear(int $year, string $examType): array
    {
        $cacheKey = self::CACHE_PREFIX . "subjects_{$year}_{$examType}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($examType) {
            return Subject::query()
                ->whereHas('examTypes', function ($q) use ($examType) {
                    $q->where('code', $examType);
                })
                ->orderBy('code')
                ->get(['id', 'code', 'name'])
                ->toArray();
        });
    }

    /**
     * Get available scope filters for user
     * 
     * Determines which regions, districts, schools the user can access
     * based on their role and scope.
     * 
     * Returns:
     * [
     *   'regions' => [...],
     *   'districts' => [...],
     *   'schools' => [...],
     *   'user_scope_type' => 'super_admin|region|district|school'
     * ]
     */
    public function getAvailableScopeFilters(User $user, int $year): array
    {
        $roleCode = $user->role->code ?? null;

        $cacheKey = self::CACHE_PREFIX . "filters_{$user->id}_{$year}_{$roleCode}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $roleCode) {
            $userScope = $user->scope;

            return match ($roleCode) {
                'super_admin' => [
                    'regions' => $this->getRegionsForResults(),
                    'districts' => [],
                    'schools' => [],
                    'user_scope_type' => 'super_admin',
                    'user_scope_id' => null,
                ],

                'regional_admin' => [
                    'regions' => [
                        [
                            'id' => $userScope->scope_id,
                            'name' => Region::find($userScope->scope_id)?->name,
                        ]
                    ],
                    'districts' => $this->getDistrictsForRegion($userScope->scope_id),
                    'schools' => [],
                    'user_scope_type' => 'region',
                    'user_scope_id' => $userScope->scope_id,
                ],

                'district_admin' => [
                    'regions' => [],
                    'districts' => [
                        [
                            'id' => $userScope->scope_id,
                            'name' => District::find($userScope->scope_id)?->name,
                        ]
                    ],
                    'schools' => $this->getSchoolsForDistrict($userScope->scope_id),
                    'user_scope_type' => 'district',
                    'user_scope_id' => $userScope->scope_id,
                ],

                'school_user' => [
                    'regions' => [],
                    'districts' => [],
                    'schools' => [
                        [
                            'id' => $user->school_id,
                            'name' => $user->school?->name,
                        ]
                    ],
                    'user_scope_type' => 'school',
                    'user_scope_id' => $user->school_id,
                ],

                default => [
                    'regions' => [],
                    'districts' => [],
                    'schools' => [],
                    'user_scope_type' => null,
                    'user_scope_id' => null,
                ]
            };
        });
    }

    /**
     * Get regions that have results
     */
    protected function getRegionsForResults(): array
    {
        return Region::query()
            ->whereHas('schools.candidates.results', function ($q) {
                $q->where('is_published', true);
            })
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    /**
     * Get districts for a specific region
     */
    protected function getDistrictsForRegion(int $regionId): array
    {
        return District::query()
            ->where('region_id', $regionId)
            ->whereHas('schools.candidates.results', function ($q) {
                $q->where('is_published', true);
            })
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    /**
     * Get schools for a specific district
     */
    protected function getSchoolsForDistrict(int $districtId): array
    {
        return School::query()
            ->where('district_id', $districtId)
            ->whereHas('candidates.results', function ($q) {
                $q->where('is_published', true);
            })
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    /**
     * Apply scope filter based on user role and requested filters
     * 
     * Ensures user cannot access data outside their jurisdiction.
     * 
     * Returns:
     * [
     *   'region_id' => int|null,
     *   'district_id' => int|null,
     *   'school_id' => int|null,
     * ]
     */
    public function applyScopeFilter(User $user, array $requestFilters): array
    {
        $roleCode = $user->role->code ?? null;

        return match ($roleCode) {
            'super_admin' => [
                'region_id' => $requestFilters['region_id'] ?? null,
                'district_id' => $requestFilters['district_id'] ?? null,
                'school_id' => $requestFilters['school_id'] ?? null,
            ],

            'regional_admin' => [
                'region_id' => $user->scope->scope_id,
                'district_id' => $requestFilters['district_id'] ?? null,
                'school_id' => $requestFilters['school_id'] ?? null,
            ],

            'district_admin' => [
                'region_id' => null,
                'district_id' => $user->scope->scope_id,
                'school_id' => $requestFilters['school_id'] ?? null,
            ],

            'school_user' => [
                'region_id' => null,
                'district_id' => null,
                'school_id' => $user->school_id,
            ],

            default => [
                'region_id' => null,
                'district_id' => null,
                'school_id' => null,
            ]
        };
    }

    /**
     * Apply scope filters to query
     */
    public function applyScopeQuery(Builder $query, array $scopes): Builder
    {
        if ($scopes['school_id']) {
            return $query->whereHas('candidate', function ($q) {
                $q->where('school_id', $scopes['school_id']);
            });
        }

        if ($scopes['district_id']) {
            return $query->whereHas('candidate.school', function ($q) {
                $q->where('district_id', $scopes['district_id']);
            });
        }

        if ($scopes['region_id']) {
            return $query->whereHas('candidate.school', function ($q) {
                $q->where('region_id', $scopes['region_id']);
            });
        }

        return $query;
    }

    /**
     * Validate that user's requested scopes don't exceed their jurisdiction
     */
    public function validateUserScopes(User $user, array $requestScopes): void
    {
        $roleCode = $user->role->code ?? null;

        if ($roleCode === 'regional_admin') {
            // Regional admin can only view their region
            if (isset($requestScopes['region_id']) && 
                $requestScopes['region_id'] !== $user->scope->scope_id) {
                abort(403, 'Cannot access data outside your region');
            }
        }

        if ($roleCode === 'district_admin') {
            // District admin can only view their district
            if (isset($requestScopes['district_id']) && 
                $requestScopes['district_id'] !== $user->scope->scope_id) {
                abort(403, 'Cannot access data outside your district');
            }
        }

        if ($roleCode === 'school_user') {
            // School user can only view their school
            if (isset($requestScopes['school_id']) && 
                $requestScopes['school_id'] !== $user->school_id) {
                abort(403, 'Cannot access data outside your school');
            }
        }
    }

    /**
     * Get results data for export
     */
    public function getExportResults(User $user, array $filters): \Illuminate\Database\Eloquent\Collection
    {
        $query = CandidateResult::query()
            ->where('is_published', true)
            ->where('year', $filters['year'])
            ->whereHas('examType', function ($q) {
                $q->where('code', 'ACSEE');
            });

        // Apply scope
        $scopes = $this->applyScopeFilter($user, $filters);
        $query = $this->applyScopeQuery($query, $scopes);

        // Eager load
        return $query->with([
            'candidate:id,school_id,candidate_id,full_name,gender',
            'candidate.school:id,district_id,region_id,name',
            'candidate.school.district:id,region_id,name',
            'candidate.school.region:id,name',
            'examType:id,code,name',
            'subjectMarks:id,candidate_id,subject_id,exam_type_id,year,marks_obtained,grade,grade_from_average'
                ->with('subject:id,code,name'),
        ])->get();
    }

    /**
     * Log export action for audit trail
     */
    public function logExportAction(User $user, string $format, array $filters): void
    {
        try {
            ExportAuditLog::create([
                'user_id' => $user->id,
                'module' => 'acsee_results',
                'format' => $format,
                'year' => $filters['year'],
                'region_id' => $filters['region_id'] ?? null,
                'district_id' => $filters['district_id'] ?? null,
                'school_id' => $filters['school_id'] ?? null,
                'exported_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            \Log::warning('Failed to log export action', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear cache for a specific user/year combination
     */
    public function clearCache(User $user, int $year): void
    {
        Cache::forget(self::CACHE_PREFIX . "filters_{$user->id}_{$year}");
    }

    /**
     * Clear all results cache
     */
    public function clearAllCache(): void
    {
        Cache::tags([self::CACHE_PREFIX])->flush();
    }
}
