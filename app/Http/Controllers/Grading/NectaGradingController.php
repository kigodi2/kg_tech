<?php

namespace App\Http\Controllers\Grading;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\FinalGrade;
use App\Services\Results\NectaGradingService;
use Illuminate\Http\Request;

class NectaGradingController extends Controller
{
    private NectaGradingService $gradingService;

    public function __construct(NectaGradingService $gradingService)
    {
        $this->gradingService = $gradingService;
    }

    /**
     * Display grading dashboard
     */
    public function dashboard()
    {
        $examYears = ExamYear::where('is_active', true)->get();
        $examTypes = ExamType::all();

        return view('grading.dashboard', compact('examYears', 'examTypes'));
    }

    /**
     * Get candidate results/grades
     */
    public function candidateResults(Candidate $candidate, Request $request)
    {
        $examTypeId = $request->input('exam_type_id', 1); // Default to ACSEE
        $year = $request->input('year', now()->year);

        $report = $this->gradingService->generateGradingReport($candidate, $examTypeId, $year);

        if (!$report) {
            return response()->json(['error' => 'No marks found for this candidate'], 404);
        }

        return view('grading.candidate-results', compact('report', 'candidate'));
    }

    /**
     * API: Get candidate grades (JSON)
     */
    public function apiCandidateGrades(Candidate $candidate, Request $request)
    {
        $examTypeId = $request->input('exam_type_id', 1);
        $year = $request->input('year', now()->year);

        $report = $this->gradingService->generateGradingReport($candidate, $examTypeId, $year);

        if (!$report) {
            return response()->json(['error' => 'No marks found'], 404);
        }

        return response()->json($report);
    }

    /**
     * Calculate grade for marks
     */
    public function apiCalculateGrade(Request $request)
    {
        $validated = $request->validate([
            'marks' => 'required|numeric|min:0|max:100',
            'exam_type' => 'nullable|string|in:ACSEE,CSEE',
        ]);
        $marks = (float) $validated['marks'];
        $examTypeCode = strtoupper((string) ($validated['exam_type'] ?? 'ACSEE'));

        $grade = $this->gradingService->calculateGradeForExamType($marks, $examTypeCode);
        $competence = $this->gradingService->getCompetenceLevelForExamType($grade, $examTypeCode);
        $points = $this->gradingService->getGradePointsForExamType($grade, $examTypeCode);
        $color = $this->gradingService->getGradeColorForExamType($grade, $examTypeCode);

        return response()->json([
            'marks' => $marks,
            'exam_type' => $examTypeCode,
            'grade' => $grade,
            'competence' => $competence,
            'competence_level' => "Grade {$grade} ({$competence})",
            'points' => $points,
            'color' => $color,
        ]);
    }

    /**
     * Get school/centre grading statistics
     */
    public function schoolGradingStats(Request $request)
    {
        $schoolId = $request->input('school_id');
        $examTypeId = $request->input('exam_type_id', 1);
        $year = $request->input('year', now()->year);

        $candidates = Candidate::where('school_id', $schoolId)
            ->with(['marks.subject', 'examRegistrations'])
            ->get();

        $results = [];
        $stats = [
            'total_candidates' => 0,
            'graded_candidates' => 0,
            'average_gpa' => 0,
            'division_distribution' => [],
            'grade_distribution' => [],
        ];

        foreach ($candidates as $candidate) {
            $report = $this->gradingService->generateGradingReport($candidate, $examTypeId, $year);

            if ($report) {
                $stats['total_candidates']++;
                $stats['graded_candidates']++;
                $results[] = $report;

                // Accumulate for average
                if ($report['gpa']) {
                    $stats['average_gpa'] += $report['gpa'];
                }

                // Division distribution
                if ($report['division']) {
                    $div = $report['division']['division'];
                    $stats['division_distribution'][$div] = ($stats['division_distribution'][$div] ?? 0) + 1;
                }

                // Grade distribution
                $grade = $report['overall_grade'];
                $stats['grade_distribution'][$grade] = ($stats['grade_distribution'][$grade] ?? 0) + 1;
            }
        }

        // Calculate average GPA
        if ($stats['graded_candidates'] > 0) {
            $stats['average_gpa'] = round($stats['average_gpa'] / $stats['graded_candidates'], 2);
        }

        return response()->json([
            'statistics' => $stats,
            'results' => $results,
        ]);
    }

    /**
     * Get all grades reference data
     */
    public function apiGradeReference()
    {
        $examTypeCode = strtoupper((string) request()->input('exam_type', 'ACSEE'));

        return response()->json([
            'exam_type' => $examTypeCode,
            'grade_boundaries' => $this->gradingService->getGradeBoundariesForExamType($examTypeCode),
            'grade_points' => $this->gradingService->getGradePointsMappingForExamType($examTypeCode),
            'division_boundaries' => $this->gradingService->getDivisionBoundariesForExamType($examTypeCode),
            'excluded_subjects' => $this->gradingService->getExcludedSubjects(),
        ]);
    }

    /**
     * Store/Update grades for candidate
     */
    public function storeGrades(Candidate $candidate, Request $request)
    {
        $examTypeId = $request->input('exam_type_id', 1);
        $year = $request->input('year', now()->year);

        $report = $this->gradingService->generateGradingReport($candidate, $examTypeId, $year);

        if (!$report) {
            return response()->json(['error' => 'Unable to generate grades'], 400);
        }

        // Store in FinalGrade table
        $finalGrade = FinalGrade::updateOrCreate(
            [
                'candidate_id' => $candidate->id,
                'exam_type_id' => $examTypeId,
                'year' => $year,
            ],
            [
                'overall_grade' => $report['overall_grade'],
                'total_marks' => $report['total_marks'],
                'grade_points' => $report['total_points'],
                'gpa' => $report['gpa'],
                'is_published' => false,
            ]
        );

        return response()->json([
            'message' => 'Grades stored successfully',
            'final_grade' => $finalGrade,
            'report' => $report,
        ]);
    }

    /**
     * Batch process grades for exam year
     */
    public function batchProcessGrades(Request $request)
    {
        $examTypeId = $request->input('exam_type_id');
        $year = $request->input('year');
        $schoolId = $request->input('school_id');

        if (!$examTypeId || !$year) {
            return response()->json(['error' => 'exam_type_id and year required'], 400);
        }

        $results = $this->gradingService->processBatchGrading($examTypeId, $year, $schoolId);

        $stored = 0;
        foreach ($results as $report) {
            FinalGrade::updateOrCreate(
                [
                    'candidate_id' => $report['candidate_id'],
                    'exam_type_id' => $examTypeId,
                    'year' => $year,
                ],
                [
                    'overall_grade' => $report['overall_grade'],
                    'total_marks' => $report['total_marks'],
                    'grade_points' => $report['total_points'],
                    'gpa' => $report['gpa'],
                ]
            );
            $stored++;
        }

        return response()->json([
            'message' => "Processed {$stored} candidates",
            'processed' => $stored,
            'results' => $results,
        ]);
    }

    /**
     * Publish grades (make visible)
     */
    public function publishGrades(Request $request)
    {
        $examTypeId = $request->input('exam_type_id');
        $year = $request->input('year');

        $updated = FinalGrade::where('exam_type_id', $examTypeId)
            ->where('year', $year)
            ->update([
                'is_published' => true,
                'published_at' => now(),
            ]);

        return response()->json([
            'message' => "Published {$updated} grade records",
            'published' => $updated,
        ]);
    }

    /**
     * Lock grades (prevent modifications)
     */
    public function lockGrades(Request $request)
    {
        $examTypeId = $request->input('exam_type_id');
        $year = $request->input('year');

        $updated = FinalGrade::where('exam_type_id', $examTypeId)
            ->where('year', $year)
            ->update([
                'is_locked' => true,
                'locked_at' => now(),
            ]);

        return response()->json([
            'message' => "Locked {$updated} grade records",
            'locked' => $updated,
        ]);
    }
}
