<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest
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
            'notifiable_type' => ['required', 'string', 'max:255'],
            'notifiable_id'   => ['required', 'integer'],
            'type'            => ['required', 'string', 'max:255'],
            'data'            => ['required', 'array'],
            'read_at'         => ['nullable', 'date'],
        ];
    }
}