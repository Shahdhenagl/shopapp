<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Addresses;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
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
            'label' => ['sometimes', 'nullable', 'string'],
            'address' => ['sometimes', 'string'],
            'city' => ['sometimes', 'string'],
            'area' => ['sometimes', 'nullable', 'string'],
            'branch' => ['sometimes', 'nullable', 'string'],
            'phone' => ['sometimes', 'nullable', 'string'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
