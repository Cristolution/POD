<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:10240'],
            'model_type' => ['required', 'string', 'max:255'],
            'model_id' => ['required', 'string'],
            'collection_name' => ['required', 'string', 'max:60'],
        ];
    }
}
