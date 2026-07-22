<?php

namespace App\Application\Assignment\Exceptions;

use RuntimeException;

/**
 * OA-MVP-007: POST /v1/assignments/{assignment_id}/submissions returns 403
 * when the assignment's lesson (LessonStatusRule, OA-MVP-005 Domain Rules
 * 3-4) is Locked for the requesting learner.
 */
final class AssignmentNotUnlockedException extends RuntimeException
{
    public function __construct(string $assignmentId)
    {
        parent::__construct("Assignment [{$assignmentId}] is not yet unlocked.");
    }
}
