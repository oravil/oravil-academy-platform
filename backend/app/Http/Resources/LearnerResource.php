<?php

namespace App\Http\Resources;

use App\Models\Learner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Learner */
class LearnerResource extends JsonResource
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
            'learner_id' => $this->id,
            'email' => $this->email,
            'display_name' => $this->display_name,
        ];
    }
}
