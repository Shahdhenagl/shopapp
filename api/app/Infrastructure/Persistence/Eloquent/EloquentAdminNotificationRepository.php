<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Auth\Models\User;
use App\Domain\Notifications\Contracts\AdminNotificationRepositoryInterface;
use App\Domain\Notifications\Models\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentAdminNotificationRepository implements AdminNotificationRepositoryInterface
{
    public function all(): Collection
    {
        return Notification::query()
            ->with('images')
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(string $type, array $message, ?int $userId, array $images): Notification
    {
        return DB::transaction(function () use ($type, $message, $userId, $images): Notification {
            /** @var Notification $notification */
            $notification = Notification::query()->create([
                'type' => $type,
                'message' => $message,
                'user_id' => $userId,
            ]);

            foreach (array_values($images) as $position => $url) {
                $notification->images()->create(['url' => $url, 'position' => $position]);
            }

            return $notification->load('images');
        });
    }

    public function recipientIds(?int $userId): array
    {
        if ($userId !== null) {
            return [$userId];
        }

        return User::query()->pluck('id')->all();
    }
}
