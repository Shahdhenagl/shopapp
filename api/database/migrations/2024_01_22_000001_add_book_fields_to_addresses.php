<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table): void {
            $table->string('label')->nullable()->after('user_id');
            $table->string('phone')->nullable()->after('branch');
            $table->boolean('is_default')->default(false)->after('phone');

            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'is_default']);
            $table->dropColumn(['label', 'phone', 'is_default']);
        });
    }
};
