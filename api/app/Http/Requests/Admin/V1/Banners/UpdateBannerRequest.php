<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\V1\Banners;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBannerRequest extends FormRequest
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
            'image_url' => ['sometimes', 'required', 'url'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'subtitle' => ['sometimes', 'nullable', 'string', 'max:255'],
            'cta_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'link_type' => ['sometimes', 'required', Rule::in(['none', 'category', 'product', 'url'])],
            'link_value' => ['sometimes', 'nullable', 'string', 'max:2048', Rule::when(
                $this->input('link_type') === 'url',
                ['url'],
            )],
            'sort_order' => ['sometimes', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
