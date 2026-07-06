<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Cart\DTOs\CartSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function __construct(
        mixed $resource,
        private readonly ?CartSummary $summary = null,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'items' => $this->items->map(function ($item): array {
                return [
                    'line_id' => $item->lineId(),
                    'size' => $item->size,
                    'color' => (int) $item->color_value,
                    'quantity' => (int) $item->quantity,
                    'line_total' => (int) round((float) $item->product->price * $item->quantity),
                    'product' => new ProductResource($item->product),
                ];
            })->values(),
            'summary' => $this->buildSummary(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSummary(): array
    {
        if ($this->summary === null) {
            $subtotal = 0;
            foreach ($this->items as $item) {
                $subtotal += (int) round((float) $item->product->price * $item->quantity);
            }

            return [
                'subtotal' => $subtotal,
                'discount' => 0,
                'total' => $subtotal,
                'currency' => 'EGP',
                'applied_promo' => null,
            ];
        }

        return [
            'subtotal' => $this->summary->subtotal->toMajorInt(),
            'discount' => $this->summary->discount->toMajorInt(),
            'total' => $this->summary->total->toMajorInt(),
            'currency' => $this->summary->total->currency(),
            'applied_promo' => $this->summary->hasPromo()
                ? ['code' => $this->summary->promoCode, 'fraction' => $this->summary->promoFraction]
                : null,
        ];
    }
}
