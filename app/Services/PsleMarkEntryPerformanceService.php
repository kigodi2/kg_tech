<?php

namespace App\Services;

use App\Models\User;
use App\Models\RawMark;
use App\Models\MarkEntryAssignment;
use App\Services\PsleCandidateRosterService;
use Carbon\Carbon;

class PsleMarkEntryPerformanceService
{
    /**
     * Get Mark Entry Officer performance ranking list based on selected filters and user scope.
     *
     * @param array $filters
     * @param User $user
     * @return array
     */
    public function getRankings(array $filters, User $user): array
    {
        $examYearId = $filters['exam_year_id'] ?? null;
        $regionId = $filters['region_id'] ?? null;
        $districtId = $filters['district_id'] ?? null;
        $schoolId = $filters['school_id'] ?? null;
        $subjectId = $filters['subject_id'] ?? null;

        // Force region locking for non-admin users with region scopes (MEOs and REOs)
        if (!$user->isAdmin()) {
            if ($user->region_id) {
                $regionId = $user->region_id;
            } else {
                // If they have no assigned region and are not admin, they have no scope
                return [];
            }
        }

        $cacheKey = sprintf(
            'psle:ranking:%s:%s:%s:%s:%s',
            $examYearId ?? 'all',
            $regionId ?? 'all',
            $districtId ?? 'all',
            $schoolId ?? 'all',
            $subjectId ?? 'all'
        );

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 60, function() use ($examYearId, $regionId, $districtId, $schoolId, $subjectId) {
            // 1. Get all active Mark Entry Officers in system
            $officers = User::query()
                ->where(function($q) {
                    $q->whereIn('portal_role', ['mark_officer', 'mark_entry_officer', 'meo'])
                      ->orWhereHas('role', function($rq) {
                          $rq->whereIn('code', ['mark_officer', 'mark_entry_officer', 'meo'])
                             ->orWhere('name', 'Mark Entry Officer');
                      });
                })
                ->when($regionId, fn($q) => $q->where('region_id', $regionId))
                ->get();

            // 2. Identify all user IDs who have entered marks under the filtered scope
            $enteredUserIds = RawMark::query()
                ->when($examYearId, fn($q) => $q->where('exam_year_id', $examYearId))
                ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
                ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
                ->when($regionId, function($q) use ($regionId) {
                    $q->whereHas('school', fn($sq) => $sq->where('region_id', $regionId));
                })
                ->when($districtId, function($q) use ($districtId) {
                    $q->whereHas('school', fn($sq) => $sq->where('district_id', $districtId));
                })
                ->whereNotNull('entered_by')
                ->distinct()
                ->pluck('entered_by')
                ->toArray();

            $userIds = array_unique(array_merge($officers->pluck('id')->toArray(), $enteredUserIds));

            if (empty($userIds)) {
                return [];
            }

            // Pre-query total marks in scope for contribution calculations
            $totalMarksInScope = RawMark::query()
                ->when($examYearId, fn($q) => $q->where('exam_year_id', $examYearId))
                ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
                ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
                ->when($regionId, function($q) use ($regionId) {
                    $q->whereHas('school', fn($sq) => $sq->where('region_id', $regionId));
                })
                ->when($districtId, function($q) use ($districtId) {
                    $q->whereHas('school', fn($sq) => $sq->where('district_id', $districtId));
                })
                ->count();

            $rankings = [];

            foreach ($userIds as $uid) {
                $officer = User::find($uid);
                if (!$officer) continue;

                // Strict check: if officer region does not match selected region scope, skip (security check)
                if ($regionId && $officer->region_id && (int) $officer->region_id !== (int) $regionId) {
                    continue;
                }

                // A. Marks entered by this officer in scope
                $marksQuery = RawMark::where('entered_by', $uid)
                    ->when($examYearId, fn($q) => $q->where('exam_year_id', $examYearId))
                    ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
                    ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
                    ->when($regionId, function($q) use ($regionId) {
                        $q->whereHas('school', fn($sq) => $sq->where('region_id', $regionId));
                    })
                    ->when($districtId, function($q) use ($districtId) {
                        $q->whereHas('school', fn($sq) => $sq->where('district_id', $districtId));
                    });

                $marksCount = $marksQuery->count();
                $schoolsCount = (clone $marksQuery)->distinct()->count('school_id');
                $subjectsCount = (clone $marksQuery)->distinct()->count('subject_id');

                $lastActivityMark = (clone $marksQuery)->latest('updated_at')->first();
                $lastActivityAt = $lastActivityMark ? ($lastActivityMark->updated_at ?: $lastActivityMark->created_at) : null;

                // B. Expected Marks / Completion percentage
                $assignments = MarkEntryAssignment::where('assigned_to', $uid)
                    ->where('status', 'active')
                    ->when($examYearId, fn($q) => $q->where('exam_year_id', $examYearId))
                    ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
                    ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
                    ->when($regionId, fn($q) => $q->where('region_id', $regionId))
                    ->when($districtId, fn($q) => $q->where('district_id', $districtId))
                    ->get();

                $expectedMarks = 0;
                $hasAssignments = $assignments->isNotEmpty();

                if ($hasAssignments && $examYearId) {
                    foreach ($assignments as $assignment) {
                        $candCount = PsleCandidateRosterService::getDeduplicatedCount((int) $examYearId, $assignment->school_id);
                        $expectedMarks += $candCount;
                    }
                }

                if ($expectedMarks > 0) {
                    $percentage = min(100.0, round(($marksCount / $expectedMarks) * 100, 1));
                    $isContribution = false;
                } else {
                    if ($totalMarksInScope > 0) {
                        $percentage = round(($marksCount / $totalMarksInScope) * 100, 1);
                    } else {
                        $percentage = 0.0;
                    }
                    $isContribution = true;
                }

                $rankings[] = [
                    'user_id' => $officer->id,
                    'name' => strtoupper($officer->name),
                    'role_label' => 'Mark Entry Officer',
                    'region_name' => $officer->region->name ?? 'N/A',
                    'marks_entered' => $marksCount,
                    'completion_percentage' => $percentage,
                    'is_contribution' => $isContribution,
                    'schools_touched' => $schoolsCount,
                    'subjects_touched' => $subjectsCount,
                    'last_activity_at' => $lastActivityAt ? $lastActivityAt->toIso8601String() : null,
                    'last_activity_display' => $lastActivityAt ? Carbon::parse($lastActivityAt)->diffForHumans() : 'Never',
                    'last_activity_timestamp' => $lastActivityAt ? Carbon::parse($lastActivityAt)->timestamp : 0,
                ];
            }

            // 3. Sort rankings by marks_entered DESC, then completion_percentage DESC, then last_activity_timestamp DESC
            usort($rankings, function($a, $b) {
                if ($b['marks_entered'] !== $a['marks_entered']) {
                    return $b['marks_entered'] <=> $a['marks_entered'];
                }
                if ($b['completion_percentage'] !== $a['completion_percentage']) {
                    return $b['completion_percentage'] <=> $a['completion_percentage'];
                }
                return $b['last_activity_timestamp'] <=> $a['last_activity_timestamp'];
            });

            // 4. Assign rank positions
            foreach ($rankings as $index => &$rank) {
                $rank['rank'] = $index + 1;
            }

            return $rankings;
        });
    }
}
