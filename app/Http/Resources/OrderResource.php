<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,  // UUID PK
            'customer_id' => $this->customer_id,
            'shipping_address_id' => $this->shipping_address_id,
            'shipping_line1' => $this->shipping_line1,
            'shipping_city' => $this->shipping_city,
            'shipping_country' => $this->shipping_country,
            'shipping_phone' => $this->shipping_phone,
            'status' => $this->status,
            'derived_status' => $this->derivedStatus(),
            'total_amount' => (float) $this->total_amount,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            'customer' => $this->whenLoaded('customer'),
            'shipping_address' => $this->whenLoaded('shippingAddress'),
            'items' => $this->whenLoaded('items'),
            'items_count' => $this->whenCounted('items'),
            'payments' => $this->whenLoaded('payments'),
            'shipments' => $this->whenLoaded('shipments'),
            'confirmed_payment' => $this->whenLoaded('payments'),
        ];
    }
}
