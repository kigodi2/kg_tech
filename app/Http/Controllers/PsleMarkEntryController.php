<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\School;
use App\Models\Subject;
use App\Models\ExamYear;
use App\Models\GovernanceAuditLog;
use App\Models\MarkEntryAssignment;
use App\Models\MarkImportBatch;
use App\Models\PsleActivityLog;
use App\Models\Region;
use App\Models\District;
use App\Services\MarkEntry\PsleMarkEntryService;
use App\Services\MarkEntry\PsleScoresheetFpdfService;
use App\Services\PsleActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Throwable;

class PsleMarkEntryController extends Controller
{
    public function __construct(
        private PsleMarkEntryService $service,
        private PsleScoresheetFpdfService $scoresheetService,
        private \App\Services\MarkEntryOutlierService $outlierService,
        private \App\Services\MarkEntry\Reporting\PsleReportService $reportService,
        private \App\Services\MarkEntry\PsleMarkValidationService $validationService
    ) {
    }

    public function index(Request $request)
    {
        $currentView = $request->query('view', 'overview');
        if ($currentView === 'candidate-registration') {
            return redirect()->route('mark-entry.psle.index');
        }

        $user = $request->user();
        $isTrulyAdmin = $user->isAdmin();
        $moderationStats = [];
        $lockingStats = [];
        $diagnosticsMeta = null;
        $schoolSummaries = null;
        $schoolDetails = null;
        $classification = 'all';
        
        // Role Simulator for Testing (Admins Only)
        $simulatedRole = $request->query('simulate_role');
        $isAdmin = $isTrulyAdmin;
        
        if ($isTrulyAdmin && $simulatedRole) {
            $isReo = ($simulatedRole === 'reo');
            $isMarkOfficer = ($simulatedRole === 'mark_officer');
            $isAdmin = ($simulatedRole === 'admin'); // Override isAdmin for view logic if simulating others
        } else {
            $isReo = $this->isReoUser($user) || ($user->region_id && !$user->hasRole('officer') && ! $this->isMarkOfficerUser($user) && !$isTrulyAdmin);
            $isMarkOfficer = $this->isMarkOfficerUser($user);
        }

        if (in_array($currentView, ['start-entry', 'bulk-import']) && ($isAdmin || $isReo)) {
            return redirect('/mark-entry/psle')
                ->with('warning', 'Admin and REO accounts can review mark sheets in read-only mode only.');
        }
        
        // Active Exam Year & Filters
        $activeYear = \App\Models\ExamYear::where('is_active', true)->first();
        $examYears = \App\Models\ExamYear::orderBy('year_label', 'desc')->get();
        $selectedYearId = $this->normalizeOptionalId($request->query('exam_year_id')) ?? $activeYear?->id;
        $selectedStatus = $request->query('status');
        $selectedCreatedBy = $request->query('created_by');

        $psleExamType = \App\Models\ExamType::where('code', 'PSLE')->first();
        $psleExamTypeId = $psleExamType?->id;

        $currentView = $request->query('view', 'overview');
        $allowedViews = [
            'overview', 'entry-validation', 'moderation-review', 'submission-locking', 'reports-exports', 'monitoring-audit',
            'start-entry', 'bulk-import', 'assignments', 'missing-marks', 'validation-errors', 'outliers', 'reports',
            'user-management', 'marking-centres', 'entry-sheet', 'subject-panel-assignments'
        ];
        if (!in_array($currentView, $allowedViews, true)) {
            $currentView = 'overview';
        }

        // Safe Region Scoping - Now includes Mark Entry Officers
        $isRegionScopedUser = ($isReo || $isMarkOfficer) && !$isTrulyAdmin;
        $allowedRegionId = null;
        if ($isRegionScopedUser) {
            $allowedRegionId = $user->region_id;
        }

        if ($currentView === 'bulk-import') {
            return $this->renderPsleBulkImportView(
                $request,
                $user,
                $isAdmin,
                $isReo,
                $isMarkOfficer,
                $isTrulyAdmin,
                $simulatedRole,
                $examYears,
                $selectedYearId,
                $psleExamTypeId,
                $allowedRegionId,
                $currentView
            );
        }

        // Dropdown data
        $regionsQuery = \App\Models\Region::where('name', 'NOT LIKE', '%UNASSIGNED%')
            ->where('name', 'NOT LIKE', '%CSEE%')
            ->where('name', 'NOT LIKE', '%ACSEE%')
            ->orderBy('name');
        if ($allowedRegionId) {
            $regionsQuery->where('id', $allowedRegionId);
        } elseif ($isRegionScopedUser) {
            $regionsQuery->whereRaw('1 = 0');
        }
        $regions = $regionsQuery->get();

        // Safe URL Overrides
        $selectedRegionId = $this->normalizeOptionalId($request->query('region_id'));
        if ($allowedRegionId) {
            $selectedRegionId = $allowedRegionId; // Force REO/Officer to their own region
        } elseif ($selectedRegionId) {
            $selectedRegionId = $regions->contains('id', $selectedRegionId) ? $selectedRegionId : null;
        }

        // If still null and it's a simulated role for admin, pick the first region to avoid empty filters
        if (!$selectedRegionId && $isTrulyAdmin && ($isReo || $isMarkOfficer)) {
            $selectedRegionId = $regions->first()?->id;
        }

        $districts = collect();
        $selectedDistrictId = $this->normalizeOptionalId($request->query('district_id'));
        if ($selectedRegionId) {
            $districtsQuery = \App\Models\District::where('region_id', $selectedRegionId)
                ->whereHas('schools', function($q) {
                    $q->whereIn('school_type', ['PRIMARY', 'BOTH'])
                      ->where('education_level', 'PRIMARY');
                });
            if ($isMarkOfficer && !$isTrulyAdmin) {
                if ($user->region_id) {
                    // Region-assigned MEO - can access all districts in their region
                } else {
                    $assignedSchoolIds = \App\Models\MarkEntryAssignment::where([
                        'assigned_to' => $user->id,
                        'exam_year_id' => $selectedYearId,
                        'exam_type_id' => $psleExamTypeId,
                        'status' => 'active',
                    ])->pluck('school_id')->unique()->toArray();
                    $districtsQuery->whereHas('schools', function($q) use ($assignedSchoolIds) {
                        $q->whereIn('id', $assignedSchoolIds);
                    });
                }
            }
            $districts = $districtsQuery->orderBy('name')->get();
            $selectedDistrictId = $districts->contains('id', $selectedDistrictId) ? $selectedDistrictId : null;
        } else {
            $selectedDistrictId = null;
        }

        $schools = collect();
        $selectedSchoolId = $this->normalizeOptionalId($request->query('school_id'));
        if ($selectedDistrictId || $selectedRegionId) {
            $schoolsQuery = \App\Models\School::whereIn('school_type', ['PRIMARY', 'BOTH'])
                ->where('education_level', 'PRIMARY');
            if ($selectedDistrictId) {
                $schoolsQuery->where('district_id', $selectedDistrictId);
            } elseif ($selectedRegionId) {
                $schoolsQuery->where('region_id', $selectedRegionId);
            }
            if ($isMarkOfficer && !$isTrulyAdmin) {
                if ($user->region_id) {
                    // Region-assigned MEO - can access all schools in their region
                } else {
                    $assignedSchoolIds = \App\Models\MarkEntryAssignment::where([
                        'assigned_to' => $user->id,
                        'exam_year_id' => $selectedYearId,
                        'exam_type_id' => $psleExamTypeId,
                        'status' => 'active',
                    ])->pluck('school_id')->unique()->toArray();
                    $schoolsQuery->whereIn('id', $assignedSchoolIds);
                }
            }
            $schools = $schoolsQuery->orderBy('name')->get();
            $selectedSchoolId = $schools->contains('id', $selectedSchoolId) ? $selectedSchoolId : null;
        } else {
            if ($isMarkOfficer && !$isTrulyAdmin && !$user->region_id) {
                $assignedSchoolIds = \App\Models\MarkEntryAssignment::where([
                    'assigned_to' => $user->id,
                    'exam_year_id' => $selectedYearId,
                    'exam_type_id' => $psleExamTypeId,
                    'status' => 'active',
                ])->pluck('school_id')->unique()->toArray();
                
                $schools = \App\Models\School::whereIn('id', $assignedSchoolIds)
                    ->orderBy('name')
                    ->get();
                $selectedSchoolId = $schools->contains('id', $selectedSchoolId) ? $selectedSchoolId : null;
            } else {
                $selectedSchoolId = null;
            }
        }

        $psleSubjectsQuery = \App\Models\Subject::query()
            ->where('exam_type_id', $psleExamTypeId)
            ->where('is_active', true);
        if ($isMarkOfficer && !$isTrulyAdmin) {
            if ($user->region_id) {
                // Region-assigned MEO - can enter marks for all active subjects in scope
            } else {
                $assignedSubjectIds = \App\Models\MarkEntryAssignment::where([
                    'assigned_to' => $user->id,
                    'exam_year_id' => $selectedYearId,
                    'exam_type_id' => $psleExamTypeId,
                    'status' => 'active',
                ])->pluck('subject_id')->unique()->toArray();
                $psleSubjectsQuery->whereIn('id', $assignedSubjectIds);
            }
        }
        $psleSubjects = $psleSubjectsQuery->orderBy('code')->get();
        $selectedSubjectId = $this->normalizeOptionalId($request->query('subject_id'));

        $currentView = $request->query('view', 'overview');
        $allowedViews = [
            'overview', 'entry-validation', 'moderation-review', 'submission-locking', 'reports-exports', 'monitoring-audit',
            'start-entry', 'bulk-import', 'assignments', 'missing-marks', 'validation-errors', 'outliers', 'reports',
            'user-management', 'marking-centres', 'entry-sheet', 'subject-panel-assignments'
        ];
        if (!in_array($currentView, $allowedViews)) {
            $currentView = 'overview';
        }

        // Restrict user-management, marking-centres, and assignments to Admins/REOs
        if ($currentView === 'assignments' && !$isAdmin && !$isReo && !$isTrulyAdmin) {
            return redirect('/mark-entry/psle')->with('warning', 'Access denied. Only Administrators and Regional Officers can access the assignments page.');
        }

        if (($currentView === 'user-management' || $currentView === 'marking-centres' || $currentView === 'subject-panel-assignments' || $currentView === 'monitoring-audit') && !$isAdmin && !$isReo && !$isTrulyAdmin) {
            return redirect('/mark-entry/psle')->with('warning', 'Access denied.');
        }

        $activeFilters = [
            'exam_year_id' => $selectedYearId,
            'region_id' => $selectedRegionId,
            'district_id' => $selectedDistrictId,
            'school_id' => $selectedSchoolId,
            'subject_id' => $selectedSubjectId,
            'status' => $selectedStatus,
            'created_by' => $selectedCreatedBy,
        ];

        // Apply scopes to Summary Metrics (Cached to avoid massive live calculation overhead)
        $statsCacheKey = sprintf(
            'psle:dashboard_stats:%s:%s:%s:%s:%s',
            $selectedYearId ?? 'all',
            $selectedRegionId ?? 'all',
            $selectedDistrictId ?? 'all',
            $selectedSchoolId ?? 'all',
            $selectedSubjectId ?? 'all'
        );

        $cachedMetrics = \Illuminate\Support\Facades\Cache::remember($statsCacheKey, 30, function() use ($selectedYearId, $psleExamTypeId, $selectedRegionId, $selectedDistrictId, $selectedSchoolId, $selectedSubjectId, $psleSubjects) {
            $candidatesQuery = \App\Services\PsleCandidateRosterService::rosterQuery((int) $selectedYearId);

            if ($selectedRegionId) {
                $candidatesQuery->whereHas('school', function($q) use ($selectedRegionId) {
                    $q->where('region_id', $selectedRegionId);
                });
            }
            if ($selectedDistrictId) {
                $candidatesQuery->whereHas('school', function($q) use ($selectedDistrictId) {
                    $q->where('district_id', $selectedDistrictId);
                });
            }
            if ($selectedSchoolId) {
                $candidatesQuery->where('candidates.school_id', $selectedSchoolId);
            }

            $candidateCount = $candidatesQuery->count();
            
            $marksQuery = \App\Models\RawMark::whereHas('candidate', function($cq) use ($selectedYearId, $psleExamTypeId) {
                    $cq->whereHas('examRegistrations', function($rq) use ($selectedYearId, $psleExamTypeId) {
                        $rq->where('exam_type_id', $psleExamTypeId);
                        if ($selectedYearId) $rq->where('exam_year_id', $selectedYearId);
                    })
                    ->whereHas('school', function($sq) {
                        $sq->whereIn('school_type', ['PRIMARY', 'BOTH'])
                          ->where('education_level', 'PRIMARY');
                    });
                })
                ->whereHas('batch', function($q) use ($selectedYearId, $selectedRegionId, $selectedDistrictId, $selectedSchoolId, $selectedSubjectId) {
                    $q->whereHas('examType', fn($sq) => $sq->where('code', 'PSLE'));
                    
                    $yearLabel = \App\Models\ExamYear::where('id', $selectedYearId)->value('year_label');
                    if ($yearLabel) $q->where('exam_year', $yearLabel);
                    
                    if ($selectedRegionId) $q->where('region_id', $selectedRegionId);
                    if ($selectedDistrictId) $q->where('district_id', $selectedDistrictId);
                    if ($selectedSchoolId) $q->where('school_id', $selectedSchoolId);
                    if ($selectedSubjectId) $q->where('subject_id', $selectedSubjectId);
                })
                ->where(function($q) {
                    $q->whereNotNull('paper_1_marks')
                      ->orWhereNotNull('paper_2_marks')
                      ->orWhereNotNull('paper_3_marks')
                      ->orWhereNotNull('practical_marks')
                      ->orWhereNotNull('project_marks')
                      ->orWhereNotNull('subject_status');
                });

            $enteredMarksCount = $marksQuery->count();

            // Missing Marks Calculation
            $missingMarksCount = 0;
            $regionMissingMarksCount = 0;
            $subjectStats = [];
            
            $regionCandidatesQuery = \App\Services\PsleCandidateRosterService::rosterQuery((int) $selectedYearId);
            if ($selectedRegionId) {
                $regionCandidatesQuery->whereHas('school', function($q) use ($selectedRegionId) {
                    $q->where('region_id', $selectedRegionId);
                });
            }
            $regionCandidateCount = $regionCandidatesQuery->count();

            foreach ($psleSubjects as $subject) {
                $sMarksQuery = \App\Models\RawMark::where('subject_id', $subject->id)
                    ->whereHas('candidate', function($cq) use ($selectedYearId, $psleExamTypeId) {
                        $cq->whereHas('examRegistrations', function($rq) use ($selectedYearId, $psleExamTypeId) {
                            $rq->where('exam_type_id', $psleExamTypeId);
                            if ($selectedYearId) $rq->where('exam_year_id', $selectedYearId);
                        })
                        ->whereHas('school', function($sq) {
                            $sq->whereIn('school_type', ['PRIMARY', 'BOTH'])
                              ->where('education_level', 'PRIMARY');
                        });
                    })
                    ->whereHas('batch', function($q) use ($selectedYearId, $selectedRegionId, $selectedDistrictId, $selectedSchoolId) {
                        $q->whereHas('examType', fn($sq) => $sq->where('code', 'PSLE'));
                        $yearLabel = \App\Models\ExamYear::where('id', $selectedYearId)->value('year_label');
                        if ($yearLabel) $q->where('exam_year', $yearLabel);
                        if ($selectedRegionId) $q->where('region_id', $selectedRegionId);
                        if ($selectedDistrictId) $q->where('district_id', $selectedDistrictId);
                        if ($selectedSchoolId) $q->where('school_id', $selectedSchoolId);
                    })
                    ->where(function($q) {
                        $q->whereNotNull('paper_1_marks')
                          ->orWhereNotNull('paper_2_marks')
                          ->orWhereNotNull('paper_3_marks')
                          ->orWhereNotNull('practical_marks')
                          ->orWhereNotNull('project_marks')
                          ->orWhereNotNull('subject_status');
                    });

                // Global region-wide query for the region summary card
                $gMarksQuery = \App\Models\RawMark::where('subject_id', $subject->id)
                    ->whereHas('candidate', function($cq) use ($selectedYearId, $psleExamTypeId) {
                        $cq->whereHas('examRegistrations', function($rq) use ($selectedYearId, $psleExamTypeId) {
                            $rq->where('exam_type_id', $psleExamTypeId);
                            if ($selectedYearId) $rq->where('exam_year_id', $selectedYearId);
                        })
                        ->whereHas('school', function($sq) {
                            $sq->whereIn('school_type', ['PRIMARY', 'BOTH'])
                              ->where('education_level', 'PRIMARY');
                        });
                    })
                    ->whereHas('batch', function($q) use ($selectedYearId, $selectedRegionId) {
                        $q->whereHas('examType', fn($sq) => $sq->where('code', 'PSLE'));
                        $yearLabel = \App\Models\ExamYear::where('id', $selectedYearId)->value('year_label');
                        if ($yearLabel) $q->where('exam_year', $yearLabel);
                        if ($selectedRegionId) $q->where('region_id', $selectedRegionId);
                    })
                    ->where(function($q) {
                        $q->whereNotNull('paper_1_marks')
                          ->orWhereNotNull('paper_2_marks')
                          ->orWhereNotNull('paper_3_marks')
                          ->orWhereNotNull('practical_marks')
                          ->orWhereNotNull('project_marks')
                          ->orWhereNotNull('subject_status');
                    });

                $enteredCount = $sMarksQuery->count();
                $missingCount = max(0, $candidateCount - $enteredCount);
                
                if (!$selectedSubjectId || (int)$selectedSubjectId === (int)$subject->id) {
                    $missingMarksCount += $missingCount;
                    $regionMissingMarksCount += max(0, $regionCandidateCount - $gMarksQuery->count());
                }

                $subjectStats[$subject->id] = [
                    'entered' => $enteredCount,
                    'missing' => $missingCount,
                    'outliers' => \App\Models\MarkEntryOutlier::where('subject_id', $subject->id)
                        ->when($selectedYearId, fn($q) => $q->where('exam_year_id', $selectedYearId))
                        ->when($selectedRegionId, fn($q) => $q->where('region_id', $selectedRegionId))
                        ->when($selectedDistrictId, fn($q) => $q->where('district_id', $selectedDistrictId))
                        ->when($selectedSchoolId, fn($q) => $q->where('school_id', $selectedSchoolId))
                        ->count()
                ];
            }

            // Outlier Count
            $outlierCount = 0;
            if (\Illuminate\Support\Facades\Schema::hasTable('mark_entry_outliers')) {
                $outlierCountQuery = \App\Models\MarkEntryOutlier::query();
                if ($selectedYearId) $outlierCountQuery->where('exam_year_id', $selectedYearId);
                if ($selectedRegionId) $outlierCountQuery->where('region_id', $selectedRegionId);
                if ($selectedDistrictId) $outlierCountQuery->where('district_id', $selectedDistrictId);
                if ($selectedSchoolId) $outlierCountQuery->where('school_id', $selectedSchoolId);
                if ($selectedSubjectId) $outlierCountQuery->where('subject_id', $selectedSubjectId);
                $outlierCount = $outlierCountQuery->count();
            }

            return [
                'candidateCount' => $candidateCount,
                'enteredMarksCount' => $enteredMarksCount,
                'missingMarksCount' => $missingMarksCount,
                'regionMissingMarksCount' => $regionMissingMarksCount,
                'outlierCount' => $outlierCount,
                'subjectStats' => $subjectStats,
            ];
        });

        $candidateCount = $cachedMetrics['candidateCount'];
        $enteredMarksCount = $cachedMetrics['enteredMarksCount'];
        $missingMarksCount = $cachedMetrics['missingMarksCount'];
        $regionMissingMarksCount = $cachedMetrics['regionMissingMarksCount'];
        $outlierCount = $cachedMetrics['outlierCount'];
        $subjectStats = $cachedMetrics['subjectStats'];

        // Cached regional progress
        $progressCacheKey = 'psle:regional_progress:' . ($selectedYearId ?? 'all') . ':' . ($selectedRegionId ?? 'all');
        $overviewRegionalProgress = $selectedYearId
            ? \Illuminate\Support\Facades\Cache::remember($progressCacheKey, 30, function() use ($selectedYearId, $selectedRegionId, $psleSubjects) {
                return $this->reportService->getOverviewRegionalProgress(
                    (int) $selectedYearId,
                    $selectedRegionId ? (int) $selectedRegionId : null,
                    max(1, $psleSubjects->count())
                );
            })
            : collect();

        // View Logic
        $assignment = null;
        $candidates = collect();
        $missingMarks = collect();
        
        if ($currentView === 'entry-sheet') {
            $assignmentId = $request->query('assignment_id');
            $schoolId = $request->query('school_id');
            $subjectId = $request->query('subject_id');
            $requestedDistrictId = $request->query('district_id');

            if ($assignmentId) {
                $assignment = \App\Models\MarkEntryAssignment::with(['school', 'subject', 'markingCentre'])->find($assignmentId);
                
                if (!$assignment || $assignment->status !== 'active' || (!$isAdmin && $assignment->assigned_to !== $user->id && !$isReo)) {
                    return redirect('/mark-entry/psle?view=start-entry')->with('error', 'Unauthorized or invalid assignment.');
                }
                
                $schoolId = $assignment->school_id;
                $subjectId = $assignment->subject_id;
                $requestedDistrictId = $assignment->district_id;
            } else {
                // Region-based access for Officers/REOs
                if (!$selectedYearId || !$schoolId || !$subjectId) {
                    return redirect('/mark-entry/psle?view=start-entry')->with('error', 'Please select an exam year, school, and subject.');
                }

                $targetSchool = \App\Models\School::find($schoolId);
                if (! $targetSchool) {
                    return redirect('/mark-entry/psle?view=start-entry')->with('error', 'Please select a valid school.');
                }

                if ($requestedDistrictId && (int) $targetSchool->district_id !== (int) $requestedDistrictId) {
                    return redirect('/mark-entry/psle?view=start-entry')->with('error', 'The selected school does not belong to the selected district.');
                }

                if (!$isAdmin && $targetSchool->region_id !== $user->region_id) {
                    return redirect('/mark-entry/psle?view=start-entry')->with('error', 'Unauthorized: This school is outside your assigned region.');
                }

                if (! $psleSubjects->contains('id', (int) $subjectId)) {
                    return redirect('/mark-entry/psle?view=start-entry')->with('error', 'Please select a valid PSLE subject.');
                }

                if ($isMarkOfficer && !$isTrulyAdmin) {
                    $hasRegionalAssignment = ($user->region_id && (int) $targetSchool->region_id === (int) $user->region_id);
                    if (!$hasRegionalAssignment) {
                        $hasAssignment = \App\Models\MarkEntryAssignment::where([
                            'assigned_to' => $user->id,
                            'school_id' => $schoolId,
                            'subject_id' => $subjectId,
                            'exam_year_id' => $selectedYearId,
                            'exam_type_id' => $psleExamTypeId,
                            'status' => 'active',
                        ])->exists();

                        if (!$hasAssignment) {
                            return redirect('/mark-entry/psle?view=start-entry')->with('error', 'Unauthorized: You are not assigned to enter marks for this school and subject.');
                        }
                    }
                }
            }

            $selectedSubjectId = $subjectId;
            $selectedSchoolId = $schoolId;
            $selectedDistrictId = $requestedDistrictId ?: \App\Models\School::whereKey($schoolId)->value('district_id');
            $activeFilters['school_id'] = $selectedSchoolId;
            $activeFilters['subject_id'] = $selectedSubjectId;
            $activeFilters['district_id'] = $selectedDistrictId;

            \Log::info('PSLE mark entry sheet opening.', [
                'exam_year_id' => $selectedYearId,
                'district_id' => $selectedDistrictId,
                'school_id' => $schoolId,
                'subject_id' => $subjectId,
                'assignment_id' => $assignment?->id,
                'user_id' => $user->id,
            ]);

            $candidates = \App\Services\PsleCandidateRosterService::rosterQuery($selectedYearId, $schoolId)
                ->with(['examRegistrations' => fn($q) => $q->where('exam_type_id', $psleExamTypeId)->where('exam_year_id', $selectedYearId)])
                ->with(['rawMarks' => function($q) use ($subjectId, $selectedYearId) {
                    $q->where('subject_id', $subjectId)
                        ->where(function ($yearQuery) use ($selectedYearId) {
                            $yearQuery->where('exam_year_id', $selectedYearId)
                                ->orWhereHas('batch', fn($batchQuery) => $batchQuery->where('exam_year_id', $selectedYearId));
                        })
                        ->with('batch')
                        ->orderByRaw('CASE WHEN exam_year_id IS NOT NULL THEN 0 ELSE 1 END')
                        ->latest('updated_at');
                }])
                ->orderBy('candidates.candidate_id', 'asc')
                ->get();
            
            // Deduplicate loaded candidates prioritizing the correct school code prefix
            $schoolModel = \App\Models\School::find($schoolId);
            $candidates = \App\Services\PsleCandidateRosterService::deduplicate($candidates, $schoolModel ? $schoolModel->code : '');

            $candidateIds = $candidates->pluck('id');
            $existingMarksCount = \App\Models\RawMark::query()
                ->whereIn('candidate_id', $candidateIds)
                ->where('subject_id', $subjectId)
                ->where(function ($yearQuery) use ($selectedYearId) {
                    $yearQuery->where('exam_year_id', $selectedYearId)
                        ->orWhereHas('batch', fn($batchQuery) => $batchQuery->where('exam_year_id', $selectedYearId));
                })
                ->count();

            $candidates->each(function ($candidate) use ($selectedYearId) {
                $candidate->rawMarks->each(function ($mark) use ($selectedYearId) {
                    if (!$mark->exam_year_id && $mark->batch?->exam_year_id == $selectedYearId) {
                        $mark->setAttribute('exam_year_id', $selectedYearId);
                    }
                });
            });

            // Calculate diagnosticsMeta
            $adminRegisteredCount = \App\Services\PsleCandidateRosterService::getDeduplicatedCount($selectedYearId, $schoolId);
            $meoRosterCount = $candidates->count();
            $directCandidatesCount = \App\Models\Candidate::where('school_id', $schoolId)->count();
            $candidatesWithMissingExamRegistration = \App\Models\Candidate::where('school_id', $schoolId)
                ->whereDoesntHave('examRegistrations', function ($q) use ($psleExamTypeId, $selectedYearId) {
                    $q->where('exam_type_id', $psleExamTypeId)
                      ->where('exam_year_id', $selectedYearId);
                })
                ->count();
            $candidatesWithExistingMarks = \App\Models\RawMark::where('school_id', $schoolId)
                ->where('subject_id', $subjectId)
                ->where('exam_year_id', $selectedYearId)
                ->distinct('candidate_id')
                ->count();

            $diagnosticsMeta = [
                'admin_registered_count' => $adminRegisteredCount,
                'meo_roster_count' => $meoRosterCount,
                'direct_candidates_count' => $directCandidatesCount,
                'candidates_with_missing_exam_registration' => $candidatesWithMissingExamRegistration,
                'candidates_with_existing_marks' => $candidatesWithExistingMarks,
                'school_id' => $schoolId,
                'school_code' => \App\Models\School::whereKey($schoolId)->value('code'),
                'exam_year_id' => $selectedYearId,
                'subject_id' => $subjectId,
            ];

            if ($adminRegisteredCount !== $meoRosterCount) {
                if (app()->environment('local')) {
                    throw new \RuntimeException("MEO Roster and Admin Roster mismatch! MEO has $meoRosterCount candidates, Admin has $adminRegisteredCount candidates for school ID $schoolId.");
                } else {
                    \Log::error("MEO Roster and Admin Roster mismatch! MEO: $meoRosterCount, Admin: $adminRegisteredCount for school ID $schoolId.");
                }
            }

            \Log::info('PSLE mark entry sheet roster loaded.', [
                'exam_year_id' => $selectedYearId,
                'school_id' => $schoolId,
                'subject_id' => $subjectId,
                'registered_candidates_found' => $candidates->count(),
                'existing_marks_found' => $existingMarksCount,
            ]);
        } elseif ($currentView === 'missing-marks') {
            $classification = $request->query('classification', 'all');
            $activeFilters['classification'] = $classification;

            $missingMarksService = app(\App\Services\MarkEntry\PsleMissingMarksService::class);

            if ($selectedSchoolId) {
                $schoolModel = \App\Models\School::findOrFail($selectedSchoolId);
                $schoolDetails = $missingMarksService->getSchoolDetails($schoolModel, $activeFilters, $user);
            } else {
                $schoolSummariesCollection = $missingMarksService->getSchoolSummaries($activeFilters, $user);
                
                $perPage = 20;
                $page = $request->query('page', 1);
                $schoolSummaries = new \Illuminate\Pagination\LengthAwarePaginator(
                    $schoolSummariesCollection->forPage($page, $perPage)->values(),
                    $schoolSummariesCollection->count(),
                    $perPage,
                    $page,
                    ['path' => $request->url(), 'query' => $request->query()]
                );
            }

            // Find candidates who are registered but have no marks in RawMark for those subjects
            $psleSubjectIds = $psleSubjects->pluck('id')->toArray();
            
            $missingMarksQuery = \App\Models\Candidate::whereHas('examRegistrations', function($q) use ($selectedYearId, $psleExamTypeId) {
                    $q->where('exam_type_id', $psleExamTypeId);
                    if ($selectedYearId) $q->where('exam_year_id', $selectedYearId);
                })
                ->whereHas('school', function($q) use ($selectedRegionId, $selectedDistrictId, $selectedSchoolId) {
                    $q->whereIn('school_type', ['PRIMARY', 'BOTH'])
                      ->where('education_level', 'PRIMARY');
                    if ($selectedRegionId) $q->where('region_id', $selectedRegionId);
                    if ($selectedDistrictId) $q->where('district_id', $selectedDistrictId);
                    if ($selectedSchoolId) $q->where('id', $selectedSchoolId);
                });

            if ($selectedSubjectId) {
                // Specific subject missing
                $missingMarksQuery->whereDoesntHave('rawMarks', function($mq) use ($selectedSubjectId, $selectedYearId) {
                    $mq->where('subject_id', $selectedSubjectId)
                       ->whereHas('batch', fn($bq) => $bq->where('exam_year_id', $selectedYearId));
                });
            } else {
                // Any PSLE subject missing
                $missingMarksQuery->where(function($q) use ($psleSubjectIds, $selectedYearId) {
                    foreach ($psleSubjectIds as $sid) {
                        $q->orWhereDoesntHave('rawMarks', function($mq) use ($sid, $selectedYearId) {
                            $mq->where('subject_id', $sid)
                               ->whereHas('batch', fn($bq) => $bq->where('exam_year_id', $selectedYearId));
                        });
                    }
                });
            }

            $missingMarks = $missingMarksQuery->with(['school', 'rawMarks' => function($mq) use ($selectedYearId) {
                    if ($selectedYearId) {
                        $mq->whereHas('batch', fn($bq) => $bq->where('exam_year_id', $selectedYearId));
                    }
                }])
                ->paginate(20)->withQueryString();
        } elseif ($currentView === 'submission-locking' || $currentView === 'moderation-review') {
            $batchesQuery = \App\Models\MarkImportBatch::with(['school', 'subject', 'user', 'assignment.assignedTo'])
                ->withCount(['rawMarks as marks_count'])
                ->where('exam_type_id', $psleExamTypeId);
            
            if ($selectedYearId) $batchesQuery->where('exam_year_id', $selectedYearId);
            if ($selectedRegionId) $batchesQuery->where('region_id', $selectedRegionId);
            if ($selectedDistrictId) $batchesQuery->where('district_id', $selectedDistrictId);
            if ($selectedSchoolId) $batchesQuery->where('school_id', $selectedSchoolId);
            if ($selectedSubjectId) $batchesQuery->where('subject_id', $selectedSubjectId);

            if ($selectedStatus) {
                $batchesQuery->where('status', $selectedStatus);
            }
            if ($selectedCreatedBy) {
                $batchesQuery->where('created_by', $selectedCreatedBy);
            }

            if ($currentView === 'moderation-review') {
                $batchesQuery->whereIn('status', ['submitted', 'approved', 'rejected']);
            } else {
                // submission-locking
                if ($isMarkOfficer && !$isAdmin) {
                    $batchesQuery->where('created_by', $user->id);
                }
            }

            $batches = $batchesQuery->latest()->paginate(15)->withQueryString();

            // Calculate whole-scoped statistics
            $statsQuery = \App\Models\MarkImportBatch::query()
                ->where('exam_type_id', $psleExamTypeId);
            if ($selectedYearId) $statsQuery->where('exam_year_id', $selectedYearId);
            if ($selectedRegionId) $statsQuery->where('region_id', $selectedRegionId);
            if ($selectedDistrictId) $statsQuery->where('district_id', $selectedDistrictId);
            if ($selectedSchoolId) $statsQuery->where('school_id', $selectedSchoolId);
            if ($selectedSubjectId) $statsQuery->where('subject_id', $selectedSubjectId);
            if ($selectedCreatedBy) $statsQuery->where('created_by', $selectedCreatedBy);

            if ($currentView === 'moderation-review') {
                $moderationStats = [
                    'pending' => (clone $statsQuery)->where('status', 'submitted')->count(),
                    'approved' => (clone $statsQuery)->where('status', 'approved')->count(),
                    'rejected' => (clone $statsQuery)->where('status', 'rejected')->count(),
                ];
            } else {
                // submission-locking
                if ($isMarkOfficer && !$isAdmin) {
                    $statsQuery->where('created_by', $user->id);
                }
                $lockingStats = [
                    'draft' => (clone $statsQuery)->where('status', 'draft')->count(),
                    'pending' => (clone $statsQuery)->where('status', 'submitted')->count(),
                    'locked' => (clone $statsQuery)->where('status', 'locked')->count(),
                ];
            }

            // Load officers for filtering
            $officersQuery = \App\Models\User::where(function($q) {
                $q->whereHas('role', fn($rq) => $rq->whereIn('code', ['mark_officer', 'mark_entry_officer', 'meo'])->orWhere('name', 'Mark Entry Officer'))
                  ->orWhereIn('portal_role', ['mark_officer', 'mark_entry_officer', 'meo']);
            });
            $officersQuery->where('status', 'active');
            if ($allowedRegionId) {
                $officersQuery->where('region_id', $allowedRegionId);
            }
            $officers = $officersQuery->orderBy('name')->get();
        } elseif ($currentView === 'outliers') {
            $outliersQuery = \App\Models\MarkEntryOutlier::with(['candidate', 'school', 'subject', 'officer', 'rawMark']);
            
            if ($selectedYearId) $outliersQuery->where('exam_year_id', $selectedYearId);
            if ($selectedRegionId) $outliersQuery->where('region_id', $selectedRegionId);
            if ($selectedDistrictId) $outliersQuery->where('district_id', $selectedDistrictId);
            if ($selectedSchoolId) $outliersQuery->where('school_id', $selectedSchoolId);
            if ($selectedSubjectId) $outliersQuery->where('subject_id', $selectedSubjectId);
            
            if ($request->query('severity')) $outliersQuery->where('severity', $request->query('severity'));
            if ($request->query('status')) $outliersQuery->where('status', $request->query('status'));
            if ($request->query('officer_id')) $outliersQuery->where('officer_id', $request->query('officer_id'));

            // Role Scoping for Outliers - Phase 12: Show outliers inside assigned region
            if ($isMarkOfficer && !$isAdmin) {
                $outliersQuery->where('region_id', $user->region_id);
            } elseif ($isReo && !$isAdmin) {
                $outliersQuery->where('region_id', $user->region_id);
            }

            $outliers = collect();
            if (\Illuminate\Support\Facades\Schema::hasTable('mark_entry_outliers')) {
                $outliers = $outliersQuery->latest()->paginate(15)->withQueryString();
            }
            
            // Outlier Stats
            $outlierStats = [
                'total' => 0, 'high' => 0, 'low' => 0, 'patterns' => 0, 'verified' => 0, 'pending' => 0
            ];
        } elseif ($currentView === 'entry-validation') {
            $validationSummary = [
                'registered' => $candidateCount,
                'with_marks' => $enteredMarksCount,
                'pending' => max(0, $candidateCount - $enteredMarksCount),
                'missing' => $missingMarksCount,
                'invalid' => 0,
                'abs' => 0,
                'warnings' => 0,
                'ready_for_review' => 0,
            ];

            $yearLabel = \App\Models\ExamYear::where('id', $selectedYearId)->value('year_label');

            // ABS Count
            $absQuery = \App\Models\RawMark::where('subject_status', 'ABS')
                ->whereHas('batch', function($q) use ($yearLabel, $selectedRegionId, $selectedDistrictId, $selectedSchoolId, $selectedSubjectId) {
                    $q->whereHas('examType', fn($sq) => $sq->where('code', 'PSLE'));
                    if ($yearLabel) $q->where('exam_year', $yearLabel);
                    if ($selectedRegionId) $q->where('region_id', $selectedRegionId);
                    if ($selectedDistrictId) $q->where('district_id', $selectedDistrictId);
                    if ($selectedSchoolId) $q->where('school_id', $selectedSchoolId);
                    if ($selectedSubjectId) $q->where('subject_id', $selectedSubjectId);
                });
            $validationSummary['abs'] = $absQuery->count();

            if (\Illuminate\Support\Facades\Schema::hasTable('mark_entry_validations')) {
                $vQuery = \App\Models\MarkEntryValidation::query()
                    ->where('status', 'open')
                    ->when($selectedYearId, fn($q) => $q->where('exam_year_id', $selectedYearId))
                    ->when($selectedRegionId, fn($q) => $q->where('region_id', $selectedRegionId))
                    ->when($selectedDistrictId, fn($q) => $q->where('district_id', $selectedDistrictId))
                    ->when($selectedSchoolId, fn($q) => $q->where('school_id', $selectedSchoolId))
                    ->when($selectedSubjectId, fn($q) => $q->where('subject_id', $selectedSubjectId));
                
                $validationSummary['invalid'] = (clone $vQuery)->count();
                $validationSummary['warnings'] = (clone $vQuery)->whereIn('severity', ['low', 'medium'])->count();
            }

            // Subject Entry Status Table
            $schoolsQuery = \App\Models\School::query()
                ->whereIn('school_type', ['PRIMARY', 'BOTH'])
                ->when($selectedRegionId, fn($q) => $q->where('region_id', $selectedRegionId))
                ->when($selectedDistrictId, fn($q) => $q->where('district_id', $selectedDistrictId))
                ->when($selectedSchoolId, fn($q) => $q->where('id', $selectedSchoolId))
                ->orderBy('name');
            
            $paginatedSchools = $schoolsQuery->paginate(15)->withQueryString();
            
            $subjectEntryStatus = [];
            foreach ($paginatedSchools as $school) {
                $subjectsToScan = $selectedSubjectId 
                    ? \App\Models\Subject::where('id', $selectedSubjectId)->get() 
                    : $psleSubjects;
                
                foreach ($subjectsToScan as $subject) {
                    $regCount = \App\Services\PsleCandidateRosterService::getDeduplicatedCount($selectedYearId, $school->id);
                    
                    $entCount = \App\Models\RawMark::where('school_id', $school->id)
                        ->where('subject_id', $subject->id)
                        ->whereHas('batch', function($q) use ($yearLabel) {
                            if ($yearLabel) $q->where('exam_year', $yearLabel);
                        })
                        ->count();
                    
                    $invCount = 0;
                    if (\Illuminate\Support\Facades\Schema::hasTable('mark_entry_validations')) {
                        $invCount = \App\Models\MarkEntryValidation::where('school_id', $school->id)
                            ->where('subject_id', $subject->id)
                            ->where('exam_year_id', $selectedYearId)
                            ->where('status', 'open')
                            ->count();
                    }
                    
                    $abCount = \App\Models\RawMark::where('school_id', $school->id)
                        ->where('subject_id', $subject->id)
                        ->where('subject_status', 'ABS')
                        ->whereHas('batch', function($q) use ($yearLabel) {
                            if ($yearLabel) $q->where('exam_year', $yearLabel);
                        })
                        ->count();
                    
                    $missCount = max(0, $regCount - $entCount);
                    $prog = $regCount > 0 ? round(($entCount / $regCount) * 100) : 0;
                    
                    $st = 'Not Started';
                    $stB = 'badge-outline';
                    
                    if ($entCount > 0) {
                        if ($missCount > 0) {
                            $st = 'In Progress';
                            $stB = 'badge-yellow';
                        } elseif ($invCount > 0) {
                            $st = 'Has Errors';
                            $stB = 'badge-red';
                        } else {
                            $st = 'Ready for Review';
                            $stB = 'badge-green';
                        }
                    }
                    
                    $subjectEntryStatus[] = [
                        'school' => $school,
                        'subject' => $subject,
                        'registered' => $regCount,
                        'entered' => $entCount,
                        'missing' => $missCount,
                        'invalid' => $invCount,
                        'abs' => $abCount,
                        'progress' => $prog,
                        'status' => $st,
                        'status_badge' => $stB,
                    ];
                }
            }
            $validationSummary['ready_for_review'] = collect($subjectEntryStatus)->where('status', 'Ready for Review')->count();

        } elseif ($currentView === 'validation-errors') {
            $validationErrors = collect();
            $validationStats = [];
            
            if (\Illuminate\Support\Facades\Schema::hasTable('mark_entry_validations')) {
                $validationErrors = $this->validationService->getValidationErrors($activeFilters, $user);
                
                // Validation Stats
                $valStatsQuery = \App\Models\MarkEntryValidation::query();
                if ($selectedYearId) $valStatsQuery->where('exam_year_id', $selectedYearId);
                if ($selectedRegionId) $valStatsQuery->where('region_id', $selectedRegionId);
                if ($selectedDistrictId) $valStatsQuery->where('district_id', $selectedDistrictId);
                if ($selectedSchoolId) $valStatsQuery->where('school_id', $selectedSchoolId);
                if ($selectedSubjectId) $valStatsQuery->where('subject_id', $selectedSubjectId);
                
                $validationStats = [
                    'total' => (clone $valStatsQuery)->where('status', 'open')->count(),
                    'invalid_marks' => (clone $valStatsQuery)->where('status', 'open')->whereIn('error_type', ['Invalid Mark', 'Wrong Format', 'Mark Above Maximum', 'Negative Mark'])->count(),
                    'duplicates' => (clone $valStatsQuery)->where('status', 'open')->where('error_type', 'Duplicate Entry')->count(),
                    'resolved' => (clone $valStatsQuery)->where('status', 'resolved')->count(),
                    'abs_conflicts' => (clone $valStatsQuery)->where('status', 'open')->where('error_type', 'ABS Conflict')->count(),
                    'critical' => (clone $valStatsQuery)->where('status', 'open')->where('severity', 'critical')->count(),
                ];
            }
        } elseif ($currentView === 'reports' || $currentView === 'reports-exports') {
            // Summary for the reports page using pre-calculated metrics
            $reportSummary = [
                'total_candidates' => $candidateCount,
                'total_marks' => $enteredMarksCount,
                'total_outliers' => $outlierCount,
                'total_missing' => $missingMarksCount,
            ];
        } elseif ($currentView === 'monitoring-audit') {
            // Monitoring & Audit Data
            $monitoringOfficerId = ($isMarkOfficer && !$isAdmin) ? (int) $user->id : null;
            
            // Pass all active filters to reportService methods
            $productivityStats = $this->reportService->getOfficerProductivity(
                (int) $selectedYearId,
                $selectedRegionId ? (int) $selectedRegionId : null,
                $monitoringOfficerId,
                $selectedDistrictId ? (int) $selectedDistrictId : null,
                $selectedSchoolId ? (int) $selectedSchoolId : null,
                $selectedSubjectId ? (int) $selectedSubjectId : null
            );
            
            $regionalProgress = $this->reportService->getRegionalProgress((int) $selectedYearId, $selectedRegionId ? (int) $selectedRegionId : null);
            
            $recentActivity = $this->reportService->getRecentActivity(
                (int) $selectedYearId,
                $selectedRegionId ? (int) $selectedRegionId : null,
                20,
                $monitoringOfficerId,
                $selectedDistrictId ? (int) $selectedDistrictId : null,
                $selectedSchoolId ? (int) $selectedSchoolId : null,
                $selectedSubjectId ? (int) $selectedSubjectId : null
            );
            
            $batchActivity = $this->reportService->getBatchActivity(
                (int) $selectedYearId,
                $selectedRegionId ? (int) $selectedRegionId : null,
                20,
                $monitoringOfficerId,
                $selectedDistrictId ? (int) $selectedDistrictId : null,
                $selectedSchoolId ? (int) $selectedSchoolId : null,
                $selectedSubjectId ? (int) $selectedSubjectId : null
            );
            
            $auditTrail = $this->reportService->getAuditTrail(
                (int) $selectedYearId,
                $selectedRegionId ? (int) $selectedRegionId : null,
                20,
                $monitoringOfficerId,
                $selectedDistrictId ? (int) $selectedDistrictId : null,
                $selectedSchoolId ? (int) $selectedSchoolId : null,
                $selectedSubjectId ? (int) $selectedSubjectId : null
            );

            // Active officers count dynamically filtered
            $officersCountQuery = \App\Models\User::where(function($query) {
                    $query->whereHas('role', fn($q) => $q->whereIn('code', ['mark_officer', 'mark_entry_officer', 'meo'])->orWhere('name', 'Mark Entry Officer'))
                        ->orWhereIn('portal_role', ['mark_officer', 'mark_entry_officer', 'meo']);
                })
                ->where('status', 'active')
                ->when($selectedRegionId, fn($q) => $q->where('region_id', $selectedRegionId));

            if ($selectedDistrictId || $selectedSchoolId || $selectedSubjectId) {
                $assignedMeoIds = \App\Models\MarkEntryAssignment::where('exam_year_id', $selectedYearId)
                    ->where('status', 'active')
                    ->when($selectedDistrictId, fn($q) => $q->where('district_id', $selectedDistrictId))
                    ->when($selectedSchoolId, fn($q) => $q->where('school_id', $selectedSchoolId))
                    ->when($selectedSubjectId, fn($q) => $q->where('subject_id', $selectedSubjectId))
                    ->pluck('assigned_to')
                    ->unique()
                    ->toArray();
                $officersCountQuery->whereIn('id', $assignedMeoIds);
            }
            $activeOfficersCount = $officersCountQuery->count();

            // Marks Today count dynamically filtered
            $marksTodayQuery = \App\Models\RawMark::whereDate('updated_at', now()->today())
                ->where('exam_year_id', $selectedYearId)
                ->whereHas('candidate', function($cq) use ($psleExamTypeId) {
                    $cq->whereHas('examRegistrations', fn($rq) => $rq->where('exam_type_id', $psleExamTypeId));
                })
                ->whereHas('batch', function($bq) use ($selectedRegionId, $selectedDistrictId, $selectedSchoolId, $selectedSubjectId) {
                    if ($selectedRegionId) $bq->where('region_id', $selectedRegionId);
                    if ($selectedDistrictId) $bq->where('district_id', $selectedDistrictId);
                    if ($selectedSchoolId) $bq->where('school_id', $selectedSchoolId);
                    if ($selectedSubjectId) $bq->where('subject_id', $selectedSubjectId);
                });
            $marksTodayCount = $marksTodayQuery->count();

            // Submitted batches count dynamically filtered
            $submittedBatchesQuery = \App\Models\MarkImportBatch::where('exam_type_id', $psleExamTypeId)
                ->whereIn('status', ['submitted', 'approved', 'locked'])
                ->when($selectedYearId, function($q) use ($selectedYearId) {
                     $yearLabel = \App\Models\ExamYear::where('id', $selectedYearId)->value('year_label');
                     if ($yearLabel) $q->where('exam_year', $yearLabel);
                })
                ->when($selectedRegionId, fn($q) => $q->where('region_id', $selectedRegionId))
                ->when($selectedDistrictId, fn($q) => $q->where('district_id', $selectedDistrictId))
                ->when($selectedSchoolId, fn($q) => $q->where('school_id', $selectedSchoolId))
                ->when($selectedSubjectId, fn($q) => $q->where('subject_id', $selectedSubjectId));
            $submittedBatchesCount = $submittedBatchesQuery->count();

            // Validation Runs count dynamically filtered
            $validationRunsQuery = \App\Models\SystemEventLog::where('action', 'validation_run')
                ->when($selectedRegionId, function($q) use ($selectedRegionId) {
                    $q->where(function($sub) use ($selectedRegionId) {
                        $sub->whereHas('actor', fn($aq) => $aq->where('region_id', $selectedRegionId))
                            ->orWhere('context', 'like', '%"region_id":' . $selectedRegionId . '%')
                            ->orWhere('context', 'like', '%"region_id": "' . $selectedRegionId . '"%')
                            ->orWhere('context', 'like', '%"region_id":' . json_encode($selectedRegionId) . '%');
                    });
                })
                ->when($selectedDistrictId, function($q) use ($selectedDistrictId) {
                    $q->where(function($sub) use ($selectedDistrictId) {
                        $sub->where('context', 'like', '%"district_id":' . $selectedDistrictId . '%')
                            ->orWhere('context', 'like', '%"district_id": "' . $selectedDistrictId . '"%')
                            ->orWhere('context', 'like', '%"district_id":' . json_encode($selectedDistrictId) . '%');
                    });
                })
                ->when($selectedSchoolId, function($q) use ($selectedSchoolId) {
                    $q->where(function($sub) use ($selectedSchoolId) {
                        $sub->where('context', 'like', '%"school_id":' . $selectedSchoolId . '%')
                            ->orWhere('context', 'like', '%"school_id": "' . $selectedSchoolId . '"%')
                            ->orWhere('context', 'like', '%"school_id":' . json_encode($selectedSchoolId) . '%');
                    });
                })
                ->when($selectedSubjectId, function($q) use ($selectedSubjectId) {
                    $q->where(function($sub) use ($selectedSubjectId) {
                        $sub->where('context', 'like', '%"subject_id":' . $selectedSubjectId . '%')
                            ->orWhere('context', 'like', '%"subject_id": "' . $selectedSubjectId . '"%')
                            ->orWhere('context', 'like', '%"subject_id":' . json_encode($selectedSubjectId) . '%');
                    });
                });
            $validationRunsCount = $validationRunsQuery->count();

            // Audit events count dynamically filtered using event category and context filters
            $auditEventsQuery = \App\Models\SystemEventLog::query()
                ->where(function ($query) {
                    $query->whereIn('category', [
                        \App\Models\SystemEventLog::CAT_IMPORT,
                        \App\Models\SystemEventLog::CAT_MODERATION,
                        \App\Models\SystemEventLog::CAT_SUBMISSION,
                        \App\Models\SystemEventLog::CAT_LOCKING,
                        \App\Models\SystemEventLog::CAT_EXPORT,
                        \App\Models\SystemEventLog::CAT_ADMIN,
                    ])->orWhere('action', 'like', 'psle_batch_%');
                });
            if ($selectedRegionId) {
                $auditEventsQuery->where(function($q) use ($selectedRegionId) {
                    $q->whereHas('actor', fn($aq) => $aq->where('region_id', $selectedRegionId))
                      ->orWhere('context', 'like', '%"region_id":' . $selectedRegionId . '%')
                      ->orWhere('context', 'like', '%"region_id": "' . $selectedRegionId . '"%')
                      ->orWhere('context', 'like', '%"region_id":' . json_encode($selectedRegionId) . '%');
                });
            }
            if ($selectedDistrictId) {
                $auditEventsQuery->where(function($q) use ($selectedDistrictId) {
                    $q->whereHas('actor', fn($aq) => $aq->where('district_council_id', $selectedDistrictId))
                      ->orWhere('context', 'like', '%"district_id":' . $selectedDistrictId . '%')
                      ->orWhere('context', 'like', '%"district_id": "' . $selectedDistrictId . '"%')
                      ->orWhere('context', 'like', '%"district_id":' . json_encode($selectedDistrictId) . '%');
                });
            }
            if ($selectedSchoolId) {
                $auditEventsQuery->where(function($q) use ($selectedSchoolId) {
                    $q->where('context', 'like', '%"school_id":' . $selectedSchoolId . '%')
                      ->orWhere('context', 'like', '%"school_id": "' . $selectedSchoolId . '"%')
                      ->orWhere('context', 'like', '%"school_id":' . json_encode($selectedSchoolId) . '%');
                });
            }
            if ($selectedSubjectId) {
                $auditEventsQuery->where(function($q) use ($selectedSubjectId) {
                    $q->where('context', 'like', '%"subject_id":' . $selectedSubjectId . '%')
                      ->orWhere('context', 'like', '%"subject_id": "' . $selectedSubjectId . '"%')
                      ->orWhere('context', 'like', '%"subject_id":' . json_encode($selectedSubjectId) . '%');
                });
            }
            $auditEventsCount = $auditEventsQuery->count();

            $monitoringSummary = [
                'active_officers' => $activeOfficersCount,
                'marks_today' => $marksTodayCount,
                'total_marks' => $enteredMarksCount,
                'pending_marks' => $missingMarksCount,
                'submitted_batches' => $submittedBatchesCount,
                'validation_runs' => $validationRunsCount,
                'audit_events' => $auditEventsCount,
            ];
        }

        $portalUsers = [];
        $userCounts = [];
        $markingCentres = [];
        $districtCouncils = collect();
        $assignments = [];
        $officers = [];
        $panelLeaders = collect();
        $assignmentDistricts = collect();
        $assignmentSchools = collect();

        if ($currentView === 'subject-panel-assignments') {
            $assignmentsQuery = \App\Models\SubjectPanelAssignment::with(['user', 'subject', 'examYear', 'region', 'createdBy'])
                ->latest();
            
            if ($isReo && !$isTrulyAdmin) {
                $assignmentsQuery->where(function($q) use ($allowedRegionId) {
                    $q->where('region_id', $allowedRegionId)->orWhereNull('region_id');
                });
            }

            $assignments = $assignmentsQuery->paginate(30)->withQueryString();

            $panelLeaders = \App\Models\User::where('portal_role', 'subject_panel_leader')
                ->where('status', 'active')
                ->orderBy('name')->get();
        }

        if ($currentView === 'user-management' || $currentView === 'marking-centres' || $currentView === 'assignments') {
            $userSearch = trim((string) $request->query('user_search', ''));
            $portalUsersQuery = \App\Models\User::with(['role', 'region', 'markingCentre', 'council'])
                ->where(function($q) {
                    $q->whereHas('role', function($rq) {
                        $rq->whereIn('code', ['reo', 'centre_verifier', 'mark_officer', 'mark_entry_officer', 'meo', 'mock_rao', 'rao', 'subject_panel_leader'])
                            ->orWhereIn('name', ['Regional Education Officer', 'Marking Centre Verifier', 'Mark Entry Officer', 'Regional Academic Officer', 'Subject Panel Leader']);
                    })
                    ->orWhereIn('portal_role', ['reo', 'centre_verifier', 'mark_officer', 'mark_entry_officer', 'meo', 'mock_rao', 'rao', 'subject_panel_leader']);
                });
            if ($userSearch !== '') {
                $portalUsersQuery->where(function($q) use ($userSearch) {
                    $q->where('name', 'like', "%{$userSearch}%")
                        ->orWhere('email', 'like', "%{$userSearch}%")
                        ->orWhere('phone', 'like', "%{$userSearch}%")
                        ->orWhereHas('role', fn($rq) => $rq->where('name', 'like', "%{$userSearch}%"))
                        ->orWhereHas('region', fn($rq) => $rq->where('name', 'like', "%{$userSearch}%"))
                        ->orWhereHas('council', fn($cq) => $cq->where('name', 'like', "%{$userSearch}%"))
                        ->orWhereHas('markingCentre', fn($mq) => $mq->where('name', 'like', "%{$userSearch}%"));
                });
            }
            if ($allowedRegionId) {
                $portalUsersQuery->where('region_id', $allowedRegionId);
            } elseif ($isReo && !$isTrulyAdmin) {
                $portalUsersQuery->whereRaw('1 = 0');
            }
            $portalUsers = $portalUsersQuery
                ->latest()
                ->paginate(50)
                ->withQueryString();

            $roles = \App\Models\Role::where(function($q) {
                $q->whereIn('code', ['reo', 'centre_verifier', 'mark_officer', 'mark_entry_officer', 'meo', 'subject_panel_leader'])
                    ->orWhereIn('name', ['Regional Education Officer', 'Marking Centre Verifier', 'Mark Entry Officer', 'Subject Panel Leader']);
            })->orderBy('name')->get();
            $markingCentresQuery = \App\Models\MarkingCentre::with('region');
            $markingCentresQuery->where('status', 'active');
            if ($allowedRegionId) {
                $markingCentresQuery->where('region_id', $allowedRegionId);
            } elseif ($isReo && !$isTrulyAdmin) {
                $markingCentresQuery->whereRaw('1 = 0');
            }
            $markingCentres = $markingCentresQuery->orderBy('name')->get();
            $districtCouncilsQuery = \App\Models\DistrictCouncil::query();
            if ($allowedRegionId) {
                $districtCouncilsQuery->where('region_id', $allowedRegionId);
            } elseif ($isReo && !$isTrulyAdmin) {
                $districtCouncilsQuery->whereRaw('1 = 0');
            }
            $districtCouncils = $districtCouncilsQuery->orderBy('name')->get();

            $officersQuery = \App\Models\User::where(function($q) {
                $q->whereHas('role', fn($rq) => $rq->whereIn('code', ['mark_officer', 'mark_entry_officer', 'meo'])->orWhere('name', 'Mark Entry Officer'))
                  ->orWhereIn('portal_role', ['mark_officer', 'mark_entry_officer', 'meo']);
            });
            $officersQuery->where('status', 'active');
            if ($allowedRegionId) {
                $officersQuery->where('region_id', $allowedRegionId);
            } elseif ($isReo && !$isTrulyAdmin) {
                $officersQuery->whereRaw('1 = 0');
            }
            $officers = $officersQuery->orderBy('name')->get();
            
            if ($currentView === 'assignments') {
                $assignmentsQuery = \App\Models\MarkEntryAssignment::with(['assignedTo', 'school.district.region', 'subject', 'markingCentre', 'examYear'])
                    ->where('exam_type_id', $psleExamTypeId);
                if ($selectedYearId) {
                    $assignmentsQuery->where('exam_year_id', $selectedYearId);
                }
                $this->applyReoRegionScope($assignmentsQuery, $user, $isReo && !$isTrulyAdmin);
                $assignments = $assignmentsQuery->latest()->get();

                $assignmentDistrictsQuery = \App\Models\District::query();
                if ($allowedRegionId) {
                    $assignmentDistrictsQuery->where('region_id', $allowedRegionId);
                } elseif ($isReo && !$isTrulyAdmin) {
                    $assignmentDistrictsQuery->whereRaw('1 = 0');
                }
                $assignmentDistricts = $assignmentDistrictsQuery->orderBy('name')->get(['id', 'name', 'region_id']);

                $assignmentSchoolsQuery = \App\Models\School::whereIn('school_type', ['PRIMARY', 'BOTH'])
                    ->where('education_level', 'PRIMARY')
                    ->with('district:id,name,region_id');
                if ($allowedRegionId) {
                    $assignmentSchoolsQuery->whereHas('district', fn($q) => $q->where('region_id', $allowedRegionId));
                } elseif ($isReo && !$isTrulyAdmin) {
                    $assignmentSchoolsQuery->whereRaw('1 = 0');
                }
                $assignmentSchools = $assignmentSchoolsQuery->orderBy('name')->get(['id', 'code', 'name', 'district_id']);
            }

            $userCounts = [
                'total' => \App\Models\User::where(function($q) {
                    $q->whereHas('role', function($rq) {
                        $rq->whereIn('code', ['reo', 'centre_verifier', 'mark_officer', 'mark_entry_officer', 'meo', 'mock_rao', 'rao', 'subject_panel_leader'])
                            ->orWhereIn('name', ['Regional Education Officer', 'Marking Centre Verifier', 'Mark Entry Officer', 'Regional Academic Officer', 'Subject Panel Leader']);
                    })
                    ->orWhereIn('portal_role', ['reo', 'centre_verifier', 'mark_officer', 'mark_entry_officer', 'meo', 'mock_rao', 'rao', 'subject_panel_leader']);
                })->count(),
                'reos' => \App\Models\User::where(function($q) {
                    $q->whereHas('role', fn($rq) => $rq->whereIn('code', ['reo', 'rao']))
                      ->orWhereIn('portal_role', ['reo', 'rao', 'mock_rao']);
                })->count(),
                'supervisors' => \App\Models\User::where(function($q) {
                    $q->whereHas('role', fn($rq) => $rq->where('code', 'centre_verifier'))
                      ->orWhere('portal_role', 'centre_verifier');
                })->count(),
                'officers' => \App\Models\User::where(function($q) {
                    $q->whereHas('role', fn($rq) => $rq->whereIn('code', ['mark_officer', 'mark_entry_officer', 'meo'])->orWhere('name', 'Mark Entry Officer'))
                      ->orWhereIn('portal_role', ['mark_officer', 'mark_entry_officer', 'meo']);
                })->count(),
                'panelLeaders' => \App\Models\User::where(function($q) {
                    $q->whereHas('role', fn($rq) => $rq->where('code', 'subject_panel_leader'))
                      ->orWhere('portal_role', 'subject_panel_leader');
                })->count(),
            ];
        }

        $hasNoAssignments = false;

        $userAssignments = [];
        if ($isMarkOfficer || $isReo) {
            $userAssignments = \App\Models\MarkEntryAssignment::with(['school', 'subject', 'markingCentre'])
                ->where('assigned_to', $user->id)
                ->where('status', 'active')
                ->get();
        }

        // Officer Region Check (Ensure they have a region assigned or active assignments)
        if (($isMarkOfficer || $isReo) && !$isAdmin) {
            if (!$user->region_id && $userAssignments->isEmpty()) {
                $hasNoAssignments = true; // Still use this flag to show "Access Restricted" but for missing region
            }
        }

        // Active Exam Year
        $selectedSubjectId = $request->query('subject_id');

        $dataQualityIssues = collect();
        if ($currentView === 'missing-marks' && $isAdmin) {
            $dataQualityIssues = \App\Models\School::where(function($q) {
                    $q->whereNotIn('school_type', ['PRIMARY', 'BOTH'])
                      ->orWhere('education_level', '!=', 'PRIMARY');
                })
                ->whereHas('candidates', function($cq) use ($selectedYearId, $psleExamTypeId) {
                    $cq->whereHas('examRegistrations', function($rq) use ($selectedYearId, $psleExamTypeId) {
                        $rq->where('exam_type_id', $psleExamTypeId);
                        if ($selectedYearId) $rq->where('exam_year_id', $selectedYearId);
                    });
                })
                ->withCount(['candidates as candidate_count' => function($cq) use ($selectedYearId, $psleExamTypeId) {
                    $cq->whereHas('examRegistrations', function($rq) use ($selectedYearId, $psleExamTypeId) {
                        $rq->where('exam_type_id', $psleExamTypeId);
                        if ($selectedYearId) $rq->where('exam_year_id', $selectedYearId);
                    });
                }])
                ->get();
        }

        $activeFilters = [
            'exam_year_id' => $selectedYearId,
            'region_id' => $selectedRegionId,
            'district_id' => $selectedDistrictId,
            'school_id' => $selectedSchoolId,
            'subject_id' => $selectedSubjectId,
        ];

        $returnedPanelMarks = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('mark_verifications')) {
            $returnedPanelMarksQuery = \App\Models\MarkVerification::with([
                    'rawMark.subject',
                    'rawMark.batch.school',
                    'verifiedBy',
                ])
                ->where('status', \App\Models\MarkVerification::STATUS_RETURNED)
                ->when($selectedYearId, fn($q) => $q->where('exam_year_id', $selectedYearId))
                ->when($selectedSubjectId, fn($q) => $q->where('subject_id', $selectedSubjectId))
                ->when($selectedSchoolId, fn($q) => $q->where('school_id', $selectedSchoolId));

            if ($isMarkOfficer && !$isAdmin) {
                $returnedPanelMarksQuery->where('returned_to_user_id', $user->id);
            } elseif ($selectedRegionId) {
                $returnedPanelMarksQuery->whereHas('rawMark.batch', fn($q) => $q->where('region_id', $selectedRegionId));
            }

            $returnedPanelMarks = $returnedPanelMarksQuery
                ->latest('returned_at')
                ->limit(10)
                ->get();
        }

        $recentActivities = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('psle_activity_logs')) {
            $recentActivitiesQuery = PsleActivityLog::query()
                ->with(['user.role', 'region', 'district', 'school', 'subject', 'examYear'])
                ->when($selectedYearId, fn($q) => $q->where('exam_year_id', $selectedYearId))
                ->when($selectedRegionId, fn($q) => $q->where('region_id', $selectedRegionId))
                ->when($selectedDistrictId, fn($q) => $q->where('district_id', $selectedDistrictId))
                ->when($selectedSchoolId, fn($q) => $q->where('school_id', $selectedSchoolId))
                ->when($selectedSubjectId, fn($q) => $q->where('subject_id', $selectedSubjectId));

            if (!$isAdmin) {
                if ($user->region_id) {
                    $recentActivitiesQuery->where('region_id', $user->region_id);
                } else {
                    $recentActivitiesQuery->where('user_id', $user->id);
                }
            }

            $recentActivities = $recentActivitiesQuery
                ->latest()
                ->limit(15)
                ->get();
        }

        Log::info('[PSLE_RECENT_ACTIVITY_DEBUG]', [
            'user_id' => auth()->id(),
            'role' => $user?->role?->name ?? $user?->role?->code ?? $user?->portal_role,
            'exam_year_id' => $selectedYearId,
            'activities_count' => $recentActivities->count(),
        ]);

        $hiddenTakenSubjectsCount = 0;

        \Log::info('[PSLE_SUBJECT_DROPDOWN_DEBUG] PSLE subject dropdown loaded', [
            'user_id' => $user?->id,
            'role' => $user?->role?->code ?? $user?->role?->name ?? $user?->portal_role,
            'exam_year_id' => $selectedYearId ? (int) $selectedYearId : null,
            'district_id' => $selectedDistrictId ? (int) $selectedDistrictId : null,
            'school_id' => $selectedSchoolId ? (int) $selectedSchoolId : null,
            'total_subjects_returned' => $psleSubjects->count(),
        ]);

        return view('mark-entry.psle.index', [
            'moderationStats' => $moderationStats,
            'lockingStats' => $lockingStats,
            'dataQualityIssues' => $dataQualityIssues,
            'hasNoAssignments' => $hasNoAssignments ?? false,
            'candidateCount' => $candidateCount,
            'examYears' => $examYears,
            'psleSubjects' => $psleSubjects,
            'hiddenTakenSubjectsCount' => $hiddenTakenSubjectsCount,
            'enteredMarksCount' => $enteredMarksCount,
            'missingMarksCount' => $missingMarksCount,
            'regionMissingMarksCount' => $regionMissingMarksCount,
            'outlierCount' => $outlierCount,
            'subjectStats' => $subjectStats,
            'user' => $user,
            'regions' => $regions,
            'districts' => $districts,
            'schools' => $schools,
            'selectedYearId' => $selectedYearId,
            'selectedRegionId' => $selectedRegionId,
            'selectedDistrictId' => $selectedDistrictId,
            'selectedSchoolId' => $selectedSchoolId,
            'selectedSubjectId' => $selectedSubjectId,
            'activeFilters' => $activeFilters,
            'allowedRegionId' => $allowedRegionId,
            'isAdmin' => $isAdmin,
            'isReo' => $isReo,
            'isMarkOfficer' => $isMarkOfficer,
            'isTrulyAdmin' => $isTrulyAdmin,
            'simulatedRole' => $simulatedRole,
            'currentView' => $currentView,
            'markingCentres' => $markingCentres ?? collect(),
            'assignments' => $assignments ?? collect(),
            'panelLeaders' => $panelLeaders,
            'assignmentDistricts' => $assignmentDistricts ?? collect(),
            'assignmentSchools' => $assignmentSchools ?? collect(),
            'assignmentDistrictOptions' => ($assignmentDistricts ?? collect())->map(fn($district) => [
                'id' => (int) $district->id,
                'name' => $district->name,
                'region_id' => (int) $district->region_id,
            ])->values()->all(),
            'assignmentSchoolOptions' => ($assignmentSchools ?? collect())->map(fn($school) => [
                'id' => (int) $school->id,
                'code' => $school->code,
                'name' => $school->name,
                'district_id' => (int) $school->district_id,
                'region_id' => (int) ($school->district->region_id ?? 0),
            ])->values()->all(),
            'userAssignments' => $userAssignments ?? collect(),
            'assignment' => $assignment,
            'candidates' => $candidates ?? collect(),
            'missingMarks' => $missingMarks ?? collect(),
            'batches' => $batches ?? collect(),
            'outliers' => $outliers ?? collect(),
            'outlierStats' => $outlierStats ?? [],
            'validationErrors' => $validationErrors ?? collect(),
            'validationStats' => $validationStats ?? [],
            'validationSummary' => $validationSummary ?? [],
            'subjectEntryStatus' => $subjectEntryStatus ?? [],
            'paginatedSchools' => $paginatedSchools ?? collect(),
            'reportSummary' => $reportSummary ?? [],
            'monitoringSummary' => $monitoringSummary ?? [],
            'productivityStats' => $productivityStats ?? collect(),
            'overviewRegionalProgress' => $overviewRegionalProgress ?? collect(),
            'regionalProgress' => $regionalProgress ?? collect(),
            'recentActivity' => $recentActivity ?? collect(),
            'batchActivity' => $batchActivity ?? collect(),
            'auditTrail' => $auditTrail ?? collect(),
            'officers' => $officers ?? collect(),
            'portalUsers' => $portalUsers ?? collect(),
            'userCounts' => $userCounts ?? [],
            'roles' => $roles ?? [],
            'districtCouncils' => $districtCouncils ?? collect(),
            'returnedPanelMarks' => $returnedPanelMarks,
            'recentActivities' => $recentActivities,
            'diagnosticsMeta' => $diagnosticsMeta,
            'isGeofenceEnabled' => \App\Helpers\MarkEntrySettings::geofenceEnabled(),
            'schoolSummaries' => $schoolSummaries,
            'schoolDetails' => $schoolDetails,
            'classification' => $classification,
        ]);
    }

    private function renderPsleBulkImportView(
        Request $request,
        $user,
        bool $isAdmin,
        bool $isReo,
        bool $isMarkOfficer,
        bool $isTrulyAdmin,
        ?string $simulatedRole,
        $examYears,
        $selectedYearId,
        ?int $psleExamTypeId,
        $allowedRegionId,
        string $currentView = 'bulk-import'
    ) {
        $selectedRegionId = $allowedRegionId ?: $request->query('region_id');
        $selectedDistrictId = $request->query('district_id');
        $selectedSchoolId = $request->query('school_id');
        $selectedSubjectId = $request->query('subject_id');

        $regionsQuery = \App\Models\Region::query()->orderBy('name');
        if ($allowedRegionId) {
            $regionsQuery->where('id', $allowedRegionId);
            $selectedRegionId = $allowedRegionId;
        }
        $regions = $regionsQuery->get(['id', 'name']);

        if ($selectedRegionId && !$regions->contains('id', (int) $selectedRegionId)) {
            $selectedRegionId = $allowedRegionId ?: null;
        }

        $districts = collect();
        if ($selectedRegionId) {
            $districts = \App\Models\District::query()
                ->where('region_id', $selectedRegionId)
                ->orderBy('name')
                ->get(['id', 'name', 'region_id']);

            if ($selectedDistrictId && !$districts->contains('id', (int) $selectedDistrictId)) {
                $selectedDistrictId = null;
            }
        }

        $schools = collect();
        if ($selectedSchoolId) {
            $selectedSchool = School::query()
                ->whereKey($selectedSchoolId)
                ->when($selectedRegionId, fn($q) => $q->where('region_id', $selectedRegionId))
                ->when($selectedDistrictId, fn($q) => $q->where('district_id', $selectedDistrictId))
                ->first(['id', 'code', 'name', 'region_id', 'district_id']);

            if ($selectedSchool) {
                $schools = collect([$selectedSchool]);
                $selectedRegionId = $selectedRegionId ?: $selectedSchool->region_id;
                $selectedDistrictId = $selectedDistrictId ?: $selectedSchool->district_id;
            } else {
                $selectedSchoolId = null;
            }
        } elseif ($selectedDistrictId) {
            $schools = School::query()
                ->whereIn('school_type', ['PRIMARY', 'BOTH'])
                ->where('education_level', 'PRIMARY')
                ->where('district_id', $selectedDistrictId)
                ->orderBy('code')
                ->limit(50)
                ->get(['id', 'code', 'name', 'region_id', 'district_id']);
        }

        $psleSubjects = collect();
        if ($selectedSchoolId && $selectedYearId && $psleExamTypeId) {
            $psleSubjects = Subject::query()
                ->where('exam_type_id', $psleExamTypeId)
                ->where('is_active', true)
                ->whereHas('selections', fn($selectionQuery) => $selectionQuery
                    ->where('exam_type_id', $psleExamTypeId)
                    ->where('exam_year_id', $selectedYearId)
                    ->whereHas('candidate', fn($candidateQuery) => $candidateQuery->where('school_id', $selectedSchoolId)))
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'exam_type_id', 'is_active']);

            if ($psleSubjects->isEmpty()) {
                $hasRegisteredCandidates = Candidate::query()
                    ->where('school_id', $selectedSchoolId)
                    ->whereHas('examRegistrations', fn($registrationQuery) => $registrationQuery
                        ->where('exam_type_id', $psleExamTypeId)
                        ->where('exam_year_id', $selectedYearId))
                    ->exists();

                if ($hasRegisteredCandidates) {
                    $psleSubjects = Subject::query()
                        ->where('exam_type_id', $psleExamTypeId)
                        ->where('is_active', true)
                        ->orderBy('code')
                        ->get(['id', 'code', 'name', 'exam_type_id', 'is_active']);
                }
            }
        }

        if ($selectedSubjectId && !$psleSubjects->contains('id', (int) $selectedSubjectId)) {
            $selectedSubjectId = null;
        }

        $activeFilters = [
            'exam_year_id' => $selectedYearId,
            'region_id' => $selectedRegionId,
            'district_id' => $selectedDistrictId,
            'school_id' => $selectedSchoolId,
            'subject_id' => $selectedSubjectId,
        ];

        $hasNoAssignments = false;
        if (($isMarkOfficer || $isReo) && !$isAdmin) {
            if (!$user->region_id) {
                $hasAssignments = \App\Models\MarkEntryAssignment::where([
                    'assigned_to' => $user->id,
                    'status' => 'active',
                ])->exists();
                if (!$hasAssignments) {
                    $hasNoAssignments = true;
                }
            }
        }

        return view('mark-entry.psle.index', [
            'dataQualityIssues' => collect(),
            'hasNoAssignments' => $hasNoAssignments,
            'candidateCount' => 0,
            'examYears' => $examYears,
            'psleSubjects' => $psleSubjects,
            'hiddenTakenSubjectsCount' => 0,
            'enteredMarksCount' => 0,
            'missingMarksCount' => 0,
            'regionMissingMarksCount' => 0,
            'outlierCount' => 0,
            'subjectStats' => [],
            'user' => $user,
            'regions' => $regions,
            'districts' => $districts,
            'schools' => $schools,
            'selectedYearId' => $selectedYearId,
            'selectedRegionId' => $selectedRegionId,
            'selectedDistrictId' => $selectedDistrictId,
            'selectedSchoolId' => $selectedSchoolId,
            'selectedSubjectId' => $selectedSubjectId,
            'activeFilters' => $activeFilters,
            'allowedRegionId' => $allowedRegionId,
            'isAdmin' => $isAdmin,
            'isReo' => $isReo,
            'isMarkOfficer' => $isMarkOfficer,
            'isTrulyAdmin' => $isTrulyAdmin,
            'simulatedRole' => $simulatedRole,
            'currentView' => $currentView,
            'markingCentres' => collect(),
            'assignments' => collect(),
            'assignmentDistricts' => collect(),
            'assignmentSchools' => collect(),
            'assignmentDistrictOptions' => [],
            'assignmentSchoolOptions' => [],
            'userAssignments' => collect(),
            'assignment' => null,
            'candidates' => collect(),
            'missingMarks' => collect(),
            'batches' => collect(),
            'outliers' => collect(),
            'outlierStats' => [],
            'validationErrors' => collect(),
            'validationStats' => [],
            'validationSummary' => [],
            'subjectEntryStatus' => [],
            'paginatedSchools' => collect(),
            'reportSummary' => [],
            'monitoringSummary' => [],
            'productivityStats' => collect(),
            'overviewRegionalProgress' => collect(),
            'regionalProgress' => collect(),
            'recentActivity' => collect(),
            'batchActivity' => collect(),
            'auditTrail' => collect(),
            'officers' => collect(),
            'portalUsers' => collect(),
            'userCounts' => [],
            'roles' => [],
            'districtCouncils' => collect(),
            'returnedPanelMarks' => collect(),
            'recentActivities' => collect(),
        ]);
    }

    private function applyReoRegionScope(Builder $query, $user, bool $isReo): Builder
    {
        if (! $isReo) {
            return $query;
        }

        if (! $user->region_id) {
            return $query->whereRaw('1 = 0');
        }

        // Scope through the school's district so legacy assignment region_id values cannot widen REO access.
        return $query->whereHas('school.district', function (Builder $districtQuery) use ($user) {
            $districtQuery->where('region_id', (int) $user->region_id);
        });
    }

    private function validateReoAssignmentScope(array $validated, $user): ?string
    {
        if ($user->isAdmin() || ! $this->isReoUser($user)) {
            return null;
        }

        if (! $user->region_id) {
            return 'Your account is not assigned to a region.';
        }

        $regionId = (int) $user->region_id;

        if ((int) $validated['region_id'] !== $regionId) {
            return 'You cannot create an assignment outside your assigned region.';
        }

        if (! empty($validated['district_id'])) {
            $districtInRegion = \App\Models\District::whereKey($validated['district_id'])
                ->where('region_id', $regionId)
                ->exists();

            if (! $districtInRegion) {
                return 'The selected district is outside your assigned region.';
            }
        }

        $schoolInRegion = \App\Models\School::whereKey($validated['school_id'])
            ->whereHas('district', fn (Builder $query) => $query->where('region_id', $regionId))
            ->when(! empty($validated['district_id']), fn (Builder $query) => $query->where('district_id', (int) $validated['district_id']))
            ->exists();

        if (! $schoolInRegion) {
            return 'The selected school is outside your assigned region.';
        }

        $centreInRegion = \App\Models\MarkingCentre::whereKey($validated['marking_centre_id'])
            ->where('region_id', $regionId)
            ->exists();

        if (! $centreInRegion) {
            return 'The selected marking centre is outside your assigned region.';
        }

        $officerInRegion = \App\Models\User::whereKey($validated['assigned_to'])
            ->where('region_id', $regionId)
            ->exists();

        if (! $officerInRegion) {
            return 'The selected officer is outside your assigned region.';
        }

        return null;
    }

    private function assignmentBelongsToReoRegion(MarkEntryAssignment $assignment, $user): bool
    {
        if ($user->isAdmin() || ! $this->isReoUser($user)) {
            return true;
        }

        if (! $user->region_id) {
            return false;
        }

        return MarkEntryAssignment::whereKey($assignment->id)
            ->whereHas('school.district', fn (Builder $query) => $query->where('region_id', (int) $user->region_id))
            ->exists();
    }

    private function isReoUser($user): bool
    {
        return $user->hasRole('reo') || $user->hasRole('rao') || in_array($user->portal_role, ['reo', 'rao', 'mock_rao'], true);
    }

    private function isMarkOfficerUser($user): bool
    {
        $roleCode = $user->role?->code;
        $roleName = $user->role?->name;

        return in_array($roleCode, ['mark_officer', 'mark_entry_officer', 'meo'], true)
            || $roleName === 'Mark Entry Officer'
            || in_array($user->portal_role, ['mark_officer', 'mark_entry_officer', 'meo'], true);
    }

    private function isAdminOrReo($user): bool
    {
        if (!$user) {
            return false;
        }
        $isTrulyAdmin = $user->isAdmin();
        $isReo = $this->isReoUser($user) || ($user->region_id && !$user->hasRole('officer') && !$this->isMarkOfficerUser($user) && !$isTrulyAdmin);
        return $isTrulyAdmin || $isReo;
    }

    private function takeSubjectAssignment($user, int $examYearId, int $examTypeId, School $school, int $subjectId, ?int $assignedBy = null): MarkEntryAssignment
    {
        if (! $examYearId || ! $examTypeId || ! $school->id || ! $subjectId) {
            throw new \RuntimeException('Please select a valid school, subject, and exam year.');
        }

        $existingOwnAssignment = MarkEntryAssignment::query()
            ->where('exam_year_id', $examYearId)
            ->where('exam_type_id', $examTypeId)
            ->where('school_id', $school->id)
            ->where('subject_id', $subjectId)
            ->where('assignment_type', 'entry')
            ->where('status', 'active')
            ->where('active_lock', 1)
            ->where('assigned_to', $user->id)
            ->first();

        if ($existingOwnAssignment) {
            return $existingOwnAssignment;
        }

        $takenByOther = MarkEntryAssignment::query()
            ->where('exam_year_id', $examYearId)
            ->where('exam_type_id', $examTypeId)
            ->where('school_id', $school->id)
            ->where('subject_id', $subjectId)
            ->where('assignment_type', 'entry')
            ->where('status', 'active')
            ->where('active_lock', 1)
            ->where('assigned_to', '!=', $user->id)
            ->exists();

        if ($takenByOther) {
            throw new \RuntimeException('This subject has already been taken by another Mark Entry Officer for this school.');
        }

        try {
            return DB::transaction(function () use ($user, $examYearId, $examTypeId, $school, $subjectId, $assignedBy) {
                $assignment = MarkEntryAssignment::create([
                    'exam_year_id' => $examYearId,
                    'exam_type_id' => $examTypeId,
                    'region_id' => $school->region_id,
                    'district_id' => $school->district_id,
                    'school_id' => $school->id,
                    'subject_id' => $subjectId,
                    'marking_centre_id' => $this->resolveMarkingCentreId($user, (int) $school->region_id),
                    'assigned_to' => $user->id,
                    'assigned_by' => $assignedBy,
                    'assignment_type' => 'entry',
                    'status' => 'active',
                    'active_lock' => 1,
                    'starts_at' => now(),
                ]);

                $this->logSubjectAssignmentAction(
                    action: 'SUBJECT_TAKEN_BY_MEO',
                    actorId: $assignedBy,
                    officerId: $user->id,
                    assignment: $assignment,
                    oldStatus: null,
                    newStatus: 'active'
                );

                return $assignment;
            });
        } catch (QueryException $exception) {
            throw new \RuntimeException('This subject has already been taken by another Mark Entry Officer for this school.');
        }
    }

    private function resolveMarkingCentreId($user, int $regionId): int
    {
        $markingCentreId = $user->marking_centre_id
            ?: \App\Models\MarkingCentre::where('region_id', $regionId)->value('id')
            ?: \App\Models\MarkingCentre::query()->value('id');

        if (! $markingCentreId) {
            throw new \RuntimeException('No marking centre is available for this assignment. Contact an administrator.');
        }

        return (int) $markingCentreId;
    }

    private function logPsleActivity(array $data): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('psle_activity_logs')) {
            return;
        }

        try {
            app(PsleActivityLogger::class)->log($data);
        } catch (\Throwable $exception) {
            Log::warning('Unable to write PSLE activity log.', [
                'event_type' => $data['event_type'] ?? null,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function psleSaveDebugContext(Request $request): array
    {
        $user = $request->user();

        return [
            'user_id' => $user?->id,
            'user_role' => $user?->role?->code ?? $user?->role?->name ?? $user?->portal_role,
            'exam_year_id' => $request->input('exam_year_id'),
            'region_id' => $user?->region_id,
            'district_id' => $request->input('district_id') ?? $user?->district_council_id,
            'school_id' => $request->input('school_id'),
            'subject_id' => $request->input('subject_id'),
            'candidate_id' => $request->input('candidate_id'),
            'candidate_number' => null,
            'mark_value' => $request->input('score'),
            'request_method' => $request->method(),
            'request_url' => $request->fullUrl(),
        ];
    }

    private function isTemporaryDatabaseException(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'database is locked')
            || str_contains($message, 'deadlock')
            || str_contains($message, 'lock wait timeout')
            || str_contains($message, 'too many connections')
            || str_contains($message, 'server has gone away')
            || str_contains($message, 'database table is locked');
    }

    private function recordPsleSaveMetric(int $status, int $durationMs): void
    {
        try {
            $day = now()->format('Ymd');
            $prefix = "psle_mark_save_metrics:{$day}";
            $ttl = now()->addDays(2);

            cache()->add("{$prefix}:total", 0, $ttl);
            cache()->add("{$prefix}:duration_total_ms", 0, $ttl);
            cache()->increment("{$prefix}:total");
            cache()->increment("{$prefix}:duration_total_ms", $durationMs);

            if ($status >= 400) {
                cache()->add("{$prefix}:failed", 0, $ttl);
                cache()->increment("{$prefix}:failed");
            }

            if (in_array($status, [502, 503, 504], true)) {
                cache()->add("{$prefix}:temporary_unavailable", 0, $ttl);
                cache()->increment("{$prefix}:temporary_unavailable");
            }

            $slowest = (int) cache()->get("{$prefix}:slowest_ms", 0);
            if ($durationMs > $slowest) {
                cache()->put("{$prefix}:slowest_ms", $durationMs, $ttl);
            }
        } catch (Throwable $exception) {
            Log::debug('Unable to record PSLE mark save metric.', ['error' => $exception->getMessage()]);
        }
    }

    private function psleSaveMetricsToday(): array
    {
        $day = now()->format('Ymd');
        $prefix = "psle_mark_save_metrics:{$day}";
        $total = (int) cache()->get("{$prefix}:total", 0);
        $durationTotal = (int) cache()->get("{$prefix}:duration_total_ms", 0);

        return [
            'total_requests' => $total,
            'failed_saves' => (int) cache()->get("{$prefix}:failed", 0),
            'temporary_unavailable_count' => (int) cache()->get("{$prefix}:temporary_unavailable", 0),
            'average_duration_ms' => $total > 0 ? (int) round($durationTotal / $total) : 0,
            'slowest_duration_ms' => (int) cache()->get("{$prefix}:slowest_ms", 0),
        ];
    }

    private function logSubjectAssignmentAction(string $action, ?int $actorId, int $officerId, MarkEntryAssignment $assignment, ?string $oldStatus, string $newStatus): void
    {
        GovernanceAuditLog::log(
            strtolower($action),
            userId: $officerId,
            adminId: $actorId,
            data: [
                'action_code' => $action,
                'assignment_id' => $assignment->id,
                'mark_entry_officer_id' => $officerId,
                'school_id' => $assignment->school_id,
                'subject_id' => $assignment->subject_id,
                'exam_year_id' => $assignment->exam_year_id,
                'exam_type_id' => $assignment->exam_type_id,
                'assignment_type' => $assignment->assignment_type,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'ip_address' => request()->ip(),
                'source' => request()->path(),
                'timestamp' => now()->toDateTimeString(),
            ]
        );
    }

    public function createUser(Request $request)
    {
        $user = $request->user();
        if (!$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'role_id' => 'required',
            'region_id' => 'nullable|exists:regions,id',
            'district_council_id' => 'nullable|exists:district_councils,id',
            'marking_centre_id' => 'nullable|exists:marking_centres,id',
            'status' => 'required|in:active,inactive,suspended',
            'password_mode' => 'nullable|in:auto,manual',
            'force_password_reset' => 'nullable|boolean',
        ]);

        if (($validated['password_mode'] ?? 'manual') === 'manual' && empty($validated['password'])) {
            return redirect()->back()
                ->withErrors(['password' => 'Password is required when manual password mode is selected.'])
                ->withInput();
        }

        if ($request->filled('region_id') && $request->filled('district_council_id')) {
            $councilBelongsToRegion = \App\Models\DistrictCouncil::where('id', $request->district_council_id)
                ->where('region_id', $request->region_id)
                ->exists();
            if (!$councilBelongsToRegion) {
                return redirect()->back()
                    ->withErrors(['district_council_id' => 'The selected council does not belong to the selected region.'])
                    ->withInput();
            }
        }

        if ($request->filled('region_id') && $request->filled('marking_centre_id')) {
            $centreBelongsToRegion = \App\Models\MarkingCentre::where('id', $request->marking_centre_id)
                ->where('region_id', $request->region_id)
                ->exists();
            if (!$centreBelongsToRegion) {
                return redirect()->back()
                    ->withErrors(['marking_centre_id' => 'The selected marking centre does not belong to the selected region.'])
                    ->withInput();
            }
        }

        $password = $validated['password'] ?: \Illuminate\Support\Str::password(12);
        $roleInfo = $this->resolvePsleUserRole((string) $validated['role_id']);

        if ($roleInfo['portal_role'] === 'reo' && empty($validated['region_id'])) {
            return redirect()->back()
                ->withErrors(['region_id' => 'Region is required for REO users.'])
                ->withInput();
        }

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => \Illuminate\Support\Facades\Hash::make($password),
            'region_id' => $validated['region_id'] ?? null,
            'district_council_id' => $validated['district_council_id'] ?? null,
            'marking_centre_id' => $validated['marking_centre_id'] ?? null,
            'role_id' => $roleInfo['role']?->id,
            'portal_role' => $roleInfo['portal_role'],
            'status' => $this->normalizeImportedUserStatus($validated['status']),
            'password_reset_required' => (bool) ($validated['force_password_reset'] ?? true),
        ];

        // Defensive check: only include marking_centre_id if the column exists in the database
        if (isset($userData['marking_centre_id']) && !\Illuminate\Support\Facades\Schema::hasColumn('users', 'marking_centre_id')) {
            unset($userData['marking_centre_id']);
        }
        if (isset($userData['district_council_id']) && !\Illuminate\Support\Facades\Schema::hasColumn('users', 'district_council_id')) {
            unset($userData['district_council_id']);
        }

        $newUser = \App\Models\User::create($userData);

        \App\Models\GovernanceAuditLog::log(
            \App\Models\GovernanceAuditLog::ACTION_USER_CREATED,
            $newUser->id,
            $user->id,
            [
                'email' => $newUser->email,
                'role_id' => $newUser->role_id,
                'portal_role' => $newUser->portal_role,
                'region_id' => $newUser->region_id,
                'district_council_id' => $newUser->district_council_id,
                'marking_centre_id' => $newUser->marking_centre_id,
                'status' => $newUser->status,
            ]
        );

        return redirect()->back()->with('success', 'User created successfully.');
    }

    public function downloadUserImportTemplate(Request $request)
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $filename = 'psle_mark_entry_users_template.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['name', 'email', 'phone', 'role', 'region', 'council', 'marking_centre', 'password', 'status']);
            fputcsv($handle, ['LAILATH MAKOTI', 'lailath@iringa.co.tz', '255700000001', 'Mark Entry Officer', 'IRINGA', 'IRINGA MC', "IFUNDA GIRLS' SECONDARY SCHOOL", 'Password@123', 'active']);
            fputcsv($handle, ['AGNES GABRIEL NKACHA', 'agnes.nkacha@yahoo.com', '255700000002', 'Mark Entry Officer', 'DODOMA', 'DODOMA CC', 'BIHAWANA SECONDARY SCHOOL', 'Password@123', 'active']);
            fputcsv($handle, ['VIVIAN AGREY KIGODI', 'vivian@example.com', '255700000003', 'Subject Panel Leader', 'IRINGA', '', '', 'Password@123', 'active']);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importUsers(Request $request)
    {
        $admin = $request->user();
        if (! $admin?->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $request->validate([
            'users_csv' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        [$rows, $readErrors] = $this->readPsleUserImportCsv($request->file('users_csv')->getRealPath());
        $errors = $readErrors;
        $validRows = [];
        $seenEmails = [];

        $regions = \App\Models\Region::query()->get();
        $councils = \App\Models\DistrictCouncil::query()->get();
        $centres = \App\Models\MarkingCentre::query()->get();
        $existingEmails = \App\Models\User::query()
            ->whereIn('email', collect($rows)->pluck('email')->filter()->map(fn($email) => strtolower($email))->all())
            ->pluck('email')
            ->map(fn($email) => strtolower($email))
            ->flip();

        foreach ($rows as $row) {
            $rowErrors = [];
            $rowNumber = $row['_row_number'];
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '') {
                $rowErrors[] = 'Name is required';
            }
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = 'Valid email is required';
            } elseif (isset($existingEmails[$email])) {
                $rowErrors[] = 'Email already exists';
            } elseif (isset($seenEmails[$email])) {
                $rowErrors[] = 'Duplicate email in uploaded CSV';
            }
            $seenEmails[$email] = true;

            $roleInfo = null;
            try {
                $roleInfo = $this->resolvePsleUserRole((string) ($row['role'] ?? ''));
            } catch (\InvalidArgumentException $exception) {
                $rowErrors[] = $exception->getMessage();
            }

            $region = $this->matchNamedModel($regions, (string) ($row['region'] ?? ''));
            $portalRole = $roleInfo ? $roleInfo['portal_role'] : null;
            if (! $region && $portalRole !== 'subject_panel_leader') {
                $rowErrors[] = 'Region not found: ' . ($row['region'] ?? '');
            }

            $council = null;
            if (trim((string) ($row['council'] ?? '')) !== '') {
                $scopedCouncils = $region ? $councils->where('region_id', $region->id) : $councils;
                $council = $this->matchNamedModel($scopedCouncils, (string) $row['council']);
                if (! $council) {
                    $rowErrors[] = 'Council not found: ' . $row['council'];
                }
            }

            $markingCentre = null;
            if (trim((string) ($row['marking_centre'] ?? '')) !== '') {
                $scopedCentres = $region ? $centres->where('region_id', $region->id) : $centres;
                $markingCentre = $this->matchNamedModel($scopedCentres, (string) $row['marking_centre']);
                if (! $markingCentre) {
                    $rowErrors[] = 'Marking centre not found: ' . $row['marking_centre'];
                }
            }

            $password = trim((string) ($row['password'] ?? ''));
            if ($password !== '' && strlen($password) < 8) {
                $rowErrors[] = 'Password must be at least 8 characters';
            }

            $status = $this->normalizeImportedUserStatus((string) ($row['status'] ?? 'active'));
            if (! in_array(strtolower(trim((string) ($row['status'] ?? 'active'))), ['active', 'inactive', 'suspended', ''], true)) {
                $rowErrors[] = 'Status must be active or inactive';
            }

            if ($rowErrors) {
                $errors[] = [
                    'row_number' => $rowNumber,
                    'name' => $name,
                    'email' => $email,
                    'reason' => implode('; ', $rowErrors),
                ];
                continue;
            }

            $validRows[] = [
                'row_number' => $rowNumber,
                'name' => $name,
                'email' => $email,
                'phone' => trim((string) ($row['phone'] ?? '')) ?: null,
                'password' => $password ?: \Illuminate\Support\Str::password(12),
                'password_was_generated' => $password === '',
                'role' => $roleInfo['role'],
                'portal_role' => $roleInfo['portal_role'],
                'region_id' => $region?->id,
                'district_council_id' => $council?->id,
                'marking_centre_id' => $markingCentre?->id,
                'status' => $status,
            ];
        }

        $created = 0;
        $roleSummary = [];
        foreach (array_chunk($validRows, 100) as $chunk) {
            foreach ($chunk as $row) {
                try {
                    DB::transaction(function () use ($row, $admin, &$created, &$roleSummary) {
                        $newUser = \App\Models\User::create([
                            'name' => $row['name'],
                            'email' => $row['email'],
                            'phone' => $row['phone'],
                            'password' => \Illuminate\Support\Facades\Hash::make($row['password']),
                            'role_id' => $row['role']?->id,
                            'portal_role' => $row['portal_role'],
                            'region_id' => $row['region_id'],
                            'district_council_id' => \Illuminate\Support\Facades\Schema::hasColumn('users', 'district_council_id') ? $row['district_council_id'] : null,
                            'marking_centre_id' => \Illuminate\Support\Facades\Schema::hasColumn('users', 'marking_centre_id') ? $row['marking_centre_id'] : null,
                            'status' => $row['status'],
                            'password_reset_required' => true,
                        ]);

                        \App\Models\GovernanceAuditLog::log(
                            \App\Models\GovernanceAuditLog::ACTION_USER_CREATED,
                            $newUser->id,
                            $admin->id,
                            [
                                'source' => 'psle_user_csv_import',
                                'row_number' => $row['row_number'],
                                'email' => $newUser->email,
                                'role_id' => $newUser->role_id,
                                'portal_role' => $newUser->portal_role,
                                'region_id' => $newUser->region_id,
                                'district_council_id' => $newUser->district_council_id,
                                'marking_centre_id' => $newUser->marking_centre_id,
                            ]
                        );

                        $created++;
                        $roleSummary[$row['portal_role']] = ($roleSummary[$row['portal_role']] ?? 0) + 1;
                    });
                } catch (Throwable $exception) {
                    $errors[] = [
                        'row_number' => $row['row_number'],
                        'name' => $row['name'],
                        'email' => $row['email'],
                        'reason' => 'Import failed: ' . $exception->getMessage(),
                    ];
                }
            }
        }

        \App\Models\GovernanceAuditLog::log(
            \App\Models\GovernanceAuditLog::ACTION_IMPORT_COMPLETED,
            null,
            $admin->id,
            [
                'source' => 'psle_user_csv_import',
                'uploaded_file_name' => $request->file('users_csv')->getClientOriginalName(),
                'total_rows' => count($rows),
                'created' => $created,
                'skipped_or_failed' => count($errors),
                'role_summary' => $roleSummary,
                'timestamp' => now()->toIso8601String(),
            ]
        );

        $errorFilename = null;
        if ($errors) {
            $errorFilename = $this->writePsleUserImportErrorReport($errors);
        }

        return redirect('/mark-entry/psle?view=user-management')
            ->with('user_import_summary', [
                'total' => count($rows),
                'created' => $created,
                'failed' => count($errors),
                'duplicates' => collect($errors)->filter(fn($error) => str_contains($error['reason'], 'Email already exists'))->count(),
                'error_report' => $errorFilename,
            ])
            ->with($created > 0 ? 'success' : 'error', $created > 0 ? 'User import completed.' : 'No users were imported. Review the error report.');
    }

    public function downloadUserImportErrors(Request $request, string $filename)
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $filename = basename($filename);
        $path = storage_path('app/psle-user-import-errors/' . $filename);

        abort_unless(is_file($path), 404);

        return response()->download($path, $filename, ['Content-Type' => 'text/csv']);
    }

    private function readPsleUserImportCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return [[], [[
                'row_number' => 0,
                'name' => '',
                'email' => '',
                'reason' => 'Unable to read uploaded CSV file',
            ]]];
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);
            return [[], [[
                'row_number' => 1,
                'name' => '',
                'email' => '',
                'reason' => 'CSV file is empty',
            ]]];
        }

        $header = array_map(fn($value) => strtolower(trim((string) $value, " \t\n\r\0\x0B\xEF\xBB\xBF")), $header);
        $required = ['name', 'email', 'role', 'region', 'marking_centre', 'status'];
        $missing = array_values(array_diff($required, $header));
        if ($missing) {
            fclose($handle);
            return [[], [[
                'row_number' => 1,
                'name' => '',
                'email' => '',
                'reason' => 'Missing required CSV columns: ' . implode(', ', $missing),
            ]]];
        }

        $rows = [];
        $errors = [];
        $rowNumber = 1;
        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if (count(array_filter($data, fn($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            if (count($data) < count($header)) {
                $data = array_pad($data, count($header), '');
            }

            $row = array_combine($header, array_slice($data, 0, count($header)));
            $row['_row_number'] = $rowNumber;
            $rows[] = $row;
        }
        fclose($handle);

        return [$rows, $errors];
    }

    private function resolvePsleUserRole(string $value): array
    {
        $normalized = strtolower(trim(str_replace(['_', '-'], ' ', $value)));
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        if ($normalized === '') {
            throw new \InvalidArgumentException('Role is required');
        }

        $map = [
            'mark entry officer' => ['code' => 'mark_officer', 'name' => 'Mark Entry Officer', 'portal_role' => 'mark_officer'],
            'mark officer' => ['code' => 'mark_officer', 'name' => 'Mark Entry Officer', 'portal_role' => 'mark_officer'],
            'meo' => ['code' => 'mark_officer', 'name' => 'Mark Entry Officer', 'portal_role' => 'mark_officer'],
            'reo' => ['code' => 'reo', 'name' => 'Regional Education Officer', 'portal_role' => 'reo'],
            'regional education officer' => ['code' => 'reo', 'name' => 'Regional Education Officer', 'portal_role' => 'reo'],
            'supervisor' => ['code' => 'centre_verifier', 'name' => 'Marking Centre Verifier', 'portal_role' => 'centre_verifier'],
            'verifier' => ['code' => 'centre_verifier', 'name' => 'Marking Centre Verifier', 'portal_role' => 'centre_verifier'],
            'marking centre verifier' => ['code' => 'centre_verifier', 'name' => 'Marking Centre Verifier', 'portal_role' => 'centre_verifier'],
            'centre verifier' => ['code' => 'centre_verifier', 'name' => 'Marking Centre Verifier', 'portal_role' => 'centre_verifier'],
            'subject panel leader' => ['code' => 'subject_panel_leader', 'name' => 'Subject Panel Leader', 'portal_role' => 'subject_panel_leader'],
            'subject_panel_leader' => ['code' => 'subject_panel_leader', 'name' => 'Subject Panel Leader', 'portal_role' => 'subject_panel_leader'],
            'psle subject panel' => ['code' => 'subject_panel_leader', 'name' => 'Subject Panel Leader', 'portal_role' => 'subject_panel_leader'],
            'psle_subject_panel' => ['code' => 'subject_panel_leader', 'name' => 'Subject Panel Leader', 'portal_role' => 'subject_panel_leader'],
            'psle subject panel leader' => ['code' => 'subject_panel_leader', 'name' => 'Subject Panel Leader', 'portal_role' => 'subject_panel_leader'],
            'psle_subject_panel_leader' => ['code' => 'subject_panel_leader', 'name' => 'Subject Panel Leader', 'portal_role' => 'subject_panel_leader'],
            'subject panel' => ['code' => 'subject_panel_leader', 'name' => 'Subject Panel Leader', 'portal_role' => 'subject_panel_leader'],
            'subject_panel' => ['code' => 'subject_panel_leader', 'name' => 'Subject Panel Leader', 'portal_role' => 'subject_panel_leader'],
        ];

        if (is_numeric($value)) {
            $role = \App\Models\Role::find((int) $value);
            if (! $role) {
                throw new \InvalidArgumentException('Role not found: ' . $value);
            }

            $mapped = $this->resolvePortalRoleForRole($role);
            if (! $mapped) {
                throw new \InvalidArgumentException('Role is not allowed for PSLE Mark Entry: ' . $role->name);
            }

            return ['role' => $role, 'portal_role' => $mapped];
        }

        $definition = $map[$normalized] ?? null;
        if (! $definition) {
            $role = \App\Models\Role::query()
                ->whereRaw('LOWER(code) = ?', [strtolower(trim($value))])
                ->orWhereRaw('LOWER(name) = ?', [strtolower(trim($value))])
                ->first();

            if ($role && ($portalRole = $this->resolvePortalRoleForRole($role))) {
                return ['role' => $role, 'portal_role' => $portalRole];
            }

            throw new \InvalidArgumentException('Invalid role: ' . $value);
        }

        $role = \App\Models\Role::firstOrCreate(
            ['code' => $definition['code']],
            ['name' => $definition['name'], 'description' => 'PSLE Mark Entry role']
        );

        return ['role' => $role, 'portal_role' => $definition['portal_role']];
    }

    private function resolvePortalRoleForRole(\App\Models\Role $role): ?string
    {
        if (in_array($role->code, ['mark_officer', 'mark_entry_officer', 'meo'], true) || $role->name === 'Mark Entry Officer') {
            return 'mark_officer';
        }

        if (in_array($role->code, ['reo', 'rao'], true) || in_array($role->name, ['Regional Education Officer', 'Regional Academic Officer'], true)) {
            return $role->code === 'rao' ? 'rao' : 'reo';
        }

        if ($role->code === 'centre_verifier' || in_array($role->name, ['Marking Centre Verifier', 'Verifier', 'Supervisor'], true)) {
            return 'centre_verifier';
        }

        if ($role->code === 'subject_panel_leader' || $role->name === 'Subject Panel Leader' || $role->name === 'PSLE Subject Panel') {
            return 'subject_panel_leader';
        }

        return null;
    }

    private function matchNamedModel($models, string $value)
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $exact = $models->first(fn($model) => trim((string) $model->name) === $value || trim((string) ($model->code ?? '')) === $value);
        if ($exact) {
            return $exact;
        }

        $normalized = strtolower(preg_replace('/\s+/', ' ', $value));

        return $models->first(function ($model) use ($normalized) {
            return strtolower(preg_replace('/\s+/', ' ', trim((string) $model->name))) === $normalized
                || strtolower(preg_replace('/\s+/', ' ', trim((string) ($model->code ?? '')))) === $normalized;
        });
    }

    private function normalizeImportedUserStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return in_array($status, ['inactive', 'suspended'], true) ? 'suspended' : 'active';
    }

    private function writePsleUserImportErrorReport(array $errors): string
    {
        $directory = storage_path('app/psle-user-import-errors');
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $filename = 'psle_user_import_errors_' . now()->format('Ymd_His') . '_' . \Illuminate\Support\Str::random(6) . '.csv';
        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        $handle = fopen($path, 'w');
        fputcsv($handle, ['row_number', 'name', 'email', 'reason']);
        foreach ($errors as $error) {
            fputcsv($handle, [
                $error['row_number'] ?? '',
                $error['name'] ?? '',
                $error['email'] ?? '',
                $error['reason'] ?? '',
            ]);
        }
        fclose($handle);

        return $filename;
    }

    public function toggleUserStatus(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $targetUser = \App\Models\User::findOrFail($id);
        $targetUser->status = $targetUser->status === 'active' ? 'suspended' : 'active';
        $targetUser->save();

        $action = $targetUser->status === 'active' ? \App\Models\GovernanceAuditLog::ACTION_USER_ACTIVATED : \App\Models\GovernanceAuditLog::ACTION_USER_SUSPENDED;
        \App\Models\GovernanceAuditLog::log(
            $action,
            $targetUser->id,
            $user->id,
            ['status' => $targetUser->status]
        );

        return redirect()->back()->with('success', 'User status updated successfully.');
    }

    public function createMarkingCentre(Request $request)
    {
        $user = $request->user();
        if (!$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        // Normalize code before validation so Laravel checks unique constraint on capitalized version
        if ($request->has('code')) {
            $request->merge([
                'code' => strtoupper(trim(preg_replace('/\s+/', ' ', $request->input('code'))))
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:marking_centres',
            'region_id' => 'required|exists:regions,id',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'allowed_radius_meters' => 'nullable|integer|min:5',
        ]);

        $centre = \App\Models\MarkingCentre::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'region_id' => $validated['region_id'],
            'location' => $validated['location'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'allowed_radius_meters' => $validated['allowed_radius_meters'] ?? 50,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        // Audit the coordinates change
        if ($centre->latitude !== null || $centre->longitude !== null) {
            \App\Models\MarkEntryLocationLog::create([
                'user_id' => $user->id,
                'marking_centre_id' => $centre->id,
                'centre_latitude' => $centre->latitude,
                'centre_longitude' => $centre->longitude,
                'allowed' => true,
                'reason' => 'Centre coordinates configured during creation by admin'
            ]);
        }

        return redirect()->back()->with('success', 'Marking Centre created successfully.');
    }

    public function toggleMarkingCentreStatus(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $centre = \App\Models\MarkingCentre::findOrFail($id);
        $centre->status = $centre->status === 'active' ? 'inactive' : 'active';
        $centre->save();

        return redirect()->back()->with('success', 'Marking Centre status updated successfully.');
    }

    public function updateMarkingCentre(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user || !$user->isAdmin()) {
                return redirect()->back()->with('error', 'Unauthorized action.');
            }

            $centre = \App\Models\MarkingCentre::findOrFail($id);

            if ($request->has('code')) {
                $request->merge([
                    'code' => strtoupper(trim(preg_replace('/\s+/', ' ', $request->input('code'))))
                ]);
            }

            // Convert empty inputs to null safely for latitude, longitude, and radius
            if ($request->has('latitude') && $request->input('latitude') === '') {
                $request->merge(['latitude' => null]);
            }
            if ($request->has('longitude') && $request->input('longitude') === '') {
                $request->merge(['longitude' => null]);
            }
            if ($request->has('allowed_radius_meters') && $request->input('allowed_radius_meters') === '') {
                $request->merge(['allowed_radius_meters' => null]);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:marking_centres,code,' . $centre->id,
                'region_id' => 'required|exists:regions,id',
                'location' => 'nullable|string|max:255',
                'status' => 'required|in:active,inactive',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'allowed_radius_meters' => 'nullable|integer|min:5',
            ]);

            $oldLat = $centre->latitude;
            $oldLon = $centre->longitude;
            $oldRadius = $centre->allowed_radius_meters;

            $centre->update([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'region_id' => $validated['region_id'],
                'location' => $validated['location'] ?? null,
                'status' => $validated['status'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'allowed_radius_meters' => $validated['allowed_radius_meters'] ?? 50,
            ]);

            // Audit coordinates change
            if ($oldLat != $centre->latitude || $oldLon != $centre->longitude || $oldRadius != $centre->allowed_radius_meters) {
                \App\Models\MarkEntryLocationLog::create([
                    'user_id' => $user->id,
                    'marking_centre_id' => $centre->id,
                    'centre_latitude' => $centre->latitude,
                    'centre_longitude' => $centre->longitude,
                    'allowed' => true,
                    'reason' => 'Centre coordinates/radius modified by admin'
                ]);
            }

            return redirect()->back()->with('success', 'Marking Centre updated successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Log::error('Unable to update marking centre: ' . $e->getMessage(), [
                'id' => $id,
                'exception' => $e
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Unable to update marking centre. Please check the system log.');
        }
    }

    public function deleteMarkingCentre(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $centre = \App\Models\MarkingCentre::findOrFail($id);

        $linkedUsers = \App\Models\User::where('marking_centre_id', $centre->id)->exists();
        $linkedAssignments = \App\Models\MarkEntryAssignment::where('marking_centre_id', $centre->id)->exists();

        if ($linkedUsers || $linkedAssignments) {
            return redirect()->back()->with('error', 'This marking centre is currently linked to active users or assignments and cannot be removed. Please deactivate it instead.');
        }

        $centre->delete();

        return redirect()->back()->with('success', 'Marking Centre deleted successfully.');
    }

    public function toggleGeofence(Request $request)
    {
        $user = $request->user();
        if (!$user->isAdmin()) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $enabled = (bool) $request->input('enabled');
        $oldValue = \App\Helpers\MarkEntrySettings::geofenceEnabled();

        \App\Helpers\MarkEntrySettings::setGeofenceEnabled($enabled);

        // Record a structured compliance audit log
        \App\Models\GovernanceAuditLog::log(
            $enabled ? 'mark_entry_geofence_enabled' : 'mark_entry_geofence_disabled',
            userId: $user->id,
            adminId: $user->id,
            data: [
                'changed_by_email' => $user->email,
                'old_value' => $oldValue,
                'new_value' => $enabled,
                'ip_address' => $request->ip(),
                'user_agent_hash' => hash('sha256', $request->userAgent()),
                'timestamp' => now()->toIso8601String()
            ]
        );

        return response()->json([
            'ok' => true,
            'enabled' => $enabled,
            'message' => $enabled 
                ? 'Marking centre location restriction has been enabled.' 
                : 'Marking centre location restriction has been disabled. Mark Entry Officers can proceed without GPS verification.'
        ]);
    }

    public function createAssignment(Request $request)
    {
        $user = $request->user();
        if (!$user->isAdmin() && !$this->isReoUser($user)) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'marking_centre_id' => 'required|exists:marking_centres,id',
            'region_id' => 'required|exists:regions,id',
            'district_id' => 'nullable|exists:districts,id',
            'school_id' => 'required|exists:schools,id',
            'subject_id' => 'required|exists:subjects,id',
            'assignment_type' => 'required|in:entry,verification',
        ]);

        if ($scopeError = $this->validateReoAssignmentScope($validated, $user)) {
            abort(403, $scopeError);
        }

        $activeYear = \App\Models\ExamYear::where('is_active', true)->first();
        $psleType = \App\Models\ExamType::where('code', 'PSLE')->first();

        if (! $activeYear || ! $psleType) {
            return redirect()->back()->with('error', 'Active PSLE exam year is not configured.');
        }

        // Prevent duplicate active subject ownership for the same school.
        $exists = \App\Models\MarkEntryAssignment::where([
            'exam_year_id' => $activeYear->id,
            'exam_type_id' => $psleType->id,
            'school_id' => $validated['school_id'],
            'subject_id' => $validated['subject_id'],
            'assignment_type' => $validated['assignment_type'],
            'status' => 'active',
            'active_lock' => 1,
        ])->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'This subject has already been taken by another Mark Entry Officer for this school.');
        }

        try {
            $assignment = \App\Models\MarkEntryAssignment::create([
                'exam_year_id' => $activeYear->id,
                'exam_type_id' => $psleType->id,
                'region_id' => $validated['region_id'],
                'district_id' => $validated['district_id'],
                'school_id' => $validated['school_id'],
                'subject_id' => $validated['subject_id'],
                'marking_centre_id' => $validated['marking_centre_id'],
                'assigned_to' => $validated['assigned_to'],
                'assigned_by' => $user->id,
                'assignment_type' => $validated['assignment_type'],
                'status' => 'active',
                'active_lock' => 1,
                'starts_at' => now(),
            ]);
        } catch (QueryException $exception) {
            return redirect()->back()->with('error', 'This subject has already been taken by another Mark Entry Officer for this school.');
        }

        $this->logSubjectAssignmentAction(
            action: 'SUBJECT_TAKEN_BY_MEO',
            actorId: $user->id,
            officerId: (int) $assignment->assigned_to,
            assignment: $assignment,
            oldStatus: null,
            newStatus: 'active'
        );

        return redirect()->back()->with('success', 'Assignment created successfully.');
    }

    public function revokeAssignment(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->isAdmin() && !$this->isReoUser($user)) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $assignment = \App\Models\MarkEntryAssignment::findOrFail($id);
        if (! $this->assignmentBelongsToReoRegion($assignment, $user)) {
            abort(403, 'You cannot revoke an assignment outside your assigned region.');
        }

        // Check if marks exist for this assignment (or officer, school, subject, year)
        $marksExist = \App\Models\RawMark::where('subject_id', $assignment->subject_id)
            ->where(function($query) use ($assignment) {
                $query->whereHas('batch', function($q) use ($assignment) {
                    $q->where('school_id', $assignment->school_id)
                      ->where('exam_year_id', $assignment->exam_year_id)
                      ->where(function($sub) use ($assignment) {
                          $sub->where('created_by', $assignment->assigned_to)
                              ->orWhere('assignment_id', $assignment->id);
                      });
                });
            })->exists();

        if ($marksExist) {
            $oldStatus = $assignment->status;
            $assignment->status = 'revoked';
            $assignment->active_lock = null;
            $assignment->ends_at = now();
            $assignment->save();

            $this->logSubjectAssignmentAction(
                action: 'SUBJECT_ASSIGNMENT_DEACTIVATED',
                actorId: $user->id,
                officerId: (int) $assignment->assigned_to,
                assignment: $assignment,
                oldStatus: $oldStatus,
                newStatus: 'revoked'
            );

            return redirect()->back()->with('warning', 'This assignment has marks and cannot be deleted. It has been deactivated instead.');
        }

        $oldStatus = $assignment->status;
        $assignment->status = 'revoked';
        $assignment->active_lock = null;
        $assignment->ends_at = now();
        $assignment->save();

        $this->logSubjectAssignmentAction(
            action: 'SUBJECT_ASSIGNMENT_CANCELLED',
            actorId: $user->id,
            officerId: (int) $assignment->assigned_to,
            assignment: $assignment,
            oldStatus: $oldStatus,
            newStatus: 'revoked'
        );

        $assignment->delete(); // Soft delete

        return redirect()->back()->with('success', 'Assignment revoked successfully.');
    }


    public function saveMark(Request $request)
    {
        $startedAt = microtime(true);
        $responseStatus = 200;
        $debugContext = $this->psleSaveDebugContext($request);
        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        Log::info('[PSLE_MARK_SAVE_PERFORMANCE] save request received', $debugContext + [
            'request_start_time' => now()->toIso8601String(),
        ]);

        try {
        $subjectId = $request->input('subject_id');
        $assignmentId = $request->input('assignment_id');
        if ($assignmentId) {
            $assignment = \App\Models\MarkEntryAssignment::find($assignmentId);
            if ($assignment) {
                $subjectId = $assignment->subject_id;
            }
        }
        $subject = $subjectId ? \App\Models\Subject::find($subjectId) : null;
        $maxScore = $subject ? $subject->max_marks : 50;

        $validator = Validator::make($request->all(), [
            'candidate_id' => 'required|exists:candidates,id',
            'assignment_id' => 'nullable|exists:mark_entry_assignments,id',
            'school_id' => 'required_without:assignment_id|exists:schools,id',
            'subject_id' => 'required_without:assignment_id|exists:subjects,id',
            'exam_year_id' => 'required_without:assignment_id|exists:exam_years,id',
            'score' => 'nullable|numeric|min:0|max:' . $maxScore,
        ]);

        if ($validator->fails()) {
            $responseStatus = 422;

            return response()->json([
                'ok' => false,
                'success' => false,
                'type' => 'validation_error',
                'message' => 'Invalid mark value or save context.',
                'errors' => $validator->errors(),
            ], $responseStatus);
        }

        $validated = $validator->validated();
        $user = $request->user();
        if (! $user) {
            $responseStatus = 401;

            return response()->json([
                'ok' => false,
                'success' => false,
                'type' => 'authentication_error',
                'message' => 'Your session has expired. Please refresh and login again.',
            ], $responseStatus);
        }

        if ($this->isAdminOrReo($user)) {
            $responseStatus = 403;
            return response()->json([
                'ok' => false,
                'success' => false,
                'type' => 'authorization_error',
                'message' => 'Admin and REO accounts can view mark sheets only. Mark saving is not permitted.',
            ], $responseStatus);
        }

        $isAdmin = $user->isAdmin();
        $isTrulyAdmin = $user->isAdmin(); // For internal checks
        $isReo = $this->isReoUser($user) || ($user->region_id && !$user->hasRole('officer') && ! $this->isMarkOfficerUser($user) && !$isTrulyAdmin);
        $isMarkOfficer = $this->isMarkOfficerUser($user);

        $assignmentId = isset($validated['assignment_id']) && $validated['assignment_id'] !== '' ? $validated['assignment_id'] : null;
        $schoolId = $validated['school_id'] ?? null;
        $subjectId = $validated['subject_id'] ?? null;
        $examYearId = $validated['exam_year_id'] ?? null;

        if ($assignmentId) {
            $assignment = \App\Models\MarkEntryAssignment::findOrFail($assignmentId);
            if (!$isAdmin && $assignment->assigned_to !== $user->id && !$isReo) {
                $responseStatus = 403;
                return response()->json(['ok' => false, 'success' => false, 'type' => 'authorization_error', 'message' => 'Unauthorized assignment.'], $responseStatus);
            }
            if ($assignment->status !== 'active') {
                $responseStatus = 403;
                return response()->json(['ok' => false, 'success' => false, 'type' => 'authorization_error', 'message' => 'Assignment is not active.'], $responseStatus);
            }
            $schoolId = $assignment->school_id;
            $subjectId = $assignment->subject_id;
            $examYearId = $assignment->exam_year_id;
        } else {
            // Regional access validation
            $school = \App\Models\School::findOrFail($schoolId);
            if (!$isAdmin && $user->region_id && $school->region_id !== $user->region_id) {
                $responseStatus = 403;
                return response()->json(['ok' => false, 'success' => false, 'type' => 'authorization_error', 'message' => 'Unauthorized: School is outside your region.'], $responseStatus);
            }

            if ($isMarkOfficer && !$isTrulyAdmin) {
                $hasRegionalAssignment = ($user->region_id && (int) $school->region_id === (int) $user->region_id);
                if (!$hasRegionalAssignment) {
                    $psleExamTypeId = (int) \App\Models\ExamType::where('code', 'PSLE')->value('id');
                    $hasAssignment = \App\Models\MarkEntryAssignment::where([
                        'assigned_to' => $user->id,
                        'school_id' => $schoolId,
                        'subject_id' => $subjectId,
                        'exam_year_id' => $examYearId,
                        'exam_type_id' => $psleExamTypeId,
                        'status' => 'active',
                    ])->first();

                    if (!$hasAssignment) {
                        $responseStatus = 403;
                        return response()->json([
                            'ok' => false,
                            'success' => false,
                            'type' => 'authorization_error',
                            'message' => 'Unauthorized: You do not have an active assignment to enter marks for this school and subject.',
                        ], $responseStatus);
                    }
                }
            }
        }

        // Ensure $school is always available for batch creation
        if (!isset($school)) {
            $school = \App\Models\School::findOrFail($schoolId);
        }

        $psleExamTypeId = (int) \App\Models\ExamType::where('code', 'PSLE')->value('id');
        $validPsleSubject = \App\Models\Subject::query()
            ->whereKey($subjectId)
            ->where('exam_type_id', $psleExamTypeId)
            ->where('is_active', true)
            ->exists();

        if (! $validPsleSubject) {
            $responseStatus = 422;
            return response()->json([
                'ok' => false,
                'success' => false,
                'type' => 'validation_error',
                'message' => 'Please select a valid active PSLE subject.',
            ], $responseStatus);
        }

        $candidate = \App\Models\Candidate::findOrFail($validated['candidate_id']);
        $debugContext['candidate_number'] = $candidate->candidate_id;
        if ((int) $candidate->school_id !== (int) $schoolId) {
            $responseStatus = 422;
            return response()->json([
                'ok' => false,
                'success' => false,
                'type' => 'validation_error',
                'message' => 'Candidate is not registered under the selected school.',
            ], $responseStatus);
        }

        $candidateRegistered = $candidate->examRegistrations()
            ->where('exam_type_id', $psleExamTypeId)
            ->where('exam_year_id', $examYearId)
            ->exists();

        if (! $candidateRegistered) {
            $responseStatus = 422;
            return response()->json([
                'ok' => false,
                'success' => false,
                'type' => 'validation_error',
                'message' => 'Candidate is not registered for PSLE in the selected exam year.',
            ], $responseStatus);
        }

        // PSLE candidates are registered for all subjects, no CandidateSubjectSelection validation required.
        $subjectSelectionExists = false;

        $returnedVerification = null;
        if (\Illuminate\Support\Facades\Schema::hasTable('mark_verifications')) {
            $returnedVerification = \App\Models\MarkVerification::where([
                    'candidate_id' => $validated['candidate_id'],
                    'subject_id' => $subjectId,
                    'exam_year_id' => $examYearId,
                    'status' => \App\Models\MarkVerification::STATUS_RETURNED,
                ])
                ->when(!$isAdmin && $isMarkOfficer, fn($q) => $q->where('returned_to_user_id', $user->id))
                ->first();
        }

        // Block changes if a batch already exists in a non-draft state (submitted, approved, locked)
        $existingNonDraftBatch = \App\Models\MarkImportBatch::where([
            'school_id' => $schoolId,
            'subject_id' => $subjectId,
            'exam_year_id' => $examYearId,
        ])->where('status', '!=', 'draft')->first();

        if ($existingNonDraftBatch && !$returnedVerification) {
            $responseStatus = 403;
            return response()->json([
                'ok' => false,
                'success' => false,
                'type' => 'authorization_error',
                'message' => 'This sheet is currently in state "' . ucfirst($existingNonDraftBatch->status) . '" and cannot be modified.',
            ], $responseStatus);
        }

        // Find or create Batch for this scope
        $batch = \App\Models\MarkImportBatch::where([
            'school_id' => $schoolId,
            'subject_id' => $subjectId,
            'exam_year_id' => $examYearId,
            'created_by' => $user->id,
            'status' => 'draft',
            'batch_type' => 'manual',
        ]);

        if ($assignmentId) {
            $batch->where('assignment_id', $assignmentId);
        }

        $batch = $batch->first();

        if (!$batch) {
            $subject   = \App\Models\Subject::find($subjectId);
            $examYear  = \App\Models\ExamYear::find($examYearId);

            $baseCode = strtoupper(optional($school->region)->name ?? 'REG')
                                   . '_PSLE_' . ($examYear->year_label ?? 'YR')
                                   . '_' . substr(str_replace(' ', '_', strtoupper($school->name)), 0, 20)
                                   . '_' . strtoupper($subject->code ?? 'SUB')
                                   . '_' . strtoupper(str_replace(' ', '', substr($user->name, 0, 10)));
            
            $batchCode = $baseCode;
            $counter = 1;
            while (\App\Models\MarkImportBatch::where('batch_code', $batchCode)->exists()) {
                $batchCode = substr($baseCode, 0, 180) . '_' . $counter;
                $counter++;
            }

            $batch = \App\Models\MarkImportBatch::create([
                'exam_year'    => $examYear?->year_label,
                'exam_year_id' => $examYearId,
                'exam_type_id' => \App\Models\ExamType::where('code', 'PSLE')->value('id'),
                'region_id'    => $school->region_id,
                'district_id'  => $school->district_id,
                'school_id'    => $schoolId,
                'subject_id'   => $subjectId,
                'assignment_id'=> $assignmentId,
                'created_by'   => $user->id,
                'status'       => 'draft',
                'batch_type'   => 'manual',
                'batch_name'   => ($isMarkOfficer ? 'Officer Entry' : 'Manual Entry')
                                   . ' - ' . $school->name . ' (' . ($subject->name ?? '') . ')',
                'batch_code'   => $batchCode,
            ]);
        }

        $marksForCandidateSubject = \App\Models\RawMark::query()
            ->where('candidate_id', $validated['candidate_id'])
            ->where('subject_id', $subjectId)
            ->where(function ($schoolQuery) use ($schoolId) {
                $schoolQuery->where('school_id', $schoolId)
                    ->orWhereHas('candidate', fn($candidateQuery) => $candidateQuery->where('school_id', $schoolId))
                    ->orWhereHas('batch', fn($batchQuery) => $batchQuery->where('school_id', $schoolId));
            })
            ->where(function ($yearQuery) use ($examYearId) {
                $yearQuery->where('exam_year_id', $examYearId)
                    ->orWhereHas('batch', fn($batchQuery) => $batchQuery->where('exam_year_id', $examYearId));
            })
            ->with('batch')
            ->orderByRaw('CASE WHEN exam_year_id IS NOT NULL THEN 0 ELSE 1 END')
            ->latest('updated_at')
            ->get();

        $mark = $marksForCandidateSubject->first();

        if ($validated['score'] === null || $validated['score'] === '') {
            if ($marksForCandidateSubject->isNotEmpty()) {
                $blockedMark = $marksForCandidateSubject->first(function ($candidateMark) {
                    return $candidateMark->is_locked || ($candidateMark->batch && $candidateMark->batch->status !== 'draft');
                });

                if ($blockedMark) {
                    $responseStatus = 403;
                    return response()->json([
                        'ok' => false,
                        'success' => false,
                        'type' => 'authorization_error',
                        'message' => 'This mark record is locked or belongs to a non-draft batch and cannot be deleted.',
                    ], $responseStatus);
                }

                DB::transaction(function () use ($marksForCandidateSubject) {
                    $rawMarkIds = $marksForCandidateSubject->pluck('id')->all();

                    \App\Models\MarkEntryValidation::whereIn('raw_mark_id', $rawMarkIds)->delete();
                    \App\Models\MarkEntryOutlier::whereIn('raw_mark_id', $rawMarkIds)->delete();
                    \App\Models\MarkEntryChange::whereIn('raw_mark_id', $rawMarkIds)->delete();

                    if (\Illuminate\Support\Facades\Schema::hasTable('mark_verifications')) {
                        \App\Models\MarkVerification::whereIn('raw_mark_id', $rawMarkIds)->delete();
                    }

                    \App\Models\RawMark::whereIn('id', $rawMarkIds)->delete();
                });

                \Log::info('PSLE manual mark cleared.', [
                    'candidate_id' => $validated['candidate_id'],
                    'exam_year_id' => $examYearId,
                    'school_id' => $schoolId,
                    'subject_id' => $subjectId,
                    'user_id' => $user->id,
                    'deleted_raw_mark_ids' => $marksForCandidateSubject->pluck('id')->all(),
                ]);

                $subjectForActivity = \App\Models\Subject::find($subjectId);
                $this->logPsleActivity([
                    'event_type' => 'mark_cleared',
                    'title' => 'Mark cleared',
                    'description' => sprintf(
                        '%s cleared %s marks for %s.',
                        $user->name,
                        $subjectForActivity?->name ?? 'PSLE',
                        $school->name
                    ),
                    'exam_year_id' => $examYearId,
                    'region_id' => $school->region_id,
                    'district_id' => $school->district_id,
                    'school_id' => $schoolId,
                    'subject_id' => $subjectId,
                    'user_id' => $user->id,
                    'affected_candidates' => 1,
                    'affected_marks' => $marksForCandidateSubject->count(),
                    'metadata' => ['deleted_raw_mark_ids' => $marksForCandidateSubject->pluck('id')->all()],
                ]);
            }
            return response()->json([
                'ok' => true,
                'success' => true,
                'status' => 'saved',
                'message' => 'Mark cleared.',
                'candidate_id' => (int) $validated['candidate_id'],
                'mark' => null,
                'raw_mark_id' => null,
                'batch_status' => $batch->status,
                'saved_at' => now()->toIso8601String(),
                'completion' => $this->psleScoreSheetCompletion(
                    (int) $examYearId,
                    (int) $schoolId,
                    (int) $subjectId,
                    $psleExamTypeId,
                    $subjectSelectionExists
                ),
            ]);
        }

        if ($mark) {
            // Guard: never update a locked record or a record in a non-draft batch
            if ($mark->is_locked) {
                $responseStatus = 403;
                return response()->json([
                    'ok' => false,
                    'success' => false,
                    'type' => 'authorization_error',
                    'message' => 'This mark record is locked and cannot be edited. Contact an Administrator.',
                ], $responseStatus);
            }
            if ($mark->batch && $mark->batch->status !== 'draft' && !$returnedVerification) {
                $responseStatus = 403;
                return response()->json([
                    'ok' => false,
                    'success' => false,
                    'type' => 'authorization_error',
                    'message' => 'This mark belongs to a batch that is in state "' . ucfirst($mark->batch->status) . '" and cannot be modified.',
                ], $responseStatus);
            }
            $oldScore = $mark->paper_1_marks;

            DB::transaction(function () use ($mark, $validated, $batch, $assignmentId, $examYearId, $schoolId, $user, $oldScore, $request) {
                $mark->update([
                    'paper_1_marks' => $validated['score'],
                    'mark_import_batch_id' => $batch->id,
                    'assignment_id' => $assignmentId,
                    'exam_year_id' => $examYearId,
                    'school_id' => $schoolId,
                    'updated_by' => $user->id,
                ]);

                if ((string) $oldScore !== (string) $validated['score']) {
                    \App\Models\MarkEntryChange::create([
                        'raw_mark_id' => $mark->id,
                        'changed_by' => $user->id,
                        'change_type' => 'edit',
                        'field_name' => 'paper_1_marks',
                        'old_value' => $oldScore,
                        'new_value' => $validated['score'],
                        'reason' => 'PSLE manual mark edited from entry sheet.',
                        'changed_at' => now(),
                        'ip_address' => $request->ip(),
                    ]);
                }
            });

            $saveAction = 'updated';
        } else {
            $mark = DB::transaction(function () use ($batch, $examYearId, $schoolId, $validated, $candidate, $subjectId, $assignmentId, $user) {
                return \App\Models\RawMark::updateOrCreate(
                    [
                        'exam_year_id' => $examYearId,
                        'school_id' => $schoolId,
                        'candidate_id' => $validated['candidate_id'],
                        'subject_id' => $subjectId,
                    ],
                    [
                        'mark_import_batch_id'   => $batch->id,
                        // candidate_index_number: use candidate_id field (PSLE index number stored there)
                        'candidate_index_number' => $candidate->candidate_id ?? '',
                        'full_name'              => $candidate->full_name ?? '',
                        'assignment_id'          => $assignmentId,
                        'entered_by'             => $user->id,
                        'updated_by'             => $user->id,
                        'paper_1_marks'          => $validated['score'],
                        'row_number'             => 0,
                        'raw_data'               => [],
                    ]
                );
            });

            $saveAction = $mark->wasRecentlyCreated ? 'created' : 'updated';
        }

        if ($marksForCandidateSubject->count() > 1) {
            $duplicateIds = $marksForCandidateSubject->pluck('id')->filter(fn($id) => (int) $id !== (int) $mark->id)->values();
            DB::transaction(function () use ($duplicateIds) {
                if ($duplicateIds->isEmpty()) {
                    return;
                }

                \App\Models\MarkEntryValidation::whereIn('raw_mark_id', $duplicateIds)->delete();
                \App\Models\MarkEntryOutlier::whereIn('raw_mark_id', $duplicateIds)->delete();
                \App\Models\MarkEntryChange::whereIn('raw_mark_id', $duplicateIds)->delete();

                if (\Illuminate\Support\Facades\Schema::hasTable('mark_verifications')) {
                    \App\Models\MarkVerification::whereIn('raw_mark_id', $duplicateIds)->delete();
                }

                \App\Models\RawMark::whereIn('id', $duplicateIds)->delete();
            });
        }

        \Log::info('PSLE manual mark saved.', [
            'action' => $saveAction,
            'raw_mark_id' => $mark->id,
            'candidate_id' => $mark->candidate_id,
            'exam_year_id' => $examYearId,
            'school_id' => $schoolId,
            'subject_id' => $subjectId,
            'assignment_id' => $assignmentId,
            'user_id' => $user->id,
        ]);

        $subjectForActivity = \App\Models\Subject::find($subjectId);
        $this->logPsleActivity([
            'event_type' => $saveAction === 'updated' ? 'mark_updated' : 'mark_saved',
            'title' => $saveAction === 'updated' ? 'Marks updated' : 'Marks saved',
            'description' => sprintf(
                '%s %s %s marks for %s.',
                $user->name,
                $saveAction === 'updated' ? 'updated' : 'saved',
                $subjectForActivity?->name ?? 'PSLE',
                $school->name
            ),
            'exam_year_id' => $examYearId,
            'region_id' => $school->region_id,
            'district_id' => $school->district_id,
            'school_id' => $schoolId,
            'subject_id' => $subjectId,
            'user_id' => $user->id,
            'affected_candidates' => 1,
            'affected_marks' => 1,
            'metadata' => [
                'raw_mark_id' => $mark->id,
                'candidate_id' => $mark->candidate_id,
                'batch_id' => $batch->id,
                'score' => $validated['score'],
            ],
        ]);

        if ($returnedVerification) {
            $returnedVerification->update([
                'status' => \App\Models\MarkVerification::STATUS_CORRECTED,
            ]);

            \App\Models\GovernanceAuditLog::log('panel_returned_mark_corrected', userId: $user->id, data: [
                'raw_mark_id' => $mark->id,
                'candidate_id' => $mark->candidate_id,
                'subject_id' => $mark->subject_id,
                'school_id' => $batch->school_id,
                'exam_year_id' => $examYearId,
                'old_status' => \App\Models\MarkVerification::STATUS_RETURNED,
                'new_status' => \App\Models\MarkVerification::STATUS_CORRECTED,
                'ip_address' => $request->ip(),
            ]);
        }

        return response()->json([
            'ok' => true,
            'success' => true,
            'status' => 'entered',
            'message' => 'Mark saved.',
            'candidate_id' => (int) $mark->candidate_id,
            'mark' => $mark->paper_1_marks,
            'mark_id' => $mark->id,
            'raw_mark_id' => $mark->id,
            'batch_status' => $batch->status,
            'saved_at' => now()->toIso8601String(),
            'completion' => $this->psleScoreSheetCompletion(
                (int) $examYearId,
                (int) $schoolId,
                (int) $subjectId,
                $psleExamTypeId,
                $subjectSelectionExists
            ),
        ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $responseStatus = 422;
            Log::warning('[PSLE_MARK_SAVE_PERFORMANCE] save model not found', $debugContext + [
                'response_status' => $responseStatus,
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
                'memory_usage' => memory_get_usage(true),
                'db_query_count' => $queryCount,
            ]);

            return response()->json([
                'ok' => false,
                'success' => false,
                'type' => 'validation_error',
                'message' => 'The selected candidate, school, subject, assignment, or exam year could not be found.',
            ], $responseStatus);
        } catch (QueryException $e) {
            $responseStatus = $this->isTemporaryDatabaseException($e) ? 503 : 500;
            Log::error('[PSLE_MARK_SAVE_PERFORMANCE] save query exception', $debugContext + [
                'response_status' => $responseStatus,
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
                'memory_usage' => memory_get_usage(true),
                'db_query_count' => $queryCount,
            ]);

            return response()->json([
                'ok' => false,
                'success' => false,
                'type' => $responseStatus === 503 ? 'temporary_unavailable' : 'server_error',
                'message' => $responseStatus === 503
                    ? 'Server is temporarily busy. The system will retry automatically.'
                    : 'The mark could not be saved. Please retry.',
            ], $responseStatus);
        } catch (Throwable $e) {
            $responseStatus = 500;
            Log::error('[PSLE_MARK_SAVE_PERFORMANCE] save exception', $debugContext + [
                'response_status' => $responseStatus,
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
                'memory_usage' => memory_get_usage(true),
                'db_query_count' => $queryCount,
            ]);

            return response()->json([
                'ok' => false,
                'success' => false,
                'type' => 'server_error',
                'message' => 'The mark could not be saved. Please retry.',
            ], $responseStatus);
        } finally {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            $this->recordPsleSaveMetric($responseStatus, $durationMs);

            Log::info('[PSLE_MARK_SAVE_PERFORMANCE] save request completed', $debugContext + [
                'response_status' => $responseStatus,
                'duration_ms' => $durationMs,
                'memory_usage' => memory_get_usage(true),
                'db_query_count' => $queryCount,
            ]);
        }
    }

    public function health(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->isAdmin()) {
            return response()->json([
                'ok' => false,
                'type' => 'authorization_error',
                'message' => 'Admin access is required.',
            ], 403);
        }

        $checks = [
            'app' => 'ok',
            'db' => 'unknown',
            'exam_year_query' => 'unknown',
            'psle_subject_query' => 'unknown',
            'cache' => 'unknown',
        ];

        try {
            DB::connection()->getPdo();
            $checks['db'] = 'ok';
            $checks['exam_year_query'] = ExamYear::query()->limit(1)->exists() ? 'ok' : 'empty';
            $psleExamTypeId = \App\Models\ExamType::where('code', 'PSLE')->value('id');
            $checks['psle_subject_query'] = Subject::query()
                ->where('exam_type_id', $psleExamTypeId)
                ->where('is_active', true)
                ->exists() ? 'ok' : 'empty';
        } catch (Throwable $exception) {
            $checks['db'] = 'failed';
            $checks['db_error'] = $exception->getMessage();
        }

        try {
            cache()->put('psle_mark_entry_health_check', now()->toIso8601String(), 60);
            $checks['cache'] = cache()->has('psle_mark_entry_health_check') ? 'ok' : 'failed';
        } catch (Throwable $exception) {
            $checks['cache'] = 'failed';
            $checks['cache_error'] = $exception->getMessage();
        }

        return response()->json([
            'ok' => ! in_array('failed', $checks, true),
            'environment' => app()->environment(),
            'debug' => (bool) config('app.debug'),
            'db_driver' => DB::connection()->getDriverName(),
            'database_risk' => DB::connection()->getDriverName() === 'sqlite'
                ? 'SQLite is high risk for production mark entry because concurrent writes can lock the database.'
                : null,
            'queue_connection' => config('queue.default'),
            'session_driver' => config('session.driver'),
            'cache_driver' => config('cache.default'),
            'php' => [
                'max_execution_time' => ini_get('max_execution_time'),
                'memory_limit' => ini_get('memory_limit'),
            ],
            'current_exam_year' => ExamYear::query()->where('is_active', true)->value('year_label'),
            'active_psle_subjects' => Subject::query()
                ->where('exam_type_id', \App\Models\ExamType::where('code', 'PSLE')->value('id'))
                ->where('is_active', true)
                ->count(),
            'mark_save_metrics_today' => $this->psleSaveMetricsToday(),
            'checks' => $checks,
        ]);
    }

    private function psleScoreSheetCompletion(int $examYearId, int $schoolId, int $subjectId, int $psleExamTypeId, bool $subjectSelectionExists): array
    {
        $candidateQuery = \App\Models\Candidate::query()
            ->where('school_id', $schoolId)
            ->whereIn('exam_type', ['PSLE', 'PRIMARY'])
            ->where('is_active', true)
            ->whereHas('examRegistrations', fn($query) => $query
                ->where('exam_type_id', $psleExamTypeId)
                ->where('exam_year_id', $examYearId))
            ->when($subjectSelectionExists, fn($query) => $query->whereHas('subjectSelections', fn($selectionQuery) => $selectionQuery
                ->where('exam_type_id', $psleExamTypeId)
                ->where('exam_year_id', $examYearId)
                ->where('subject_id', $subjectId)));

        $candidateIds = $candidateQuery->pluck('id');
        $totalCandidates = $candidateIds->count();

        $savedCount = $totalCandidates > 0
            ? \App\Models\RawMark::query()
                ->whereIn('candidate_id', $candidateIds)
                ->where('exam_year_id', $examYearId)
                ->where('school_id', $schoolId)
                ->where('subject_id', $subjectId)
                ->whereNotNull('paper_1_marks')
                ->count()
            : 0;

        return [
            'total_candidates' => $totalCandidates,
            'saved_count' => $savedCount,
            'pending_count' => max(0, $totalCandidates - $savedCount),
            'is_complete' => $totalCandidates > 0 && $savedCount === $totalCandidates,
        ];
    }

    public function verifyOutlier(Request $request, $id)
    {
        $outlier = \App\Models\MarkEntryOutlier::findOrFail($id);
        $user = $request->user();
        
        // REO or Admin can verify
        if (!$user->isAdmin() && !$user->hasRole('reo')) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $this->outlierService->verifyOutlier($outlier, $user, $request->input('comment'));
        
        return redirect()->back()->with('success', 'Outlier verified as correct.');
    }

    public function resolveOutlier(Request $request, $id)
    {
        $outlier = \App\Models\MarkEntryOutlier::findOrFail($id);
        $user = $request->user();
        
        if (!$user->isAdmin() && !$user->hasRole('reo')) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $this->outlierService->resolveOutlier($outlier, $user, $request->input('comment'));
        
        return redirect()->back()->with('success', 'Outlier resolved.');
    }

    public function escalateOutlier(Request $request, $id)
    {
        $outlier = \App\Models\MarkEntryOutlier::findOrFail($id);
        $user = $request->user();
        
        $outlier->update([
            'status' => 'escalated',
            'severity' => 'critical',
            'message' => $outlier->message . ' (Escalated: ' . $request->input('comment') . ')'
        ]);

        \App\Models\GovernanceAuditLog::log(
            'OUTLIER_ESCALATED',
            $outlier->id,
            $user->id,
            ['comment' => $request->input('comment')]
        );

        return redirect()->back()->with('success', 'Outlier escalated to high-level review.');
    }
    public function singleValidate(Request $request)
    {
        if ($this->isAdminOrReo($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Admin and REO accounts cannot validate or bulk import marks.'
            ], 403);
        }
        $validated = $request->validate([
            'exam_year' => ['required', 'string'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        return response()->json(
            $this->service->validateSingleCsv(
                $request->file('file'),
                (string) $validated['exam_year'],
                (int) $validated['school_id'],
                (int) $validated['subject_id']
            )
        );
    }

    public function singleCommit(Request $request)
    {
        if ($this->isAdminOrReo($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Admin and REO accounts cannot validate or bulk import marks.'
            ], 403);
        }
        $validated = $request->validate([
            'exam_year' => ['required', 'string'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $result = $this->service->commitSingleCsv(
            $request->file('file'),
            (string) $validated['exam_year'],
            (int) $validated['school_id'],
            (int) $validated['subject_id'],
            auth()->id() ?? 1
        );

        if (($result['success'] ?? false) === true) {
            $school = School::find($validated['school_id']);
            $subject = Subject::find($validated['subject_id']);
            $examYear = ExamYear::where('year_label', (string) $validated['exam_year'])->first();
            $this->logPsleActivity([
                'event_type' => 'marks_imported',
                'title' => 'Bulk marks imported',
                'description' => sprintf(
                    '%s %s marks imported for %s.',
                    number_format((int) data_get($result, 'totals.valid_rows', 0)),
                    $subject?->name ?? 'PSLE',
                    $school?->name ?? 'selected school'
                ),
                'exam_year_id' => $examYear?->id,
                'region_id' => $school?->region_id,
                'district_id' => $school?->district_id,
                'school_id' => $school?->id,
                'subject_id' => $subject?->id,
                'user_id' => auth()->id(),
                'affected_marks' => (int) data_get($result, 'totals.valid_rows', 0),
                'metadata' => [
                    'source' => 'single_csv',
                    'batch_id' => data_get($result, 'batch.id'),
                    'batch_code' => data_get($result, 'batch.batch_code'),
                ],
            ]);
        }

        return response()->json($result);
    }

    public function schoolValidateZip(Request $request)
    {
        if ($this->isAdminOrReo($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Admin and REO accounts cannot validate or bulk import marks.'
            ], 403);
        }
        $validated = $request->validate([
            'exam_year' => ['required', 'string'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'file' => ['required', 'file', 'mimes:zip'],
        ]);

        return response()->json(
            $this->service->validateSchoolZip(
                $request->file('file'),
                (string) $validated['exam_year'],
                (int) $validated['school_id']
            )
        );
    }

    public function schoolCommitZip(Request $request)
    {
        if ($this->isAdminOrReo($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Admin and REO accounts cannot validate or bulk import marks.'
            ], 403);
        }
        $validated = $request->validate([
            'exam_year' => ['required', 'string'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'file' => ['required', 'file', 'mimes:zip'],
        ]);

        $result = $this->service->commitSchoolZip(
            $request->file('file'),
            (string) $validated['exam_year'],
            (int) $validated['school_id'],
            auth()->id() ?? 1
        );

        if (($result['success'] ?? false) === true) {
            $school = School::find($validated['school_id']);
            $examYear = ExamYear::where('year_label', (string) $validated['exam_year'])->first();
            $this->logPsleActivity([
                'event_type' => 'marks_imported',
                'title' => 'Bulk marks imported',
                'description' => number_format((int) data_get($result, 'totals.valid_rows', 0)) . ' PSLE marks imported for ' . ($school?->name ?? 'selected school') . '.',
                'exam_year_id' => $examYear?->id,
                'region_id' => $school?->region_id,
                'district_id' => $school?->district_id,
                'school_id' => $school?->id,
                'user_id' => auth()->id(),
                'affected_marks' => (int) data_get($result, 'totals.valid_rows', 0),
                'metadata' => ['source' => 'school_zip', 'batch_count' => count($result['batches'] ?? [])],
            ]);
        }

        return response()->json($result);
    }

    public function districtValidateZip(Request $request)
    {
        if ($this->isAdminOrReo($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Admin and REO accounts cannot validate or bulk import marks.'
            ], 403);
        }
        $validated = $request->validate([
            'exam_year' => ['required', 'string'],
            'district_id' => ['required', 'integer', 'exists:districts,id'],
            'file' => ['required', 'file', 'mimes:zip'],
        ]);

        return response()->json(
            $this->service->validateDistrictZip(
                $request->file('file'),
                (string) $validated['exam_year'],
                (int) $validated['district_id']
            )
        );
    }

    public function districtCommitZip(Request $request)
    {
        if ($this->isAdminOrReo($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Admin and REO accounts cannot validate or bulk import marks.'
            ], 403);
        }
        $validated = $request->validate([
            'exam_year' => ['required', 'string'],
            'district_id' => ['required', 'integer', 'exists:districts,id'],
            'file' => ['required', 'file', 'mimes:zip'],
        ]);

        $result = $this->service->commitDistrictZip(
            $request->file('file'),
            (string) $validated['exam_year'],
            (int) $validated['district_id'],
            auth()->id() ?? 1
        );

        if (($result['success'] ?? false) === true) {
            $district = \App\Models\District::find($validated['district_id']);
            $examYear = ExamYear::where('year_label', (string) $validated['exam_year'])->first();
            $this->logPsleActivity([
                'event_type' => 'marks_imported',
                'title' => 'Bulk marks imported',
                'description' => number_format((int) data_get($result, 'totals.valid_rows', 0)) . ' PSLE marks imported for ' . ($district?->name ?? 'selected district') . '.',
                'exam_year_id' => $examYear?->id,
                'region_id' => $district?->region_id,
                'district_id' => $district?->id,
                'user_id' => auth()->id(),
                'affected_marks' => (int) data_get($result, 'totals.valid_rows', 0),
                'metadata' => ['source' => 'district_zip', 'batch_count' => count($result['batches'] ?? [])],
            ]);
        }

        return response()->json($result);
    }

    public function recentBatches()
    {
        return response()->json([
            'data' => $this->service->recentBatches(),
        ]);
    }

    public function lifecycleDashboard(Request $request)
    {
        return response()->json([
            'data' => $this->service->lifecycleDashboard($request->only([
                'exam_year', 'region_id', 'district_id', 'school_id', 'subject_id',
            ])),
        ]);
    }



    public function scoresheetSubjects(Request $request)
    {
        $validated = $request->validate([
            'exam_year' => ['required', 'string'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'mode' => ['nullable', 'in:approved,all'],
        ]);

        return response()->json([
            'data' => $this->scoresheetService
                ->getSubjectsWithMarks(
                    (string) $validated['exam_year'],
                    (int) $validated['school_id'],
                    (string) ($validated['mode'] ?? 'approved')
                )
                ->values()
                ->all(),
        ]);
    }



    public function auditSummary(Request $request)
    {
        return response()->json([
            'data' => $this->service->auditSummary($request->only([
                'exam_year', 'region_id', 'district_id', 'school_id', 'subject_id',
            ])),
        ]);
    }

    public function administrationSummary(Request $request)
    {
        return response()->json([
            'data' => $this->service->administrationSummary($request->only([
                'exam_year', 'region_id', 'district_id', 'school_id', 'subject_id',
            ])),
        ]);
    }

    public function bulkValidate(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $isTrulyAdmin = $user->isAdmin();
        $isReo = $this->isReoUser($user) || ($user->region_id && !$user->hasRole('officer') && !$this->isMarkOfficerUser($user) && !$isTrulyAdmin);
        $isMarkOfficer = $this->isMarkOfficerUser($user);
        $isAdmin = $isTrulyAdmin;

        if (!$isAdmin && !$isReo && !$isMarkOfficer) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $activeYear = \App\Models\ExamYear::where('is_active', true)->first();
        if (!$activeYear) {
            return response()->json(['success' => false, 'message' => 'Active exam year is not configured.'], 422);
        }

        $psleExamType = \App\Models\ExamType::where('code', 'PSLE')->first();
        $psleExamTypeId = $psleExamType ? $psleExamType->id : null;

        // Build query for batches
        $query = \App\Models\MarkImportBatch::with(['school', 'subject', 'user'])
            ->where('exam_type_id', $psleExamTypeId);

        if ($request->input('all_drafts')) {
            // Apply current filters
            $selectedYearId = $request->input('exam_year_id');
            if ($selectedYearId) $query->where('exam_year_id', $selectedYearId);

            $selectedRegionId = $request->input('region_id');
            if ($selectedRegionId) $query->where('region_id', $selectedRegionId);

            $selectedDistrictId = $request->input('district_id');
            if ($selectedDistrictId) $query->where('district_id', $selectedDistrictId);

            $selectedSchoolId = $request->input('school_id');
            if ($selectedSchoolId) $query->where('school_id', $selectedSchoolId);

            $selectedSubjectId = $request->input('subject_id');
            if ($selectedSubjectId) $query->where('subject_id', $selectedSubjectId);

            $selectedCreatedBy = $request->input('created_by');
            if ($selectedCreatedBy) $query->where('created_by', $selectedCreatedBy);

            // Filter to only DRAFT status
            $query->where('status', 'draft');
        } else {
            $batchIds = $request->input('batch_ids', []);
            if (empty($batchIds)) {
                return response()->json(['success' => false, 'message' => 'No batches selected.'], 422);
            }
            $query->whereIn('id', $batchIds);
        }

        // Apply role scoping
        if (!$isAdmin) {
            if ($isMarkOfficer) {
                $query->where('created_by', $user->id);
            }
            if ($user->region_id) {
                $query->where('region_id', $user->region_id);
            }
        }

        $batches = $query->get();

        $eligible = [];
        $skipped = [];

        foreach ($batches as $batch) {
            $reason = $this->getBatchIneligibilityReason($batch, $user, $activeYear);
            
            $batchData = [
                'id' => $batch->id,
                'batch_code' => $batch->batch_code,
                'school_name' => $batch->school->name ?? 'N/A',
                'subject_name' => $batch->subject->name ?? 'N/A',
                'marks_count' => $batch->rawMarks()->count(),
            ];

            if ($reason) {
                $batchData['reason'] = $reason;
                $skipped[] = $batchData;
            } else {
                $eligible[] = $batchData;
            }
        }

        return response()->json([
            'success' => true,
            'total_selected' => count($batches),
            'eligible' => $eligible,
            'skipped' => $skipped,
        ]);
    }

    public function bulkSubmit(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $isTrulyAdmin = $user->isAdmin();
        $isReo = $this->isReoUser($user) || ($user->region_id && !$user->hasRole('officer') && !$this->isMarkOfficerUser($user) && !$isTrulyAdmin);
        $isMarkOfficer = $this->isMarkOfficerUser($user);
        $isAdmin = $isTrulyAdmin;

        if (!$isAdmin && !$isReo && !$isMarkOfficer) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $activeYear = \App\Models\ExamYear::where('is_active', true)->first();
        if (!$activeYear) {
            return response()->json(['success' => false, 'message' => 'Active exam year is not configured.'], 422);
        }

        $batchIds = $request->input('batch_ids', []);
        if (empty($batchIds)) {
            return response()->json(['success' => false, 'message' => 'No batches selected for submission.'], 422);
        }

        // Fetch batches
        $batches = \App\Models\MarkImportBatch::with(['school', 'subject'])
            ->whereIn('id', $batchIds)
            ->get();

        $submitted = [];
        $skipped = [];
        $failed = [];

        foreach ($batches as $batch) {
            // Scope protection checks (per batch revalidation server-side!)
            if (!$isAdmin) {
                if ($isMarkOfficer && (int) $batch->created_by !== (int) $user->id) {
                    $skipped[] = ['batch_code' => $batch->batch_code, 'reason' => 'Created by another officer'];
                    continue;
                }
                if ($user->region_id && (int) $batch->region_id !== (int) $user->region_id) {
                    $skipped[] = ['batch_code' => $batch->batch_code, 'reason' => 'Belongs to another region'];
                    continue;
                }
            }

            // Perform backend eligibility validation
            $ineligibilityReason = $this->getBatchIneligibilityReason($batch, $user, $activeYear);
            if ($ineligibilityReason) {
                $skipped[] = ['batch_code' => $batch->batch_code, 'reason' => $ineligibilityReason];
                continue;
            }

            // Run validation and outlier services just-in-time
            try {
                // Trigger full outlier detection for the batch
                $this->outlierService->detectForBatch($batch);

                // Run fresh validation for the batch
                $this->validationService->runValidation(['batch_id' => $batch->id], $user);

                // Check again after validation to be 100% sure no new errors arose
                $postValidationReason = $this->getBatchIneligibilityReason($batch, $user, $activeYear);
                if ($postValidationReason) {
                    $skipped[] = ['batch_code' => $batch->batch_code, 'reason' => $postValidationReason];
                    continue;
                }

                // Proceed with transition in a database transaction per batch (so other valid batches succeed even if one fails!)
                $result = DB::transaction(function () use ($batch, $user) {
                    return $this->service->transitionBatch($batch->id, 'submit', $user);
                });

                if ($result['success'] ?? false) {
                    // Log individual psle activity
                    $this->logPsleActivity([
                        'event_type' => 'subject_submitted',
                        'title' => 'Subject submitted',
                        'description' => ($batch->subject?->name ?? 'PSLE') . ' marks submitted for review (Bulk).',
                        'exam_year_id' => $batch->exam_year_id,
                        'region_id' => $batch->region_id,
                        'district_id' => $batch->district_id,
                        'school_id' => $batch->school_id,
                        'subject_id' => $batch->subject_id,
                        'user_id' => $user->id,
                        'affected_marks' => $batch->rawMarks()->count(),
                        'metadata' => ['batch_id' => $batch->id, 'batch_code' => $batch->batch_code, 'bulk_submit' => true],
                    ]);

                    $submitted[] = $batch->batch_code;
                } else {
                    $failed[] = ['batch_code' => $batch->batch_code, 'reason' => $result['message'] ?? 'Failed transition.'];
                }
            } catch (\Throwable $e) {
                \Log::error('Bulk submit failed for batch: ' . $batch->batch_code, [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $failed[] = ['batch_code' => $batch->batch_code, 'reason' => $e->getMessage()];
            }
        }

        // Add overall audit logging for this bulk chunk
        if (count($submitted) > 0 || count($skipped) > 0 || count($failed) > 0) {
            $context = [
                'batch_ids_requested' => $batchIds,
                'submitted_count' => count($submitted),
                'skipped_count' => count($skipped),
                'failed_count' => count($failed),
                'submitted_batch_codes' => $submitted,
                'skipped_batches' => $skipped,
                'failed_batches' => $failed,
            ];

            // 1. Record System Event Log
            \App\Models\SystemEventLog::record(
                \App\Models\SystemEventLog::CAT_SUBMISSION,
                'psle_bulk_submit_chunk',
                count($failed) === 0 ? \App\Models\SystemEventLog::STATUS_SUCCESS : \App\Models\SystemEventLog::STATUS_WARNING,
                sprintf(
                    'PSLE Bulk submission chunk processed. Submitted: %d, Skipped: %d, Failed: %d.',
                    count($submitted),
                    count($skipped),
                    count($failed)
                ),
                $context,
                null,
                $user->id
            );

            // 2. Record Governance Audit Log
            \App\Models\GovernanceAuditLog::log(
                'psle_bulk_submit_chunk',
                $user->id,
                $user->id,
                $context
            );
        }

        return response()->json([
            'success' => true,
            'submitted' => $submitted,
            'skipped' => $skipped,
            'failed' => $failed,
        ]);
    }

    private function getBatchIneligibilityReason(\App\Models\MarkImportBatch $batch, \App\Models\User $user, \App\Models\ExamYear $activeYear): ?string
    {
        // 1. Status is Draft
        if ($batch->status !== 'draft') {
            return 'Batch status is ' . ucfirst($batch->status) . ' (expected Draft).';
        }

        // 2. Belongs to the current exam year
        if ((int) $batch->exam_year_id !== (int) $activeYear->id) {
            return 'Batch belongs to exam year ' . ($batch->examYear->year_label ?? $batch->exam_year) . ' (expected current year ' . $activeYear->year_label . ').';
        }

        // 3. Has at least one entered mark
        $marksCount = $batch->rawMarks()->count();
        if ($marksCount === 0) {
            return 'Batch has no entered marks.';
        }

        // 4. Has no validation errors
        $unresolvedValidations = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('mark_entry_validations')) {
            $unresolvedValidations = \App\Models\MarkEntryValidation::whereHas('rawMark', fn($q) => $q->where('mark_import_batch_id', $batch->id))
                ->where('status', 'open')
                ->count();
        }
        if ($unresolvedValidations > 0) {
            return "Batch has {$unresolvedValidations} unresolved validation errors.";
        }

        // 5. Has no unresolved missing required marks
        $regCount = \App\Services\PsleCandidateRosterService::getDeduplicatedCount($batch->exam_year_id, $batch->school_id);
        $missingCount = max(0, $regCount - $marksCount);
        if ($missingCount > 0) {
            return "Batch has {$missingCount} unresolved missing marks (all registered candidates must have marks entered).";
        }

        // 6. Belongs to the user's allowed scope
        $isTrulyAdmin = $user->isAdmin();
        $isReo = $this->isReoUser($user) || ($user->region_id && !$user->hasRole('officer') && !$this->isMarkOfficerUser($user) && !$isTrulyAdmin);
        $isMarkOfficer = $this->isMarkOfficerUser($user);

        if (!$isTrulyAdmin) {
            if ($isMarkOfficer && (int) $batch->created_by !== (int) $user->id) {
                return 'Batch was created by another officer.';
            }
            if ($user->region_id && (int) $batch->region_id !== (int) $user->region_id) {
                return 'Batch belongs to another region.';
            }
        }

        // 7. Check for unresolved high/critical outliers
        $unresolvedOutliers = \App\Models\MarkEntryOutlier::where('batch_id', $batch->id)
            ->whereIn('severity', ['high', 'critical'])
            ->where('status', 'pending')
            ->count();
        if ($unresolvedOutliers > 0) {
            return "Batch has {$unresolvedOutliers} unresolved high/critical outliers.";
        }

        return null;
    }

    public function submitBatch(Request $request, int $batchId)
    {
        $batch = \App\Models\MarkImportBatch::findOrFail($batchId);
        $user = $request->user();
        $isTrulyAdmin = $user->isAdmin();
        $isReo = $this->isReoUser($user) || ($user->region_id && !$user->hasRole('officer') && !$this->isMarkOfficerUser($user) && !$isTrulyAdmin);
        $isMarkOfficer = $this->isMarkOfficerUser($user);
        $isAdmin = $isTrulyAdmin;

        // Security / Scoping Checks
        if (!$isAdmin && !$isReo && !$isMarkOfficer) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        if (!$isAdmin && $batch->created_by !== $user->id) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action: you can only submit your own batches.'], 403);
            }
            return redirect()->back()->with('error', 'Unauthorized action: you can only submit your own batches.');
        }

        if (!$isAdmin && $user->region_id && $batch->region_id !== $user->region_id) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Access denied: batch belongs to a different region.'], 403);
            }
            return redirect()->back()->with('error', 'Access denied: batch belongs to a different region.');
        }

        // Enforce zero-mark submission blocks
        $marksCount = $batch->rawMarks()->count();
        if ($marksCount === 0) {
            $msg = "Cannot submit batch. This batch has no entered marks and cannot be submitted.";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg
                ], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        // Trigger full outlier detection for the batch
        $this->outlierService->detectForBatch($batch);

        // Run fresh validation for the batch
        $this->validationService->runValidation(['batch_id' => $batchId], $user);
        
        // Check for unresolved high/critical severity outliers
        $unresolvedOutliers = \App\Models\MarkEntryOutlier::where('batch_id', $batchId)
            ->whereIn('severity', ['high', 'critical'])
            ->where('status', 'pending')
            ->count();

        // Check for unresolved high/critical validation errors
        $unresolvedValidations = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('mark_entry_validations')) {
            $unresolvedValidations = \App\Models\MarkEntryValidation::whereHas('rawMark', fn($q) => $q->where('mark_import_batch_id', $batchId))
                ->whereIn('severity', ['high', 'critical'])
                ->where('status', 'open')
                ->count();
        }
            
        if ($unresolvedOutliers > 0 || $unresolvedValidations > 0) {
            $msg = "Cannot submit batch. ";
            if ($unresolvedOutliers > 0) $msg .= "There are {$unresolvedOutliers} unresolved high-severity outliers. ";
            if ($unresolvedValidations > 0) $msg .= "There are {$unresolvedValidations} unresolved critical validation errors.";
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => trim($msg)
                ], 422);
            }
            return redirect()->back()->with('error', trim($msg));
        }

        try {
            $result = $this->service->transitionBatch($batchId, 'submit', $user);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        if (($result['success'] ?? true) !== false) {
            $batch->loadMissing(['school', 'subject']);
            $this->logPsleActivity([
                'event_type' => 'subject_submitted',
                'title' => 'Subject submitted',
                'description' => ($batch->subject?->name ?? 'PSLE') . ' marks submitted for review.',
                'exam_year_id' => $batch->exam_year_id,
                'region_id' => $batch->region_id,
                'district_id' => $batch->district_id,
                'school_id' => $batch->school_id,
                'subject_id' => $batch->subject_id,
                'user_id' => $user->id,
                'affected_marks' => $batch->rawMarks()->count(),
                'metadata' => ['batch_id' => $batch->id, 'batch_code' => $batch->batch_code],
            ]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($result);
        }

        if (!($result['success'] ?? true)) {
            return redirect()->back()->with('error', $result['message'] ?? 'Failed to submit batch.');
        }
        return redirect()->back()->with('success', 'Batch submitted successfully.');
    }

    public function approveBatch(Request $request, int $batchId)
    {
        $validated = $request->validate([
            'feedback' => ['nullable', 'string', 'max:1000'],
        ]);

        $batch = \App\Models\MarkImportBatch::findOrFail($batchId);
        $user = $request->user();
        $isTrulyAdmin = $user->isAdmin();
        $isReo = $this->isReoUser($user) || ($user->region_id && !$user->hasRole('officer') && !$this->isMarkOfficerUser($user) && !$isTrulyAdmin);
        $isAdmin = $isTrulyAdmin;

        if (!$isAdmin && !$isReo) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        if (!$isAdmin && $user->region_id && $batch->region_id !== $user->region_id) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Access denied: batch belongs to a different region.'], 403);
            }
            return redirect()->back()->with('error', 'Access denied: batch belongs to a different region.');
        }

        try {
            $result = $this->service->transitionBatch($batchId, 'approve', $user, $validated['feedback'] ?? null);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }
        if (($result['success'] ?? true) !== false) {
            $batch->loadMissing(['school', 'subject']);
            $this->logPsleActivity([
                'event_type' => 'moderation_action',
                'title' => 'Batch approved',
                'description' => ($batch->subject?->name ?? 'PSLE') . ' marks approved for ' . ($batch->school?->name ?? 'selected school') . '.',
                'exam_year_id' => $batch->exam_year_id,
                'region_id' => $batch->region_id,
                'district_id' => $batch->district_id,
                'school_id' => $batch->school_id,
                'subject_id' => $batch->subject_id,
                'user_id' => $user->id,
                'affected_marks' => $batch->rawMarks()->count(),
                'metadata' => ['batch_id' => $batch->id, 'batch_code' => $batch->batch_code],
            ]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($result);
        }

        if (!($result['success'] ?? true)) {
            return redirect()->back()->with('error', $result['message'] ?? 'Failed to approve batch.');
        }
        return redirect()->back()->with('success', 'Batch approved successfully.');
    }

    public function rejectBatch(Request $request, int $batchId)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $batch = \App\Models\MarkImportBatch::findOrFail($batchId);
        $user = $request->user();
        $isTrulyAdmin = $user->isAdmin();
        $isReo = $this->isReoUser($user) || ($user->region_id && !$user->hasRole('officer') && !$this->isMarkOfficerUser($user) && !$isTrulyAdmin);
        $isAdmin = $isTrulyAdmin;

        if (!$isAdmin && !$isReo) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        if (!$isAdmin && $user->region_id && $batch->region_id !== $user->region_id) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Access denied: batch belongs to a different region.'], 403);
            }
            return redirect()->back()->with('error', 'Access denied: batch belongs to a different region.');
        }

        try {
            $result = $this->service->transitionBatch($batchId, 'reject', $user, $validated['reason']);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }
        if (($result['success'] ?? true) !== false) {
            $batch->loadMissing(['school', 'subject']);
            $this->logPsleActivity([
                'event_type' => 'moderation_action',
                'title' => 'Batch rejected',
                'description' => ($batch->subject?->name ?? 'PSLE') . ' marks returned for correction.',
                'exam_year_id' => $batch->exam_year_id,
                'region_id' => $batch->region_id,
                'district_id' => $batch->district_id,
                'school_id' => $batch->school_id,
                'subject_id' => $batch->subject_id,
                'user_id' => $user->id,
                'affected_marks' => $batch->rawMarks()->count(),
                'metadata' => ['batch_id' => $batch->id, 'batch_code' => $batch->batch_code, 'reason' => $validated['reason']],
            ]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($result);
        }

        if (!($result['success'] ?? true)) {
            return redirect()->back()->with('error', $result['message'] ?? 'Failed to reject batch.');
        }
        return redirect()->back()->with('success', 'Batch rejected successfully.');
    }

    public function lockBatch(Request $request, int $batchId)
    {
        $batch = \App\Models\MarkImportBatch::findOrFail($batchId);
        $user = $request->user();
        
        // Only admin can lock
        if (!$user->isAdmin()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        // Final check for outliers before locking
        $unresolvedCount = \App\Models\MarkEntryOutlier::where('batch_id', $batchId)
            ->whereIn('severity', ['medium', 'high', 'critical'])
            ->where('status', 'pending')
            ->count();
            
        if ($unresolvedCount > 0) {
            $msg = "Cannot lock batch. There are {$unresolvedCount} unresolved outliers (Medium or higher severity).";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg
                ], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        try {
            $result = $this->service->transitionBatch($batchId, 'lock', $user);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        if (($result['success'] ?? true) !== false) {
            $batch->loadMissing(['school', 'subject']);
            $this->logPsleActivity([
                'event_type' => 'marks_locked',
                'title' => 'Marks locked',
                'description' => 'PSLE marks locked for ' . ($batch->school?->name ?? 'selected school') . '.',
                'exam_year_id' => $batch->exam_year_id,
                'region_id' => $batch->region_id,
                'district_id' => $batch->district_id,
                'school_id' => $batch->school_id,
                'subject_id' => $batch->subject_id,
                'user_id' => $user->id,
                'affected_marks' => $batch->rawMarks()->count(),
                'metadata' => ['batch_id' => $batch->id, 'batch_code' => $batch->batch_code],
            ]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($result);
        }

        if (!($result['success'] ?? true)) {
            return redirect()->back()->with('error', $result['message'] ?? 'Failed to lock batch.');
        }
        return redirect()->back()->with('success', 'Batch locked successfully.');
    }

    public function unlockBatch(Request $request, int $batchId)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $user = $request->user();
        $batch = \App\Models\MarkImportBatch::findOrFail($batchId);

        // Only admin can unlock
        if (!$user->isAdmin()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        try {
            $result = $this->service->transitionBatch($batchId, 'unlock', $user, $validated['reason']);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }
        if (($result['success'] ?? true) !== false) {
            $batch->loadMissing(['school', 'subject']);
            $this->logPsleActivity([
                'event_type' => 'marks_unlocked',
                'title' => 'Marks unlocked',
                'description' => 'PSLE marks unlocked for ' . ($batch->school?->name ?? 'selected school') . '.',
                'exam_year_id' => $batch->exam_year_id,
                'region_id' => $batch->region_id,
                'district_id' => $batch->district_id,
                'school_id' => $batch->school_id,
                'subject_id' => $batch->subject_id,
                'user_id' => $user->id,
                'affected_marks' => $batch->rawMarks()->count(),
                'metadata' => ['batch_id' => $batch->id, 'batch_code' => $batch->batch_code, 'reason' => $validated['reason']],
            ]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($result);
        }
        if (!($result['success'] ?? true)) {
            return redirect()->back()->with('error', $result['message'] ?? 'Failed to unlock batch.');
        }
        return redirect()->back()->with('success', 'Batch unlocked successfully.');
    }

    private function normalizeOptionalId($value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '' || $value === 'null' || $value === 'undefined' || $value === 'all' || $value === '0') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    private function validateReportScope(Request $request, ?int $targetRegionId = null): void
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        $isAdmin = $user->isAdmin();
        $isReo = $this->isReoUser($user) || ($user->region_id && !$user->hasRole('officer') && !$this->isMarkOfficerUser($user) && !$isAdmin);
        $isMarkOfficer = $this->isMarkOfficerUser($user);

        if (!$isAdmin && !$isReo && !$isMarkOfficer) {
            abort(403, 'Unauthorized role.');
        }

        if ($user->region_id && !$isAdmin) {
            if ($targetRegionId && (int) $targetRegionId !== (int) $user->region_id) {
                abort(403, 'Unauthorized access to outside region.');
            }
        }
    }

    public function exportRegionalProgressExcel(Request $request)
    {
        $user = $request->user();
        $this->validateReportScope($request, $request->query('region_id'));

        $examYearId = $request->query('exam_year_id');
        $regionId = $request->query('region_id');
        if ($user->region_id && !$user->isAdmin()) {
            $regionId = $user->region_id;
        }

        $data = $this->reportService->getRegionalProgress((int) $examYearId, $regionId ? (int) $regionId : null);
        $result = $this->reportService->exportExcel('regional_progress', $data, 'regional_progress_report.csv');

        $this->reportService->logAudit('REGIONAL_PROGRESS_EXCEL', $user, ['region_id' => $regionId]);

        return response()->download($result['file_path'], $result['filename'])->deleteFileAfterSend(true);
    }

    public function exportOfficerProductivityExcel(Request $request)
    {
        $user = $request->user();
        $this->validateReportScope($request, $request->query('region_id'));

        $examYearId = $request->query('exam_year_id');
        $regionId = $request->query('region_id');
        if ($user->region_id && !$user->isAdmin()) {
            $regionId = $user->region_id;
        }

        $data = $this->reportService->getOfficerProductivity((int) $examYearId, $regionId ? (int) $regionId : null);
        $result = $this->reportService->exportExcel('officer_productivity', $data, 'officer_productivity_report.csv');

        $this->reportService->logAudit('OFFICER_PRODUCTIVITY_EXCEL', $user, ['region_id' => $regionId]);

        return response()->download($result['file_path'], $result['filename'])->deleteFileAfterSend(true);
    }

    public function exportMissingMarksExcel(Request $request)
    {
        $user = $request->user();
        $this->validateReportScope($request, $request->query('region_id'));

        $examYearId = $request->query('exam_year_id');
        $regionId = $request->query('region_id');
        if ($user->region_id && !$user->isAdmin()) {
            $regionId = $user->region_id;
        }

        $data = $this->reportService->getMissingMarks((int) $examYearId, $regionId ? (int) $regionId : null);
        $result = $this->reportService->exportExcel('missing_marks', $data, 'missing_marks_report.csv');

        $this->reportService->logAudit('MISSING_MARKS_EXCEL', $user, ['region_id' => $regionId]);

        return response()->download($result['file_path'], $result['filename'])->deleteFileAfterSend(true);
    }

    public function exportOutliersExcel(Request $request)
    {
        $user = $request->user();
        $this->validateReportScope($request, $request->query('region_id'));

        $examYearId = $request->query('exam_year_id');
        $regionId = $request->query('region_id');
        if ($user->region_id && !$user->isAdmin()) {
            $regionId = $user->region_id;
        }

        $data = $this->reportService->getOutliers((int) $examYearId, $regionId ? (int) $regionId : null);
        $result = $this->reportService->exportExcel('outliers', $data, 'outliers_report.csv');

        $this->reportService->logAudit('OUTLIERS_EXCEL', $user, ['region_id' => $regionId]);

        return response()->download($result['file_path'], $result['filename'])->deleteFileAfterSend(true);
    }

    // ==================== PSLE PDF & ZIP REPORTS ====================

    public function scoresheetPdf(Request $request)
    {
        $user = $request->user();
        $this->validateReportScope($request, $request->query('region_id'));

        $examYearId = $request->query('exam_year_id');
        $schoolId = $request->query('school_id');
        $subjectId = $request->query('subject_id');
        $mode = $request->query('mode', 'approved');

        if (!$schoolId || !$subjectId) {
            abort(422, 'Please select School and Subject.');
        }

        $school = School::findOrFail($schoolId);
        $subject = Subject::findOrFail($subjectId);
        $examYear = ExamYear::findOrFail($examYearId);

        $this->validateReportScope($request, $school->region_id);

        try {
            $result = $this->scoresheetService->generateSingle(
                $examYear->year_label,
                (int) $schoolId,
                (int) $subjectId,
                $mode,
                'formal',
                $user->id
            );

            return response()->download($result['file_path'], $result['filename'])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('PSLE scoresheet PDF failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function scoresheetSchoolZip(Request $request)
    {
        $user = $request->user();
        $this->validateReportScope($request, $request->query('region_id'));

        $examYearId = $request->query('exam_year_id');
        $schoolId = $request->query('school_id');
        $mode = $request->query('mode', 'approved');

        if (!$schoolId) {
            abort(422, 'Please select School.');
        }

        $school = School::findOrFail($schoolId);
        $examYear = ExamYear::findOrFail($examYearId);

        $this->validateReportScope($request, $school->region_id);

        try {
            $result = $this->scoresheetService->generateSchoolZip(
                $examYear->year_label,
                (int) $schoolId,
                $mode,
                'formal',
                $user->id
            );

            return response()->download($result['file_path'], $result['filename'])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('PSLE school ZIP failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function scoresheetDistrictZip(Request $request)
    {
        $user = $request->user();
        $this->validateReportScope($request, $request->query('region_id'));

        $examYearId = $request->query('exam_year_id');
        $districtId = $request->query('district_id');
        $mode = $request->query('mode', 'approved');

        if (!$districtId) {
            abort(422, 'Please select District.');
        }

        $district = District::findOrFail($districtId);
        $examYear = ExamYear::findOrFail($examYearId);

        $this->validateReportScope($request, $district->region_id);

        try {
            $result = $this->scoresheetService->generateDistrictZip(
                $examYear->year_label,
                (int) $districtId,
                $mode,
                'formal',
                $user->id
            );

            return response()->download($result['file_path'], $result['filename'])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('PSLE district ZIP failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function scoresheetRegionZip(Request $request)
    {
        $user = $request->user();
        $this->validateReportScope($request, $request->query('region_id'));

        $examYearId = $request->query('exam_year_id');
        $regionId = $request->query('region_id');
        $mode = $request->query('mode', 'approved');

        if ($user->region_id && !$user->isAdmin()) {
            $regionId = $user->region_id;
        }

        if (!$regionId) {
            abort(422, 'Please select Region.');
        }

        $region = Region::findOrFail($regionId);
        $examYear = ExamYear::findOrFail($examYearId);

        $this->validateReportScope($request, $region->id);

        try {
            $result = $this->scoresheetService->generateRegionZip(
                $examYear->year_label,
                (int) $regionId,
                $mode,
                'formal',
                $user->id
            );

            return response()->download($result['file_path'], $result['filename'])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('PSLE region ZIP failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function enteredMarksPdf(Request $request)
    {
        $user = $request->user();
        $this->validateReportScope($request, $request->query('region_id'));

        $examYearId = $request->query('exam_year_id');
        $schoolId = $request->query('school_id');
        $subjectId = $request->query('subject_id');
        $mode = $request->query('mode', 'approved');

        if (!$schoolId || !$subjectId) {
            abort(422, 'Please select School and Subject.');
        }

        $school = School::findOrFail($schoolId);
        $subject = Subject::findOrFail($subjectId);
        $examYear = ExamYear::findOrFail($examYearId);

        $this->validateReportScope($request, $school->region_id);

        try {
            $result = $this->scoresheetService->generateEnteredSingle(
                $examYear->year_label,
                (int) $schoolId,
                (int) $subjectId,
                $mode,
                $user->id
            );

            return response()->download($result['file_path'], $result['filename'])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('PSLE entered PDF failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function enteredMarksSchoolZip(Request $request)
    {
        $user = $request->user();
        $this->validateReportScope($request, $request->query('region_id'));

        $examYearId = $request->query('exam_year_id');
        $schoolId = $request->query('school_id');
        $mode = $request->query('mode', 'approved');

        if (!$schoolId) {
            abort(422, 'Please select School.');
        }

        $school = School::findOrFail($schoolId);
        $examYear = ExamYear::findOrFail($examYearId);

        $this->validateReportScope($request, $school->region_id);

        try {
            $result = $this->scoresheetService->generateEnteredSchoolZip(
                $examYear->year_label,
                (int) $schoolId,
                $mode,
                $user->id
            );

            return response()->download($result['file_path'], $result['filename'])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('PSLE entered school ZIP failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function enteredMarksDistrictZip(Request $request)
    {
        $user = $request->user();
        $this->validateReportScope($request, $request->query('region_id'));

        $examYearId = $request->query('exam_year_id');
        $districtId = $request->query('district_id');
        $mode = $request->query('mode', 'approved');

        if (!$districtId) {
            abort(422, 'Please select District.');
        }

        $district = District::findOrFail($districtId);
        $examYear = ExamYear::findOrFail($examYearId);

        $this->validateReportScope($request, $district->region_id);

        try {
            $result = $this->scoresheetService->generateEnteredDistrictZip(
                $examYear->year_label,
                (int) $districtId,
                $mode,
                $user->id
            );

            return response()->download($result['file_path'], $result['filename'])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('PSLE entered district ZIP failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function enteredMarksRegionZip(Request $request)
    {
        $user = $request->user();
        $this->validateReportScope($request, $request->query('region_id'));

        $examYearId = $request->query('exam_year_id');
        $regionId = $request->query('region_id');
        $mode = $request->query('mode', 'approved');

        if ($user->region_id && !$user->isAdmin()) {
            $regionId = $user->region_id;
        }

        if (!$regionId) {
            abort(422, 'Please select Region.');
        }

        $region = Region::findOrFail($regionId);
        $examYear = ExamYear::findOrFail($examYearId);

        $this->validateReportScope($request, $region->id);

        try {
            $result = $this->scoresheetService->generateEnteredRegionZip(
                $examYear->year_label,
                (int) $regionId,
                $mode,
                $user->id
            );

            return response()->download($result['file_path'], $result['filename'])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('PSLE entered region ZIP failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function reportsSummary(Request $request)
    {
        $user = $request->user();
        
        $examYearId = $this->normalizeOptionalId($request->query('exam_year_id')) ?? \App\Models\ExamYear::where('is_active', true)->value('id');
        $regionId = $this->normalizeOptionalId($request->query('region_id'));
        $districtId = $this->normalizeOptionalId($request->query('district_id'));
        $schoolId = $this->normalizeOptionalId($request->query('school_id'));
        $subjectId = $this->normalizeOptionalId($request->query('subject_id'));

        if ($user->region_id && !$user->isAdmin()) {
            $regionId = $user->region_id;
        }

        $this->validateReportScope($request, $regionId);

        $examYear = ExamYear::findOrFail($examYearId);
        $psleExamType = \App\Models\ExamType::where('code', 'PSLE')->firstOrFail();

        // Total candidates count
        $candidatesQuery = \App\Services\PsleCandidateRosterService::rosterQuery((int) $examYearId);

        if ($regionId) {
            $candidatesQuery->whereHas('school', fn($q) => $q->where('region_id', $regionId));
        }
        if ($districtId) {
            $candidatesQuery->whereHas('school', fn($q) => $q->where('district_id', $districtId));
        }
        if ($schoolId) {
            $candidatesQuery->where('candidates.school_id', $schoolId);
        }
        $totalCandidates = $candidatesQuery->count();

        // Recorded marks count
        $marksQuery = \App\Models\RawMark::whereHas('candidate', function($cq) use ($examYearId, $psleExamType) {
                $cq->whereHas('examRegistrations', function($rq) use ($examYearId, $psleExamType) {
                    $rq->where('exam_type_id', $psleExamType->id);
                    if ($examYearId) $rq->where('exam_year_id', $examYearId);
                })
                ->whereHas('school', function($sq) {
                    $sq->whereIn('school_type', ['PRIMARY', 'BOTH'])
                      ->where('education_level', 'PRIMARY');
                });
            })
            ->whereHas('batch', function($q) use ($examYear, $regionId, $districtId, $schoolId, $subjectId) {
                $q->whereHas('examType', fn($sq) => $sq->where('code', 'PSLE'));
                $q->where('exam_year', $examYear->year_label);
                
                if ($regionId) $q->where('region_id', $regionId);
                if ($districtId) $q->where('district_id', $districtId);
                if ($schoolId) $q->where('school_id', $schoolId);
                if ($subjectId) $q->where('subject_id', $subjectId);
            })
            ->where(function($q) {
                $q->whereNotNull('paper_1_marks')
                  ->orWhereNotNull('paper_2_marks')
                  ->orWhereNotNull('paper_3_marks')
                  ->orWhereNotNull('practical_marks')
                  ->orWhereNotNull('project_marks')
                  ->orWhereNotNull('subject_status');
            });
        $totalMarks = $marksQuery->count();

        // Outliers count
        $outlierCount = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('mark_entry_outliers')) {
            $outlierCountQuery = \App\Models\MarkEntryOutlier::query();
            if ($examYearId) $outlierCountQuery->where('exam_year_id', $examYearId);
            if ($regionId) $outlierCountQuery->where('region_id', $regionId);
            if ($districtId) $outlierCountQuery->where('district_id', $districtId);
            if ($schoolId) $outlierCountQuery->where('school_id', $schoolId);
            if ($subjectId) $outlierCountQuery->where('subject_id', $subjectId);
            $outlierCount = $outlierCountQuery->count();
        }

        // Missing marks calculation
        $totalMissing = 0;
        $psleSubjects = \App\Models\Subject::where('exam_type_id', $psleExamType->id)->where('is_active', true)->get();
        foreach ($psleSubjects as $subj) {
            if ($subjectId && (int)$subjectId !== (int)$subj->id) continue;
            
            $subjMarksQuery = \App\Models\RawMark::where('subject_id', $subj->id)
                ->whereHas('candidate', function($cq) use ($examYearId, $psleExamType) {
                    $cq->whereHas('examRegistrations', function($rq) use ($examYearId, $psleExamType) {
                        $rq->where('exam_type_id', $psleExamType->id);
                        if ($examYearId) $rq->where('exam_year_id', $examYearId);
                    })
                    ->whereHas('school', function($sq) {
                        $sq->whereIn('school_type', ['PRIMARY', 'BOTH'])
                          ->where('education_level', 'PRIMARY');
                    });
                })
                ->whereHas('batch', function($q) use ($examYear, $regionId, $districtId, $schoolId) {
                    $q->whereHas('examType', fn($sq) => $sq->where('code', 'PSLE'));
                    $q->where('exam_year', $examYear->year_label);
                    if ($regionId) $q->where('region_id', $regionId);
                    if ($districtId) $q->where('district_id', $districtId);
                    if ($schoolId) $q->where('school_id', $schoolId);
                })
                ->where(function($q) {
                    $q->whereNotNull('paper_1_marks')
                      ->orWhereNotNull('paper_2_marks')
                      ->orWhereNotNull('paper_3_marks')
                      ->orWhereNotNull('practical_marks')
                      ->orWhereNotNull('project_marks')
                      ->orWhereNotNull('subject_status');
                });
            
            $entered = $subjMarksQuery->count();
            $missing = max(0, $totalCandidates - $entered);
            $totalMissing += $missing;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_candidates' => $totalCandidates,
                'total_marks' => $totalMarks,
                'total_outliers' => $outlierCount,
                'total_missing' => $totalMissing,
            ]
        ]);
    }

    public function reportsExport(Request $request)
    {
        $user = $request->user();
        $this->validateReportScope($request, $request->query('region_id'));

        $examYearId = $request->query('exam_year_id');
        $regionId = $request->query('region_id');
        $districtId = $request->query('district_id');
        $schoolId = $request->query('school_id');
        $subjectId = $request->query('subject_id');

        if ($user->region_id && !$user->isAdmin()) {
            $regionId = $user->region_id;
        }

        $examYear = \App\Models\ExamYear::findOrFail($examYearId);
        $psleExamType = \App\Models\ExamType::where('code', 'PSLE')->firstOrFail();

        // Query raw marks
        $query = \App\Models\RawMark::query()
            ->select([
                'raw_marks.id',
                'raw_marks.candidate_id',
                'raw_marks.candidate_index_number',
                'raw_marks.paper_1_marks',
                'raw_marks.subject_status',
                'raw_marks.created_at',
                'raw_marks.updated_at',
                'raw_marks.subject_id',
                'raw_marks.school_id',
                'raw_marks.entered_by',
                'mark_import_batches.batch_code',
                'mark_import_batches.status as batch_status',
                'candidates.full_name as candidate_name',
                'candidates.gender as sex',
                'candidates.prem_no',
                'schools.code as school_code',
                'schools.name as school_name',
                'subjects.code as subject_code',
                'subjects.name as subject_name',
                'districts.name as district_name',
                'regions.name as region_name',
                'users.name as entered_by_name'
            ])
            ->join('mark_import_batches', 'raw_marks.mark_import_batch_id', '=', 'mark_import_batches.id')
            ->join('candidates', 'raw_marks.candidate_id', '=', 'candidates.id')
            ->join('schools', 'raw_marks.school_id', '=', 'schools.id')
            ->join('subjects', 'raw_marks.subject_id', '=', 'subjects.id')
            ->join('districts', 'schools.district_id', '=', 'districts.id')
            ->join('regions', 'schools.region_id', '=', 'regions.id')
            ->leftJoin('users', 'raw_marks.entered_by', '=', 'users.id')
            ->where('mark_import_batches.exam_type_id', $psleExamType->id)
            ->where('mark_import_batches.exam_year', (string) $examYear->year_label)
            ->where('raw_marks.has_errors', false);

        if ($regionId) {
            $query->where('schools.region_id', $regionId);
        }
        if ($districtId) {
            $query->where('schools.district_id', $districtId);
        }
        if ($schoolId) {
            $query->where('raw_marks.school_id', $schoolId);
        }
        if ($subjectId) {
            $query->where('raw_marks.subject_id', $subjectId);
        }

        // Region / District / School names for filename
        $regionName = $regionId ? \App\Models\Region::where('id', $regionId)->value('name') : 'all_regions';
        $regionName = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $regionName));
        
        $filename = "psle_raw_marks_{$examYear->year_label}_{$regionName}_" . now()->format('Ymd_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() use ($query, $examYear) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, [
                'exam_year', 'region', 'district', 'school_code', 'school_name', 
                'subject_code', 'subject_name', 'candidate_number', 'prem_no', 
                'candidate_name', 'sex', 'mark', 'status', 'entered_by', 'entered_at', 
                'batch_code', 'batch_status'
            ]);

            // Chunk the query for large database sets
            $query->chunk(1000, function ($rows) use ($file, $examYear) {
                foreach ($rows as $row) {
                    $status = $row->subject_status ?: 'Entered';
                    fputcsv($file, [
                        $examYear->year_label,
                        $row->region_name,
                        $row->district_name,
                        $row->school_code,
                        $row->school_name,
                        $row->subject_code,
                        $row->subject_name,
                        $row->candidate_index_number,
                        $row->prem_no,
                        $row->candidate_name,
                        $row->sex,
                        $row->paper_1_marks,
                        $status,
                        $row->entered_by_name ?? 'System',
                        $row->created_at ? $row->created_at->toDateTimeString() : '',
                        $row->batch_code,
                        $row->batch_status
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bulkImportTemplate(Request $request)
    {
        $schoolId = $request->query('school_id');
        $subjectId = $request->query('subject_id');
        $examYearId = $request->query('exam_year_id');

        if (!$schoolId || !$subjectId || !$examYearId) {
            return response()->json(['success' => false, 'message' => 'Please select Exam Year, School, and Subject.', 'errors' => []], 422);
        }

        $school = School::findOrFail($schoolId);
        $subject = Subject::findOrFail($subjectId);
        $examYear = ExamYear::findOrFail($examYearId);

        if ($failure = $this->authorizePsleBulkScope($request, $examYear, $school, $subject)) {
            return $failure;
        }

        $candidates = $this->psleBulkCandidatesQuery($examYear, $school, $subject)
            ->orderBy('candidate_id')
            ->get();
        
        $candidates = \App\Services\PsleCandidateRosterService::deduplicate($candidates, $school->code);

        if ($candidates->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No candidates were found for this school and subject. Please confirm that candidates are registered for the selected PSLE subject.',
                'errors' => [],
            ], 422);
        }

        $safeSchoolName = preg_replace('/[^A-Za-z0-9]+/', '_', trim((string) $school->name));
        $safeSchoolName = trim((string) $safeSchoolName, '_') ?: 'SCHOOL';
        $filename = "PSLE_{$examYear->year_label}_{$school->code}_{$safeSchoolName}_{$subject->code}_MARK_TEMPLATE.csv";
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['CNO', 'PReM', 'Name', 'Sex', 'Mark'];

        $callback = function() use($candidates, $columns) {
            $file = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns);

            foreach ($candidates as $candidate) {
                fputcsv($file, [
                    $candidate->candidate_id,
                    $candidate->prem_no,
                    $candidate->full_name,
                    $candidate->gender,
                    ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bulkImportPreview(Request $request)
    {
        $user = $request->user();
        if ($user && $this->isAdminOrReo($user)) {
            return response()->json(['success' => false, 'message' => 'Admin and REO accounts can view mark sheets only. Bulk import is not permitted.', 'errors' => []], 403);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt',
            'school_id' => 'required|integer|exists:schools,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'exam_year_id' => 'required|integer|exists:exam_years,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Please upload a valid CSV file and select Exam Year, School, and Subject.', 'errors' => $validator->errors()], 422);
        }

        $examYear = ExamYear::findOrFail($request->exam_year_id);
        $school = School::findOrFail($request->school_id);
        $subject = Subject::findOrFail($request->subject_id);

        if ($failure = $this->authorizePsleBulkScope($request, $examYear, $school, $subject)) {
            return $failure;
        }
        
        $result = $this->service->validateSingleCsv(
            $request->file('file'),
            $examYear->year_label,
            $request->school_id,
            $request->subject_id
        );

        $this->logPsleActivity([
            'event_type' => 'bulk_import_previewed',
            'title' => 'Bulk import preview completed',
            'description' => sprintf(
                'Upload preview completed. %s valid rows and %s invalid rows found for %s at %s.',
                number_format((int) data_get($result, 'totals.valid_rows', 0)),
                number_format((int) data_get($result, 'totals.invalid_rows', 0)),
                $subject->name,
                $school->name
            ),
            'exam_year_id' => $examYear->id,
            'region_id' => $school->region_id,
            'district_id' => $school->district_id,
            'school_id' => $school->id,
            'subject_id' => $subject->id,
            'user_id' => $request->user()?->id,
            'affected_candidates' => (int) data_get($result, 'totals.total_rows', 0),
            'metadata' => [
                'file_name' => $request->file('file')?->getClientOriginalName(),
                'valid_rows' => data_get($result, 'totals.valid_rows', 0),
                'invalid_rows' => data_get($result, 'totals.invalid_rows', 0),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);

        return response()->json($result);
    }

    public function bulkImportConfirm(Request $request)
    {
        $user = $request->user();
        if ($user && $this->isAdminOrReo($user)) {
            return response()->json(['success' => false, 'message' => 'Admin and REO accounts can view mark sheets only. Bulk import is not permitted.', 'errors' => []], 403);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt',
            'school_id' => 'required|integer|exists:schools,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'exam_year_id' => 'required|integer|exists:exam_years,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Please upload a valid CSV file and select Exam Year, School, and Subject.', 'errors' => $validator->errors()], 422);
        }

        $examYear = ExamYear::findOrFail($request->exam_year_id);
        $school = School::findOrFail($request->school_id);
        $subject = Subject::findOrFail($request->subject_id);

        if ($failure = $this->authorizePsleBulkScope($request, $examYear, $school, $subject)) {
            return $failure;
        }
        
        $validation = $this->service->validateSingleCsv(
            $request->file('file'),
            $examYear->year_label,
            $request->school_id,
            $request->subject_id
        );

        $validRows = $validation['validated_rows'] ?? [];
        if (empty($validRows)) {
            return response()->json($validation, 422);
        }

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $rowErrors = [];
        $changes = [];

        DB::beginTransaction();
        try {
            foreach ($validRows as $row) {
                $existing = \App\Models\RawMark::query()
                    ->where('exam_year_id', $examYear->id)
                    ->where('school_id', $school->id)
                    ->where('candidate_id', $row['candidate_id'])
                    ->where('subject_id', $subject->id)
                    ->first();
                $oldMark = $existing?->paper_1_marks;

                $saveRequest = Request::create('/api/mark-entry/psle/marks/save', 'POST', [
                    'candidate_id' => $row['candidate_id'],
                    'school_id' => $school->id,
                    'subject_id' => $subject->id,
                    'exam_year_id' => $examYear->id,
                    'score' => $row['paper_1_marks'],
                ]);
                $saveRequest->setUserResolver(fn () => $request->user());
                $saveRequest->headers->set('Accept', 'application/json');
                $saveRequest->headers->set('User-Agent', (string) $request->userAgent());
                $saveRequest->server->set('REMOTE_ADDR', (string) $request->ip());

                $response = $this->saveMark($saveRequest);
                $payload = json_decode($response->getContent(), true) ?: [];

                if (($payload['success'] ?? false) !== true) {
                    $skipped++;
                    $rowErrors[] = [
                        'row' => $row['row_number'],
                        'candidate_id' => $row['candidate_id'],
                        'message' => $payload['message'] ?? 'The mark could not be imported.',
                    ];
                    continue;
                }

                if ($existing) {
                    $updated++;
                    if ((string) $oldMark !== (string) $row['paper_1_marks']) {
                        $changes[] = [
                            'candidate_id' => $row['candidate_id'],
                            'old_mark' => $oldMark,
                            'new_mark' => $row['paper_1_marks'],
                        ];
                    }
                } else {
                    $inserted++;
                }
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('PSLE bulk import failed.', [
                'user_id' => $request->user()?->id,
                'exam_year_id' => $examYear->id,
                'school_id' => $school->id,
                'subject_id' => $subject->id,
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk import could not be completed. Please retry.',
                'errors' => [],
            ], 500);
        }

        $summary = [
            'total_rows' => (int) data_get($validation, 'totals.total_rows', 0),
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'invalid' => (int) data_get($validation, 'totals.invalid_rows', 0),
            'total_processed' => $inserted + $updated,
        ];

        $this->logPsleActivity([
            'event_type' => 'marks_imported',
            'title' => 'Bulk marks imported',
            'description' => sprintf(
                'Bulk import completed successfully. %s marks inserted and %s marks updated.',
                number_format($inserted),
                number_format($updated)
            ),
            'exam_year_id' => $examYear->id,
            'region_id' => $school->region_id,
            'district_id' => $school->district_id,
            'school_id' => $school->id,
            'subject_id' => $subject->id,
            'user_id' => $request->user()?->id,
            'affected_candidates' => $inserted + $updated,
            'affected_marks' => $inserted + $updated,
            'metadata' => [
                'source' => 'psle_bulk_import_csv',
                'file_name' => $request->file('file')?->getClientOriginalName(),
                'summary' => $summary,
                'updated_marks' => array_slice($changes, 0, 200),
                'row_errors' => $rowErrors,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => sprintf('Bulk import completed successfully. %s marks inserted and %s marks updated.', number_format($inserted), number_format($updated)),
            'summary' => $summary,
            'errors' => $rowErrors,
        ]);
    }

    public function bulkImportHistory(Request $request)
    {
        $limit = $request->query('limit', 20);
        $data = collect($this->service->recentBatches($limit));

        if ($request->filled('school_id')) {
            $school = School::find($request->query('school_id'));
            $data = $data->filter(fn($batch) => (int) ($batch['school_id'] ?? 0) === (int) $request->query('school_id')
                || ($school && ($batch['school_code'] ?? null) === $school->code));
        }

        if ($request->filled('subject_id')) {
            $subject = Subject::find($request->query('subject_id'));
            $data = $data->filter(fn($batch) => (int) ($batch['subject_id'] ?? 0) === (int) $request->query('subject_id')
                || ($subject && ($batch['subject_code'] ?? null) === $subject->code));
        }

        return response()->json([
            'success' => true,
            'data' => $data->values()->all(),
        ]);
    }

    public function bulkImportDownloadErrors(Request $request, int $batchId)
    {
        // This is a placeholder as the specific logic for error CSV might vary.
        // Usually, we'd fetch the RawMark records with errors for this batch.
        return response()->json(['message' => 'Error download not yet implemented for this batch.'], 501);
    }

    public function bulkFilterRegions(Request $request)
    {
        $examYear = ExamYear::find($request->query('exam_year_id')) ?: ExamYear::where('is_active', true)->first();
        $psleExamTypeId = (int) \App\Models\ExamType::where('code', 'PSLE')->value('id');
        $user = $request->user();

        $query = \App\Models\Region::query()
            ->select('regions.id', 'regions.name')
            ->join('schools', 'schools.region_id', '=', 'regions.id')
            ->join('candidates', 'candidates.school_id', '=', 'schools.id')
            ->join('candidate_exam_registrations', 'candidate_exam_registrations.candidate_id', '=', 'candidates.id')
            ->whereIn('schools.school_type', ['PRIMARY', 'BOTH'])
            ->where('schools.education_level', 'PRIMARY')
            ->where('candidate_exam_registrations.exam_type_id', $psleExamTypeId)
            ->when($examYear, fn($yearQuery) => $yearQuery->where('candidate_exam_registrations.exam_year_id', $examYear->id))
            ->distinct()
            ->orderBy('regions.name');

        if ($user && ! $user->isAdmin()) {
            if ($user->region_id) {
                $query->where('regions.id', $user->region_id);
            } else {
                $assignedRegionIds = MarkEntryAssignment::where([
                    'assigned_to' => $user->id,
                    'exam_year_id' => $examYear?->id,
                    'exam_type_id' => $psleExamTypeId,
                    'status' => 'active',
                ])->pluck('region_id')->unique()->toArray();
                $query->whereIn('regions.id', $assignedRegionIds);
            }
        }

        return response()->json(['success' => true, 'data' => $query->get(), 'message' => 'Regions loaded.']);
    }

    public function bulkFilterDistricts(Request $request)
    {
        $examYear = ExamYear::find($request->query('exam_year_id')) ?: ExamYear::where('is_active', true)->first();
        $regionId = $request->query('region_id');
        $psleExamTypeId = (int) \App\Models\ExamType::where('code', 'PSLE')->value('id');
        $user = $request->user();

        if ($user && ! $user->isAdmin()) {
            if ($user->region_id) {
                $regionId = $user->region_id;
            } else {
                $assignedDistrictIds = MarkEntryAssignment::where([
                    'assigned_to' => $user->id,
                    'exam_year_id' => $examYear?->id,
                    'exam_type_id' => $psleExamTypeId,
                    'status' => 'active',
                ])->pluck('district_id')->unique()->toArray();
            }
        }

        $query = \App\Models\District::query()
            ->select('districts.id', 'districts.name', 'districts.region_id')
            ->join('schools', 'schools.district_id', '=', 'districts.id')
            ->join('candidates', 'candidates.school_id', '=', 'schools.id')
            ->join('candidate_exam_registrations', 'candidate_exam_registrations.candidate_id', '=', 'candidates.id')
            ->when(isset($regionId) && $regionId, fn($districtQuery) => $districtQuery->where('districts.region_id', $regionId))
            ->when(isset($assignedDistrictIds) && !empty($assignedDistrictIds), fn($districtQuery) => $districtQuery->whereIn('districts.id', $assignedDistrictIds))
            ->whereIn('schools.school_type', ['PRIMARY', 'BOTH'])
            ->where('schools.education_level', 'PRIMARY')
            ->where('candidate_exam_registrations.exam_type_id', $psleExamTypeId)
            ->when($examYear, fn($yearQuery) => $yearQuery->where('candidate_exam_registrations.exam_year_id', $examYear->id))
            ->distinct()
            ->orderBy('districts.name');

        return response()->json(['success' => true, 'data' => $query->get(), 'message' => 'Districts loaded.']);
    }

    public function bulkFilterSchools(Request $request)
    {
        $examYear = ExamYear::find($request->query('exam_year_id')) ?: ExamYear::where('is_active', true)->first();
        $regionId = $request->query('region_id');
        $districtId = $request->query('district_id');
        $search = trim((string) $request->query('q', $request->query('term', '')));
        $selectedSchoolId = $request->query('id');
        $psleExamTypeId = (int) \App\Models\ExamType::where('code', 'PSLE')->value('id');
        $user = $request->user();

        if ($user && ! $user->isAdmin()) {
            if ($user->region_id) {
                $regionId = $user->region_id;
            } else {
                $assignedSchoolIds = MarkEntryAssignment::where([
                    'assigned_to' => $user->id,
                    'exam_year_id' => $examYear?->id,
                    'exam_type_id' => $psleExamTypeId,
                    'status' => 'active',
                ])->pluck('school_id')->unique()->toArray();
            }
        }

        if ($search === '' && ! $selectedSchoolId) {
            return response()->json(['success' => true, 'data' => [], 'message' => 'Type at least two characters to search schools.']);
        }

        $query = School::query()
            ->select('schools.id', 'schools.name', 'schools.code', 'schools.region_id', 'schools.district_id')
            ->join('candidates', 'candidates.school_id', '=', 'schools.id')
            ->join('candidate_exam_registrations', 'candidate_exam_registrations.candidate_id', '=', 'candidates.id')
            ->whereIn('schools.school_type', ['PRIMARY', 'BOTH'])
            ->where('schools.education_level', 'PRIMARY')
            ->when($regionId, fn($schoolQuery) => $schoolQuery->where('schools.region_id', $regionId))
            ->when($districtId, fn($schoolQuery) => $schoolQuery->where('schools.district_id', $districtId))
            ->when(isset($assignedSchoolIds) && !empty($assignedSchoolIds), fn($schoolQuery) => $schoolQuery->whereIn('schools.id', $assignedSchoolIds))
            ->where('candidate_exam_registrations.exam_type_id', $psleExamTypeId)
            ->when($examYear, fn($yearQuery) => $yearQuery->where('candidate_exam_registrations.exam_year_id', $examYear->id))
            ->when($selectedSchoolId, fn($schoolQuery) => $schoolQuery->where('schools.id', $selectedSchoolId))
            ->when($search !== '', function ($schoolQuery) use ($search) {
                $schoolQuery->where(function ($inner) use ($search) {
                    $inner->where('schools.name', 'like', "%{$search}%")
                        ->orWhere('schools.code', 'like', "%{$search}%");
                    if (Schema::hasColumn('schools', 'centre_number')) {
                        $inner->orWhere('schools.centre_number', 'like', "%{$search}%");
                    }
                    if (Schema::hasColumn('schools', 'school_code')) {
                        $inner->orWhere('schools.school_code', 'like', "%{$search}%");
                    }
                });
            })
            ->distinct()
            ->orderBy('schools.code')
            ->limit(100);

        $data = $query->get()
            ->map(fn($school) => [
                'id' => $school->id,
                'name' => $school->name,
                'code' => $school->code,
                'text' => trim(($school->code ? $school->code . ' - ' : '') . $school->name),
                'region_id' => $school->region_id,
                'district_id' => $school->district_id,
            ]);

        return response()->json(['success' => true, 'data' => $data, 'message' => 'Schools loaded.']);
    }

    public function bulkFilterSubjects(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_year_id' => 'required|integer|exists:exam_years,id',
            'school_id' => 'required|integer|exists:schools,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Please select Exam Year and Primary School.', 'errors' => $validator->errors()], 422);
        }

        $examYear = ExamYear::findOrFail($request->query('exam_year_id'));
        $school = School::findOrFail($request->query('school_id'));
        $user = $request->user();

        $psleExamTypeId = (int) \App\Models\ExamType::where('code', 'PSLE')->value('id');

        if ($user && ! $user->isAdmin()) {
            if ($user->region_id) {
                if ((int) $school->region_id !== (int) $user->region_id) {
                    return response()->json(['success' => false, 'message' => 'You are not authorized to import marks for this school.', 'errors' => []], 403);
                }
            } else {
                $assignedSchoolIds = MarkEntryAssignment::where([
                    'assigned_to' => $user->id,
                    'exam_year_id' => $examYear->id,
                    'exam_type_id' => $psleExamTypeId,
                    'status' => 'active',
                ])->pluck('school_id')->unique()->toArray();

                if (!in_array((int) $school->id, array_map('intval', $assignedSchoolIds))) {
                    return response()->json(['success' => false, 'message' => 'You are not authorized to import marks for this school.', 'errors' => []], 403);
                }
            }
        }

        if ($user && ! $user->isAdmin() && !$user->region_id) {
            $assignedSubjectIds = MarkEntryAssignment::where([
                'assigned_to' => $user->id,
                'school_id' => $school->id,
                'exam_year_id' => $examYear->id,
                'exam_type_id' => $psleExamTypeId,
                'status' => 'active',
            ])->pluck('subject_id')->unique()->toArray();
            
            $subjects = Subject::query()
                ->whereIn('id', $assignedSubjectIds)
                ->where('exam_type_id', $psleExamTypeId)
                ->where('is_active', true)
                ->orderBy('code')
                ->get();
        } else {
            $subjects = Subject::query()
                ->select('subjects.id', 'subjects.code', 'subjects.name')
                ->join('candidate_subject_selections', 'candidate_subject_selections.subject_id', '=', 'subjects.id')
                ->join('candidates', 'candidates.id', '=', 'candidate_subject_selections.candidate_id')
                ->where('subjects.exam_type_id', $psleExamTypeId)
                ->where('subjects.is_active', true)
                ->where('candidate_subject_selections.exam_type_id', $psleExamTypeId)
                ->where('candidate_subject_selections.exam_year_id', $examYear->id)
                ->where('candidates.school_id', $school->id)
                ->distinct()
                ->orderBy('subjects.code')
                ->get();

            if ($subjects->isEmpty()) {
                $hasRegisteredCandidates = Candidate::query()
                    ->where('school_id', $school->id)
                    ->whereHas('examRegistrations', fn($registrationQuery) => $registrationQuery
                        ->where('exam_type_id', $psleExamTypeId)
                        ->where('exam_year_id', $examYear->id))
                    ->exists();

                if ($hasRegisteredCandidates) {
                    $subjects = Subject::query()
                        ->where('exam_type_id', $psleExamTypeId)
                        ->where('is_active', true)
                        ->orderBy('code')
                        ->get(['id', 'code', 'name']);
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $subjects->map(fn($subject) => [
                'id' => $subject->id,
                'code' => $subject->code,
                'name' => $subject->name,
                'text' => "{$subject->code} - {$subject->name}",
            ])->values(),
            'message' => 'Subjects loaded.',
        ]);
    }

    private function authorizePsleBulkScope(Request $request, ExamYear $examYear, School $school, Subject $subject)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Your session has expired. Please refresh and login again.', 'errors' => []], 401);
        }

        if (! $examYear->is_active) {
            return response()->json(['success' => false, 'message' => 'The selected exam year is not active for mark entry.', 'errors' => []], 422);
        }

        $psleExamTypeId = (int) \App\Models\ExamType::where('code', 'PSLE')->value('id');

        $isTrulyAdmin = $user->isAdmin();
        $isMarkOfficer = $this->isMarkOfficerUser($user);

        if ($isMarkOfficer && !$isTrulyAdmin) {
            $hasRegionalAssignment = ($user->region_id && (int) $school->region_id === (int) $user->region_id);
            if (!$hasRegionalAssignment) {
                $hasAssignment = \App\Models\MarkEntryAssignment::where([
                    'assigned_to' => $user->id,
                    'school_id' => $school->id,
                    'subject_id' => $subject->id,
                    'exam_year_id' => $examYear->id,
                    'exam_type_id' => $psleExamTypeId,
                    'status' => 'active',
                ])->exists();

                if (!$hasAssignment) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have an active assignment to enter marks for this school and subject.',
                        'errors' => [],
                    ], 403);
                }
            }
        } elseif (!$isTrulyAdmin) {
            if ($user->region_id && (int) $school->region_id !== (int) $user->region_id) {
                return response()->json(['success' => false, 'message' => 'You are not authorized to import marks for this school.', 'errors' => []], 403);
            }
        }

        if ((int) $subject->exam_type_id !== $psleExamTypeId || ! $subject->is_active) {
            return response()->json(['success' => false, 'message' => 'Please select a valid active PSLE subject.', 'errors' => []], 422);
        }

        $existingNonDraftBatch = MarkImportBatch::where([
            'school_id' => $school->id,
            'subject_id' => $subject->id,
            'exam_year_id' => $examYear->id,
        ])->where('status', '!=', 'draft')->first();

        if ($existingNonDraftBatch) {
            return response()->json([
                'success' => false,
                'message' => 'This subject has already been submitted and locked. Bulk import is no longer allowed unless reopened by an administrator.',
                'errors' => [],
            ], 403);
        }

        $lockedExistingMark = \App\Models\RawMark::query()
            ->where('exam_year_id', $examYear->id)
            ->where('school_id', $school->id)
            ->where('subject_id', $subject->id)
            ->where(function ($query) {
                $query->where('is_locked', true)
                    ->orWhereHas('batch', fn($batchQuery) => $batchQuery->where('status', '!=', 'draft'));
            })
            ->exists();

        if ($lockedExistingMark) {
            return response()->json([
                'success' => false,
                'message' => 'This subject has already been submitted and locked. Bulk import is no longer allowed unless reopened by an administrator.',
                'errors' => [],
            ], 403);
        }

        return null;
    }

    private function psleBulkCandidatesQuery(ExamYear $examYear, School $school, Subject $subject): Builder
    {
        $psleExamTypeId = (int) \App\Models\ExamType::where('code', 'PSLE')->value('id');

        return \App\Services\PsleCandidateRosterService::rosterQuery($examYear->id, $school->id)
            ->select('candidates.*')
            ->leftJoin('candidate_exam_registrations', function ($join) use ($psleExamTypeId, $examYear) {
                $join->on('candidate_exam_registrations.candidate_id', '=', 'candidates.id')
                    ->where('candidate_exam_registrations.exam_type_id', '=', $psleExamTypeId)
                    ->where('candidate_exam_registrations.exam_year_id', '=', $examYear->id);
            })
            ->distinct()
            ->orderBy('candidates.candidate_id', 'asc')
            ->orderBy('candidates.full_name', 'asc');
    }

    public function runValidation(Request $request)
    {
        $user = $request->user();
        $filters = [
            'exam_year_id' => $request->query('exam_year_id'),
            'region_id' => $request->query('region_id'),
            'district_id' => $request->query('district_id'),
            'school_id' => $request->query('school_id'),
            'subject_id' => $request->query('subject_id'),
        ];

        // Security/Role Scoping
        if (!$user->isAdmin()) {
            $filters['region_id'] = $user->region_id;
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('mark_entry_validations')) {
            $this->logPsleActivity([
                'event_type' => 'validation_failed',
                'title' => 'Validation failed',
                'description' => 'Validation could not run because the validation table is not initialized.',
                'exam_year_id' => $filters['exam_year_id'] ?: null,
                'region_id' => $filters['region_id'] ?: null,
                'district_id' => $filters['district_id'] ?: null,
                'school_id' => $filters['school_id'] ?: null,
                'subject_id' => $filters['subject_id'] ?: null,
                'user_id' => $user?->id,
                'metadata' => ['reason' => 'missing_mark_entry_validations_table'],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'The validation system is not yet initialized. Please run database migrations (php artisan migrate).'
            ], 500);
        }

        $count = $this->validationService->runValidation($filters, $user);

        $this->logPsleActivity([
            'event_type' => 'validation_completed',
            'title' => 'Validation completed',
            'description' => "Validation completed. Processed {$count} records.",
            'exam_year_id' => $filters['exam_year_id'] ?: null,
            'region_id' => $filters['region_id'] ?: null,
            'district_id' => $filters['district_id'] ?: null,
            'school_id' => $filters['school_id'] ?: null,
            'subject_id' => $filters['subject_id'] ?: null,
            'user_id' => $user?->id,
            'affected_marks' => (int) $count,
            'metadata' => ['filters' => $filters],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Validation completed. Processed {$count} records.",
            'processed_count' => $count
        ]);
    }

    public function correctValidationError(Request $request)
    {
        $request->validate([
            'validation_id' => 'required|exists:mark_entry_validations,id',
            'new_mark' => 'nullable|numeric|min:0|max:50',
            'subject_status' => 'nullable|string|in:ABS,INC,P',
            'comment' => 'nullable|string'
        ]);

        $user = $request->user();
        $validation = \App\Models\MarkEntryValidation::with('rawMark')->findOrFail($request->validation_id);
        $rawMark = $validation->rawMark;

        // Security check
        if (!$user->isAdmin() && $rawMark->batch->region_id !== $user->region_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this region\'s data.'], 403);
        }

        if ($rawMark->is_locked) {
            return response()->json(['success' => false, 'message' => 'Cannot correct a locked record.'], 422);
        }

        DB::transaction(function () use ($rawMark, $validation, $request, $user) {
            $oldMark = $rawMark->paper_1_marks;
            $oldStatus = $rawMark->subject_status;

            // Update RawMark
            $rawMark->update([
                'paper_1_marks' => $request->subject_status === 'ABS' ? null : $request->new_mark,
                'subject_status' => $request->subject_status,
                'status_reason' => $request->comment,
            ]);

            // Mark as resolved
            $validation->update([
                'status' => 'corrected',
                'resolved_by' => $user->id,
                'resolved_at' => now(),
                'resolution_comment' => $request->comment ?: "Corrected mark from {$oldMark} to " . ($request->new_mark ?? 'NULL')
            ]);

            // Log change
            \App\Models\MarkEntryChange::create([
                'raw_mark_id' => $rawMark->id,
                'changed_by' => $user->id,
                'field_name' => 'paper_1_marks',
                'old_value' => $oldMark,
                'new_value' => $request->new_mark,
                'change_type' => 'correction',
                'reason' => $request->comment ?? 'Validation error correction',
                'changed_at' => now(),
                'ip_address' => $request->ip()
            ]);

            // Re-validate this specific mark to see if other errors remain or if it's clean now
            $this->validationService->validateRawMark($rawMark);
        });

        return response()->json([
            'success' => true,
            'message' => 'Correction applied successfully.'
        ]);
    }

    public function resolveValidationError(Request $request)
    {
        $request->validate([
            'validation_id' => 'required|exists:mark_entry_validations,id',
            'resolution' => 'required|string|in:resolved,ignored',
            'comment' => 'nullable|string'
        ]);

        $user = $request->user();
        $validation = \App\Models\MarkEntryValidation::findOrFail($request->validation_id);

        // Security check
        if (!$user->isAdmin() && $validation->region_id !== $user->region_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this region\'s data.'], 403);
        }

        $validation->update([
            'status' => $request->resolution,
            'resolved_by' => $user->id,
            'resolved_at' => now(),
            'resolution_comment' => $request->comment
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Validation error marked as ' . $request->resolution . '.'
        ]);
    }

    public function exportValidationErrorsCsv(Request $request)
    {
        $user = $request->user();
        $this->validateReportScope($request, $request->query('region_id'));
        
        $filters = $request->all();
        if ($user->region_id && !$user->isAdmin()) {
            $filters['region_id'] = $user->region_id;
        }

        $errors = \App\Models\MarkEntryValidation::with(['rawMark', 'candidate', 'school', 'subject', 'district', 'region'])
            ->when(!empty($filters['exam_year_id']), fn($q) => $q->where('exam_year_id', $filters['exam_year_id']))
            ->when(!empty($filters['region_id']), fn($q) => $q->where('region_id', $filters['region_id']))
            ->when(!empty($filters['district_id']), fn($q) => $q->where('district_id', $filters['district_id']))
            ->when(!empty($filters['school_id']), fn($q) => $q->where('school_id', $filters['school_id']))
            ->when(!empty($filters['subject_id']), fn($q) => $q->where('subject_id', $filters['subject_id']))
            ->when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="psle_validation_errors_' . now()->format('Ymd_His') . '.csv"',
        ];

        $callback = function() use ($errors) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['CNO', 'Candidate Name', 'Sex', 'Region', 'District', 'School', 'Subject', 'Mark', 'Error Type', 'Severity', 'Message', 'Status']);

            foreach ($errors as $e) {
                fputcsv($file, [
                    $e->candidate->candidate_id ?? $e->rawMark->candidate_index_number,
                    $e->candidate->full_name ?? 'N/A',
                    $e->candidate->gender ?? 'N/A',
                    $e->region->name ?? 'N/A',
                    $e->district->name ?? 'N/A',
                    $e->school->name ?? 'N/A',
                    $e->subject->name ?? 'N/A',
                    $e->rawMark->paper_1_marks ?? ($e->rawMark->subject_status ?? '-'),
                    $e->error_type,
                    $e->severity,
                    $e->message,
                    $e->status
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function performanceRankings(Request $request, \App\Services\PsleMarkEntryPerformanceService $performanceService)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $isTrulyAdmin = $user->isAdmin();
        $isReo = $this->isReoUser($user) || ($user->region_id && !$user->hasRole('officer') && !$this->isMarkOfficerUser($user) && !$isTrulyAdmin);
        $isMarkOfficer = $this->isMarkOfficerUser($user);

        // Security scoping check
        $regionId = $request->query('region_id');
        if (($isReo || $isMarkOfficer) && !$isTrulyAdmin) {
            // Force scoping to their assigned region ID
            $regionId = $user->region_id;
        }

        $filters = [
            'exam_year_id' => $request->query('exam_year_id'),
            'region_id' => $regionId,
            'district_id' => $request->query('district_id'),
            'school_id' => $request->query('school_id'),
            'subject_id' => $request->query('subject_id'),
        ];

        try {
            $rankings = $performanceService->getRankings($filters, $user);

            return response()->json([
                'success' => true,
                'generated_at' => now()->toDateTimeString(),
                'scope' => [
                    'exam_year_id' => $filters['exam_year_id'] ? (int) $filters['exam_year_id'] : null,
                    'region_id' => $filters['region_id'] ? (int) $filters['region_id'] : null,
                    'district_id' => $filters['district_id'] ? (int) $filters['district_id'] : null,
                    'school_id' => $filters['school_id'] ? (int) $filters['school_id'] : null,
                    'subject_id' => $filters['subject_id'] ? (int) $filters['subject_id'] : null,
                ],
                'rankings' => $rankings,
            ]);
        } catch (\Exception $e) {
            \Log::error('Performance Rankings API error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch ranking data.',
            ], 500);
        }
    }

    public function approveMissingMarks(Request $request, \App\Services\MarkEntry\PsleMissingMarksService $missingMarksService)
    {
        $request->validate([
            'selected_items' => 'required|array',
            'reason' => 'required|string',
        ]);

        $user = $request->user();
        if ($user->hasRole('mark_officer') || $user->portal_role === 'mark_officer') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        try {
            $count = $missingMarksService->approveMissingMarks(
                $request->selected_items,
                $request->reason,
                $user
            );
            return response()->json([
                'success' => true,
                'message' => "Successfully approved {$count} missing mark(s).",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            $statusCode = (strpos($e->getMessage(), 'Unauthorized') !== false) ? 400 : 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function rejectMissingMarks(Request $request, \App\Services\MarkEntry\PsleMissingMarksService $missingMarksService)
    {
        $request->validate([
            'selected_items' => 'required|array',
            'reason' => 'nullable|string',
        ]);

        $user = $request->user();
        if ($user->hasRole('mark_officer') || $user->portal_role === 'mark_officer') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        try {
            $count = $missingMarksService->rejectMissingMarks(
                $request->selected_items,
                $request->reason,
                $user
            );
            return response()->json([
                'success' => true,
                'message' => "Successfully rejected {$count} missing mark(s).",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            $statusCode = (strpos($e->getMessage(), 'Unauthorized') !== false) ? 400 : 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function commitApprovedABS(Request $request, \App\Services\MarkEntry\PsleMissingMarksService $missingMarksService)
    {
        $request->validate([
            'school_id' => 'required|integer',
            'exam_year_id' => 'required|integer',
        ]);

        $user = $request->user();
        if ($user->hasRole('mark_officer') || $user->portal_role === 'mark_officer') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        try {
            $result = $missingMarksService->commitApprovedABS(
                (int)$request->school_id,
                (int)$request->exam_year_id,
                $user
            );
            return response()->json(array_merge(['success' => true], $result));
        } catch (\Exception $e) {
            $statusCode = (strpos($e->getMessage(), 'Unauthorized') !== false) ? 400 : 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function bulkApproveMissingMarks(Request $request, \App\Services\MarkEntry\PsleMissingMarksService $missingMarksService)
    {
        $request->validate([
            'school_ids' => 'required|array',
            'exam_year_id' => 'required|integer',
            'reason' => 'required|string',
            'subject_id' => 'nullable|integer',
        ]);

        $user = $request->user();
        if ($user->hasRole('mark_officer') || $user->portal_role === 'mark_officer') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        try {
            $count = $missingMarksService->approveBulkMissingMarks(
                $request->school_ids,
                (int)$request->exam_year_id,
                $request->reason,
                $request->subject_id ? (int)$request->subject_id : null,
                $user
            );
            return response()->json([
                'success' => true,
                'message' => "Successfully bulk approved {$count} missing mark(s).",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            $statusCode = (strpos($e->getMessage(), 'Unauthorized') !== false) ? 400 : 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function bulkCommitPreview(Request $request, \App\Services\MarkEntry\PsleMissingMarksService $missingMarksService)
    {
        $request->validate([
            'exam_year_id' => 'required|integer',
            'school_ids' => 'nullable|array',
            'region_id' => 'nullable|integer',
            'district_id' => 'nullable|integer',
            'school_id' => 'nullable|integer',
            'subject_id' => 'nullable|integer',
        ]);

        $user = $request->user();

        try {
            $result = $missingMarksService->previewBulkCommit(
                $request->all(),
                $user
            );
            return response()->json(array_merge(['success' => true], $result));
        } catch (\Exception $e) {
            $statusCode = (strpos($e->getMessage(), 'Unauthorized') !== false) ? 400 : 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function bulkCommitApprovedABS(Request $request, \App\Services\MarkEntry\PsleMissingMarksService $missingMarksService)
    {
        $request->validate([
            'exam_year_id' => 'required|integer',
            'confirmation_text' => 'required|string',
            'school_ids' => 'nullable|array',
            'region_id' => 'nullable|integer',
            'district_id' => 'nullable|integer',
            'school_id' => 'nullable|integer',
            'subject_id' => 'nullable|integer',
        ]);

        if ($request->confirmation_text !== 'COMMIT ABS') {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error: Confirmation text is invalid.'
            ], 422);
        }

        $user = $request->user();
        if ($user->hasRole('mark_officer') || $user->portal_role === 'mark_officer') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        try {
            $result = $missingMarksService->commitBulkApprovedABS(
                $request->all(),
                $user
            );
            return response()->json([
                'success' => true,
                'results' => $result
            ]);
        } catch (\Exception $e) {
            $statusCode = (strpos($e->getMessage(), 'Unauthorized') !== false) ? 400 : 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function saveIncMissingMark(Request $request, \App\Services\MarkEntry\PsleMissingMarksService $missingMarksService)
    {
        $request->validate([
            'candidate_id' => 'required',
            'school_id' => 'required',
            'subject_id' => 'required',
            'exam_year_id' => 'required',
            'score' => 'required',
            'remark' => 'nullable|string',
        ]);

        $user = $request->user();

        try {
            $result = $missingMarksService->saveIncMissingMark(
                $request->all(),
                $user
            );
            return response()->json([
                'success' => true,
                'message' => 'Missing INC mark completed successfully.',
                'raw_mark_id' => $result['raw_mark_id']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
