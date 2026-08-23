<?php

declare(strict_types=1);

namespace App\Http\Requests\Shipment;

use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'string', 'exists:orders,id'],
            'printer_provider_id' => ['required', 'string', 'exists:printer_provider_profiles,id'],
            'delivery_company_id' => ['required', 'integer', 'exists:delivery_companies,id'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
        ];
    }
}
