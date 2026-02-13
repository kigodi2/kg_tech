<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubjectRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'code' => 'required|string|max:30',
            'name' => 'required|string|max:100',
            'category' => 'required|in:ARTS,SCIENCE,BUSINESS',
            'writtenPapers' => 'required|integer|in:1,2,3',
            'hasPractical' => 'boolean',
            'hasProject' => 'boolean',
            'description' => 'nullable|string',
            'max_marks' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Subject code is required',
            'code.unique' => 'Subject code must be unique per exam type',
            'name.required' => 'Subject name is required',
            'category.required' => 'Category is required',
            'category.in' => 'Category must be ARTS, SCIENCE, or BUSINESS',
            'writtenPapers.required' => 'Number of papers is required',
            'writtenPapers.in' => 'Number of papers must be 1, 2, or 3',
        ];
    }
}
