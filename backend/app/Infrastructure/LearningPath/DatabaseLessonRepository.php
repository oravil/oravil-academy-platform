<?php

namespace App\Infrastructure\LearningPath;

use App\Application\LearningPath\AssignmentDetail;
use App\Application\LearningPath\Contracts\LessonRepository;
use App\Application\LearningPath\LessonDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseLessonRepository implements LessonRepository
{
    public function find(string $lessonId): ?LessonDetail
    {
        // Guard against malformed ids reaching Postgres as a uuid literal
        // (OA-MVP-008 §Architectural Constraints 14 — no raw database
        // errors in responses). A malformed id is simply not found.
        if (! Str::isUuid($lessonId)) {
            return null;
        }

        $row = DB::table('lessons')
            ->join('assignments', 'assignments.lesson_id', '=', 'lessons.id')
            ->where('lessons.id', $lessonId)
            ->first([
                'lessons.id as lesson_id',
                'lessons.module_id',
                'lessons.position',
                'lessons.title',
                'lessons.estimated_reading_minutes',
                'lessons.content',
                'assignments.id as assignment_id',
                'assignments.deliverable_name',
                'assignments.prompt',
                'assignments.minimum_word_count',
            ]);

        if ($row === null) {
            return null;
        }

        return new LessonDetail(
            lessonId: $row->lesson_id,
            moduleId: $row->module_id,
            position: $row->position,
            title: $row->title,
            estimatedReadingMinutes: $row->estimated_reading_minutes,
            content: $row->content,
            assignment: new AssignmentDetail(
                assignmentId: $row->assignment_id,
                deliverableName: $row->deliverable_name,
                prompt: $row->prompt,
                minimumWordCount: $row->minimum_word_count,
            ),
        );
    }
}
