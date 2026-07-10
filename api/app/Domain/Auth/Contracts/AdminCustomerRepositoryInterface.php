<?php

declare(strict_types=1);

namespace App\Domain\Auth\Contracts;

use App\Domain\Auth\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminCustomerRepositoryInterface
{
    /**
     * App users in the current tenant, newest first, optionally filtered by a
     * name/email search term and/or account status, with order counts attached.
     */
    public function paginate(?string $search, ?string $status, int $perPage): LengthAwarePaginator;

    public function find(string $id): ?User;

    public function updateStatus(User $user, string $status): User;
}
