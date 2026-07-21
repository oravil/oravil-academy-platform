<?php

namespace App\Application\LearningPath\Contracts;

use App\Application\LearningPath\ModuleContent;

interface ModuleRepository
{
    /**
     * Returns the module's content and its lessons ordered by position, or
     * null if no module exists for the given id (including malformed ids).
     */
    public function findWithLessons(string $moduleId): ?ModuleContent;
}
