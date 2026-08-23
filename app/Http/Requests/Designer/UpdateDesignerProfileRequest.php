<?php

declare(strict_types=1);

namespace App\Http\Requests\Designer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDesignerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'bio' => ['sometimes', 'nullable', 'string', 'max:2048'],
        ];
    }
}
