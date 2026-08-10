<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'order_id'               => $this->order_id,
            'method'                 => $this->method,
            'status'                 => $this->status,
            'confirmed_by_admin_id'  => $this->confirmed_by_admin_id,
            'confirmed_at'           => $this->confirmed_at?->toIso8601String(),
            'created_at'             => $this->created_at?->toIso8601String(),
            'updated_at'             => $this->updated_at?->toIso8601String(),
            'deleted_at'             => $this->deleted_at?->toIso8601String(),

            'is_cash_on_delivery' => $this->isCashOnDelivery(),
            'is_bank_transfer'    => $this->isBankTransfer(),
            'is_card'             => $this->isCard(),

            'order'                => $this->whenLoaded('order'),
            'confirmed_by_admin'   => $this->whenLoaded('confirmedByAdmin'),
            'media'                => $this->whenLoaded('media'),
            'proof_of_payment'     => $this->whenLoaded('proofOfPayment'),
        ];
    }
}