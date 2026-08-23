<?php

namespace App\Http\Resources;

use App\Models\DeliveryCompany;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DeliveryCompany
 */
class DeliveryCompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'coverage_zones' => $this->coverage_zones,
            'tracking_url_pattern' => $this->tracking_url_pattern,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            'shipments' => $this->whenLoaded('shipments'),
            'shipments_count' => $this->whenCounted('shipments'),
        ];
    }
}
