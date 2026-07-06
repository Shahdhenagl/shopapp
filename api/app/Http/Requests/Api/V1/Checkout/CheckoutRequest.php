<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
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
            'amount' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string'],
            'payment_method' => ['required', Rule::in(['creditCard', 'cash'])],
            'address' => ['required', 'array'],
            'address.address' => ['required', 'string'],
            'address.city' => ['required', 'string'],
            'address.area' => ['nullable', 'string'],
            'address.branch' => ['nullable', 'string'],
            'card' => ['nullable', 'array'],
            'card.payment_token' => ['required_if:payment_method,creditCard', 'nullable', 'string'],
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
