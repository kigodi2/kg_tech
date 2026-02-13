<?php

namespace App\Observers;

use App\Models\CandidateExamRegistration;
use App\Models\CandidateSubjectSelection;
use App\Models\Combination;
use App\Models\ExamType;

/**
 * CandidateExamRegistrationObserver
 *
 * Ensures that whenever an ACSEE exam registration is created,
 * corresponding subject selections are automatically generated
 * based on the candidate's combination.
 *
 * This prevents the issue where registrations exist but subject
 * selections are missing, which would break mark entry.
 */
class CandidateExamRegistrationObserver
{
    /**
     * Handle the CandidateExamRegistration "created" event.
     *
     * Automatically creates subject selections for any ACSEE candidate registration
     * based on the candidate's combination. This ensures mark entry always has
     * subjects available for any school, any year, any time.
     */
    public function created(CandidateExamRegistration $registration): void
    {
        // Only process ACSEE registrations
        $acsee = ExamType::where('code', 'ACSEE')->first();
        if (!$acsee || $registration->exam_type_id !== $acsee->id) {
            return;
        }

        // Get the candidate with fresh data
        $candidate = $registration->candidate;
        if (!$candidate) {
            \Log::error("Candidate not found for registration", [
                'registration_id' => $registration->id,
            ]);
            return;
        }

        // Must have a combination
        if (empty($candidate->combination)) {
            \Log::warning("ACSEE candidate has no combination - subject selections skipped", [
                'candidate_id' => $candidate->id,
                'registration_id' => $registration->id,
            ]);
            return;
        }

        // Get the combination record
        $combination = Combination::where('code', $candidate->combination)
            ->where('exam_type_id', $acsee->id)
            ->first();

        if (!$combination) {
            \Log::error("Combination code not found - cannot create subject selections", [
                'candidate_id' => $candidate->id,
                'combination' => $candidate->combination,
                'registration_id' => $registration->id,
            ]);
            return;
        }

        // Get all subjects for this combination
        $subjects = $combination->subjects()
            ->where('is_active', true)
            ->get();

        if ($subjects->isEmpty()) {
            \Log::error("Combination has no active subjects - cannot create selections", [
                'candidate_id' => $candidate->id,
                'combination' => $candidate->combination,
                'exam_type_id' => $acsee->id,
                'registration_id' => $registration->id,
            ]);
            return;
        }

        // Create subject selections for this registration
        $created = 0;
        foreach ($subjects as $subject) {
            // Check if already exists (avoid duplicates)
            $existing = CandidateSubjectSelection::where('candidate_id', $candidate->id)
                ->where('subject_id', $subject->id)
                ->where('exam_year_id', $registration->exam_year_id)
                ->first();

            // Only create if not already exists
            if (!$existing) {
                CandidateSubjectSelection::create([
                    'candidate_id' => $candidate->id,
                    'exam_type_id' => $acsee->id,
                    'exam_year_id' => $registration->exam_year_id,
                    'subject_id' => $subject->id,
                    'year' => $registration->year ?? (int)$registration->examYear->year_label,
                    'is_active' => true,
                ]);
                $created++;
            }
        }

        // Log successful creation
        if ($created > 0) {
            \Log::info("Subject selections auto-created for ACSEE candidate", [
                'candidate_id' => $candidate->id,
                'combination' => $candidate->combination,
                'selections_created' => $created,
                'exam_year_id' => $registration->exam_year_id,
            ]);
        }
    }
}
