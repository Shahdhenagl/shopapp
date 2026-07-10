<?php

declare(strict_types=1);

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Cart\Models\PromoCode;

it('lists promo codes for the tenant', function (): void {
    PromoCode::query()->create(['code' => 'SAVE10', 'type' => 'percent', 'fraction' => 0.1]);

    $response = $this->getJson('/api/admin/v1/promos', adminHeaders());

    $response->assertStatus(200);
    $response->assertJsonPath('data.0.code', 'SAVE10');
});

it('creates a promo, upper-casing the code', function (): void {
    $response = $this->postJson('/api/admin/v1/promos', [
        'code' => 'welcome15',
        'type' => 'percent',
        'fraction' => 0.15,
    ], adminHeaders());

    $response->assertStatus(201);
    $response->assertJsonPath('data.code', 'WELCOME15');
    $this->assertDatabaseHas('promo_codes', ['code' => 'WELCOME15']);
});

it('rejects a duplicate promo code', function (): void {
    PromoCode::query()->create(['code' => 'DUP', 'type' => 'percent', 'fraction' => 0.1]);

    $this->postJson('/api/admin/v1/promos', [
        'code' => 'dup',
        'type' => 'percent',
        'fraction' => 0.2,
    ], adminHeaders())->assertStatus(422);
});

it('rejects a fraction above 1', function (): void {
    $this->postJson('/api/admin/v1/promos', [
        'code' => 'BAD', 'type' => 'percent', 'fraction' => 1.5,
    ], adminHeaders())->assertStatus(422);
});

it('updates a promo', function (): void {
    $promo = PromoCode::query()->create(['code' => 'EDIT', 'type' => 'percent', 'fraction' => 0.1]);

    $this->patchJson("/api/admin/v1/promos/{$promo->id}", ['fraction' => 0.25], adminHeaders())
        ->assertStatus(200)
        ->assertJsonPath('data.fraction', 0.25);
});

it('toggles active via a partial patch', function (): void {
    $promo = PromoCode::query()->create(['code' => 'TOG', 'type' => 'percent', 'fraction' => 0.1, 'active' => true]);

    $this->patchJson("/api/admin/v1/promos/{$promo->id}", ['active' => false], adminHeaders())
        ->assertStatus(200)
        ->assertJsonPath('data.active', false);
});

it('deletes a promo', function (): void {
    $promo = PromoCode::query()->create(['code' => 'DEL', 'type' => 'percent', 'fraction' => 0.1]);

    $this->deleteJson("/api/admin/v1/promos/{$promo->id}", [], adminHeaders())->assertNoContent();
    $this->assertDatabaseMissing('promo_codes', ['id' => $promo->id]);
});

it('forbids staff from creating a promo', function (): void {
    $staff = makeAdmin(AdminUser::ROLE_STAFF);

    $this->postJson('/api/admin/v1/promos', [
        'code' => 'X', 'type' => 'percent', 'fraction' => 0.1,
    ], adminHeaders($staff))->assertStatus(403);
});
