<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
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
            'status'              => ['sometimes', Rule::in(['pending', 'confirmed', 'rejected'])],
            'confirmed_by_admin_id' => ['sometimes', 'nullable', 'string', 'exists:users,id'],
            'confirmed_at'        => ['sometimes', 'nullable', 'date'],
        ];
    }
}