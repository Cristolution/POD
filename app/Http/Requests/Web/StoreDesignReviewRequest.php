<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use App\Models\DesignReview;
use Illuminate\Foundation\Http\FormRequest;

class StoreDesignReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCustomer() ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:'.DesignReview::MIN_RATING.','.DesignReview::MAX_RATING],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
