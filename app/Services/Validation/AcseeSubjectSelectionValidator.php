<?php

namespace App\Services\Validation;

use App\Models\CandidateExamRegistration;
use App\Models\CandidateSubjectSelection;
use App\Models\ExamType;

/**
 * AcseeSubjectSelectionValidator
 *
 * Validates that all ACSEE candidate registrations have corresponding
 * subject selections based on their combinations.
 *
 * Can be used to:
 * - Check for missing selections after bulk imports
 * - Validate data integrity
 * - Generate reports on potential issues
 */
class AcseeSubjectSelectionValidator
{
    /**
     * Validate that a school has complete subject selections for all ACSEE candidates
     *
     * @param int $schoolId
     * @param int $examYearId
     * @return array ['valid' => bool, 'missing_count' => int, 'registrations' => int, 'selections' => int]
     */
    public function validateSchool(int $schoolId, int $examYearId): array
    {
        $acsee = ExamType::where('code', 'ACSEE')->first();
        if (!$acsee) {
            return ['valid' => false, 'error' => 'ACSEE exam type not found'];
        }

        // Count registrations
        $registrations = CandidateExamRegistration::query()
            ->whereHas('candidate', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })
            ->where('exam_type_id', $acsee->id)
            ->where('exam_year_id', $examYearId)
            ->count();

        // Count selections
        $selections = CandidateSubjectSelection::query()
            ->whereHas('candidate', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })
            ->where('exam_type_id', $acsee->id)
            ->where('exam_year_id', $examYearId)
            ->count();

        // At least 3 selections per registration (typical combinations)
        $expectedMinimum = $registrations * 3;

        return [
            'valid' => $selections >= $expectedMinimum,
            'registrations' => $registrations,
            'selections' => $selections,
            'expected_minimum' => $expectedMinimum,
            'missing_count' => max(0, $expectedMinimum - $selections),
        ];
    }

    /**
     * Validate that an exam year has complete selections for all schools
     *
     * @param int $examYearId
     * @return array ['valid' => bool, 'schools_with_issues' => array]
     */
    public function validateExamYear(int $examYearId): array
    {
        $acsee = ExamType::where('code', 'ACSEE')->first();
        if (!$acsee) {
            return ['valid' => false, 'error' => 'ACSEE exam type not found'];
        }

        // Get all schools with ACSEE registrations
        $schools = \App\Models\School::query()
            ->join('candidates', 'schools.id', '=', 'candidates.school_id')
            ->join('candidate_exam_registrations', 'candidates.id', '=', 'candidate_exam_registrations.candidate_id')
            ->where('candidate_exam_registrations.exam_type_id', $acsee->id)
            ->where('candidate_exam_registrations.exam_year_id', $examYearId)
            ->distinct()
            ->pluck('schools.id');

        $schoolsWithIssues = [];
        $allValid = true;

        foreach ($schools as $schoolId) {
            $validation = $this->validateSchool($schoolId, $examYearId);
            if (!$validation['valid']) {
                $allValid = false;
                $schoolsWithIssues[] = [
                    'school_id' => $schoolId,
                    'registrations' => $validation['registrations'],
                    'selections' => $validation['selections'],
                    'missing_count' => $validation['missing_count'],
                ];
            }
        }

        return [
            'valid' => $allValid,
            'schools_with_issues' => $schoolsWithIssues,
            'total_schools_checked' => $schools->count(),
        ];
    }

    /**
     * Get a detailed report of all schools and their selection status
     *
     * @param int $examYearId
     * @return array
     */
    public function getDetailedReport(int $examYearId): array
    {
        $acsee = ExamType::where('code', 'ACSEE')->first();
        if (!$acsee) {
            return ['error' => 'ACSEE exam type not found'];
        }

        // Get all schools with ACSEE registrations
        $schools = \App\Models\School::query()
            ->select('schools.id', 'schools.code', 'schools.name')
            ->join('candidates', 'schools.id', '=', 'candidates.school_id')
            ->join('candidate_exam_registrations', 'candidates.id', '=', 'candidate_exam_registrations.candidate_id')
            ->where('candidate_exam_registrations.exam_type_id', $acsee->id)
            ->where('candidate_exam_registrations.exam_year_id', $examYearId)
            ->distinct()
            ->get();

        $report = [];
        foreach ($schools as $school) {
            $validation = $this->validateSchool($school->id, $examYearId);
            $report[] = [
                'school_code' => $school->code,
                'school_name' => $school->name,
                'registrations' => $validation['registrations'],
                'selections' => $validation['selections'],
                'status' => $validation['valid'] ? '✅ OK' : '❌ MISSING',
                'missing_count' => $validation['missing_count'] ?? 0,
            ];
        }

        // Sort by code
        usort($report, fn($a, $b) => strcmp($a['school_code'], $b['school_code']));

        return $report;
    }
}
