<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The SaaS account / store. Every domain table carries tenant_id and is
        // isolated at the data layer via the BelongsToTenant global scope.
        Schema::create('tenants', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // White-label surface read by GET /settings/app. One row per tenant.
        Schema::create('tenant_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('app_name')->nullable();
            $table->string('currency')->default('EGP');
            $table->string('brand_primary')->nullable();
            $table->string('brand_on_primary')->nullable();
            $table->string('brand_accent')->nullable();
            $table->json('flags')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
        Schema::dropIfExists('tenants');
    }
};
