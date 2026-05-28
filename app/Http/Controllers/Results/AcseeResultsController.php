<?php

namespace App\Http\Controllers\Results;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\CandidateResult;
use App\Models\CandidateSubjectSelection;
use App\Models\District;
use App\Models\ExamType;
use App\Models\Region;
use App\Models\School;
use App\Models\SubjectMarks;
use App\Services\Results\AcseeResultsService;
use App\Services\Results\ResultsExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * ACSEE Results Controller
 * 
 * Provides read-only access to published ACSEE results with role-based scoping,
 * filtering, and export capabilities.
 * 
 * Requirements:
 * - Authentication required
 * - Role-based data scoping
 * - Published results only
 * - No inline editing
 * - Audit logging for exports
 */
class AcseeResultsController extends Controller
{
    protected AcseeResultsService $resultsService;
    protected ResultsExportService $exportService;

    public function __construct(
        AcseeResultsService $resultsService,
        ResultsExportService $exportService
    ) {
        $this->resultsService = $resultsService;
        $this->exportService = $exportService;
    }

    /**
     * Display ACSEE results with filtering and pagination
     * 
     * GET /results/acsee
     * 
     * Query Parameters:
     * - year (optional): exam year
     * - region_id (optional): region filter
     * - district_id (optional): district filter
     * - school_id (optional): school filter
     * - search (optional): search by index number or candidate name
     * - page (optional): pagination page
     * - per_page (optional): results per page (default: 50, max: 500)
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $year = $request->input('year');
        $examYears = collect($this->resultsService->getAvailableExamYears($user));
        $errors = collect([]);
        $results = null;
        $subjects = collect([]);
        $publishedYear = null;
        $availableFilters = collect(['regions' => [], 'districts' => [], 'schools' => []]);
        $currentFilters = collect(['region_id' => null, 'district_id' => null, 'school_id' => null]);

        // If year is provided, get results
        if ($year) {
            // Verify exam year has published ACSEE results
            $publishedYear = $this->resultsService->getPublishedExamYear($year);
            if (!$publishedYear) {
                $errors = collect(['year' => 'No published results found for this year']);
            } else {
                // Get available scope filters for the user
                $availableFilters = collect($this->resultsService->getAvailableScopeFilters($user, $year));

                // Apply role-based scoping
                $scopedResults = $this->resultsService->applyScopeFilter(
                    $user,
                    $request->only(['region_id', 'district_id', 'school_id'])
                );
                $currentFilters = collect($scopedResults);

                // Build base query
                $query = CandidateResult::query()
                    ->where('is_published', true)
                    ->where('year', $year)
                    ->whereHas('examType', function ($q) {
                        $q->where('code', 'ACSEE');
                    });

                // Apply role-based scoping
                $query = $this->resultsService->applyScopeQuery($query, $scopedResults);

                // Apply search filter
                if ($search = $request->input('search')) {
                    $query->whereHas('candidate', function ($q) use ($search) {
                        $q->where('candidate_id', 'LIKE', "%{$search}%")
                          ->orWhere('full_name', 'LIKE', "%{$search}%");
                    });
                }

                // Eager load relationships to avoid N+1
                $query->with([
                    'candidate:id,school_id,candidate_id,full_name,gender',
                    'candidate.school:id,district_id,region_id',
                    'examType:id,code,name',
                    'subjectMarks:id,candidate_id,subject_id,exam_type_id,year,mark,grade'
                        ->with('subject:id,code,name'),
                ]);

                // Paginate
                $perPage = min($request->input('per_page', 20), 500);
                $results = $query->paginate($perPage)->withQueryString();

                // Get subject list for this year/exam type
                $subjects = collect($this->resultsService->getSubjectsForYear($year, 'ACSEE'));
            }
        }

        return view('results.acsee.index', [
            'results' => $results,
            'subjects' => $subjects,
            'year' => $year,
            'publishedYear' => $publishedYear,
            'examYears' => $examYears,
            'availableFilters' => $availableFilters,
            'currentFilters' => $currentFilters,
            'userRole' => $user->role->code ?? null,
            'errors' => $errors,
        ]);
    }

    /**
     * Export ACSEE results as PDF
     * 
     * POST /results/acsee/export-pdf
     * 
     * Request Body:
     * - year (required)
     * - region_id (optional)
     * - district_id (optional)
     * - school_id (optional)
     * - include_marks (boolean): include raw marks or just grades
     */
    public function exportPdf(Request $request)
    {
        $this->authorize('exportResults');

        $validated = $request->validate([
            'year' => 'required|integer',
            'region_id' => 'nullable|integer',
            'district_id' => 'nullable|integer',
            'school_id' => 'nullable|integer',
            'include_marks' => 'boolean',
        ]);

        $user = auth()->user();

        // Verify user scope includes requested filters
        $this->resultsService->validateUserScopes($user, $request->only([
            'region_id', 'district_id', 'school_id'
        ]));

        // Get results data
        $results = $this->resultsService->getExportResults(
            $user,
            $validated
        );

        // Log export action
        $this->resultsService->logExportAction($user, 'pdf', $validated);

        // Generate PDF
        return $this->exportService->generatePdf(
            $results,
            $validated['year'],
            $request->input('school_id')
        );
    }

    /**
     * Export ACSEE results as CSV
     * 
     * POST /results/acsee/export-csv
     * 
     * Request Body:
     * - year (required)
     * - region_id (optional)
     * - district_id (optional)
     * - school_id (optional)
     */
    public function exportCsv(Request $request)
    {
        $this->authorize('exportResults');

        $validated = $request->validate([
            'year' => 'required|integer',
            'region_id' => 'nullable|integer',
            'district_id' => 'nullable|integer',
            'school_id' => 'nullable|integer',
        ]);

        $user = auth()->user();

        // Verify user scope includes requested filters
        $this->resultsService->validateUserScopes($user, $request->only([
            'region_id', 'district_id', 'school_id'
        ]));

        // Get results data
        $results = $this->resultsService->getExportResults(
            $user,
            $validated
        );

        // Log export action
        $this->resultsService->logExportAction($user, 'csv', $validated);

        // Generate CSV
        return $this->exportService->generateCsv(
            $results,
            $validated['year']
        );
    }

    /**
     * Get available filters for current user (AJAX)
     * 
     * GET /results/acsee/filters
     * 
     * Returns JSON with available regions, districts, schools
     */
    public function getFilters(Request $request)
    {
        $user = auth()->user();
        $year = $request->input('year');

        if (!$year) {
            return response()->json(['error' => 'Year is required'], 422);
        }

        $filters = $this->resultsService->getAvailableScopeFilters($user, $year);

        return response()->json($filters);
    }

    /**
     * Get candidate detail with all subject marks (AJAX)
     * 
     * GET /results/acsee/candidate/{candidateId}
     */
    public function getCandidateDetail(Request $request, $candidateId)
    {
        $user = auth()->user();

        $result = CandidateResult::query()
            ->where('is_published', true)
            ->whereHas('candidate', function ($q) use ($candidateId) {
                $q->where('id', $candidateId);
            })
            ->with([
                'candidate:id,school_id,candidate_id,full_name,gender',
                'candidate.school:id,name,district_id,region_id',
                'examType:id,code,name',
                'subjectMarks:id,candidate_id,subject_id,exam_type_id,year,mark,grade'
                    ->with('subject:id,code,name'),
            ])
            ->firstOrFail();

        // Verify user can view this result
        $this->authorize('viewResult', $result);

        return response()->json($result->toArray());
    }
}
