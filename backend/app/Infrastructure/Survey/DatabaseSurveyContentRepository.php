<?php

namespace App\Infrastructure\Survey;

use App\Application\Survey\Contracts\SurveyContentRepository;
use App\Application\Survey\SurveyDetail;
use App\Application\Survey\SurveyQuestionDetail;
use App\Application\Survey\SurveyResponseResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSurveyContentRepository implements SurveyContentRepository
{
    public function findForModule(string $moduleId): ?SurveyDetail
    {
        if (! Str::isUuid($moduleId)) {
            return null;
        }

        $survey = DB::table('surveys')
            ->where('module_id', $moduleId)
            ->first(['id', 'module_id', 'title']);

        if ($survey === null) {
            return null;
        }

        return $this->hydrate($survey);
    }

    public function findWithQuestions(string $surveyId): ?SurveyDetail
    {
        // Guard against malformed ids reaching Postgres as a uuid literal
        // (OA-MVP-008 §Architectural Constraints 14 — no raw database
        // errors in responses). A malformed id is simply not found.
        if (! Str::isUuid($surveyId)) {
            return null;
        }

        $survey = DB::table('surveys')
            ->where('id', $surveyId)
            ->first(['id', 'module_id', 'title']);

        if ($survey === null) {
            return null;
        }

        return $this->hydrate($survey);
    }

    public function createResponses(string $surveyId, string $learnerId, array $answers): SurveyResponseResult
    {
        $submittedAt = now();

        DB::transaction(function () use ($learnerId, $answers, $submittedAt): void {
            foreach ($answers as $answer) {
                DB::table('survey_responses')->insert([
                    'id' => (string) Str::uuid(),
                    'learner_id' => $learnerId,
                    'survey_question_id' => $answer['survey_question_id'],
                    'answer_text' => $answer['answer_text'] ?? null,
                    'answer_rating' => $answer['answer_rating'] ?? null,
                    'submitted_at' => $submittedAt,
                ]);
            }
        });

        return new SurveyResponseResult(
            surveyId: $surveyId,
            submittedAt: $submittedAt->toIso8601String(),
        );
    }

    private function hydrate(object $survey): SurveyDetail
    {
        $questions = DB::table('survey_questions')
            ->where('survey_id', $survey->id)
            ->orderBy('position')
            ->get(['id', 'position', 'question_text', 'question_type', 'required']);

        return new SurveyDetail(
            surveyId: $survey->id,
            moduleId: $survey->module_id,
            title: $survey->title,
            questions: $questions->map(fn (object $question): SurveyQuestionDetail => new SurveyQuestionDetail(
                surveyQuestionId: $question->id,
                position: $question->position,
                questionText: $question->question_text,
                questionType: $question->question_type,
                required: (bool) $question->required,
            ))->all(),
        );
    }
}
