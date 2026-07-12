<?php

declare(strict_types=1);

use App\Domain\Banners\Models\Banner;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;

it('creates a banner with no deep-link', function (): void {
    $response = $this->postJson('/api/admin/v1/banners', [
        'image_url' => 'https://cdn.test/hero.jpg',
        'link_type' => 'none',
    ], adminHeaders());

    $response->assertStatus(201);
    $response->assertJsonPath('data.link_type', 'none');
    $response->assertJsonPath('data.link_value', null);
});

it('creates a banner linking to a live category', function (): void {
    $slug = Category::query()->create([
        'slug' => 'shoes', 'name' => ['en' => 'Shoes', 'ar' => 'أحذية'], 'sort_order' => 0,
    ])->slug;

    $this->postJson('/api/admin/v1/banners', [
        'image_url' => 'https://cdn.test/hero.jpg',
        'link_type' => 'category',
        'link_value' => $slug,
    ], adminHeaders())->assertStatus(201)->assertJsonPath('data.link_value', $slug);
});

it('rejects a banner linking to a missing category (§7.3)', function (): void {
    $this->postJson('/api/admin/v1/banners', [
        'image_url' => 'https://cdn.test/hero.jpg',
        'link_type' => 'category',
        'link_value' => 'ghost-category',
    ], adminHeaders())->assertStatus(422);
});

it('creates a banner linking to a live product', function (): void {
    $product = Product::factory()->create();

    $this->postJson('/api/admin/v1/banners', [
        'image_url' => 'https://cdn.test/hero.jpg',
        'link_type' => 'product',
        'link_value' => (string) $product->id,
    ], adminHeaders())->assertStatus(201);
});

it('rejects a banner linking to a missing product (§7.3)', function (): void {
    $this->postJson('/api/admin/v1/banners', [
        'image_url' => 'https://cdn.test/hero.jpg',
        'link_type' => 'product',
        'link_value' => 'no-such-product',
    ], adminHeaders())->assertStatus(422);
});

it('requires a value when the link type is not none', function (): void {
    $this->postJson('/api/admin/v1/banners', [
        'image_url' => 'https://cdn.test/hero.jpg',
        'link_type' => 'category',
    ], adminHeaders())->assertStatus(422);
});

it('accepts a url deep-link and validates its format', function (): void {
    $this->postJson('/api/admin/v1/banners', [
        'image_url' => 'https://cdn.test/hero.jpg',
        'link_type' => 'url',
        'link_value' => 'not-a-url',
    ], adminHeaders())->assertStatus(422);

    $this->postJson('/api/admin/v1/banners', [
        'image_url' => 'https://cdn.test/hero.jpg',
        'link_type' => 'url',
        'link_value' => 'https://promo.test/sale',
    ], adminHeaders())->assertStatus(201);
});

it('re-validates the link on a partial update', function (): void {
    $banner = Banner::query()->create([
        'image_url' => 'https://cdn.test/a.jpg', 'link_type' => 'none', 'sort_order' => 0, 'is_active' => true,
    ]);

    $this->patchJson("/api/admin/v1/banners/{$banner->id}", [
        'link_type' => 'category',
        'link_value' => 'ghost',
    ], adminHeaders())->assertStatus(422);
});

it('deletes a banner', function (): void {
    $banner = Banner::query()->create([
        'image_url' => 'https://cdn.test/a.jpg', 'link_type' => 'none', 'sort_order' => 0, 'is_active' => true,
    ]);

    $this->deleteJson("/api/admin/v1/banners/{$banner->id}", [], adminHeaders())->assertNoContent();
    $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
});
