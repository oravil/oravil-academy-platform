<?php

namespace App\Http\Resources;

use App\Application\Progress\LearnerProgress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LearnerProgress */
class LearnerProgressResource extends JsonResource
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
            'lessons_complete' => $this->lessonsComplete,
            'lessons_total' => $this->lessonsTotal,
            'current_lesson_id' => $this->currentLessonId,
            'module_status' => $this->moduleStatus->value,
            'survey_submitted' => $this->surveySubmitted,
        ];
    }
}
