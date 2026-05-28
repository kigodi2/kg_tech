<?php

namespace App\Http\Controllers\Results;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\School;
use App\Models\Combination;
use App\Models\ExamYear;
use App\Models\ExamType;
use Illuminate\Http\Request;

/**
 * ResultsManagementController
 *
 * Manages viewing, publishing, and unpublishing of ACSEE results.
 * Read-only after publishing. Supports filtering and viewing per candidate, school, or combination.
 */
class ResultsManagementController extends Controller
{
    private function examCode(Request $request): string
    {
        return $request->routeIs('results.psle.*') ? 'PSLE' : 'ACSEE';
    }

    private function legacyLifecycleRedirectResponse(string $action)
    {
        return response()->json([
            'success' => false,
            'message' => "Legacy {$action} endpoint is disabled. Use /results/acsee lifecycle snapshot publish/lock workflow instead.",
        ], 410);
    }

    public function index(Request $request)
    {
        $examYear = ExamYear::active()->first();
        $examType = ExamType::where('code', $this->examCode($request))->first();

        $query = CandidateExamRegistration::where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->with('candidate.school', 'candidate.combination');

        // Filters
        if ($request->filled('school_id')) {
            $query->whereHas('candidate', fn($q) => $q->where('school_id', $request->school_id));
        }

        if ($request->filled('combination_id')) {
            $query->whereHas('candidate', fn($q) => $q->where('combination_id', $request->combination_id));
        }

        if ($request->filled('status')) {
            $query->where('result_status', $request->status);
        }

        $results = $query->paginate(20);
        $schools = School::query()
            ->whereHas('candidates.examRegistrations', function ($q) use ($examType, $examYear) {
                $q->where('exam_type_id', $examType->id)
                  ->where('exam_year_id', $examYear->id);
            })
            ->orderBy('name')
            ->get();
        $combinations = Combination::where('exam_type_id', $examType->id)->orderBy('code')->get();

        $statusCounts = CandidateExamRegistration::query()
            ->where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->selectRaw("COALESCE(result_status, 'draft') as status, count(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('results.acsee.results.index', compact(
            'results',
            'schools',
            'combinations',
            'examYear',
            'examType',
            'statusCounts'
        ));
    }

    public function candidateResult(Request $request, $candidateId)
    {
        $candidate = Candidate::findOrFail($candidateId);
        $examYear = ExamYear::active()->first();
        $examType = ExamType::where('code', $this->examCode($request))->first();

        $registration = CandidateExamRegistration::where('candidate_id', $candidateId)
            ->where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->first();

        if (!$registration) {
            abort(404, 'Result not found for this candidate.');
        }

        return view('results.acsee.results.candidate', compact('candidate', 'registration', 'examYear', 'examType'));
    }

    public function schoolResults(Request $request, $schoolId)
    {
        $school = School::findOrFail($schoolId);
        $examYear = ExamYear::active()->first();
        $examType = ExamType::where('code', $this->examCode($request))->first();

        $results = CandidateExamRegistration::whereHas('candidate', fn($q) => $q->where('school_id', $schoolId))
            ->where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->with('candidate')
            ->paginate(20);

        return view('results.acsee.results.school', compact('school', 'results', 'examYear', 'examType'));
    }

    public function combinationResults(Request $request, $combinationId)
    {
        $combination = Combination::findOrFail($combinationId);
        $examYear = ExamYear::active()->first();
        $examType = ExamType::where('code', $this->examCode($request))->first();

        $results = CandidateExamRegistration::whereHas('candidate', fn($q) => $q->where('combination_id', $combinationId))
            ->where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->with('candidate')
            ->paginate(20);

        return view('results.acsee.results.combination', compact('combination', 'results', 'examYear', 'examType'));
    }

    public function publish($id)
    {
        return $this->legacyLifecycleRedirectResponse('publish');
    }

    public function unpublish($id)
    {
        return $this->legacyLifecycleRedirectResponse('unpublish');
    }
}
