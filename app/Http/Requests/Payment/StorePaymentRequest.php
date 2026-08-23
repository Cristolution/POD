<?php

declare(strict_types=1);

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
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
            'method' => ['required', 'string', Rule::in([
                'cash_on_delivery',
                'bank_transfer',
                'card',
            ])],
        ];
    }
}
