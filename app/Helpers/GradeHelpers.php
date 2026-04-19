<?php

/**
 * Grade Display Helpers
 * 
 * Utility functions for displaying grade-related information
 * in views and throughout the application.
 */

use App\Services\Results\NectaGradingService;

/**
 * Format division number to display string
 * 
 * @param int|string $division Division number (1, 2, 3, 4, 0)
 * @return string Formatted division (I, II, III, IV, 0)
 * 
 * @example
 *   format_division(1)  // Returns: 'I'
 *   format_division(3)  // Returns: 'III'
 */
if (!function_exists('format_division')) {
    function format_division($division): string
    {
        if ($division === null || $division === '') {
            return '-';
        }

        $divisionMap = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            0 => '0',
        ];

        return $divisionMap[(int)$division] ?? '-';
    }
}

/**
 * Get competence level for a grade
 * 
 * @param string $grade Letter grade (A-F, S)
 * @return string Competence level
 * 
 * @example
 *   get_competence('A')  // Returns: 'Excellent'
 *   get_competence('F')  // Returns: 'Fail'
 */
if (!function_exists('get_competence')) {
    function get_competence(string $grade, string $examTypeCode = 'ACSEE'): string
    {
        return app(NectaGradingService::class)->getCompetenceLevelForExamType($grade, $examTypeCode);
    }
}

/**
 * Get grade color code
 * 
 * @param string $grade Letter grade (A-F, S)
 * @return string Hex color code
 * 
 * @example
 *   get_grade_color('A')  // Returns: '#00AA7A'
 */
if (!function_exists('get_grade_color')) {
    function get_grade_color(string $grade, string $examTypeCode = 'ACSEE'): string
    {
        return app(NectaGradingService::class)->getGradeColorForExamType($grade, $examTypeCode);
    }
}

/**
 * Calculate GPA from points and subject count
 * 
 * @param int $points Total grade points
 * @param int $count Number of subjects (excluding excluded subjects)
 * @return float GPA value
 * 
 * @example
 *   calculate_gpa(10, 5)  // Returns: 2.0
 *   calculate_gpa(8, 5)   // Returns: 1.6
 */
if (!function_exists('calculate_gpa')) {
    function calculate_gpa(int $points, int $count): float
    {
        if ($count <= 0) {
            return 0;
        }

        return round($points / $count, 4);
    }
}

/**
 * Get grade points from grade letter
 * 
 * @param string $grade Letter grade (A-F, S)
 * @return int Points (1-7)
 * 
 * @example
 *   get_grade_points('A')  // Returns: 1
 *   get_grade_points('F')  // Returns: 7
 */
if (!function_exists('get_grade_points')) {
    function get_grade_points(string $grade, string $examTypeCode = 'ACSEE'): int
    {
        return app(NectaGradingService::class)->getGradePointsForExamType($grade, $examTypeCode);
    }
}

/**
 * Get grade from mark value
 * 
 * @param float $mark Mark value (0-100)
 * @return string Letter grade
 * 
 * @example
 *   get_grade_from_mark(85)  // Returns: 'A'
 *   get_grade_from_mark(45)  // Returns: 'E'
 */
if (!function_exists('get_grade_from_mark')) {
    function get_grade_from_mark(float $mark, string $examTypeCode = 'ACSEE'): string
    {
        return app(NectaGradingService::class)->calculateGradeForExamType($mark, $examTypeCode);
    }
}

/**
 * Get division info from total points
 * 
 * @param int $totalPoints Sum of all grade points
 * @return array ['division' => int, 'name' => string, 'competence' => string]
 * 
 * @example
 *   get_division_info(8)
 *   // Returns: ['division' => 1, 'name' => 'I', 'competence' => 'Excellent']
 */
if (!function_exists('get_division_info')) {
    function get_division_info(int $totalPoints, string $examTypeCode = 'ACSEE'): array
    {
        $gradingService = app(NectaGradingService::class);
        $divisionInfo = $gradingService->calculateDivisionForExamType($totalPoints, $examTypeCode);

        if (!$divisionInfo) {
            return [
                'division' => 0,
                'name' => '0',
                'competence' => 'Fail',
            ];
        }

        return [
            'division' => $divisionInfo['division'],
            'name' => format_division($divisionInfo['division']),
            'competence' => $divisionInfo['competence'] ?? 'Unknown',
        ];
    }
}

/**
 * Check if subject is excluded from GPA calculation
 * 
 * @param string $subjectName Subject name
 * @return bool True if excluded
 * 
 * @example
 *   is_excluded_subject('GENERAL STUDIES')  // Returns: true
 */
if (!function_exists('is_excluded_subject')) {
    function is_excluded_subject(string $subjectName): bool
    {
        return app(NectaGradingService::class)->isExcludedSubject($subjectName);
    }
}

/**
 * Format GPA with competence level
 * 
 * @param float $gpa GPA value
 * @return string Formatted GPA with competence
 * 
 * @example
 *   format_gpa(3.5)  // Returns: '3.5000 Good'
 */
if (!function_exists('format_gpa')) {
    function format_gpa(float $gpa): string
    {
        // Use NECTA grading system GPA competence mapping
        $gradingService = app(\App\Services\Results\NectaGradingService::class);
        $gpaInfo = $gradingService->getGpaCompetence($gpa);
        
        return number_format($gpa, 4) . " " . $gpaInfo['competence'];
    }
}

/**
 * Get GPA competence info (for styling)
 * 
 * @param float $gpa GPA value
 * @return array ['text' => string, 'color' => string]
 * 
 * @example
 *   get_gpa_info(3.5)  // Returns: ['text' => '3.5000 Good', 'color' => '#1FEE0B']
 */
if (!function_exists('get_gpa_info')) {
    function get_gpa_info(float $gpa): array
    {
        $gradingService = app(\App\Services\Results\NectaGradingService::class);
        $gpaInfo = $gradingService->getGpaCompetence($gpa);
        
        return [
            'text' => number_format($gpa, 4) . " " . $gpaInfo['competence'],
            'color' => $gpaInfo['color'],
        ];
    }
}
