<?php

namespace App\Services\Results;

use App\Models\SubjectMarks;
use App\Models\Subject;

/**
 * AverageMarksService
 * 
 * Handles calculation of average marks for subjects with multiple papers.
 * Provides a centralized place for mark averaging logic.
 */
class AverageMarksService
{
    private NectaGradingService $gradingService;

    public function __construct(NectaGradingService $gradingService)
    {
        $this->gradingService = $gradingService;
    }

    /**
     * Calculate the average mark for a subject based on number of papers
     * 
     * @param float $marksObtained The sum of all paper marks
     * @param Subject $subject The subject (contains paper count info)
     * @return float The calculated average
     * 
     * Example:
     *   Chemistry (3 papers): calculateAverage(115, chemistry) = 38.33
     *   English (2 papers): calculateAverage(130, english) = 65.00
     *   Kiswahili (1 paper): calculateAverage(75, kiswahili) = 75.00
     */
    public function calculateAverage(float $marksObtained, Subject $subject): float
    {
        // Determine total number of papers/components for this subject
        $totalPapers = ($subject->written_papers ?? 1) + 
                      ($subject->has_practical ? 1 : 0) + 
                      ($subject->has_project ? 1 : 0);

        // Calculate average: divide by number of papers
        // For single-paper: divide by 1 (same as original)
        // For multi-paper: divide by count
        if ($totalPapers > 1) {
            return round($marksObtained / $totalPapers, 2);
        }

        return $marksObtained;
    }

    /**
     * Get grade for an average mark
     * 
     * @param float $average The average mark
     * @return string The letter grade (A, B, C, D, E, S, F)
     */
    public function getGradeForAverage(float $average): string
    {
        return $this->gradingService->calculateGrade($average);
    }

    /**
     * Get both average and grade in one call
     * This is the main lookup function - use this!
     * 
     * @param SubjectMarks $mark The subject mark record
     * @param Subject $subject The subject configuration
     * @return array ['average' => float, 'grade' => string]
     * 
     * Example:
     *   $result = $service->getAverageAndGrade($mark, $subject);
     *   // Returns: ['average' => 38.33, 'grade' => 'F']
     */
    public function getAverageAndGrade(SubjectMarks $mark, Subject $subject): array
    {
        $average = $this->calculateAverage($mark->marks_obtained, $subject);
        $grade = $this->getGradeForAverage($average);

        return [
            'average' => $average,
            'grade' => $grade,
        ];
    }

    /**
     * Calculate total marks from all subject averages
     * 
     * @param array $subjectMarks Collection of SubjectMarks
     * @param array $subjectConfigs Array of subject configurations keyed by subject_id
     * @return float The sum of all calculated averages
     */
    public function calculateTotalFromAverages($subjectMarks, array $subjectConfigs): float
    {
        $total = 0;

        foreach ($subjectMarks as $mark) {
            $subject = $subjectConfigs[$mark->subject_id] ?? null;
            if ($subject && $mark->marks_obtained !== null) {
                $average = $this->calculateAverage($mark->marks_obtained, $subject);
                $total += $average;
            }
        }

        return $total;
    }
}
