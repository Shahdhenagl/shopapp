<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dashboard operators — separate from app `users`. A null tenant_id marks
        // a super-admin (SaaS owner) who can act across all tenants.
        Schema::create('admin_users', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('tenant-admin'); // super-admin | tenant-admin | staff
            $table->string('two_factor_secret')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('tenant_id');
        });

        // Immutable trail of every mutating admin action (who/what/before/after).
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('admin_user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');                 // e.g. category.created
            $table->string('entity_type')->nullable(); // e.g. Category
            $table->string('entity_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('admin_users');
    }
};
