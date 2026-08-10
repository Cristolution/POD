<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\DesignProductMapping
 */
class DesignProductMappingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'design_id'            => $this->design_id,
            'product_template_id'  => $this->product_template_id,
            'preferred_printer_id' => $this->preferred_printer_id,
            'final_price'          => (float) $this->final_price,
            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),

            'design'            => $this->whenLoaded('design'),
            'product_template'  => $this->whenLoaded('productTemplate'),
            'preferred_printer' => $this->whenLoaded('preferredPrinter'),
        ];
    }
}