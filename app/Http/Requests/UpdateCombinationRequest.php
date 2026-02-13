<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCombinationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:combinations,code,' . $this->route('id'),
            ],
            'category' => [
                'required',
                'string',
                'in:ARTS,SCIENCE,BUSINESS',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'subject_ids' => [
                'nullable',
                'array',
                'min:1',
            ],
            'subject_ids.*' => [
                'integer',
                'exists:subjects,id',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'code.required' => 'Combination code is required',
            'code.unique' => 'A combination with this code already exists',
            'category.required' => 'Category is required',
            'category.in' => 'Category must be one of: ARTS, SCIENCE, BUSINESS',
            'subject_ids.min' => 'At least one subject must be selected',
            'subject_ids.*.exists' => 'One or more selected subjects do not exist',
        ];
    }
}
