<?php

declare(strict_types=1);

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $settingId = $this->route('setting')?->id;

        return [
            'key' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('settings', 'key')->ignore($settingId)],
            'value' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
