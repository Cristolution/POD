<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductTemplateRequest extends FormRequest
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
            'printer_provider_id' => ['required', 'string', 'exists:printer_provider_profiles,id'],
            'type' => ['required', 'string', 'max:100'],
            'base_cost' => ['required', 'numeric', 'min:0'],
            'specs' => ['nullable', 'array'],
        ];
    }
}
