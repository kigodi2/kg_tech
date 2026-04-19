<?php

namespace App\Services\ExamDevelopment;

use App\Models\ExamDevelopment\ExamProjectSection;
use App\Models\ExamDevelopment\ExamProjectSlot;
use App\Models\ExamDevelopment\SubjectFormatQuestionRule;

class PaperSlotGenerationService
{
    public function generateForSection(ExamProjectSection $section): array
    {
        $section->slots()->delete();
        $created = [];

        $rules = $section->formatSection?->rules()->orderBy('display_order')->get() ?? collect();
        foreach ($rules as $rule) {
            $created = array_merge($created, $this->generateSlotsFromRule($section, $rule));
        }

        return $created;
    }

    protected function generateSlotsFromRule(ExamProjectSection $section, SubjectFormatQuestionRule $rule): array
    {
        $start = $rule->question_no_from ?? ($section->slots()->max('question_no') + 1);
        $end = $rule->question_no_to ?? $start;
        $slots = [];

        for ($number = $start; $number <= $end; $number++) {
            $choiceGroup = null;
            if (!$rule->is_compulsory || $rule->answer_mode !== 'all') {
                $choiceGroup = sprintf('%s-choice-%s-%s', $section->id, $rule->id, $rule->question_no_from ?? $number);
            }

            $slots[] = $section->slots()->create([
                'rule_id' => $rule->id,
                'slot_label' => 'Q' . $number,
                'question_no' => $number,
                'question_type' => $rule->question_type,
                'items_per_question' => $rule->items_per_question,
                'marks_per_item' => $rule->marks_per_item,
                'marks_per_question' => $rule->marks_per_question ?? $rule->total_marks,
                'is_compulsory' => $rule->is_compulsory,
                'choice_group' => $choiceGroup,
                'display_order' => count($slots) + 1,
            ])->toArray();
        }

        return $slots;
    }
}
