<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductTemplateRequest extends FormRequest
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
        return [
            'type' => ['sometimes', 'string', 'max:100'],
            'base_cost' => ['sometimes', 'numeric', 'min:0'],
            'specs' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
