<?php

namespace App\Infrastructure\Assignment;

use App\Application\Assignment\AssignmentContext;
use App\Application\Assignment\Contracts\AssignmentRepository;
use App\Application\Assignment\SubmissionResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseAssignmentRepository implements AssignmentRepository
{
    public function findContext(string $assignmentId): ?AssignmentContext
    {
        // Guard against malformed ids reaching Postgres as a uuid literal
        // (OA-MVP-008 §Architectural Constraints 14 — no raw database
        // errors in responses). A malformed id is simply not found.
        if (! Str::isUuid($assignmentId)) {
            return null;
        }

        $row = DB::table('assignments')
            ->join('lessons', 'lessons.id', '=', 'assignments.lesson_id')
            ->where('assignments.id', $assignmentId)
            ->first([
                'assignments.id as assignment_id',
                'assignments.minimum_word_count',
                'lessons.id as lesson_id',
                'lessons.module_id',
            ]);

        if ($row === null) {
            return null;
        }

        return new AssignmentContext(
            assignmentId: $row->assignment_id,
            lessonId: $row->lesson_id,
            moduleId: $row->module_id,
            minimumWordCount: $row->minimum_word_count,
        );
    }

    public function createSubmission(string $learnerId, string $assignmentId, string $content): SubmissionResult
    {
        $submissionId = (string) Str::uuid();
        $submittedAt = now();

        DB::table('assignment_submissions')->insert([
            'id' => $submissionId,
            'learner_id' => $learnerId,
            'assignment_id' => $assignmentId,
            'content' => $content,
            'status' => 'submitted',
            'submitted_at' => $submittedAt,
        ]);

        return new SubmissionResult(
            submissionId: $submissionId,
            assignmentId: $assignmentId,
            status: 'submitted',
            submittedAt: $submittedAt->toIso8601String(),
        );
    }
}
