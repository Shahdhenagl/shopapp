<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Cart\Contracts\AdminPromoRepositoryInterface;
use App\Domain\Cart\Models\PromoCode;
use Illuminate\Database\Eloquent\Collection;

final class EloquentAdminPromoRepository implements AdminPromoRepositoryInterface
{
    public function all(): Collection
    {
        return PromoCode::query()
            ->orderByDesc('created_at')
            ->get();
    }

    public function find(string $id): ?PromoCode
    {
        return PromoCode::query()->find($id);
    }

    public function codeExists(string $code, ?string $exceptId = null): bool
    {
        return PromoCode::query()
            ->where('code', strtoupper($code))
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    public function create(array $attrs): PromoCode
    {
        return PromoCode::query()->create($attrs);
    }

    public function update(PromoCode $promo, array $attrs): PromoCode
    {
        $promo->update($attrs);

        return $promo->refresh();
    }

    public function delete(PromoCode $promo): void
    {
        $promo->delete();
    }
}
