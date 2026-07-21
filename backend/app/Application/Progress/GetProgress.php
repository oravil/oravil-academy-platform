<?php

namespace App\Application\Progress;

use App\Application\LearningPath\Exceptions\ModuleNotFoundException;
use App\Application\Progress\Contracts\SurveyRepository;

/**
 * Use case for GET /v1/learners/me/progress/{module_id} (OA-MVP-007).
 * Owning feature domain: Progress (OA-MVP-008 Use Case Ownership table).
 */
final class GetProgress
{
    public function __construct(
        private readonly ModuleProgressService $progress,
        private readonly SurveyRepository $surveys,
    ) {}

    /**
     * @throws ModuleNotFoundException
     */
    public function handle(string $learnerId, string $moduleId): LearnerProgress
    {
        $progress = $this->progress->compute($learnerId, $moduleId);

        return new LearnerProgress(
            moduleId: $progress->moduleId,
            lessonsComplete: $progress->lessonsComplete,
            lessonsTotal: $progress->lessonsTotal,
            currentLessonId: $progress->currentLessonId,
            moduleStatus: $progress->moduleStatus,
            surveySubmitted: $this->surveys->hasSubmittedResponse($learnerId, $moduleId),
        );
    }
}
