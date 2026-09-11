<?php

namespace App\Http\Resources;

use App\Models\ProductTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductTemplate
 */
class ProductTemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'printer_provider_id' => $this->printer_provider_id,
            'type' => $this->type,
            'base_cost' => (float) $this->base_cost,
            'specs' => $this->specs,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),

            'printer_provider' => $this->whenLoaded('printerProvider'),
            'variants' => $this->whenLoaded('variants'),
            'variants_count' => $this->whenCounted('variants'),
            'design_product_mappings' => $this->whenLoaded('designProductMappings'),
            'design_product_mappings_count' => $this->whenCounted('designProductMappings'),
        ];
    }
}
