<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDesignProductMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Only columns present on `design_product_mappings`. `is_active` is part
     * of the planned surface but no physical column exists yet.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'preferred_printer_id' => ['sometimes', 'nullable', 'string', 'exists:printer_provider_profiles,id'],
            'final_price' => ['sometimes', 'required', 'numeric', 'min:0'],
        ];
    }
}
