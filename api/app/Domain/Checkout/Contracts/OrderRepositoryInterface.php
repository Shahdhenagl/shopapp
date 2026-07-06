<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Contracts;

use App\Domain\Auth\Models\User;
use App\Domain\Cart\Models\Cart;
use App\Domain\Checkout\DTOs\AddressDetails;
use App\Domain\Checkout\Models\Order;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    /**
     * Persist an order + items snapshotted from the cart with server-recomputed
     * totals. Promo fraction (0..1) and code are applied if provided.
     */
    public function createFromCart(
        User $user,
        Cart $cart,
        float $promoFraction,
        ?string $promoCode,
        string $paymentMethod,
        AddressDetails $address,
    ): Order;

    /**
     * Set the order's payment status; mark the order paid when payment succeeded,
     * otherwise leave it pending.
     */
    public function applyPaymentStatus(Order $order, string $paymentStatus): Order;

    public function findById(string $id): ?Order;

    /**
     * Order history for the user, newest first, with items eager-loaded.
     *
     * @return Collection<int, Order>
     */
    public function forUser(User $user): Collection;
}
