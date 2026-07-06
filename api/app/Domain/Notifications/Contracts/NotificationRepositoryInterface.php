<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Contracts;

use App\Domain\Auth\Models\User;
use App\Domain\Notifications\Models\Notification;
use Illuminate\Database\Eloquent\Collection;

interface NotificationRepositoryInterface
{
    /**
     * The user's visible notifications (user-targeted + tenant broadcasts),
     * newest first, each carrying a computed per-user is_read attribute.
     *
     * @return Collection<int, Notification>
     */
    public function feedForUser(User $user): Collection;

    /**
     * Mark every visible notification read for the user, then return the
     * refreshed feed.
     *
     * @return Collection<int, Notification>
     */
    public function markAllRead(User $user): Collection;

    /**
     * Number of the user's visible notifications that are still unread.
     */
    public function unreadCount(User $user): int;

    /**
     * Register or update (upsert by token) a device push token for the user.
     */
    public function registerDevice(User $user, string $token, string $platform): void;

    /**
     * Remove a previously registered device token for the user.
     */
    public function unregisterDevice(User $user, string $token): void;
}
