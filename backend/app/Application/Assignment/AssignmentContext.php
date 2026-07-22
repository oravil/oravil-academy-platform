<?php

namespace App\Application\Assignment;

final class AssignmentContext
{
    public function __construct(
        public readonly string $assignmentId,
        public readonly string $lessonId,
        public readonly string $moduleId,
        public readonly ?int $minimumWordCount,
    ) {}
}
