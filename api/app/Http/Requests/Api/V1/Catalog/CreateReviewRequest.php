<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class CreateReviewRequest extends FormRequest
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
            'rating' => ['required', 'integer', 'between:0,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rating.required' => __('validation.required', ['attribute' => 'rating']),
            'rating.integer' => __('validation.integer', ['attribute' => 'rating']),
            'rating.between' => __('validation.between.numeric', ['attribute' => 'rating', 'min' => 0, 'max' => 5]),
        ];
    }
}
