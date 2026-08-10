<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
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
            'order_id'  => ['required', 'string', 'exists:orders,id'],
            'method'    => ['required', Rule::in(['cash_on_delivery', 'bank_transfer', 'card'])],
            'status'    => ['sometimes', Rule::in(['pending', 'confirmed', 'rejected'])],
        ];
    }
}