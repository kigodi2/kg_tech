<?php

namespace App\Services\MarkImport;

use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * ScoresheetService
 *
 * Handles scoresheet generation with audit watermarking, document hashing,
 * and bulk export functionality.
 */
class ScoresheetService
{
    /**
     * Generate scoresheet data for a given school, subject, and exam year
     *
     * IMPORTANT: Uses registrations as authority, not candidates directly.
     * A candidate appears ONLY IF:
     * - They are registered for the exam year
     * - Their combination includes the subject
     *
     * @param int $examYearId
     * @param int $schoolId
     * @param int $subjectId
     * @return array Scoresheet data with registrations, metadata, and hash
     */
    public function generateScoresheetData(int $examYearId, int $schoolId, int $subjectId): array
    {
        // Validate parameters
        $examYear = ExamYear::findOrFail($examYearId);
        $school = School::findOrFail($schoolId);
        $subject = Subject::findOrFail($subjectId);

        // Ensure subject is ACSEE
        $acsee = ExamType::where('code', 'ACSEE')->firstOrFail();
        if ($subject->exam_type_id !== $acsee->id) {
            throw new \Exception('Subject must be ACSEE type');
        }

        // Get registrations for this school/year where candidate's combination includes the subject
        $registrations = $this->getRegistrationsForSubject($schoolId, $examYearId, $subjectId, $acsee->id);

        // Safety check: ensure we have candidates for this subject
        if ($registrations->isEmpty()) {
            throw new \Exception(
                "No candidates registered for {$subject->code} in {$examYear->year_label} at this school."
            );
        }

        // Get paper structure for this subject
        $paperStructure = [
            'written_papers' => $subject->written_papers ?? 2,
            'has_practical' => $subject->has_practical ?? false,
            'has_project' => $subject->has_project ?? false,
        ];

        // Generate document hash for integrity
        $hash = $this->generateDocumentHash($examYearId, $schoolId, $subjectId, $registrations);

        // Generate timestamp
        $timestamp = now();

        return [
            'exam_year' => $examYear,
            'school' => $school,
            'subject' => $subject,
            'registrations' => $registrations,
            'paper_structure' => $paperStructure,
            'document_hash' => $hash,
            'timestamp' => $timestamp,
            'total_candidates' => $registrations->count(),
        ];
    }

    /**
     * Get registrations for a specific subject
     *
     * A candidate appears ONLY IF:
     * - Registered for the exam year
     * - Their combination includes the subject
     *
     * Uses combination-based filtering (matches candidate.combination → combination → combination_subject)
     *
     * @param int $schoolId
     * @param int $examYearId
     * @param int $subjectId
     * @param int $examTypeId
     * @return Collection
     */
    private function getRegistrationsForSubject(int $schoolId, int $examYearId, int $subjectId, int $examTypeId): Collection
    {
        return \App\Models\CandidateExamRegistration::query()
            ->where('exam_year_id', $examYearId)
            ->where('exam_type_id', $examTypeId)
            ->whereHas('candidate', function ($query) use ($schoolId, $subjectId) {
                $query->where('school_id', $schoolId)
                      // Filter to only candidates whose combination includes this subject
                      ->whereHas('combination.subjects', function ($subQuery) use ($subjectId) {
                          $subQuery->where('subject_id', $subjectId);
                      }, '>=', 1); // Ensure at least one subject matches
            })
            ->with([
                'candidate:id,candidate_id,full_name,gender,combination',
            ])
            ->orderBy('id')
            ->get()
            ->sortBy(fn($reg) => $reg->candidate->candidate_id); // Sort by index number
    }

    /**
     * Generate SHA-256 hash for document integrity
     *
     * Hash includes:
     * - exam_year_id
     * - school_id
     * - subject_id
     * - sorted candidate index numbers from registrations
     * - generation timestamp (minute precision)
     *
     * @param int $examYearId
     * @param int $schoolId
     * @param int $subjectId
     * @param Collection $registrations
     * @return string
     */
    private function generateDocumentHash(int $examYearId, int $schoolId, int $subjectId, Collection $registrations): string
    {
        // Extract candidate index numbers in sorted order from registrations
        $candidateIndices = $registrations
            ->map(fn($reg) => $reg->candidate->candidate_id)
            ->sort()
            ->implode(',');

        // Timestamp with minute precision (for reproducibility)
        $timestamp = now()->format('Y-m-d H:i');

        // Build hash source
        $hashSource = sprintf(
            '%d|%d|%d|%s|%s',
            $examYearId,
            $schoolId,
            $subjectId,
            $candidateIndices,
            $timestamp
        );

        return hash('sha256', $hashSource);
    }

    /**
     * Get hash prefix for display (first 10 characters)
     *
     * @param string $fullHash
     * @return string
     */
    public function getHashPrefix(string $fullHash): string
    {
        return substr($fullHash, 0, 10);
    }

    /**
     * Log scoresheet action for audit trail
     *
     * @param string $action (print, bulk_export)
     * @param int $examYearId
     * @param int $schoolId
     * @param int|null $subjectId
     * @param string $documentHash
     * @return void
     */
    public function logScoresheetAction(
        string $action,
        int $examYearId,
        int $schoolId,
        ?int $subjectId,
        string $documentHash
    ): void {
        Log::channel('audit')->info('Scoresheet Action', [
            'action' => $action,
            'user_id' => auth()->id() ?? 'system',
            'exam_year_id' => $examYearId,
            'school_id' => $schoolId,
            'subject_id' => $subjectId,
            'document_hash' => $documentHash,
            'timestamp' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Get registered subjects for a school in an exam year
     *
     * @param int $schoolId
     * @param int $examYearId
     * @return Collection
     */
    public function getRegisteredSubjects(int $schoolId, int $examYearId): Collection
    {
        $acsee = ExamType::where('code', 'ACSEE')->firstOrFail();

        // Get subjects that have registered candidates through combinations
        return Subject::query()
            ->distinct()
            ->select('subjects.id', 'subjects.code', 'subjects.name', 
                     'subjects.written_papers', 'subjects.has_practical', 'subjects.has_project')
            ->join('combination_subject', 'subjects.id', '=', 'combination_subject.subject_id')
            ->join('combinations', 'combination_subject.combination_id', '=', 'combinations.id')
            ->join('candidates', 'combinations.code', '=', 'candidates.combination')
            ->join('candidate_exam_registrations', function ($join) use ($acsee, $examYearId) {
                $join->on('candidates.id', '=', 'candidate_exam_registrations.candidate_id')
                     ->where('candidate_exam_registrations.exam_type_id', '=', $acsee->id)
                     ->where('candidate_exam_registrations.exam_year_id', '=', $examYearId);
            })
            ->where('candidates.school_id', '=', $schoolId)
            ->where('candidates.exam_type', '=', $acsee->code)
            ->where('subjects.exam_type_id', '=', $acsee->id)
            ->where('subjects.is_active', true)
            ->orderBy('subjects.code')
            ->get();
    }
}
