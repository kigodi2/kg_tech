<?php

namespace App\Services\MarkImport;

use App\Models\Subject;
use App\Models\School;
use App\Models\ExamType;
use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\CandidateSubjectSelection;
use Illuminate\Support\Collection;

/**
 * ACSEE Mark Template Service
 * 
 * Generates professional CSV templates for ACSEE mark entry with minimal data exposure.
 * - Templates expose ONLY index_number and sex (as read-only reference)
 * - Candidate identification relies ONLY on index_number
 * - Full names are NEVER included in templates
 * - Templates are school-, subject-, and exam-year specific
 */
class AcseeMarkTemplateService
{
    /**
     * Generate CSV template headers
     * 
     * Returns: [index_number, sex, paper_p1, paper_p2, ...]
     * 
     * Paper columns are derived from subject paper structure.
     */
    private function generateHeaders(Subject $subject): array
    {
        $headers = ['index_number', 'sex'];

        // Add paper columns dynamically based on subject configuration
        for ($i = 1; $i <= $subject->written_papers; $i++) {
            $headers[] = "paper_p{$i}";
        }

        // Add practical column if applicable
        if ($subject->has_practical) {
            $headers[] = 'practical';
        }

        // Add project column if applicable
        if ($subject->has_project) {
            $headers[] = 'project';
        }

        return $headers;
    }

    /**
     * Get eligible candidates for template
     * 
     * Returns candidates who are:
     * - Registered for ACSEE
     * - Belong to the selected school
     * - Have a combination that includes the selected subject
     * - For the specified exam year
     */
    private function getEligibleCandidates(
        int $schoolId,
        int $subjectId,
        int $examYear
    ): Collection {
        $acsee = ExamType::where('code', 'ACSEE')->firstOrFail();

        return Candidate::where('school_id', $schoolId)
            ->whereHas('examRegistrations', function ($query) use ($acsee, $examYear) {
                $query->where('exam_type_id', $acsee->id)
                    ->where('year', $examYear)
                    ->where('is_active', true);
            })
            ->whereHas('subjectSelections', function ($query) use ($acsee, $subjectId, $examYear) {
                $query->where('exam_type_id', $acsee->id)
                    ->where('subject_id', $subjectId)
                    ->where('year', $examYear)
                    ->where('is_active', true);
            })
            ->select('id', 'candidate_id', 'gender')
            ->orderBy('candidate_id')
            ->get();
    }

    /**
     * Generate CSV template as string
     * 
     * CRITICAL: This template MUST NOT include full names.
     * Only index_number and sex are included for reference.
     * 
     * @param int $examYear
     * @param int $schoolId
     * @param int $subjectId
     * @return string CSV content
     */
    public function generateTemplate(
        int $examYear,
        int $schoolId,
        int $subjectId
    ): string {
        $subject = Subject::findOrFail($subjectId);
        $candidates = $this->getEligibleCandidates($schoolId, $subjectId, $examYear);

        $headers = $this->generateHeaders($subject);
        $csv = implode(',', $headers) . "\n";

        foreach ($candidates as $candidate) {
            $row = [
                $candidate->candidate_id,  // index_number only
                $candidate->gender,         // sex (read-only reference)
            ];

            // Add empty cells for marks (to be filled in)
            for ($i = 1; $i <= $subject->written_papers; $i++) {
                $row[] = '';
            }

            if ($subject->has_practical) {
                $row[] = '';
            }

            if ($subject->has_project) {
                $row[] = '';
            }

            $csv .= implode(',', array_map(function ($val) {
                // Escape CSV values
                if (is_string($val) && (strpos($val, ',') !== false || strpos($val, '"') !== false)) {
                    return '"' . str_replace('"', '""', $val) . '"';
                }
                return $val;
            }, $row)) . "\n";
        }

        return $csv;
    }

    /**
     * Generate professional CSV filename
     * 
     * Format: SCHOOL_NAME_SUBJECT_CODE.csv
     * - Uppercase
     * - Spaces replaced with underscores
     * - Special characters removed
     */
    public function generateFilename(int $schoolId, int $subjectId): string
    {
        $school = School::findOrFail($schoolId);
        $subject = Subject::findOrFail($subjectId);

        // Sanitize school name
        $schoolName = preg_replace('/[^A-Za-z0-9]/', '_', $school->name);
        $schoolName = preg_replace('/_+/', '_', $schoolName); // Replace multiple underscores
        $schoolName = strtoupper(trim($schoolName, '_'));

        // Use subject code
        $subjectCode = strtoupper($subject->code);

        return "{$schoolName}_{$subjectCode}.csv";
    }

    /**
     * Get eligible candidate count
     */
    public function getEligibleCandidateCount(
        int $schoolId,
        int $subjectId,
        int $examYear
    ): int {
        return $this->getEligibleCandidates($schoolId, $subjectId, $examYear)->count();
    }

    /**
     * Get list of eligible candidate index numbers
     * (Used for checksum verification)
     */
    public function getEligibleCandidateIndexNumbers(
        int $schoolId,
        int $subjectId,
        int $examYear
    ): array {
        return $this->getEligibleCandidates($schoolId, $subjectId, $examYear)
            ->pluck('candidate_id')
            ->toArray();
    }

    /**
     * Get subject's paper structure
     */
    public function getSubjectPaperStructure(int $subjectId): array
    {
        $subject = Subject::findOrFail($subjectId);

        return [
            'written_papers' => $subject->written_papers,
            'has_practical' => $subject->has_practical,
            'has_project' => $subject->has_project,
            'code' => $subject->code,
            'name' => $subject->name,
        ];
    }
}
