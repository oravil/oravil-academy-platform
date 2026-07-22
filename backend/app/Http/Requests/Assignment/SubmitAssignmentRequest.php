<?php

namespace App\Http\Requests\Assignment;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Laravel's `required` rule trims strings, so this also covers
            // whitespace-only content (OA-MVP-004 Screen 3 validation copy).
            'content.required' => 'Your assignment is empty. Please write your response before submitting.',
        ];
    }
}
