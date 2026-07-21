<?php

namespace App\Application\LearningPath\Exceptions;

use RuntimeException;

/**
 * OA-MVP-007: GET /v1/lessons/{lesson_id} returns 403 when the lesson's
 * computed status (LessonStatusRule, OA-MVP-005 Domain Rules 3-4) is Locked
 * for the requesting learner.
 */
final class LessonLockedException extends RuntimeException
{
    public function __construct(string $lessonId)
    {
        parent::__construct("Lesson [{$lessonId}] is locked.");
    }
}
