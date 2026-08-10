<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'order_id'            => ['required', 'string', 'exists:orders,id'],
            'printer_provider_id' => ['required', 'string', 'exists:printer_provider_profiles,id'],
            'delivery_company_id' => ['required', 'integer', 'exists:delivery_companies,id'],
            'tracking_number'     => ['nullable', 'string', 'max:100'],
            'status'              => ['sometimes', Rule::in(['pending', 'shipped', 'delivered', 'returned'])],
            'shipped_at'          => ['nullable', 'date'],
            'delivered_at'        => ['nullable', 'date'],
        ];
    }
}