<?php

declare(strict_types=1);

namespace App\Http\Requests\Printer;

use Illuminate\Foundation\Http\FormRequest;

class StorePrinterProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPrinterProvider() ?? false;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:160'],
        ];
    }
}
