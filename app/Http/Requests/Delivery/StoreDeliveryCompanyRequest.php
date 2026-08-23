<?php

declare(strict_types=1);

namespace App\Http\Requests\Delivery;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryCompanyRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'coverage_zones' => ['nullable', 'array'],
            'coverage_zones.*' => ['string', 'max:64'],
            'tracking_url_pattern' => ['nullable', 'string', 'max:255'],
        ];
    }
}
