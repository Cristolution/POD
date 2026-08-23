<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isAdmin() || $user->isDesigner());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'designer_id' => ['sometimes', 'nullable', 'string', 'exists:designer_profiles,id'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'title' => ['sometimes', 'required', 'string', 'max:160'],
            'status' => ['sometimes', 'nullable', Rule::in(['draft', 'published', 'archived'])],
            'tag_ids' => ['sometimes', 'nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ];
    }
}
