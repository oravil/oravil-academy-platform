<?php

namespace App\Application\LearningPath;

final class ModuleContent
{
    /**
     * @param  LessonContent[]  $lessons  Ordered ascending by lesson position.
     */
    public function __construct(
        public readonly string $moduleId,
        public readonly string $title,
        public readonly ?string $purpose,
        public readonly ?string $deliverableDescription,
        public readonly array $lessons,
    ) {}
}
