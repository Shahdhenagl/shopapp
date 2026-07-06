<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Cart;

use Illuminate\Foundation\Http\FormRequest;

class ApplyPromoRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => __('validation.required', ['attribute' => 'code']),
            'code.max' => __('validation.max.string', ['attribute' => 'code', 'max' => 50]),
        ];
    }
}
