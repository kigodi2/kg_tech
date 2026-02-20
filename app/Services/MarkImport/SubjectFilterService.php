<?php

namespace App\Services\MarkImport;

use App\Models\Candidate;
use App\Models\ExamType;
use Illuminate\Support\Collection;

/**
 * SubjectFilterService
 * 
 * Provides intelligent filtering of ACSEE subjects based on actual
 * candidate registrations in a specific school for a given exam year.
 * 
 * Query Chain:
 * Subjects → CandidateSubjectSelections → CandidateExamRegistrations
 *   (by exam_type + year) → Candidates (by school) → Subjects (DISTINCT)
 * 
 * This ensures only subjects that are actually taken by registered
 * ACSEE candidates appear in the mark import UI.
 */
class SubjectFilterService
{
    /**
     * Get ACSEE subjects offered by FORMALLY REGISTERED candidates in a school for a year
     *
     * IMPORTANT: Only uses candidates with CandidateExamRegistration records.
     * Candidates must be formally registered through /registration/candidates.
     * 
     * Chain: Candidate (school_id) → CandidateExamRegistration (exam_year_id) 
     *   → CandidateSubjectSelection → Subjects (DISTINCT)
     * 
     * @param int $schoolId
     * @param int $examYear Either exam year ID or integer year (will be converted to ID)
     * @return Collection Subject collection with id, code, name, written_papers, has_practical, has_project
     */
    public function getSubjectsBySchoolAndYear(int $schoolId, int $examYear): Collection
    {
        // Get ACSEE exam type
        $acsee = ExamType::where('code', 'ACSEE')->first();
        
        if (!$acsee) {
            return collect([]);
        }

        // Resolve examYear parameter to exam_year_id
        // If it's a 4-digit year, try to find matching exam_year record
        $examYearId = $this->resolveExamYearId($examYear);
        
        if (!$examYearId) {
            return collect([]);
        }

        // Get subjects directly from candidate_subject_selections
        // This works for ALL candidates (both government and private schools)
        // regardless of whether they have a combination code set
        // Chain: Subjects → CandidateSubjectSelections → CandidateExamRegistrations → Candidates (school_id)
        $subjects = \App\Models\Subject::query()
            ->distinct()
            ->select('subjects.id', 'subjects.code', 'subjects.name', 
                     'subjects.written_papers', 'subjects.has_practical', 'subjects.has_project')
            ->join('candidate_subject_selections', 'subjects.id', '=', 'candidate_subject_selections.subject_id')
            ->join('candidate_exam_registrations', function ($join) use ($acsee, $examYearId) {
                $join->on('candidate_subject_selections.candidate_id', '=', 'candidate_exam_registrations.candidate_id')
                     ->on('candidate_subject_selections.exam_year_id', '=', 'candidate_exam_registrations.exam_year_id')
                     ->where('candidate_exam_registrations.exam_type_id', '=', $acsee->id)
                     ->where('candidate_exam_registrations.exam_year_id', '=', $examYearId);
            })
            ->join('candidates', 'candidate_exam_registrations.candidate_id', '=', 'candidates.id')
            ->where('candidates.school_id', '=', $schoolId)
            ->where('candidate_subject_selections.exam_type_id', '=', $acsee->id)
            ->where('candidate_subject_selections.exam_year_id', '=', $examYearId)
            ->where('subjects.exam_type_id', '=', $acsee->id)
            ->where('subjects.is_active', '=', true)
            ->orderBy('subjects.code')
            ->get();

        return $subjects;
    }

    /**
     * Check if a school has FORMALLY REGISTERED ACSEE candidates for a given exam year
     *
     * IMPORTANT: Only returns true if candidates have CandidateExamRegistration records
     * for the specified year.
     * 
     * @param int $schoolId
     * @param int $examYear Either exam year ID or integer year
     * @return bool
     */
    public function schoolHasACSEECandidates(int $schoolId, int $examYear): bool
    {
        $acsee = ExamType::where('code', 'ACSEE')->first();
        
        if (!$acsee) {
            return false;
        }

        // Resolve to exam_year_id FK
        $examYearId = $this->resolveExamYearId($examYear);
        
        if (!$examYearId) {
            return false;
        }

        // Check only formally registered candidates (CandidateExamRegistration)
        return \App\Models\CandidateExamRegistration::query()
            ->whereHas('candidate', function ($query) use ($schoolId) {
                $query->where('school_id', $schoolId);
            })
            ->where('exam_type_id', $acsee->id)
            ->where('exam_year_id', $examYearId)
            ->exists();
    }

    /**
     * Get count of FORMALLY REGISTERED ACSEE candidates in a school for a year
     *
     * IMPORTANT: Only counts candidates with CandidateExamRegistration records.
     * 
     * @param int $schoolId
     * @param int $examYear Either exam year ID or integer year
     * @return int
     */
    public function getACSEECandidateCount(int $schoolId, int $examYear): int
    {
        $acsee = ExamType::where('code', 'ACSEE')->first();
        
        if (!$acsee) {
            return 0;
        }

        // Resolve to exam_year_id FK
        $examYearId = $this->resolveExamYearId($examYear);
        
        if (!$examYearId) {
            return 0;
        }

        // Count only formally registered candidates (CandidateExamRegistration)
        return \App\Models\CandidateExamRegistration::query()
            ->whereHas('candidate', function ($query) use ($schoolId) {
                $query->where('school_id', $schoolId);
            })
            ->where('exam_type_id', $acsee->id)
            ->where('exam_year_id', $examYearId)
            ->count();
    }

    /**
     * Get subject enrollment stats for debugging/audit
     *
     * Returns array of [subject_code => candidate_count]
     * Uses exam_year_id FK for strict year isolation.
     * 
     * @param int $schoolId
     * @param int $examYear Either exam year ID or integer year
     * @return array
     */
    public function getSubjectEnrollmentStats(int $schoolId, int $examYear): array
    {
        $acsee = ExamType::where('code', 'ACSEE')->first();
        
        if (!$acsee) {
            return [];
        }

        // Resolve to exam_year_id FK
        $examYearId = $this->resolveExamYearId($examYear);
        
        if (!$examYearId) {
            return [];
        }

        $stats = \App\Models\Subject::query()
            ->select('subjects.code')
            ->selectRaw('COUNT(DISTINCT candidate_subject_selections.candidate_id) as candidate_count')
            ->join('candidate_subject_selections', 'subjects.id', '=', 'candidate_subject_selections.subject_id')
            ->join('candidate_exam_registrations', function ($join) use ($acsee, $examYearId) {
                $join->on('candidate_subject_selections.candidate_id', '=', 'candidate_exam_registrations.candidate_id')
                     ->on('candidate_subject_selections.exam_year_id', '=', 'candidate_exam_registrations.exam_year_id')
                     ->where('candidate_exam_registrations.exam_type_id', '=', $acsee->id)
                     ->where('candidate_exam_registrations.exam_year_id', '=', $examYearId);
            })
            ->join('candidates', 'candidate_exam_registrations.candidate_id', '=', 'candidates.id')
            ->where('candidates.school_id', '=', $schoolId)
            ->where('candidate_subject_selections.exam_type_id', '=', $acsee->id)
            ->where('candidate_subject_selections.exam_year_id', '=', $examYearId)
            ->where('subjects.exam_type_id', '=', $acsee->id)
            ->where('subjects.is_active', '=', true)
            ->groupBy('subjects.id', 'subjects.code')
            ->get();

        return $stats->pluck('candidate_count', 'code')->toArray();
    }

    /**
     * Resolve an exam year parameter to exam_year_id.
     *
     * Handles both:
     * - Integer exam_year_id (returns as-is)
     * - Integer year label like 2024 (finds matching exam_year record)
     *
     * @param int $examYear
     * @return int|null exam_year_id, or null if not found
     */
    private function resolveExamYearId(int $examYear): ?int
    {
        // If it's a small ID (1-100), it's likely an exam_year_id
        if ($examYear < 100) {
            return \App\Models\ExamYear::find($examYear)?->id;
        }

        // Otherwise treat as a year label (2024, 2025, etc.)
        return \App\Models\ExamYear::where('year_label', (string)$examYear)->first()?->id;
    }
}
