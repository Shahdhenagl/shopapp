<?php

declare(strict_types=1);

namespace App\Domain\Auth\Contracts;

use App\Domain\Auth\Models\User;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    /**
     * Find a user by their linked social identity (provider + provider id).
     */
    public function findByProvider(string $provider, string $providerId): ?User;

    public function findById(int|string $id): ?User;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): User;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, array $attributes): User;

    public function updatePassword(User $user, string $hashedPassword): User;

    /**
     * Stamp the account as email-verified (sign-up OTP confirmed).
     */
    public function markEmailVerified(User $user): User;
}
