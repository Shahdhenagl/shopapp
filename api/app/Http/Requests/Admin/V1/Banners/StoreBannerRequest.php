<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\V1\Banners;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBannerRequest extends FormRequest
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
            'image_url' => ['required', 'url'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'cta_text' => ['nullable', 'string', 'max:255'],
            'link_type' => ['required', Rule::in(['none', 'category', 'product', 'url'])],
            // Existence of a category/product target is checked in the action
            // (§7.3); here we only enforce a URL shape when the target is a URL.
            'link_value' => ['nullable', 'string', 'max:2048', Rule::when(
                $this->input('link_type') === 'url',
                ['url'],
            )],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
