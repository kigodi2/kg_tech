<?php

namespace App\Http\Requests\Results;

use Illuminate\Foundation\Http\FormRequest;

class GradingPreviewImpactRequest extends FormRequest
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
            'exam_year_id' => ['required', 'integer', 'exists:exam_years,id'],
            'config_id' => ['nullable', 'integer', 'exists:grading_profiles,id'],
            'scope' => ['nullable', 'array'],
            'scope.region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'scope.council_id' => ['nullable', 'integer', 'exists:district_councils,id'],
            'scope.school_id' => ['nullable', 'integer', 'exists:schools,id'],
            'sample_size' => ['nullable', 'regex:/^(ALL|[1-9][0-9]*)$/i'],
        ];
    }
}
