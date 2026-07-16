<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Actions\Admin;

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Admin\Support\AuditLogger;
use App\Domain\Cart\Contracts\PromoRepositoryInterface;
use App\Domain\Catalog\Exceptions\ProductNotFoundException;
use App\Domain\Catalog\Models\Product;
use App\Domain\Checkout\Exceptions\EmptyPosSaleException;
use App\Domain\Checkout\Exceptions\InsufficientStockException;
use App\Domain\Checkout\Exceptions\PaymentTotalMismatchException;
use App\Domain\Checkout\Models\Order;
use App\Domain\Shared\ValueObjects\Money;
use Illuminate\Support\Facades\DB;

/**
 * Records an in-store (POS) sale: the cashier picks products directly, so there
 * is no cart and no app account required. Prices come from the catalog — client
 * totals are never trusted — stock is decremented in the same transaction, and
 * the money may be collected across several methods (cash / InstaPay / wallet /
 * card), with any 'deferred' portion left outstanding.
 */
final readonly class CreatePosOrderAction
{
    public function __construct(
        private PromoRepositoryInterface $promos,
        private AuditLogger $audit,
    ) {
    }

    /**
     * @param  list<array{product_id: int|string, size: string, color_value: int, quantity: int}>  $items
     * @param  list<array{method: string, amount: int|float|string}>  $payments  One row per tender; must add up to the sale total.
     */
    public function execute(
        AdminUser $actor,
        array $items,
        array $payments,
        ?int $userId = null,
        ?string $customerName = null,
        ?string $customerPhone = null,
        ?string $promoCode = null,
    ): Order {
        if ($items === []) {
            throw new EmptyPosSaleException;
        }

        $currency = (string) config('app.currency', 'EGP');

        return DB::transaction(function () use (
            $actor, $items, $payments, $userId, $customerName, $customerPhone, $promoCode, $currency
        ): Order {
            $subtotal = Money::zero($currency);
            $lines = [];

            foreach ($items as $item) {
                /** @var Product|null $product */
                $product = Product::query()->lockForUpdate()->find($item['product_id']);

                if ($product === null) {
                    throw new ProductNotFoundException;
                }

                $quantity = (int) $item['quantity'];

                if ((int) $product->stock < $quantity) {
                    throw new InsufficientStockException((string) $product->name, (int) $product->stock);
                }

                $product->decrement('stock', $quantity);

                $unitPrice = Money::fromMajor((string) $product->price, $currency);
                $lineTotal = $unitPrice->multiply($quantity);
                $subtotal = $subtotal->add($lineTotal);

                $lines[] = [
                    'product_id' => $product->id,
                    'name_snapshot' => (string) $product->name,
                    'size' => $item['size'],
                    'color_value' => (int) $item['color_value'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice->toMajorFloat(),
                    'line_total' => $lineTotal->toMajorFloat(),
                ];
            }

            $fraction = 0.0;
            $appliedCode = null;

            if ($promoCode !== null && $promoCode !== '') {
                $promo = $this->promos->findUsableByCode($promoCode);

                if ($promo !== null) {
                    $fraction = (float) $promo->fraction;
                    $appliedCode = $promo->code;
                }
            }

            $discount = $subtotal->percentage($fraction);
            $amount = $subtotal->subtract($discount);

            // The tenders must account for the whole sale — no silent shortfall.
            $collected = Money::zero($currency);
            $deferred = Money::zero($currency);

            foreach ($payments as $payment) {
                $tender = Money::fromMajor((string) $payment['amount'], $currency);
                $collected = $collected->add($tender);

                if ($payment['method'] === Order::PAYMENT_METHOD_DEFERRED) {
                    $deferred = $deferred->add($tender);
                }
            }

            if ($collected->toCents() !== $amount->toCents()) {
                throw new PaymentTotalMismatchException(
                    $collected->toMajorFloat(),
                    $amount->toMajorFloat(),
                );
            }

            // Fully collected only when nothing was left on the tab.
            $paid = $deferred->isZero();

            // Keep a readable summary on the order; the breakdown lives in
            // order_payments once a sale is split across methods.
            $method = count($payments) === 1
                ? (string) $payments[0]['method']
                : Order::PAYMENT_METHOD_SPLIT;

            /** @var Order $order */
            $order = Order::query()->create([
                'user_id' => $userId,
                'channel' => Order::CHANNEL_POS,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'status' => $paid ? Order::STATUS_PAID : Order::STATUS_PENDING,
                'subtotal' => $subtotal->toMajorFloat(),
                'discount' => $discount->toMajorFloat(),
                'amount' => $amount->toMajorFloat(),
                'currency' => $currency,
                'promo_code' => $appliedCode,
                'payment_method' => $method,
                'payment_status' => $paid ? Order::PAYMENT_PAID : Order::PAYMENT_PENDING,
            ]);

            $order->items()->createMany($lines);

            $order->payments()->createMany(array_map(
                static fn (array $payment): array => [
                    'method' => $payment['method'],
                    'amount' => Money::fromMajor((string) $payment['amount'], $currency)->toMajorFloat(),
                ],
                $payments,
            ));

            $this->audit->log($actor, 'order.pos_sale', $order, null, $order->toArray());

            return $order->load('items.product.images', 'payments');
        });
    }
}
