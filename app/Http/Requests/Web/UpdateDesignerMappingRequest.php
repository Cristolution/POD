<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for designers updating an existing design ↔ product-template
 * mapping via the web UI at /designer/mappings/{mapping}/edit.
 *
 * design_id and product_template_id are intentionally immutable here — the
 * designer deletes-and-recreates if they need to change the tuple. Mirrors
 * the API UpdateDesignProductMappingRequest contract enforced by
 * UpdateDesignProductMappingAction.
 */
class UpdateDesignerMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDesigner() ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'preferred_printer_id' => ['sometimes', 'required', 'string', 'exists:printer_provider_profiles,id'],
            'final_price' => ['sometimes', 'required', 'numeric', 'min:0'],
        ];
    }
}
