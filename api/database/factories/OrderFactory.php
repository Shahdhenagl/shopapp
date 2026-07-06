<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Auth\Models\User;
use App\Domain\Checkout\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
final class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => 'pending',
            'subtotal' => 820,
            'discount' => 0,
            'amount' => 820,
            'currency' => 'EGP',
            'payment_method' => 'creditCard',
            'payment_status' => 'pending',
        ];
    }
}
