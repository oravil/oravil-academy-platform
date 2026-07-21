<?php

namespace App\Application\Progress\Contracts;

interface SurveyRepository
{
    /**
     * Whether the learner has submitted the module's post-module survey.
     */
    public function hasSubmittedResponse(string $learnerId, string $moduleId): bool;
}
