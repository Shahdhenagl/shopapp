<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions\Admin;

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Admin\Support\AuditLogger;
use App\Domain\Catalog\Contracts\AdminCategoryRepositoryInterface;
use App\Domain\Catalog\Models\Category;
use Illuminate\Support\Str;

final readonly class CreateCategoryAction
{
    public function __construct(
        private AdminCategoryRepositoryInterface $categories,
        private AuditLogger $audit,
    ) {
    }

    /**
     * Creates a category under the current tenant. Normalises the i18n `name`,
     * derives a unique per-tenant slug when one isn't supplied, and records the
     * mutation in the audit trail.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(AdminUser $actor, array $data): Category
    {
        $name = $this->normalizeName($data['name']);
        $slug = $this->resolveSlug($data['slug'] ?? null, $name['en']);

        $category = $this->categories->create([
            'name' => $name,
            'slug' => $slug,
            'parent_id' => $data['parent_id'] ?? null,
            'label_key' => $data['label_key'] ?? null,
            'icon_key' => $data['icon_key'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        $this->audit->log($actor, 'category.created', $category, null, $category->toArray());

        return $category;
    }

    /**
     * Accepts either a plain string (mirrored to both locales) or an
     * `{ en, ar }` object, always returning a filled translations map.
     *
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
     * Slugify the supplied slug (or the English name) and guarantee uniqueness
     * within the tenant by suffixing -2, -3, … as needed. Queries are already
     * tenant-scoped, so uniqueness is naturally per-tenant.
     */
    private function resolveSlug(?string $slug, string $nameEn): string
    {
        $base = Str::slug($slug !== null && $slug !== '' ? $slug : $nameEn);

        if ($base === '') {
            $base = 'category';
        }

        $candidate = $base;
        $suffix = 2;

        while (Category::withTrashed()->where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
