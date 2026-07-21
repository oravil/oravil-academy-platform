<?php

namespace App\Infrastructure\Progress;

use App\Application\Progress\Contracts\SubmissionRepository;
use Illuminate\Support\Facades\DB;

class DatabaseSubmissionRepository implements SubmissionRepository
{
    public function submittedLessonIds(string $learnerId, string $moduleId): array
    {
        return DB::table('assignment_submissions')
            ->join('assignments', 'assignments.id', '=', 'assignment_submissions.assignment_id')
            ->join('lessons', 'lessons.id', '=', 'assignments.lesson_id')
            ->where('lessons.module_id', $moduleId)
            ->where('assignment_submissions.learner_id', $learnerId)
            ->where('assignment_submissions.status', 'submitted')
            ->pluck('lessons.id')
            ->all();
    }
}
