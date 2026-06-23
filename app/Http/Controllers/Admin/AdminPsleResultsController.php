<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\District;
use App\Models\School;
use App\Models\Candidate;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPsleResultsController extends Controller
{
    private const EXAM_TYPE_CODE = 'PSLE';

    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function legacy(Request $request)
    {
        $yearLabel = 2026;
        if ($request->filled('exam_year_id')) {
            $year = ExamYear::find($request->input('exam_year_id'));
            if ($year) {
                $yearLabel = (int) $year->year_label;
            }
        }
        
        return redirect()->route('results.psle.dashboard', array_merge(
            ['year' => $yearLabel],
            $request->query()
        ));
    }

    public function index(Request $request, ?int $year = null)
    {
        $view = strtolower((string) $request->input('view', 'processing'));
        $allowedViews = [
            'overview', 'processing', 'summary', 'candidate-results', 'candidates',
            'school-results', 'schools', 'school', 'district-results', 'districts',
            'regional-results', 'subject-performance', 'reports', 'audit'
        ];
        
        if (!in_array($view, $allowedViews, true)) {
            $view = 'processing';
        }

        // Active exam year context
        $activeYear = null;
        if ($year) {
            $activeYear = ExamYear::where('year_label', $year)->first();
        }
        if (!$activeYear) {
            $activeYear = ExamYear::where('is_active', true)->first() 
                ?: ExamYear::orderByDesc('year_label')->first();
        }
        
        $examYearId = (int) $request->input('exam_year_id', $activeYear->id ?? 0);
        $examYear = ExamYear::find($examYearId) ?: $activeYear;
        $yearLabel = (int) ($examYear->year_label ?? 2026);

        // Fetch TASIDO regions
        $tasidoRegions = Region::whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])
            ->orderBy('name')
            ->get();
        $tasidoRegionIds = $tasidoRegions->pluck('id')->toArray();

        // Filters
        $regionId = $request->filled('region_id') ? (int) $request->input('region_id') : null;
        $districtId = $request->filled('district_id') ? (int) $request->input('district_id') : null;
        $schoolId = $request->filled('school_id') ? (int) $request->input('school_id') : null;

        // Load dropdowns based on selections
        $districts = collect();
        if ($regionId) {
            $districts = District::where('region_id', $regionId)->orderBy('name')->get();
        } elseif (!empty($tasidoRegionIds)) {
            $districts = District::whereIn('region_id', $tasidoRegionIds)->orderBy('name')->get();
        }

        $schools = collect();
        if ($districtId) {
            $schools = School::where('district_id', $districtId)->where('education_level', 'PRIMARY')->orderBy('name')->get();
        } elseif ($regionId) {
            $schools = School::where('region_id', $regionId)->where('education_level', 'PRIMARY')->orderBy('name')->get();
        } elseif (!empty($tasidoRegionIds)) {
            $schools = School::whereIn('region_id', $tasidoRegionIds)->where('education_level', 'PRIMARY')->orderBy('name')->get();
        }

        // Log access in Audit log
        $this->logAuditAction("psle_results_view_{$view}", "Accessed PSLE Results Portal view: {$view} for year {$yearLabel}");

        // Base metrics for overview
        $metrics = $this->fetchOverviewMetrics($yearLabel, $tasidoRegionIds);

        // Fetch view-specific data
        $viewData = [];
        try {
            switch ($view) {
                case 'overview':
                case 'summary':
                    $viewData = $this->getOverviewData($yearLabel, $tasidoRegionIds);
                    break;
                case 'processing':
                    $viewData = $this->getProcessingData($yearLabel, $tasidoRegionIds);
                    break;
                case 'candidate-results':
                case 'candidates':
                    $viewData = $this->getCandidateResultsData($request, $yearLabel, $tasidoRegionIds, $regionId, $districtId, $schoolId);
                    break;
                case 'school-results':
                case 'schools':
                case 'school':
                    $viewData = $this->getSchoolResultsData($request, $yearLabel, $tasidoRegionIds, $regionId, $districtId);
                    break;
                case 'district-results':
                case 'districts':
                    $viewData = $this->getDistrictResultsData($yearLabel, $tasidoRegionIds, $regionId);
                    break;
                case 'regional-results':
                    $viewData = $this->getRegionalResultsData($yearLabel, $tasidoRegionIds);
                    break;
                case 'subject-performance':
                    $viewData = $this->getSubjectPerformanceData($yearLabel, $tasidoRegionIds, $regionId, $districtId);
                    break;
                case 'reports':
                    $viewData = $this->getReportsData($request, $yearLabel, $tasidoRegionIds, $regionId, $districtId);
                    break;
                case 'audit':
                    $viewData = $this->getAuditLogsData($request, $examYear->id ?? 0);
                    break;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('PSLE Results Portal Error', [
                'url' => $request->fullUrl(),
                'view' => $view,
                'year' => $yearLabel,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Safe fallback to prevent public-facing 500 error
            if ($view === 'processing') {
                $viewData = [
                    'readiness' => (object)['total' => 0, 'complete' => 0],
                    'validationErrors' => collect(),
                    'lastRuns' => collect(),
                ];
            } else {
                $viewData = [];
            }
            session()->now('warning', 'An error occurred while loading this view: ' . $e->getMessage());
        }

        $examYears = ExamYear::orderByDesc('year_label')->get();

        return view('results.psle.index', compact(
            'view', 'metrics', 'viewData', 'tasidoRegions', 
            'districts', 'schools', 'examYears', 'examYear', 
            'regionId', 'districtId', 'schoolId'
        ));
    }

    // --- Dynamic Calculations Directly From Raw subject_marks ---

    private function fetchOverviewMetrics(int $year, array $regionIds): array
    {
        if (empty($regionIds)) {
            return ['regions' => 0, 'schools' => 0, 'registered' => 0, 'complete' => 0, 'missing' => 0, 'processed' => 0, 'published' => 'Yes'];
        }

        $schoolsQuery = School::whereIn('region_id', $regionIds)->where('education_level', 'PRIMARY');
        $schoolsCount = $schoolsQuery->count();
        $schoolIds = $schoolsQuery->pluck('id')->toArray();

        $registeredCount = Candidate::whereIn('school_id', $schoolIds)->where('exam_type', self::EXAM_TYPE_CODE)->count();

        // Candidates with all 6 subject marks entered
        $completeCount = DB::table('subject_marks as sm')
            ->join('candidates as c', 'c.id', '=', 'sm.candidate_id')
            ->whereIn('c.school_id', $schoolIds)
            ->where('sm.year', $year)
            ->where('c.exam_type', self::EXAM_TYPE_CODE)
            ->groupBy('c.id')
            ->having(DB::raw('count(distinct sm.subject_id)'), '>=', 6)
            ->select('c.id')
            ->get()
            ->count();

        $missingCount = max(0, $registeredCount - $completeCount);

        return [
            'regions' => count($regionIds),
            'schools' => $schoolsCount,
            'registered' => $registeredCount,
            'complete' => $completeCount,
            'missing' => $missingCount,
            'processed' => $completeCount,
            'published' => 'Active',
            'available_reports' => $schoolsCount
        ];
    }

    private function getOverviewData(int $year, array $regionIds): array
    {
        // 1. Regional Table Summary
        $regionalSummary = DB::table('regions as r')
            ->whereIn('r.id', $regionIds)
            ->leftJoin('schools as s', function ($join) {
                $join->on('s.region_id', '=', 'r.id')->where('s.education_level', 'PRIMARY');
            })
            ->leftJoin('candidates as c', function ($join) {
                $join->on('c.school_id', '=', 's.id')->where('c.exam_type', self::EXAM_TYPE_CODE);
            })
            ->selectRaw('r.id, r.name, count(distinct s.id) as schools_count, count(distinct c.id) as candidates_count')
            ->groupBy('r.id', 'r.name')
            ->orderBy('r.name')
            ->get();

        // 2. Subject Marks completeness breakdown
        $subjectCompleteness = DB::table('subjects as sub')
            ->join('subject_marks as sm', 'sm.subject_id', '=', 'sub.id')
            ->join('candidates as c', 'c.id', '=', 'sm.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->whereIn('s.region_id', $regionIds)
            ->where('sm.year', $year)
            ->where('c.exam_type', self::EXAM_TYPE_CODE)
            ->selectRaw('sub.code, sub.name, count(distinct sm.candidate_id) as marks_count')
            ->groupBy('sub.code', 'sub.name')
            ->orderBy('sub.name')
            ->get();

        return compact('regionalSummary', 'subjectCompleteness');
    }

    private function getProcessingData(int $year, array $regionIds): array
    {
        // Mock processing audit lists & status
        $readiness = DB::table('candidates as c')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->whereIn('s.region_id', $regionIds)
            ->where('c.exam_type', self::EXAM_TYPE_CODE)
            ->selectRaw('count(distinct c.id) as total, sum(case when (select count(*) from subject_marks where candidate_id = c.id and year = ?) >= 6 then 1 else 0 end) as complete', [$year])
            ->first();

        $validationErrors = DB::table('raw_marks as rm')
            ->join('mark_import_batches as mib', 'rm.mark_import_batch_id', '=', 'mib.id')
            ->join('schools as s', 's.id', '=', 'mib.school_id')
            ->join('subjects as sub', 'sub.id', '=', 'rm.subject_id')
            ->whereIn('s.region_id', $regionIds)
            ->where('mib.exam_year', $year)
            ->where(function ($query) {
                $query->where('rm.paper_1_marks', '>', 50)
                      ->orWhere('rm.paper_1_marks', '<', 0);
            })
            ->selectRaw('rm.candidate_index_number as candidate_cno, sub.code as subject_code, rm.paper_1_marks as mark, s.name as school_name')
            ->limit(20)
            ->get();

        $examYearId = DB::table('exam_years')->where('year_label', $year)->value('id') ?? 1;
        $examTypeId = DB::table('exam_types')->where('code', self::EXAM_TYPE_CODE)->value('id') ?? 1;

        $lastRuns = DB::table('result_processes')
            ->where('exam_year_id', $examYearId)
            ->where('exam_type_id', $examTypeId)
            ->orderByDesc('processed_at')
            ->limit(10)
            ->get();

        $correctionBatches = \App\Models\SchoolResultCorrectionBatch::where('exam_year', $year)
            ->with(['school:id,name,code', 'openedByUser:id,name', 'correctedByUser:id,name', 'recalculatedByUser:id,name', 'republishedByUser:id,name', 'cancelledByUser:id,name'])
            ->orderByDesc('created_at')
            ->get();

        return compact('readiness', 'validationErrors', 'lastRuns', 'correctionBatches');
    }

    private function getCandidateResultsData(Request $request, int $year, array $regionIds, $regionId, $districtId, $schoolId): array
    {
        $query = DB::table('candidates as c')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->join('districts as d', 'd.id', '=', 's.district_id')
            ->join('regions as r', 'r.id', '=', 's.region_id')
            ->whereIn('s.region_id', $regionIds)
            ->where('c.exam_type', self::EXAM_TYPE_CODE);

        // Apply filters
        if ($regionId) $query->where('s.region_id', $regionId);
        if ($districtId) $query->where('s.district_id', $districtId);
        if ($schoolId) $query->where('s.id', $schoolId);

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('c.candidate_id', 'like', $search)
                  ->orWhere('c.full_name', 'like', $search)
                  ->orWhere('c.prem_no', 'like', $search);
            });
        }

        $candidates = $query->select([
            'c.id as candidate_pk', 'c.candidate_id as cno', 'c.full_name', 'c.gender',
            's.name as school_name', 'd.name as district_name', 'r.name as region_name'
        ])
        ->orderBy('c.candidate_id')
        ->paginate(20);

        // Load subject marks for current page
        $candidatePks = collect($candidates->items())->pluck('candidate_pk')->toArray();
        $subjectMarks = DB::table('subject_marks as sm')
            ->join('subjects as sub', 'sub.id', '=', 'sm.subject_id')
            ->whereIn('sm.candidate_id', $candidatePks)
            ->where('sm.year', $year)
            ->select('sm.candidate_id', 'sub.name as subject_name', 'sm.marks_obtained')
            ->get()
            ->groupBy('candidate_id');

        // Map marks to candidate items
        foreach ($candidates->items() as $c) {
            $marks = $subjectMarks->get($c->candidate_pk, collect());
            $c->kiswahili = $marks->firstWhere('subject_name', 'KISWAHILI')->marks_obtained ?? null;
            $c->english = $marks->firstWhere('subject_name', 'ENGLISH LANGUAGE')->marks_obtained ?? null;
            $c->mathematics = $marks->firstWhere('subject_name', 'MATHEMATICS')->marks_obtained ?? null;
            $c->science = $marks->firstWhere('subject_name', 'SCIENCE AND TECHNOLOGY')->marks_obtained ?? null;
            $c->civic = $marks->firstWhere('subject_name', 'CIVIC AND MORAL EDUCATION')->marks_obtained ?? null;
            $c->social = $marks->firstWhere('subject_name', 'SOCIAL STUDIES AND VOCATIONAL SKILLS')->marks_obtained ?? null;

            // Math computations
            $allMarks = array_filter([$c->kiswahili, $c->english, $c->mathematics, $c->science, $c->civic, $c->social], fn($v) => !is_null($v));
            $c->total = array_sum($allMarks);
            $c->average = count($allMarks) > 0 ? round($c->total / count($allMarks), 2) : 0;
            
            // Grade using correct 0-50 scaling
            $c->grade = $this->gradeFromRaw50($c->average);
        }

        return compact('candidates');
    }

    private function getSchoolResultsData(Request $request, int $year, array $regionIds, $regionId, $districtId): array
    {
        $query = DB::table('schools as s')
            ->join('districts as d', 'd.id', '=', 's.district_id')
            ->join('regions as r', 'r.id', '=', 's.region_id')
            ->whereIn('s.region_id', $regionIds)
            ->where('s.education_level', 'PRIMARY');

        if ($regionId) $query->where('s.region_id', $regionId);
        if ($districtId) $query->where('s.district_id', $districtId);

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where('s.name', 'like', $search)->orWhere('s.code', 'like', $search);
        }

        $schools = $query->select(['s.id', 's.code', 's.name', 'd.name as district_name', 'r.name as region_name'])
            ->orderBy('s.name')
            ->paginate(15);

        // Gather metrics for school items
        foreach ($schools->items() as $school) {
            $school->registered = Candidate::where('school_id', $school->id)->where('exam_type', self::EXAM_TYPE_CODE)->count();
            
            // Average calculation
            $scores = DB::table('subject_marks as sm')
                ->join('candidates as c', 'c.id', '=', 'sm.candidate_id')
                ->where('c.school_id', $school->id)
                ->where('sm.year', $year)
                ->where('c.exam_type', self::EXAM_TYPE_CODE)
                ->pluck('sm.marks_obtained')
                ->toArray();

            $school->complete = DB::table('subject_marks as sm')
                ->join('candidates as c', 'c.id', '=', 'sm.candidate_id')
                ->where('c.school_id', $school->id)
                ->where('sm.year', $year)
                ->groupBy('c.id')
                ->having(DB::raw('count(*)'), '>=', 6)
                ->get()
                ->count();

            $school->average = count($scores) > 0 ? round(array_sum($scores) / (count($scores) / 6), 2) : 0;
            $school->status = $school->complete >= $school->registered && $school->registered > 0 ? 'Complete' : 'Incomplete';
        }

        return compact('schools');
    }

    private function getDistrictResultsData(int $year, array $regionIds, $regionId): array
    {
        $query = DB::table('districts as d')
            ->join('regions as r', 'r.id', '=', 'd.region_id')
            ->whereIn('d.region_id', $regionIds);

        if ($regionId) $query->where('d.region_id', $regionId);

        $districts = $query->select(['d.id', 'd.name', 'r.name as region_name'])->orderBy('d.name')->get();

        foreach ($districts as $d) {
            $schoolIds = School::where('district_id', $d->id)->where('education_level', 'PRIMARY')->pluck('id')->toArray();
            $d->schools_count = count($schoolIds);
            $d->registered = Candidate::whereIn('school_id', $schoolIds)->where('exam_type', self::EXAM_TYPE_CODE)->count();
            
            $scores = DB::table('subject_marks as sm')
                ->join('candidates as c', 'c.id', '=', 'sm.candidate_id')
                ->whereIn('c.school_id', $schoolIds)
                ->where('sm.year', $year)
                ->pluck('sm.marks_obtained')
                ->toArray();
                
            $d->average = count($scores) > 0 ? round(array_sum($scores) / (count($scores) / 6), 2) : 0;
        }

        $districts = collect($districts)->sortByDesc('average')->values();
        foreach ($districts as $index => $d) {
            $d->rank = $index + 1;
        }

        return compact('districts');
    }

    private function getRegionalResultsData(int $year, array $regionIds): array
    {
        $regions = Region::whereIn('id', $regionIds)->orderBy('name')->get();

        foreach ($regions as $r) {
            $schoolIds = School::where('region_id', $r->id)->where('education_level', 'PRIMARY')->pluck('id')->toArray();
            $r->districts_count = District::where('region_id', $r->id)->count();
            $r->schools_count = count($schoolIds);
            $r->registered = Candidate::whereIn('school_id', $schoolIds)->where('exam_type', self::EXAM_TYPE_CODE)->count();
            
            $scores = DB::table('subject_marks as sm')
                ->join('candidates as c', 'c.id', '=', 'sm.candidate_id')
                ->whereIn('c.school_id', $schoolIds)
                ->where('sm.year', $year)
                ->pluck('sm.marks_obtained')
                ->toArray();
                
            $r->average = count($scores) > 0 ? round(array_sum($scores) / (count($scores) / 6), 2) : 0;
        }

        $regions = collect($regions)->sortByDesc('average')->values();
        foreach ($regions as $index => $r) {
            $r->rank = $index + 1;
        }

        return compact('regions');
    }

    private function getSubjectPerformanceData(int $year, array $regionIds, $regionId, $districtId): array
    {
        $subjects = DB::table('subjects as s')
            ->join('exam_types as et', 'et.id', '=', 's.exam_type_id')
            ->where('et.code', self::EXAM_TYPE_CODE)
            ->select('s.*')
            ->get();
        $schoolQuery = School::whereIn('region_id', $regionIds)->where('education_level', 'PRIMARY');
        
        if ($regionId) $schoolQuery->where('region_id', $regionId);
        if ($districtId) $schoolQuery->where('district_id', $districtId);
        
        $schoolIds = $schoolQuery->pluck('id')->toArray();

        $performance = [];
        foreach ($subjects as $sub) {
            $marks = DB::table('subject_marks as sm')
                ->join('candidates as c', 'c.id', '=', 'sm.candidate_id')
                ->whereIn('c.school_id', $schoolIds)
                ->where('sm.subject_id', $sub->id)
                ->where('sm.year', $year)
                ->pluck('sm.marks_obtained')
                ->toArray();

            $total = count($marks);
            $grades = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0];
            foreach ($marks as $m) {
                $grades[$this->gradeFromRaw50($m)]++;
            }

            $performance[] = (object) [
                'name' => $sub->name,
                'candidates' => $total,
                'highest' => $total > 0 ? max($marks) : 0,
                'lowest' => $total > 0 ? min($marks) : 0,
                'average' => $total > 0 ? round(array_sum($marks) / $total, 2) : 0,
                'a' => $grades['A'],
                'b' => $grades['B'],
                'c' => $grades['C'],
                'd' => $grades['D'],
                'e' => $grades['E']
            ];
        }

        return compact('performance');
    }

    private function getReportsData(Request $request, int $year, array $regionIds, ?int $regionId, ?int $districtId): array
    {
        // 1. Fetch available districts for filtering
        $filterDistricts = District::whereIn('region_id', $regionIds)->orderBy('name')->get();

        // 2. Query primary schools
        $schoolQuery = School::whereIn('region_id', $regionIds)
            ->where('education_level', 'PRIMARY')
            ->with(['region', 'district']);

        if ($regionId) {
            $schoolQuery->where('region_id', $regionId);
        }
        if ($districtId) {
            $schoolQuery->where('district_id', $districtId);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $schoolQuery->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $schools = $schoolQuery->orderBy('name')->paginate(15)->appends($request->query());

        // 3. For each school in the current page, fetch stats
        $psleExamTypeId = (int) ExamType::query()->where('code', self::EXAM_TYPE_CODE)->value('id');
        $schoolIds = collect($schools->items())->pluck('id')->toArray();

        $registeredCounts = [];
        $completeCounts = [];

        if (!empty($schoolIds)) {
            // Count registered candidates per school
            $registeredCounts = DB::table('candidates')
                ->whereIn('school_id', $schoolIds)
                ->where('exam_type', self::EXAM_TYPE_CODE)
                ->groupBy('school_id')
                ->selectRaw('school_id, count(*) as count')
                ->pluck('count', 'school_id')
                ->toArray();

            // Count complete results per school (from candidate_results table)
            $completeCounts = DB::table('candidate_results as cr')
                ->join('candidates as c', 'c.id', '=', 'cr.candidate_id')
                ->whereIn('c.school_id', $schoolIds)
                ->where('cr.exam_type_id', $psleExamTypeId)
                ->where('cr.year', $year)
                ->groupBy('c.school_id')
                ->selectRaw('c.school_id, count(*) as count')
                ->pluck('count', 'school_id')
                ->toArray();
        }

        $schoolStats = [];
        foreach ($schools->items() as $school) {
            $registered = $registeredCounts[$school->id] ?? 0;
            $complete = $completeCounts[$school->id] ?? 0;
            $missing = max(0, $registered - $complete);

            $status = 'No Marks';
            if ($registered > 0) {
                if ($complete === $registered) {
                    $status = 'Ready';
                } elseif ($complete > 0) {
                    $status = 'In Progress';
                }
            }

            $schoolStats[$school->id] = [
                'registered' => $registered,
                'complete' => $complete,
                'missing' => $missing,
                'status' => $status
            ];
        }

        return [
            'districts' => $filterDistricts,
            'schools' => $schools,
            'schoolStats' => $schoolStats
        ];
    }

    private function getAuditLogsData(Request $request, int $examYearId): array
    {
        $query = AuditLog::where('module', 'results')
            ->where('exam_year_id', $examYearId)
            ->with('user');

        if ($request->filled('action_filter')) {
            $query->where('action', $request->input('action_filter'));
        }

        $logs = $query->latest()->paginate(20);
        return compact('logs');
    }

    // --- Administrative Actions ---

    public function validateData(Request $request)
    {
        $activeYear = ExamYear::where('is_active', true)->first();
        $examYearId = (int) $request->input('exam_year_id', $activeYear->id ?? 0);
        $examYear = ExamYear::find($examYearId) ?: $activeYear;

        if (!$examYear) {
            return response()->json(['success' => false, 'message' => 'No active exam year found.'], 400);
        }

        $psleType = ExamType::where('code', self::EXAM_TYPE_CODE)->first();
        if (!$psleType) {
            return response()->json(['success' => false, 'message' => 'PSLE exam type not found.'], 400);
        }

        $tasidoRegions = Region::whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])->get();
        $tasidoRegionIds = $tasidoRegions->pluck('id')->toArray();
        $schoolIds = School::whereIn('region_id', $tasidoRegionIds)->where('education_level', 'PRIMARY')->pluck('id')->toArray();

        DB::table('psle_result_validation_errors')->where('exam_year_id', $examYear->id)->delete();

        $validationResult = $this->runValidationChecks($examYear, $tasidoRegionIds, $schoolIds, $psleType->id);
        $errors = $validationResult['errors'];
        $errorsCount = $validationResult['errors_count'];
        $criticalCount = $validationResult['critical_count'];

        $this->logAuditAction("psle_results_validate", "Ran PSLE data integrity pre-run validation checks. Found " . $errorsCount . " errors (" . $criticalCount . " critical).");

        return response()->json([
            'success' => true,
            'message' => 'Integrity checks completed. Found ' . $errorsCount . ' errors (' . $criticalCount . ' critical).',
            'errors_count' => $errorsCount,
            'critical_count' => $criticalCount,
            'errors' => $errors,
        ]);
    }

    public function submitAndLockRawMarks(Request $request)
    {
        $activeYear = ExamYear::where('is_active', true)->first();
        $examYearId = (int) $request->input('exam_year_id', $activeYear->id ?? 0);
        $examYear = ExamYear::find($examYearId) ?: $activeYear;

        if (!$examYear) {
            return response()->json(['success' => false, 'message' => 'No active exam year found.'], 400);
        }

        $psleType = ExamType::where('code', self::EXAM_TYPE_CODE)->first();
        if (!$psleType) {
            return response()->json(['success' => false, 'message' => 'PSLE exam type not found.'], 400);
        }

        $tasidoRegions = Region::whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])->get();
        $tasidoRegionIds = $tasidoRegions->pluck('id')->toArray();
        $schoolIds = School::whereIn('region_id', $tasidoRegionIds)->where('education_level', 'PRIMARY')->pluck('id')->toArray();

        // Check for running process
        $running = DB::table('result_processes')
            ->where('exam_year_id', $examYear->id)
            ->where('exam_type_id', $psleType->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists();
        if ($running) {
            return response()->json(['success' => false, 'message' => 'Another processing action is currently running.'], 400);
        }

        // Check if already submitted and locked
        $alreadyLocked = DB::table('result_processes')
            ->where('exam_year_id', $examYear->id)
            ->where('exam_type_id', $psleType->id)
            ->where('type', 'submit_lock')
            ->where('status', 'completed')
            ->exists();
        if ($alreadyLocked) {
            return response()->json(['success' => false, 'message' => 'Raw marks are already submitted and locked.'], 400);
        }

        // Run validation check (non-destructive)
        DB::table('psle_result_validation_errors')->where('exam_year_id', $examYear->id)->delete();
        $validationResult = $this->runValidationChecks($examYear, $tasidoRegionIds, $schoolIds, $psleType->id);
        $criticalCount = $validationResult['critical_count'];
        $errors = $validationResult['errors'];

        if ($criticalCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot submit and lock. There are ' . $criticalCount . ' critical validation errors.',
                'critical_count' => $criticalCount,
                'errors' => array_slice($errors, 0, 10),
            ], 422);
        }

        // Lock raw marks and save record in a transaction
        DB::transaction(function() use ($schoolIds, $examYear, $psleType) {
            // Lock raw marks in DB
            DB::table('raw_marks')
                ->whereIn('school_id', $schoolIds)
                ->where('exam_year_id', $examYear->id)
                ->update([
                    'is_locked' => true,
                    'locked_at' => now(),
                    'locked_by' => auth()->id(),
                    'submitted_at' => now(),
                    'submitted_by' => auth()->id(),
                    'processing_ready_at' => now(),
                    'processing_ready_by' => auth()->id(),
                ]);

            // Create process log
            DB::table('result_processes')->insert([
                'exam_type_id' => $psleType->id,
                'exam_year_id' => $examYear->id,
                'user_id' => auth()->id(),
                'type' => 'submit_lock',
                'status' => 'completed',
                'processing_status' => 'ready',
                'total_candidates' => DB::table('candidate_exam_registrations as cer')
                    ->join('candidates as c', 'c.id', '=', 'cer.candidate_id')
                    ->join('schools as s', 's.id', '=', 'c.school_id')
                    ->whereIn('s.region_id', Region::whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])->pluck('id')->toArray())
                    ->where('cer.exam_type_id', $psleType->id)
                    ->where('cer.year', $examYear->year_label)
                    ->count(),
                'processed_count' => 0,
                'error_count' => 0,
                'submitted_at' => now(),
                'submitted_by' => auth()->id(),
                'locked_at' => now(),
                'locked_by' => auth()->id(),
                'processing_ready_at' => now(),
                'processing_ready_by' => auth()->id(),
                'processed_at' => now(),
                'completed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->logAuditAction("psle_raw_marks_submitted", "Raw marks submitted, locked, and marked ready for processing across all TASIDO regions.");

        return response()->json([
            'success' => true,
            'message' => 'Raw marks successfully submitted, locked, and marked ready for processing.'
        ]);
    }

    public function draftRun(Request $request)
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        @ini_set('memory_limit', '1024M');

        $activeYear = ExamYear::where('is_active', true)->first();
        $examYearId = (int) $request->input('exam_year_id', $activeYear->id ?? 0);
        $examYear = ExamYear::find($examYearId) ?: $activeYear;

        if (!$examYear) {
            return response()->json(['success' => false, 'message' => 'No active exam year found.'], 400);
        }

        $psleType = ExamType::where('code', self::EXAM_TYPE_CODE)->first();
        if (!$psleType) {
            return response()->json(['success' => false, 'message' => 'PSLE exam type not found.'], 400);
        }

        $tasidoRegions = Region::whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])->get();
        $tasidoRegionIds = $tasidoRegions->pluck('id')->toArray();
        $schoolIds = School::whereIn('region_id', $tasidoRegionIds)->where('education_level', 'PRIMARY')->pluck('id')->toArray();

        // 1. Verify raw marks are submitted and locked
        $isLocked = DB::table('result_processes')
            ->where('exam_year_id', $examYear->id)
            ->where('exam_type_id', $psleType->id)
            ->where('type', 'submit_lock')
            ->where('status', 'completed')
            ->where('processing_status', 'ready')
            ->exists();
        if (!$isLocked) {
            return response()->json(['success' => false, 'message' => 'Raw marks must be submitted and locked before processing.'], 400);
        }

        // Check running process
        $running = DB::table('result_processes')
            ->where('exam_year_id', $examYear->id)
            ->where('exam_type_id', $psleType->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists();
        if ($running) {
            return response()->json(['success' => false, 'message' => 'Another processing action is currently running.'], 400);
        }

        // Run draft processing inside a transaction
        $count = DB::transaction(function() use ($examYear, $psleType, $schoolIds, $tasidoRegionIds) {
            // Create a process record
            $processId = DB::table('result_processes')->insertGetId([
                'exam_type_id' => $psleType->id,
                'exam_year_id' => $examYear->id,
                'user_id' => auth()->id(),
                'type' => 'draft',
                'status' => 'in_progress',
                'total_candidates' => 0,
                'processed_count' => 0,
                'error_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $processed = $this->computeAndSaveResults($examYear, $psleType->id, $schoolIds, $tasidoRegionIds, null, $processId);

            DB::table('result_processes')->where('id', $processId)->update([
                'status' => 'completed',
                'total_candidates' => $processed,
                'processed_count' => $processed,
                'processed_at' => now(),
                'completed_at' => now(),
            ]);

            return $processed;
        });

        $this->logAuditAction("psle_results_draft_run", "Executed draft computation snapshot run for PSLE. Processed {$count} candidates.");

        return response()->json([
            'success' => true,
            'message' => "PSLE Draft computation completed. {$count} candidates processed successfully!"
        ]);
    }

    public function finalRun(Request $request)
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        @ini_set('memory_limit', '1024M');

        $activeYear = ExamYear::where('is_active', true)->first();
        $examYearId = (int) $request->input('exam_year_id', $activeYear->id ?? 0);
        $examYear = ExamYear::find($examYearId) ?: $activeYear;

        if (!$examYear) {
            return response()->json(['success' => false, 'message' => 'No active exam year found.'], 400);
        }

        $psleType = ExamType::where('code', self::EXAM_TYPE_CODE)->first();
        if (!$psleType) {
            return response()->json(['success' => false, 'message' => 'PSLE exam type not found.'], 400);
        }

        $tasidoRegions = Region::whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])->get();
        $tasidoRegionIds = $tasidoRegions->pluck('id')->toArray();
        $schoolIds = School::whereIn('region_id', $tasidoRegionIds)->where('education_level', 'PRIMARY')->pluck('id')->toArray();

        // 1. Verify raw marks are submitted and locked
        $isLocked = DB::table('result_processes')
            ->where('exam_year_id', $examYear->id)
            ->where('exam_type_id', $psleType->id)
            ->where('type', 'submit_lock')
            ->where('status', 'completed')
            ->where('processing_status', 'ready')
            ->exists();
        if (!$isLocked) {
            return response()->json(['success' => false, 'message' => 'Raw marks must be submitted and locked before processing.'], 400);
        }

        // Check running process
        $running = DB::table('result_processes')
            ->where('exam_year_id', $examYear->id)
            ->where('exam_type_id', $psleType->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists();
        if ($running) {
            return response()->json(['success' => false, 'message' => 'Another processing action is currently running.'], 400);
        }

        // Run final processing inside a transaction
        $result = DB::transaction(function() use ($examYear, $psleType, $schoolIds, $tasidoRegionIds) {
            // Deactivate existing snapshots
            DB::table('result_snapshots')
                ->where('exam_year_id', $examYear->id)
                ->where('exam_type', self::EXAM_TYPE_CODE)
                ->update(['is_active' => false]);

            // Calculate version
            $latestVersion = DB::table('result_snapshots')
                ->where('exam_year_id', $examYear->id)
                ->where('exam_type', self::EXAM_TYPE_CODE)
                ->max('version') ?? 0;
            $newVersion = $latestVersion + 1;

            // Create process log
            $processId = DB::table('result_processes')->insertGetId([
                'exam_type_id' => $psleType->id,
                'exam_year_id' => $examYear->id,
                'user_id' => auth()->id(),
                'type' => 'final',
                'status' => 'in_progress',
                'total_candidates' => 0,
                'processed_count' => 0,
                'error_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create Snapshot record
            $snapshotId = DB::table('result_snapshots')->insertGetId([
                'exam_type' => self::EXAM_TYPE_CODE,
                'exam_year_id' => $examYear->id,
                'process_id' => $processId,
                'version' => $newVersion,
                'is_active' => true,
                'is_rolled_back' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Compute and save snapshot-linked results
            $processed = $this->computeAndSaveResults($examYear, $psleType->id, $schoolIds, $tasidoRegionIds, $snapshotId, $processId);

            DB::table('result_processes')->where('id', $processId)->update([
                'status' => 'completed',
                'total_candidates' => $processed,
                'processed_count' => $processed,
                'processed_at' => now(),
                'completed_at' => now(),
            ]);

            return [
                'version' => $newVersion,
                'processed' => $processed,
            ];
        });

        $this->logAuditAction("psle_results_final_run", "Executed official final lock computation run for PSLE. Version: {$result['version']}, Candidates: {$result['processed']}.");

        return response()->json([
            'success' => true,
            'message' => "PSLE official results locked and snapshot version {$result['version']} created with {$result['processed']} candidates."
        ]);
    }

    public function publishSnapshot(Request $request)
    {
        $activeYear = ExamYear::where('is_active', true)->first();
        $examYearId = (int) $request->input('exam_year_id', $activeYear->id ?? 0);
        $examYear = ExamYear::find($examYearId) ?: $activeYear;

        if (!$examYear) {
            return response()->json(['success' => false, 'message' => 'No active exam year found.'], 400);
        }

        // Find active non-rolled-back snapshot
        $snapshot = DB::table('result_snapshots')
            ->where('exam_year_id', $examYear->id)
            ->where('exam_type', self::EXAM_TYPE_CODE)
            ->where('is_active', true)
            ->where('is_rolled_back', false)
            ->first();

        if (!$snapshot) {
            return response()->json(['success' => false, 'message' => 'No active final snapshot found to publish.'], 400);
        }

        DB::transaction(function() use ($examYear, $snapshot) {
            // Update or Create publication record
            $existing = DB::table('psle_result_publications')
                ->where('exam_year_id', $examYear->id)
                ->where('snapshot_id', $snapshot->id)
                ->first();

            if ($existing) {
                DB::table('psle_result_publications')
                    ->where('id', $existing->id)
                    ->update([
                        'status' => 'published',
                        'published_at' => now(),
                        'published_by' => auth()->id(),
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('psle_result_publications')->insert([
                    'exam_year_id' => $examYear->id,
                    'snapshot_id' => $snapshot->id,
                    'region_id' => null,
                    'council_id' => null,
                    'school_id' => null,
                    'publication_scope' => 'TASIDO',
                    'status' => 'published',
                    'version_no' => $snapshot->version,
                    'published_by' => auth()->id(),
                    'published_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $this->logAuditAction("psle_results_published", "Published PSLE snapshot version {$snapshot->version} for year {$examYear->year_label}.");

        // Dispatch background precalculation job
        \App\Jobs\PrecalculatePsleEvaluationsJob::dispatch((int) $examYear->year_label, 'all', null, null, true);

        return response()->json([
            'success' => true,
            'message' => "PSLE Results Snapshot version {$snapshot->version} successfully published to portals."
        ]);
    }

    public function rollback(Request $request)
    {
        $activeYear = ExamYear::where('is_active', true)->first();
        $examYearId = (int) $request->input('exam_year_id', $activeYear->id ?? 0);
        $examYear = ExamYear::find($examYearId) ?: $activeYear;

        if (!$examYear) {
            return response()->json(['success' => false, 'message' => 'No active exam year found.'], 400);
        }

        $psleType = ExamType::where('code', self::EXAM_TYPE_CODE)->first();
        if (!$psleType) {
            return response()->json(['success' => false, 'message' => 'PSLE exam type not found.'], 400);
        }

        $tasidoRegions = Region::whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])->get();
        $tasidoRegionIds = $tasidoRegions->pluck('id')->toArray();
        $schoolIds = School::whereIn('region_id', $tasidoRegionIds)->where('education_level', 'PRIMARY')->pluck('id')->toArray();

        // Find current active snapshot
        $snapshot = DB::table('result_snapshots')
            ->where('exam_year_id', $examYear->id)
            ->where('exam_type', self::EXAM_TYPE_CODE)
            ->where('is_active', true)
            ->where('is_rolled_back', false)
            ->first();

        DB::transaction(function() use ($examYear, $psleType, $schoolIds, $snapshot) {
            if ($snapshot) {
                // 1. Mark snapshot as rolled back and inactive
                DB::table('result_snapshots')
                    ->where('id', $snapshot->id)
                    ->update([
                        'is_active' => false,
                        'is_rolled_back' => true,
                        'rolled_back_at' => now(),
                        'rolled_back_by' => auth()->id(),
                    ]);

                // 2. Set matching publication record to rolled_back (non-destructive)
                DB::table('psle_result_publications')
                    ->where('exam_year_id', $examYear->id)
                    ->where('snapshot_id', $snapshot->id)
                    ->update([
                        'status' => 'rolled_back',
                        'updated_at' => now(),
                    ]);

                // Set precalculated evaluations cache status to stale/pending
                DB::table('psle_precalculated_evaluations')
                    ->where('snapshot_id', $snapshot->id)
                    ->update([
                        'status' => 'stale',
                        'updated_at' => now(),
                    ]);
            }

            // 3. Unlock raw marks (clear locks but keep submission history)
            DB::table('raw_marks')
                ->whereIn('school_id', $schoolIds)
                ->where('exam_year_id', $examYear->id)
                ->update([
                    'is_locked' => false,
                    'locked_at' => null,
                    'locked_by' => null,
                    'processing_ready_at' => null,
                    'processing_ready_by' => null,
                ]);

            // 4. Update submit_lock process status
            DB::table('result_processes')
                ->where('exam_year_id', $examYear->id)
                ->where('exam_type_id', $psleType->id)
                ->where('type', 'submit_lock')
                ->update([
                    'status' => 'rolled_back',
                    'processing_status' => 'rolled_back',
                    'locked_at' => null,
                    'locked_by' => null,
                    'processing_ready_at' => null,
                    'processing_ready_by' => null,
                ]);
        });

        $snapshotVersionStr = $snapshot ? " version {$snapshot->version}" : "";
        $this->logAuditAction("psle_results_rollback", "Rolled back PSLE snapshot{$snapshotVersionStr} and unlocked raw marks for editing.");

        return response()->json([
            'success' => true,
            'message' => 'Rollback sequence finished. Active parameters restored to draft mode, and raw marks unlocked for editing.'
        ]);
    }

    public function initiateCorrection(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Only administrator can initiate correction.'], 403);
        }

        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'exam_year_id' => 'required',
            'reason' => 'required|string|min:5',
        ]);

        try {
            $school = School::findOrFail($request->input('school_id'));
            $examYearId = $request->input('exam_year_id');
            $reason = $request->input('reason');

            $service = app(\App\Services\Results\SchoolRollbackService::class);
            $batch = $service->initiateRollback($school, (int)$examYearId, $reason, auth()->id());

            return response()->json([
                'success' => true,
                'message' => "Successfully initiated correction for {$school->name}.",
                'batch' => $batch
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function completeCorrection(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'batch_id' => 'required|exists:school_result_correction_batches,id',
        ]);

        try {
            $batch = \App\Models\SchoolResultCorrectionBatch::findOrFail($request->input('batch_id'));
            $service = app(\App\Services\Results\SchoolRollbackService::class);
            $batch = $service->completeCorrection($batch, auth()->id());

            return response()->json([
                'success' => true,
                'message' => "Correction phase marked as completed for school {$batch->school_name_snapshot}.",
                'batch' => $batch
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function recalculateCorrection(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'batch_id' => 'required|exists:school_result_correction_batches,id',
        ]);

        try {
            $batch = \App\Models\SchoolResultCorrectionBatch::findOrFail($request->input('batch_id'));
            $service = app(\App\Services\Results\SchoolRollbackService::class);
            $batch = $service->recalculateResults($batch, auth()->id());

            return response()->json([
                'success' => true,
                'message' => "Results recalculated successfully.",
                'batch' => $batch
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function republishCorrection(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'batch_id' => 'required|exists:school_result_correction_batches,id',
        ]);

        try {
            $batch = \App\Models\SchoolResultCorrectionBatch::findOrFail($request->input('batch_id'));
            $service = app(\App\Services\Results\SchoolRollbackService::class);
            $batch = $service->republishResults($batch, auth()->id());

            return response()->json([
                'success' => true,
                'message' => "Results successfully republished.",
                'batch' => $batch
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function cancelCorrection(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'batch_id' => 'required|exists:school_result_correction_batches,id',
            'reason' => 'required|string|min:5',
        ]);

        try {
            $batch = \App\Models\SchoolResultCorrectionBatch::findOrFail($request->input('batch_id'));
            $reason = $request->input('reason');
            $service = app(\App\Services\Results\SchoolRollbackService::class);
            $batch = $service->cancelRollback($batch, $reason, auth()->id());

            return response()->json([
                'success' => true,
                'message' => "Correction batch cancelled successfully.",
                'batch' => $batch
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    // --- Helpers ---

    private function runValidationChecks(ExamYear $examYear, array $tasidoRegionIds, array $schoolIds, int $psleExamTypeId)
    {
        $minScore = (float) \App\Helpers\SystemSettingsHelper::getSetting('minimum_subject_score', 0);
        $maxScore = (float) \App\Helpers\SystemSettingsHelper::getSetting('maximum_subject_score', 50);
        $absentCode = \App\Helpers\SystemSettingsHelper::getSetting('absent_code', 'ABS');
        $incompleteCode = \App\Helpers\SystemSettingsHelper::getSetting('incomplete_code', 'INC');

        // Fetch active subjects
        $subjects = DB::table('subjects as s')
            ->join('exam_types as et', 'et.id', '=', 's.exam_type_id')
            ->where('et.code', self::EXAM_TYPE_CODE)
            ->select('s.*')
            ->get();
        $subjectIds = $subjects->pluck('id')->toArray();
        $subjectMap = $subjects->keyBy('id');

        $errorsCount = 0;
        $criticalCount = 0;
        $sampleErrors = [];

        // 1. Chunk check candidate registrations & their marks
        DB::table('candidate_exam_registrations as cer')
            ->join('candidates as c', 'c.id', '=', 'cer.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->whereIn('s.region_id', $tasidoRegionIds)
            ->where('cer.exam_type_id', $psleExamTypeId)
            ->where('cer.year', $examYear->year_label)
            ->select(['c.id as candidate_pk', 'c.candidate_id as index_number', 'c.prem_no', 'c.full_name', 'c.school_id', 's.region_id'])
            ->orderBy('c.id')
            ->chunk(5000, function ($candidatesChunk) use ($schoolIds, $examYear, $subjectIds, $subjectMap, $minScore, $maxScore, $absentCode, $incompleteCode, &$errorsCount, &$criticalCount, &$sampleErrors) {
                $candidateIds = $candidatesChunk->pluck('candidate_pk')->toArray();

                // Fetch raw marks for this chunk of candidates
                $rawMarks = DB::table('raw_marks')
                    ->whereIn('school_id', $schoolIds)
                    ->where('exam_year_id', $examYear->id)
                    ->whereIn('candidate_id', $candidateIds)
                    ->get();

                $rawMarkGroups = [];
                $rawMarkRecords = [];
                foreach ($rawMarks as $rm) {
                    $rawMarkGroups[$rm->candidate_id][$rm->subject_id] = ($rawMarkGroups[$rm->candidate_id][$rm->subject_id] ?? 0) + 1;
                    $rawMarkRecords[$rm->candidate_id][$rm->subject_id] = $rm;
                }

                foreach ($candidatesChunk as $candidate) {
                    foreach ($subjectIds as $subId) {
                        $count = $rawMarkGroups[$candidate->candidate_pk][$subId] ?? 0;
                        $subject = $subjectMap->get($subId);

                        $err = null;

                        if ($count === 0) {
                            $err = [
                                'school_id' => $candidate->school_id,
                                'candidate_id' => $candidate->candidate_pk,
                                'subject_id' => $subId,
                                'candidate_no' => $candidate->index_number,
                                'subject_code' => $subject->code ?? null,
                                'error_type' => 'missing_marks',
                                'error_message' => "Candidate {$candidate->index_number} is missing marks for subject " . ($subject->name ?? 'Unknown') . ".",
                                'severity' => 'critical',
                            ];
                        } elseif ($count > 1) {
                            $err = [
                                'school_id' => $candidate->school_id,
                                'candidate_id' => $candidate->candidate_pk,
                                'subject_id' => $subId,
                                'candidate_no' => $candidate->index_number,
                                'subject_code' => $subject->code ?? null,
                                'error_type' => 'duplicate_marks',
                                'error_message' => "Candidate {$candidate->index_number} has duplicate mark entries for subject " . ($subject->name ?? 'Unknown') . ".",
                                'severity' => 'critical',
                            ];
                        } else {
                            $record = $rawMarkRecords[$candidate->candidate_pk][$subId];
                            if (!is_null($record->paper_1_marks)) {
                                $markVal = (float) $record->paper_1_marks;
                                if ($markVal < $minScore || $markVal > $maxScore) {
                                    $err = [
                                        'school_id' => $candidate->school_id,
                                        'candidate_id' => $candidate->candidate_pk,
                                        'subject_id' => $subId,
                                        'candidate_no' => $candidate->index_number,
                                        'subject_code' => $subject->code ?? null,
                                        'error_type' => 'invalid_score_range',
                                        'error_message' => "Candidate {$candidate->index_number} has out-of-range mark {$markVal} for subject " . ($subject->name ?? 'Unknown') . " (expected {$minScore}-{$maxScore}).",
                                        'severity' => 'critical',
                                    ];
                                }
                            } else {
                                $statusVal = strtoupper(trim((string) $record->subject_status));
                                if ($statusVal !== strtoupper($absentCode) && $statusVal !== strtoupper($incompleteCode)) {
                                    $err = [
                                        'school_id' => $candidate->school_id,
                                        'candidate_id' => $candidate->candidate_pk,
                                        'subject_id' => $subId,
                                        'candidate_no' => $candidate->index_number,
                                        'subject_code' => $subject->code ?? null,
                                        'error_type' => 'invalid_status_code',
                                        'error_message' => "Candidate {$candidate->index_number} has null mark but invalid status code '{$record->subject_status}' for subject " . ($subject->name ?? 'Unknown') . " (expected {$absentCode} or {$incompleteCode}).",
                                        'severity' => 'critical',
                                    ];
                                }
                            }
                        }

                        if ($err) {
                            $errorsCount++;
                            if ($err['severity'] === 'critical') {
                                $criticalCount++;
                            }
                            if (count($sampleErrors) < 100) {
                                $sampleErrors[] = $err;
                            }
                        }
                    }
                }
            });

        // 2. Fetch orphan marks using direct database LEFT JOIN (extremely memory efficient)
        $orphanMarks = DB::table('raw_marks as rm')
            ->leftJoin('candidate_exam_registrations as cer', function ($join) use ($psleExamTypeId, $examYear) {
                $join->on('cer.candidate_id', '=', 'rm.candidate_id')
                    ->where('cer.exam_type_id', '=', $psleExamTypeId)
                    ->where('cer.year', '=', $examYear->year_label);
            })
            ->leftJoin('candidates as c', 'c.id', '=', 'rm.candidate_id')
            ->whereIn('rm.school_id', $schoolIds)
            ->where('rm.exam_year_id', $examYear->id)
            ->whereNull('cer.candidate_id')
            ->select(['rm.school_id', 'rm.candidate_id', 'rm.subject_id', 'rm.candidate_index_number', 'c.id as reg_candidate_id'])
            ->get();

        foreach ($orphanMarks as $rm) {
            $candidateNo = $rm->candidate_index_number ?? 'Unknown';
            $sub = $subjectMap->get($rm->subject_id);
            $err = [
                'school_id' => $rm->school_id,
                'candidate_id' => $rm->candidate_id,
                'subject_id' => $rm->subject_id,
                'candidate_no' => $candidateNo,
                'subject_code' => $sub->code ?? null,
                'error_type' => 'orphan_marks',
                'error_message' => "Raw mark record found for candidate ID {$rm->candidate_id} ({$candidateNo}) who is not registered in TASIDO region for this year.",
                'severity' => 'critical',
            ];

            $errorsCount++;
            $criticalCount++;
            if (count($sampleErrors) < 100) {
                $sampleErrors[] = $err;
            }
        }

        return [
            'errors' => $sampleErrors,
            'errors_count' => $errorsCount,
            'critical_count' => $criticalCount,
        ];
    }

    private function computeAndSaveResults(ExamYear $examYear, int $examTypeId, array $schoolIds, array $tasidoRegionIds, $snapshotId = null, $processId = null)
    {
        $minScore = (float) \App\Helpers\SystemSettingsHelper::getSetting('minimum_subject_score', 0);
        $maxScore = (float) \App\Helpers\SystemSettingsHelper::getSetting('maximum_subject_score', 50);
        $absentCode = \App\Helpers\SystemSettingsHelper::getSetting('absent_code', 'ABS');
        $incompleteCode = \App\Helpers\SystemSettingsHelper::getSetting('incomplete_code', 'INC');

        // Fetch subjects
        $subjects = DB::table('subjects as s')
            ->join('exam_types as et', 'et.id', '=', 's.exam_type_id')
            ->where('et.code', self::EXAM_TYPE_CODE)
            ->select('s.*')
            ->get();
        $subjectIds = $subjects->pluck('id')->toArray();

        // Clear existing draft results if computing a draft
        if ($snapshotId === null) {
            DB::table('candidate_results')
                ->where('exam_type_id', $examTypeId)
                ->where('year', $examYear->year_label)
                ->whereNull('snapshot_id')
                ->delete();

            DB::table('subject_marks')
                ->where('exam_type_id', $examTypeId)
                ->where('year', $examYear->year_label)
                ->whereNull('snapshot_id')
                ->delete();
        }

        $totalProcessed = 0;

        DB::table('candidate_exam_registrations as cer')
            ->join('candidates as c', 'c.id', '=', 'cer.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->whereIn('s.region_id', $tasidoRegionIds)
            ->where('cer.exam_type_id', $examTypeId)
            ->where('cer.year', $examYear->year_label)
            ->select(['c.id as candidate_pk', 'c.gender', 'c.school_id'])
            ->orderBy('c.id')
            ->chunk(5000, function ($candidatesChunk) use ($schoolIds, $examYear, $examTypeId, $snapshotId, $processId, $subjectIds, $absentCode, $incompleteCode, &$totalProcessed) {
                $candidateIds = $candidatesChunk->pluck('candidate_pk')->toArray();

                // Fetch raw marks ONLY for this chunk of candidates
                $allRawMarks = DB::table('raw_marks')
                    ->whereIn('school_id', $schoolIds)
                    ->where('exam_year_id', $examYear->id)
                    ->whereIn('candidate_id', $candidateIds)
                    ->get()
                    ->groupBy('candidate_id');

                $candidateResultsData = [];
                $subjectMarksData = [];

                foreach ($candidatesChunk as $cand) {
                    $candMarks = $allRawMarks->get($cand->candidate_pk, collect());
                    $candMarksBySubject = $candMarks->keyBy('subject_id');

                    $totalMarks = 0.0;
                    $hasInc = false;
                    $gradedCount = 0;
                    $absCount = 0;

                    $tempSubjectData = [];

                    foreach ($subjectIds as $subId) {
                        $rm = $candMarksBySubject->get($subId);

                        $marksObtained = null;
                        $maxMarks = 50.0;
                        $percentage = null;
                        $grade = 'E';

                        if ($rm) {
                            if (!is_null($rm->paper_1_marks)) {
                                $marksObtained = (float) $rm->paper_1_marks;
                                $percentage = ($marksObtained / $maxMarks) * 100;
                                $grade = $this->gradeFromRaw50($marksObtained);
                                $totalMarks += $marksObtained;
                                $gradedCount++;
                            } else {
                                $status = strtoupper(trim((string) $rm->subject_status));
                                if ($status === strtoupper($absentCode)) {
                                    $grade = 'ABS';
                                    $absCount++;
                                } elseif ($status === strtoupper($incompleteCode)) {
                                    $grade = 'INC';
                                    $hasInc = true;
                                }
                            }
                        } else {
                            $grade = 'ABS';
                            $absCount++;
                        }

                        $tempSubjectData[] = [
                            'candidate_id' => $cand->candidate_pk,
                            'exam_type_id' => $examTypeId,
                            'subject_id' => $subId,
                            'year' => $examYear->year_label,
                            'marks_obtained' => $marksObtained,
                            'max_marks' => $maxMarks,
                            'percentage' => $percentage,
                            'grade' => $grade,
                            'snapshot_id' => $snapshotId,
                            'process_id' => $processId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    $overallStatus = 'RELEASED';
                    if ($hasInc || ($gradedCount < count($subjectIds) && $absCount < count($subjectIds))) {
                        $overallStatus = 'PENDING';
                    }

                    $overallGrade = 'E';
                    if ($absCount === count($subjectIds)) {
                        $overallGrade = 'ABS';
                    } elseif ($hasInc || ($gradedCount < count($subjectIds))) {
                        $overallGrade = 'INC';
                    } else {
                        $avg = $gradedCount > 0 ? ($totalMarks / $gradedCount) : 0.0;
                        $overallGrade = $this->gradeFromRaw50($avg);
                    }

                    $candidateResultsData[] = [
                        'candidate_id' => $cand->candidate_pk,
                        'exam_type_id' => $examTypeId,
                        'year' => $examYear->year_label,
                        'total_marks' => $gradedCount > 0 ? $totalMarks : null,
                        'total_percentage' => $gradedCount > 0 ? ($totalMarks / (count($subjectIds) * 50)) * 100 : null,
                        'overall_grade' => $overallGrade,
                        'status' => $overallStatus,
                        'released_at' => $overallStatus === 'RELEASED' ? now() : null,
                        'snapshot_id' => $snapshotId,
                        'process_id' => $processId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    foreach ($tempSubjectData as $tsd) {
                        $subjectMarksData[] = $tsd;
                    }
                }

                foreach (array_chunk($candidateResultsData, 500) as $chunk) {
                    DB::table('candidate_results')->insert($chunk);
                }

                foreach (array_chunk($subjectMarksData, 500) as $chunk) {
                    DB::table('subject_marks')->insert($chunk);
                }

                $totalProcessed += count($candidateResultsData);
            });

        return $totalProcessed;
    }

    private function gradeFromRaw50($mark): string
    {
        if (is_null($mark)) return 'E';
        if ($mark >= 241 / 6) return 'A';
        if ($mark >= 181 / 6) return 'B';
        if ($mark >= 121 / 6) return 'C';
        if ($mark >= 61 / 6) return 'D';
        return 'E';
    }

    private function logAuditAction(string $action, string $details): void
    {
        try {
            $activeYear = ExamYear::where('is_active', true)->first();
            AuditLog::create([
                'user_id' => auth()->id() ?? 1,
                'exam_year_id' => $activeYear->id ?? 1,
                'module' => 'results',
                'action' => $action,
                'details' => $details,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        } catch (\Exception $e) {
            // Silence exceptions in logging to prevent interface locks
        }
    }
}
