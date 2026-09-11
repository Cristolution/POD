<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'required', 'string', 'max:60'],
            'base_cost' => ['sometimes', 'required', 'numeric', 'min:0'],
            'specs' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
