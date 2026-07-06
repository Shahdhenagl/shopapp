<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            // Null user_id = broadcast to every user within the tenant.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->json('message');
            // Read state for user-targeted rows; broadcast rows track reads in
            // the notification_reads table instead.
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'created_at']);
        });

        Schema::create('notification_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('notification_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->unsignedInteger('position')->default(0);
        });

        Schema::create('notification_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('notification_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');

            $table->unique(['user_id', 'notification_id']);
        });

        Schema::create('notification_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->string('platform');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_devices');
        Schema::dropIfExists('notification_reads');
        Schema::dropIfExists('notification_images');
        Schema::dropIfExists('notifications');
    }
};
