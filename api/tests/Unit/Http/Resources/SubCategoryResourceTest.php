<?php

use App\Domain\Catalog\Models\SubCategory;
use App\Http\Resources\SubCategoryResource;
use Illuminate\Http\Request;

it('includes the image in the api resource payload', function (): void {
    $subcategory = new SubCategory([
        'id' => 1,
        'category_id' => 2,
        'tenant_id' => 3,
        'product_id' => 4,
        'slug' => 'test-subcategory',
        'name' => ['en' => 'Test Subcategory'],
        'image' => 'subcategories/test.jpg',
    ]);

    $payload = (new SubCategoryResource($subcategory))->resolve(new Request());

    expect($payload['image'])->toBe('subcategories/test.jpg');
    expect($payload['image_url'])->toContain('subcategories/test.jpg');
});
