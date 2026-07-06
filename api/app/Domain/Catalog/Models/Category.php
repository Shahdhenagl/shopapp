<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use BelongsToTenant;
    use HasTranslations;
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'slug',
        'label_key',
        'icon_key',
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
}
