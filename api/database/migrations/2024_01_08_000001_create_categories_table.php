<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            // Surrogate UUID key: the wire "id" the client sees is the per-tenant
            // `slug` (tshirt, pants, …), exposed by CategoryResource. A surrogate
            // PK lets two tenants both own a "tshirt" category.
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('label_key');
            $table->string('icon_key');
            $table->integer('sort_order')->default(0);
            $table->json('name');
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
