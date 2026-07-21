<?php

namespace App\Application\Progress;

use App\Application\LearningPath\Contracts\ModuleRepository;
use App\Application\LearningPath\Exceptions\ModuleNotFoundException;
use App\Application\LearningPath\LessonContent;
use App\Application\Progress\Contracts\SubmissionRepository;
use App\Domain\Progress\LessonProgressInput;
use App\Domain\Progress\LessonStatus;
use App\Domain\Progress\LessonStatusRule;
use App\Domain\Progress\ModuleStatusRule;

/**
 * Owns per-learner progress derivation for a module (OA-MVP-008 Progress
 * feature domain: "derives state from Assignment and Survey submission
 * events"). Used both directly by the Get Progress use case and by Learning
 * Path's Get Module Overview use case, which delegates here for per-learner
 * lesson status per OA-MVP-008's Use Case Ownership table.
 */
final class ModuleProgressService
{
    public function __construct(
        private readonly ModuleRepository $modules,
        private readonly SubmissionRepository $submissions,
        private readonly LessonStatusRule $lessonStatusRule,
        private readonly ModuleStatusRule $moduleStatusRule,
    ) {}

    /**
     * @throws ModuleNotFoundException
     */
    public function compute(string $learnerId, string $moduleId): ModuleProgressResult
    {
        $module = $this->modules->findWithLessons($moduleId);

        if ($module === null) {
            throw new ModuleNotFoundException($moduleId);
        }

        $submittedLessonIds = $this->submissions->submittedLessonIds($learnerId, $moduleId);

        $inputs = array_map(
            fn (LessonContent $lesson): LessonProgressInput => new LessonProgressInput(
                $lesson->lessonId,
                in_array($lesson->lessonId, $submittedLessonIds, true),
            ),
            $module->lessons,
        );

        $statuses = $this->lessonStatusRule->apply($inputs);
        $moduleStatus = $this->moduleStatusRule->apply(array_values($statuses));

        $lessonsComplete = count(array_filter(
            $statuses,
            fn (LessonStatus $status): bool => $status === LessonStatus::Complete,
        ));

        $currentLessonId = null;
        foreach ($statuses as $lessonId => $status) {
            if ($status === LessonStatus::Available) {
                $currentLessonId = $lessonId;
                break;
            }
        }

        return new ModuleProgressResult(
            moduleId: $module->moduleId,
            lessonStatuses: $statuses,
            lessonsComplete: $lessonsComplete,
            lessonsTotal: count($module->lessons),
            currentLessonId: $currentLessonId,
            moduleStatus: $moduleStatus,
        );
    }
}
