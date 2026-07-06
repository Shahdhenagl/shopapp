<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_id' => (string) $this->product_id,
            'name' => $this->name_snapshot,
            'size' => $this->size,
            'color' => (int) $this->color_value,
            'quantity' => (int) $this->quantity,
            'unit_price' => (int) round((float) $this->unit_price),
            'line_total' => (int) round((float) $this->line_total),
        ];
    }
}
