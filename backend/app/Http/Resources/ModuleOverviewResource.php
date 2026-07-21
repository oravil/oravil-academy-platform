<?php

namespace App\Http\Resources;

use App\Application\LearningPath\ModuleOverview;
use App\Application\LearningPath\ModuleOverviewLesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ModuleOverview */
class ModuleOverviewResource extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'module_id' => $this->moduleId,
            'title' => $this->title,
            'purpose' => $this->purpose,
            'deliverable_description' => $this->deliverableDescription,
            'lessons' => array_map(fn (ModuleOverviewLesson $lesson): array => [
                'lesson_id' => $lesson->lessonId,
                'position' => $lesson->position,
                'title' => $lesson->title,
                'status' => $lesson->status->value,
            ], $this->lessons),
            'module_status' => $this->moduleStatus->value,
        ];
    }
}
