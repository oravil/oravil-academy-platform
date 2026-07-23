<?php

namespace App\Http\Resources;

use App\Application\Survey\SurveyResponseResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SurveyResponseResult */
class SurveyResponseResource extends JsonResource
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
            'survey_id' => $this->surveyId,
            'submitted_at' => $this->submittedAt,
        ];
    }
}
