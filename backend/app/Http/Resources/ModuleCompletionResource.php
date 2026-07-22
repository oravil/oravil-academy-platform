<?php

namespace App\Http\Resources;

use App\Application\Progress\ModuleCompletion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ModuleCompletion */
class ModuleCompletionResource extends JsonResource
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
            'deliverable_description' => $this->deliverableDescription,
            'completed_lessons' => array_map(
                fn ($lesson): array => [
                    'lesson_id' => $lesson->lessonId,
                    'position' => $lesson->position,
                    'title' => $lesson->title,
                ],
                $this->completedLessons,
            ),
            'survey_submitted' => $this->surveySubmitted,
        ];
    }
}
