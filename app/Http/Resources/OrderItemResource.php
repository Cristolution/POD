<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\OrderItem
 */
class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'order_id'                  => $this->order_id,
            'design_product_mapping_id' => $this->design_product_mapping_id,
            'product_variant_id'        => $this->product_variant_id,
            'printer_provider_id'       => $this->printer_provider_id,
            'status'                    => $this->status,
            'quantity'                  => $this->quantity,
            'unit_price'                => (float) $this->unit_price,
            'line_total'                => $this->lineTotal(),
            'is_cancellable'            => $this->isCancellable(),
            'created_at'                => $this->created_at?->toIso8601String(),
            'updated_at'                => $this->updated_at?->toIso8601String(),

            'order'                  => $this->whenLoaded('order'),
            'design_product_mapping' => $this->whenLoaded('designProductMapping'),
            'product_variant'        => $this->whenLoaded('productVariant'),
            'printer_provider'       => $this->whenLoaded('printerProvider'),
        ];
    }
}