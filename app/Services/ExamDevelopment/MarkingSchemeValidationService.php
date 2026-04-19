<?php

namespace App\Services\ExamDevelopment;

use App\Models\ExamDevelopment\ExamProject;
use App\Models\ExamDevelopment\MarkingScheme;
use Illuminate\Validation\ValidationException;

class MarkingSchemeValidationService
{
    public function validateScheme(MarkingScheme $scheme): array
    {
        $itemTotal = (float) $scheme->items()->sum('marks');
        $questionMarks = (float) $scheme->question->marks;

        return [
            'is_valid' => round($itemTotal ?: (float) $scheme->total_marks, 2) === round($questionMarks, 2),
            'expected_total' => $questionMarks,
            'actual_total' => $itemTotal ?: (float) $scheme->total_marks,
        ];
    }

    public function ensureValid(MarkingScheme $scheme): void
    {
        $result = $this->validateScheme($scheme);

        if (!$result['is_valid']) {
            throw ValidationException::withMessages([
                'marking_scheme' => ['Marking scheme total must equal the question marks.'],
            ]);
        }
    }

    public function validateProject(ExamProject $project): array
    {
        $project->loadMissing(['papers.sections.slots.assignedQuestion.markingSchemes.items']);
        $errors = [];

        foreach ($project->papers as $paper) {
            foreach ($paper->sections as $section) {
                foreach ($section->slots as $slot) {
                    $scheme = $slot->assignedQuestion?->markingSchemes?->first();
                    if (!$scheme) {
                        $errors[] = "{$slot->slot_label} is missing a marking scheme.";
                        continue;
                    }

                    if (!$this->validateScheme($scheme)['is_valid']) {
                        $errors[] = "{$slot->slot_label} has an invalid marking scheme total.";
                    }
                }
            }
        }

        return ['is_valid' => $errors === [], 'errors' => $errors];
    }
}
