<?php

namespace App\Domain\Progress;

/**
 * OA-MVP-005 Domain Rules 3 and 4: lessons within a module are ordered and
 * completed in sequence; a lesson is complete when its own Assignment has a
 * submitted submission.
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
