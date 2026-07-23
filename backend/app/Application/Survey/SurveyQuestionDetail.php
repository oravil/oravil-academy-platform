<?php

namespace App\Application\Survey;

final class SurveyQuestionDetail
{
    public function __construct(
        public readonly string $surveyQuestionId,
        public readonly int $position,
        public readonly string $questionText,
        public readonly string $questionType,
        public readonly bool $required,
    ) {}
}
