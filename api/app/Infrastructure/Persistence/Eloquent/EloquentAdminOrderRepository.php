<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Checkout\Contracts\AdminOrderRepositoryInterface;
use App\Domain\Checkout\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentAdminOrderRepository implements AdminOrderRepositoryInterface
{
    public function paginate(?string $status, int $perPage): LengthAwarePaginator
    {
        return Order::query()
            ->with(['items.product.images', 'user', 'address'])
            ->when($status !== null && $status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function find(string $id): ?Order
    {
        return Order::query()
            ->with(['items.product.images', 'user', 'address'])
            ->find($id);
    }

    public function updateStatus(Order $order, string $status, ?string $paymentStatus): Order
    {
        $order->status = $status;

        if ($paymentStatus !== null) {
            $order->payment_status = $paymentStatus;
        }

        $order->save();

        return $order->load(['items.product.images', 'user', 'address']);
    }
}
