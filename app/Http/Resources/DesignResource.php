<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Design
 */
class DesignResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'designer_id' => $this->designer_id,
            'category_id' => $this->category_id,
            'title'       => $this->title,
            'status'      => $this->status,
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
            'deleted_at'  => $this->deleted_at?->toIso8601String(),

            'designer'         => $this->whenLoaded('designer'),
            'category'         => $this->whenLoaded('category'),
            'tags'             => $this->whenLoaded('tags'),
            'mappings'         => $this->whenLoaded('mappings'),
            'media'            => $this->whenLoaded('media'),
            'mockups'          => $this->whenLoaded('mockups'),
            'print_files'      => $this->whenLoaded('printFiles'),
            'designs_count'    => $this->whenCounted('mappings'),
        ];
    }
}