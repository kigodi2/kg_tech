<?php

namespace App\Services\Results;

use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\CandidateSubjectSelection;
use App\Models\SubjectMarks;
use Illuminate\Support\Facades\Log;

/**
 * GradeCalculationService
 * 
 * Automatically calculates and updates grades, GPA, division, and points
 * for candidates after marks are imported.
 * 
 * This service integrates with NectaGradingService to ensure all grades
 * follow NECTA standards.
 */
class GradeCalculationService
{
    private NectaGradingService $gradingService;

    public function __construct(NectaGradingService $gradingService)
    {
        $this->gradingService = $gradingService;
    }

    /**
     * Calculate grades for a single candidate for a specific exam year
     * Called after marks are imported for that candidate
     */
    public function calculateForCandidate(int $candidateId, int $examYearId, int $examTypeId): bool
    {
        try {
            $candidate = Candidate::find($candidateId);
            if (!$candidate) {
                Log::warning("Candidate not found for grade calculation: {$candidateId}");
                return false;
            }

            // Get the exam year to get the actual year value
            $examYear = \App\Models\ExamYear::find($examYearId);
            if (!$examYear) {
                Log::warning("Exam year not found: {$examYearId}");
                return false;
            }

            // Get all marks for this candidate in this exam year
            // Note: SubjectMarks uses 'year' column which stores the numeric year value
            // ExamYear has 'year_label' field
            $yearValue = $examYear->year_label ?? $examYear->year;
            if (!$yearValue) {
                Log::warning("Exam year has no year value: {$examYearId}");
                return false;
            }

            $marks = SubjectMarks::where('candidate_id', $candidateId)
                ->where('year', $yearValue)
                ->where('exam_type_id', $examTypeId)
                ->with('subject')
                ->get();

            if ($marks->isEmpty()) {
                Log::info("No marks found for candidate {$candidateId} in exam year {$examYearId}");
                return false;
            }

            // Calculate grades for each subject mark
            $totalMarks = 0;
            $totalPoints = 0;
            $gpaPoints = 0;
            $validSubjectCount = 0;
            $incSubjectCount = 0;
            $subjectGrades = [];
            $principalSubjectIds = CandidateSubjectSelection::query()
                ->where('candidate_id', $candidateId)
                ->where('exam_type_id', $examTypeId)
                ->where(function ($q) use ($examYearId, $yearValue) {
                    $q->where('exam_year_id', $examYearId)
                      ->orWhere('year', $yearValue);
                })
                ->where('is_active', true)
                ->where('is_principal', true)
                ->pluck('subject_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
            $coreSubjectIds = !empty($principalSubjectIds) ? $principalSubjectIds : null;

            foreach ($marks as $mark) {
                // Skip INC and X/ABS subjects — they must NOT be graded as 0
                $subjectStatus = $mark->subject_status ?? null;
                if (in_array($subjectStatus, ['INC', 'X', 'ABS'], true)) {
                    if ($subjectStatus === 'INC') $incSubjectCount++;
                    continue;
                }

                // Get the final mark - use marks_obtained if available, otherwise use percentage
                $finalMark = $mark->marks_obtained ?? $mark->percentage;
                
                // Skip marks that haven't been obtained yet
                if ($finalMark === null) {
                    continue;
                }

                // Calculate grade from marks
                $grade = $this->gradingService->calculateGrade($finalMark);
                
                // Update the subject_marks record with calculated grade
                $mark->update(['grade' => $grade]);

                // Add to totals
                $totalMarks += $finalMark;

                // Calculate points only for non-excluded subjects
                $subjectName = $mark->subject?->name ?? '';
                if (!$this->gradingService->isExcludedSubject($subjectName)) {
                    $points = $this->gradingService->getGradePoints($grade);
                    $gpaPoints += $points;
                    $validSubjectCount++;
                    $subjectGrades[] = [
                        'subject_id' => $mark->subject_id,
                        'subject_name' => $subjectName,
                        'grade' => $grade,
                        'points' => $points,
                    ];
                }
            }

            $aggtPoints = $this->gradingService->calculateAggtFromSubjectGrades($subjectGrades, $coreSubjectIds);
            $totalPoints = $aggtPoints ?? 0;
            $principalPassCount = $this->gradingService->countPrincipalPassesFromSubjectGrades($subjectGrades, $coreSubjectIds);

            // Calculate GPA (4 decimal places for precision)
            $gpa = $validSubjectCount > 0 ? round($gpaPoints / $validSubjectCount, 4) : 0;

            // Calculate division using AGGT division bands.
            $divisionInfo = $totalPoints > 0
                ? $this->gradingService->calculateDivisionWithEligibility(
                    (float) $totalPoints,
                    (int) $principalPassCount
                )
                : ['division' => 0, 'competence' => 'Fail', 'points' => 0];
            $division = $divisionInfo ? $divisionInfo['division'] : 'O';

            // Update the exam registration with calculated values
            $registration = CandidateExamRegistration::where('candidate_id', $candidateId)
                ->where('exam_year_id', $examYearId)
                ->where('exam_type_id', $examTypeId)
                ->first();

            if ($registration) {
                $registration->update([
                    'total_marks' => $totalMarks,
                    'total_points' => $totalPoints,
                    'gpa' => $gpa,
                    'division' => $division,
                    'grade' => $this->getOverallGrade($marks),
                ]);

                Log::info("Grades calculated for candidate {$candidateId}: GPA={$gpa}, Division={$division}");
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error("Error calculating grades for candidate {$candidateId}", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Calculate grades for all candidates in a school for a specific exam year
     * Called after bulk import completes
     */
    public function calculateForSchool(int $schoolId, int $examYearId, int $examTypeId): array
    {
        $candidates = Candidate::where('school_id', $schoolId)
            ->get(['id']);

        $results = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
        ];

        foreach ($candidates as $candidate) {
            $results['total']++;
            if ($this->calculateForCandidate($candidate->id, $examYearId, $examTypeId)) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        Log::info("Batch grade calculation completed for school {$schoolId}", $results);
        return $results;
    }

    /**
     * Calculate grades for all candidates in an exam year
     * Called after major bulk import operations
     */
    public function calculateForExamYear(int $examYearId, int $examTypeId): array
    {
        $candidates = Candidate::whereHas('examRegistrations', function ($q) use ($examYearId, $examTypeId) {
            $q->where('exam_year_id', $examYearId)
              ->where('exam_type_id', $examTypeId);
        })->get(['id']);

        $results = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
        ];

        foreach ($candidates as $candidate) {
            $results['total']++;
            if ($this->calculateForCandidate($candidate->id, $examYearId, $examTypeId)) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        Log::info("Batch grade calculation completed for exam year {$examYearId}", $results);
        return $results;
    }

    /**
     * Get the overall grade for a candidate (best grade among subjects)
     */
    private function getOverallGrade($marks): string
    {
        $bestGrade = 'F';
        $bestPoints = 7;

        foreach ($marks as $mark) {
            $subjectStatus = strtoupper((string) ($mark->subject_status ?? ''));
            if (in_array($subjectStatus, ['INC', 'ABS', 'X'], true)) {
                continue;
            }

            $grade = $mark->grade;
            if (!$grade) {
                if ($mark->marks_obtained === null) {
                    continue;
                }
                $grade = $this->gradingService->calculateGrade((float) $mark->marks_obtained);
            }

            if (in_array(strtoupper((string) $grade), ['INC', 'ABS', 'X'], true)) {
                continue;
            }

            $points = $this->gradingService->getGradePoints($grade);

            if ($points < $bestPoints) {
                $bestPoints = $points;
                $bestGrade = $grade;
            }
        }

        return $bestGrade;
    }
}
