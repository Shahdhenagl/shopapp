<?php

declare(strict_types=1);

namespace App\Domain\Profile\Actions;

use App\Domain\Auth\Models\User;

final readonly class UpdateProfileAction
{
    /**
     * Update the authenticated user's editable profile fields. Only the keys
     * present in $attributes are touched (partial update).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(User $user, array $attributes): User
    {
        $user->fill(array_intersect_key($attributes, array_flip([
            'name',
            'phone',
            'avatar_url',
        ])));

        $user->save();

        return $user;
    }
}
