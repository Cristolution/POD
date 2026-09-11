<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request for designers creating a design via the web UI at /designer/designs.
 *
 * Differs from the API StoreDesignRequest in two ways:
 *  - The designer_id is forced from the authenticated user inside the controller
 *    (this request doesn't accept it; web designers can only create their own).
 *  - Adds file uploads for the mockup + print_file collections, plus optional
 *    per-product mockups (`product_mockups[template_id]`) for designs the
 *    designer wants to preview on specific product types (e.g. a t-shirt mockup).
 */
class StoreDesignerDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDesigner() ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'mockup' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:10240'],
            'print_file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:10240'],
            // Optional per-product mockups — keyed by product_template_id so
            // designers can upload "this design on a t-shirt" without
            // needing a "this design on a mug" if they don't have one.
            'product_mockups' => ['nullable', 'array'],
            'product_mockups.*' => ['file', 'mimes:jpg,jpeg,png,webp,svg', 'max:10240'],
        ];
    }
}
