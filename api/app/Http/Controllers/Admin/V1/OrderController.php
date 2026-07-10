<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Checkout\Actions\Admin\UpdateOrderStatusAction;
use App\Domain\Checkout\Contracts\AdminOrderRepositoryInterface;
use App\Domain\Checkout\Models\Order;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Orders\UpdateOrderStatusRequest;
use App\Http\Resources\Admin\AdminOrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(
        private readonly AdminOrderRepositoryInterface $orders,
        private readonly UpdateOrderStatusAction $updateStatusAction,
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
