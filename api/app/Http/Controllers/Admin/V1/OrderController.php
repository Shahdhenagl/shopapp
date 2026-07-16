<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Checkout\Actions\Admin\CreatePosOrderAction;
use App\Domain\Checkout\Actions\Admin\UpdateOrderStatusAction;
use App\Domain\Checkout\Contracts\AdminOrderRepositoryInterface;
use App\Domain\Checkout\Models\Order;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Orders\StorePosOrderRequest;
use App\Http\Requests\Admin\V1\Orders\UpdateOrderStatusRequest;
use App\Http\Resources\Admin\AdminOrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(
        private readonly AdminOrderRepositoryInterface $orders,
        private readonly UpdateOrderStatusAction $updateStatusAction,
        private readonly CreatePosOrderAction $createPosOrderAction,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $paginator = $this->orders->paginate(
            $request->query('status'),
            (int) $request->query('per_page', 20),
        );

        return AdminOrderResource::collection($paginator);
    }

    public function show(string $id): AdminOrderResource
    {
        return AdminOrderResource::make($this->findOrFail($id));
    }

    /**
     * Record an in-store (POS) sale. Totals are computed from the catalog and
     * stock is decremented server-side.
     */
    public function store(StorePosOrderRequest $request): JsonResponse
    {
        /** @var list<array{product_id: int|string, size: string, color_value: int, quantity: int}> $items */
        $items = $request->validated('items');

        $order = $this->createPosOrderAction->execute(
            $this->actor($request),
            $items,
            $request->validated('payment_method'),
            $request->validated('user_id'),
            $request->validated('customer_name'),
            $request->validated('customer_phone'),
            $request->validated('promo_code'),
        );

        return AdminOrderResource::make($order)->response()->setStatusCode(201);
    }

    public function update(UpdateOrderStatusRequest $request, string $id): AdminOrderResource
    {
        $order = $this->findOrFail($id);

        $order = $this->updateStatusAction->execute(
            $this->actor($request),
            $order,
            $request->validated('status'),
        );

        return AdminOrderResource::make($order);
    }

    private function findOrFail(string $id): Order
    {
        $order = $this->orders->find($id);

        abort_if($order === null, 404, __('api.not_found'));

        return $order;
    }

    private function actor(Request $request): AdminUser
    {
        /** @var AdminUser $admin */
        $admin = $request->user();

        return $admin;
    }
}
