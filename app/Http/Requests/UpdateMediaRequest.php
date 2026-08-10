<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMediaRequest extends FormRequest
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
            'collection_name' => ['sometimes', Rule::in(['mockup', 'print_file', 'payment_proof', 'attachment'])],
            'file_path'       => ['sometimes', 'string', 'max:500'],
        ];
    }
}