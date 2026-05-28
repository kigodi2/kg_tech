<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;

/**
 * ACSEE Allocation Validator
 * 
 * Validates that a candidate's subject allocation conforms to NECTA ACSEE rules:
 * - General Studies (code 111 or name "GENERAL STUDIES") is mandatory
 * - Minimum 3 principal subjects (excluding General Studies and BAM)
 * - No duplicate subject allocations
 */
class AcseeAllocationValidator
{
    protected $candidate;
    protected $examTypeId;
    protected $examYearId;
    protected $errors = [];
    protected $warnings = [];
    protected $principalSubjectIds = [];
    protected $allSubjectIds = [];

    /**
     * Validate a candidate's allocation
     * 
     * @param Candidate $candidate
     * @param int $examTypeId
     * @param int $examYearId
     * @param array $subjectIds - IDs of subjects to validate
     * @return array {ok: bool, errors: [], warnings: [], principal_subject_ids: [], all_subject_ids: []}
     */
    public function validate(Candidate $candidate, int $examTypeId, int $examYearId, array $subjectIds)
    {
        $this->candidate = $candidate;
        $this->examTypeId = $examTypeId;
        $this->examYearId = $examYearId;
        $this->errors = [];
        $this->warnings = [];
        $this->principalSubjectIds = [];
        $this->allSubjectIds = $subjectIds;

        // Rule 1 & 2: apply to all ACSEE allocations, including private candidates
        $this->validateGeneralStudies();
        $this->validatePrincipalSubjectCount();

        // Rule 3: No duplicates (checked)
        $this->validateNoDuplicates();

        return [
            'ok' => count($this->errors) === 0,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'principal_subject_ids' => $this->principalSubjectIds,
            'all_subject_ids' => $this->allSubjectIds,
        ];
    }

    /**
     * Validate General Studies is present
     */
    protected function validateGeneralStudies()
    {
        // Find General Studies by code (111) or name
        $generalStudies = Subject::where('code', '111')
            ->orWhere('name', 'GENERAL STUDIES')
            ->orWhere('name', 'General Studies')
            ->first();

        if (!$generalStudies) {
            // Subject not found in system, cannot validate
            $this->errors[] = "General Studies subject not configured in system";
            return;
        }

        if (!in_array($generalStudies->id, $this->allSubjectIds)) {
            $this->errors[] = "General Studies (code 111) is mandatory for ACSEE candidates";
            return;
        }

        // General Studies is mandatory but never principal.
    }

    /**
     * Validate minimum 3 principal subjects
     * 
     * Principal subjects = all subjects except General Studies and BAM
     */
    protected function validatePrincipalSubjectCount()
    {
        // Find General Studies ID
        $generalStudies = Subject::where('code', '111')
            ->orWhere('name', 'GENERAL STUDIES')
            ->orWhere('name', 'General Studies')
            ->first();

        $generalStudiesId = $generalStudies ? $generalStudies->id : null;
        $bamId = Subject::where('code', '141')
            ->orWhere('name', 'BASIC APPLIED MATHEMATICS')
            ->orWhere('name', 'Basic Applied Mathematics')
            ->first()?->id;

        // Count principals = all subjects - General Studies - BAM
        $principalCount = count(array_filter($this->allSubjectIds, function ($id) use ($generalStudiesId, $bamId) {
            if ($generalStudiesId && (int) $id === (int) $generalStudiesId) {
                return false;
            }
            if ($bamId && (int) $id === (int) $bamId) {
                return false;
            }
            return true;
        }));

        if ($principalCount < 3) {
            $this->errors[] = "Minimum 3 principal subjects required (found " . $principalCount . ")";
            return;
        }

        // Set principal subject IDs = all - General Studies - BAM
        $this->principalSubjectIds = array_filter(
            $this->allSubjectIds,
            fn($id) => $id !== $generalStudiesId && $id !== $bamId
        );
    }

    /**
     * Validate no duplicates in subject list
     */
    protected function validateNoDuplicates()
    {
        $uniqueIds = array_unique($this->allSubjectIds);
        if (count($uniqueIds) !== count($this->allSubjectIds)) {
            $duplicates = array_diff_assoc($this->allSubjectIds, $uniqueIds);
            $this->warnings[] = "Duplicate subjects detected and will be removed: " . implode(', ', array_unique($duplicates));
            $this->allSubjectIds = $uniqueIds;
        }
    }

    /**
     * Validate and apply allocation from a combination template
     * 
     * @param Candidate $candidate
     * @param int $combinationId
     * @param int $examTypeId
     * @param int $examYearId
     * @return array {ok: bool, errors: [], warnings: [], principal_subject_ids: [], all_subject_ids: []}
     */
    public function validateFromCombination(Candidate $candidate, int $combinationId, int $examTypeId, int $examYearId)
    {
        // Load subjects from combination
        $combination = \App\Models\Combination::with('subjects')->find($combinationId);
        
        if (!$combination) {
            return [
                'ok' => false,
                'errors' => ['Combination not found'],
                'warnings' => [],
                'principal_subject_ids' => [],
                'all_subject_ids' => [],
            ];
        }

        $subjectIds = $combination->subjects()->pluck('subjects.id')->toArray();
        
        return $this->validate($candidate, $examTypeId, $examYearId, $subjectIds);
    }
}
