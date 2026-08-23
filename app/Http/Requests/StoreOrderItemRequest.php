<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderItemRequest extends FormRequest
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
            'order_id' => ['required', 'string', 'exists:orders,id'],
            'design_product_mapping_id' => ['required', 'string', 'exists:design_product_mappings,id'],
            'product_variant_id' => ['nullable', 'string', 'exists:product_variants,id'],
            'printer_provider_id' => ['required', 'string', 'exists:printer_provider_profiles,id'],
            'status' => ['sometimes', Rule::in(['pending', 'received', 'printing', 'printed', 'handed_off', 'cancelled'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
