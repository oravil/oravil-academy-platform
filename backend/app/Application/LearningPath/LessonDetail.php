<?php

namespace App\Application\LearningPath;

final class LessonDetail
{
    public function __construct(
        public readonly string $lessonId,
        public readonly string $moduleId,
        public readonly int $position,
        public readonly string $title,
        public readonly ?int $estimatedReadingMinutes,
        public readonly string $content,
        public readonly AssignmentDetail $assignment,
    ) {}
}
