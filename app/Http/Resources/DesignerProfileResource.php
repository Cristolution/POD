<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\DesignerProfile
 */
class DesignerProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'user_id'    => $this->user_id,
            'bio'        => $this->bio,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            'user'           => $this->whenLoaded('user'),
            'designs'        => $this->whenLoaded('designs'),
            'designs_count'  => $this->whenCounted('designs'),
        ];
    }
}