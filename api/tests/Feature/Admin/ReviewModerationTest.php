<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductReview;

function seedReview(Product $product, int $rating, string $status = 'approved'): ProductReview
{
    return ProductReview::query()->create([
        'product_id' => $product->id,
        'author_name' => 'Tester',
        'rating' => $rating,
        'comment' => 'A comment',
        'status' => $status,
    ]);
}

it('lists reviews with a moderation status', function (): void {
    $product = Product::factory()->create();
    seedReview($product, 5);

    $response = $this->getJson('/api/admin/v1/reviews', adminHeaders());

    $response->assertStatus(200);
    $response->assertJsonStructure(['data' => [['id', 'product_id', 'rating', 'status']]]);
});

it('filters reviews by status', function (): void {
    $product = Product::factory()->create();
    seedReview($product, 5, 'approved');
    seedReview($product, 1, 'pending');

    $response = $this->getJson('/api/admin/v1/reviews?status=pending', adminHeaders());

    expect(collect($response->json('data'))->pluck('status')->unique()->all())->toBe(['pending']);
});

it('recomputes the product rating when a review is hidden', function (): void {
    $product = Product::factory()->create(['rating' => 0]);
    $keep = seedReview($product, 5, 'approved');
    $drop = seedReview($product, 1, 'approved');
    // Two approved reviews average (5 + 1) / 2 = 3.
    $product->update(['rating' => 3]);

    $this->patchJson("/api/admin/v1/reviews/{$drop->id}", ['status' => 'hidden'], adminHeaders())
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'hidden');

    // Only the 5-star review remains approved.
    expect((float) $product->fresh()->rating)->toBe(5.0);
});

it('recomputes to zero when the last approved review is deleted', function (): void {
    $product = Product::factory()->create(['rating' => 4]);
    $only = seedReview($product, 4, 'approved');

    $this->deleteJson("/api/admin/v1/reviews/{$only->id}", [], adminHeaders())->assertNoContent();

    expect((float) $product->fresh()->rating)->toBe(0.0);
});

it('forbids staff from moderating reviews', function (): void {
    $product = Product::factory()->create();
    $r = seedReview($product, 3);

    $this->patchJson("/api/admin/v1/reviews/{$r->id}", ['status' => 'hidden'],
        adminHeaders(makeAdmin(\App\Domain\Admin\Models\AdminUser::ROLE_STAFF)))
        ->assertStatus(403);
});
