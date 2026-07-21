<?php

namespace App\Application\LearningPath;

use App\Domain\Progress\ModuleStatus;

final class ModuleOverview
{
    /**
     * @param  ModuleOverviewLesson[]  $lessons  Ordered ascending by lesson position.
     */
    public function __construct(
        public readonly string $moduleId,
        public readonly string $title,
        public readonly ?string $purpose,
        public readonly ?string $deliverableDescription,
        public readonly array $lessons,
        public readonly ModuleStatus $moduleStatus,
    ) {}
}
