<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
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
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => __('validation.required', ['attribute' => 'file']),
            'file.file' => __('validation.file', ['attribute' => 'file']),
            'file.image' => __('validation.image', ['attribute' => 'file']),
            'file.mimes' => __('validation.mimes', ['attribute' => 'file', 'values' => 'jpg, jpeg, png, webp']),
            'file.max' => __('validation.max.file', ['attribute' => 'file', 'max' => '5120']),
        ];
    }
}
