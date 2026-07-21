<?php

namespace App\Http\Resources;

use App\Application\LearningPath\LessonDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LessonDetail */
class LessonResource extends JsonResource
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
            'lesson_id' => $this->lessonId,
            'module_id' => $this->moduleId,
            'position' => $this->position,
            'title' => $this->title,
            'estimated_reading_minutes' => $this->estimatedReadingMinutes,
            'content' => $this->content,
            'assignment' => [
                'assignment_id' => $this->assignment->assignmentId,
                'deliverable_name' => $this->assignment->deliverableName,
                'prompt' => $this->assignment->prompt,
                'minimum_word_count' => $this->assignment->minimumWordCount,
            ],
        ];
    }
}
