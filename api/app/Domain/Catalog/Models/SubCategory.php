<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;
use App\Traits\Uploadable;

class SubCategory extends Model
{
    /** @use HasFactory<\Database\Factories\SubCategoryFactory> */
    use HasFactory, HasTranslations, Uploadable;

    protected $fillable = [
        'category_id',
        'tenant_id',
        'product_id',
        'slug',
        'name',
        'image',
    ];

    public array $translatable = ['name'];

    protected $casts = [
        'name' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }


    public function setImageAttribute($value): void
    {
        if ($value) {
            $this->attributes['image'] = $this->uploadFile(
                $value,
                'sub-categories'
            );
        }
    }

    public function getImageAttribute($value): string
    {
        return $this->getImagePath(
            $value,
            'sub-categories'
        );
    }
}
