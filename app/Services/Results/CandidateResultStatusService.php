<?php

namespace App\Services\Results;

class CandidateResultStatusService
{
    public const EXPECTED_SUBJECTS = 6;

    /**
     * Determine candidate result status and calculate marks/grades.
     *
     * Statuses:
     * - COMPLETE: 6 numeric marks. Calculates total marks, WASTANI wa Alama, overall grade, and position.
     * - INC: 1-5 numeric marks. Grade and status set to INC, total score and position display as INC, missing subjects are X.
     * - ABS: 0 numeric marks. Grade and status set to ABS, total score and position display as ABS, all subjects are X.
     *
     * @param int $candidateId
     * @param array $marksBySubject Array of subject marks (subject_id => marks_obtained)
     * @param array $subjectIds List of expected subject IDs
     * @return array
     */
    public function evaluateCandidate(int $candidateId, array $marksBySubject, array $subjectIds): array
    {
        $numericSubjectCount = 0;
        $totalMarks = 0.0;
        $subjectDetails = [];

        foreach ($subjectIds as $subId) {
            $mark = $marksBySubject[$subId] ?? null;

            $isNumeric = is_numeric($mark) && $mark >= 0;

            if ($isNumeric) {
                $numericSubjectCount++;
                $totalMarks += (float) $mark;
                $score50 = (float) $mark; // PSLE raw mark is out of 50
                $percentage = ($score50 / 50.0) * 100.0;
                $grade = $this->gradeFromRaw50($score50);
            } else {
                $score50 = null;
                $percentage = null;
                // If marks are missing, we check if it is explicitly ABS or INC in the database.
                // We'll set grade to 'ABS' or 'INC' dynamically based on candidate overall status.
                // Wait! If numericCount is 0, then ABS. If numericCount > 0, then INC.
                // We will populate grade below once we know the overall status.
                $grade = null;
            }

            $subjectDetails[$subId] = [
                'subject_id' => $subId,
                'marks_obtained' => $score50,
                'percentage' => $percentage,
                'grade' => $grade,
                'is_numeric' => $isNumeric,
            ];
        }

        // Determine overall status
        if ($numericSubjectCount === self::EXPECTED_SUBJECTS) {
            $status = 'COMPLETE';
            $overallGrade = $this->gradeFromRaw50($totalMarks / self::EXPECTED_SUBJECTS);
            $totalScoreValue = $totalMarks;
            $percentageValue = ($totalMarks / (self::EXPECTED_SUBJECTS * 50.0)) * 100.0;
            $dbStatus = 'RELEASED';
        } elseif ($numericSubjectCount > 0) {
            $status = 'INC';
            $overallGrade = 'INC';
            $totalScoreValue = null;
            $percentageValue = null;
            $dbStatus = 'PENDING';
        } else {
            $status = 'ABS';
            $overallGrade = 'ABS';
            $totalScoreValue = null;
            $percentageValue = null;
            $dbStatus = 'RELEASED';
        }

        // Backfill subject grades for missing marks
        foreach ($subjectDetails as $subId => &$details) {
            if (!$details['is_numeric']) {
                $details['grade'] = $status === 'ABS' ? 'ABS' : 'INC';
            }
        }
        unset($details);

        return [
            'candidate_id' => $candidateId,
            'status' => $status, // 'COMPLETE', 'INC', 'ABS'
            'overall_grade' => $overallGrade,
            'total_marks' => $totalScoreValue,
            'total_percentage' => $percentageValue,
            'db_status' => $dbStatus,
            'numeric_count' => $numericSubjectCount,
            'subjects' => $subjectDetails,
        ];
    }

    /**
     * Map a raw mark out of 50 to a PSLE grade.
     */
    public function gradeFromRaw50(float $mark): string
    {
        if ($mark >= 241.0 / 6.0) return 'A'; // ~40.1667
        if ($mark >= 181.0 / 6.0) return 'B'; // ~30.1667
        if ($mark >= 121.0 / 6.0) return 'C'; // ~20.1667
        if ($mark >= 61.0 / 6.0)  return 'D'; // ~10.1667
        return 'E';
    }

    /**
     * Map a PSLE grade to grade points.
     */
    public function gradePointFromGrade(string $grade): int
    {
        return match (strtoupper($grade)) {
            'A' => 1,
            'B' => 2,
            'C' => 3,
            'D' => 4,
            'E' => 5,
            default => 5,
        };
    }
}
