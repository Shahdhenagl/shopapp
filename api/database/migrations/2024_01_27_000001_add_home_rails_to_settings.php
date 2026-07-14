<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dashboard-curated Home rails: the tenant picks an ordered set of
        // categories to promote on the app Home screen (GET /settings/app).
        // The row holds only category ids + caps — no product data is joined
        // here; the client resolves the actual products from the catalog it
        // already loaded.
        Schema::table('tenant_settings', function (Blueprint $table): void {
            $table->json('home_rail_categories')->nullable()->after('flags'); // ordered category ids (slugs)
            $table->unsignedInteger('max_home_rails')->default(8)->after('home_rail_categories');
            $table->unsignedInteger('home_rail_item_count')->default(5)->after('max_home_rails');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_settings', function (Blueprint $table): void {
            $table->dropColumn(['home_rail_categories', 'max_home_rails', 'home_rail_item_count']);
        });
    }
};
