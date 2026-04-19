<?php

namespace App\Services\ExamDevelopment;

use App\Models\ExamDevelopment\ExamProject;
use App\Models\ExamDevelopment\ExamProjectPaper;

class PaperValidationService
{
    public function validateProject(ExamProject $project): array
    {
        $project->loadMissing(['format.papers.sections.rules', 'papers.sections.slots.assignedQuestion.markingSchemes']);

        $errors = [];
        $warnings = [];

        if (!$project->format?->is_active) {
            $errors[] = 'Every project must reference an active subject format.';
        }

        foreach ($project->papers as $paper) {
            $paperValidation = $this->validatePaper($paper);
            $errors = array_merge($errors, $paperValidation['errors']);
            $warnings = array_merge($warnings, $paperValidation['warnings']);
        }

        return [
            'is_valid' => $errors === [],
            'errors' => array_values(array_unique($errors)),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    public function validatePaper(ExamProjectPaper $paper): array
    {
        $paper->loadMissing(['formatPaper.sections.rules', 'sections.slots.assignedQuestion.markingSchemes']);

        $errors = [];
        $warnings = [];
        $sectionTotal = 0;

        foreach ($paper->sections as $section) {
            $sectionTotal += (float) $section->total_marks;
            $slotTotal = $section->slots->sum(fn ($slot) => (float) ($slot->marks_per_question ?? 0));

            if (round($slotTotal, 2) !== round((float) $section->total_marks, 2)) {
                $errors[] = "Section {$section->section_name} slot marks do not match the section total.";
            }

            foreach ($section->slots as $slot) {
                if ($slot->is_compulsory && !$slot->assigned_question_id) {
                    $errors[] = "{$paper->paper_name} has an unfilled mandatory slot ({$slot->slot_label}).";
                }

                if ($slot->assignedQuestion && $slot->assignedQuestion->markingSchemes->isEmpty()) {
                    $errors[] = "{$slot->slot_label} has no marking scheme.";
                }
            }
        }

        if (round($sectionTotal, 2) !== round((float) $paper->total_marks, 2)) {
            $errors[] = "{$paper->paper_name} section totals do not match the official paper total.";
        }

        if ($paper->project->subject->code === 'CIV' || str_contains(strtoupper($paper->project->subject->name), 'CIVICS')) {
            if ($paper->sections->count() !== 3) {
                $warnings[] = 'Civics is expected to have Sections A, B, and C.';
            }
        }

        if (str_contains(strtoupper($paper->project->subject->name), 'BASIC MATHEMATICS') && round((float) $paper->total_marks, 2) !== 100.0) {
            $errors[] = 'Basic Mathematics paper total must equal 100.';
        }

        if (str_contains(strtoupper($paper->project->subject->name), 'PHYSICS') && $paper->paper_type === 'practical' && $paper->project->papers()->where('paper_type', 'practical')->count() > 0) {
            if ($paper->apparatusLists()->count() === 0 || $paper->confidentialInstructions()->count() === 0) {
                $errors[] = 'Physics practical paper requires apparatus setup and confidential instructions before final approval.';
            }
        }

        return [
            'is_valid' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
