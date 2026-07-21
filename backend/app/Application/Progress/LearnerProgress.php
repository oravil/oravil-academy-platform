<?php

namespace App\Application\Progress;

use App\Domain\Progress\ModuleStatus;

final class LearnerProgress
{
    public function __construct(
        public readonly string $moduleId,
        public readonly int $lessonsComplete,
        public readonly int $lessonsTotal,
        public readonly ?string $currentLessonId,
        public readonly ModuleStatus $moduleStatus,
        public readonly bool $surveySubmitted,
    ) {}
}
