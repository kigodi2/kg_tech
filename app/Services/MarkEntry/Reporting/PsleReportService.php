<?php

namespace App\Services\MarkEntry\Reporting;

use App\Models\Candidate;
use App\Models\MarkImportBatch;
use App\Models\RawMark;
use App\Models\School;
use App\Models\Subject;
use App\Models\Region;
use App\Models\District;
use App\Models\User;
use App\Models\MarkEntryAssignment;
use App\Models\MarkEntryOutlier;
use App\Models\GovernanceAuditLog;
use App\Models\SystemEventLog;
use App\Models\MarkEntryChange;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use ZipArchive;

class PsleReportService
{
    private const TEMP_DIR = 'storage/app/temp/psle-reports';

    /**
     * Generate Regional Progress Data
     */
    public function getOverviewRegionalProgress(int $examYearId, ?int $regionId = null, int $subjectCount = 7): Collection
    {
        $regions = Region::where('name', 'NOT LIKE', '%UNASSIGNED%')
            ->where('name', 'NOT LIKE', '%CSEE%')
            ->where('name', 'NOT LIKE', '%ACSEE%')
            ->when($regionId, fn($q) => $q->where('id', $regionId))
            ->orderBy('name')
            ->get();

        $subjectCount = max(1, $subjectCount);

        // Pre-fetch counts grouped by region to avoid N+1 Eloquent queries
        $schoolsPerRegion = DB::table('schools')
            ->whereIn('school_type', ['PRIMARY', 'BOTH'])
            ->where('education_level', 'PRIMARY')
            ->groupBy('region_id')
            ->select('region_id', DB::raw('count(*) as aggregate'))
            ->pluck('aggregate', 'region_id')
            ->toArray();

        $candidatesPerRegion = DB::table('candidates')
            ->join('schools', 'candidates.school_id', '=', 'schools.id')
            ->whereIn('schools.school_type', ['PRIMARY', 'BOTH'])
            ->where('schools.education_level', 'PRIMARY')
            ->whereExists(function ($query) use ($examYearId) {
                $query->select(DB::raw(1))
                    ->from('candidate_exam_registrations')
                    ->join('exam_types', 'candidate_exam_registrations.exam_type_id', '=', 'exam_types.id')
                    ->whereColumn('candidate_exam_registrations.candidate_id', 'candidates.id')
                    ->where('candidate_exam_registrations.exam_year_id', $examYearId)
                    ->where('exam_types.code', 'PSLE');
            })
            ->groupBy('schools.region_id')
            ->select('schools.region_id', DB::raw('count(candidates.id) as aggregate'))
            ->pluck('aggregate', 'schools.region_id')
            ->toArray();

        $marksPerRegion = DB::table('raw_marks')
            ->join('candidates', 'raw_marks.candidate_id', '=', 'candidates.id')
            ->join('schools', 'candidates.school_id', '=', 'schools.id')
            ->whereIn('schools.school_type', ['PRIMARY', 'BOTH'])
            ->where('schools.education_level', 'PRIMARY')
            ->where('raw_marks.exam_year_id', $examYearId)
            ->whereExists(function ($query) use ($examYearId) {
                $query->select(DB::raw(1))
                    ->from('candidate_exam_registrations')
                    ->join('exam_types', 'candidate_exam_registrations.exam_type_id', '=', 'exam_types.id')
                    ->whereColumn('candidate_exam_registrations.candidate_id', 'candidates.id')
                    ->where('candidate_exam_registrations.exam_year_id', $examYearId)
                    ->where('exam_types.code', 'PSLE');
            })
            ->groupBy('schools.region_id')
            ->select('schools.region_id', DB::raw('count(raw_marks.id) as aggregate'))
            ->pluck('aggregate', 'schools.region_id')
            ->toArray();

        $outliersPerRegion = DB::table('mark_entry_outliers')
            ->where('exam_year_id', $examYearId)
            ->groupBy('region_id')
            ->select('region_id', DB::raw('count(*) as aggregate'))
            ->pluck('aggregate', 'region_id')
            ->toArray();

        return $regions->map(function ($region) use ($examYearId, $subjectCount, $schoolsPerRegion, $candidatesPerRegion, $marksPerRegion, $outliersPerRegion) {
            $schoolCount = $schoolsPerRegion[$region->id] ?? 0;
            $candidateCount = $candidatesPerRegion[$region->id] ?? 0;
            $marksEntered = $marksPerRegion[$region->id] ?? 0;
            $outliers = $outliersPerRegion[$region->id] ?? 0;

            $totalPossibleMarks = $candidateCount * $subjectCount;
            $progress = $totalPossibleMarks > 0 ? round(($marksEntered / $totalPossibleMarks) * 100, 1) : 0;

            return (object) [
                'region' => $region->name,
                'schools' => $schoolCount,
                'candidates' => $candidateCount,
                'marks_entered' => $marksEntered,
                'missing_marks' => max(0, $totalPossibleMarks - $marksEntered),
                'outliers' => $outliers,
                'progress' => min(100, $progress),
                'status' => $progress >= 100 ? 'Completed' : ($progress > 0 ? 'In Progress' : 'Pending'),
            ];
        });
    }

    public function getRegionalProgress(int $examYearId, ?int $regionId = null): Collection
    {
        $districts = District::whereHas('region', function ($q) {
                $q->where('name', 'NOT LIKE', '%UNASSIGNED%')
                  ->where('name', 'NOT LIKE', '%CSEE%')
                  ->where('name', 'NOT LIKE', '%ACSEE%');
            })
            ->when($regionId, fn($q) => $q->where('region_id', $regionId))
            ->orderBy('name')
            ->get();

        return $districts->map(function($district) use ($examYearId) {
            $schoolCount = School::where('district_id', $district->id)->count();
            
            $candidateCount = Candidate::whereHas('school', fn($q) => $q->where('district_id', $district->id))
                ->whereHas('examRegistrations', fn($q) => $q->where('exam_year_id', $examYearId))
                ->count();

            $marksEntered = RawMark::whereHas('batch', function($q) use ($examYearId, $district) {
                $q->where('district_id', $district->id);
                $yearLabel = \App\Models\ExamYear::where('id', $examYearId)->value('year_label');
                if ($yearLabel) $q->where('exam_year', $yearLabel);
            })->count();

            $outliers = MarkEntryOutlier::where('exam_year_id', $examYearId)
                ->where('district_id', $district->id)
                ->count();

            $totalPossibleMarks = $candidateCount * 7; // PSLE has 7 subjects
            $progress = $totalPossibleMarks > 0 ? round(($marksEntered / $totalPossibleMarks) * 100, 1) : 0;

            return (object) [
                'district' => $district->name,
                'schools' => $schoolCount,
                'candidates' => $candidateCount,
                'marks_entered' => $marksEntered,
                'missing_marks' => max(0, $totalPossibleMarks - $marksEntered),
                'outliers' => $outliers,
                'progress' => $progress,
                'status' => $progress >= 100 ? 'Completed' : ($progress > 0 ? 'In Progress' : 'Pending'),
            ];
        });
    }

    /**
     * Generate Officer Productivity Data
     */
    public function getOfficerProductivity(
        int $examYearId,
        ?int $regionId = null,
        ?int $officerId = null,
        ?int $districtId = null,
        ?int $schoolId = null,
        ?int $subjectId = null
    ): Collection {
        $officers = User::where(function($query) {
                $query->whereHas('role', fn($q) => $q->whereIn('code', ['mark_officer', 'mark_entry_officer', 'meo'])->orWhere('name', 'Mark Entry Officer'))
                    ->orWhereIn('portal_role', ['mark_officer', 'mark_entry_officer', 'meo']);
            })
            ->where(function($q) {
                $q->whereNull('region_id')
                  ->orWhereHas('region', function($rq) {
                      $rq->where('name', 'NOT LIKE', '%UNASSIGNED%')
                        ->where('name', 'NOT LIKE', '%CSEE%')
                        ->where('name', 'NOT LIKE', '%ACSEE%');
                  });
            })
            ->when($regionId, fn($q) => $q->where('region_id', $regionId))
            ->when($officerId, fn($q) => $q->whereKey($officerId))
            ->get();

        return $officers->map(function($officer) use ($examYearId, $regionId, $districtId, $schoolId, $subjectId) {
            // Check if they have active specific school-subject assignments
            $hasSpecificAssignments = MarkEntryAssignment::where('assigned_to', $officer->id)
                ->where('exam_year_id', $examYearId)
                ->exists();

            $totalCandidates = 0;
            $hasAssignment = false;

            if ($hasSpecificAssignments) {
                $hasAssignment = true;
                $assignments = MarkEntryAssignment::where('assigned_to', $officer->id)
                    ->where('exam_year_id', $examYearId)
                    ->when($regionId, fn($q) => $q->where('region_id', $regionId))
                    ->when($districtId, fn($q) => $q->where('district_id', $districtId))
                    ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
                    ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
                    ->get();

                foreach ($assignments as $asgn) {
                    $totalCandidates += Candidate::where('school_id', $asgn->school_id)
                        ->whereHas('examRegistrations', fn($eq) => $eq->where('exam_year_id', $examYearId))
                        ->whereHas('subjectSelections', fn($q) => $q->where('subject_id', $asgn->subject_id))
                        ->count();
                }
            } elseif ($officer->region_id) {
                $hasAssignment = true;
                // Regional workload: all primary school candidates in the officer's region * subjects within scope
                $regionCandidatesQuery = Candidate::whereHas('school', function ($q) use ($officer, $regionId, $districtId, $schoolId) {
                        $q->whereIn('school_type', ['PRIMARY', 'BOTH'])
                          ->where('education_level', 'PRIMARY');
                        
                        if ($regionId) {
                            $q->where('region_id', $regionId);
                        } else {
                            $q->where('region_id', $officer->region_id);
                        }
                        if ($districtId) $q->where('district_id', $districtId);
                        if ($schoolId) $q->where('school_id', $schoolId);
                    })
                    ->whereHas('examRegistrations', fn($eq) => $eq->where('exam_year_id', $examYearId));

                $candidateCount = $regionCandidatesQuery->count();

                if ($subjectId) {
                    $subjectCount = 1;
                } else {
                    $subjectCount = Subject::whereHas('examType', fn($eq) => $eq->where('code', 'PSLE'))
                        ->where('is_active', true)
                        ->count();
                    if ($subjectCount === 0) $subjectCount = 7; // default fallback
                }

                $totalCandidates = $candidateCount * $subjectCount;
            }

            // Calculate entered marks using all filters
            $enteredMarks = RawMark::where('entered_by', $officer->id)
                ->where('exam_year_id', $examYearId)
                ->whereHas('batch', function($q) use ($examYearId, $regionId, $districtId, $schoolId, $subjectId) {
                    $yearLabel = \App\Models\ExamYear::where('id', $examYearId)->value('year_label');
                    if ($yearLabel) $q->where('exam_year', $yearLabel);
                    
                    if ($regionId) $q->where('region_id', $regionId);
                    if ($districtId) $q->where('district_id', $districtId);
                    if ($schoolId) $q->where('school_id', $schoolId);
                    if ($subjectId) $q->where('subject_id', $subjectId);
                })
                ->count();

            // Set pending marks: null if they have historical entries but no active assignment
            $pendingMarks = null;
            if ($hasAssignment) {
                $pendingMarks = max(0, $totalCandidates - $enteredMarks);
            }

            return (object) [
                'officer' => $officer->name,
                'region' => $officer->region->name ?? 'N/A',
                'assigned_candidates' => $totalCandidates,
                'entered_marks' => $enteredMarks,
                'pending_marks' => $pendingMarks,
                'has_assignment' => $hasAssignment,
                'last_active' => $officer->last_login_at ? $officer->last_login_at->format('d M Y, H:i') : 'Never',
            ];
        });
    }

    /**
     * Generate Missing Marks Data
     */
    public function getMissingMarks(int $examYearId, ?int $regionId = null): Collection
    {
        return Candidate::whereHas('examRegistrations', fn($q) => $q->where('exam_year_id', $examYearId))
            ->whereHas('school', function($q) use ($regionId) {
                if ($regionId) $q->where('region_id', $regionId);
            })
            ->whereHas('subjectSelections', function($q) {
                $q->whereDoesntHave('rawMarks');
            })
            ->with(['school', 'subjectSelections.subject'])
            ->get()
            ->flatMap(function($candidate) use ($examYearId) {
                return $candidate->subjectSelections->map(function($sel) use ($candidate, $examYearId) {
                    $assignment = MarkEntryAssignment::where([
                        'school_id' => $candidate->school_id,
                        'subject_id' => $sel->subject_id,
                        'exam_year_id' => $examYearId
                    ])->first();

                    return (object) [
                        'cno' => $candidate->candidate_id,
                        'name' => $candidate->full_name,
                        'sex' => $candidate->gender,
                        'school' => $candidate->school->name,
                        'subject' => $sel->subject->name,
                        'assigned_officer' => $assignment->assignedTo->name ?? 'Unassigned',
                        'status' => 'Missing',
                    ];
                });
            });
    }

    /**
     * Generate Outliers Data
     */
    public function getOutliers(int $examYearId, ?int $regionId = null): Collection
    {
        return MarkEntryOutlier::with(['candidate', 'school', 'subject', 'officer'])
            ->where('exam_year_id', $examYearId)
            ->when($regionId, fn($q) => $q->where('region_id', $regionId))
            ->get()
            ->map(fn($o) => (object) [
                'cno' => $o->candidate->candidate_id ?? 'N/A',
                'name' => $o->candidate->full_name ?? 'N/A',
                'school' => $o->school->name ?? 'N/A',
                'subject' => $o->subject->name ?? 'N/A',
                'mark' => $o->observed_value,
                'type' => $o->outlier_type,
                'severity' => $o->severity,
                'status' => $o->status,
            ]);
    }

    /**
     * Export to Excel (CSV)
     */
    public function exportExcel(string $type, Collection $data, string $filename): array
    {
        $this->ensureTempDirectory();
        $filePath = base_path(self::TEMP_DIR . '/' . $filename);
        $handle = fopen($filePath, 'w');

        if ($data->isNotEmpty()) {
            $first = (array) $data->first();
            fputcsv($handle, array_keys($first));
            foreach ($data as $row) {
                fputcsv($handle, (array) $row);
            }
        }
        
        fclose($handle);

        return [
            'file_path' => $filePath,
            'filename' => $filename,
        ];
    }

    private function ensureTempDirectory(): void
    {
        $dir = base_path(self::TEMP_DIR);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function logAudit(string $type, $user, array $filters = []): void
    {
        GovernanceAuditLog::log(
            'REPORT_GENERATED',
            null,
            $user->id,
            [
                'report_type' => $type,
                'filters' => $filters,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]
        );
    }

    /**
     * Get recent activity for monitoring
     */
    public function getRecentActivity(
        int $examYearId,
        ?int $regionId = null,
        int $limit = 20,
        ?int $officerId = null,
        ?int $districtId = null,
        ?int $schoolId = null,
        ?int $subjectId = null
    ): Collection {
        return SystemEventLog::with('actor')
            ->when($officerId, fn($q) => $q->where('actor_user_id', $officerId))
            ->when($regionId, function($q) use ($regionId) {
                $q->where(function($sub) use ($regionId) {
                    $sub->whereHas('actor', fn($aq) => $aq->where('region_id', $regionId))
                        ->orWhere('context->region_id', $regionId);
                });
            })
            ->when($districtId, function($q) use ($districtId) {
                $q->where(function($sub) use ($districtId) {
                    $sub->whereHas('actor', fn($aq) => $aq->where('district_council_id', $districtId))
                        ->orWhere('context->district_id', $districtId);
                });
            })
            ->when($schoolId, fn($q) => $q->where('context->school_id', $schoolId))
            ->when($subjectId, fn($q) => $q->where('context->subject_id', $subjectId))
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get batch activity for monitoring
     */
    public function getBatchActivity(
        int $examYearId,
        ?int $regionId = null,
        int $limit = 20,
        ?int $officerId = null,
        ?int $districtId = null,
        ?int $schoolId = null,
        ?int $subjectId = null
    ): Collection {
        $yearLabel = \App\Models\ExamYear::where('id', $examYearId)->value('year_label');
        
        return MarkImportBatch::with(['school', 'subject', 'region', 'district', 'assignment'])
            ->when($yearLabel, fn($q) => $q->where('exam_year', $yearLabel))
            ->when($regionId, fn($q) => $q->where('region_id', $regionId))
            ->when($districtId, fn($q) => $q->where('district_id', $districtId))
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
            ->when($officerId, function($q) use ($officerId) {
                $q->where(function($owned) use ($officerId) {
                    $owned->where('created_by', $officerId)
                        ->orWhere('imported_by', $officerId)
                        ->orWhereHas('assignment', fn($assignmentQuery) => $assignmentQuery->where('assigned_to', $officerId));
                });
            })
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit trail preview
     */
    public function getAuditTrail(
        int $examYearId,
        ?int $regionId = null,
        int $limit = 20,
        ?int $officerId = null,
        ?int $districtId = null,
        ?int $schoolId = null,
        ?int $subjectId = null
    ): Collection {
        // Combine SystemEventLog and MarkEntryChange for a comprehensive trail
        $events = SystemEventLog::with('actor')
            ->when($officerId, fn($q) => $q->where('actor_user_id', $officerId))
            ->when($regionId, function($q) use ($regionId) {
                $q->where(function($sub) use ($regionId) {
                    $sub->whereHas('actor', fn($aq) => $aq->where('region_id', $regionId))
                        ->orWhere('context->region_id', $regionId);
                });
            })
            ->when($districtId, function($q) use ($districtId) {
                $q->where(function($sub) use ($districtId) {
                    $sub->whereHas('actor', fn($aq) => $aq->where('district_council_id', $districtId))
                        ->orWhere('context->district_id', $districtId);
                });
            })
            ->when($schoolId, fn($q) => $q->where('context->school_id', $schoolId))
            ->when($subjectId, fn($q) => $q->where('context->subject_id', $subjectId))
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($e) => (object) [
                'time' => $e->created_at,
                'user' => $e->actor->name ?? 'System',
                'role' => $e->actor->portal_role ?? 'System',
                'region' => $e->actor->region->name ?? 'N/A',
                'action' => $e->action,
                'details' => $e->message,
                'ip' => $e->ip_address,
            ]);

        return $events;
    }
}
