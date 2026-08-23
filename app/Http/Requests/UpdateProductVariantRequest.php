<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends FormRequest
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
        $variantId = $this->route('product_variant')?->id;

        return [
            'attributes' => ['sometimes', 'array'],
            'price_delta' => ['sometimes', 'numeric', 'min:0'],
            'sku' => ['sometimes', 'nullable', 'string', 'max:100', Rule::unique('product_variants', 'sku')->ignore($variantId)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
