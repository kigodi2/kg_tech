<?php

namespace App\Http\Controllers\Results;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Models\School;
use App\Models\ResultProcess;
use App\Models\GradingProfile;
use App\Models\AuditLog;
use Illuminate\Http\Request;

/**
 * ResultsController
 *
 * Main controller for ACSEE results management module.
 * Handles dashboard and overall results workflow.
 */
class ResultsController extends Controller
{
    /**
     * Display ACSEE Results Dashboard
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        // Get active exam year
        $examYear = ExamYear::active()->first();
        if (!$examYear) {
            return view('results.acsee.dashboard', [
                'exam_year' => 'Not Set',
                'metrics' => [],
                'grading_profile' => null,
                'last_processing' => null,
                'linking_status' => [],
                'recent_processing' => collect(),
                'recent_audit_logs' => collect(),
            ]);
        }

        // Get ACSEE exam type
        $acsee = ExamType::where('code', 'ACSEE')->first();

        // Calculate metrics
        $metrics = [
            'total_candidates' => $this->getTotalCandidates($acsee, $examYear),
            'schools_submitted' => $this->getSchoolsSubmitted($acsee, $examYear),
            'total_schools' => School::active()->count(),
            'processing_percentage' => $this->getProcessingPercentage($acsee, $examYear),
            'draft_count' => $this->getResultCount($acsee, $examYear, 'draft'),
            'final_count' => $this->getResultCount($acsee, $examYear, 'final'),
            'published_count' => $this->getResultCount($acsee, $examYear, 'published'),
        ];

        // Get active grading profile
        $gradingProfile = GradingProfile::where('exam_type_id', $acsee->id)
            ->where('exam_year_id', $examYear->id)
            ->where('is_active', true)
            ->first();

        // Get last processing
        $lastProcessing = ResultProcess::where('exam_type_id', $acsee->id)
            ->where('exam_year_id', $examYear->id)
            ->latest('processed_at')
            ->first();

        // Get result linking status
        $linkingStatus = $this->getLinkingStatus($acsee, $examYear);

        // Get recent processing history
        $recentProcessing = ResultProcess::where('exam_type_id', $acsee->id)
            ->where('exam_year_id', $examYear->id)
            ->with('user')
            ->latest('processed_at')
            ->limit(5)
            ->get();

        // Get recent audit logs
        $recentAuditLogs = AuditLog::where('module', 'results')
            ->where('exam_year_id', $examYear->id)
            ->with('user')
            ->latest()
            ->limit(5)
            ->get();

        return view('results.acsee.dashboard', [
            'exam_year' => $examYear->year_label,
            'metrics' => $metrics,
            'grading_profile' => $gradingProfile,
            'last_processing' => $lastProcessing,
            'linking_status' => $linkingStatus,
            'recent_processing' => $recentProcessing,
            'recent_audit_logs' => $recentAuditLogs,
        ]);
    }

    /**
     * Get total ACSEE candidates
     */
    private function getTotalCandidates($examType, $examYear): int
    {
        return CandidateExamRegistration::where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->count();
    }

    /**
     * Get schools that have submitted marks
     */
    private function getSchoolsSubmitted($examType, $examYear): int
    {
        return School::query()
            ->join('candidates', 'schools.id', '=', 'candidates.school_id')
            ->join('candidate_exam_registrations', 'candidates.id', '=', 'candidate_exam_registrations.candidate_id')
            ->where('candidate_exam_registrations.exam_type_id', $examType->id)
            ->where('candidate_exam_registrations.exam_year_id', $examYear->id)
            ->distinct('schools.id')
            ->count('schools.id');
    }

    /**
     * Get processing percentage
     */
    private function getProcessingPercentage($examType, $examYear): int
    {
        $total = $this->getTotalCandidates($examType, $examYear);
        if ($total === 0) {
            return 0;
        }

        // Count candidates with grades assigned
        $graded = \DB::table('candidate_exam_registrations')
            ->where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->where('grade', '!=', null)
            ->count();

        return (int)(($graded / $total) * 100);
    }

    /**
     * Get result count by status
     */
    private function getResultCount($examType, $examYear, $status): int
    {
        return \DB::table('candidate_exam_registrations')
            ->where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->where('result_status', $status)
            ->count();
    }

    /**
     * Get result linking status
     */
    private function getLinkingStatus($examType, $examYear): array
    {
        $total = $this->getTotalCandidates($examType, $examYear);
        
        if ($total === 0) {
            return [
                'is_complete' => true,
                'missing_count' => 0,
                'invalid_combinations' => 0,
            ];
        }

        // Check for missing links
        $missingLinks = Candidate::query()
            ->whereHas('examRegistrations', function ($q) use ($examType, $examYear) {
                $q->where('exam_type_id', $examType->id)
                  ->where('exam_year_id', $examYear->id);
            })
            ->where(function ($q) {
                $q->whereNull('combination')
                  ->orWhere('combination', '');
            })
            ->count();

        // Check for invalid combinations
        $invalidCombos = \DB::table('candidates')
            ->join('candidate_exam_registrations', 'candidates.id', '=', 'candidate_exam_registrations.candidate_id')
            ->where('candidate_exam_registrations.exam_type_id', $examType->id)
            ->where('candidate_exam_registrations.exam_year_id', $examYear->id)
            ->whereNotExists(function ($q) {
                $q->select('id')
                  ->from('combinations')
                  ->whereRaw('UPPER(combinations.code) = UPPER(candidates.combination)');
            })
            ->where('candidates.combination', '!=', null)
            ->distinct('candidates.id')
            ->count('candidates.id');

        return [
            'is_complete' => $missingLinks === 0 && $invalidCombos === 0,
            'missing_count' => $missingLinks,
            'invalid_combinations' => $invalidCombos,
        ];
    }
}
