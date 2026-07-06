<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Notifications;

use App\Domain\Auth\Models\User;
use App\Domain\Notifications\Actions\RegisterDeviceAction;
use App\Domain\Notifications\Actions\UnregisterDeviceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notifications\RegisterDeviceRequest;
use App\Http\Requests\Api\V1\Notifications\UnregisterDeviceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeviceController extends Controller
{
    public function __construct(
        private readonly RegisterDeviceAction $registerDeviceAction,
        private readonly UnregisterDeviceAction $unregisterDeviceAction,
    ) {
    }

    public function store(RegisterDeviceRequest $request): Response
    {
        $this->registerDeviceAction->execute(
            $this->user($request),
            $request->validated('token'),
            $request->validated('platform'),
        );

        return response()->noContent();
    }

    public function destroy(UnregisterDeviceRequest $request): Response
    {
        $this->unregisterDeviceAction->execute(
            $this->user($request),
            $request->validated('token'),
        );

        return response()->noContent();
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
