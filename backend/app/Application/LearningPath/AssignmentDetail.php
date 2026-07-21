<?php

namespace App\Application\LearningPath;

final class AssignmentDetail
{
    public function __construct(
        public readonly string $assignmentId,
        public readonly string $deliverableName,
        public readonly string $prompt,
        public readonly ?int $minimumWordCount,
    ) {}
}
