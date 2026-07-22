<?php

namespace App\Application\Assignment\Exceptions;

use RuntimeException;

/**
 * OA-MVP-005 Domain Rule 9 / OA-MVP-007: a learner has at most one
 * Assignment Submission per Assignment. Thrown both when the lesson's
 * computed status is already Complete (pre-check) and when the database's
 * unique (learner_id, assignment_id) constraint rejects a racing insert
 * (backstop) — both cases surface the same 403.
 */
final class AssignmentAlreadySubmittedException extends RuntimeException
{
    public function __construct(string $assignmentId)
    {
        parent::__construct("Assignment [{$assignmentId}] has already been submitted.");
    }
}
