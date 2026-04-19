<?php

namespace App\Http\Requests\Results;

use Illuminate\Foundation\Http\FormRequest;

class UpsertGradingConfigRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['nullable', 'integer', 'exists:grading_profiles,id'],
            'exam_year_id' => ['required', 'integer', 'exists:exam_years,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'rules' => ['required', 'array', 'min:1'],
            'rules.*.grade' => ['required', 'string', 'max:4'],
            'rules.*.grade_name' => ['nullable', 'string', 'max:60'],
            'rules.*.min_percentage' => ['required', 'numeric', 'between:0,100'],
            'rules.*.max_percentage' => ['required', 'numeric', 'between:0,100'],
            'rules.*.points' => ['nullable', 'integer', 'between:1,20'],
            'rules.*.is_principal' => ['nullable', 'boolean'],
            'rules.*.is_subsidiary' => ['nullable', 'boolean'],
            'rules.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
