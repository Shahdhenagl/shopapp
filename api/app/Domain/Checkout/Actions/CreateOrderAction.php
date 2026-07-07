<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Actions;

use App\Domain\Auth\Exceptions\EmailNotVerifiedException;
use App\Domain\Auth\Models\User;
use App\Domain\Cart\Contracts\CartRepositoryInterface;
use App\Domain\Cart\Contracts\PromoRepositoryInterface;
use App\Domain\Checkout\Contracts\OrderRepositoryInterface;
use App\Domain\Checkout\DTOs\AddressDetails;
use App\Domain\Checkout\Exceptions\EmptyCartException;
use App\Domain\Checkout\Models\Order;
use App\Domain\Checkout\Support\PaymentProcessorFactory;

/**
 * Snapshots the user's cart into an order (totals recomputed server-side),
 * then processes payment via the matching processor. Never trusts client totals.
 */
final readonly class CreateOrderAction
{
    public function __construct(
        private CartRepositoryInterface $carts,
        private PromoRepositoryInterface $promos,
        private OrderRepositoryInterface $orders,
        private PaymentProcessorFactory $processors,
    ) {
    }

    public function execute(User $user, string $paymentMethod, ?string $paymentToken, AddressDetails $address): Order
    {
        // Soft email verification is enforced here — the single server-side gate.
        if ($user->email_verified_at === null) {
            throw new EmailNotVerifiedException;
        }

        $cart = $this->carts->forUser($user);

        if ($cart->items->isEmpty()) {
            throw new EmptyCartException;
        }

        $fraction = 0.0;
        $appliedCode = null;

        if ($cart->promo_code !== null && $cart->promo_code !== '') {
            $promo = $this->promos->findUsableByCode($cart->promo_code);

            if ($promo !== null) {
                $fraction = (float) $promo->fraction;
                $appliedCode = $promo->code;
            }
        }

        $order = $this->orders->createFromCart($user, $cart, $fraction, $appliedCode, $paymentMethod, $address);

        $processor = $this->processors->for($paymentMethod);
        $status = $processor->process($order, $paymentToken);

        $order = $this->orders->applyPaymentStatus($order, $status);

        $this->carts->clear($cart);

        return $order;
    }
}
