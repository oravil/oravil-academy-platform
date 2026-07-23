<?php

namespace App\Application\Survey\Contracts;

use App\Application\Survey\SurveyDetail;
use App\Application\Survey\SurveyResponseResult;

/**
 * Named SurveyContentRepository, not SurveyRepository, to stay distinct from
 * App\Application\Progress\Contracts\SurveyRepository (which answers only
 * "has this learner submitted?"). Two same-named repositories in different
 * namespaces would force aliasing anywhere both are used together (GetSurvey
 * and SubmitSurveyResponse both need both) — not worth the reading cost.
 */
interface SurveyContentRepository
{
    /**
     * Returns the survey and its questions (ordered by position) for the
     * given module, or null if the module has no survey.
     */
    public function findForModule(string $moduleId): ?SurveyDetail;

    /**
     * Returns the survey and its questions (ordered by position) for the
     * given survey id, or null if no survey exists for it (including
     * malformed ids).
     */
    public function findWithQuestions(string $surveyId): ?SurveyDetail;

    /**
     * Persists all answers for one learner's survey submission as a single
     * transaction — either every answer row is written or none are. Throws
     * Illuminate\Database\UniqueConstraintViolationException if a response
     * already exists for this learner and one of the answered questions
     * (learner_id, survey_question_id is unique at the database level) —
     * callers must handle the race window between their own duplicate
     * pre-check and this insert.
     *
     * @param  array<int, array{survey_question_id: string, answer_text: ?string, answer_rating: ?int}>  $answers
     */
    public function createResponses(string $surveyId, string $learnerId, array $answers): SurveyResponseResult;
}
