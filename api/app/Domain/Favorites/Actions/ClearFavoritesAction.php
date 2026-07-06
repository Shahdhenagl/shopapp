<?php

declare(strict_types=1);

namespace App\Domain\Favorites\Actions;

use App\Domain\Auth\Models\User;
use App\Domain\Favorites\Contracts\FavoriteRepositoryInterface;

final readonly class ClearFavoritesAction
{
    public function __construct(
        private FavoriteRepositoryInterface $favorites,
    ) {
    }

    /**
     * @return array<int, string> Always an empty list after clearing.
     */
    public function execute(User $user): array
    {
        $this->favorites->clear($user);

        return [];
    }
}
