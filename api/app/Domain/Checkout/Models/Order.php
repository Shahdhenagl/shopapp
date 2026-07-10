<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Models;

use App\Domain\Auth\Models\User;
use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use SoftDeletes;

    public const string STATUS_PENDING = 'pending';
    public const string STATUS_PAID = 'paid';
    public const string STATUS_SHIPPED = 'shipped';
    public const string STATUS_DELIVERED = 'delivered';
    public const string STATUS_CANCELLED = 'cancelled';
    public const string STATUS_REFUNDED = 'refunded';

    /**
     * Allowed status transitions for the dashboard (§3.7). A terminal state
     * (delivered / cancelled / refunded) has no onward moves.
     *
     * @var array<string, list<string>>
     */
    public const array STATUS_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_PAID, self::STATUS_CANCELLED],
        self::STATUS_PAID => [self::STATUS_SHIPPED, self::STATUS_CANCELLED, self::STATUS_REFUNDED],
        self::STATUS_SHIPPED => [self::STATUS_DELIVERED, self::STATUS_REFUNDED],
        self::STATUS_DELIVERED => [self::STATUS_REFUNDED],
        self::STATUS_CANCELLED => [],
        self::STATUS_REFUNDED => [],
    ];

    public const string PAYMENT_METHOD_CARD = 'creditCard';
    public const string PAYMENT_METHOD_CASH = 'cash';

    public const string PAYMENT_PENDING = 'pending';
    public const string PAYMENT_PAID = 'paid';
    public const string PAYMENT_FAILED = 'failed';
    public const string PAYMENT_REFUNDED = 'refunded';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'status',
        'subtotal',
        'discount',
        'amount',
        'currency',
        'promo_code',
        'payment_method',
        'payment_status',
        'idempotency_key',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $model): void {
            if (empty($model->id)) {
                do {
                    $id = 'MOD-' . strtoupper(Str::random(6));
                } while (static::query()->whereKey($id)->exists());

                $model->id = $id;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasOne<Address, $this>
     */
    public function address(): HasOne
    {
        return $this->hasOne(Address::class);
    }

    /**
     * @return list<string>
     */
    public function allowedTransitions(): array
    {
        return self::STATUS_TRANSITIONS[$this->status] ?? [];
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }
}
