<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\V1\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
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
        $hex = ['nullable', 'string', 'regex:/^#([0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/'];

        return [
            'app_name' => ['sometimes', 'string', 'max:255'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'storefront_mode' => ['sometimes', Rule::in(['single', 'multi_department'])],
            'logo_url' => ['sometimes', 'nullable', 'url'],
            'shipping_fee' => ['sometimes', 'numeric', 'min:0'],
            'brand_primary' => array_merge(['sometimes'], $hex),
            'brand_on_primary' => array_merge(['sometimes'], $hex),
            'brand_accent' => array_merge(['sometimes'], $hex),
            'flags' => ['sometimes', 'array'],
            'flags.*' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }
}
