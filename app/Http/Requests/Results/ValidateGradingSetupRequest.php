<?php

namespace App\Http\Requests\Results;

use Illuminate\Foundation\Http\FormRequest;

class ValidateGradingSetupRequest extends FormRequest
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
            'rules' => ['required', 'array', 'min:1'],
            'rules.*.grade' => ['required', 'string', 'max:4'],
            'rules.*.min_percentage' => ['required', 'numeric', 'between:0,100'],
            'rules.*.max_percentage' => ['required', 'numeric', 'between:0,100'],
            'rules.*.points' => ['nullable', 'integer', 'between:1,20'],
        ];
    }
}
