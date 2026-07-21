<?php

namespace App\Application\LearningPath;

final class LessonContent
{
    public function __construct(
        public readonly string $lessonId,
        public readonly int $position,
        public readonly string $title,
    ) {}
}
