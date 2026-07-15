<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;
use App\Domain\Catalog\Models\SubCategory;

class Category extends Model
{
    use BelongsToTenant;
    use HasTranslations;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'slug',
        'parent_id',
        'label_key',
        'icon_key',
        'image_url',
        'sort_order',
        'name',
    ];

    /**
     * @var list<string>
     */
    public array $translatable = ['name'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        // 'name' is managed by spatie/laravel-translatable (no 'array' cast).
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * Products reference the category by its per-tenant slug (the wire id), not
     * the surrogate UUID key.
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id', 'slug');
    }

    public function subCategories()
    {
        return $this->hasMany(SubCategory::class);
    }
    /**
     * The parent category (null = a top-level department / the app's left rail).
     *
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Direct sub-categories, ordered for display.
     *
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * A leaf has no children — only leaves may own products.
     */
    public function isLeaf(): bool
    {
        return ! $this->children()->exists();
    }
}
