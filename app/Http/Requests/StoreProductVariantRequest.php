<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductVariantRequest extends FormRequest
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
            'product_template_id' => ['required', 'string', 'exists:product_templates,id'],
            'attributes' => ['required', 'array'],
            'price_delta' => ['required', 'numeric', 'min:0'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('product_variants', 'sku')],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
