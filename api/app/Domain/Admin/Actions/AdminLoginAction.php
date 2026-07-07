<?php

declare(strict_types=1);

namespace App\Domain\Admin\Actions;

use App\Domain\Admin\Exceptions\InvalidAdminCredentialsException;
use App\Domain\Admin\Models\AdminUser;
use Illuminate\Support\Facades\Hash;

final readonly class AdminLoginAction
{
    /**
     * Authenticates a dashboard operator and issues an admin-ability token.
     *
     * AdminUser is not tenant-scoped, so the lookup is global (email is unique
     * across all admins). The token carries the `admin` ability that the admin
     * route group requires.
     *
     * @return array{token: string, admin: AdminUser}
     */
    public function execute(string $email, string $password): array
    {
        $admin = AdminUser::query()->where('email', $email)->first();

        if ($admin === null || ! Hash::check($password, $admin->password)) {
            throw new InvalidAdminCredentialsException;
        }

        $token = $admin->createToken('dashboard', ['admin'])->plainTextToken;

        return ['token' => $token, 'admin' => $admin];
    }
}
