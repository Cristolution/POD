<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDesignProductMappingRequest extends FormRequest
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
            'design_id'           => ['required', 'string', 'exists:designs,id'],
            'product_template_id' => ['required', 'string', 'exists:product_templates,id'],
            'preferred_printer_id' => ['required', 'string', 'exists:printer_provider_profiles,id'],
            'final_price'         => ['required', 'numeric', 'min:0'],
        ];
    }
}