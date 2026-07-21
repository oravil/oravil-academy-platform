<?php

namespace App\Application\LearningPath;

use App\Application\LearningPath\Contracts\LessonRepository;
use App\Application\LearningPath\Exceptions\LessonLockedException;
use App\Application\LearningPath\Exceptions\LessonNotFoundException;
use App\Application\Progress\ModuleProgressService;
use App\Domain\Progress\LessonStatus;

/**
 * Use case for GET /v1/lessons/{lesson_id} (OA-MVP-007). Access gating
 * reuses the lesson status already computed by ModuleProgressService
 * (Task 7 Phase A's LessonStatusRule, OA-MVP-005 Domain Rules 3-4) — no
 * new domain logic. The module is resolved via the lesson itself, so
 * ModuleProgressService's own ModuleNotFoundException cannot occur here:
 * a lesson row only exists with a valid module_id foreign key.
 */
final class GetLessonContent
{
    public function __construct(
        private readonly LessonRepository $lessons,
        private readonly ModuleProgressService $progress,
    ) {}

    /**
     * @throws LessonNotFoundException
     * @throws LessonLockedException
     */
    public function handle(string $learnerId, string $lessonId): LessonDetail
    {
        $lesson = $this->lessons->find($lessonId);

        if ($lesson === null) {
            throw new LessonNotFoundException($lessonId);
        }

        $moduleProgress = $this->progress->compute($learnerId, $lesson->moduleId);
        $status = $moduleProgress->lessonStatuses[$lesson->lessonId];

        if ($status === LessonStatus::Locked) {
            throw new LessonLockedException($lessonId);
        }

        return $lesson;
    }
}
