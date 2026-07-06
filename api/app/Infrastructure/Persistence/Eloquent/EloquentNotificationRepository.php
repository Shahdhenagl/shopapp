<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Auth\Models\User;
use App\Domain\Notifications\Contracts\NotificationRepositoryInterface;
use App\Domain\Notifications\Models\Notification;
use App\Domain\Notifications\Models\NotificationDevice;
use App\Domain\Notifications\Models\NotificationRead;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function feedForUser(User $user): Collection
    {
        /** @var Collection<int, Notification> $notifications */
        $notifications = $this->visibleQuery($user)
            ->with('images')
            ->orderByDesc('created_at')
            ->get();

        return $this->withComputedReadState($user, $notifications);
    }

    public function markAllRead(User $user): Collection
    {
        DB::transaction(function () use ($user): void {
            $now = Carbon::now();

            // (a) User-targeted rows: stamp read_at directly.
            Notification::query()
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => $now]);

            // (b) Broadcast rows: insert a per-user read row where missing.
            $alreadyRead = NotificationRead::query()
                ->where('user_id', $user->id)
                ->pluck('notification_id')
                ->all();

            $broadcastIds = Notification::query()
                ->whereNull('user_id')
                ->whereNotIn('id', $alreadyRead)
                ->pluck('id');

            foreach ($broadcastIds as $notificationId) {
                NotificationRead::query()->create([
                    'user_id' => $user->id,
                    'notification_id' => $notificationId,
                    'read_at' => $now,
                ]);
            }
        });

        return $this->feedForUser($user);
    }

    public function unreadCount(User $user): int
    {
        return $this->withComputedReadState($user, $this->visibleQuery($user)->get())
            ->filter(static fn (Notification $n): bool => $n->getAttribute('is_read_computed') === false)
            ->count();
    }

    public function registerDevice(User $user, string $token, string $platform): void
    {
        NotificationDevice::query()->updateOrCreate(
            ['token' => $token],
            ['user_id' => $user->id, 'platform' => $platform],
        );
    }

    public function unregisterDevice(User $user, string $token): void
    {
        NotificationDevice::query()
            ->where('user_id', $user->id)
            ->where('token', $token)
            ->delete();
    }

    /**
     * Rows visible to the user: targeted to them OR broadcast to the tenant.
     * The tenant scope is already applied by the model's global scope.
     *
     * @return Builder<Notification>
     */
    private function visibleQuery(User $user): Builder
    {
        return Notification::query()
            ->where(function (Builder $inner) use ($user): void {
                $inner->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            });
    }

    /**
     * Set a transient `is_read_computed` attribute on each notification using a
     * single lookup of the user's broadcast read ids.
     *
     * @param  Collection<int, Notification>  $notifications
     * @return Collection<int, Notification>
     */
    private function withComputedReadState(User $user, Collection $notifications): Collection
    {
        $readBroadcastIds = NotificationRead::query()
            ->where('user_id', $user->id)
            ->pluck('notification_id')
            ->flip();

        return $notifications->each(function (Notification $notification) use ($user, $readBroadcastIds): void {
            if ($notification->user_id === $user->id) {
                $isRead = $notification->read_at !== null;
            } else {
                $isRead = $readBroadcastIds->has($notification->id);
            }

            $notification->setAttribute('is_read_computed', $isRead);
        });
    }
}
