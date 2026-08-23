<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class ReassignPrinterRequest extends FormRequest
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
            // order_items.printer_provider_id references printer_provider_profiles.id
            'printer_provider_id' => ['required', 'string', 'exists:printer_provider_profiles,id'],
        ];
    }
}
