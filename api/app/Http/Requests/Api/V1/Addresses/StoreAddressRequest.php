<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Addresses;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
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
            'label' => ['nullable', 'string'],
            'address' => ['required', 'string'],
            'city' => ['required', 'string'],
            'area' => ['nullable', 'string'],
            'branch' => ['nullable', 'string'],
            'phone' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'address.required' => __('validation.required', ['attribute' => 'address']),
            'city.required' => __('validation.required', ['attribute' => 'city']),
        ];
    }
}
