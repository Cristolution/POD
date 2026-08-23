<?php

declare(strict_types=1);

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'line1' => ['required', 'string', 'max:200'],
            'city' => ['required', 'string', 'max:120'],
            'country' => ['required', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:32'],
        ];
    }
}
