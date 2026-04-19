<?php

namespace App\Http\Requests\Results;

use Illuminate\Foundation\Http\FormRequest;

class SaveGradingConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exam_year_id' => ['required', 'integer', 'exists:exam_years,id'],
            'config_id' => ['nullable', 'integer', 'exists:grading_profiles,id'],
            'name' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'grading_rules' => ['required', 'array', 'min:1'],
            'grading_rules.*.id' => ['nullable', 'integer', 'exists:grading_rules,id'],
            'grading_rules.*.grade' => ['required', 'string', 'max:4'],
            'grading_rules.*.name' => ['nullable', 'string', 'max:60'],
            'grading_rules.*.min_mark' => ['required', 'numeric', 'between:0,100'],
            'grading_rules.*.max_mark' => ['required', 'numeric', 'between:0,100'],
            'grading_rules.*.points' => ['nullable', 'numeric', 'between:0,20'],
            'grading_rules.*.is_principal' => ['nullable', 'boolean'],
            'grading_rules.*.is_subsidiary' => ['nullable', 'boolean'],
            'grading_rules.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'grading_rules.*.is_disabled' => ['nullable', 'boolean'],

            'gpa_settings' => ['required', 'array'],
            'gpa_settings.method' => ['required', 'string', 'max:50'],
            'gpa_settings.max_gpa' => ['nullable', 'numeric', 'min:0'],
            'gpa_settings.rounding_decimals' => ['nullable', 'integer', 'between:0,6'],
            'gpa_settings.rounding_mode' => ['nullable', 'in:half_up,half_down,ceil,floor'],
            'gpa_settings.principal_count' => ['nullable', 'integer', 'min:1'],
            'gpa_settings.include_subsidiary' => ['nullable', 'boolean'],

            'gpa_grade_points' => ['required', 'array', 'min:1'],
            'gpa_grade_points.*.grade' => ['required', 'string', 'max:4'],
            'gpa_grade_points.*.gpa_point_value' => ['required', 'numeric', 'between:0,20'],

            'division_rules' => ['required', 'array', 'min:1'],
            'division_rules.*.division_label' => ['required', 'string', 'max:10'],
            'division_rules.*.min_points' => ['required', 'numeric', 'min:0'],
            'division_rules.*.max_points' => ['required', 'numeric', 'min:0'],
            'division_rules.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'division_rules.*.notes' => ['nullable', 'string', 'max:100'],
            'division_rules.*.is_disabled' => ['nullable', 'boolean'],

            'competence_rules' => ['required', 'array', 'min:1'],
            'competence_rules.*.level_label' => ['required', 'string', 'max:50'],
            'competence_rules.*.min_value' => ['required', 'numeric', 'min:0'],
            'competence_rules.*.max_value' => ['required', 'numeric', 'min:0'],
            'competence_rules.*.basis' => ['required', 'in:GPA,POINTS,MARKS,GRADE'],
            'competence_rules.*.color_code' => ['nullable', 'string', 'max:20'],
            'competence_rules.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'competence_rules.*.is_disabled' => ['nullable', 'boolean'],
        ];
    }
}
