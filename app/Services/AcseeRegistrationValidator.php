<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Subject;
use App\Models\ExamType;
use Illuminate\Support\Collection;

/**
 * ACSEE Registration Validation Service
 * 
 * Validates candidate subject allocations against NECTA ACSEE rules:
 * - Minimum 3 principal subjects
 * - General Studies mandatory
 * - No duplicate subjects
 * - Maximum subjects limit
 * - Subject conflict prevention
 */
class AcseeRegistrationValidator
{
    const ACSEE_CODE = 'ACSEE';
    const MIN_PRINCIPAL_SUBJECTS = 3;
    const MAX_SUBJECTS = 8;
    const GENERAL_STUDIES_CODE = 'GS';

    /**
     * Validate a candidate's subject allocations against ACSEE rules
     * 
     * @param Candidate $candidate
     * @param int|null $examTypeId Override exam type (defaults to ACSEE)
     * @return ValidationResult
     */
    public function validate(Candidate $candidate, ?int $examTypeId = null): ValidationResult
    {
        $examType = $examTypeId 
            ? ExamType::findOrFail($examTypeId)
            : ExamType::where('code', self::ACSEE_CODE)->firstOrFail();

        $allocatedSubjects = $candidate->allocatedSubjects()
            ->where('exam_type_id', $examType->id)
            ->where('is_active', true)
            ->with('subject')
            ->get();

        $errors = [];
        $warnings = [];

        // Get principals and all subjects
        $principals = $allocatedSubjects->where('is_principal', true);
        $allSubjectIds = $allocatedSubjects->pluck('subject_id')->toArray();

        // ====== RULE 1: Minimum 3 Principal Subjects ======
        if ($principals->count() < self::MIN_PRINCIPAL_SUBJECTS) {
            $errors[] = sprintf(
                "Minimum %d principal subjects required. Found: %d",
                self::MIN_PRINCIPAL_SUBJECTS,
                $principals->count()
            );
        }

        // ====== RULE 2: General Studies Mandatory ======
        $hasGeneralStudies = $allocatedSubjects
            ->contains(fn($selection) => $selection->subject->code === self::GENERAL_STUDIES_CODE);

        if (!$hasGeneralStudies) {
            $errors[] = sprintf(
                "General Studies (%s) is mandatory for ACSEE registration",
                self::GENERAL_STUDIES_CODE
            );
        }

        // ====== RULE 3: No Duplicate Subjects ======
        if (count($allSubjectIds) !== count(array_unique($allSubjectIds))) {
            $errors[] = "Duplicate subjects found in allocation";
        }

        // ====== RULE 4: Maximum Subjects Limit ======
        if ($allocatedSubjects->count() > self::MAX_SUBJECTS) {
            $errors[] = sprintf(
                "Maximum %d subjects allowed. Found: %d",
                self::MAX_SUBJECTS,
                $allocatedSubjects->count()
            );
        }

        // ====== RULE 5: Subject Conflict Prevention ======
        $conflictErrors = $this->validateSubjectConflicts($allocatedSubjects);
        $errors = array_merge($errors, $conflictErrors);

        // ====== WARNINGS (Non-blocking) ======
        if ($principals->count() > 5) {
            $warnings[] = sprintf(
                "More than 5 principal subjects (%d) may affect performance",
                $principals->count()
            );
        }

        // Check if all principals have General Studies
        if ($hasGeneralStudies && $principals->count() >= self::MIN_PRINCIPAL_SUBJECTS) {
            $gsIsPrincipal = $allocatedSubjects
                ->where('is_principal', true)
                ->contains(fn($s) => $s->subject->code === self::GENERAL_STUDIES_CODE);
            
            if (!$gsIsPrincipal) {
                $warnings[] = "General Studies is allocated but not marked as principal";
            }
        }

        return new ValidationResult(
            valid: empty($errors),
            errors: $errors,
            warnings: $warnings,
            principals_count: $principals->count(),
            subjects_count: $allocatedSubjects->count(),
            allocated_subjects: $allocatedSubjects->map(fn($s) => [
                'id' => $s->id,
                'subject_id' => $s->subject_id,
                'subject_code' => $s->subject->code,
                'subject_name' => $s->subject->name,
                'is_principal' => $s->is_principal,
            ])->toArray(),
        );
    }

    /**
     * Validate subject conflicts based on configurable rules
     * 
     * @param Collection $allocatedSubjects
     * @return array
     */
    private function validateSubjectConflicts(Collection $allocatedSubjects): array
    {
        $errors = [];
        $subjectCodes = $allocatedSubjects->map(fn($s) => $s->subject->code)->toArray();

        // Define subject conflict rules (subject pairs that cannot coexist)
        // These are NECTA rules for ACSEE
        $conflictRules = [
            // Example: Physics cannot be with Geography or History
            // ['PHYS', 'GEO'],
            // ['PHYS', 'HIST'],
            // Add more rules as needed
        ];

        foreach ($conflictRules as [$subject1, $subject2]) {
            $has1 = in_array($subject1, $subjectCodes);
            $has2 = in_array($subject2, $subjectCodes);

            if ($has1 && $has2) {
                $errors[] = sprintf(
                    "Subject conflict: %s cannot be allocated with %s",
                    $subject1,
                    $subject2
                );
            }
        }

        return $errors;
    }

    /**
     * Validate for PRIVATE candidate registration
     * (More lenient - just checks basic rules, not combination constraints)
     * 
     * @param Candidate $candidate
     * @return ValidationResult
     */
    public function validatePrivateCandidate(Candidate $candidate): ValidationResult
    {
        if (!$candidate->isPrivate()) {
            throw new \InvalidArgumentException('This validator is for PRIVATE candidates only');
        }

        return $this->validate($candidate);
    }

    /**
     * Validate for SCHOOL candidate registration
     * (Checks combination consistency if applicable)
     * 
     * @param Candidate $candidate
     * @return ValidationResult
     */
    public function validateSchoolCandidate(Candidate $candidate): ValidationResult
    {
        if (!$candidate->isSchool()) {
            throw new \InvalidArgumentException('This validator is for SCHOOL candidates only');
        }

        $result = $this->validate($candidate);

        // Additional check: If combination is set, verify subjects match
        if ($candidate->combination || $candidate->combination_id) {
            $combination = $candidate->combinationRelation 
                ?? $candidate->combination()->first();

            if ($combination) {
                $expectedSubjectIds = $combination->subjects()
                    ->pluck('subjects.id')
                    ->toArray();

                $actualSubjectIds = collect($result->allocated_subjects)
                    ->pluck('subject_id')
                    ->toArray();

                if (array_diff($expectedSubjectIds, $actualSubjectIds)) {
                    $result->warnings[] = "Some combination subjects are not allocated";
                }

                if (array_diff($actualSubjectIds, $expectedSubjectIds)) {
                    $result->warnings[] = "Some allocated subjects are not in the combination template";
                }
            }
        }

        return $result;
    }

    /**
     * Check if a candidate can register (pass all validations)
     * 
     * @param Candidate $candidate
     * @return bool
     */
    public function canRegister(Candidate $candidate): bool
    {
        $result = $this->validate($candidate);
        return $result->valid;
    }

    /**
     * Get validation errors only (for user-facing messages)
     * 
     * @param Candidate $candidate
     * @return array
     */
    public function getErrors(Candidate $candidate): array
    {
        return $this->validate($candidate)->errors;
    }

    /**
     * Get validation warnings only (informational)
     * 
     * @param Candidate $candidate
     * @return array
     */
    public function getWarnings(Candidate $candidate): array
    {
        return $this->validate($candidate)->warnings;
    }
}

/**
 * Validation Result Data Class
 */
class ValidationResult
{
    public function __construct(
        public bool $valid,
        public array $errors = [],
        public array $warnings = [],
        public int $principals_count = 0,
        public int $subjects_count = 0,
        public array $allocated_subjects = [],
    ) {}

    /**
     * Convert to array for JSON response
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'principals_count' => $this->principals_count,
            'subjects_count' => $this->subjects_count,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'allocated_subjects' => $this->allocated_subjects,
        ];
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
