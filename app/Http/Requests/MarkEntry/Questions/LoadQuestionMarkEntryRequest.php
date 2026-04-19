<?php

namespace App\Http\Requests\MarkEntry\Questions;

use Illuminate\Foundation\Http\FormRequest;

class LoadQuestionMarkEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('mark-entry.questions') ?? false;
    }

    public function rules(): array
    {
        return [
            'candidate_no' => ['required', 'string', 'max:100'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
        ];
    }
}
