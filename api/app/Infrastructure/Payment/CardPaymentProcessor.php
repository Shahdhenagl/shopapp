<?php

declare(strict_types=1);

namespace App\Infrastructure\Payment;

use App\Domain\Checkout\Contracts\PaymentProcessor;
use App\Domain\Checkout\Exceptions\PaymentDeclinedException;
use App\Domain\Checkout\Models\Order;

final class CardPaymentProcessor implements PaymentProcessor
{
    public function method(): string
    {
        return Order::PAYMENT_METHOD_CARD;
    }

    public function process(Order $order, ?string $paymentToken): string
    {
        if ($paymentToken === null || $paymentToken === '') {
            throw new PaymentDeclinedException;
        }

        // TODO: integrate a real PSP SDK (Stripe/Moyasar/Checkout.com) — charge the tokenized card server-side.
        return Order::PAYMENT_PAID;
    }
}
