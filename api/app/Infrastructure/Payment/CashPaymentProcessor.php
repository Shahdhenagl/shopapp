<?php

declare(strict_types=1);

namespace App\Infrastructure\Payment;

use App\Domain\Checkout\Contracts\PaymentProcessor;
use App\Domain\Checkout\Models\Order;

final class CashPaymentProcessor implements PaymentProcessor
{
    public function method(): string
    {
        return Order::PAYMENT_METHOD_CASH;
    }

    public function process(Order $order, ?string $paymentToken): string
    {
        // Cash on delivery — payment is collected later, so it stays pending.
        return Order::PAYMENT_PENDING;
    }
}
