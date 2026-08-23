<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPrinterProvider() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', 'string', 'max:60'],
            'base_cost' => ['required', 'numeric', 'min:0'],
            'specs' => ['nullable', 'array'],
        ];
    }
}
