<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMediaRequest extends FormRequest
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
            'model_type'     => ['required', 'string', 'max:255'],
            'model_id'       => ['required', 'integer'],
            'collection_name' => ['required', Rule::in(['mockup', 'print_file', 'payment_proof', 'attachment'])],
            'file_path'      => ['required', 'string', 'max:500'],
        ];
    }
}