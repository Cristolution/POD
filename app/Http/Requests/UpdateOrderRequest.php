<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
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
            'status' => ['sometimes', Rule::in(['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled'])],
            'total_amount' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
