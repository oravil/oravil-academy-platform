<?php

namespace App\Infrastructure\Progress;

use App\Application\Progress\Contracts\SurveyRepository;
use Illuminate\Support\Facades\DB;

class DatabaseSurveyRepository implements SurveyRepository
{
    public function hasSubmittedResponse(string $learnerId, string $moduleId): bool
    {
        return DB::table('survey_responses')
            ->join('survey_questions', 'survey_questions.id', '=', 'survey_responses.survey_question_id')
            ->join('surveys', 'surveys.id', '=', 'survey_questions.survey_id')
            ->where('surveys.module_id', $moduleId)
            ->where('survey_responses.learner_id', $learnerId)
            ->exists();
    }
}
