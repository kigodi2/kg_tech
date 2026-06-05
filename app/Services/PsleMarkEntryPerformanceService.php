<?php

namespace App\Services;

use App\Models\User;
use App\Models\RawMark;
use App\Models\MarkEntryAssignment;
use App\Models\Candidate;
use App\Models\School;
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

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 900, function() use ($examYearId, $regionId, $districtId, $schoolId, $subjectId) {
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

            // 2. Identify all user IDs: active officers and users with active assignments in this exam year
            $assignedUserIds = MarkEntryAssignment::where('status', 'active')
                ->when($examYearId, fn($q) => $q->where('exam_year_id', $examYearId))
                ->distinct()
                ->pluck('assigned_to')
                ->toArray();

            $userIds = array_unique(array_merge($officers->pluck('id')->toArray(), $assignedUserIds));

            if (empty($userIds)) {
                return [];
            }

            // Pre-query total marks in scope for contribution calculations
            $totalMarksInScopeQuery = \Illuminate\Support\Facades\DB::table('raw_marks')
                ->when($examYearId, fn($q) => $q->where('raw_marks.exam_year_id', $examYearId))
                ->when($schoolId, fn($q) => $q->where('raw_marks.school_id', $schoolId))
                ->when($subjectId, fn($q) => $q->where('raw_marks.subject_id', $subjectId));

            if ($regionId || $districtId) {
                $totalMarksInScopeQuery->join('schools', 'raw_marks.school_id', '=', 'schools.id')
                    ->when($regionId, fn($q) => $q->where('schools.region_id', $regionId))
                    ->when($districtId, fn($q) => $q->where('schools.district_id', $districtId));
            }

            $totalMarksInScope = $totalMarksInScopeQuery->count();

            // Bulk load all users in scope to avoid N+1 User query in the loop
            $usersMap = User::whereIn('id', $userIds)->with('region')->get()->keyBy('id');

            // Bulk aggregate mark stats per user in scope
            $userStatsQuery = \Illuminate\Support\Facades\DB::table('raw_marks')
                ->when($examYearId, fn($q) => $q->where('raw_marks.exam_year_id', $examYearId))
                ->when($schoolId, fn($q) => $q->where('raw_marks.school_id', $schoolId))
                ->when($subjectId, fn($q) => $q->where('raw_marks.subject_id', $subjectId));

            if ($regionId || $districtId) {
                $userStatsQuery->join('schools', 'raw_marks.school_id', '=', 'schools.id')
                    ->when($regionId, fn($q) => $q->where('schools.region_id', $regionId))
                    ->when($districtId, fn($q) => $q->where('schools.district_id', $districtId));
            }

            $userStats = $userStatsQuery
                ->whereIn('raw_marks.entered_by', $userIds)
                ->groupBy('raw_marks.entered_by')
                ->select(
                    'raw_marks.entered_by as user_id',
                    \Illuminate\Support\Facades\DB::raw('count(*) as marks_count'),
                    \Illuminate\Support\Facades\DB::raw('count(distinct raw_marks.school_id) as schools_count'),
                    \Illuminate\Support\Facades\DB::raw('count(distinct raw_marks.subject_id) as subjects_count'),
                    \Illuminate\Support\Facades\DB::raw('max(raw_marks.updated_at) as last_activity_updated_at'),
                    \Illuminate\Support\Facades\DB::raw('max(raw_marks.created_at) as last_activity_created_at')
                )
                ->get()
                ->keyBy('user_id');

            // Pre-load all assignments for these users
            $allAssignments = MarkEntryAssignment::whereIn('assigned_to', $userIds)
                ->where('status', 'active')
                ->when($examYearId, fn($q) => $q->where('exam_year_id', $examYearId))
                ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
                ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
                ->when($regionId, fn($q) => $q->where('region_id', $regionId))
                ->when($districtId, fn($q) => $q->where('district_id', $districtId))
                ->get();

            $schoolIds = $allAssignments->pluck('school_id')->unique()->toArray();
            $schoolCountsMap = [];

            if (!empty($schoolIds) && $examYearId) {
                $psleType = \App\Models\ExamType::where('code', 'PSLE')->first();
                $psleTypeId = $psleType ? $psleType->id : 4;

                // Bulk query candidates for all these schools
                $allCandidates = Candidate::query()
                    ->where('candidates.is_active', true)
                    ->where(function ($q) use ($psleTypeId) {
                        $q->where('candidates.exam_type', 'PSLE')
                          ->orWhereHas('examRegistrations', fn($r) => $r->where('exam_type_id', $psleTypeId));
                    })
                    ->whereHas('examRegistrations', function ($q) use ($psleTypeId, $examYearId) {
                        $q->where('exam_type_id', $psleTypeId)
                          ->where('exam_year_id', $examYearId);
                    })
                    ->whereIn('candidates.school_id', $schoolIds)
                    ->get();

                $candidatesBySchool = $allCandidates->groupBy('school_id');
                $schoolsMap = School::whereIn('id', $schoolIds)->get()->keyBy('id');

                foreach ($schoolIds as $sid) {
                    $schoolCandidates = $candidatesBySchool->get($sid, collect());
                    $school = $schoolsMap->get($sid);
                    $schoolCode = $school ? $school->code : '';
                    $schoolCountsMap[$sid] = PsleCandidateRosterService::deduplicate($schoolCandidates, $schoolCode)->count();
                }
            }

            $rankings = [];

            foreach ($userIds as $uid) {
                $officer = $usersMap->get($uid);
                if (!$officer) continue;

                // Strict check: if officer region does not match selected region scope, skip (security check)
                if ($regionId && $officer->region_id && (int) $officer->region_id !== (int) $regionId) {
                    continue;
                }

                // A. Marks entered by this officer in scope
                $stats = $userStats->get($uid);
                $marksCount = $stats ? $stats->marks_count : 0;
                $schoolsCount = $stats ? $stats->schools_count : 0;
                $subjectsCount = $stats ? $stats->subjects_count : 0;

                $lastActivityUpdated = $stats ? $stats->last_activity_updated_at : null;
                $lastActivityCreated = $stats ? $stats->last_activity_created_at : null;
                $lastActivityAt = $lastActivityUpdated ?: $lastActivityCreated;
                $lastActivityAt = $lastActivityAt ? Carbon::parse($lastActivityAt) : null;

                // B. Expected Marks / Completion percentage
                $userAssignments = $allAssignments->where('assigned_to', $uid);
                $expectedMarks = 0;
                $hasAssignments = $userAssignments->isNotEmpty();

                if ($hasAssignments && $examYearId) {
                    foreach ($userAssignments as $assignment) {
                        $candCount = $schoolCountsMap[$assignment->school_id] ?? 0;
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
