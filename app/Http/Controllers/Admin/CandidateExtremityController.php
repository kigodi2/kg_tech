<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Models\CandidateExtremityAnalysis;
use App\Services\Extremity\CandidateCrossSubjectAnalysisService;
use Illuminate\Http\Request;

class CandidateExtremityController extends Controller
{
    public function __construct(private CandidateCrossSubjectAnalysisService $analysisService)
    {
    }

    /**
     * Trigger candidate cross-subject analysis
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'exam_year_id' => 'required|exists:exam_years,id',
            'exam_type_id' => 'required|exists:exam_types,id',
            'subject_id' => 'nullable|exists:subjects,id',
        ]);

        try {
            $examYear = ExamYear::findOrFail($request->exam_year_id);
            $examType = ExamType::findOrFail($request->exam_type_id);

            // If subject_id provided, pass it to the service
            if ($request->subject_id) {
                $this->analysisService->analyzeCandidates($examYear, $examType, $request->subject_id);
            } else {
                $this->analysisService->analyzeCandidates($examYear, $examType);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cross-subject analysis completed',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Analysis failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get dashboard with flagged candidates
     */
    public function dashboard(Request $request)
    {
        $query = CandidateExtremityAnalysis::query();

        if ($request->exam_year_id) {
            $query->where('exam_year_id', $request->exam_year_id);
        }

        if ($request->risk_level) {
            $query->where('risk_level', $request->risk_level);
        }

        if (!$request->reviewed_only) {
            $query->where('reviewed', false);
        }

        // Filter by subject if provided
        if ($request->subject_id) {
            $query->whereHas('subjectOutliers', function ($q) {
                $q->where('subject_id', request('subject_id'));
            });
        }

        $reports = $query
            ->with(['candidate.school', 'examYear', 'examType', 'subjectOutliers.subject'])
            ->orderBy('risk_level', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $summary = [
            'total_flagged' => $query->count(),
            'high_risk' => $query->clone()->where('risk_level', 'High')->count(),
            'moderate_risk' => $query->clone()->where('risk_level', 'Moderate')->count(),
            'low_risk' => $query->clone()->where('risk_level', 'Low')->count(),
            'pending_review' => $query->clone()->where('reviewed', false)->count(),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'reports' => $reports,
        ]);
    }

    /**
     * Get detailed candidate analysis
     */
    public function show(CandidateExtremityAnalysis $report)
    {
        $report->load([
            'candidate.school',
            'examYear',
            'examType',
            'subjectOutliers.subject',
            'reviewedBy',
        ]);

        $subjectAnalysis = $report->subjectOutliers->map(function ($outlier) {
            return [
                'subject_id' => $outlier->subject_id,
                'subject_code' => $outlier->subject->code,
                'subject_name' => $outlier->subject->name,
                'score' => $outlier->score,
                'candidate_average' => $outlier->candidate_average,
                'deviation' => $outlier->deviation_from_average,
                'deviation_percentage' => $outlier->deviation_percentage,
                'zscore' => $outlier->zscore,
                'type' => $outlier->outlier_type,
            ];
        })->toArray();

        return response()->json([
            'success' => true,
            'candidate' => [
                'id' => $report->candidate->id,
                'index_number' => $report->candidate->candidate_id,
                'name' => $report->candidate->full_name,
                'school' => $report->candidate->school,
            ],
            'analysis' => [
                'combination' => $report->combination,
                'subjects_count' => $report->subject_count,
                'average_score' => $report->average_score,
                'std_dev' => $report->std_dev_across_subjects,
                'outlier_subjects' => $subjectAnalysis,
                'flags' => $report->flags,
                'risk_level' => $report->risk_level,
            ],
            'review' => [
                'reviewed' => $report->reviewed,
                'reviewed_at' => $report->reviewed_at,
                'reviewed_by' => $report->reviewedBy,
                'notes' => $report->review_notes,
            ],
        ]);
    }

    /**
     * Mark candidate analysis as reviewed
     */
    public function markReviewed(Request $request, CandidateExtremityAnalysis $report)
    {
        $request->validate([
            'action' => 'required|in:marked_for_investigation,no_action_needed,data_corrected',
            'notes' => 'nullable|string|max:500',
        ]);

        $report->markReviewed(
            auth()->user(),
            json_encode([
                'action' => $request->action,
                'notes' => $request->notes,
                'timestamp' => now(),
            ])
        );

        return response()->json([
            'success' => true,
            'message' => 'Analysis marked as reviewed',
        ]);
    }

    /**
     * Export flagged candidates
     */
    public function export(Request $request)
    {
        $query = CandidateExtremityAnalysis::query();

        if ($request->exam_year_id) {
            $query->where('exam_year_id', $request->exam_year_id);
        }

        if ($request->risk_level) {
            $query->where('risk_level', $request->risk_level);
        }

        // Filter by subject if provided
        if ($request->subject_id) {
            $query->whereHas('subjectOutliers', function ($q) {
                $q->where('subject_id', request('subject_id'));
            });
        }

        $reports = $query
            ->with(['candidate.school', 'subjectOutliers.subject'])
            ->get();

        $csv = "Candidate Index,Name,School,Combination,Subjects,Avg Score,Outlier Count,Risk Level,Flagged Subjects,Deviation %\n";

        foreach ($reports as $report) {
            $outlierSubjects = $report->subjectOutliers
                ->map(fn($o) => $o->subject->code . ' (' . $o->outlier_type . ')')
                ->implode(';');

            $deviations = $report->subjectOutliers
                ->map(fn($o) => $o->deviation_percentage . '%')
                ->implode(';');

            $csv .= "\"{$report->candidate->candidate_id}\",\"{$report->candidate->full_name}\",\"{$report->candidate->school->name}\",\"{$report->combination}\",{$report->subject_count},{$report->average_score},{$report->outlier_subject_count},{$report->risk_level},\"{$outlierSubjects}\",\"{$deviations}\"\n";
        }

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="candidate_extremity_' . now()->format('Y-m-d') . '.csv"');
    }
}
