<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            // Stores the category slug (the per-tenant identifier). No DB FK to
            // categories because that key is now a surrogate UUID; integrity is
            // enforced at the application layer within the tenant.
            $table->string('category_id');
            $table->decimal('price', 10, 2);
            $table->string('currency')->default('EGP');
            $table->decimal('rating', 2, 1)->default(0);
            $table->boolean('is_newest')->default(false);
            $table->json('name');
            $table->json('style');
            $table->json('description');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'category_id', 'is_newest']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
