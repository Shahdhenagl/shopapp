<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Products gain a publish state and product-level stock. Existing rows
        // stay visible/in-stock-agnostic: `active` + a stock that never blocks.
        Schema::table('products', function (Blueprint $table): void {
            $table->string('status')->default('active')->after('is_newest'); // active | hidden
            $table->unsignedInteger('stock')->default(0)->after('status');

            $table->index(['tenant_id', 'status']);
        });

        // Reviews become moderatable. Rows written before moderation existed are
        // already public, so the column defaults to `approved` — flipping the
        // default to `pending` would silently hide the whole existing feed.
        Schema::table('product_reviews', function (Blueprint $table): void {
            $table->string('status')->default('approved')->after('comment'); // pending | approved | hidden

            $table->index(['tenant_id', 'status']);
        });

        // App users can be suspended from the dashboard (login is then refused).
        Schema::table('users', function (Blueprint $table): void {
            $table->string('status')->default('active')->after('avatar_url'); // active | suspended
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('status');
        });

        Schema::table('product_reviews', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropColumn('status');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropColumn(['status', 'stock']);
        });
    }
};
