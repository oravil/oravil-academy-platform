<?php

namespace App\Application\Survey\Exceptions;

use RuntimeException;

/**
 * OA-MVP-007: both survey endpoints return 404 when no survey exists for
 * the given module id (GET) or survey id (POST, including malformed ids).
 */
final class SurveyNotFoundException extends RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Survey [{$id}] not found.");
    }
}
