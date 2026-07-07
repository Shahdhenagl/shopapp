<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Addresses\Contracts\AddressRepositoryInterface;
use App\Domain\Addresses\Exceptions\AddressNotFoundException;
use App\Domain\Auth\Models\User;
use App\Domain\Checkout\Models\Address;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentAddressRepository implements AddressRepositoryInterface
{
    /**
     * @return Collection<int, Address>
     */
    public function listForUser(User $user): Collection
    {
        return $this->bookQuery($user)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    public function create(User $user, array $attrs): void
    {
        DB::transaction(function () use ($user, $attrs): void {
            $makeDefault = (bool) ($attrs['is_default'] ?? false)
                || ! $this->bookQuery($user)->exists();

            if ($makeDefault) {
                $this->clearDefault($user);
            }

            $attrs['user_id'] = $user->id;
            $attrs['is_default'] = $makeDefault;

            Address::query()->create($attrs);
        });
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    public function update(User $user, string $id, array $attrs): void
    {
        DB::transaction(function () use ($user, $id, $attrs): void {
            $address = $this->findOrFail($user, $id);

            if ((bool) ($attrs['is_default'] ?? false)) {
                $this->clearDefault($user);
            }

            $address->fill($attrs)->save();
        });
    }

    public function delete(User $user, string $id): void
    {
        DB::transaction(function () use ($user, $id): void {
            $address = $this->findOrFail($user, $id);
            $wasDefault = $address->is_default;

            $address->delete();

            if ($wasDefault) {
                $newest = $this->bookQuery($user)
                    ->orderByDesc('created_at')
                    ->first();

                if ($newest !== null) {
                    $newest->is_default = true;
                    $newest->save();
                }
            }
        });
    }

    public function setDefault(User $user, string $id): void
    {
        DB::transaction(function () use ($user, $id): void {
            $address = $this->findOrFail($user, $id);

            $this->clearDefault($user);

            $address->is_default = true;
            $address->save();
        });
    }

    /**
     * @return Builder<Address>
     */
    private function bookQuery(User $user): Builder
    {
        return Address::query()
            ->where('user_id', $user->id)
            ->whereNull('order_id');
    }

    private function findOrFail(User $user, string $id): Address
    {
        $address = $this->bookQuery($user)->find($id);

        if ($address === null) {
            throw new AddressNotFoundException;
        }

        return $address;
    }

    private function clearDefault(User $user): void
    {
        $this->bookQuery($user)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }
}
