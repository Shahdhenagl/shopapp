<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions\Admin;

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Admin\Support\AuditLogger;
use App\Domain\Catalog\Contracts\AdminCategoryRepositoryInterface;
use App\Domain\Catalog\Exceptions\CategoryCycleException;
use App\Domain\Catalog\Models\Category;
use Illuminate\Support\Str;

final readonly class UpdateCategoryAction
{
    public function __construct(
        private AdminCategoryRepositoryInterface $categories,
        private AuditLogger $audit,
    ) {
    }

    /**
     * Applies a partial update (only the supplied keys) to a category, including
     * reparenting. Guards against cycles (§7.4) before persisting and records the
     * before/after snapshot in the audit trail.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(AdminUser $actor, Category $category, array $data): Category
    {
        $before = $category->toArray();
        $attributes = [];

        if (array_key_exists('name', $data)) {
            $attributes['name'] = $this->normalizeName($data['name']);
        }

        if (array_key_exists('slug', $data) && $data['slug'] !== null && $data['slug'] !== '') {
            $attributes['slug'] = $this->resolveSlug((string) $data['slug'], $category);
        }

        if (array_key_exists('parent_id', $data)) {
            $parentId = $data['parent_id'] !== null ? (string) $data['parent_id'] : null;
            $this->guardAgainstCycle($category, $parentId);
            $attributes['parent_id'] = $parentId;
        }

        foreach (['label_key', 'icon_key', 'image_url'] as $key) {
            if (array_key_exists($key, $data)) {
                $attributes[$key] = $data[$key];
            }
        }

        if (array_key_exists('sort_order', $data) && $data['sort_order'] !== null) {
            $attributes['sort_order'] = (int) $data['sort_order'];
        }

        $category = $this->categories->update($category, $attributes);

        $this->audit->log($actor, 'category.updated', $category, $before, $category->toArray());

        return $category;
    }

    /**
     * A category may not become its own descendant or self: walk up the proposed
     * parent chain and reject if it reaches the category being edited.
     */
    private function guardAgainstCycle(Category $category, ?string $newParentId): void
    {
        if ($newParentId === null) {
            return;
        }

        if ($newParentId === $category->getKey()) {
            throw new CategoryCycleException;
        }

        $current = $this->categories->find($newParentId);

        while ($current !== null) {
            if ($current->getKey() === $category->getKey()) {
                throw new CategoryCycleException;
            }

            $current = $current->parent_id !== null
                ? $this->categories->find($current->parent_id)
                : null;
        }
    }

    /**
     * @return array{en: string, ar: string}
     */
    private function normalizeName(mixed $name): array
    {
        if (is_array($name)) {
            $en = (string) ($name['en'] ?? $name['ar'] ?? '');
            $ar = (string) ($name['ar'] ?? $name['en'] ?? '');

            return ['en' => $en, 'ar' => $ar];
        }

        $value = (string) $name;

        return ['en' => $value, 'ar' => $value];
    }

    /**
     * Slugify and guarantee per-tenant uniqueness, ignoring the category itself.
     */
    private function resolveSlug(string $slug, Category $ignore): string
    {
        $base = Str::slug($slug);

        if ($base === '') {
            $base = 'category';
        }

        $candidate = $base;
        $suffix = 2;

        while (
            Category::withTrashed()
                ->where('slug', $candidate)
                ->whereKeyNot($ignore->getKey())
                ->exists()
        ) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
