<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeliveryCompanyRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', Rule::unique('delivery_companies', 'name')],
            'coverage_zones' => ['nullable', 'array'],
            'coverage_zones.*' => ['string'],
            'tracking_url_pattern' => ['nullable', 'string', 'max:500'],
        ];
    }
}
