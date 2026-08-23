<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'sku' => ['sometimes', 'required', 'string', 'max:80', 'unique:product_variants,sku'],
            'attributes' => ['sometimes', 'nullable', 'array'],
            'price_delta' => ['sometimes', 'nullable', 'numeric'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
