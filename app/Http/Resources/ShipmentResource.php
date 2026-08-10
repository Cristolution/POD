<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Shipment
 */
class ShipmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'order_id'            => $this->order_id,
            'printer_provider_id' => $this->printer_provider_id,
            'delivery_company_id' => $this->delivery_company_id,
            'tracking_number'     => $this->tracking_number,
            'tracking_url'        => $this->trackingUrl(),
            'status'              => $this->status,
            'shipped_at'          => $this->shipped_at?->toIso8601String(),
            'delivered_at'        => $this->delivered_at?->toIso8601String(),
            'created_at'          => $this->created_at?->toIso8601String(),
            'updated_at'          => $this->updated_at?->toIso8601String(),

            'order'                  => $this->whenLoaded('order'),
            'printer_provider'       => $this->whenLoaded('printerProvider'),
            'delivery_company'       => $this->whenLoaded('deliveryCompany'),
            'covered_items'          => $this->whenLoaded('coveredItems'),
        ];
    }
}