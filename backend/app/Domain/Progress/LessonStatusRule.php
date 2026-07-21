<?php

namespace App\Domain\Progress;

/**
 * OA-MVP-005 Domain Rules 3 and 4: lessons within a module are ordered and
 * completed in sequence; a lesson is complete when its own Assignment has a
 * submitted submission. A lesson's own completion takes priority over the
 * sequence gate — ratified 2026-07-21 (Task 7 Phase A approval): this state
 * is unreachable through the API write-path, so it only defines defensive
 * read behavior, and surfacing a submission beats hiding one if data ever
 * diverges. Do not reopen without a new ADR.
 */
final class LessonStatusRule
{
    /**
     * @param  LessonProgressInput[]  $lessonsInOrder  Ascending by module lesson position.
     * @return array<string, LessonStatus> Keyed by lesson id, in input order.
     */
    public function apply(array $lessonsInOrder): array
    {
        $statuses = [];
        // No prior lesson satisfies the sequence gate vacuously, so the first lesson starts available.
        $previousLessonComplete = true;

        foreach ($lessonsInOrder as $lesson) {
            $status = match (true) {
                $lesson->hasSubmittedAssignment => LessonStatus::Complete,
                $previousLessonComplete => LessonStatus::Available,
                default => LessonStatus::Locked,
            };

            $statuses[$lesson->lessonId] = $status;
            $previousLessonComplete = $status === LessonStatus::Complete;
        }

        return $statuses;
    }
}
