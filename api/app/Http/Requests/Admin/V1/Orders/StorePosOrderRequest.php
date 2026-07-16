<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\V1\Orders;

use App\Domain\Checkout\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePosOrderRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required'],
            'items.*.size' => ['required', 'string', 'max:32'],
            'items.*.color_value' => ['required', 'integer', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],

            // One row per tender — a sale may be split across methods. The
            // amounts must add up to the sale total (checked server-side).
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', Rule::in(Order::POS_PAYMENT_METHODS)],
            'payments.*.amount' => ['required', 'numeric', 'gt:0'],

            // Optional link to a registered customer; otherwise it's a walk-in.
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'customer_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'customer_phone' => ['sometimes', 'nullable', 'string', 'max:32'],

            'promo_code' => ['sometimes', 'nullable', 'string', 'max:64'],
        ];
    }
}
