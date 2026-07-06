<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Cart;

use Illuminate\Foundation\Http\FormRequest;

class AddCartItemRequest extends FormRequest
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
            'size' => ['required', 'string'],
            'color' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => __('validation.required', ['attribute' => 'product_id']),
            'size.required' => __('validation.required', ['attribute' => 'size']),
            'color.required' => __('validation.required', ['attribute' => 'color']),
            'color.integer' => __('validation.integer', ['attribute' => 'color']),
            'quantity.required' => __('validation.required', ['attribute' => 'quantity']),
            'quantity.integer' => __('validation.integer', ['attribute' => 'quantity']),
            'quantity.min' => __('validation.min.numeric', ['attribute' => 'quantity', 'min' => 1]),
        ];
    }
}
