<?php

declare(strict_types=1);

namespace App\Domain\Profile\Actions;

use App\Domain\Auth\Models\User;

final class ShowProfileAction
{
    public function execute(User $user): User
    {
        return $user;
    }
}
