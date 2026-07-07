<?php

declare(strict_types=1);

namespace App\Domain\Addresses\Actions;

use App\Domain\Addresses\Contracts\AddressRepositoryInterface;
use App\Domain\Auth\Models\User;
use App\Domain\Checkout\Models\Address;
use Illuminate\Database\Eloquent\Collection;

final readonly class DeleteAddressAction
{
    public function __construct(
        private AddressRepositoryInterface $addresses,
    ) {
    }

    /**
     * @return Collection<int, Address>
     */
    public function execute(User $user, string $id): Collection
    {
        $this->addresses->delete($user, $id);

        return $this->addresses->listForUser($user);
    }
}
