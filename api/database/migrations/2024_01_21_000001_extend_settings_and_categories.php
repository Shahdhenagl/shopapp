<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Store-owner-tunable settings surfaced by GET /settings/app.
        Schema::table('tenant_settings', function (Blueprint $table): void {
            $table->string('storefront_mode')->default('single')->after('currency'); // single | multi_department
            $table->string('logo_url')->nullable()->after('storefront_mode');
            $table->decimal('shipping_fee', 10, 2)->default(0)->after('logo_url');
        });

        // Categories become a tree: a nullable self-referencing parent + a browse
        // tile image + soft deletes (so hiding never orphans historical orders).
        Schema::table('categories', function (Blueprint $table): void {
            $table->unsignedBigInteger('parent_id')->nullable()->after('slug');
            $table->string('image_url')->nullable()->after('icon_key');
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();
            $table->index(['tenant_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'image_url', 'deleted_at']);
        });

        Schema::table('tenant_settings', function (Blueprint $table): void {
            $table->dropColumn(['storefront_mode', 'logo_url', 'shipping_fee']);
        });
    }
};
