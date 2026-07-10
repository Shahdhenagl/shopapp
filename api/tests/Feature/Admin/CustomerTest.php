<?php

declare(strict_types=1);

use App\Domain\Auth\Models\User;

it('lists app customers with order counts', function (): void {
    User::factory()->create(['name' => 'Sara Ahmed']);

    $response = $this->getJson('/api/admin/v1/customers', adminHeaders());

    $response->assertStatus(200);
    $response->assertJsonStructure(['data' => [['id', 'name', 'email', 'status', 'orders_count']]]);
});

it('searches customers by name/email/phone', function (): void {
    User::factory()->create(['name' => 'Layla Hassan', 'email' => 'layla@test.dev']);
    User::factory()->create(['name' => 'Omar Khaled', 'email' => 'omar@test.dev']);

    $response = $this->getJson('/api/admin/v1/customers?search=layla', adminHeaders());

    $names = collect($response->json('data'))->pluck('name');
    expect($names)->toContain('Layla Hassan');
    expect($names)->not->toContain('Omar Khaled');
});

it('suspends a customer', function (): void {
    $user = User::factory()->create(['status' => 'active']);

    $this->patchJson("/api/admin/v1/customers/{$user->id}", ['status' => 'suspended'], adminHeaders())
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'suspended');

    $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'suspended']);
});

it('refuses login for a suspended customer (§3.10)', function (): void {
    $user = User::factory()->create(['status' => 'suspended']);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ], ['Accept' => 'application/json'])->assertStatus(403);
});

it('lets a reactivated customer log in again', function (): void {
    $user = User::factory()->create(['status' => 'suspended']);

    $this->patchJson("/api/admin/v1/customers/{$user->id}", ['status' => 'active'], adminHeaders())
        ->assertStatus(200);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ], ['Accept' => 'application/json'])->assertStatus(200);
});

it('forbids staff from suspending a customer', function (): void {
    $user = User::factory()->create();

    $this->patchJson("/api/admin/v1/customers/{$user->id}", ['status' => 'suspended'],
        adminHeaders(makeAdmin(\App\Domain\Admin\Models\AdminUser::ROLE_STAFF)))
        ->assertStatus(403);
});
