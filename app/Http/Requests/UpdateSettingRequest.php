<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
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
        $settingId = $this->route('setting')?->id;

        return [
            'key'   => ['sometimes', 'string', 'max:255', Rule::unique('settings', 'key')->ignore($settingId)],
            'value' => ['sometimes', 'nullable', 'string'],
        ];
    }
}