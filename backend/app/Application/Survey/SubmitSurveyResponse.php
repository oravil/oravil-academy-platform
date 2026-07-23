<?php

namespace App\Application\Survey;

use App\Application\LearningPath\Exceptions\ModuleNotFoundException;
use App\Application\Progress\Contracts\SurveyRepository;
use App\Application\Progress\Exceptions\ModuleNotCompleteException;
use App\Application\Progress\ModuleProgressService;
use App\Application\Survey\Contracts\SurveyContentRepository;
use App\Application\Survey\Exceptions\SurveyAlreadySubmittedException;
use App\Application\Survey\Exceptions\SurveyNotFoundException;
use App\Domain\Progress\ModuleStatus;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

/**
 * Use case for POST /v1/surveys/{survey_id}/responses (OA-MVP-007). The
 * static request shape (answers present, each item well-formed) is
 * validated by SubmitSurveyResponseRequest; the rules below are dynamic —
 * they depend on which questions and types are actually stored for this
 * survey — so they belong here, mirroring SubmitAssignment's split between
 * static FormRequest rules and the dynamic minimum-word-count check.
 */
final class SubmitSurveyResponse
{
    public function __construct(
        private readonly SurveyContentRepository $surveys,
        private readonly ModuleProgressService $progress,
        private readonly SurveyRepository $surveyResponses,
    ) {}

    /**
     * @param  array<int, array{survey_question_id: string, answer_text: ?string, answer_rating: ?int}>  $answers
     *
     * @throws SurveyNotFoundException
     * @throws ModuleNotFoundException
     * @throws ModuleNotCompleteException
     * @throws SurveyAlreadySubmittedException
     * @throws ValidationException
     */
    public function handle(string $learnerId, string $surveyId, array $answers): SurveyResponseResult
    {
        $survey = $this->surveys->findWithQuestions($surveyId);

        if ($survey === null) {
            throw new SurveyNotFoundException($surveyId);
        }

        $progress = $this->progress->compute($learnerId, $survey->moduleId);

        if ($progress->moduleStatus !== ModuleStatus::Complete) {
            throw new ModuleNotCompleteException($survey->moduleId);
        }

        if ($this->surveyResponses->hasSubmittedResponse($learnerId, $survey->moduleId)) {
            throw new SurveyAlreadySubmittedException($surveyId);
        }

        $this->validateAnswers($survey->questions, $answers);

        try {
            return $this->surveys->createResponses($surveyId, $learnerId, $answers);
        } catch (UniqueConstraintViolationException) {
            // Race backstop: two concurrent requests both passed the
            // hasSubmittedResponse pre-check above before either committed.
            // The database's unique (learner_id, survey_question_id)
            // constraint is the real guarantee; this translates its
            // violation to the same 403 as the pre-check.
            throw new SurveyAlreadySubmittedException($surveyId);
        }
    }

    /**
     * @param  SurveyQuestionDetail[]  $questions
     * @param  array<int, array{survey_question_id: string, answer_text: ?string, answer_rating: ?int}>  $answers
     *
     * @throws ValidationException
     */
    private function validateAnswers(array $questions, array $answers): void
    {
        $questionsById = [];
        foreach ($questions as $question) {
            $questionsById[$question->surveyQuestionId] = $question;
        }

        $answersByQuestionId = [];
        foreach ($answers as $answer) {
            $answersByQuestionId[$answer['survey_question_id']] = $answer;
        }

        $errors = [];

        foreach (array_keys($answersByQuestionId) as $questionId) {
            if (! isset($questionsById[$questionId])) {
                $errors['answers'][] = "Question [{$questionId}] does not belong to this survey.";
            }
        }

        foreach ($questions as $question) {
            $answer = $answersByQuestionId[$question->surveyQuestionId] ?? null;

            if ($answer === null) {
                if ($question->required) {
                    $errors['answers'][] = "Question [{$question->surveyQuestionId}] is required.";
                }

                continue;
            }

            if ($question->questionType === 'rating') {
                $rating = $answer['answer_rating'] ?? null;

                if (! is_int($rating) || $rating < 1 || $rating > 5) {
                    $errors['answers'][] = "Question [{$question->surveyQuestionId}] requires an integer rating between 1 and 5.";
                }
            } else {
                $text = trim((string) ($answer['answer_text'] ?? ''));

                if ($text === '') {
                    $errors['answers'][] = "Question [{$question->surveyQuestionId}] requires a non-empty text answer.";
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
