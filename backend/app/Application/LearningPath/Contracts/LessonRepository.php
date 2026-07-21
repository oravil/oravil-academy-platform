<?php

namespace App\Application\LearningPath\Contracts;

use App\Application\LearningPath\LessonDetail;

interface LessonRepository
{
    /**
     * Returns the lesson's full content and assignment, or null if no
     * lesson exists for the given id (including malformed ids).
     */
    public function find(string $lessonId): ?LessonDetail;
}
