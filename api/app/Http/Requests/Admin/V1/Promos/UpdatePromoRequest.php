<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\V1\Promos;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePromoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Every field is optional so the same endpoint serves a full edit and the
     * lightweight active toggle ({ active }).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'required', 'string', 'max:64'],
            'type' => ['sometimes', 'required', 'in:percent,fixed'],
            'fraction' => ['sometimes', 'required', 'numeric', 'min:0', 'max:1'],
            'active' => ['sometimes', 'boolean'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }
}
