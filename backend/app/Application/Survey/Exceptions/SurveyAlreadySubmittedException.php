<?php

namespace App\Application\Survey\Exceptions;

use RuntimeException;

/**
 * OA-MVP-005 Domain Rule 10 / OA-MVP-007: a learner has at most one Survey
 * Response per Survey. Thrown both when SurveyRepository::hasSubmittedResponse
 * pre-check is already true and when the database's unique
 * (learner_id, survey_question_id) constraint rejects a racing insert
 * (backstop) — both cases surface the same 403.
 */
final class SurveyAlreadySubmittedException extends RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Survey [{$id}] has already been submitted.");
    }
}
