<?php

namespace App\Application\LearningPath;

use App\Application\LearningPath\Contracts\ModuleRepository;
use App\Application\LearningPath\Exceptions\ModuleNotFoundException;
use App\Application\Progress\ModuleProgressService;

/**
 * Use case for GET /v1/modules/{module_id}/overview (OA-MVP-007).
 * Owning feature domain: Learning Path, delegating to Progress for
 * per-learner lesson status (OA-MVP-008 Use Case Ownership table).
 */
final class GetModuleOverview
{
    public function __construct(
        private readonly ModuleRepository $modules,
        private readonly ModuleProgressService $progress,
    ) {}

    /**
     * @throws ModuleNotFoundException
     */
    public function handle(string $learnerId, string $moduleId): ModuleOverview
    {
        $module = $this->modules->findWithLessons($moduleId);

        if ($module === null) {
            throw new ModuleNotFoundException($moduleId);
        }

        $progress = $this->progress->compute($learnerId, $moduleId);

        $lessons = array_map(
            fn (LessonContent $lesson): ModuleOverviewLesson => new ModuleOverviewLesson(
                lessonId: $lesson->lessonId,
                position: $lesson->position,
                title: $lesson->title,
                status: $progress->lessonStatuses[$lesson->lessonId],
            ),
            $module->lessons,
        );

        return new ModuleOverview(
            moduleId: $module->moduleId,
            title: $module->title,
            purpose: $module->purpose,
            deliverableDescription: $module->deliverableDescription,
            lessons: $lessons,
            moduleStatus: $progress->moduleStatus,
        );
    }
}
