<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Contracts;

use App\Domain\Checkout\Models\Order;

interface PaymentProcessor
{
    /**
     * The payment method this processor handles (matches Order::PAYMENT_METHOD_*).
     */
    public function method(): string;

    /**
     * Process payment for the order, returning one of Order::PAYMENT_* statuses.
     */
    public function process(Order $order, ?string $paymentToken): string;
}
