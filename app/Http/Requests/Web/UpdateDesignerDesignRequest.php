<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request for designers updating their own design via the web UI.
 * All fields are sometimes — the designer may submit any subset.
 * Files are nullable; if present, the same mimes/size rules apply.
 */
class UpdateDesignerDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDesigner() ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in(['draft', 'published', 'archived'])],
            'tag_ids' => ['sometimes', 'nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'mockup' => ['sometimes', 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:10240'],
            'print_file' => ['sometimes', 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:10240'],
        ];
    }
}
