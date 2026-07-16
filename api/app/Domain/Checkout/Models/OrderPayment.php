<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single tender against an order. A sale collected across several methods
 * has one row per method; the amounts add up to the order total.
 */
class OrderPayment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'method',
        'amount',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
