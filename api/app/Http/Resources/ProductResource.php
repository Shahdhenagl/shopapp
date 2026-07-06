<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => (string) $this->name,
            'style' => (string) $this->style,
            'description' => (string) $this->description,
            'price' => (int) round((float) $this->price),
            'currency' => $this->currency,
            'images' => $this->images->pluck('url')->values(),
            'colors' => $this->colors->map(
                fn ($c): string => '#' . strtoupper(str_pad(dechex((int) $c->color_value), 8, '0', STR_PAD_LEFT))
            )->values(),
            'sizes' => $this->sizes->pluck('size')->values(),
            'category_id' => $this->category_id,
            'rating' => (float) $this->rating,
            'is_newest' => (bool) $this->is_newest,
        ];
    }
}
