<?php

namespace App\Application\Progress;

final class ModuleCompletion
{
    /**
     * @param  CompletedLesson[]  $completedLessons  Ordered ascending by lesson position.
     */
    public function __construct(
        public readonly string $moduleId,
        public readonly string $title,
        public readonly ?string $deliverableDescription,
        public readonly array $completedLessons,
        public readonly bool $surveySubmitted,
    ) {}
}
