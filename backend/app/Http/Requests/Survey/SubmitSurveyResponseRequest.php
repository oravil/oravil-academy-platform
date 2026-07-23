<?php

namespace App\Http\Requests\Survey;

use Illuminate\Foundation\Http\FormRequest;

class SubmitSurveyResponseRequest extends FormRequest
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
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.survey_question_id' => ['required', 'string'],
            'answers.*.answer_text' => ['nullable', 'string'],
            'answers.*.answer_rating' => ['nullable', 'integer', 'between:1,5'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'answers.required' => 'At least one answer is required.',
            'answers.min' => 'At least one answer is required.',
        ];
    }
}
