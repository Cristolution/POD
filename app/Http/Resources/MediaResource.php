<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Media
 */
class MediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'model_type'      => $this->model_type,
            'model_id'        => $this->model_id,
            'collection_name' => $this->collection_name,
            'file_path'       => $this->file_path,
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),

            'is_mockup'         => $this->isMockup(),
            'is_print_file'     => $this->isPrintFile(),
            'is_payment_proof'  => $this->isPaymentProof(),
            'is_attachment'     => $this->isAttachment(),

            'model' => $this->whenLoaded('model'),
        ];
    }
}