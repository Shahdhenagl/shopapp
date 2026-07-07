<?php

declare(strict_types=1);

namespace App\Domain\Addresses\Actions;

use App\Domain\Addresses\Contracts\AddressRepositoryInterface;
use App\Domain\Auth\Models\User;
use App\Domain\Checkout\Models\Address;
use Illuminate\Database\Eloquent\Collection;

final readonly class CreateAddressAction
{
    public function __construct(
        private AddressRepositoryInterface $addresses,
    ) {
    }

    /**
     * @param  array<string, mixed>  $attrs
     * @return Collection<int, Address>
     */
    public function execute(User $user, array $attrs): Collection
    {
        $this->addresses->create($user, $attrs);

        return $this->addresses->listForUser($user);
    }
}
