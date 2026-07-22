<?php

namespace App\Application\Assignment\Exceptions;

use RuntimeException;

final class AssignmentNotFoundException extends RuntimeException
{
    public function __construct(string $assignmentId)
    {
        parent::__construct("Assignment [{$assignmentId}] not found.");
    }
}
