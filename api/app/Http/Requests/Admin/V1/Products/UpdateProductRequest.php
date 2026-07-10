<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\V1\Products;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
            'name' => ['sometimes', 'required'],
            'name.en' => ['sometimes', 'string'],
            'name.ar' => ['sometimes', 'string'],

            'style' => ['sometimes', 'nullable'],
            'style.en' => ['sometimes', 'string'],
            'style.ar' => ['sometimes', 'string'],

            'description' => ['sometimes', 'nullable'],
            'description.en' => ['sometimes', 'string'],
            'description.ar' => ['sometimes', 'string'],

            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'nullable', 'string'],
            'is_newest' => ['sometimes', 'boolean'],
            'rating' => ['sometimes', 'nullable', 'numeric', 'between:0,5'],
            'status' => ['sometimes', 'in:active,hidden'],
            'stock' => ['sometimes', 'integer', 'min:0'],

            'category_id' => ['sometimes', 'required', 'string'],

            'images' => ['sometimes', 'nullable', 'array'],
            'images.*' => ['url'],

            'sizes' => ['sometimes', 'nullable', 'array'],
            'sizes.*' => ['string'],

            'colors' => ['sometimes', 'nullable', 'array'],
            'colors.*' => ['nullable'],
        ];
    }
}
