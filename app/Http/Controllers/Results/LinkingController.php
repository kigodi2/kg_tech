<?php

namespace App\Http\Controllers\Results;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Combination;
use App\Models\ExamYear;
use App\Models\ExamType;
use Illuminate\Http\Request;

/**
 * LinkingController
 *
 * Pre-processing validation: ensures candidates are properly linked to
 * schools, combinations, and subjects before result processing.
 */
class LinkingController extends Controller
{
    public function index()
    {
        $examYear = ExamYear::active()->first();
        $acsee = ExamType::where('code', 'ACSEE')->first();

        $report = $this->generateLinkingReport($acsee, $examYear);

        return view('results.acsee.linking.index', compact('report', 'examYear'));
    }

    public function validateLinks(Request $request)
    {
        $examYear = ExamYear::active()->first();
        $acsee = ExamType::where('code', 'ACSEE')->first();

        $issues = [];

        // Check 1: Missing school links
        $missingSchools = Candidate::whereNull('school_id')
            ->whereHas('examRegistrations', fn($q) => $q->where('exam_type_id', $acsee->id)->where('exam_year_id', $examYear->id))
            ->count();

        if ($missingSchools > 0) {
            $issues[] = "❌ $missingSchools candidates missing school assignment";
        }

        // Check 2: Missing combinations
        $missingCombinations = Candidate::whereNull('combination')
            ->orWhere('combination', '')
            ->whereHas('examRegistrations', fn($q) => $q->where('exam_type_id', $acsee->id)->where('exam_year_id', $examYear->id))
            ->count();

        if ($missingCombinations > 0) {
            $issues[] = "❌ $missingCombinations candidates missing subject combination";
        }

        // Check 3: Invalid combinations
        $invalidCombos = Candidate::whereHas('examRegistrations', fn($q) => $q->where('exam_type_id', $acsee->id)->where('exam_year_id', $examYear->id))
            ->whereNotNull('combination')
            ->where('combination', '!=', '')
            ->whereNotExists(function ($q) {
                $q->select('id')->from('combinations')
                    ->whereRaw('UPPER(combinations.code) = UPPER(candidates.combination)');
            })
            ->count();

        if ($invalidCombos > 0) {
            $issues[] = "❌ $invalidCombos candidates have invalid subject combinations";
        }

        // Check 4: Missing subject selections
        $missingSubjects = Candidate::whereHas('examRegistrations', fn($q) => $q->where('exam_type_id', $acsee->id)->where('exam_year_id', $examYear->id))
            ->whereDoesntHave('subjectSelections', fn($q) => $q->where('exam_year_id', $examYear->id))
            ->count();

        if ($missingSubjects > 0) {
            $issues[] = "❌ $missingSubjects candidates missing subject selections";
        }

        return response()->json([
            'valid' => count($issues) === 0,
            'issues' => $issues,
        ]);
    }

    public function fixMissing(Request $request)
    {
        $request->validate(['type' => 'required|in:schools,combinations,subjects']);

        $examYear = ExamYear::active()->first();
        $acsee = ExamType::where('code', 'ACSEE')->first();

        $fixed = 0;

        if ($request->type === 'combinations') {
            // Auto-assign default combination if only one exists
            $defaultCombo = Combination::where('exam_type_id', $acsee->id)
                ->where('is_default', true)
                ->first();

            if ($defaultCombo) {
                // Update both combination code and combination_id FK together
                $candidates = Candidate::whereNull('combination')
                    ->whereHas('examRegistrations', fn($q) => $q->where('exam_type_id', $acsee->id)->where('exam_year_id', $examYear->id))
                    ->get();

                foreach ($candidates as $candidate) {
                    $candidate->update([
                        'combination' => $defaultCombo->code,
                        'combination_id' => $defaultCombo->id,
                    ]);
                    $fixed++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'fixed_count' => $fixed,
            'message' => "$fixed records fixed.",
        ]);
    }

    public function report()
    {
        $examYear = ExamYear::active()->first();
        $acsee = ExamType::where('code', 'ACSEE')->first();

        $report = $this->generateLinkingReport($acsee, $examYear);

        return response()->json($report);
    }

    private function generateLinkingReport($acsee, $examYear)
    {
        $totalCandidates = Candidate::whereHas(
            'examRegistrations',
            fn($q) => $q->where('exam_type_id', $acsee->id)->where('exam_year_id', $examYear->id)
        )->count();

        return [
            'total_candidates' => $totalCandidates,
            'missing_schools' => $this->countMissingSchools($acsee, $examYear),
            'missing_combinations' => $this->countMissingCombinations($acsee, $examYear),
            'invalid_combinations' => $this->countInvalidCombinations($acsee, $examYear),
            'missing_subjects' => $this->countMissingSubjects($acsee, $examYear),
            'is_complete' => $this->isLinkingComplete($acsee, $examYear),
        ];
    }

    private function countMissingSchools($acsee, $examYear)
    {
        return 0;
    }
    private function countMissingCombinations($acsee, $examYear)
    {
        return 0;
    }
    private function countInvalidCombinations($acsee, $examYear)
    {
        return 0;
    }
    private function countMissingSubjects($acsee, $examYear)
    {
        return 0;
    }
    private function isLinkingComplete($acsee, $examYear)
    {
        return true;
    }
}
