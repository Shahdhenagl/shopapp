<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Addresses;

use App\Domain\Addresses\Actions\CreateAddressAction;
use App\Domain\Addresses\Actions\DeleteAddressAction;
use App\Domain\Addresses\Actions\ListAddressesAction;
use App\Domain\Addresses\Actions\SetDefaultAddressAction;
use App\Domain\Addresses\Actions\UpdateAddressAction;
use App\Domain\Auth\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Addresses\StoreAddressRequest;
use App\Http\Requests\Api\V1\Addresses\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __construct(
        private readonly ListAddressesAction $listAddressesAction,
        private readonly CreateAddressAction $createAddressAction,
        private readonly UpdateAddressAction $updateAddressAction,
        private readonly DeleteAddressAction $deleteAddressAction,
        private readonly SetDefaultAddressAction $setDefaultAddressAction,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $list = $this->listAddressesAction->execute($this->user($request));

        return response()->json(['data' => AddressResource::collection($list)]);
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $list = $this->createAddressAction->execute($this->user($request), $request->validated());

        return response()->json(['data' => AddressResource::collection($list)]);
    }

    public function update(UpdateAddressRequest $request, string $id): JsonResponse
    {
        $list = $this->updateAddressAction->execute($this->user($request), $id, $request->validated());

        return response()->json(['data' => AddressResource::collection($list)]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $list = $this->deleteAddressAction->execute($this->user($request), $id);

        return response()->json(['data' => AddressResource::collection($list)]);
    }

    public function default(Request $request, string $id): JsonResponse
    {
        $list = $this->setDefaultAddressAction->execute($this->user($request), $id);

        return response()->json(['data' => AddressResource::collection($list)]);
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
