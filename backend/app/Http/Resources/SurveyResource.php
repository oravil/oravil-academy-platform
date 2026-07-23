<?php

namespace App\Http\Resources;

use App\Application\Survey\SurveyDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SurveyDetail */
class SurveyResource extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'survey_id' => $this->surveyId,
            'module_id' => $this->moduleId,
            'title' => $this->title,
            'questions' => array_map(
                fn ($question): array => [
                    'survey_question_id' => $question->surveyQuestionId,
                    'position' => $question->position,
                    'question_text' => $question->questionText,
                    'question_type' => $question->questionType,
                    'required' => $question->required,
                ],
                $this->questions,
            ),
        ];
    }
}
