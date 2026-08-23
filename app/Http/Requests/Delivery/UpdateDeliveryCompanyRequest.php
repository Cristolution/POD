<?php

declare(strict_types=1);

namespace App\Http\Requests\Delivery;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryCompanyRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'coverage_zones' => ['sometimes', 'nullable', 'array'],
            'coverage_zones.*' => ['string', 'max:64'],
            'tracking_url_pattern' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
