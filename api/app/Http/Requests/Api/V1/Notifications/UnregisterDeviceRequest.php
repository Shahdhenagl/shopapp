<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Notifications;

use Illuminate\Foundation\Http\FormRequest;

class UnregisterDeviceRequest extends FormRequest
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
            'token' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token.required' => __('validation.required', ['attribute' => 'token']),
        ];
    }
}
