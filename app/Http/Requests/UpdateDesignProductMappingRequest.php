<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDesignProductMappingRequest extends FormRequest
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
            'preferred_printer_id' => ['sometimes', 'string', 'exists:printer_provider_profiles,id'],
            'final_price'          => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}