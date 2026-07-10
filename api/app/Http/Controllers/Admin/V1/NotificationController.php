<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Notifications\Actions\Admin\BroadcastNotificationAction;
use App\Domain\Notifications\Contracts\AdminNotificationRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Notifications\BroadcastNotificationRequest;
use App\Http\Resources\Admin\AdminNotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function __construct(
        private readonly AdminNotificationRepositoryInterface $notifications,
        private readonly BroadcastNotificationAction $broadcastAction,
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        return AdminNotificationResource::collection($this->notifications->all());
    }

    public function store(BroadcastNotificationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $target = $data['target'] ?? 'all';
        $userId = $target === 'user' ? (int) $data['user_id'] : null;

        $notification = $this->broadcastAction->execute(
            $this->actor($request),
            $data['type'],
            $this->normalizeMessage($data['message']),
            $userId,
            $data['images'] ?? [],
        );

        return AdminNotificationResource::make($notification)->response()->setStatusCode(201);
    }

    /**
     * A scalar mirrors to both locales; a map passes through with gaps filled.
     *
     * @param  string|array<string, string>  $message
     * @return array<string, string>
     */
    private function normalizeMessage(string|array $message): array
    {
        if (is_array($message)) {
            $en = (string) ($message['en'] ?? $message['ar'] ?? '');
            $ar = (string) ($message['ar'] ?? $message['en'] ?? '');

            return ['en' => $en, 'ar' => $ar];
        }

        return ['en' => $message, 'ar' => $message];
    }

    private function actor(Request $request): AdminUser
    {
        /** @var AdminUser $admin */
        $admin = $request->user();

        return $admin;
    }
}
