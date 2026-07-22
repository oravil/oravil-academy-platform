<?php

namespace App\Http\Resources;

use App\Application\Assignment\SubmissionResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SubmissionResult */
class AssignmentSubmissionResource extends JsonResource
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
            'submission_id' => $this->submissionId,
            'assignment_id' => $this->assignmentId,
            'status' => $this->status,
            'submitted_at' => $this->submittedAt,
        ];
    }
}
