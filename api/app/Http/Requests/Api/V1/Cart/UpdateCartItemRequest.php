<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Cart;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
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
            'quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quantity.required' => __('validation.required', ['attribute' => 'quantity']),
            'quantity.integer' => __('validation.integer', ['attribute' => 'quantity']),
            'quantity.min' => __('validation.min.numeric', ['attribute' => 'quantity', 'min' => 0]),
        ];
    }
}
