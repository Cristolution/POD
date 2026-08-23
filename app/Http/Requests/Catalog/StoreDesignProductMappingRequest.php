<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class StoreDesignProductMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDesigner() ?? false;
    }

    /**
     * Note: `preferred_printer_id` references `printer_provider_profiles.id`
     * (not `users.id`) per the existing migration. `is_active` is omitted
     * because the physical column does not yet exist. Despite the public
     * spec marking it nullable, the underlying migration declares it
     * NOT NULL via `->constrained()`, so we require it.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'design_id' => ['required', 'string', 'exists:designs,id'],
            'product_template_id' => ['required', 'string', 'exists:product_templates,id'],
            'preferred_printer_id' => ['required', 'string', 'exists:printer_provider_profiles,id'],
            'final_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
