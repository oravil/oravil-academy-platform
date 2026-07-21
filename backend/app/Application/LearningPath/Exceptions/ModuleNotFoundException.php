<?php

namespace App\Application\LearningPath\Exceptions;

use RuntimeException;

final class ModuleNotFoundException extends RuntimeException
{
    public function __construct(string $moduleId)
    {
        parent::__construct("Module [{$moduleId}] not found.");
    }
}
