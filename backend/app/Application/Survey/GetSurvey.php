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

/**
 * Use case for GET /v1/modules/{module_id}/survey (OA-MVP-007). Only
 * accessible when the module is complete and the learner has not yet
 * submitted a response (OA-MVP-005 Domain Rules 6, 10).
 */
final class GetSurvey
{
    public function __construct(
        private readonly ModuleProgressService $progress,
        private readonly SurveyContentRepository $surveys,
        private readonly SurveyRepository $surveyResponses,
    ) {}

    /**
     * @throws ModuleNotFoundException
     * @throws ModuleNotCompleteException
     * @throws SurveyAlreadySubmittedException
     * @throws SurveyNotFoundException
     */
    public function handle(string $learnerId, string $moduleId): SurveyDetail
    {
        $progress = $this->progress->compute($learnerId, $moduleId);

        if ($progress->moduleStatus !== ModuleStatus::Complete) {
            throw new ModuleNotCompleteException($moduleId);
        }

        if ($this->surveyResponses->hasSubmittedResponse($learnerId, $moduleId)) {
            throw new SurveyAlreadySubmittedException($moduleId);
        }

        $survey = $this->surveys->findForModule($moduleId);

        if ($survey === null) {
            throw new SurveyNotFoundException($moduleId);
        }

        return $survey;
    }
}
