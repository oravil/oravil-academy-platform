<?php

namespace App\Http\Resources;

use App\Models\Learner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Learner */
class LearnerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'display_name' => $this->display_name,
            'enrolled_at' => $this->enrolled_at->toIso8601String(),
        ];
    }
}
