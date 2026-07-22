<?php

namespace App\Application\Progress;

use App\Application\LearningPath\Contracts\ModuleRepository;
use App\Application\LearningPath\Exceptions\ModuleNotFoundException;
use App\Application\LearningPath\LessonContent;
use App\Application\Progress\Contracts\SurveyRepository;
use App\Application\Progress\Exceptions\ModuleNotCompleteException;
use App\Domain\Progress\LessonStatus;
use App\Domain\Progress\ModuleStatus;

/**
 * Use case for GET /v1/modules/{module_id}/completion (OA-MVP-007).
 * Owning feature domain: Progress (OA-MVP-008 Use Case Ownership table:
 * "Enforces completion precondition; delegates to Learning Path for lesson
 * list"). Pure composition over ModuleProgressService, ModuleRepository, and
 * SurveyRepository — no new domain rule (OA-MVP-005 Rules 5-6 are already
 * enforced by ModuleStatusRule).
 */
final class GetModuleCompletion
{
    public function __construct(
        private readonly ModuleRepository $modules,
        private readonly ModuleProgressService $progress,
        private readonly SurveyRepository $surveys,
    ) {}

    /**
     * @throws ModuleNotFoundException
     * @throws ModuleNotCompleteException
     */
    public function handle(string $learnerId, string $moduleId): ModuleCompletion
    {
        $module = $this->modules->findWithLessons($moduleId);

        if ($module === null) {
            throw new ModuleNotFoundException($moduleId);
        }

        $progress = $this->progress->compute($learnerId, $moduleId);

        if ($progress->moduleStatus !== ModuleStatus::Complete) {
            throw new ModuleNotCompleteException($moduleId);
        }

        $completedLessons = array_values(array_filter(
            array_map(
                fn (LessonContent $lesson): CompletedLesson => new CompletedLesson(
                    lessonId: $lesson->lessonId,
                    position: $lesson->position,
                    title: $lesson->title,
                ),
                $module->lessons,
            ),
            fn (CompletedLesson $lesson): bool => $progress->lessonStatuses[$lesson->lessonId] === LessonStatus::Complete,
        ));

        return new ModuleCompletion(
            moduleId: $module->moduleId,
            title: $module->title,
            deliverableDescription: $module->deliverableDescription,
            completedLessons: $completedLessons,
            surveySubmitted: $this->surveys->hasSubmittedResponse($learnerId, $moduleId),
        );
    }
}
