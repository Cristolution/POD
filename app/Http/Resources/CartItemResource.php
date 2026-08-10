<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\CartItem
 */
class CartItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'user_id'                   => $this->user_id,
            'design_product_mapping_id' => $this->design_product_mapping_id,
            'product_variant_id'        => $this->product_variant_id,
            'quantity'                  => $this->quantity,
            'unit_price'                => $this->unitPrice(),
            'line_total'                => $this->lineTotal(),
            'created_at'                => $this->created_at?->toIso8601String(),
            'updated_at'                => $this->updated_at?->toIso8601String(),

            'user'                    => $this->whenLoaded('user'),
            'design_product_mapping'  => $this->whenLoaded('designProductMapping'),
            'product_variant'         => $this->whenLoaded('productVariant'),
        ];
    }
}