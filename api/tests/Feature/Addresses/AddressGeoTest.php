<?php

declare(strict_types=1);

use App\Domain\Auth\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    Sanctum::actingAs(User::factory()->create());
});

it('stores and returns a map-picked geo point on an address', function (): void {
    $response = $this->postJson('/api/v1/addresses', [
        'address' => '12 Nile St',
        'city' => 'Cairo',
        'area' => 'Zamalek',
        'latitude' => 30.0626,
        'longitude' => 31.2197,
    ], ['Accept' => 'application/json']);

    $response->assertStatus(200);
    $created = collect($response->json('data'))->firstWhere('address', '12 Nile St');

    expect((float) $created['latitude'])->toBe(30.0626);
    expect((float) $created['longitude'])->toBe(31.2197);
});

it('accepts a hand-typed address with no pin (geo null)', function (): void {
    $response = $this->postJson('/api/v1/addresses', [
        'address' => 'No Pin St',
        'city' => 'Giza',
    ], ['Accept' => 'application/json']);

    $response->assertStatus(200);
    $created = collect($response->json('data'))->firstWhere('address', 'No Pin St');

    expect($created['latitude'])->toBeNull();
    expect($created['longitude'])->toBeNull();
});

it('rejects an out-of-range latitude', function (): void {
    $this->postJson('/api/v1/addresses', [
        'address' => 'Bad', 'city' => 'X', 'latitude' => 200, 'longitude' => 10,
    ], ['Accept' => 'application/json'])->assertStatus(422);
});
