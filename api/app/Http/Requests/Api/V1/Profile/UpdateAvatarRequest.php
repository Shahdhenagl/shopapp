<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvatarRequest extends FormRequest
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
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required' => __('validation.required', ['attribute' => 'image']),
            'image.file' => __('validation.file', ['attribute' => 'image']),
            'image.image' => __('validation.image', ['attribute' => 'image']),
            'image.mimes' => __('validation.mimes', ['attribute' => 'image', 'values' => 'jpg, jpeg, png, webp']),
            'image.max' => __('validation.max.file', ['attribute' => 'image', 'max' => '5120']),
        ];
    }
}
