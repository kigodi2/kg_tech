<?php

namespace App\Services\Results;

/**
 * GradeLookupService
 * 
 * Centralized lookup service for all grade-related information:
 * - Grade from marks
 * - Competence level from grade
 * - Grade points from grade
 * - Division from points
 * - Color code from grade
 * 
 * All lookups in one place for easy reuse across the application.
 */
class GradeLookupService
{
    private NectaGradingService $gradingService;

    public function __construct(NectaGradingService $gradingService)
    {
        $this->gradingService = $gradingService;
    }

    /**
     * Lookup: Get grade from marks
     * 
     * @param float $marks The mark (0-100)
     * @return string The letter grade (A, B, C, D, E, S, F)
     * 
     * Example:
     *   getGradeFromMarks(85) → 'A'
     *   getGradeFromMarks(45) → 'E'
     */
    public function getGradeFromMarks(float $marks, string $examTypeCode = 'ACSEE'): string
    {
        return $this->gradingService->calculateGradeForExamType($marks, $examTypeCode);
    }

    /**
     * Lookup: Get competence level from grade
     * 
     * @param string $grade The letter grade (A-F, S)
     * @return string The competence level
     * 
     * Example:
     *   getCompetenceLevel('A') → 'Excellent'
     *   getCompetenceLevel('F') → 'Fail'
     */
    public function getCompetenceLevel(string $grade, string $examTypeCode = 'ACSEE'): string
    {
        return $this->gradingService->getCompetenceLevelForExamType($grade, $examTypeCode);
    }

    /**
     * Lookup: Get grade color code
     * 
     * @param string $grade The letter grade (A-F, S)
     * @return string The hex color code
     * 
     * Example:
     *   getGradeColor('A') → '#00AA7A'
     */
    public function getGradeColor(string $grade, string $examTypeCode = 'ACSEE'): string
    {
        return $this->gradingService->getGradeColorForExamType($grade, $examTypeCode);
    }

    /**
     * Lookup: Get grade points (for GPA calculation)
     * 
     * @param string $grade The letter grade (A-F, S)
     * @return int The points (1-7)
     * 
     * Example:
     *   getGradePoints('A') → 1
     *   getGradePoints('F') → 7
     */
    public function getGradePoints(string $grade, string $examTypeCode = 'ACSEE'): int
    {
        return $this->gradingService->getGradePointsForExamType($grade, $examTypeCode);
    }

    /**
     * Lookup: Get division from total points
     * 
     * @param int $totalPoints The sum of all grade points
     * @return array ['division' => int, 'name' => string, 'competence' => string]
     * 
     * Example:
     *   getDivisionFromPoints(8) → 
     *     ['division' => 1, 'name' => 'I', 'competence' => 'Excellent']
     *   getDivisionFromPoints(15) → 
     *     ['division' => 3, 'name' => 'III', 'competence' => 'Good']
     */
    public function getDivisionFromPoints(int $totalPoints, string $examTypeCode = 'ACSEE'): array
    {
        $divisionInfo = $this->gradingService->calculateDivisionForExamType($totalPoints, $examTypeCode);
        
        if (!$divisionInfo) {
            return [
                'division' => 0,
                'name' => '0',
                'competence' => 'Fail',
            ];
        }

        return [
            'division' => $divisionInfo['division'],
            'name' => $divisionInfo['division'],
            'competence' => $divisionInfo['competence'] ?? 'Unknown',
        ];
    }

    /**
     * Lookup: Get GPA from total points and subject count
     * 
     * @param int $totalPoints The sum of all grade points
     * @param int $subjectCount The number of subjects counted
     * @return float The GPA (rounded to 4 decimal places)
     * 
     * Example:
     *   getGPA(10, 5) → 2.0
     *   getGPA(8, 5) → 1.6
     */
    public function getGPA(int $totalPoints, int $subjectCount): float
    {
        if ($subjectCount <= 0) {
            return 0;
        }

        return round($totalPoints / $subjectCount, 4);
    }

    /**
     * Lookup: Get everything in one call
     * 
     * @param float $marks The mark (0-100)
     * @param int $gradePoints The grade points (1-7)
     * @param int $totalPoints The sum of all grade points
     * @param int $subjectCount The number of subjects
     * @return array Complete grade information
     * 
     * Returns:
     *   'grade' => 'A',
     *   'competence' => 'Excellent',
     *   'points' => 1,
     *   'color' => '#00AA7A',
     *   'gpa' => 2.0,
     *   'division' => 1,
     *   'divisionName' => 'I',
     *   'divisionCompetence' => 'Excellent'
     */
    public function getCompleteGradeInfo(
        float $marks,
        int $totalPoints,
        int $subjectCount
    ): array {
        $grade = $this->getGradeFromMarks($marks);
        $gradePoints = $this->getGradePoints($grade);
        $competence = $this->getCompetenceLevel($grade);
        $color = $this->getGradeColor($grade);
        $gpa = $this->getGPA($totalPoints, $subjectCount);
        $division = $this->getDivisionFromPoints($totalPoints);

        return [
            'grade' => $grade,
            'competence' => $competence,
            'points' => $gradePoints,
            'color' => $color,
            'gpa' => $gpa,
            'division' => $division['division'],
            'divisionName' => $division['name'],
            'divisionCompetence' => $division['competence'],
        ];
    }

    /**
     * Check if a subject should be excluded from GPA/points
     * 
     * @param string $subjectName The subject name
     * @return bool True if excluded
     */
    public function isExcludedSubject(string $subjectName): bool
    {
        return $this->gradingService->isExcludedSubject($subjectName);
    }

    /**
     * Format division number to display string
     * 
     * @param int|string $division The division number (1, 2, 3, 4, 0)
     * @return string The formatted division (I, II, III, IV, 0)
     * 
     * Example:
     *   formatDivision(1) → 'I'
     *   formatDivision(3) → 'III'
     */
    public function formatDivision($division): string
    {
        $divisionMap = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            0 => '0',
        ];

        return $divisionMap[$division] ?? '-';
    }
}
