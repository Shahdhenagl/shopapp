<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Contracts;

use App\Domain\Checkout\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminOrderRepositoryInterface
{
    /**
     * Tenant orders, newest first, with items/user/address eager-loaded.
     * Optionally filtered by status.
     */
    public function paginate(?string $status, int $perPage): LengthAwarePaginator;

    public function find(string $id): ?Order;

    public function updateStatus(Order $order, string $status, ?string $paymentStatus): Order;
}
