<?php

namespace App\Services\MarkEntry;

use App\Models\Candidate;
use App\Models\RawMark;
use App\Models\School;
use App\Models\Subject;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Models\Region;
use App\Models\District;
use App\Models\User;
use App\Models\PsleMissingMarkValidation;
use App\Models\SystemEventLog;
use App\Models\MarkImportBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class PsleMissingMarksService
{
    /**
     * Get school summaries of missing marks, ABS, and INC candidates.
     */
    public function getSchoolSummaries(array $filters, User $user, int $page = 1, int $perPage = 20): \Illuminate\Pagination\LengthAwarePaginator
    {
        $examYearId = $filters['exam_year_id'];
        $regionId = $filters['region_id'] ?? null;
        $districtId = $filters['district_id'] ?? null;
        $schoolId = $filters['school_id'] ?? null;
        $classificationFilter = $filters['classification'] ?? 'all';
        $subjectId = $filters['subject_id'] ?? null;

        // Force region locking for non-admin users with region scopes
        if (!$user->isAdmin()) {
            if ($user->region_id) {
                $regionId = $user->region_id;
            } else {
                return new \Illuminate\Pagination\LengthAwarePaginator(collect(), 0, $perPage, $page, [
                    'path' => request()->url(),
                    'query' => request()->query()
                ]);
            }
        }

        // PSLE Exam Type lookup
        $psleExamTypeId = ExamType::where('code', 'PSLE')->value('id') ?? 4;

        // Active PSLE subjects
        $activeSubjects = Subject::where('exam_type_id', $psleExamTypeId)
            ->where('is_active', true)
            ->get();
        $activeSubjectsCount = max(1, $activeSubjects->count());
        $activeSubjectIds = $activeSubjects->pluck('id')->toArray();

        // 1. Get schools matching the region/district filters
        $schoolsQuery = School::with(['region', 'district'])
            ->where('education_level', 'PRIMARY')
            ->whereIn('school_type', ['PRIMARY', 'BOTH'])
            ->when($regionId, fn($q) => $q->where('region_id', $regionId))
            ->when($districtId, fn($q) => $q->where('district_id', $districtId))
            ->when($schoolId, fn($q) => $q->where('id', $schoolId));

        $schools = $schoolsQuery->get();
        $schoolIds = $schools->pluck('id')->toArray();

        if (empty($schoolIds)) {
            return new \Illuminate\Pagination\LengthAwarePaginator(collect(), 0, $perPage, $page, [
                'path' => request()->url(),
                'query' => request()->query()
            ]);
        }

        // 2. Fetch candidate counts per school matching these school IDs
        $candCounts = DB::table('candidate_exam_registrations')
            ->join('candidates', 'candidate_exam_registrations.candidate_id', '=', 'candidates.id')
            ->select('candidates.school_id', DB::raw('count(*) as count'))
            ->where('candidate_exam_registrations.exam_year_id', $examYearId)
            ->where('candidate_exam_registrations.exam_type_id', $psleExamTypeId)
            ->whereIn('candidates.school_id', $schoolIds)
            ->where('candidates.is_active', true)
            ->groupBy('candidates.school_id')
            ->pluck('count', 'school_id')
            ->toArray();

        // 3. Fetch raw mark counts per school matching these school IDs
        $markCounts = DB::table('raw_marks')
            ->select('school_id', DB::raw('count(*) as count'))
            ->where('exam_year_id', $examYearId)
            ->whereIn('school_id', $schoolIds)
            ->groupBy('school_id')
            ->pluck('count', 'school_id')
            ->toArray();

        // 4. Fetch validation presence per school matching these school IDs
        $validationPresence = DB::table('psle_missing_mark_validations')
            ->select('school_id',
                DB::raw("SUM(CASE WHEN decision = 'pending' THEN 1 ELSE 0 END) as pending_count"),
                DB::raw("SUM(CASE WHEN decision = 'approved_abs' THEN 1 ELSE 0 END) as approved_count"),
                DB::raw("SUM(CASE WHEN decision = 'committed' THEN 1 ELSE 0 END) as committed_count"),
                DB::raw("SUM(CASE WHEN decision = 'rejected' THEN 1 ELSE 0 END) as rejected_count")
            )
            ->where('exam_year_id', $examYearId)
            ->whereIn('school_id', $schoolIds)
            ->groupBy('school_id')
            ->get()
            ->keyBy('school_id')
            ->toArray();

        // 5. Identify schools matching the classification filter
        $eligibleSchoolIds = [];

        foreach ($candCounts as $sid => $cc) {
            if ($cc === 0) continue;
            $mc = $markCounts[$sid] ?? 0;
            $val = (array) ($validationPresence[$sid] ?? []);

            $hasPending = ($val['pending_count'] ?? 0) > 0;
            $hasApproved = ($val['approved_count'] ?? 0) > 0;
            $hasCommitted = ($val['committed_count'] ?? 0) > 0;
            $hasRejected = ($val['rejected_count'] ?? 0) > 0;

            $isComplete = ($mc === $cc * $activeSubjectsCount);

            $keep = false;

            if ($classificationFilter === 'all') {
                if (!$isComplete || $hasPending || $hasApproved) {
                    $keep = true;
                }
            } elseif ($classificationFilter === 'abs') {
                // Heuristic: school has missing marks or approved ABS validations
                if ($mc < $cc * $activeSubjectsCount || $hasApproved) {
                    $keep = true;
                }
            } elseif ($classificationFilter === 'inc') {
                if ($mc > 0 && $mc < $cc * $activeSubjectsCount) {
                    $keep = true;
                }
            } elseif ($classificationFilter === 'pending') {
                if ($hasPending || $hasApproved) {
                    $keep = true;
                }
            } elseif ($classificationFilter === 'approved') {
                if ($hasApproved) {
                    $keep = true;
                }
            } elseif ($classificationFilter === 'committed') {
                if ($hasCommitted) {
                    $keep = true;
                }
            } elseif ($classificationFilter === 'rejected') {
                if ($hasRejected) {
                    $keep = true;
                }
            }

            if ($keep) {
                $eligibleSchoolIds[] = $sid;
            }
        }

        if (empty($eligibleSchoolIds)) {
            return new \Illuminate\Pagination\LengthAwarePaginator(collect(), 0, $perPage, $page, [
                'path' => request()->url(),
                'query' => request()->query()
            ]);
        }

        // 6. Query and paginate the matching School models (retrieving ONLY 20 rows)
        $schoolsPaginator = School::with(['region', 'district'])
            ->whereIn('id', $eligibleSchoolIds)
            ->orderBy('code')
            ->paginate($perPage, ['*'], 'page', $page);

        $pageSchoolIds = $schoolsPaginator->pluck('id')->toArray();

        // 7. Fetch exact candidate-level status for ONLY the 20 schools on the current page
        $candidates = Candidate::whereIn('school_id', $pageSchoolIds)
            ->whereHas('examRegistrations', function($q) use ($examYearId, $psleExamTypeId) {
                $q->where('exam_year_id', $examYearId)
                  ->where('exam_type_id', $psleExamTypeId);
            })
            ->where('is_active', true)
            ->get();
        
        $candidateIds = $candidates->pluck('id')->toArray();

        $rawMarks = RawMark::whereIn('candidate_id', $candidateIds)
            ->where('exam_year_id', $examYearId)
            ->get()
            ->groupBy('candidate_id');

        $subjectSelections = \App\Models\CandidateSubjectSelection::whereIn('candidate_id', $candidateIds)
            ->where('exam_year_id', $examYearId)
            ->where('exam_type_id', $psleExamTypeId)
            ->where('is_active', true)
            ->get()
            ->groupBy('candidate_id');

        $validations = PsleMissingMarkValidation::whereIn('candidate_id', $candidateIds)
            ->where('exam_year_id', $examYearId)
            ->get()
            ->groupBy('candidate_id');

        $candidatesBySchool = $candidates->groupBy('school_id');

        // 8. Calculate exact metrics for ONLY the 20 schools
        $summaries = collect();

        foreach ($schoolsPaginator->items() as $school) {
            $schoolCandidates = $candidatesBySchool->get($school->id) ?? collect();
            if ($schoolCandidates->isEmpty()) continue;

            $registeredCount = $schoolCandidates->count();
            $completeCount = 0;
            $absCount = 0;
            $incCount = 0;
            $totalMissingRecords = 0;

            $hasPendingVal = false;
            $hasApprovedVal = false;
            $hasCommittedVal = false;
            $hasRejectedVal = false;

            foreach ($schoolCandidates as $candidate) {
                $candidateSelections = $subjectSelections->get($candidate->id) ?? collect();
                $requiredIds = $candidateSelections->isNotEmpty() ? $candidateSelections->pluck('subject_id')->toArray() : $activeSubjectIds;

                if ($subjectId) {
                    $requiredIds = in_array((int)$subjectId, $requiredIds) ? [(int)$subjectId] : [];
                }

                if (empty($requiredIds)) continue;

                $candidateMarks = $rawMarks->get($candidate->id) ?? collect();
                $candidateVals = $validations->get($candidate->id) ?? collect();

                $numericCount = 0;
                $committedAbs = 0;
                $missingSubIds = [];

                foreach ($requiredIds as $sid) {
                    $mark = $candidateMarks->firstWhere('subject_id', $sid);
                    if ($mark) {
                        if ($mark->subject_status === 'ABS') {
                            $committedAbs++;
                        } else {
                            $numericCount++;
                        }
                    } else {
                        $missingSubIds[] = $sid;
                    }
                }

                foreach ($missingSubIds as $sid) {
                    $val = $candidateVals->firstWhere('subject_id', $sid);
                    if ($val) {
                        if ($val->decision === 'pending') $hasPendingVal = true;
                        if ($val->decision === 'approved_abs') $hasApprovedVal = true;
                        if ($val->decision === 'committed') $hasCommittedVal = true;
                        if ($val->decision === 'rejected') $hasRejectedVal = true;
                    }
                }

                $isComplete = ($numericCount + $committedAbs) === count($requiredIds);
                if ($isComplete) {
                    $completeCount++;
                } else {
                    $totalMissingRecords += count($missingSubIds);
                    if ($numericCount === 0) {
                        $absCount++;
                    } else {
                        $incCount++;
                    }
                }
            }

            $completionPct = $registeredCount > 0 ? round(($completeCount / $registeredCount) * 100, 1) : 0;

            $summaries->push((object) [
                'school_id' => $school->id,
                'school_code' => $school->code,
                'school_name' => $school->name,
                'region_name' => $school->region->name ?? 'N/A',
                'district_name' => $school->district->name ?? 'N/A',
                'registered' => $registeredCount,
                'complete' => $completeCount,
                'abs' => $absCount,
                'inc' => $incCount,
                'missing_records' => $totalMissingRecords,
                'completion_pct' => $completionPct,
                'has_pending' => $hasPendingVal,
                'has_approved' => $hasApprovedVal,
                'has_committed' => $hasCommittedVal,
                'has_rejected' => $hasRejectedVal,
            ]);
        }

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $summaries,
            $schoolsPaginator->total(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query()
            ]
        );
    }

    /**
     * Get candidate-level details for drill-down of a single school.
     */
    public function getSchoolDetails(School $school, array $filters, User $user): array
    {
        $examYearId = $filters['exam_year_id'];
        $subjectIdFilter = $filters['subject_id'] ?? null;
        $classificationFilter = $filters['classification'] ?? 'all';

        $psleExamTypeId = ExamType::where('code', 'PSLE')->value('id') ?? 4;

        // Active PSLE subjects
        $activeSubjects = Subject::where('exam_type_id', $psleExamTypeId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
        $activeSubjectsCount = $activeSubjects->count();
        $activeSubjectIds = $activeSubjects->pluck('id')->toArray();

        // Get candidates in school
        $candidates = Candidate::with('school')->where('school_id', $school->id)
            ->whereHas('examRegistrations', function($q) use ($examYearId, $psleExamTypeId) {
                $q->where('exam_year_id', $examYearId)
                  ->where('exam_type_id', $psleExamTypeId);
            })
            ->where('is_active', true)
            ->orderBy('candidate_id')
            ->get();

        $candidateIds = $candidates->pluck('id')->toArray();

        // Get raw marks
        $rawMarks = RawMark::whereIn('candidate_id', $candidateIds)
            ->where('exam_year_id', $examYearId)
            ->get()
            ->groupBy('candidate_id');

        // Get custom subject selections
        $subjectSelections = \App\Models\CandidateSubjectSelection::whereIn('candidate_id', $candidateIds)
            ->where('exam_year_id', $examYearId)
            ->where('exam_type_id', $psleExamTypeId)
            ->where('is_active', true)
            ->get()
            ->groupBy('candidate_id');

        // Get validations
        $validations = PsleMissingMarkValidation::whereIn('candidate_id', $candidateIds)
            ->where('exam_year_id', $examYearId)
            ->get()
            ->groupBy('candidate_id');

        $rows = [];

        foreach ($candidates as $candidate) {
            // Determine required subjects for this candidate
            $candidateSelections = $subjectSelections->get($candidate->id) ?? collect();
            if ($candidateSelections->isNotEmpty()) {
                $requiredIds = $candidateSelections->pluck('subject_id')->toArray();
            } else {
                $requiredIds = $activeSubjectIds;
            }

            // Overall classification before subject filter
            $candMarks = $rawMarks->get($candidate->id) ?? collect();
            $candVals = $validations->get($candidate->id) ?? collect();

            $totalRequired = count($requiredIds);
            $numericCount = 0;
            $committedAbs = 0;

            foreach ($requiredIds as $sid) {
                $mark = $candMarks->firstWhere('subject_id', $sid);
                if ($mark) {
                    if ($mark->subject_status === 'ABS') {
                        $committedAbs++;
                    } else {
                        $numericCount++;
                    }
                }
            }

            $isComplete = ($numericCount + $committedAbs) === $totalRequired;
            if ($isComplete) {
                $overallClassification = 'COMPLETE';
            } elseif ($numericCount === 0) {
                $overallClassification = 'ABS';
            } else {
                $overallClassification = 'INC';
            }

            // If subject_id filter is set, restrict checks
            if ($subjectIdFilter) {
                if (in_array((int)$subjectIdFilter, $requiredIds)) {
                    $requiredIds = [(int)$subjectIdFilter];
                } else {
                    $requiredIds = [];
                }
            }

            if (empty($requiredIds)) {
                continue;
            }

            $subjectCells = [];
            $hasFilteredPending = false;
            $hasFilteredApproved = false;
            $hasFilteredCommitted = false;
            $hasFilteredRejected = false;

            foreach ($activeSubjects as $subject) {
                $sid = $subject->id;
                
                if (!in_array($sid, $requiredIds)) {
                    $subjectCells[$sid] = [
                        'status' => 'not_applicable',
                        'display' => '-',
                        'is_missing' => false,
                        'validation' => null,
                    ];
                    continue;
                }

                $mark = $candMarks->firstWhere('subject_id', $sid);
                $val = $candVals->firstWhere('subject_id', $sid);

                if ($val) {
                    if ($val->decision === 'pending') $hasFilteredPending = true;
                    if ($val->decision === 'approved_abs') $hasFilteredApproved = true;
                    if ($val->decision === 'committed') $hasFilteredCommitted = true;
                    if ($val->decision === 'rejected') $hasFilteredRejected = true;
                }

                if ($mark) {
                    if ($mark->subject_status === 'ABS') {
                        $subjectCells[$sid] = [
                            'status' => 'committed_abs',
                            'display' => 'X',
                            'is_missing' => false,
                            'validation' => $val,
                        ];
                    } else {
                        $subjectCells[$sid] = [
                            'status' => 'numeric_mark',
                            'display' => $mark->paper_1_marks,
                            'is_missing' => false,
                            'validation' => $val,
                        ];
                    }
                } else {
                    // Missing mark
                    $statusVal = 'missing';
                    $displayVal = $overallClassification; // ABS or INC

                    if ($val) {
                        if ($val->decision === 'approved_abs') {
                            $statusVal = 'approved_abs';
                            $displayVal = 'Approved ABS';
                        } elseif ($val->decision === 'rejected') {
                            $statusVal = 'rejected';
                            $displayVal = 'Rejected';
                        }
                    }

                    $subjectCells[$sid] = [
                        'status' => $statusVal,
                        'display' => $displayVal,
                        'is_missing' => true,
                        'validation' => $val,
                    ];
                }
            }

            // Apply classification filter to candidate row
            $keep = true;
            if ($classificationFilter === 'abs' && $overallClassification !== 'ABS') $keep = false;
            if ($classificationFilter === 'inc' && $overallClassification !== 'INC') $keep = false;
            if ($classificationFilter === 'pending' && !$hasFilteredPending && !$hasFilteredApproved) $keep = false;
            if ($classificationFilter === 'approved' && !$hasFilteredApproved) $keep = false;
            if ($classificationFilter === 'committed' && !$hasFilteredCommitted) $keep = false;
            if ($classificationFilter === 'rejected' && !$hasFilteredRejected) $keep = false;

            if ($keep) {
                $rows[] = [
                    'candidate' => $candidate,
                    'classification' => $overallClassification,
                    'subject_cells' => $subjectCells,
                    'remarks' => $candVals->pluck('reason')->filter()->unique()->implode('; '),
                ];
            }
        }

        return [
            'subjects' => $activeSubjects,
            'rows' => $rows,
            'pending_count' => count(array_filter($rows, function($r) {
                return collect($r['subject_cells'])->contains(fn($cell) => isset($cell['validation']) && $cell['validation']->decision === 'approved_abs');
            })),
        ];
    }

    /**
     * Approve selected missing marks as ABS (must be ABS candidate).
     */
    public function approveMissingMarks(array $selectedItems, string $reason, User $user): int
    {
        $count = 0;
        $psleExamTypeId = ExamType::where('code', 'PSLE')->value('id') ?? 4;

        foreach ($selectedItems as $item) {
            $candidateId = $item['candidate_id'];
            $subjectId = $item['subject_id'];
            $examYearId = $item['exam_year_id'];
            $schoolId = $item['school_id'];

            $candidate = Candidate::with('school')->findOrFail($candidateId);
            if (!$user->isAdmin()) {
                if ($candidate->school->region_id !== $user->region_id) {
                    throw new \Exception("Unauthorized: Candidate belongs to another region.");
                }
            }

            // Refinement: Only allow ABS candidate validation. If they have any numeric marks, skip.
            $numericMarksCount = RawMark::where('candidate_id', $candidateId)
                ->where('exam_year_id', $examYearId)
                ->whereNull('subject_status')
                ->count();

            if ($numericMarksCount > 0) {
                continue; // INC candidate - skipped
            }

            PsleMissingMarkValidation::updateOrCreate(
                [
                    'exam_year_id' => $examYearId,
                    'school_id' => $schoolId,
                    'candidate_id' => $candidateId,
                    'subject_id' => $subjectId,
                ],
                [
                    'region_id' => $candidate->school->region_id,
                    'district_id' => $candidate->school->district_id,
                    'classification' => 'ABS',
                    'decision' => 'approved_abs',
                    'reason' => $reason,
                    'remarks' => 'Approved ABS by ' . $user->name,
                    'created_by' => $user->id,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]
            );

            $count++;
        }

        return $count;
    }

    /**
     * Reject selected missing marks validation.
     */
    public function rejectMissingMarks(array $selectedItems, ?string $reason, User $user): int
    {
        $count = 0;

        foreach ($selectedItems as $item) {
            $candidateId = $item['candidate_id'];
            $subjectId = $item['subject_id'];
            $examYearId = $item['exam_year_id'];
            $schoolId = $item['school_id'];

            $candidate = Candidate::with('school')->findOrFail($candidateId);
            if (!$user->isAdmin()) {
                if ($candidate->school->region_id !== $user->region_id) {
                    throw new \Exception("Unauthorized: Candidate belongs to another region.");
                }
            }

            PsleMissingMarkValidation::updateOrCreate(
                [
                    'exam_year_id' => $examYearId,
                    'school_id' => $schoolId,
                    'candidate_id' => $candidateId,
                    'subject_id' => $subjectId,
                ],
                [
                    'region_id' => $candidate->school->region_id,
                    'district_id' => $candidate->school->district_id,
                    'classification' => 'ABS',
                    'decision' => 'rejected',
                    'reason' => $reason ?? 'Rejected ABS validation request.',
                    'remarks' => 'Rejected by ' . $user->name,
                    'created_by' => $user->id,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]
            );

            $count++;
        }

        return $count;
    }

    /**
     * Commit approved ABS validations into raw_marks table.
     */
    public function commitApprovedABS(int $schoolId, int $examYearId, User $user): array
    {
        return DB::transaction(function() use ($schoolId, $examYearId, $user) {
            $school = School::findOrFail($schoolId);
            $examYear = ExamYear::findOrFail($examYearId);
            
            if (!$user->isAdmin()) {
                if ($school->region_id !== $user->region_id) {
                    throw new \Exception("Unauthorized: School belongs to another region.");
                }
            }

            $validations = PsleMissingMarkValidation::with(['candidate', 'subject'])
                ->where('school_id', $schoolId)
                ->where('exam_year_id', $examYearId)
                ->where('decision', 'approved_abs')
                ->get();

            $committedCount = 0;
            $skippedCount = 0;
            $psleExamTypeId = ExamType::where('code', 'PSLE')->value('id') ?? 4;

            foreach ($validations as $val) {
                $candidate = $val->candidate;
                $subject = $val->subject;

                // Protect numeric marks: check if any numeric mark exists
                $existingMark = RawMark::where([
                    'candidate_id' => $candidate->id,
                    'subject_id' => $subject->id,
                    'exam_year_id' => $examYearId,
                ])
                ->where(function($q) {
                    $q->whereNotNull('paper_1_marks')
                      ->orWhereNotNull('paper_2_marks')
                      ->orWhereNotNull('paper_3_marks')
                      ->orWhereNotNull('practical_marks')
                      ->orWhereNotNull('project_marks');
                })
                ->first();

                if ($existingMark) {
                    $skippedCount++;
                    continue; // Skip without overwriting numeric marks
                }

                // Get or create validation batch
                $batchCode = 'ABS_VAL_' . $school->code . '_' . $subject->code . '_' . $examYear->year_label;
                $batch = MarkImportBatch::where('batch_code', $batchCode)->first();
                if (!$batch) {
                    try {
                        $batch = MarkImportBatch::create([
                            'batch_code' => $batchCode,
                            'batch_name' => 'REO ABS Validation - ' . $school->name . ' (' . $subject->name . ')',
                            'batch_type' => 'manual',
                            'exam_year' => (int) $examYear->year_label,
                            'exam_year_id' => $examYearId,
                            'region_id' => $school->region_id,
                            'district_id' => $school->district_id,
                            'school_id' => $schoolId,
                            'subject_id' => $subject->id,
                            'exam_type_id' => $psleExamTypeId,
                            'status' => 'approved',
                            'lifecycle_state' => 'approved',
                            'total_records' => 0,
                            'valid_records' => 0,
                            'error_records' => 0,
                            'created_by' => $user->id,
                            'imported_by' => $user->id,
                            'imported_at' => now(),
                            'approved_by' => $user->id,
                            'approved_at' => now(),
                        ]);
                    } catch (\Illuminate\Database\QueryException $e) {
                        $msg = $e->getMessage();
                        if ($e->getCode() === '23000' || $e->getCode() === 23000 || str_contains($msg, '23000') || str_contains($msg, '1062') || str_contains($msg, 'UNIQUE constraint')) {
                            $batch = MarkImportBatch::where('batch_code', $batchCode)->first();
                            if (!$batch) {
                                throw $e;
                            }
                        } else {
                            throw $e;
                        }
                    }
                }

                // Insert or update raw_marks with subject_status = ABS
                RawMark::updateOrCreate(
                    [
                        'candidate_id' => $candidate->id,
                        'subject_id' => $subject->id,
                        'exam_year_id' => $examYearId,
                    ],
                    [
                        'mark_import_batch_id' => $batch->id,
                        'school_id' => $schoolId,
                        'candidate_index_number' => $candidate->candidate_id,
                        'full_name' => $candidate->full_name,
                        'subject_status' => 'ABS',
                        'status_reason' => $val->reason,
                        'has_errors' => false,
                        'entered_by' => $val->approved_by,
                        'updated_by' => $user->id,
                        'row_number' => 0,
                        'raw_data' => [
                            'candidate_index_number' => $candidate->candidate_id,
                            'full_name' => $candidate->full_name,
                            'prem_no' => $candidate->prem_no,
                            'gender' => $candidate->gender,
                            'subject_status' => 'ABS',
                            'reason' => $val->reason,
                        ],
                        'processed_at' => now(),
                    ]
                );

                // Update validation record
                $val->update([
                    'decision' => 'committed',
                    'committed_by' => $user->id,
                    'committed_at' => now(),
                ]);

                // Update batch totals
                $batch->update([
                    'total_records' => RawMark::where('mark_import_batch_id', $batch->id)->count(),
                    'valid_records' => RawMark::where('mark_import_batch_id', $batch->id)->where('has_errors', false)->count(),
                ]);

                // Log system event for audit trail
                SystemEventLog::record(
                    SystemEventLog::CAT_MODERATION,
                    'abs_validation_committed',
                    SystemEventLog::STATUS_SUCCESS,
                    "ABS validation committed for candidate {$candidate->candidate_id} in subject {$subject->code}",
                    [
                        'candidate_id' => $candidate->id,
                        'candidate_index_number' => $candidate->candidate_id,
                        'subject_id' => $subject->id,
                        'subject_code' => $subject->code,
                        'school_id' => $schoolId,
                        'exam_year' => $examYear->year_label,
                        'reason' => $val->reason,
                    ],
                    actorUserId: $user->id
                );

                $committedCount++;
            }

            return [
                'committed' => $committedCount,
                'skipped' => $skippedCount,
            ];
        });
    }

    /**
     * Bulk approve missing marks as ABS for selected schools.
     */
    public function approveBulkMissingMarks(array $schoolIds, int $examYearId, string $reason, ?int $subjectIdFilter, User $user): int
    {
        if (empty($schoolIds)) {
            return 0;
        }

        // Validate region scoping if user is not admin
        if (!$user->isAdmin()) {
            if ($user->region_id) {
                $unauthorizedCount = School::whereIn('id', $schoolIds)
                    ->where('region_id', '!=', $user->region_id)
                    ->count();
                if ($unauthorizedCount > 0) {
                    throw new \Exception("Unauthorized: One or more selected schools belong to another region.");
                }
            } else {
                throw new \Exception("Unauthorized: User has no assigned region.");
            }
        }

        $psleExamTypeId = ExamType::where('code', 'PSLE')->value('id') ?? 4;
        $examYear = ExamYear::findOrFail($examYearId);

        // Fetch eligible ABS candidates (candidates with active registration who have NO numeric marks)
        $eligibleCandidatesQuery = Candidate::whereIn('school_id', $schoolIds)
            ->whereHas('examRegistrations', function ($q) use ($examYearId, $psleExamTypeId) {
                $q->where('exam_year_id', $examYearId)
                  ->where('exam_type_id', $psleExamTypeId);
            })
            ->where('is_active', true)
            ->whereDoesntHave('rawMarks', function ($q) use ($examYearId) {
                $q->where('exam_year_id', $examYearId)
                  ->where(function ($inner) {
                      $inner->whereNull('subject_status')
                            ->orWhereNotNull('paper_1_marks')
                            ->orWhereNotNull('paper_2_marks')
                            ->orWhereNotNull('paper_3_marks')
                            ->orWhereNotNull('practical_marks')
                            ->orWhereNotNull('project_marks');
                  });
            });

        $count = 0;
        $activeSubjectIds = Subject::where('exam_type_id', $psleExamTypeId)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        $eligibleCandidatesQuery->chunk(100, function ($candidates) use ($examYearId, $psleExamTypeId, $subjectIdFilter, $activeSubjectIds, $reason, $user, &$count, $examYear) {
            $candidateIds = $candidates->pluck('id')->toArray();

            // Fetch existing raw marks
            $rawMarks = RawMark::whereIn('candidate_id', $candidateIds)
                ->where('exam_year_id', $examYearId)
                ->get()
                ->groupBy('candidate_id');

            // Fetch custom subject selections
            $subjectSelections = \App\Models\CandidateSubjectSelection::whereIn('candidate_id', $candidateIds)
                ->where('exam_year_id', $examYearId)
                ->where('exam_type_id', $psleExamTypeId)
                ->where('is_active', true)
                ->get()
                ->groupBy('candidate_id');

            foreach ($candidates as $candidate) {
                $selections = $subjectSelections->get($candidate->id) ?? collect();
                if ($selections->isNotEmpty()) {
                    $requiredIds = $selections->pluck('subject_id')->toArray();
                } else {
                    $requiredIds = $activeSubjectIds;
                }

                if ($subjectIdFilter) {
                    if (in_array((int)$subjectIdFilter, $requiredIds)) {
                        $requiredIds = [(int)$subjectIdFilter];
                    } else {
                        $requiredIds = [];
                    }
                }

                $candidateMarks = $rawMarks->get($candidate->id) ?? collect();

                foreach ($requiredIds as $sid) {
                    $mark = $candidateMarks->firstWhere('subject_id', $sid);
                    if (!$mark) {
                        // Create validation record
                        PsleMissingMarkValidation::updateOrCreate(
                            [
                                'exam_year_id' => $examYearId,
                                'school_id' => $candidate->school_id,
                                'candidate_id' => $candidate->id,
                                'subject_id' => $sid,
                            ],
                            [
                                'region_id' => $candidate->school->region_id,
                                'district_id' => $candidate->school->district_id,
                                'classification' => 'ABS',
                                'decision' => 'approved_abs',
                                'reason' => $reason,
                                'remarks' => 'Bulk approved ABS by ' . $user->name,
                                'created_by' => $user->id,
                                'approved_by' => $user->id,
                                'approved_at' => now(),
                            ]
                        );

                        // Audit Trail Log
                        $subject = Subject::find($sid);
                        SystemEventLog::record(
                            SystemEventLog::CAT_MODERATION,
                            'abs_validation_approved',
                            SystemEventLog::STATUS_SUCCESS,
                            "ABS validation approved in bulk for candidate {$candidate->candidate_id} in subject " . ($subject->code ?? $sid),
                            [
                                'candidate_id' => $candidate->id,
                                'candidate_index_number' => $candidate->candidate_id,
                                'subject_id' => $sid,
                                'subject_code' => $subject->code ?? null,
                                'school_id' => $candidate->school_id,
                                'exam_year' => $examYear->year_label,
                                'reason' => $reason,
                            ],
                            actorUserId: $user->id
                        );

                        $count++;
                    }
                }
            }
        });

        return $count;
    }

    /**
     * Preview bulk commit of approved ABS validation records.
     */
    public function previewBulkCommit(array $filters, User $user): array
    {
        $examYearId = $filters['exam_year_id'];
        $regionId = $filters['region_id'] ?? null;
        $districtId = $filters['district_id'] ?? null;
        $schoolId = $filters['school_id'] ?? null;
        $subjectId = $filters['subject_id'] ?? null;
        $schoolIds = $filters['school_ids'] ?? null;

        if (!$user->isAdmin()) {
            if ($user->region_id) {
                $regionId = $user->region_id;
            } else {
                throw new \Exception("Unauthorized: User has no assigned region.");
            }
        }

        $query = PsleMissingMarkValidation::where('exam_year_id', $examYearId)
            ->where('decision', 'approved_abs')
            ->where('classification', 'ABS');

        if ($regionId) {
            $query->where('region_id', $regionId);
        }
        if ($districtId) {
            $query->where('district_id', $districtId);
        }
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }
        if (!empty($schoolIds)) {
            $query->whereIn('school_id', $schoolIds);
        }

        $validations = $query->get();

        if ($validations->isEmpty()) {
            return [
                'regions' => [],
                'districts' => [],
                'schools_count' => 0,
                'candidates_count' => 0,
                'to_commit_count' => 0,
                'skipped_count' => 0,
            ];
        }

        $affectedRegions = Region::whereIn('id', $validations->pluck('region_id')->unique()->toArray())->pluck('name')->toArray();
        $affectedDistricts = District::whereIn('id', $validations->pluck('district_id')->unique()->toArray())->pluck('name')->toArray();
        $schoolsCount = $validations->pluck('school_id')->unique()->count();
        $candidatesCount = $validations->pluck('candidate_id')->unique()->count();
        $totalRecords = $validations->count();

        // Calculate skipped records (where numeric marks already exist)
        $candidateIds = $validations->pluck('candidate_id')->unique()->toArray();
        $numericMarks = RawMark::whereIn('candidate_id', $candidateIds)
            ->where('exam_year_id', $examYearId)
            ->where(function($q) {
                $q->whereNull('subject_status')
                  ->orWhereNotNull('paper_1_marks')
                  ->orWhereNotNull('paper_2_marks')
                  ->orWhereNotNull('paper_3_marks')
                  ->orWhereNotNull('practical_marks')
                  ->orWhereNotNull('project_marks');
            })
            ->get(['candidate_id', 'subject_id'])
            ->groupBy('candidate_id');

        $skippedCount = 0;
        foreach ($validations as $val) {
            $candMarks = $numericMarks->get($val->candidate_id);
            $hasNumeric = $candMarks && $candMarks->firstWhere('subject_id', $val->subject_id);
            if ($hasNumeric) {
                $skippedCount++;
            }
        }

        return [
            'regions' => $affectedRegions,
            'districts' => $affectedDistricts,
            'schools_count' => $schoolsCount,
            'candidates_count' => $candidatesCount,
            'to_commit_count' => max(0, $totalRecords - $skippedCount),
            'skipped_count' => $skippedCount,
        ];
    }

    /**
     * Commit bulk approved ABS validation records.
     */
    public function commitBulkApprovedABS(array $filters, User $user): array
    {
        $examYearId = $filters['exam_year_id'];
        $regionId = $filters['region_id'] ?? null;
        $districtId = $filters['district_id'] ?? null;
        $schoolId = $filters['school_id'] ?? null;
        $subjectId = $filters['subject_id'] ?? null;
        $schoolIds = $filters['school_ids'] ?? null;

        if (!$user->isAdmin()) {
            if ($user->region_id) {
                $regionId = $user->region_id;
            } else {
                throw new \Exception("Unauthorized: User has no assigned region.");
            }
        }

        $query = PsleMissingMarkValidation::with(['candidate', 'subject', 'school'])
            ->where('exam_year_id', $examYearId)
            ->where('decision', 'approved_abs')
            ->where('classification', 'ABS');

        if ($regionId) {
            $query->where('region_id', $regionId);
        }
        if ($districtId) {
            $query->where('district_id', $districtId);
        }
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }
        if (!empty($schoolIds)) {
            $query->whereIn('school_id', $schoolIds);
        }

        $validations = $query->get();

        $totalApproved = $validations->count();
        $committed = 0;
        $skipped = 0;
        $failed = 0;

        if ($totalApproved === 0) {
            return [
                'total_approved' => 0,
                'committed' => 0,
                'skipped' => 0,
                'failed' => 0,
            ];
        }

        $examYear = ExamYear::findOrFail($examYearId);
        $psleExamTypeId = ExamType::where('code', 'PSLE')->value('id') ?? 4;

        // Group validations by school to process in chunks / separate transactions
        $validationsBySchool = $validations->groupBy('school_id');

        foreach ($validationsBySchool as $schId => $schoolValList) {
            try {
                DB::transaction(function() use ($schId, $schoolValList, $examYear, $examYearId, $psleExamTypeId, $user, &$committed, &$skipped) {
                    $school = School::findOrFail($schId);

                    foreach ($schoolValList as $val) {
                        $candidate = $val->candidate;
                        $subject = $val->subject;

                        // Check if numeric marks exist for candidate-subject
                        $existingMark = RawMark::where([
                            'candidate_id' => $candidate->id,
                            'subject_id' => $subject->id,
                            'exam_year_id' => $examYearId,
                        ])
                        ->where(function($q) {
                            $q->whereNotNull('paper_1_marks')
                              ->orWhereNotNull('paper_2_marks')
                              ->orWhereNotNull('paper_3_marks')
                              ->orWhereNotNull('practical_marks')
                              ->orWhereNotNull('project_marks');
                        })
                        ->first();

                        if ($existingMark) {
                            $skipped++;
                            continue;
                        }

                        // Get or create import batch for validation
                        $batchCode = 'ABS_VAL_' . $school->code . '_' . $subject->code . '_' . $examYear->year_label;
                        $batch = MarkImportBatch::where('batch_code', $batchCode)->first();
                        if (!$batch) {
                            try {
                                $batch = MarkImportBatch::create([
                                    'batch_code' => $batchCode,
                                    'batch_name' => 'REO ABS Validation - ' . $school->name . ' (' . $subject->name . ')',
                                    'batch_type' => 'manual',
                                    'exam_year' => (int) $examYear->year_label,
                                    'exam_year_id' => $examYearId,
                                    'region_id' => $school->region_id,
                                    'district_id' => $school->district_id,
                                    'school_id' => $school->id,
                                    'subject_id' => $subject->id,
                                    'exam_type_id' => $psleExamTypeId,
                                    'status' => 'approved',
                                    'lifecycle_state' => 'approved',
                                    'total_records' => 0,
                                    'valid_records' => 0,
                                    'error_records' => 0,
                                    'created_by' => $user->id,
                                    'imported_by' => $user->id,
                                    'imported_at' => now(),
                                    'approved_by' => $user->id,
                                    'approved_at' => now(),
                                ]);
                            } catch (\Illuminate\Database\QueryException $e) {
                                $msg = $e->getMessage();
                                if ($e->getCode() === '23000' || $e->getCode() === 23000 || str_contains($msg, '23000') || str_contains($msg, '1062') || str_contains($msg, 'UNIQUE constraint')) {
                                    $batch = MarkImportBatch::where('batch_code', $batchCode)->first();
                                    if (!$batch) {
                                        throw $e;
                                    }
                                } else {
                                    throw $e;
                                }
                            }
                        }

                        // Insert or update raw mark
                        RawMark::updateOrCreate(
                            [
                                'candidate_id' => $candidate->id,
                                'subject_id' => $subject->id,
                                'exam_year_id' => $examYearId,
                            ],
                            [
                                'mark_import_batch_id' => $batch->id,
                                'school_id' => $school->id,
                                'candidate_index_number' => $candidate->candidate_id,
                                'full_name' => $candidate->full_name,
                                'subject_status' => 'ABS',
                                'status_reason' => $val->reason,
                                'has_errors' => false,
                                'entered_by' => $val->approved_by,
                                'updated_by' => $user->id,
                                'row_number' => 0,
                                'raw_data' => [
                                    'candidate_index_number' => $candidate->candidate_id,
                                    'full_name' => $candidate->full_name,
                                    'prem_no' => $candidate->prem_no,
                                    'gender' => $candidate->gender,
                                    'subject_status' => 'ABS',
                                    'reason' => $val->reason,
                                ],
                                'processed_at' => now(),
                            ]
                        );

                        // Update validation record to committed
                        $val->update([
                            'decision' => 'committed',
                            'committed_by' => $user->id,
                            'committed_at' => now(),
                        ]);

                        // Update batch totals
                        $batch->update([
                            'total_records' => RawMark::where('mark_import_batch_id', $batch->id)->count(),
                            'valid_records' => RawMark::where('mark_import_batch_id', $batch->id)->where('has_errors', false)->count(),
                        ]);

                        // System Event Log
                        SystemEventLog::record(
                            SystemEventLog::CAT_MODERATION,
                            'abs_validation_committed',
                            SystemEventLog::STATUS_SUCCESS,
                            "ABS validation committed for candidate {$candidate->candidate_id} in subject {$subject->code}",
                            [
                                'candidate_id' => $candidate->id,
                                'candidate_index_number' => $candidate->candidate_id,
                                'subject_id' => $subject->id,
                                'subject_code' => $subject->code,
                                'school_id' => $school->id,
                                'exam_year' => $examYear->year_label,
                                'reason' => $val->reason,
                            ],
                            actorUserId: $user->id
                        );

                        $committed++;
                    }
                });
            } catch (\Exception $e) {
                \Log::error("Failed to bulk commit ABS for school {$schId}: " . $e->getMessage());
                $failed += $schoolValList->count();
            }
        }

        return [
            'total_approved' => $totalApproved,
            'committed' => $committed,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }

    /**
     * Save/complete missing INC mark for a candidate-subject.
     */
    public function saveIncMissingMark(array $payload, User $user): array
    {
        return DB::transaction(function() use ($payload, $user) {
            $candidateId = $payload['candidate_id'];
            $schoolId = $payload['school_id'];
            $subjectId = $payload['subject_id'];
            $examYearId = $payload['exam_year_id'];
            $score = $payload['score'];
            $remark = $payload['remark'] ?? '';

            $candidate = Candidate::with('school')->findOrFail($candidateId);
            $school = School::findOrFail($schoolId);
            $subject = Subject::findOrFail($subjectId);
            $examYear = ExamYear::findOrFail($examYearId);

            // 1. Role validation
            $isTrulyAdmin = $user->isAdmin();
            $isReo = $user->hasRole('reo') || $user->hasRole('rao') || in_array($user->portal_role, ['reo', 'rao', 'mock_rao'], true);
            $isMarkOfficer = in_array($user->role?->code, ['mark_officer', 'mark_entry_officer', 'meo'], true)
                || $user->role?->name === 'Mark Entry Officer'
                || in_array($user->portal_role, ['mark_officer', 'mark_entry_officer', 'meo'], true);

            if (!$isTrulyAdmin && !$isReo && !$isMarkOfficer) {
                throw new \Exception("Unauthorized: User does not have permission to enter marks.");
            }

            // 2. Scope validation
            if ($isReo && !$isTrulyAdmin) {
                if ((int) $school->region_id !== (int) $user->region_id) {
                    throw new \Exception("Unauthorized: School belongs to another region.");
                }
            } elseif ($isMarkOfficer && !$isTrulyAdmin) {
                if ($user->region_id) {
                    if ((int) $school->region_id !== (int) $user->region_id) {
                        throw new \Exception("Unauthorized: School belongs to another region.");
                    }
                } else {
                    $psleExamTypeId = ExamType::where('code', 'PSLE')->value('id') ?? 4;
                    $hasAssignment = \App\Models\MarkEntryAssignment::where([
                        'assigned_to' => $user->id,
                        'school_id' => $schoolId,
                        'subject_id' => $subjectId,
                        'exam_year_id' => $examYearId,
                        'exam_type_id' => $psleExamTypeId,
                        'status' => 'active',
                    ])->exists();
                    if (!$hasAssignment) {
                        throw new \Exception("Unauthorized: You do not have an active assignment to enter marks for this school and subject.");
                    }
                }
            }

            // 3. Candidate validation
            if ((int) $candidate->school_id !== (int) $schoolId) {
                throw new \Exception("Validation Error: Candidate is not registered under the selected school.");
            }

            $psleExamTypeId = ExamType::where('code', 'PSLE')->value('id') ?? 4;
            $candidateRegistered = $candidate->examRegistrations()
                ->where('exam_type_id', $psleExamTypeId)
                ->where('exam_year_id', $examYearId)
                ->exists();
            if (!$candidateRegistered) {
                throw new \Exception("Validation Error: Candidate is not registered for PSLE in the selected exam year.");
            }

            // 4. Validate mark range
            if (!is_numeric($score) || $score < 0 || $score > $subject->max_marks) {
                throw new \Exception("Validation Error: Score must be a number between 0 and {$subject->max_marks}.");
            }

            // 5. Check existing raw mark
            $existingMark = RawMark::where([
                'candidate_id' => $candidateId,
                'subject_id' => $subjectId,
                'exam_year_id' => $examYearId,
            ])->first();

            $oldValue = null;
            if ($existingMark) {
                if ($existingMark->paper_1_marks !== null) {
                    throw new \Exception("Existing numeric mark exists. Overwriting is not permitted via this form.");
                }
                if ($existingMark->subject_status === 'ABS') {
                    throw new \Exception("Candidate is marked as ABS for this subject. Cannot enter INC mark.");
                }
                $oldValue = $existingMark->paper_1_marks;
            }

            // Check validation records
            $existingValidation = PsleMissingMarkValidation::where([
                'candidate_id' => $candidateId,
                'subject_id' => $subjectId,
                'exam_year_id' => $examYearId,
            ])->first();

            if ($existingValidation && $existingValidation->decision === 'committed') {
                throw new \Exception("Missing mark validation has already been committed. Cannot enter INC mark.");
            }

            // 6. Get or create batch
            $batchCode = 'INC_VAL_' . $school->code . '_' . $subject->code . '_' . $examYear->year_label;
            $batch = MarkImportBatch::where('batch_code', $batchCode)->first();
            if (!$batch) {
                try {
                    $batch = MarkImportBatch::create([
                        'batch_code' => $batchCode,
                        'batch_name' => 'INC Mark Completion - ' . $school->name . ' (' . $subject->name . ')',
                        'batch_type' => 'manual',
                        'exam_year' => (int) $examYear->year_label,
                        'exam_year_id' => $examYearId,
                        'region_id' => $school->region_id,
                        'district_id' => $school->district_id,
                        'school_id' => $schoolId,
                        'subject_id' => $subjectId,
                        'exam_type_id' => $psleExamTypeId,
                        'status' => 'approved',
                        'lifecycle_state' => 'approved',
                        'total_records' => 0,
                        'valid_records' => 0,
                        'error_records' => 0,
                        'created_by' => $user->id,
                        'imported_by' => $user->id,
                        'imported_at' => now(),
                        'approved_by' => $user->id,
                        'approved_at' => now(),
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    $msg = $e->getMessage();
                    if ($e->getCode() === '23000' || $e->getCode() === 23000 || str_contains($msg, '23000') || str_contains($msg, '1062') || str_contains($msg, 'UNIQUE constraint')) {
                        $batch = MarkImportBatch::where('batch_code', $batchCode)->first();
                        if (!$batch) {
                            throw $e;
                        }
                    } else {
                        throw $e;
                    }
                }
            }

            // 7. Save or Update RawMark
            $rawMark = RawMark::updateOrCreate(
                [
                    'candidate_id' => $candidateId,
                    'subject_id' => $subjectId,
                    'exam_year_id' => $examYearId,
                ],
                [
                    'mark_import_batch_id' => $batch->id,
                    'school_id' => $schoolId,
                    'candidate_index_number' => $candidate->candidate_id,
                    'full_name' => $candidate->full_name,
                    'paper_1_marks' => $score,
                    'subject_status' => null,
                    'status_reason' => $remark,
                    'has_errors' => false,
                    'entered_by' => $existingMark ? $existingMark->entered_by : $user->id,
                    'updated_by' => $user->id,
                    'row_number' => 0,
                    'raw_data' => [
                        'candidate_index_number' => $candidate->candidate_id,
                        'full_name' => $candidate->full_name,
                        'prem_no' => $candidate->prem_no,
                        'gender' => $candidate->gender,
                        'score' => $score,
                        'remark' => $remark,
                    ],
                    'processed_at' => now(),
                ]
            );

            // Clean up any pending validation records for this candidate-subject
            if ($existingValidation) {
                $existingValidation->delete();
            }

            // Update batch totals
            $batch->update([
                'total_records' => RawMark::where('mark_import_batch_id', $batch->id)->count(),
                'valid_records' => RawMark::where('mark_import_batch_id', $batch->id)->where('has_errors', false)->count(),
            ]);

            // 8. Write Audit Logs
            // SystemEventLog
            SystemEventLog::record(
                SystemEventLog::CAT_MODERATION,
                'inc_mark_completed',
                SystemEventLog::STATUS_SUCCESS,
                "Completed missing INC mark for candidate {$candidate->candidate_id} in subject {$subject->code}",
                [
                    'candidate_id' => $candidateId,
                    'school_id' => $schoolId,
                    'subject_id' => $subjectId,
                    'old_value' => $oldValue,
                    'new_value' => $score,
                    'actor_user_id' => $user->id,
                    'timestamp' => now()->toIso8601String(),
                    'remark' => $remark,
                ],
                actorUserId: $user->id
            );

            // GovernanceAuditLog
            \App\Models\GovernanceAuditLog::log(
                'inc_mark_completed',
                userId: $user->id,
                adminId: $isTrulyAdmin ? $user->id : null,
                data: [
                    'candidate_id' => $candidateId,
                    'school_id' => $schoolId,
                    'subject_id' => $subjectId,
                    'old_value' => $oldValue,
                    'new_value' => $score,
                    'remark' => $remark,
                    'timestamp' => now()->toIso8601String(),
                ]
            );

            // MarkEntryChange
            \App\Models\MarkEntryChange::create([
                'raw_mark_id' => $rawMark->id,
                'changed_by' => $user->id,
                'change_type' => 'edit',
                'field_name' => 'paper_1_marks',
                'old_value' => $oldValue,
                'new_value' => $score,
                'reason' => 'Completed missing INC mark from Missing Marks drill-down page. Reason: ' . $remark,
                'changed_at' => now(),
                'ip_address' => request()->ip(),
            ]);

            // 9. Update Candidate status to Complete if all subjects done
            $activeSubjectIds = Subject::where('exam_type_id', $psleExamTypeId)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();

            $candidateSelections = \App\Models\CandidateSubjectSelection::where('candidate_id', $candidateId)
                ->where('exam_year_id', $examYearId)
                ->where('exam_type_id', $psleExamTypeId)
                ->where('is_active', true)
                ->get();

            if ($candidateSelections->isNotEmpty()) {
                $requiredIds = $candidateSelections->pluck('subject_id')->toArray();
            } else {
                $requiredIds = $activeSubjectIds;
            }

            $allMarks = RawMark::where('candidate_id', $candidateId)
                ->where('exam_year_id', $examYearId)
                ->get();

            $completedCount = 0;
            foreach ($requiredIds as $sid) {
                $mark = $allMarks->firstWhere('subject_id', $sid);
                if ($mark && ($mark->paper_1_marks !== null || $mark->subject_status === 'ABS')) {
                    $completedCount++;
                }
            }

            if ($completedCount === count($requiredIds)) {
                if (strtoupper($candidate->status) === 'INC') {
                    $candidate->status = 'Complete';
                    $candidate->save();
                }

                $registration = \App\Models\CandidateExamRegistration::where([
                    'candidate_id' => $candidateId,
                    'exam_year_id' => $examYearId,
                ])->first();

                if ($registration) {
                    if (strtoupper($registration->status) === 'INC') {
                        $registration->status = 'Complete';
                        $registration->save();
                    }
                    $candidateResult = $registration->result;
                    if ($candidateResult && strtoupper($candidateResult->result_status) === 'INC') {
                        $candidateResult->result_status = 'COMPLETE';
                        $candidateResult->save();
                    }
                }
            }

            return [
                'success' => true,
                'message' => 'Missing mark saved successfully.',
                'raw_mark_id' => $rawMark->id,
            ];
        });
    }

    /**
     * Get subject short code helper.
     */
    public static function getSubjectShortCode(string $subjectNameOrCode): string
    {
        $name = strtoupper(trim($subjectNameOrCode));
        if ($name === 'KISWAHILI' || $name === 'PSLE-01') return 'KISW';
        if ($name === 'ENGLISH LANGUAGE' || $name === 'ENGLISH' || $name === 'PSLE-02') return 'ENGL';
        if ($name === 'SOCIAL STUDIES AND VOCATIONAL SKILLS' || $name === 'MAARIFA' || $name === 'PSLE-03') return 'MAAR';
        if ($name === 'MATHEMATICS' || $name === 'HISABATI' || $name === 'PSLE-04') return 'HIS';
        if ($name === 'SCIENCE AND TECHNOLOGY' || $name === 'SCIENCE' || $name === 'PSLE-05') return 'SAY';
        if ($name === 'CIVIC AND MORAL EDUCATION' || $name === 'URAIA' || $name === 'PSLE-06') return 'URA';
        
        if (str_contains($name, 'KISW')) return 'KISW';
        if (str_contains($name, 'ENGL') || str_contains($name, 'ENG')) return 'ENGL';
        if (str_contains($name, 'MAAR') || str_contains($name, 'SOC') || str_contains($name, 'SST')) return 'MAAR';
        if (str_contains($name, 'HIS') || str_contains($name, 'MATH')) return 'HIS';
        if (str_contains($name, 'SCI') || str_contains($name, 'SAY')) return 'SAY';
        if (str_contains($name, 'URA') || str_contains($name, 'CIV') || str_contains($name, 'CME')) return 'URA';

        return substr($name, 0, 4);
    }
}
