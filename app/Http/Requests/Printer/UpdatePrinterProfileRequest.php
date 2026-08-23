<?php

declare(strict_types=1);

namespace App\Http\Requests\Printer;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrinterProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['sometimes', 'string', 'max:160'],
        ];
    }
}
