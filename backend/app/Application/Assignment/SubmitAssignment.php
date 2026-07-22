<?php

namespace App\Application\Assignment;

use App\Application\Assignment\Contracts\AssignmentRepository;
use App\Application\Assignment\Exceptions\AssignmentAlreadySubmittedException;
use App\Application\Assignment\Exceptions\AssignmentNotFoundException;
use App\Application\Assignment\Exceptions\AssignmentNotUnlockedException;
use App\Application\Progress\ModuleProgressService;
use App\Domain\Progress\LessonStatus;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

/**
 * Use case for POST /v1/assignments/{assignment_id}/submissions (OA-MVP-007).
 * Access gating reuses the lesson status already computed by
 * ModuleProgressService (Task 7 Phase A's LessonStatusRule, OA-MVP-005
 * Domain Rules 3-4) — no new domain logic. The three statuses map exactly
 * onto the three write-path outcomes: Available accepts the submission,
 * Locked is "not yet unlocked" (403), and Complete is "already submitted"
 * (403) since Complete is defined by a submission already existing.
 */
final class SubmitAssignment
{
    public function __construct(
        private readonly AssignmentRepository $assignments,
        private readonly ModuleProgressService $progress,
    ) {}

    /**
     * @throws AssignmentNotFoundException
     * @throws ValidationException
     * @throws AssignmentNotUnlockedException
     * @throws AssignmentAlreadySubmittedException
     */
    public function handle(string $learnerId, string $assignmentId, string $content): SubmissionResult
    {
        $assignment = $this->assignments->findContext($assignmentId);

        if ($assignment === null) {
            throw new AssignmentNotFoundException($assignmentId);
        }

        $this->validateContent($content, $assignment->minimumWordCount);

        $moduleProgress = $this->progress->compute($learnerId, $assignment->moduleId);
        $status = $moduleProgress->lessonStatuses[$assignment->lessonId];

        if ($status === LessonStatus::Locked) {
            throw new AssignmentNotUnlockedException($assignmentId);
        }

        if ($status === LessonStatus::Complete) {
            throw new AssignmentAlreadySubmittedException($assignmentId);
        }

        try {
            return $this->assignments->createSubmission($learnerId, $assignmentId, $content);
        } catch (UniqueConstraintViolationException) {
            // Race backstop: two concurrent requests both passed the status
            // check above before either committed. The database's unique
            // (learner_id, assignment_id) constraint is the real guarantee;
            // this translates its violation to the same 403 as the pre-check.
            throw new AssignmentAlreadySubmittedException($assignmentId);
        }
    }

    /**
     * Emptiness (including whitespace-only) is already rejected by
     * SubmitAssignmentRequest's `required` rule before this use case runs —
     * only the minimum word count remains, since it depends on the specific
     * assignment and isn't known at request-validation time.
     *
     * @throws ValidationException
     */
    private function validateContent(string $content, ?int $minimumWordCount): void
    {
        if ($minimumWordCount === null) {
            return;
        }

        $wordCount = count(preg_split('/\s+/', trim($content)));

        if ($wordCount < $minimumWordCount) {
            throw ValidationException::withMessages([
                'content' => ['Your response is shorter than the minimum required. Please complete your answer.'],
            ]);
        }
    }
}
