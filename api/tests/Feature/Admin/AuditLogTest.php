<?php

declare(strict_types=1);

use App\Domain\Admin\Models\AuditLog;
use App\Domain\Cart\Models\PromoCode;

// Guards the AuditLog::UPDATED_AT regression: a typed `const string = null`
// fatally broke every audit-logged mutation. These assert the trail is written
// with before/after JSON and that the model instantiates without a fatal.

it('records a create with a null before and a populated after', function (): void {
    $admin = makeAdmin();

    $this->postJson('/api/admin/v1/promos', [
        'code' => 'AUDIT', 'type' => 'percent', 'fraction' => 0.1,
    ], adminHeaders($admin))->assertStatus(201);

    $log = AuditLog::query()->where('action', 'promo.created')->first();

    expect($log)->not->toBeNull();
    expect($log->admin_user_id)->toBe($admin->getKey());
    expect($log->before)->toBeNull();
    expect($log->after)->toBeArray();
});

it('records an update with both before and after snapshots', function (): void {
    $promo = PromoCode::query()->create(['code' => 'UPD', 'type' => 'percent', 'fraction' => 0.1]);

    $this->patchJson("/api/admin/v1/promos/{$promo->id}", ['fraction' => 0.3], adminHeaders())
        ->assertStatus(200);

    $log = AuditLog::query()->where('action', 'promo.updated')->first();

    expect($log->before)->toBeArray();
    expect($log->after)->toBeArray();
});

it('has no updated_at timestamp column managed on audit rows', function (): void {
    // Instantiating the model would fatal if UPDATED_AT were a typed-null const.
    $log = new AuditLog;

    expect($log::UPDATED_AT)->toBeNull();
});
