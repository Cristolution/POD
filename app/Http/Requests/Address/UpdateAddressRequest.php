<?php

declare(strict_types=1);

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'line1' => ['sometimes', 'string', 'max:200'],
            'city' => ['sometimes', 'string', 'max:120'],
            'country' => ['sometimes', 'string', 'size:2'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
        ];
    }
}
