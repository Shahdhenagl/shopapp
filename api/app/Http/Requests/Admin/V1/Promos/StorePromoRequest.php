<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\V1\Promos;

use Illuminate\Foundation\Http\FormRequest;

class StorePromoRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:64'],
            'type' => ['required', 'in:percent,fixed'],
            // Percentages are stored as a 0..1 fraction (0.10 = 10%). Fixed
            // amounts are a positive absolute value in the store currency.
            'fraction' => ['required', 'numeric', 'min:0', 'max:1'],
            'active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
