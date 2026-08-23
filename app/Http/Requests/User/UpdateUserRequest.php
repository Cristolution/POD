<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $target = $this->route('user');

        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($target?->id, 'id')],
            'role' => ['sometimes', 'in:admin,designer,printer_provider,customer'],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
        ];
    }
}
