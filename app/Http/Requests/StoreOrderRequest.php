<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
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
            'customer_id' => ['required', 'string', 'exists:users,id'],
            'shipping_address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'shipping_line1' => ['required', 'string', 'max:255'],
            'shipping_city' => ['required', 'string', 'max:255'],
            'shipping_country' => ['required', 'string', 'max:255'],
            'shipping_phone' => ['nullable', 'string', 'max:32'],
            'status' => ['sometimes', Rule::in(['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled'])],
            'total_amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
