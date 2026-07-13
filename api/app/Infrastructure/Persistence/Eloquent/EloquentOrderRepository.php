<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Auth\Models\User;
use App\Domain\Cart\Models\Cart;
use App\Domain\Checkout\Contracts\OrderRepositoryInterface;
use App\Domain\Checkout\DTOs\AddressDetails;
use App\Domain\Checkout\Models\Order;
use App\Domain\Shared\ValueObjects\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function createFromCart(
        User $user,
        Cart $cart,
        float $promoFraction,
        ?string $promoCode,
        string $paymentMethod,
        AddressDetails $address,
    ): Order {
        $currency = (string) config('app.currency', 'EGP');

        return DB::transaction(function () use ($user, $cart, $promoFraction, $promoCode, $paymentMethod, $address, $currency): Order {
            $subtotal = Money::fromMajor('0', $currency);
            $lines = [];

            foreach ($cart->items as $item) {
                $product = $item->product;
                $quantity = (int) $item->quantity;

                $unitPrice = Money::fromMajor((string) $product->price, $currency);
                $lineTotal = $unitPrice->multiply($quantity);
                $subtotal = $subtotal->add($lineTotal);

                $lines[] = [
                    'product_id' => $product->id,
                    'name_snapshot' => (string) $product->name,
                    'size' => $item->size,
                    'color_value' => (int) $item->color_value,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice->toMajorFloat(),
                    'line_total' => $lineTotal->toMajorFloat(),
                ];
            }

            $discount = $subtotal->percentage($promoFraction);
            $amount = $subtotal->subtract($discount);

            /** @var Order $order */
            $order = Order::query()->create([
                'user_id' => $user->id,
                'status' => Order::STATUS_PENDING,
                'subtotal' => $subtotal->toMajorFloat(),
                'discount' => $discount->toMajorFloat(),
                'amount' => $amount->toMajorFloat(),
                'currency' => $currency,
                'promo_code' => $promoCode,
                'payment_method' => $paymentMethod,
                'payment_status' => Order::PAYMENT_PENDING,
            ]);

            $order->items()->createMany($lines);

            $order->address()->create([
                'user_id' => $user->id,
                'address' => $address->address,
                'city' => $address->city,
                'area' => $address->area,
                'branch' => $address->branch,
            ]);

            return $order->load('items.product.images');
        });
    }

    public function applyPaymentStatus(Order $order, string $paymentStatus): Order
    {
        $order->payment_status = $paymentStatus;
        $order->status = $paymentStatus === Order::PAYMENT_PAID
            ? Order::STATUS_PAID
            : Order::STATUS_PENDING;
        $order->save();

        return $order;
    }

    public function findById(string $id): ?Order
    {
        return Order::query()->with('items.product.images')->find($id);
    }

    /**
     * @return Collection<int, Order>
     */
    public function forUser(User $user): Collection
    {
        return Order::query()
            ->with('items.product.images')
            ->where('user_id', $user->id)
            ->latest()
            ->get();
    }
}
