<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // The client identifies categories by the per-tenant slug, not the
            // surrogate UUID primary key.
            'id' => $this->slug,
            // Parent department's slug (null = top-level department). The app
            // builds the browse tree from these references.
            'parent_id' => $this->whenLoaded('parent', fn () => $this->parent?->slug, $this->parentSlugFallback()),
            'label_key' => $this->label_key,
            'icon_key' => $this->icon_key,
            'image_url' => $this->image_url,
            'sort_order' => (int) $this->sort_order,
        ];
    }

    /**
     * When the parent relation isn't eager-loaded, avoid an N+1: only resolve it
     * if a parent_id is actually set.
     */
    private function parentSlugFallback(): ?string
    {
        return $this->parent_id !== null ? $this->parent?->slug : null;
    }
}
