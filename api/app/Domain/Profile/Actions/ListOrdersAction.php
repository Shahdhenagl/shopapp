<?php

declare(strict_types=1);

namespace App\Domain\Profile\Actions;

use App\Domain\Auth\Models\User;
use App\Domain\Checkout\Contracts\OrderRepositoryInterface;
use App\Domain\Checkout\Models\Order;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListOrdersAction
{
    public function __construct(
        private OrderRepositoryInterface $orders,
    ) {
    }

    /**
     * The authenticated user's order history, newest first.
     *
     * @return Collection<int, Order>
     */
    public function execute(User $user): Collection
    {
        return $this->orders->forUser($user);
    }
}
