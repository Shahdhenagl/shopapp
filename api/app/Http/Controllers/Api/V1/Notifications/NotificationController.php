<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Notifications;

use App\Domain\Auth\Models\User;
use App\Domain\Notifications\Actions\CountUnreadNotificationsAction;
use App\Domain\Notifications\Actions\ListNotificationsAction;
use App\Domain\Notifications\Actions\MarkNotificationsReadAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly ListNotificationsAction $listNotificationsAction,
        private readonly MarkNotificationsReadAction $markNotificationsReadAction,
        private readonly CountUnreadNotificationsAction $countUnreadNotificationsAction,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $feed = $this->listNotificationsAction->execute($this->user($request));

        return response()->json(['data' => NotificationResource::collection($feed)]);
    }

    public function read(Request $request): JsonResponse
    {
        $feed = $this->markNotificationsReadAction->execute($this->user($request));

        return response()->json(['data' => NotificationResource::collection($feed)]);
    }

    public function count(Request $request): JsonResponse
    {
        $unread = $this->countUnreadNotificationsAction->execute($this->user($request));

        return response()->json(['data' => ['unread' => $unread]]);
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
