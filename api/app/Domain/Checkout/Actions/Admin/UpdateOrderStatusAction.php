<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Actions\Admin;

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Admin\Support\AuditLogger;
use App\Domain\Checkout\Contracts\AdminOrderRepositoryInterface;
use App\Domain\Checkout\Exceptions\InvalidOrderTransitionException;
use App\Domain\Checkout\Models\Order;

final readonly class UpdateOrderStatusAction
{
    public function __construct(
        private AdminOrderRepositoryInterface $orders,
        private AuditLogger $audit,
    ) {
    }

    /**
     * Moves an order to a new status, enforcing the allowed state machine
     * (§3.7). Payment status is kept in step for the states where it is implied:
     * marking an order paid settles payment; refunding it refunds payment.
     */
    public function execute(AdminUser $actor, Order $order, string $status): Order
    {
        if ($status === $order->status) {
            return $order;
        }

        if (! $order->canTransitionTo($status)) {
            throw new InvalidOrderTransitionException;
        }

        $before = ['status' => $order->status, 'payment_status' => $order->payment_status];

        $paymentStatus = match ($status) {
            Order::STATUS_PAID => Order::PAYMENT_PAID,
            Order::STATUS_REFUNDED => Order::PAYMENT_REFUNDED,
            default => null,
        };

        $order = $this->orders->updateStatus($order, $status, $paymentStatus);

        $this->audit->log(
            $actor,
            'order.status_updated',
            $order,
            $before,
            ['status' => $order->status, 'payment_status' => $order->payment_status],
        );

        return $order;
    }
}
