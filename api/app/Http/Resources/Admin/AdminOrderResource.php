<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Domain\Checkout\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Dashboard order shape (§3.7). Carries the operational detail the app-facing
 * OrderResource omits: customer, payment status, full money breakdown, shipping
 * address and the set of statuses this order may still transition to.
 */
class AdminOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'user_id' => (string) $this->user_id,
            'user_name' => $this->whenLoaded('user', fn () => $this->user?->name),
            'user_email' => $this->whenLoaded('user', fn () => $this->user?->email),
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount,
            'total' => (float) $this->amount,
            'currency' => $this->currency,
            'promo_code' => $this->promo_code,
            'allowed_transitions' => $this->allowedTransitions(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(
                fn (OrderItem $item): array => [
                    'id' => (string) $item->id,
                    'product_id' => $item->product_id !== null ? (string) $item->product_id : null,
                    'name_snapshot' => $item->name_snapshot,
                    'size' => $item->size,
                    'color_value' => (int) $item->color_value,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                ],
            )->values()),
            'shipping_address' => $this->whenLoaded('address', fn () => $this->formatAddress()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * A single human-readable shipping line, or null when no address exists.
     */
    private function formatAddress(): ?string
    {
        if ($this->address === null) {
            return null;
        }

        $parts = array_filter([
            $this->address->address,
            $this->address->area,
            $this->address->city,
            $this->address->branch,
        ], static fn ($v): bool => $v !== null && $v !== '');

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
