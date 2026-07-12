<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Domain\Auth\Models\User;
use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    /** Publish state — only `active` products reach the storefront. */
    public const string STATUS_ACTIVE = 'active';

    public const string STATUS_HIDDEN = 'hidden';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'tenant_id',
        'category_id',
        'price',
        'currency',
        'rating',
        'is_newest',
        'status',
        'stock',
        'name',
        'style',
        'description',
    ];

    /**
     * @var list<string>
     */
    public array $translatable = ['name', 'style', 'description'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        // NOTE: translatable fields (name/style/description) are intentionally
        // NOT cast to 'array' — spatie/laravel-translatable manages their JSON
        // storage and returns the active-locale string on access, so
        // (string) $product->name resolves correctly inside Resources.
        return [
            'price' => 'decimal:2',
            'rating' => 'decimal:1',
            'is_newest' => 'boolean',
            'stock' => 'integer',
        ];
    }

    /**
     * Only approved reviews count toward the public aggregate rating.
     *
     * @return HasMany<ProductReview, $this>
     */
    public function approvedReviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)
            ->where('status', ProductReview::STATUS_APPROVED);
    }

    protected static function newFactory(): Factory
    {
        return \Database\Factories\ProductFactory::new();
    }

    /**
     * Resolved by the category slug (the wire id) within the tenant.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'slug');
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    /**
     * @return HasMany<ProductColor, $this>
     */
    public function colors(): HasMany
    {
        return $this->hasMany(ProductColor::class)->orderBy('position');
    }

    /**
     * @return HasMany<ProductSize, $this>
     */
    public function sizes(): HasMany
    {
        return $this->hasMany(ProductSize::class)->orderBy('position');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')
            ->withTimestamps();
    }

    /**
     * Newest-first customer reviews (§6.3b).
     *
     * @return HasMany<ProductReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->latest();
    }
}
