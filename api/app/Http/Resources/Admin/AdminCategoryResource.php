<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The dashboard-facing shape of a category. Unlike the app-facing
 * CategoryResource (which keys by slug), this exposes the raw UUID identifiers
 * the admin editor works with, both locales of the name, and the nested tree.
 */
class AdminCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, string> $translations */
        $translations = $this->getTranslations('name');

        return [
            'id' => (string) $this->id,
            'slug' => $this->slug,
            'parent_id' => $this->parent_id !== null ? (string) $this->parent_id : null,
            'name' => [
                'en' => $translations['en'] ?? null,
                'ar' => $translations['ar'] ?? null,
            ],
            'label_key' => $this->label_key,
            'icon_key' => $this->icon_key,
            'image_url' => $this->image_url,
            'sort_order' => (int) $this->sort_order,
            'is_leaf' => $this->relationLoaded('children')
                ? $this->children->isEmpty()
                : $this->isLeaf(),
            'product_count' => $this->resolveProductCount(),
            'children' => $this->whenLoaded(
                'children',
                fn () => AdminCategoryResource::collection($this->children),
            ),
        ];
    }

    /**
     * Prefer the eager-loaded `products_count` from the tree query; fall back to
     * a direct count when the resource is rendered outside the tree.
     */
    private function resolveProductCount(): int
    {
        if ($this->products_count !== null) {
            return (int) $this->products_count;
        }

        return $this->products()->count();
    }
}
