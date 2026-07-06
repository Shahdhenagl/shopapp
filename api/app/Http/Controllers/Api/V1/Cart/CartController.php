<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cart;

use App\Domain\Auth\Models\User;
use App\Domain\Cart\Actions\AddItemToCartAction;
use App\Domain\Cart\Actions\ApplyPromoAction;
use App\Domain\Cart\Actions\ClearCartAction;
use App\Domain\Cart\Actions\RemoveCartItemAction;
use App\Domain\Cart\Actions\ShowCartAction;
use App\Domain\Cart\Actions\UpdateCartItemAction;
use App\Domain\Cart\Contracts\PromoRepositoryInterface;
use App\Domain\Cart\Models\Cart;
use App\Domain\Cart\Services\CartCalculator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cart\AddCartItemRequest;
use App\Http\Requests\Api\V1\Cart\ApplyPromoRequest;
use App\Http\Requests\Api\V1\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly ShowCartAction $showCartAction,
        private readonly AddItemToCartAction $addItemToCartAction,
        private readonly UpdateCartItemAction $updateCartItemAction,
        private readonly RemoveCartItemAction $removeCartItemAction,
        private readonly ClearCartAction $clearCartAction,
        private readonly ApplyPromoAction $applyPromoAction,
        private readonly CartCalculator $cartCalculator,
        private readonly PromoRepositoryInterface $promos,
    ) {
    }

    public function show(Request $request): CartResource
    {
        return $this->cartResponse($this->showCartAction->execute($this->user($request)));
    }

    public function store(AddCartItemRequest $request): CartResource
    {
        $cart = $this->addItemToCartAction->execute(
            $this->user($request),
            $request->validated('product_id'),
            $request->validated('size'),
            (int) $request->validated('color'),
            (int) $request->validated('quantity'),
        );

        return $this->cartResponse($cart);
    }

    public function update(UpdateCartItemRequest $request, string $lineId): CartResource
    {
        $cart = $this->updateCartItemAction->execute(
            $this->user($request),
            $lineId,
            (int) $request->validated('quantity'),
        );

        return $this->cartResponse($cart);
    }

    public function destroy(Request $request, string $lineId): CartResource
    {
        $cart = $this->removeCartItemAction->execute($this->user($request), $lineId);

        return $this->cartResponse($cart);
    }

    public function clear(Request $request): CartResource
    {
        return $this->cartResponse($this->clearCartAction->execute($this->user($request)));
    }

    public function promo(ApplyPromoRequest $request): JsonResponse
    {
        $promo = $this->applyPromoAction->execute(
            $this->user($request),
            $request->validated('code'),
        );

        return response()->json([
            'fraction' => (float) $promo->fraction,
            'code' => $promo->code,
        ]);
    }

    private function cartResponse(Cart $cart): CartResource
    {
        // Reflect any promo persisted on the cart in the summary, ignoring it if
        // it has since expired or been deactivated.
        $promoCode = $cart->promo_code;
        $fraction = null;

        if ($promoCode !== null && $promoCode !== '') {
            $promo = $this->promos->findUsableByCode($promoCode);
            $fraction = $promo !== null ? (float) $promo->fraction : null;
            $promoCode = $promo?->code;
        }

        $summary = $this->cartCalculator->summarize($cart, $promoCode, $fraction);

        return new CartResource($cart, $summary);
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
