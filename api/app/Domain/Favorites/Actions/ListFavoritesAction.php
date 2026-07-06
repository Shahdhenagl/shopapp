<?php

declare(strict_types=1);

namespace App\Domain\Favorites\Actions;

use App\Domain\Auth\Models\User;
use App\Domain\Favorites\Contracts\FavoriteRepositoryInterface;

final readonly class ListFavoritesAction
{
    public function __construct(
        private FavoriteRepositoryInterface $favorites,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function execute(User $user): array
    {
        return $this->favorites->idsForUser($user);
    }
}
