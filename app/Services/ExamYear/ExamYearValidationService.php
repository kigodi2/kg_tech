<?php

namespace App\Services\ExamYear;

use App\Models\ExamYear;
use App\Models\Subject;
use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use Illuminate\Validation\ValidationException;

/**
 * ExamYearValidationService
 *
 * Provides validation guardrails for exam year-based operations.
 *
 * Key principle: Exam year is a first-class domain boundary.
 * All operations must validate that:
 * 1. The exam year exists
 * 2. The exam year is not locked
 * 3. Required data (candidates, subjects) exists for the year
 * 4. No silent fallbacks to previous years
 *
 * All validations return meaningful error messages for audit and UX.
 */
class ExamYearValidationService
{
    /**
     * Validate that a candidate can register for an exam year.
     *
     * @param Candidate $candidate
     * @param ExamYear|int $examYear
     * @return array ['valid' => bool, 'message' => string, 'code' => string]
     */
    public function validateCandidateRegistration(Candidate $candidate, $examYear): array
    {
        // Handle string year_label (e.g., "2026") and int ID
        if ($examYear instanceof ExamYear) {
            $year = $examYear;
        } elseif (is_int($examYear)) {
            $year = ExamYear::findOrFail($examYear);
        } elseif (is_string($examYear)) {
            // Lookup by year_label
            $year = ExamYear::where('year_label', $examYear)->firstOrFail();
        } else {
            throw new \Exception('Invalid exam year type provided');
        }

        // Check if year is locked
        if ($year->is_locked) {
            return [
                'valid' => false,
                'message' => "Cannot register candidates for locked year: {$year->year_label}",
                'code' => 'YEAR_LOCKED'
            ];
        }

        // Check if year is published (becomes read-only)
        if ($year->isPublished()) {
            return [
                'valid' => false,
                'message' => "Results published for {$year->year_label}. No new registrations allowed.",
                'code' => 'YEAR_PUBLISHED'
            ];
        }

        // Check if candidate already registered for this year
        $existingReg = CandidateExamRegistration::where('candidate_id', $candidate->id)
            ->where('exam_year_id', $year->id)
            ->exists();

        if ($existingReg) {
            return [
                'valid' => false,
                'message' => "Candidate {$candidate->candidate_id} already registered for {$year->year_label}",
                'code' => 'ALREADY_REGISTERED'
            ];
        }

        return [
            'valid' => true,
            'message' => "Candidate is eligible for registration in {$year->year_label}",
            'code' => 'ELIGIBLE'
        ];
    }

    /**
     * Validate that mark entry is allowed for a school + year combination.
     *
     * @param int $schoolId
     * @param ExamYear|int $examYear
     * @return array ['valid' => bool, 'message' => string, 'code' => string, 'candidate_count' => int]
     */
    public function validateMarkEntry(int $schoolId, $examYear): array
    {
        // Handle both ID and year_label (2026, etc.)
        if ($examYear instanceof ExamYear) {
            $year = $examYear;
        } else {
            // Try to find by year_label first (since frontend sends year as 2026)
            $year = ExamYear::where('year_label', (string)$examYear)->first();
            
            // Fallback to ID if year_label doesn't match
            if (!$year) {
                $year = ExamYear::find($examYear);
            }
        }

        if (!$year) {
            return [
                'valid' => false,
                'message' => 'Invalid exam year',
                'code' => 'INVALID_YEAR',
                'candidate_count' => 0
            ];
        }

        // Check if year is locked (read-only)
        if ($year->is_locked) {
            return [
                'valid' => false,
                'message' => "Year {$year->year_label} is locked. Mark entry is disabled.",
                'code' => 'YEAR_LOCKED',
                'candidate_count' => 0
            ];
        }

        // Check if school has ACSEE candidates for this year
        // First check formal registrations (CandidateExamRegistration)
        $candidateCount = CandidateExamRegistration::query()
            ->whereHas('candidate', fn($q) => $q->where('school_id', $schoolId))
            ->where('exam_year_id', $year->id)
            ->count();

        // Fallback: If no formal registrations, check if school has any ACSEE candidates at all
        // (They may exist but not yet formally registered for this year)
        if ($candidateCount === 0) {
            $candidateCount = Candidate::where('school_id', $schoolId)
                ->where('exam_type', 'ACSEE')
                ->count();
        }

        if ($candidateCount === 0) {
            return [
                'valid' => false,
                'message' => "No ACSEE candidates registered for {$year->year_label} in this school",
                'code' => 'NO_CANDIDATES',
                'candidate_count' => 0
            ];
        }

        return [
            'valid' => true,
            'message' => "Mark entry allowed for {$year->year_label}",
            'code' => 'ALLOWED',
            'candidate_count' => $candidateCount
        ];
    }

    /**
     * Validate that a subject can be used for mark entry in a given year.
     *
     * @param Subject $subject
     * @param ExamYear|int $examYear
     * @param int $schoolId
     * @return array ['valid' => bool, 'message' => string, 'code' => string, 'candidate_count' => int]
     */
    public function validateSubjectForYear(Subject $subject, $examYear, int $schoolId): array
    {
        $year = $examYear instanceof ExamYear ? $examYear : ExamYear::find($examYear);

        if (!$year) {
            return [
                'valid' => false,
                'message' => 'Invalid exam year',
                'code' => 'INVALID_YEAR',
                'candidate_count' => 0
            ];
        }

        // Check if subject is active
        if (!$subject->is_active) {
            return [
                'valid' => false,
                'message' => "Subject {$subject->code} is inactive",
                'code' => 'SUBJECT_INACTIVE',
                'candidate_count' => 0
            ];
        }

        // Check if subject is registered for any candidates in this school/year
        $candidateCount = \DB::table('candidate_subject_selections')
            ->join('candidate_exam_registrations', 'candidate_subject_selections.candidate_id', '=', 'candidate_exam_registrations.candidate_id')
            ->join('candidates', 'candidate_exam_registrations.candidate_id', '=', 'candidates.id')
            ->where('candidate_subject_selections.subject_id', $subject->id)
            ->where('candidate_subject_selections.exam_year_id', $year->id)
            ->where('candidates.school_id', $schoolId)
            ->count();

        if ($candidateCount === 0) {
            return [
                'valid' => false,
                'message' => "No candidates registered for {$subject->code} in this school for {$year->year_label}",
                'code' => 'NO_SUBJECT_REGISTRATIONS',
                'candidate_count' => 0
            ];
        }

        return [
            'valid' => true,
            'message' => "Subject {$subject->code} is available for mark entry",
            'code' => 'AVAILABLE',
            'candidate_count' => $candidateCount
        ];
    }

    /**
     * Validate exam year can be locked for publication.
     *
     * @param ExamYear $year
     * @return array ['valid' => bool, 'message' => string, 'code' => string]
     */
    public function validateCanLockYear(ExamYear $year): array
    {
        // Check if already locked
        if ($year->is_locked) {
            return [
                'valid' => false,
                'message' => "Year {$year->year_label} is already locked",
                'code' => 'ALREADY_LOCKED'
            ];
        }

        // Check if all candidates have marks entered
        $candidatesWithoutMarks = \DB::table('candidate_exam_registrations')
            ->leftJoin('subject_marks', 'candidate_exam_registrations.candidate_id', '=', 'subject_marks.candidate_id')
            ->where('candidate_exam_registrations.exam_year_id', $year->id)
            ->whereNull('subject_marks.id')
            ->count();

        if ($candidatesWithoutMarks > 0) {
            return [
                'valid' => false,
                'message' => "Cannot lock year. $candidatesWithoutMarks candidate(s) still missing marks",
                'code' => 'INCOMPLETE_MARKS',
                'affected_count' => $candidatesWithoutMarks
            ];
        }

        return [
            'valid' => true,
            'message' => "Year {$year->year_label} is ready to be locked",
            'code' => 'READY_TO_LOCK'
        ];
    }

    /**
     * Get the current active exam year or null.
     * Should be called before operations to ensure context.
     *
     * @return ExamYear|null
     */
    public function getCurrentYear(): ?ExamYear
    {
        return ExamYear::active()->first();
    }

    /**
     * Get the next exam year (by year_label).
     *
     * @param ExamYear $currentYear
     * @return ExamYear|null
     */
    public function getNextYear(ExamYear $currentYear): ?ExamYear
    {
        $nextLabel = (int)$currentYear->year_label + 1;
        return ExamYear::where('year_label', (string)$nextLabel)->first();
    }

    /**
     * Ensure exam year is not locked before allowing mutations.
     *
     * @param ExamYear|int $examYear
     * @return ExamYear
     * @throws ValidationException
     */
    public function ensureUnlocked($examYear): ExamYear
    {
        // Handle string year_label (e.g., "2026") and int ID
        if ($examYear instanceof ExamYear) {
            $year = $examYear;
        } elseif (is_int($examYear)) {
            $year = ExamYear::findOrFail($examYear);
        } elseif (is_string($examYear)) {
            // Lookup by year_label
            $year = ExamYear::where('year_label', $examYear)->firstOrFail();
        } else {
            throw new \Exception('Invalid exam year type provided');
        }

        if ($year->is_locked) {
            throw ValidationException::withMessages([
                'exam_year' => "Year {$year->year_label} is locked and read-only"
            ]);
        }

        return $year;
    }
}
