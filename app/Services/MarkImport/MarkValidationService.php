<?php

namespace App\Services\MarkImport;

use App\Models\MarkImportBatch;
use App\Models\RawMark;
use App\Models\Candidate;
use App\Models\ExamType;

class MarkValidationService
{
    /**
     * Validate a single raw mark record
     */
    public function validateRawMark(RawMark $rawMark, MarkImportBatch $batch): array
    {
        $errors = [];
        $subject = $batch->subject;
        $candidate = $rawMark->candidate;

        // Rule 1: Candidate must exist
        if (!$candidate) {
            $errors[] = "Candidate with index number '{$rawMark->candidate_index_number}' not found";
            return $errors;
        }

        // Rule 2: Candidate must be registered for ACSEE in the exam year
        $acseeExamType = ExamType::where('code', 'ACSEE')->first();
        if (!$acseeExamType) {
            $errors[] = "ACSEE exam type not found in system";
            return $errors;
        }

        $registration = $candidate->examRegistrations()
            ->where('exam_type_id', $acseeExamType->id)
            ->where('year', $batch->exam_year)
            ->first();

        if (!$registration) {
            $errors[] = "Candidate is not registered for ACSEE in year {$batch->exam_year}";
            return $errors;
        }

        // Rule 3: Retrieve candidate's registered combination and validate subject
        $candidateCombination = $this->getCandidateCombination($candidate, $batch->exam_year);
        if (!$candidateCombination) {
            $errors[] = "Candidate's ACSEE combination not found";
            return $errors;
        }

        if (!$this->subjectInCombination($candidateCombination, $subject)) {
            $errors[] = "Subject '{$subject->code}' is not registered under candidate's ACSEE combination";
            return $errors;
        }

        // Rule 4: Validate marks presence — NECTA-aligned
        $structureResult = $this->validateMarksStructure($rawMark, $subject);

        if (!empty($structureResult['errors'])) {
            return array_merge($errors, $structureResult['errors']);
        }

        // Handle warnings and subject_status (X/ABS)
        if (!empty($structureResult['warnings'])) {
            $rawMark->update([
                'has_warnings' => true,
                'warning_messages' => $structureResult['warnings'],
                'subject_status' => $structureResult['subject_status'],
                'status_reason' => $structureResult['warnings'][0] ?? null,
            ]);
        }

        // Rule 5: Validate mark ranges (skip for absent/INC candidates)
        if (!in_array($structureResult['subject_status'], ['X', 'INC'])) {
            $rangeErrors = $this->validateMarkRanges($rawMark, $subject);
            if (!empty($rangeErrors)) {
                return array_merge($errors, $rangeErrors);
            }
        }

        return $errors;
    }

    /**
     * Get candidate's registered combination for a specific year
     * Derived from candidate registration data, with fallback to candidate.combination field
     */
    private function getCandidateCombination($candidate, $year)
    {
        $acseeExamType = ExamType::where('code', 'ACSEE')->first();
        if (!$acseeExamType) {
            return null;
        }

        // Get candidate's registration for the year
        $registration = $candidate->examRegistrations()
            ->where('exam_type_id', $acseeExamType->id)
            ->where('year', $year)
            ->first();

        if (!$registration) {
            return null;
        }

        // Get subjects the candidate selected for this exam/year
        $subjectIds = $candidate->subjectSelections()
            ->where('exam_type_id', $acseeExamType->id)
            ->where('year', $year)
            ->pluck('subject_id')
            ->toArray();

        if (!empty($subjectIds)) {
            // Find combination that contains ALL candidate's selected subjects
            $combinations = $acseeExamType->combinations()
                ->active()
                ->get();

            foreach ($combinations as $combo) {
                $comboSubjectIds = $combo->subjects()->pluck('subject_id')->toArray();

                // Check if all candidate's subjects are in this combination
                if (count(array_intersect($subjectIds, $comboSubjectIds)) === count($subjectIds)) {
                    return $combo;
                }
            }
        }

        // Fallback: match by candidate.combination field (case-insensitive)
        $candidateCombo = $candidate->combination ?? null;
        if ($candidateCombo) {
            $normalized = strtoupper(preg_replace('/\s+/', '', trim($candidateCombo)));
            $combo = $acseeExamType->combinations()
                ->active()
                ->get()
                ->first(function ($c) use ($normalized) {
                    return strtoupper(preg_replace('/\s+/', '', trim($c->code))) === $normalized;
                });

            if ($combo) {
                return $combo;
            }
        }

        return null;
    }

    /**
     * Check if subject belongs to candidate's combination
     */
    private function subjectInCombination($combination, $subject): bool
    {
        if (!$combination || !$subject) {
            return false;
        }

        return $combination->subjects()->where('subject_id', $subject->id)->exists();
    }

    /**
     * Validate marks structure with NECTA-aligned missing-paper rules.
     *
     * Returns ['errors' => [...], 'warnings' => [...], 'subject_status' => 'X'|null]
     *
     * RULE 1 – ALL_PAPERS_MISSING → warning (SUBJECT_ABSENT_X, non-blocking)
     * RULE 2 – PARTIAL_PAPERS_MISSING → actionable warning (INC, non-blocking for moderation)
     */
    private function validateMarksStructure(RawMark $rawMark, $subject): array
    {
        $requiredComponents = [];
        $filledComponents = [];
        $missingComponents = [];

        for ($i = 1; $i <= $subject->written_papers; $i++) {
            $field = "paper_{$i}_marks";
            $requiredComponents[] = "Paper {$i}";
            $value = $rawMark->$field;
            if ($value === null || $value === '') {
                $missingComponents[] = "Paper {$i}";
            } else {
                $filledComponents[] = "Paper {$i}";
            }
        }

        if ($subject->has_practical) {
            $requiredComponents[] = 'Practical';
            if ($rawMark->practical_marks === null || $rawMark->practical_marks === '') {
                $missingComponents[] = 'Practical';
            } else {
                $filledComponents[] = 'Practical';
            }
        }

        if ($subject->has_project) {
            $requiredComponents[] = 'Project';
            if ($rawMark->project_marks === null || $rawMark->project_marks === '') {
                $missingComponents[] = 'Project';
            } else {
                $filledComponents[] = 'Project';
            }
        }

        if (empty($requiredComponents)) {
            return ['errors' => [], 'warnings' => [], 'subject_status' => null];
        }

        // ALL missing → SUBJECT_ABSENT ('X') — warning, non-blocking
        if (count($missingComponents) === count($requiredComponents)) {
            return [
                'errors' => [],
                'warnings' => ["All required papers missing (" . implode(', ', $missingComponents) . "). Candidate marked as 'X' (did not appear)."],
                'subject_status' => 'X',
            ];
        }

        // PARTIAL missing → actionable warning (INC), non-blocking for moderation
        if (!empty($missingComponents)) {
            $warnings = [];
            foreach ($missingComponents as $component) {
                $warnings[] = "Incomplete: {$component} marks missing. Has marks for: " . implode(', ', $filledComponents) . ". Pending moderation (Accept as INC or Reject).";
            }
            return ['errors' => [], 'warnings' => $warnings, 'subject_status' => 'INC'];
        }

        // All filled → valid
        return ['errors' => [], 'warnings' => [], 'subject_status' => null];
    }

    /**
     * Validate mark value ranges
     */
    private function validateMarkRanges(RawMark $rawMark, $subject): array
    {
        $errors = [];
        $maxMarks = 100;
        $maxPracticalMarks = 50;

        // Validate written paper marks
        for ($i = 1; $i <= $subject->written_papers; $i++) {
            $field = "paper_{$i}_marks";
            $marks = $rawMark->$field;

            if ($marks !== null && $marks !== '') {
                if (!is_numeric($marks)) {
                    $errors[] = "Paper {$i} marks must be numeric (got: {$marks})";
                } elseif ($marks < 0 || $marks > $maxMarks) {
                    $errors[] = "Paper {$i} marks must be between 0 and {$maxMarks} (got: {$marks})";
                } elseif (!$this->isValidMarkIncrement($marks)) {
                    $errors[] = "Paper {$i} marks must be in increments of 0.5 (got: {$marks})";
                }
            }
        }

        // Validate practical marks (0-50 range)
        if ($subject->has_practical && $rawMark->practical_marks !== null) {
            if (!is_numeric($rawMark->practical_marks)) {
                $errors[] = "Practical marks must be numeric";
            } elseif ($rawMark->practical_marks < 0 || $rawMark->practical_marks > $maxPracticalMarks) {
                $errors[] = "Practical marks must be between 0 and {$maxPracticalMarks} (got: {$rawMark->practical_marks})";
            } elseif (!$this->isValidMarkIncrement($rawMark->practical_marks)) {
                $errors[] = "Practical marks must be in increments of 0.5";
            }
        }

        // Validate project marks
        if ($subject->has_project && $rawMark->project_marks !== null) {
            if (!is_numeric($rawMark->project_marks)) {
                $errors[] = "Project marks must be numeric";
            } elseif ($rawMark->project_marks < 0 || $rawMark->project_marks > $maxMarks) {
                $errors[] = "Project marks must be between 0 and {$maxMarks}";
            } elseif (!$this->isValidMarkIncrement($rawMark->project_marks)) {
                $errors[] = "Project marks must be in increments of 0.5";
            }
        }

        return $errors;
    }

    /**
     * Validate that a mark is in increments of 0.5
     */
    private function isValidMarkIncrement($mark): bool
    {
        // Convert to float and check if it's a multiple of 0.5
        $floatMark = (float)$mark;
        $remainder = $floatMark * 2 - floor($floatMark * 2);
        return $remainder < 0.0001 || $remainder > 0.9999;
    }
}
