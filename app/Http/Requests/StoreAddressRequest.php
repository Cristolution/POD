<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
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
            'user_id' => ['required', 'string', 'exists:users,id'],
            'line1'   => ['required', 'string', 'max:255'],
            'city'    => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:32'],
        ];
    }
}