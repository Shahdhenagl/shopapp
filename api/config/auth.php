<?php

declare(strict_types=1);

return [
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    // Lifetime (in days) of a rotating refresh token before it expires.
    'refresh_ttl_days' => (int) env('AUTH_REFRESH_TTL_DAYS', 30),

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // Sanctum guard for token-authenticated app clients (User tokens).
        'sanctum' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],

        // Sanctum guard for dashboard operators (AdminUser tokens). A distinct
        // provider means an app token can't authenticate an admin route and
        // vice-versa (Sanctum validates the tokenable against the provider model).
        'admin' => [
            'driver' => 'sanctum',
            'provider' => 'admin_users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Domain\Auth\Models\User::class),
        ],

        // Dashboard operators (separate model; authenticated via Sanctum tokens
        // that carry the `admin` ability).
        'admin_users' => [
            'driver' => 'eloquent',
            'model' => App\Domain\Admin\Models\AdminUser::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];
