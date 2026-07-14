<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Profile;

use App\Domain\Auth\Models\User;
use App\Domain\Profile\Actions\ListOrdersAction;
use App\Domain\Profile\Actions\ShowProfileAction;
use App\Domain\Profile\Actions\UpdateAvatarAction;
use App\Domain\Profile\Actions\UpdateProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdateAvatarRequest;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ShowProfileAction $showProfileAction,
        private readonly UpdateProfileAction $updateProfileAction,
        private readonly UpdateAvatarAction $updateAvatarAction,
        private readonly ListOrdersAction $listOrdersAction,
    ) {
    }

    public function show(Request $request): UserResource
    {
        return UserResource::make($this->showProfileAction->execute($this->user($request)));
    }

    public function update(UpdateProfileRequest $request): UserResource
    {
        return UserResource::make(
            $this->updateProfileAction->execute($this->user($request), $request->validated()),
        );
    }

    public function updateAvatar(UpdateAvatarRequest $request): UserResource
    {
        return UserResource::make(
            $this->updateAvatarAction->execute(
                $this->user($request),
                $request->file('image'),
            ),
        );
    }

    public function orders(Request $request): AnonymousResourceCollection
    {
        return OrderResource::collection($this->listOrdersAction->execute($this->user($request)));
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
