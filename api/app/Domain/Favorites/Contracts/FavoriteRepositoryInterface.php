<?php

declare(strict_types=1);

namespace App\Domain\Favorites\Contracts;

use App\Domain\Auth\Models\User;

interface FavoriteRepositoryInterface
{
    /**
     * @return array<int, string> Ordered product ids favorited by the user.
     */
    public function idsForUser(User $user): array;

    /**
     * Toggle a product in the user's favorites; returns the updated id list.
     *
     * @return array<int, string>
     */
    public function toggle(User $user, string $productId): array;

    public function clear(User $user): void;
}
