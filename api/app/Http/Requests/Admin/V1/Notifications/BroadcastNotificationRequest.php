<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\V1\Notifications;

use App\Domain\Tenancy\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BroadcastNotificationRequest extends FormRequest
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
            'type' => ['required', Rule::in(['order', 'promo', 'product', 'general'])],
            // Message may be a plain string (mirrored to both locales) or a
            // bilingual { en, ar } map — normalised in the controller.
            'message' => ['required'],
            'message.en' => ['sometimes', 'string'],
            'message.ar' => ['sometimes', 'string'],
            // 'all' broadcasts to every user; 'user' targets one (user_id required).
            'target' => ['nullable', Rule::in(['all', 'user'])],
            'user_id' => [
                'nullable',
                'required_if:target,user',
                'integer',
                Rule::exists('users', 'id')->where('tenant_id', app(TenantContext::class)->id()),
            ],
            'images' => ['nullable', 'array'],
            'images.*' => ['url'],
        ];
    }
}
