<?php

namespace App\Services\Results;

use App\Models\Candidate;
use App\Models\CandidateResult;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\MarkImportBatch;
use App\Models\RawMark;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PsleResultsService
{
    private const GRADE_SCALE = [
        'A' => [81, 100],
        'B' => [61, 80],
        'C' => [41, 60],
        'D' => [21, 40],
        'E' => [0, 20],
    ];

    public function resolvePsleExamType(): ExamType
    {
        return ExamType::where('code', ExamType::CODE_PSLE)->firstOrFail();
    }

    public function computeGrade(float $mark): string
    {
        foreach (self::GRADE_SCALE as $grade => [$min, $max]) {
            if ($mark >= $min && $mark <= $max) {
                return $grade;
            }
        }

        return 'E';
    }

    public function summary(array $filters = []): array
    {
        $psle = $this->resolvePsleExamType();
        $batchQuery = $this->baseBatchQuery($psle, $filters);
        $resultQuery = $this->baseResultQuery($psle, $filters);

        $batches = (clone $batchQuery)->get();
        $lockedBatches = $batches->where('status', MarkImportBatch::STATUS_LOCKED);
        $approvedBatches = $batches->where('status', MarkImportBatch::STATUS_APPROVED);

        $totalCandidates = (clone $resultQuery)->count();
        $publishedCandidates = (clone $resultQuery)->where('is_published', true)->count();
        $lockedCandidates = (clone $resultQuery)->where('is_locked', true)->count();
        $verifiedCandidates = (clone $resultQuery)->where('is_verified', true)->count();

        $schoolsWithResults = (clone $resultQuery)
            ->join('candidates', 'candidate_results.candidate_id', '=', 'candidates.id')
            ->distinct('candidates.school_id')
            ->count('candidates.school_id');

        $gradeDistribution = [];
        foreach (array_keys(self::GRADE_SCALE) as $grade) {
            $gradeDistribution[$grade] = (clone $resultQuery)
                ->where('overall_grade', $grade)
                ->count();
        }

        return [
            'total_candidates' => $totalCandidates,
            'published_candidates' => $publishedCandidates,
            'locked_candidates' => $lockedCandidates,
            'verified_candidates' => $verifiedCandidates,
            'schools_with_results' => $schoolsWithResults,
            'total_batches' => $batches->count(),
            'locked_batches' => $lockedBatches->count(),
            'approved_batches' => $approvedBatches->count(),
            'submitted_batches' => $batches->where('status', MarkImportBatch::STATUS_SUBMITTED)->count(),
            'total_marks_rows' => (int) $batches->sum('total_records'),
            'grade_distribution' => $gradeDistribution,
            'batch_status_breakdown' => [
                'draft' => $batches->where('status', MarkImportBatch::STATUS_DRAFT)->count(),
                'validated' => $batches->where('status', MarkImportBatch::STATUS_VALIDATED)->count(),
                'submitted' => $batches->where('status', MarkImportBatch::STATUS_SUBMITTED)->count(),
                'approved' => $approvedBatches->count(),
                'rejected' => $batches->where('status', MarkImportBatch::STATUS_REJECTED)->count(),
                'locked' => $lockedBatches->count(),
                'processed' => $batches->where('status', MarkImportBatch::STATUS_PROCESSED)->count(),
            ],
        ];
    }

    public function validationRun(array $filters = []): array
    {
        $psle = $this->resolvePsleExamType();
        $errors = [];

        $lockedBatches = $this->baseBatchQuery($psle, $filters)
            ->where('status', MarkImportBatch::STATUS_LOCKED)
            ->with(['school:id,name,code', 'subject:id,code,name'])
            ->get();

        if ($lockedBatches->isEmpty()) {
            return [
                'status' => 'no_data',
                'message' => 'No locked PSLE batches found for the given scope.',
                'errors' => [],
                'summary' => ['total_checks' => 0, 'errors' => 0, 'warnings' => 0],
            ];
        }

        $subjects = Subject::where('exam_type_id', $psle->id)->pluck('id', 'code');
        $expectedSubjectCount = $subjects->count();

        $schoolIds = $lockedBatches->pluck('school_id')->filter()->unique();

        foreach ($schoolIds as $schoolId) {
            $school = School::find($schoolId);
            if (!$school) {
                continue;
            }

            $schoolBatches = $lockedBatches->where('school_id', $schoolId);
            $coveredSubjects = $schoolBatches->pluck('subject_id')->unique();

            if ($coveredSubjects->count() < $expectedSubjectCount) {
                $missing = $subjects->filter(fn ($id) => !$coveredSubjects->contains($id));
                foreach ($missing as $code => $id) {
                    $errors[] = [
                        'error_type' => 'missing_subject',
                        'severity' => 'warning',
                        'school_id' => $schoolId,
                        'school_name' => $school->name ?? '-',
                        'subject_code' => $code,
                        'error_message' => "School {$school->code} is missing locked marks for subject {$code}.",
                    ];
                }
            }

            $rawMarks = RawMark::whereIn('mark_import_batch_id', $schoolBatches->pluck('id'))
                ->where('has_errors', false)
                ->get();

            foreach ($rawMarks as $mark) {
                if ($mark->paper_1_marks === null && $mark->subject_status !== 'INC') {
                    $errors[] = [
                        'error_type' => 'null_mark',
                        'severity' => 'error',
                        'school_id' => $schoolId,
                        'school_name' => $school->name ?? '-',
                        'candidate_no' => $mark->candidate_index_number,
                        'subject_id' => $mark->subject_id,
                        'error_message' => "Candidate {$mark->candidate_index_number} has null mark without INC status.",
                    ];
                }

                if ($mark->paper_1_marks !== null && ((float) $mark->paper_1_marks < 0 || (float) $mark->paper_1_marks > 50)) {
                    $errors[] = [
                        'error_type' => 'out_of_range',
                        'severity' => 'error',
                        'school_id' => $schoolId,
                        'school_name' => $school->name ?? '-',
                        'candidate_no' => $mark->candidate_index_number,
                        'subject_id' => $mark->subject_id,
                        'error_message' => "Candidate {$mark->candidate_index_number} has mark {$mark->paper_1_marks} outside 0-50 range.",
                    ];
                }
            }
        }

        return [
            'status' => empty($errors) ? 'passed' : 'has_errors',
            'message' => empty($errors)
                ? 'All validation checks passed for locked PSLE marks.'
                : count($errors) . ' validation issue(s) found.',
            'errors' => array_slice($errors, 0, 200),
            'summary' => [
                'total_checks' => $lockedBatches->count(),
                'errors' => collect($errors)->where('severity', 'error')->count(),
                'warnings' => collect($errors)->where('severity', 'warning')->count(),
                'schools_checked' => $schoolIds->count(),
            ],
        ];
    }

    public function validationErrors(array $filters = []): array
    {
        $query = DB::table('psle_result_validation_errors')
            ->leftJoin('schools', 'psle_result_validation_errors.school_id', '=', 'schools.id')
            ->leftJoin('subjects', 'psle_result_validation_errors.subject_id', '=', 'subjects.id')
            ->select(
                'psle_result_validation_errors.*',
                'schools.name as school_name',
                'schools.code as school_code',
                'subjects.code as subject_code',
                'subjects.name as subject_name'
            );

        if (!empty($filters['exam_year'])) {
            $examYear = ExamYear::where('year_label', $filters['exam_year'])->first();
            if ($examYear) {
                $query->where('psle_result_validation_errors.exam_year_id', $examYear->id);
            }
        }

        if (!empty($filters['school_id'])) {
            $query->where('psle_result_validation_errors.school_id', $filters['school_id']);
        }

        $query->orderByDesc('psle_result_validation_errors.created_at');

        return $query->limit(200)->get()->toArray();
    }

    public function candidates(array $filters = [], int $perPage = 25): array
    {
        $psle = $this->resolvePsleExamType();

        $query = CandidateResult::query()
            ->where('exam_type_id', $psle->id)
            ->with(['candidate:id,candidate_id,full_name,gender,school_id,prem_no', 'candidate.school:id,name,code,district_id,region_id']);

        if (!empty($filters['exam_year'])) {
            $query->where('year', $filters['exam_year']);
        }

        if (!empty($filters['school_id'])) {
            $query->whereHas('candidate', fn ($q) => $q->where('school_id', $filters['school_id']));
        }

        if (!empty($filters['district_id'])) {
            $query->whereHas('candidate.school', fn ($q) => $q->where('district_id', $filters['district_id']));
        }

        if (!empty($filters['region_id'])) {
            $query->whereHas('candidate.school', fn ($q) => $q->where('region_id', $filters['region_id']));
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('candidate', fn ($q) => $q->where('full_name', 'like', "%{$search}%")
                ->orWhere('candidate_id', 'like', "%{$search}%"));
        }

        $paginated = $query->orderBy('total_marks', 'desc')->paginate($perPage);

        $items = collect($paginated->items())->map(function (CandidateResult $result) use ($psle) {
            $subjectMarks = $this->getCandidateSubjectMarks($result->candidate_id, $psle->id, $result->year);

            return [
                'id' => $result->id,
                'candidate_id' => $result->candidate?->candidate_id,
                'candidate_db_id' => $result->candidate_id,
                'full_name' => $result->candidate?->full_name,
                'gender' => $result->candidate?->gender,
                'prem_no' => $result->candidate?->prem_no,
                'school_name' => $result->candidate?->school?->name,
                'school_code' => $result->candidate?->school?->code,
                'total_marks' => $result->total_marks,
                'overall_grade' => $result->overall_grade,
                'grade_points' => $result->grade_points,
                'division' => $result->division,
                'result_status' => $result->result_status,
                'is_verified' => $result->is_verified,
                'is_published' => $result->is_published,
                'is_locked' => $result->is_locked,
                'subject_marks' => $subjectMarks,
            ];
        });

        return [
            'data' => $items->all(),
            'pagination' => [
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
            ],
        ];
    }

    public function candidateStatement(int $candidateDbId, array $filters = []): array
    {
        $psle = $this->resolvePsleExamType();

        $result = CandidateResult::where('candidate_id', $candidateDbId)
            ->where('exam_type_id', $psle->id)
            ->with(['candidate:id,candidate_id,full_name,gender,school_id,prem_no', 'candidate.school:id,name,code,district_id,region_id'])
            ->first();

        if (!$result) {
            return ['error' => 'Candidate result not found.'];
        }

        $subjectMarks = $this->getCandidateSubjectMarks($candidateDbId, $psle->id, $result->year);

        return [
            'id' => $result->id,
            'candidate_id' => $result->candidate?->candidate_id,
            'full_name' => $result->candidate?->full_name,
            'gender' => $result->candidate?->gender,
            'prem_no' => $result->candidate?->prem_no,
            'school_name' => $result->candidate?->school?->name,
            'school_code' => $result->candidate?->school?->code,
            'year' => $result->year,
            'total_marks' => $result->total_marks,
            'overall_grade' => $result->overall_grade,
            'grade_points' => $result->grade_points,
            'division' => $result->division,
            'result_status' => $result->result_status,
            'is_verified' => $result->is_verified,
            'is_published' => $result->is_published,
            'is_locked' => $result->is_locked,
            'subject_marks' => $subjectMarks,
        ];
    }

    public function schoolsSummary(array $filters = []): array
    {
        $psle = $this->resolvePsleExamType();

        $query = CandidateResult::query()
            ->where('exam_type_id', $psle->id)
            ->join('candidates', 'candidate_results.candidate_id', '=', 'candidates.id')
            ->join('schools', 'candidates.school_id', '=', 'schools.id');

        if (!empty($filters['exam_year'])) {
            $query->where('candidate_results.year', $filters['exam_year']);
        }
        if (!empty($filters['region_id'])) {
            $query->where('schools.region_id', $filters['region_id']);
        }
        if (!empty($filters['district_id'])) {
            $query->where('schools.district_id', $filters['district_id']);
        }

        $schools = $query->select(
            'schools.id as school_id',
            'schools.name as school_name',
            'schools.code as school_code',
            'schools.region_id',
            'schools.district_id',
            DB::raw('COUNT(candidate_results.id) as total_candidates'),
            DB::raw('AVG(candidate_results.total_marks) as avg_marks'),
            DB::raw("SUM(CASE WHEN candidate_results.overall_grade = 'A' THEN 1 ELSE 0 END) as grade_a"),
            DB::raw("SUM(CASE WHEN candidate_results.overall_grade = 'B' THEN 1 ELSE 0 END) as grade_b"),
            DB::raw("SUM(CASE WHEN candidate_results.overall_grade = 'C' THEN 1 ELSE 0 END) as grade_c"),
            DB::raw("SUM(CASE WHEN candidate_results.overall_grade = 'D' THEN 1 ELSE 0 END) as grade_d"),
            DB::raw("SUM(CASE WHEN candidate_results.overall_grade = 'E' THEN 1 ELSE 0 END) as grade_e"),
            DB::raw("SUM(CASE WHEN candidate_results.is_published = 1 THEN 1 ELSE 0 END) as published_count"),
            DB::raw("SUM(CASE WHEN candidate_results.is_locked = 1 THEN 1 ELSE 0 END) as locked_count"),
        )
            ->groupBy('schools.id', 'schools.name', 'schools.code', 'schools.region_id', 'schools.district_id')
            ->orderByDesc('avg_marks')
            ->limit(300)
            ->get();

        return $schools->map(fn ($row) => [
            'school_id' => $row->school_id,
            'school_name' => $row->school_name,
            'school_code' => $row->school_code,
            'total_candidates' => (int) $row->total_candidates,
            'avg_marks' => round((float) $row->avg_marks, 2),
            'grade_a' => (int) $row->grade_a,
            'grade_b' => (int) $row->grade_b,
            'grade_c' => (int) $row->grade_c,
            'grade_d' => (int) $row->grade_d,
            'grade_e' => (int) $row->grade_e,
            'published_count' => (int) $row->published_count,
            'locked_count' => (int) $row->locked_count,
        ])->all();
    }

    public function schoolDetail(int $schoolId, array $filters = []): array
    {
        $psle = $this->resolvePsleExamType();
        $school = School::findOrFail($schoolId);

        $query = CandidateResult::query()
            ->where('exam_type_id', $psle->id)
            ->whereHas('candidate', fn ($q) => $q->where('school_id', $schoolId))
            ->with(['candidate:id,candidate_id,full_name,gender,prem_no,school_id']);

        if (!empty($filters['exam_year'])) {
            $query->where('year', $filters['exam_year']);
        }

        $results = $query->orderBy('total_marks', 'desc')->limit(500)->get();

        $candidates = $results->map(function (CandidateResult $result) use ($psle) {
            $subjectMarks = $this->getCandidateSubjectMarks($result->candidate_id, $psle->id, $result->year);

            return [
                'candidate_id' => $result->candidate?->candidate_id,
                'full_name' => $result->candidate?->full_name,
                'gender' => $result->candidate?->gender,
                'prem_no' => $result->candidate?->prem_no,
                'total_marks' => $result->total_marks,
                'overall_grade' => $result->overall_grade,
                'is_published' => $result->is_published,
                'is_locked' => $result->is_locked,
                'subject_marks' => $subjectMarks,
            ];
        });

        return [
            'school' => [
                'id' => $school->id,
                'name' => $school->name,
                'code' => $school->code,
            ],
            'total_candidates' => $candidates->count(),
            'candidates' => $candidates->all(),
        ];
    }

    public function approvals(array $filters = []): array
    {
        $query = DB::table('psle_result_approvals')
            ->leftJoin('schools', 'psle_result_approvals.school_id', '=', 'schools.id')
            ->leftJoin('regions', 'psle_result_approvals.region_id', '=', 'regions.id')
            ->leftJoin('users', 'psle_result_approvals.acted_by', '=', 'users.id')
            ->select(
                'psle_result_approvals.*',
                'schools.name as school_name',
                'schools.code as school_code',
                'regions.name as region_name',
                'users.name as acted_by_name'
            );

        if (!empty($filters['exam_year'])) {
            $examYear = ExamYear::where('year_label', $filters['exam_year'])->first();
            if ($examYear) {
                $query->where('psle_result_approvals.exam_year_id', $examYear->id);
            }
        }

        return $query->orderByDesc('psle_result_approvals.acted_at')->limit(200)->get()->toArray();
    }

    public function createApproval(array $data, int $userId): array
    {
        $examYear = !empty($data['exam_year']) ? ExamYear::where('year_label', $data['exam_year'])->first() : null;

        $id = DB::table('psle_result_approvals')->insertGetId([
            'exam_year_id' => $examYear?->id,
            'school_id' => $data['school_id'] ?? null,
            'council_id' => $data['council_id'] ?? null,
            'region_id' => $data['region_id'] ?? null,
            'stage' => $data['stage'] ?? 'school',
            'action' => $data['action'] ?? 'approve',
            'readiness_score' => $data['readiness_score'] ?? null,
            'comments' => $data['comments'] ?? null,
            'acted_by' => $userId,
            'acted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['success' => true, 'id' => $id, 'message' => 'Approval recorded successfully.'];
    }

    public function publications(array $filters = []): array
    {
        $query = DB::table('psle_result_publications')
            ->leftJoin('schools', 'psle_result_publications.school_id', '=', 'schools.id')
            ->leftJoin('regions', 'psle_result_publications.region_id', '=', 'regions.id')
            ->leftJoin('users', 'psle_result_publications.published_by', '=', 'users.id')
            ->select(
                'psle_result_publications.*',
                'schools.name as school_name',
                'regions.name as region_name',
                'users.name as published_by_name'
            );

        if (!empty($filters['exam_year'])) {
            $examYear = ExamYear::where('year_label', $filters['exam_year'])->first();
            if ($examYear) {
                $query->where('psle_result_publications.exam_year_id', $examYear->id);
            }
        }

        return $query->orderByDesc('psle_result_publications.published_at')->limit(100)->get()->toArray();
    }

    public function createPublication(array $data, int $userId): array
    {
        $examYear = !empty($data['exam_year']) ? ExamYear::where('year_label', $data['exam_year'])->first() : null;

        $id = DB::table('psle_result_publications')->insertGetId([
            'exam_year_id' => $examYear?->id,
            'region_id' => $data['region_id'] ?? null,
            'council_id' => $data['council_id'] ?? null,
            'school_id' => $data['school_id'] ?? null,
            'publication_scope' => $data['publication_scope'] ?? 'national',
            'status' => 'published',
            'version_no' => $data['version_no'] ?? ('v' . now()->format('Ymd_His')),
            'notes' => $data['notes'] ?? null,
            'published_by' => $userId,
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['success' => true, 'id' => $id, 'message' => 'Publication created successfully.'];
    }

    public function amendments(array $filters = []): array
    {
        $query = DB::table('psle_result_amendments')
            ->leftJoin('candidates', 'psle_result_amendments.candidate_id', '=', 'candidates.id')
            ->leftJoin('users as req_user', 'psle_result_amendments.requested_by', '=', 'req_user.id')
            ->leftJoin('users as app_user', 'psle_result_amendments.approved_by', '=', 'app_user.id')
            ->select(
                'psle_result_amendments.*',
                'candidates.candidate_id as candidate_no',
                'candidates.full_name as candidate_name',
                'req_user.name as requested_by_name',
                'app_user.name as approved_by_name'
            );

        if (!empty($filters['exam_year'])) {
            $examYear = ExamYear::where('year_label', $filters['exam_year'])->first();
            if ($examYear) {
                $query->where('psle_result_amendments.exam_year_id', $examYear->id);
            }
        }

        return $query->orderByDesc('psle_result_amendments.created_at')->limit(200)->get()->toArray();
    }

    public function createAmendment(array $data, int $userId): array
    {
        $examYear = !empty($data['exam_year']) ? ExamYear::where('year_label', $data['exam_year'])->first() : null;

        $id = DB::table('psle_result_amendments')->insertGetId([
            'psle_result_id' => $data['psle_result_id'],
            'candidate_id' => $data['candidate_id'] ?? null,
            'exam_year_id' => $examYear?->id,
            'amendment_type' => $data['amendment_type'] ?? 'mark_correction',
            'old_value' => json_encode($data['old_value'] ?? []),
            'new_value' => json_encode($data['new_value'] ?? []),
            'reason' => $data['reason'],
            'status' => 'pending',
            'requested_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['success' => true, 'id' => $id, 'message' => 'Amendment request created.'];
    }

    public function statistics(array $filters = []): array
    {
        $psle = $this->resolvePsleExamType();

        $query = CandidateResult::query()
            ->where('exam_type_id', $psle->id)
            ->join('candidates', 'candidate_results.candidate_id', '=', 'candidates.id')
            ->join('schools', 'candidates.school_id', '=', 'schools.id');

        if (!empty($filters['exam_year'])) {
            $query->where('candidate_results.year', $filters['exam_year']);
        }
        if (!empty($filters['region_id'])) {
            $query->where('schools.region_id', $filters['region_id']);
        }
        if (!empty($filters['district_id'])) {
            $query->where('schools.district_id', $filters['district_id']);
        }

        $genderBreakdown = (clone $query)
            ->select(
                'candidates.gender',
                DB::raw('COUNT(*) as total'),
                DB::raw('AVG(candidate_results.total_marks) as avg_marks'),
                DB::raw("SUM(CASE WHEN candidate_results.overall_grade = 'A' THEN 1 ELSE 0 END) as grade_a"),
                DB::raw("SUM(CASE WHEN candidate_results.overall_grade = 'B' THEN 1 ELSE 0 END) as grade_b"),
                DB::raw("SUM(CASE WHEN candidate_results.overall_grade = 'C' THEN 1 ELSE 0 END) as grade_c"),
                DB::raw("SUM(CASE WHEN candidate_results.overall_grade = 'D' THEN 1 ELSE 0 END) as grade_d"),
                DB::raw("SUM(CASE WHEN candidate_results.overall_grade = 'E' THEN 1 ELSE 0 END) as grade_e"),
            )
            ->groupBy('candidates.gender')
            ->get()
            ->map(fn ($row) => [
                'gender' => $row->gender,
                'total' => (int) $row->total,
                'avg_marks' => round((float) $row->avg_marks, 2),
                'grade_a' => (int) $row->grade_a,
                'grade_b' => (int) $row->grade_b,
                'grade_c' => (int) $row->grade_c,
                'grade_d' => (int) $row->grade_d,
                'grade_e' => (int) $row->grade_e,
            ]);

        $regionBreakdown = (clone $query)
            ->leftJoin('regions', 'schools.region_id', '=', 'regions.id')
            ->select(
                'regions.id as region_id',
                'regions.name as region_name',
                DB::raw('COUNT(candidate_results.id) as total'),
                DB::raw('AVG(candidate_results.total_marks) as avg_marks'),
            )
            ->groupBy('regions.id', 'regions.name')
            ->orderByDesc('avg_marks')
            ->get()
            ->map(fn ($row) => [
                'region_id' => $row->region_id,
                'region_name' => $row->region_name ?? 'Unknown',
                'total' => (int) $row->total,
                'avg_marks' => round((float) $row->avg_marks, 2),
            ]);

        $subjectBreakdown = $this->getSubjectStatistics($psle, $filters);

        return [
            'gender' => $genderBreakdown->all(),
            'regions' => $regionBreakdown->all(),
            'subjects' => $subjectBreakdown,
        ];
    }

    private function getSubjectStatistics(ExamType $psle, array $filters = []): array
    {
        $subjects = Subject::where('exam_type_id', $psle->id)->get();
        $result = [];

        foreach ($subjects as $subject) {
            $markQuery = RawMark::query()
                ->join('mark_import_batches', 'raw_marks.mark_import_batch_id', '=', 'mark_import_batches.id')
                ->where('mark_import_batches.exam_type_id', $psle->id)
                ->where('raw_marks.subject_id', $subject->id)
                ->where('raw_marks.has_errors', false)
                ->whereNotNull('raw_marks.paper_1_marks')
                ->where('mark_import_batches.status', MarkImportBatch::STATUS_LOCKED);

            if (!empty($filters['exam_year'])) {
                $markQuery->where('mark_import_batches.exam_year', $filters['exam_year']);
            }

            $stats = (clone $markQuery)->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('AVG(raw_marks.paper_1_marks) as avg_mark'),
                DB::raw('MAX(raw_marks.paper_1_marks) as max_mark'),
                DB::raw('MIN(raw_marks.paper_1_marks) as min_mark'),
            )->first();

            $result[] = [
                'subject_code' => $subject->code,
                'subject_name' => $subject->name,
                'total_marks' => (int) ($stats->total ?? 0),
                'avg_mark' => round((float) ($stats->avg_mark ?? 0), 2),
                'max_mark' => (float) ($stats->max_mark ?? 0),
                'min_mark' => (float) ($stats->min_mark ?? 0),
            ];
        }

        return $result;
    }

    private function getCandidateSubjectMarks(int $candidateDbId, int $psleExamTypeId, ?string $year): array
    {
        $query = RawMark::query()
            ->join('mark_import_batches', 'raw_marks.mark_import_batch_id', '=', 'mark_import_batches.id')
            ->join('subjects', 'raw_marks.subject_id', '=', 'subjects.id')
            ->where('mark_import_batches.exam_type_id', $psleExamTypeId)
            ->where('raw_marks.candidate_id', $candidateDbId)
            ->where('raw_marks.has_errors', false)
            ->whereIn('mark_import_batches.status', [
                MarkImportBatch::STATUS_LOCKED,
                MarkImportBatch::STATUS_APPROVED,
                MarkImportBatch::STATUS_PROCESSED,
            ]);

        if ($year) {
            $query->where('mark_import_batches.exam_year', $year);
        }

        return $query->select(
            'subjects.code as subject_code',
            'subjects.name as subject_name',
            'raw_marks.paper_1_marks as mark',
            'raw_marks.subject_status'
        )
            ->orderBy('subjects.code')
            ->get()
            ->map(fn ($row) => [
                'subject_code' => $row->subject_code,
                'subject_name' => $row->subject_name,
                'mark' => $row->mark !== null ? (float) $row->mark : null,
                'grade' => $row->mark !== null ? $this->computeGrade((float) $row->mark) : ($row->subject_status ?? 'INC'),
                'status' => $row->subject_status,
            ])
            ->all();
    }

    private function baseBatchQuery(ExamType $psle, array $filters = [])
    {
        $query = MarkImportBatch::query()->where('exam_type_id', $psle->id);

        if (!empty($filters['exam_year'])) {
            $query->where('exam_year', $filters['exam_year']);
        }
        if (!empty($filters['region_id'])) {
            $query->where('region_id', $filters['region_id']);
        }
        if (!empty($filters['district_id'])) {
            $query->where('district_id', $filters['district_id']);
        }
        if (!empty($filters['school_id'])) {
            $query->where('school_id', $filters['school_id']);
        }
        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        return $query;
    }

    private function baseResultQuery(ExamType $psle, array $filters = [])
    {
        $query = CandidateResult::query()->where('exam_type_id', $psle->id);

        if (!empty($filters['exam_year'])) {
            $query->where('year', $filters['exam_year']);
        }
        if (!empty($filters['school_id'])) {
            $query->whereHas('candidate', fn ($q) => $q->where('school_id', $filters['school_id']));
        }
        if (!empty($filters['district_id'])) {
            $query->whereHas('candidate.school', fn ($q) => $q->where('district_id', $filters['district_id']));
        }
        if (!empty($filters['region_id'])) {
            $query->whereHas('candidate.school', fn ($q) => $q->where('region_id', $filters['region_id']));
        }

        return $query;
    }
}
