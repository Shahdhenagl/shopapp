<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Favorites;

use Illuminate\Foundation\Http\FormRequest;

class ToggleFavoriteRequest extends FormRequest
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
            'product_id' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => __('validation.required', ['attribute' => 'product_id']),
        ];
    }
}
