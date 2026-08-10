<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\PrinterProviderProfile
 */
class PrinterProviderProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'user_id'      => $this->user_id,
            'company_name' => $this->company_name,
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),

            'user'                  => $this->whenLoaded('user'),
            'product_templates'     => $this->whenLoaded('productTemplates'),
            'product_templates_count' => $this->whenCounted('productTemplates'),
            'order_items_count'     => $this->whenCounted('orderItems'),
            'shipments_count'       => $this->whenCounted('shipments'),
        ];
    }
}