<?php

namespace App\Domain\Progress;

/**
 * One lesson's raw completion fact for a given learner, as observed from
 * Assignment Submission records (OA-MVP-005 Domain Rule 4). Carries no
 * behaviour of its own — the status derivation lives in LessonStatusRule.
 */
final class LessonProgressInput
{
    public function __construct(
        public readonly string $lessonId,
        public readonly bool $hasSubmittedAssignment,
    ) {}
}
