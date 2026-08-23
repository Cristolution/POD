<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,            // UUID PK (Pattern A)
            'name' => $this->name,
            'role' => $this->role,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Sensitive fields — exposed only when explicitly authorized via a separate
            // endpoint or when `$this->resource` is the authenticated user.
            $this->mergeWhen($request->user()?->id === $this->id || $request->user()?->isAdmin(), [
                'email' => $this->email,
                'phone' => $this->phone,
            ]),

            // Conditionally include loaded relations
            'designer_profile' => $this->whenLoaded('designerProfile'),
            'printer_provider_profile' => $this->whenLoaded('printerProviderProfile'),
        ];
    }
}
