<?php

declare(strict_types=1);

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Catalog\Models\Category;
use App\Domain\Tenancy\Models\TenantSettings;

it('shows the current tenant settings with grouped brand + flags', function (): void {
    $response = $this->getJson('/api/admin/v1/settings', adminHeaders());

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            'app_name', 'currency', 'storefront_mode', 'logo_url', 'shipping_fee',
            'brand' => ['primary', 'on_primary', 'accent'],
            'flags' => ['card_payment', 'cash_payment', 'promo_codes', 'favorites'],
        ],
    ]);
});

it('updates settings and persists brand + flags + shipping', function (): void {
    $response = $this->patchJson('/api/admin/v1/settings', [
        'app_name' => 'MODIST Live',
        'currency' => 'USD',
        'shipping_fee' => 50,
        'brand_primary' => '#FF0000',
        'brand_on_primary' => '#FFFFFF',
        'brand_accent' => '#778DA9',
        'flags' => ['card_payment' => false, 'cash_payment' => true, 'promo_codes' => true, 'favorites' => true],
    ], adminHeaders());

    $response->assertStatus(200);
    $response->assertJsonPath('data.app_name', 'MODIST Live');
    $response->assertJsonPath('data.currency', 'USD');
    $response->assertJsonPath('data.shipping_fee', 50);
    $response->assertJsonPath('data.brand.primary', '#FF0000');
    $response->assertJsonPath('data.flags.card_payment', false);

    $this->assertDatabaseHas('tenant_settings', [
        'app_name' => 'MODIST Live',
        'brand_primary' => '#FF0000',
    ]);
});

// Regression: a typed `const string UPDATED_AT = null` on AuditLog used to fatal
// on every audit-logged write. A successful settings save proves it's gone.
it('writes an audit row when settings are updated (audit regression guard)', function (): void {
    $admin = makeAdmin();

    $this->patchJson('/api/admin/v1/settings', ['app_name' => 'Audited'], adminHeaders($admin))
        ->assertStatus(200);

    $this->assertDatabaseHas('audit_logs', [
        'admin_user_id' => $admin->getKey(),
        'action' => 'settings.updated',
        'entity_type' => 'TenantSettings',
    ]);
});

it('rejects switching to multi_department with no category tree (§7.2)', function (): void {
    $this->patchJson('/api/admin/v1/settings', [
        'storefront_mode' => 'multi_department',
    ], adminHeaders())->assertStatus(422);
});

it('allows multi_department once a nested category exists', function (): void {
    $parent = Category::query()->create([
        'slug' => 'clothing', 'name' => ['en' => 'Clothing', 'ar' => 'ملابس'], 'sort_order' => 0,
    ]);
    Category::query()->create([
        'slug' => 'tees', 'parent_id' => $parent->id,
        'name' => ['en' => 'Tees', 'ar' => 'تيشيرت'], 'sort_order' => 0,
    ]);

    $this->patchJson('/api/admin/v1/settings', [
        'storefront_mode' => 'multi_department',
    ], adminHeaders())->assertStatus(200)
        ->assertJsonPath('data.storefront_mode', 'multi_department');
});

it('allows forcing multi_department past the tree guard', function (): void {
    $this->patchJson('/api/admin/v1/settings', [
        'storefront_mode' => 'multi_department',
        'force' => true,
    ], adminHeaders())->assertStatus(200);
});

it('rejects an invalid brand colour', function (): void {
    $this->patchJson('/api/admin/v1/settings', [
        'brand_primary' => 'red',
    ], adminHeaders())->assertStatus(422);
});

// --- Dashboard-curated Home rails -------------------------------------------

it('exposes home-rail defaults on the admin settings resource', function (): void {
    $this->getJson('/api/admin/v1/settings', adminHeaders())
        ->assertStatus(200)
        ->assertJsonStructure(['data' => ['home_rail_categories', 'max_home_rails', 'home_rail_item_count']])
        ->assertJsonPath('data.max_home_rails', 8)
        ->assertJsonPath('data.home_rail_item_count', 5);
});

it('persists ordered home-rail categories and drops unknown ids', function (): void {
    Category::query()->create(['slug' => 'phones', 'name' => ['en' => 'Phones', 'ar' => 'هواتف'], 'sort_order' => 0]);
    Category::query()->create(['slug' => 'laptops', 'name' => ['en' => 'Laptops', 'ar' => 'لابتوب'], 'sort_order' => 1]);

    $this->patchJson('/api/admin/v1/settings', [
        // 'ghost' is not a real category → dropped; 'phones' duplicated → de-duped.
        'home_rail_categories' => ['laptops', 'ghost', 'phones', 'phones'],
        'max_home_rails' => 6,
        'home_rail_item_count' => 4,
    ], adminHeaders())
        ->assertStatus(200)
        ->assertJsonPath('data.home_rail_categories', ['laptops', 'phones'])
        ->assertJsonPath('data.max_home_rails', 6)
        ->assertJsonPath('data.home_rail_item_count', 4);
});

it('clamps out-of-range home-rail caps (§6)', function (): void {
    $this->patchJson('/api/admin/v1/settings', ['max_home_rails' => 999], adminHeaders())
        ->assertStatus(422);
    $this->patchJson('/api/admin/v1/settings', ['home_rail_item_count' => 0], adminHeaders())
        ->assertStatus(422);
});

it('returns home-rail config on the public GET /settings/app', function (): void {
    Category::query()->create(['slug' => 'phones', 'name' => ['en' => 'Phones', 'ar' => 'هواتف'], 'sort_order' => 0]);

    $this->patchJson('/api/admin/v1/settings', [
        'home_rail_categories' => ['phones'],
        'max_home_rails' => 3,
        'home_rail_item_count' => 7,
    ], adminHeaders())->assertStatus(200);

    $this->getJson('/api/v1/settings/app', ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->assertJsonPath('data.home_rail_categories', ['phones'])
        ->assertJsonPath('data.max_home_rails', 3)
        ->assertJsonPath('data.home_rail_item_count', 7);
});

it('defaults home-rail fields on the public endpoint when unset', function (): void {
    $this->getJson('/api/v1/settings/app', ['Accept' => 'application/json'])
        ->assertStatus(200)
        ->assertJsonPath('data.home_rail_categories', [])
        ->assertJsonPath('data.max_home_rails', 8)
        ->assertJsonPath('data.home_rail_item_count', 5);
});

it('scopes settings writes to the operator tenant', function (): void {
    $other = makeOtherTenant();
    $otherAdmin = makeAdmin(AdminUser::ROLE_TENANT_ADMIN, $other);

    $this->patchJson('/api/admin/v1/settings', ['app_name' => 'Other Store Name'], adminHeaders($otherAdmin))
        ->assertStatus(200);

    // The default tenant's settings are untouched.
    expect(TenantSettings::query()->where('tenant_id', $other->id)->value('app_name'))
        ->toBe('Other Store Name');
});
