<?php

namespace App\Application\Progress\Contracts;

interface SubmissionRepository
{
    /**
     * @return string[] Ids of lessons in the module whose assignment has a
     *                  submitted Assignment Submission for this learner
     *                  (OA-MVP-005 Domain Rule 4).
     */
    public function submittedLessonIds(string $learnerId, string $moduleId): array;
}
