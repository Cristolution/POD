<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderItemRequest extends FormRequest
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
            'status'     => ['sometimes', Rule::in(['pending', 'received', 'printing', 'printed', 'handed_off', 'cancelled'])],
            'quantity'   => ['sometimes', 'integer', 'min:1', 'max:100'],
            'unit_price' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}