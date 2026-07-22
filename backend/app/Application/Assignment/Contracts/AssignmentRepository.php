<?php

namespace App\Application\Assignment\Contracts;

use App\Application\Assignment\AssignmentContext;
use App\Application\Assignment\SubmissionResult;

interface AssignmentRepository
{
    /**
     * Returns the assignment's lesson/module context and minimum word count,
     * or null if no assignment exists for the given id (including malformed ids).
     */
    public function findContext(string $assignmentId): ?AssignmentContext;

    /**
     * Persists a new submission. Throws Illuminate\Database\UniqueConstraintViolationException
     * if a submission already exists for this learner and assignment (learner_id, assignment_id
     * is unique at the database level) — callers must handle the race window between the
     * caller's own duplicate pre-check and this insert.
     */
    public function createSubmission(string $learnerId, string $assignmentId, string $content): SubmissionResult;
}
