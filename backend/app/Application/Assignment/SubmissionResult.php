<?php

namespace App\Application\Assignment;

final class SubmissionResult
{
    public function __construct(
        public readonly string $submissionId,
        public readonly string $assignmentId,
        public readonly string $status,
        public readonly string $submittedAt,
    ) {}
}
