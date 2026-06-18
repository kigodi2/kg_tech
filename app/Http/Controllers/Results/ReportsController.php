<?php

namespace App\Http\Controllers\Results;

use App\Http\Controllers\Api\Results\AcseeLifecycleApiController;
use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateResult;
use App\Models\District;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\Region;
use App\Models\School;
use App\Models\SubjectMarks;
use App\Services\Results\PsleDistrictSchoolFpdfService;
use App\Services\Schools\NectaPsle2025SchoolSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ZipArchive;

class ReportsController extends Controller
{
    public function __construct(
        private readonly AcseeLifecycleApiController $lifecycleController,
        private readonly PsleDistrictSchoolFpdfService $psleDistrictSchoolFpdfService
    ) {
    }

    private function routePrefix(Request $request): string
    {
        return $request->routeIs('results.psle.*') ? 'results.psle' : 'results.acsee';
    }

    private function examCode(Request $request): string
    {
        return $request->routeIs('results.psle.*') ? 'PSLE' : 'ACSEE';
    }

    public function index(Request $request)
    {
        if ($request->routeIs('results.psle.*')) {
            // Active exam year context
            $activeYear = ExamYear::where('is_active', true)->first() 
                ?: ExamYear::orderByDesc('year_label')->first();
            $examYearId = (int) $request->input('exam_year_id', $activeYear->id ?? 0);
            $examYear = ExamYear::find($examYearId) ?: $activeYear;
            $yearLabel = (int) ($examYear->year_label ?? 2026);

            // Fetch TASIDO regions
            $tasidoRegions = Region::whereIn(\DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])
                ->orderBy('name')
                ->get();
            $tasidoRegionIds = $tasidoRegions->pluck('id')->toArray();

            // Filters
            $regionId = $request->filled('region_id') ? (int) $request->input('region_id') : null;
            $districtId = $request->filled('district_id') ? (int) $request->input('district_id') : null;

            // Load dropdowns based on selections
            $districts = collect();
            if ($regionId) {
                $districts = District::where('region_id', $regionId)->orderBy('name')->get();
            } elseif (!empty($tasidoRegionIds)) {
                $districts = District::whereIn('region_id', $tasidoRegionIds)->orderBy('name')->get();
            }

            // Overview metrics calculation directly from raw subject marks
            $schoolsQuery = School::whereIn('region_id', $tasidoRegionIds)->where('education_level', 'PRIMARY');
            $schoolsCount = $schoolsQuery->count();
            $schoolIds = $schoolsQuery->pluck('id')->toArray();

            $registeredCount = Candidate::whereIn('school_id', $schoolIds)->where('exam_type', 'PSLE')->count();

            // Candidates with all 6 subject marks entered
            $completeCount = \DB::table('subject_marks as sm')
                ->join('candidates as c', 'c.id', '=', 'sm.candidate_id')
                ->whereIn('c.school_id', $schoolIds)
                ->where('sm.year', $yearLabel)
                ->where('c.exam_type', 'PSLE')
                ->groupBy('c.id')
                ->having(\DB::raw('count(distinct sm.subject_id)'), '>=', 6)
                ->select('c.id')
                ->get()
                ->count();

            $missingCount = max(0, $registeredCount - $completeCount);

            $metrics = [
                'regions' => count($tasidoRegionIds),
                'schools' => $schoolsCount,
                'registered' => $registeredCount,
                'complete' => $completeCount,
                'missing' => $missingCount,
                'processed' => $completeCount,
                'published' => 'Active',
                'available_reports' => $schoolsCount
            ];

            // 1. Fetch primary schools
            $schoolQuery = School::whereIn('region_id', $tasidoRegionIds)
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

            // 2. Fetch stats for each school in the current page
            $psleExamTypeId = (int) ExamType::query()->where('code', 'PSLE')->value('id');
            $currentPageSchoolIds = collect($schools->items())->pluck('id')->toArray();

            $registeredCounts = [];
            $completeCounts = [];

            if (!empty($currentPageSchoolIds)) {
                $registeredCounts = \DB::table('candidates')
                    ->whereIn('school_id', $currentPageSchoolIds)
                    ->where('exam_type', 'PSLE')
                    ->groupBy('school_id')
                    ->selectRaw('school_id, count(*) as count')
                    ->pluck('count', 'school_id')
                    ->toArray();

                $completeCounts = \DB::table('candidate_results as cr')
                    ->join('candidates as c', 'c.id', '=', 'cr.candidate_id')
                    ->whereIn('c.school_id', $currentPageSchoolIds)
                    ->where('cr.exam_type_id', $psleExamTypeId)
                    ->where('cr.year', $yearLabel)
                    ->groupBy('c.school_id')
                    ->selectRaw('c.school_id, count(*) as count')
                    ->pluck('count', 'school_id')
                    ->toArray();
            }

            $schoolStats = [];
            foreach ($schools->items() as $school) {
                $schoolRegistered = $registeredCounts[$school->id] ?? 0;
                $schoolComplete = $completeCounts[$school->id] ?? 0;
                $schoolMissing = max(0, $schoolRegistered - $schoolComplete);

                $status = 'No Marks';
                if ($schoolRegistered > 0) {
                    if ($schoolComplete === $schoolRegistered) {
                        $status = 'Ready';
                    } elseif ($schoolComplete > 0) {
                        $status = 'In Progress';
                    }
                }

                $schoolStats[$school->id] = [
                    'registered' => $schoolRegistered,
                    'complete' => $schoolComplete,
                    'missing' => $schoolMissing,
                    'status' => $status
                ];
            }

            $viewData = [
                'districts' => $districts,
                'schools' => $schools,
                'schoolStats' => $schoolStats
            ];

            $examYears = ExamYear::orderByDesc('year_label')->get();

            return view('results.psle.reports.standalone', compact(
                'metrics', 'viewData', 'tasidoRegions', 
                'districts', 'examYears', 'examYear', 
                'regionId', 'districtId'
            ));
        }

        $defaultExamYearId = old('exam_year_id', ExamYear::query()->where('is_active', true)->value('id'));
        $examYears = ExamYear::query()
            ->orderByDesc('year_label')
            ->get(['id', 'year_label', 'is_active']);

        $regions = Region::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $districts = $this->buildDistrictOptions(
            (int) $defaultExamYearId,
            old('region_id')
        );

        return view('results.acsee.reports.index', [
            'examYears' => $examYears,
            'regions' => $regions,
            'districts' => $districts,
            'noSidebar' => true,
            'defaults' => [
                'exam_year_id' => $defaultExamYearId,
                'region_id' => old('region_id'),
                'district_id' => old('district_id'),
                'mode' => old('mode', 'draft'),
            ],
        ]);
    }

    public function districtOptions(Request $request)
    {
        $request->validate([
            'exam_year_id' => ['required', 'integer', 'exists:exam_years,id'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
        ]);

        return response()->json([
            'data' => $this->buildDistrictOptions(
                (int) $request->integer('exam_year_id'),
                $request->input('region_id')
            )->values(),
        ]);
    }

    public function exportDistrictSchoolResults(Request $request)
    {
        if ($request->routeIs('results.psle.*')) {
            return $this->exportPsleDistrictSchoolResults($request);
        }

        $request->merge([
            'report_type' => 'district_school_results',
            'format' => 'pdf',
            'mode' => $request->input('mode', 'draft'),
        ]);

        $response = $this->lifecycleController->exportDownload($request);

        if ($response instanceof JsonResponse) {
            $payload = $response->getData(true);

            return redirect()
                ->route($this->routePrefix($request) . '.reports.index')
                ->withInput($request->except('_token'))
                ->withErrors([
                    'export' => (string) ($payload['message'] ?? 'District export failed.'),
                ]);
        }

        return $response;
    }

    private function exportPsleDistrictSchoolResults(Request $request)
    {
        $validated = $request->validate([
            'exam_year_id' => ['required', 'integer', 'exists:exam_years,id'],
            'district_id' => ['required', 'integer', 'exists:districts,id'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'mode' => ['nullable', 'in:published,draft'],
        ]);

        $examYear = ExamYear::query()->findOrFail((int) $validated['exam_year_id']);
        $district = District::query()->findOrFail((int) $validated['district_id']);
        $region = !empty($validated['region_id']) ? Region::query()->find((int) $validated['region_id']) : Region::query()->find($district->region_id);
        $psleExamTypeId = (int) ExamType::query()->where('code', 'PSLE')->value('id');
        $yearValue = (int) $examYear->year_label;

        $results = CandidateResult::query()
            ->where('exam_type_id', $psleExamTypeId)
            ->where('year', $yearValue)
            ->whereHas('candidate.school', fn ($query) => $query->where('district_id', $district->id))
            ->with([
                'candidate:id,school_id,candidate_id,prem_no,full_name,gender',
                'candidate.school:id,code,name,district_id,region_id',
                'candidate.school.district:id,name,region_id',
                'candidate.school.region:id,name',
            ])
            ->orderByDesc('total_marks')
            ->get();

        if ($results->isEmpty()) {
            return redirect()
                ->route($this->routePrefix($request) . '.reports.index')
                ->withInput($request->except('_token'))
                ->withErrors([
                    'export' => 'No PSLE result rows found for the selected district and year.',
                ]);
        }

        $candidateIds = $results->pluck('candidate_id')->filter()->unique()->values();
        $subjectMarks = SubjectMarks::query()
            ->where('exam_type_id', $psleExamTypeId)
            ->where('year', $yearValue)
            ->whereIn('candidate_id', $candidateIds)
            ->with('subject:id,code,name')
            ->orderBy('subject_id')
            ->get()
            ->groupBy('candidate_id');

        $results->each(function ($result) use ($subjectMarks) {
            $result->setRelation('subjectMarks', $subjectMarks->get($result->candidate_id, collect())->values());
        });

        $schools = $results
            ->groupBy(fn ($row) => (int) ($row->candidate?->school_id ?? 0))
            ->filter(fn ($rows, $schoolId) => (int) $schoolId > 0);

        if ($schools->isEmpty()) {
            return redirect()
                ->route($this->routePrefix($request) . '.reports.index')
                ->withInput($request->except('_token'))
                ->withErrors([
                    'export' => 'No PSLE schools with exportable result rows were found in the selected district.',
                ]);
        }

        $tempDir = storage_path('app/tmp/psle-district-exports-' . uniqid());
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $districtLabel = strtoupper((string) ($district->name ?? 'DISTRICT'));
        $districtLabel = preg_replace('/[\/\\\\:*?"<>|]+/', ' ', $districtLabel);
        $districtLabel = trim((string) preg_replace('/\s+/', ' ', $districtLabel));
        $zipFilename = sprintf('%s_%s_PSLE_RESULTS.zip', $districtLabel !== '' ? $districtLabel : 'DISTRICT', $examYear->year_label);
        $zipPath = $tempDir . DIRECTORY_SEPARATOR . $zipFilename;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return redirect()
                ->route($this->routePrefix($request) . '.reports.index')
                ->withInput($request->except('_token'))
                ->withErrors([
                    'export' => 'Unable to create the PSLE district ZIP export file.',
                ]);
        }

        foreach ($schools as $schoolRows) {
            $school = $schoolRows->first()?->candidate?->school;
            if (!$school) {
                continue;
            }

            $safeSchoolName = preg_replace('/[^A-Za-z0-9_-]+/', '_', strtoupper((string) $school->name));
            $pdfFilename = sprintf(
                '%s_%s_PSLE_RESULTS.pdf',
                strtoupper((string) $school->code),
                trim((string) $safeSchoolName, '_')
            );
            $pdfPath = $tempDir . DIRECTORY_SEPARATOR . $pdfFilename;

            $this->psleDistrictSchoolFpdfService->generateSchoolPdf(
                $schoolRows->values(),
                $pdfPath,
                (string) $examYear->year_label,
                $region,
                $district,
                (string) (auth()->user()->name ?? 'System')
            );

            $zip->addFile($pdfPath, $pdfFilename);
        }

        $zip->close();

        register_shutdown_function(function () use ($tempDir) {
            if (!is_dir($tempDir)) {
                return;
            }

            foreach (glob($tempDir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
                @unlink($file);
            }

            @rmdir($tempDir);
        });

        return response()->download($zipPath, $zipFilename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    public function exportSchoolPdf(Request $request, School $school)
    {
        $validated = $request->validate([
            'exam_year_id' => ['required', 'integer', 'exists:exam_years,id'],
            'mode' => ['nullable', 'in:published,draft'],
        ]);

        $examYear = ExamYear::query()->findOrFail((int) $validated['exam_year_id']);
        $psleExamTypeId = (int) ExamType::query()->where('code', 'PSLE')->value('id');
        $yearValue = (int) $examYear->year_label;
        
        $region = $school->region;
        $district = $school->district;

        $results = CandidateResult::query()
            ->where('exam_type_id', $psleExamTypeId)
            ->where('year', $yearValue)
            ->whereHas('candidate', fn ($query) => $query->where('school_id', $school->id))
            ->with([
                'candidate:id,school_id,candidate_id,prem_no,full_name,gender',
                'candidate.school:id,code,name,district_id,region_id',
                'candidate.school.district:id,name,region_id',
                'candidate.school.region:id,name',
            ])
            ->orderByDesc('total_marks')
            ->get();

        if ($results->isEmpty()) {
            return redirect()
                ->back()
                ->withErrors([
                    'export' => 'No PSLE result rows found for the selected school and year.',
                ]);
        }

        $candidateIds = $results->pluck('candidate_id')->filter()->unique()->values();
        $subjectMarks = SubjectMarks::query()
            ->where('exam_type_id', $psleExamTypeId)
            ->where('year', $yearValue)
            ->whereIn('candidate_id', $candidateIds)
            ->with('subject:id,code,name')
            ->orderBy('subject_id')
            ->get()
            ->groupBy('candidate_id');

        $results->each(function ($result) use ($subjectMarks) {
            $result->setRelation('subjectMarks', $subjectMarks->get($result->candidate_id, collect())->values());
        });

        $pdfDir = storage_path('app/public/psle-school-exports/' . $examYear->id);
        if (!is_dir($pdfDir)) {
            @mkdir($pdfDir, 0755, true);
        }

        $safeSchoolName = preg_replace('/[^A-Za-z0-9_-]+/', '_', strtoupper((string) $school->name));
        $pdfFilename = sprintf(
            '%s_%s_PSLE_RESULTS.pdf',
            strtoupper((string) $school->code),
            trim((string) $safeSchoolName, '_')
        );
        $pdfPath = $pdfDir . DIRECTORY_SEPARATOR . 'school_' . $school->id . '.pdf';

        $isAdmin = auth()->check() && (auth()->user()->is_admin || (auth()->user()->role ?? '') === 'admin');

        if (!file_exists($pdfPath) || $isAdmin) {
            $this->psleDistrictSchoolFpdfService->generateSchoolPdf(
                $results->values(),
                $pdfPath,
                (string) $examYear->year_label,
                $region,
                $district,
                (string) (auth()->user()->name ?? 'System')
            );
        }

        return response()->download($pdfPath, $pdfFilename);
    }

    private function buildDistrictOptions(int $examYearId, $regionId = null)
    {
        if (request()->routeIs('results.psle.*')) {
            return District::query()
                ->select('districts.id', 'districts.region_id', 'districts.name')
                ->join('schools', 'schools.district_id', '=', 'districts.id')
                ->where('schools.source_system', NectaPsle2025SchoolSyncService::SOURCE_SYSTEM)
                ->when($regionId, fn ($query) => $query->where('districts.region_id', (int) $regionId))
                ->distinct()
                ->orderBy('districts.name')
                ->get();
        }

        $examYear = ExamYear::query()->findOrFail($examYearId);
        $examTypeId = (int) ExamType::query()->where('code', $this->examCode(request()))->value('id');

        return District::query()
            ->select('districts.id', 'districts.region_id', 'districts.name')
            ->join('schools', 'schools.district_id', '=', 'districts.id')
            ->join('candidates', 'candidates.school_id', '=', 'schools.id')
            ->join('candidate_results', 'candidate_results.candidate_id', '=', 'candidates.id')
            ->where('candidate_results.exam_type_id', $examTypeId)
            ->where('candidate_results.year', (int) $examYear->year_label)
            ->when($regionId, fn ($query) => $query->where('districts.region_id', (int) $regionId))
            ->distinct()
            ->orderBy('districts.name')
            ->get();
    }
}
