<?php

namespace App\Services\Results;

use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\Subject;
use App\Models\SubjectMarks;
use App\Models\GradingProfile;
use App\Models\FinalGrade;
use Illuminate\Database\Eloquent\Collection;

/**
 * NectaGradingService
 * 
 * Implements NECTA grading system where:
 * - GENERAL STUDIES and BASIC APPLIED MATHEMATICS are excluded from GPA and total points
 * - These subjects are only included in TOTAL MARKS calculation
 * - All calculations follow NECTA's official grading methodology
 */
class NectaGradingService
{
    // NECTA Grade Boundaries (Marks to Grade)
    // Boundaries shifted by 0.5 to handle decimal marks correctly
    // 79.5+ = A, 69.5-79.49... = B, etc.
    private const GRADE_BOUNDARIES = [
        ['min' => 79.5, 'max' => 100, 'grade' => 'A', 'competence' => 'Excellent', 'color' => '#00AA7A'],
        ['min' => 69.5, 'max' => 79.49, 'grade' => 'B', 'competence' => 'Very Good', 'color' => '#1FEE0B'],
        ['min' => 59.5, 'max' => 69.49, 'grade' => 'C', 'competence' => 'Good', 'color' => '#1FEE0B'],
        ['min' => 49.5, 'max' => 59.49, 'grade' => 'D', 'competence' => 'Average', 'color' => '#EF7043'],
        ['min' => 39.5, 'max' => 49.49, 'grade' => 'E', 'competence' => 'Satisfactory', 'color' => '#DEF043'],
        ['min' => 34.5, 'max' => 39.49, 'grade' => 'S', 'competence' => 'Unsatisfactory', 'color' => '#FF7272'],
        ['min' => 0, 'max' => 34.49, 'grade' => 'F', 'competence' => 'Fail', 'color' => '#FF272F'],
    ];

    // NECTA Points Mapping (Grade to Points)
    private const GRADE_POINTS = [
        'A' => 1,
        'B' => 2,
        'C' => 3,
        'D' => 4,
        'E' => 5,
        'S' => 6,
        'F' => 7,
    ];

    // NECTA Division Points Range
    private const DIVISION_BOUNDARIES = [
        ['min' => 3, 'max' => 9, 'division' => 1, 'competence' => 'Excellent'],
        ['min' => 10, 'max' => 12, 'division' => 2, 'competence' => 'Very Good'],
        ['min' => 13, 'max' => 17, 'division' => 3, 'competence' => 'Good'],
        ['min' => 18, 'max' => 19, 'division' => 4, 'competence' => 'Average'],
        ['min' => 20, 'max' => 21, 'division' => 0, 'competence' => 'Fail'],
    ];

    // NECTA GPA Competence Mapping (for examination centre GPA display)
    // GPA is based on average of grade points (A=1, B=2, C=3, D=4, E=5, S=6, F=7)
    private const GPA_COMPETENCE = [
        ['min' => 1.0, 'max' => 1.4, 'grade' => 'A', 'competence' => 'Excellent', 'color' => '#00A82A'],
        ['min' => 1.5, 'max' => 2.4, 'grade' => 'B', 'competence' => 'Very Good', 'color' => '#1FEE0B'],
        ['min' => 2.5, 'max' => 3.4, 'grade' => 'C', 'competence' => 'Good', 'color' => '#1FEE0B'],
        ['min' => 3.5, 'max' => 4.4, 'grade' => 'D', 'competence' => 'Average', 'color' => '#DEF043'],
        ['min' => 4.5, 'max' => 5.4, 'grade' => 'E', 'competence' => 'Satisfactory', 'color' => '#DEF043'],
        ['min' => 5.5, 'max' => 6.4, 'grade' => 'S', 'competence' => 'Unsatisfactory', 'color' => '#FF772F'],
        ['min' => 6.5, 'max' => 7.0, 'grade' => 'F', 'competence' => 'Fail', 'color' => '#FF272F'],
    ];

    // Subjects to exclude from GPA and total points
    private const EXCLUDED_SUBJECTS = [
        'GENERAL STUDIES',
        'BASIC APPLIED MATHEMATICS',
    ];

    /**
     * Calculate grade for marks
     * Marks stored as-is (e.g., 79.5, 79.4)
     */
    public function calculateGrade(float $marks): string
    {
        foreach (self::GRADE_BOUNDARIES as $boundary) {
            if ($marks >= $boundary['min'] && $marks <= $boundary['max']) {
                return $boundary['grade'];
            }
        }
        return 'F';
    }

    /**
     * Get competence level for grade
     */
    public function getCompetenceLevel(string $grade): string
    {
        foreach (self::GRADE_BOUNDARIES as $boundary) {
            if ($boundary['grade'] === $grade) {
                return $boundary['competence'];
            }
        }
        return 'Unknown';
    }

    /**
     * Get color code (hex) for grade
     * Returns NECTA standard color for competence level display
     */
    public function getGradeColor(string $grade): string
    {
        foreach (self::GRADE_BOUNDARIES as $boundary) {
            if ($boundary['grade'] === $grade) {
                return $boundary['color'];
            }
        }
        return '#CCCCCC'; // Default gray if not found
    }

    /**
     * Get points for grade
     */
    public function getGradePoints(string $grade): int
    {
        return self::GRADE_POINTS[$grade] ?? 7;
    }

    /**
     * Get all grade boundaries
     */
    public function getGradeBoundaries(): array
    {
        return self::GRADE_BOUNDARIES;
    }

    /**
     * Get all grade points mapping
     */
    public function getGradePointsMapping(): array
    {
        return self::GRADE_POINTS;
    }

    /**
     * Check if subject is excluded from GPA/points calculation
     */
    public function isExcludedSubject(string $subjectName): bool
    {
        return in_array(strtoupper($subjectName), self::EXCLUDED_SUBJECTS);
    }

    /**
     * Get list of excluded subjects
     */
    public function getExcludedSubjects(): array
    {
        return self::EXCLUDED_SUBJECTS;
    }

    /**
     * Calculate candidate's total marks (including excluded subjects)
     */
    public function calculateTotalMarks(Candidate $candidate, int $examTypeId, int $year): ?float
    {
        $marks = $candidate->marks()
            ->where('exam_type_id', $examTypeId)
            ->where('year', $year)
            ->get();

        if ($marks->isEmpty()) {
            return null;
        }

        return $marks->sum('marks_obtained');
    }

    /**
     * Calculate candidate's total points (excluding specified subjects)
     */
    public function calculateTotalPoints(Candidate $candidate, int $examTypeId, int $year): ?float
    {
        $marks = $candidate->marks()
            ->with('subject')
            ->where('exam_type_id', $examTypeId)
            ->where('year', $year)
            ->get();

        if ($marks->isEmpty()) {
            return null;
        }

        $totalPoints = 0;
        $validSubjectCount = 0;

        foreach ($marks as $mark) {
            $subjectName = $mark->subject->name ?? '';

            // Skip excluded subjects
            if ($this->isExcludedSubject($subjectName)) {
                continue;
            }

            $grade = $this->calculateGrade($mark->marks_obtained);
            $points = $this->getGradePoints($grade);

            $totalPoints += $points;
            $validSubjectCount++;
        }

        return $validSubjectCount > 0 ? $totalPoints : null;
    }

    /**
     * Calculate candidate's GPA
     * GPA = Total Points / Number of valid subjects (excluding general studies and basic applied math)
     */
    public function calculateGPA(Candidate $candidate, int $examTypeId, int $year): ?float
    {
        $marks = $candidate->marks()
            ->with('subject')
            ->where('exam_type_id', $examTypeId)
            ->where('year', $year)
            ->get();

        if ($marks->isEmpty()) {
            return null;
        }

        $totalPoints = 0;
        $validSubjectCount = 0;

        foreach ($marks as $mark) {
            $subjectName = $mark->subject->name ?? '';

            // Skip excluded subjects from GPA calculation
            if ($this->isExcludedSubject($subjectName)) {
                continue;
            }

            $grade = $this->calculateGrade($mark->marks_obtained);
            $points = $this->getGradePoints($grade);

            $totalPoints += $points;
            $validSubjectCount++;
        }

        if ($validSubjectCount === 0) {
            return null;
        }

        // GPA is calculated as average of points
        return round($totalPoints / $validSubjectCount, 2);
    }

    /**
     * Calculate candidate's division based on total points
     */
    public function calculateDivision(float $totalPoints): ?array
    {
        // Handle 0 points (ABS/INC)
        if ($totalPoints == 0) {
            return [
                'division' => 0,
                'competence' => 'Fail',
                'points' => $totalPoints,
            ];
        }

        foreach (self::DIVISION_BOUNDARIES as $boundary) {
            if ($totalPoints >= $boundary['min'] && $totalPoints <= $boundary['max']) {
                return [
                    'division' => $boundary['division'],
                    'competence' => $boundary['competence'],
                    'points' => $totalPoints,
                ];
            }
        }

        return null;
    }

    /**
     * Get GPA competence level from GPA value
     * Maps a GPA (average of grade points) to its competence level
     */
    public function getGpaCompetence(float $gpa): array
    {
        foreach (self::GPA_COMPETENCE as $boundary) {
            if ($gpa >= $boundary['min'] && $gpa <= $boundary['max']) {
                return [
                    'grade' => $boundary['grade'],
                    'competence' => $boundary['competence'],
                    'color' => $boundary['color'],
                ];
            }
        }

        // Default to Fail if out of range
        return [
            'grade' => 'F',
            'competence' => 'Fail',
            'color' => '#FF272F',
        ];
    }

    /**
     * Get division boundaries
     */
    public function getDivisionBoundaries(): array
    {
        return self::DIVISION_BOUNDARIES;
    }

    /**
     * Calculate overall grade for candidate (usually best grade among subjects)
     */
    public function calculateOverallGrade(Candidate $candidate, int $examTypeId, int $year): ?string
    {
        $marks = $candidate->marks()
            ->with('subject')
            ->where('exam_type_id', $examTypeId)
            ->where('year', $year)
            ->get();

        if ($marks->isEmpty()) {
            return null;
        }

        $bestGrade = 'F';
        $bestPoints = 7;

        foreach ($marks as $mark) {
            $grade = $this->calculateGrade($mark->marks_obtained);
            $points = $this->getGradePoints($grade);

            if ($points < $bestPoints) {
                $bestPoints = $points;
                $bestGrade = $grade;
            }
        }

        return $bestGrade;
    }

    /**
     * Generate complete grading report for candidate
     */
    public function generateGradingReport(Candidate $candidate, int $examTypeId, int $year): ?array
    {
        $marks = $candidate->marks()
            ->with('subject')
            ->where('exam_type_id', $examTypeId)
            ->where('year', $year)
            ->get();

        if ($marks->isEmpty()) {
            return null;
        }

        $subjectGrades = [];
        $includedSubjectGrades = [];
        $excludedSubjectGrades = [];

        foreach ($marks as $mark) {
            $subjectName = $mark->subject->name ?? '';
            $grade = $this->calculateGrade($mark->marks_obtained);
            $points = $this->getGradePoints($grade);
            $competence = $this->getCompetenceLevel($grade);
            $color = $this->getGradeColor($grade);
            $competenceLevel = "Grade {$grade} ({$competence})";

            $subjectGrade = [
                'subject_id' => $mark->subject_id,
                'subject_name' => $subjectName,
                'marks_obtained' => $mark->marks_obtained,
                'grade' => $grade,
                'points' => $points,
                'competence' => $competence,
                'competence_level' => $competenceLevel,
                'color' => $color,
                'is_excluded' => $this->isExcludedSubject($subjectName),
            ];

            $subjectGrades[] = $subjectGrade;

            if ($this->isExcludedSubject($subjectName)) {
                $excludedSubjectGrades[] = $subjectGrade;
            } else {
                $includedSubjectGrades[] = $subjectGrade;
            }
        }

        $totalMarks = $this->calculateTotalMarks($candidate, $examTypeId, $year);
        $totalPoints = $this->calculateTotalPoints($candidate, $examTypeId, $year);
        $gpa = $this->calculateGPA($candidate, $examTypeId, $year);
        $division = $this->calculateDivision($totalPoints ?? 0);
        $overallGrade = $this->calculateOverallGrade($candidate, $examTypeId, $year);

        return [
            'candidate_id' => $candidate->id,
            'candidate_name' => $candidate->full_name,
            'exam_type_id' => $examTypeId,
            'year' => $year,
            'subject_grades' => $subjectGrades,
            'included_subject_grades' => $includedSubjectGrades,
            'excluded_subject_grades' => $excludedSubjectGrades,
            'total_marks' => $totalMarks,
            'total_points' => $totalPoints,
            'gpa' => $gpa,
            'division' => $division,
            'overall_grade' => $overallGrade,
            'competence_level' => $division ? $division['competence'] : null,
        ];
    }

    /**
     * Process grading for all candidates in exam registration
     */
    public function processBatchGrading(int $examTypeId, int $year, ?int $schoolId = null): array
    {
        $query = CandidateExamRegistration::with(['candidate.marks', 'candidate.subject'])
            ->where('exam_type_id', $examTypeId)
            ->where('year', $year);

        if ($schoolId) {
            $query->whereHas('candidate', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
        }

        $registrations = $query->get();
        $results = [];

        foreach ($registrations as $registration) {
            $report = $this->generateGradingReport($registration->candidate, $examTypeId, $year);
            if ($report) {
                $results[] = $report;
            }
        }

        return $results;
    }
}
