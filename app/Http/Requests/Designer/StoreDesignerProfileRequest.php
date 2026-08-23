<?php

declare(strict_types=1);

namespace App\Http\Requests\Designer;

use Illuminate\Foundation\Http\FormRequest;

class StoreDesignerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDesigner() ?? false;
    }

    public function rules(): array
    {
        return [
            'bio' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
