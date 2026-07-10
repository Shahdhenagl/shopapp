<?php

declare(strict_types=1);

use App\Domain\Auth\Models\User;

it('broadcasts a notification to all users (null user_id)', function (): void {
    User::factory()->count(2)->create();

    $response = $this->postJson('/api/admin/v1/notifications', [
        'type' => 'promo',
        'message' => ['en' => 'Big sale!', 'ar' => 'تخفيضات كبيرة!'],
        'target' => 'all',
    ], adminHeaders());

    $response->assertStatus(201);
    $response->assertJsonPath('data.is_broadcast', true);
    $this->assertDatabaseHas('notifications', ['type' => 'promo', 'user_id' => null]);
});

it('accepts a plain-string message (mirrored to both locales)', function (): void {
    User::factory()->create();

    $this->postJson('/api/admin/v1/notifications', [
        'type' => 'general',
        'message' => 'Hello everyone',
        'target' => 'all',
    ], adminHeaders())->assertStatus(201);
});

it('targets a single user', function (): void {
    $user = User::factory()->create();

    $response = $this->postJson('/api/admin/v1/notifications', [
        'type' => 'order',
        'message' => ['en' => 'Your order shipped', 'ar' => 'تم شحن طلبك'],
        'target' => 'user',
        'user_id' => $user->id,
    ], adminHeaders());

    $response->assertStatus(201);
    $response->assertJsonPath('data.is_broadcast', false);
    $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'type' => 'order']);
});

it('rejects a broadcast when the tenant has no users', function (): void {
    $response = $this->postJson('/api/admin/v1/notifications', [
        'type' => 'general',
        'message' => 'Nobody here',
        'target' => 'all',
    ], adminHeaders());

    $response->assertStatus(422);
});

it('rejects an invalid notification type', function (): void {
    User::factory()->create();

    $this->postJson('/api/admin/v1/notifications', [
        'type' => 'spam',
        'message' => 'x',
        'target' => 'all',
    ], adminHeaders())->assertStatus(422);
});

it('requires user_id when targeting a single user', function (): void {
    $this->postJson('/api/admin/v1/notifications', [
        'type' => 'general',
        'message' => 'x',
        'target' => 'user',
    ], adminHeaders())->assertStatus(422);
});
