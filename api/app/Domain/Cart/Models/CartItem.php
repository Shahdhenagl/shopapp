<?php

declare(strict_types=1);

namespace App\Domain\Cart\Models;

use App\Domain\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'size',
        'color_value',
        'quantity',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'color_value' => 'integer',
            'quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lineId(): string
    {
        return "{$this->product_id}|{$this->size}|{$this->color_value}";
    }
}
