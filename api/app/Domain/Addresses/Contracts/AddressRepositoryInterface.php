<?php

declare(strict_types=1);

namespace App\Domain\Addresses\Contracts;

use App\Domain\Auth\Models\User;
use App\Domain\Checkout\Models\Address;
use Illuminate\Database\Eloquent\Collection;

interface AddressRepositoryInterface
{
    /**
     * The user's delivery address book (order snapshots excluded),
     * ordered default-first then newest.
     *
     * @return Collection<int, Address>
     */
    public function listForUser(User $user): Collection;

    /**
     * @param  array<string, mixed>  $attrs
     */
    public function create(User $user, array $attrs): void;

    /**
     * @param  array<string, mixed>  $attrs
     */
    public function update(User $user, string $id, array $attrs): void;

    public function delete(User $user, string $id): void;

    public function setDefault(User $user, string $id): void;
}
