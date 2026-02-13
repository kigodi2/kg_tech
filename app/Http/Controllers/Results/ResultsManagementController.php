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
    public function index(Request $request)
    {
        $examYear = ExamYear::active()->first();
        $acsee = ExamType::where('code', 'ACSEE')->first();

        $query = CandidateExamRegistration::where('exam_type_id', $acsee->id)
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

        $results = $query->paginate(15);
        $schools = School::active()->get();
        $combinations = Combination::where('exam_type_id', $acsee->id)->get();

        return view('results.acsee.results.index', compact('results', 'schools', 'combinations', 'examYear'));
    }

    public function candidateResult($candidateId)
    {
        $candidate = Candidate::findOrFail($candidateId);
        $examYear = ExamYear::active()->first();
        $acsee = ExamType::where('code', 'ACSEE')->first();

        $registration = CandidateExamRegistration::where('candidate_id', $candidateId)
            ->where('exam_type_id', $acsee->id)
            ->where('exam_year_id', $examYear->id)
            ->first();

        if (!$registration) {
            abort(404, 'Result not found for this candidate.');
        }

        return view('results.acsee.results.candidate', compact('candidate', 'registration', 'examYear'));
    }

    public function schoolResults($schoolId)
    {
        $school = School::findOrFail($schoolId);
        $examYear = ExamYear::active()->first();
        $acsee = ExamType::where('code', 'ACSEE')->first();

        $results = CandidateExamRegistration::whereHas('candidate', fn($q) => $q->where('school_id', $schoolId))
            ->where('exam_type_id', $acsee->id)
            ->where('exam_year_id', $examYear->id)
            ->with('candidate')
            ->paginate(20);

        return view('results.acsee.results.school', compact('school', 'results', 'examYear'));
    }

    public function combinationResults($combinationId)
    {
        $combination = Combination::findOrFail($combinationId);
        $examYear = ExamYear::active()->first();
        $acsee = ExamType::where('code', 'ACSEE')->first();

        $results = CandidateExamRegistration::whereHas('candidate', fn($q) => $q->where('combination_id', $combinationId))
            ->where('exam_type_id', $acsee->id)
            ->where('exam_year_id', $examYear->id)
            ->with('candidate')
            ->paginate(20);

        return view('results.acsee.results.combination', compact('combination', 'results', 'examYear'));
    }

    public function publish($id)
    {
        $registration = CandidateExamRegistration::findOrFail($id);

        if ($registration->result_status === 'published') {
            return response()->json(['error' => 'Result already published.'], 422);
        }

        if ($registration->result_status !== 'final') {
            return response()->json(['error' => 'Only final results can be published.'], 422);
        }

        $registration->update(['result_status' => 'published', 'published_at' => now()]);

        // Log audit
        \App\Models\AuditLog::create([
            'module' => 'results',
            'action' => 'publish_result',
            'exam_year_id' => $registration->exam_year_id,
            'user_id' => auth()->id(),
            'metadata' => ['candidate_id' => $registration->candidate_id],
        ]);

        return response()->json(['success' => true, 'message' => 'Result published.']);
    }

    public function unpublish($id)
    {
        $registration = CandidateExamRegistration::findOrFail($id);

        if ($registration->result_status !== 'published') {
            return response()->json(['error' => 'Only published results can be unpublished.'], 422);
        }

        $registration->update(['result_status' => 'final', 'published_at' => null]);

        // Log audit
        \App\Models\AuditLog::create([
            'module' => 'results',
            'action' => 'unpublish_result',
            'exam_year_id' => $registration->exam_year_id,
            'user_id' => auth()->id(),
            'metadata' => ['candidate_id' => $registration->candidate_id],
        ]);

        return response()->json(['success' => true, 'message' => 'Result unpublished.']);
    }
}
