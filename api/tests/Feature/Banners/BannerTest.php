<?php

declare(strict_types=1);

use App\Domain\Banners\Models\Banner;

it('returns an empty array when there are no banners', function (): void {
    $response = $this->getJson('/api/v1/home/banners', ['Accept' => 'application/json']);

    $response->assertStatus(200);
    expect($response->json('data'))->toBe([]);
});

it('returns active in-window banners ordered by sort_order', function (): void {
    Banner::create([
        'image_url' => 'https://cdn/b2.webp',
        'title' => 'Second',
        'link_type' => Banner::LINK_NONE,
        'sort_order' => 1,
        'is_active' => true,
    ]);
    Banner::create([
        'image_url' => 'https://cdn/b1.webp',
        'title' => 'First',
        'link_type' => Banner::LINK_CATEGORY,
        'link_value' => 'tshirt',
        'sort_order' => 0,
        'is_active' => true,
    ]);

    $response = $this->getJson('/api/v1/home/banners', ['Accept' => 'application/json']);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            ['id', 'image_url', 'title', 'subtitle', 'cta_text', 'link_type', 'link_value'],
        ],
    ]);
    $response->assertJsonPath('data.0.title', 'First');
    $response->assertJsonPath('data.1.title', 'Second');
});

it('excludes inactive and out-of-window banners', function (): void {
    Banner::create([
        'image_url' => 'https://cdn/inactive.webp',
        'title' => 'Inactive',
        'is_active' => false,
    ]);
    Banner::create([
        'image_url' => 'https://cdn/expired.webp',
        'title' => 'Expired',
        'is_active' => true,
        'ends_at' => now()->subDay(),
    ]);

    $response = $this->getJson('/api/v1/home/banners', ['Accept' => 'application/json']);

    $response->assertStatus(200);
    expect($response->json('data'))->toBe([]);
});
