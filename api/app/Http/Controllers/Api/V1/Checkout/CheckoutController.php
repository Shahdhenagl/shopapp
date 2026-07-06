<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Domain\Auth\Models\User;
use App\Domain\Checkout\Actions\CreateOrderAction;
use App\Domain\Checkout\DTOs\AddressDetails;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Checkout\CheckoutRequest;
use App\Http\Resources\OrderResource;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CreateOrderAction $createOrderAction,
    ) {
    }

    public function store(CheckoutRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $order = $this->createOrderAction->execute(
            $user,
            $request->validated('payment_method'),
            $request->input('card.payment_token'),
            AddressDetails::fromArray($request->validated('address')),
        );

        return OrderResource::make($order)->response()->setStatusCode(201);
    }
}
