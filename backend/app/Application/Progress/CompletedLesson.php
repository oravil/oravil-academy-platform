<?php

namespace App\Application\Progress;

final class CompletedLesson
{
    public function __construct(
        public readonly string $lessonId,
        public readonly int $position,
        public readonly string $title,
    ) {}
}
