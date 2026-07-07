<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->getTranslations('name'),
            'style' => $this->getTranslations('style'),
            'description' => $this->getTranslations('description'),
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'rating' => (float) $this->rating,
            'is_newest' => (bool) $this->is_newest,
            'category_id' => $this->category_id,
            'images' => $this->images->pluck('url')->values(),
            'sizes' => $this->sizes->pluck('size')->values(),
            'colors' => $this->colors->map(
                fn ($c): string => '#' . strtoupper(str_pad(dechex((int) $c->color_value), 8, '0', STR_PAD_LEFT))
            )->values(),
            'created_at' => $this->created_at,
        ];
    }
}
