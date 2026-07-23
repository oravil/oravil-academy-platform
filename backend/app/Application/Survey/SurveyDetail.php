<?php

namespace App\Application\Survey;

final class SurveyDetail
{
    /**
     * @param  SurveyQuestionDetail[]  $questions  Ordered ascending by position.
     */
    public function __construct(
        public readonly string $surveyId,
        public readonly string $moduleId,
        public readonly string $title,
        public readonly array $questions,
    ) {}
}
