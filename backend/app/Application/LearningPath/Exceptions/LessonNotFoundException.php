<?php

namespace App\Application\LearningPath\Exceptions;

use RuntimeException;

final class LessonNotFoundException extends RuntimeException
{
    public function __construct(string $lessonId)
    {
        parent::__construct("Lesson [{$lessonId}] not found.");
    }
}
