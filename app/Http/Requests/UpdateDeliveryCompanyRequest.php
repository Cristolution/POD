<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeliveryCompanyRequest extends FormRequest
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
        $companyId = $this->route('delivery_company')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('delivery_companies', 'name')->ignore($companyId)],
            'coverage_zones' => ['sometimes', 'nullable', 'array'],
            'coverage_zones.*' => ['string'],
            'tracking_url_pattern' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
