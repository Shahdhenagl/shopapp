<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            // The promo applied to the cart, persisted so checkout can recompute
            // the discounted total server-side (the client sends no promo).
            $table->string('promo_code')->nullable();
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('size');
            $table->unsignedBigInteger('color_value');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['cart_id', 'product_id', 'size', 'color_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
