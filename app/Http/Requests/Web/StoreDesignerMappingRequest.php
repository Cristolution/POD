<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for designers creating a design ↔ product-template mapping via
 * the web UI at /designer/mappings/create.
 *
 * Differs from the API StoreDesignProductMappingRequest in that web designers
 * pick from a dropdown of their own designs. Ownership is re-checked by the
 * StoreDesignProductMappingAction against $profile->designs()->find() —
 * defense in depth.
 */
class StoreDesignerMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDesigner() ?? false;
    }

    /** @return array<string,mixed> */
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
