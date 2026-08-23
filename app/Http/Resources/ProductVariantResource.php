<?php

namespace App\Http\Resources;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductVariant
 */
class ProductVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_template_id' => $this->product_template_id,
            'attributes' => $this->attributes,
            'price_delta' => (float) $this->price_delta,
            'sku' => $this->sku,
            'is_active' => $this->is_active,
            'label' => $this->label(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            'product_template' => $this->whenLoaded('productTemplate'),
        ];
    }
}
