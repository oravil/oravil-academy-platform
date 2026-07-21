<?php

namespace App\Application\LearningPath;

use App\Domain\Progress\LessonStatus;

final class ModuleOverviewLesson
{
    public function __construct(
        public readonly string $lessonId,
        public readonly int $position,
        public readonly string $title,
        public readonly LessonStatus $status,
    ) {}
}
