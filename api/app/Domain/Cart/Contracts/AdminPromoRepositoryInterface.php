<?php

declare(strict_types=1);

namespace App\Domain\Cart\Contracts;

use App\Domain\Cart\Models\PromoCode;
use Illuminate\Database\Eloquent\Collection;

interface AdminPromoRepositoryInterface
{
    /**
     * All promo codes for the current tenant, newest first.
     *
     * @return Collection<int, PromoCode>
     */
    public function all(): Collection;

    public function find(string $id): ?PromoCode;

    /**
     * Whether a code already exists in the tenant, optionally excluding one row
     * (used when editing so a promo doesn't collide with itself).
     */
    public function codeExists(string $code, ?string $exceptId = null): bool;

    /**
     * @param  array<string, mixed>  $attrs
     */
    public function create(array $attrs): PromoCode;

    /**
     * @param  array<string, mixed>  $attrs
     */
    public function update(PromoCode $promo, array $attrs): PromoCode;

    public function delete(PromoCode $promo): void;
}
