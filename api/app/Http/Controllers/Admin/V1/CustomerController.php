<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Admin\Support\AuditLogger;
use App\Domain\Auth\Contracts\AdminCustomerRepositoryInterface;
use App\Domain\Auth\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Customers\UpdateCustomerStatusRequest;
use App\Http\Resources\Admin\AdminCustomerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    public function __construct(
        private readonly AdminCustomerRepositoryInterface $customers,
        private readonly AuditLogger $audit,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $paginator = $this->customers->paginate(
            $request->query('search'),
            $request->query('status'),
            (int) $request->query('per_page', 20),
        );

        return AdminCustomerResource::collection($paginator);
    }

    public function show(string $id): AdminCustomerResource
    {
        return AdminCustomerResource::make($this->findOrFail($id));
    }

    public function update(UpdateCustomerStatusRequest $request, string $id): AdminCustomerResource
    {
        $user = $this->findOrFail($id);
        $before = ['status' => $user->status];

        $user = $this->customers->updateStatus($user, $request->validated('status'));

        $this->audit->log($this->actor($request), 'customer.status_updated', $user, $before, ['status' => $user->status]);

        return AdminCustomerResource::make($user);
    }

    private function findOrFail(string $id): User
    {
        $user = $this->customers->find($id);

        abort_if($user === null, 404, __('api.not_found'));

        return $user;
    }

    private function actor(Request $request): AdminUser
    {
        /** @var AdminUser $admin */
        $admin = $request->user();

        return $admin;
    }
}
