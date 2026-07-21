<?php

namespace App\Application\Progress;

use App\Domain\Progress\LessonStatus;
use App\Domain\Progress\ModuleStatus;

final class ModuleProgressResult
{
    /**
     * @param  array<string, LessonStatus>  $lessonStatuses  Keyed by lesson id, in module lesson order.
     */
    public function __construct(
        public readonly string $moduleId,
        public readonly array $lessonStatuses,
        public readonly int $lessonsComplete,
        public readonly int $lessonsTotal,
        public readonly ?string $currentLessonId,
        public readonly ModuleStatus $moduleStatus,
    ) {}
}
