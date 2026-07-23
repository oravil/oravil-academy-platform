<?php

namespace App\Application\Survey;

final class SurveyResponseResult
{
    public function __construct(
        public readonly string $surveyId,
        public readonly string $submittedAt,
    ) {}
}
