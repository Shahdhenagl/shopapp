<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Auth\Contracts\AdminCustomerRepositoryInterface;
use App\Domain\Auth\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class EloquentAdminCustomerRepository implements AdminCustomerRepositoryInterface
{
    public function paginate(?string $search, ?string $status, int $perPage): LengthAwarePaginator
    {
        return User::query()
            ->withCount('orders')
            ->when($status !== null && $status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== null && $search !== '', function (Builder $query) use ($search): void {
                $term = '%' . $search . '%';
                $query->where(function (Builder $inner) use ($term): void {
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function find(string $id): ?User
    {
        return User::query()->withCount('orders')->find($id);
    }

    public function updateStatus(User $user, string $status): User
    {
        $user->status = $status;
        $user->save();

        return $user;
    }
}
