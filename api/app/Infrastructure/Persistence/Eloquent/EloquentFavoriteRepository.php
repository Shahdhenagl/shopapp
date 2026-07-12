<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Auth\Models\User;
use App\Domain\Favorites\Contracts\FavoriteRepositoryInterface;

final class EloquentFavoriteRepository implements FavoriteRepositoryInterface
{
    public function idsForUser(User $user): array
    {
        // The wire contract keeps all ids as strings, so cast the (now bigint)
        // product ids to string.
        return $user->favorites()
            ->pluck('products.id')
            ->map(static fn ($id): string => (string) $id)
            ->all();
    }

    public function toggle(User $user, string $productId): array
    {
        $user->favorites()->toggle($productId);

        return $this->idsForUser($user);
    }

    public function clear(User $user): void
    {
        $user->favorites()->detach();
    }
}
