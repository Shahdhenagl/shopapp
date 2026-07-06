<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('image_url');
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('cta_text')->nullable();
            // none | category | product | url — where a tap goes (§6.9).
            $table->string('link_type')->default('none');
            $table->string('link_value')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            // Optional scheduling window; null = always in-window.
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
