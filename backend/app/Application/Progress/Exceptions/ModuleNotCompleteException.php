<?php

namespace App\Application\Progress\Exceptions;

use RuntimeException;

/**
 * OA-MVP-007: GET /v1/modules/{module_id}/completion returns 403 when the
 * module's computed status (ModuleStatusRule, OA-MVP-005 Domain Rule 5) is
 * not yet Complete for the requesting learner.
 */
final class ModuleNotCompleteException extends RuntimeException
{
    public function __construct(string $moduleId)
    {
        parent::__construct("Module [{$moduleId}] is not yet complete.");
    }
}
