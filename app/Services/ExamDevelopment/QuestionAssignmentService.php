<?php

namespace App\Services\ExamDevelopment;

use App\Models\ExamDevelopment\ExamProjectSlot;
use App\Models\ExamDevelopment\PaperQuestion;
use App\Models\ExamDevelopment\Question;
use Illuminate\Validation\ValidationException;

class QuestionAssignmentService
{
    public function assign(ExamProjectSlot $slot, Question $question, array $overrides = []): PaperQuestion
    {
        $this->assertCompatibility($slot, $question);

        $slot->update(['assigned_question_id' => $question->id]);

        return PaperQuestion::query()->updateOrCreate(
            ['exam_project_slot_id' => $slot->id, 'question_id' => $question->id],
            [
                'custom_marks' => $overrides['custom_marks'] ?? $slot->marks_per_question,
                'custom_instructions' => $overrides['custom_instructions'] ?? null,
                'inserted_by' => $overrides['inserted_by'] ?? auth()->id(),
            ]
        );
    }

    public function assertCompatibility(ExamProjectSlot $slot, Question $question): void
    {
        $errors = [];

        if ($question->status !== Question::STATUS_APPROVED) {
            $errors[] = 'Only approved questions can be assigned to a paper slot.';
        }

        if ($slot->question_type !== $question->question_type) {
            $errors[] = 'Question type does not match the slot requirement.';
        }

        if ($slot->marks_per_question !== null && (float) $question->marks !== (float) $slot->marks_per_question) {
            $errors[] = 'Question marks do not match the slot marks.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['question_id' => $errors]);
        }
    }
}
