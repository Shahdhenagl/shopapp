<?php

declare(strict_types=1);

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Auth\Models\User;

it('logs a dashboard operator in and returns a flat token + admin', function (): void {
    $admin = makeAdmin();

    $response = $this->postJson('/api/admin/v1/auth/login', [
        'email' => $admin->email,
        'password' => 'password',
    ], ['Accept' => 'application/json']);

    $response->assertStatus(200);
    $response->assertJsonStructure(['token', 'admin' => ['id', 'name', 'email', 'role']]);
    expect($response->json('admin.email'))->toBe($admin->email);
});

it('rejects wrong admin credentials', function (): void {
    $admin = makeAdmin();

    $this->postJson('/api/admin/v1/auth/login', [
        'email' => $admin->email,
        'password' => 'wrong-password',
    ], ['Accept' => 'application/json'])->assertStatus(401);
});

it('returns the authenticated admin from /me', function (): void {
    $admin = makeAdmin();

    $response = $this->getJson('/api/admin/v1/me', adminHeaders($admin));

    $response->assertStatus(200);
    $response->assertJsonPath('data.email', $admin->email);
});

it('blocks an unauthenticated request to a protected admin route', function (): void {
    $this->getJson('/api/admin/v1/settings', ['Accept' => 'application/json'])
        ->assertStatus(401);
});

it('rejects an admin token that lacks the admin ability', function (): void {
    $admin = makeAdmin();

    $this->getJson('/api/admin/v1/settings', adminHeaders($admin, abilities: []))
        ->assertStatus(403);
});

it('does not let an app-user token reach the admin API', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('app', ['*'])->plainTextToken;

    $this->getJson('/api/admin/v1/settings', [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '.$token,
    ])->assertStatus(401);
});

it('logs out by revoking the current token', function (): void {
    $admin = makeAdmin();
    $headers = adminHeaders($admin);

    $this->postJson('/api/admin/v1/auth/logout', [], $headers)->assertNoContent();

    // The revoked token can no longer authenticate.
    $this->getJson('/api/admin/v1/me', $headers)->assertStatus(401);
});

it('forbids a staff operator from a tenant-admin-only write', function (): void {
    $staff = makeAdmin(AdminUser::ROLE_STAFF);

    $this->patchJson('/api/admin/v1/settings', ['app_name' => 'Nope'], adminHeaders($staff))
        ->assertStatus(403);
});
