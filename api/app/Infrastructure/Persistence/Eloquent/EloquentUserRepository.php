<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Auth\Contracts\UserRepositoryInterface;
use App\Domain\Auth\Models\User;

final class EloquentUserRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function findByProvider(string $provider, string $providerId): ?User
    {
        return User::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();
    }

    public function findById(int|string $id): ?User
    {
        return User::query()->find($id);
    }

    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function update(User $user, array $attributes): User
    {
        $user->fill($attributes);
        $user->save();

        return $user;
    }

    public function updatePassword(User $user, string $hashedPassword): User
    {
        $user->password = $hashedPassword;
        $user->save();

        return $user;
    }

    public function markEmailVerified(User $user): User
    {
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }
}
