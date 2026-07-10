<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\V1\Products;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            // May be a scalar string OR a { en, ar } map — normalized in the
            // Action. Nested keys are validated only when the map form is sent.
            'name' => ['required'],
            'name.en' => ['sometimes', 'string'],
            'name.ar' => ['sometimes', 'string'],

            'style' => ['nullable'],
            'style.en' => ['sometimes', 'string'],
            'style.ar' => ['sometimes', 'string'],

            'description' => ['nullable'],
            'description.en' => ['sometimes', 'string'],
            'description.ar' => ['sometimes', 'string'],

            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string'],
            'is_newest' => ['boolean'],
            'rating' => ['nullable', 'numeric', 'between:0,5'],
            'status' => ['nullable', 'in:active,hidden'],
            'stock' => ['nullable', 'integer', 'min:0'],

            'category_id' => ['required', 'string'],

            'images' => ['nullable', 'array'],
            'images.*' => ['url'],

            'sizes' => ['nullable', 'array'],
            'sizes.*' => ['string'],

            'colors' => ['nullable', 'array'],
            'colors.*' => ['nullable'],
        ];
    }
}
