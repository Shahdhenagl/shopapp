<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            // Where the sale came from: the app checkout or the in-store POS.
            $table->string('channel')->default('app')->after('tenant_id'); // app | pos
            // Walk-in details — a POS sale may have no account behind it.
            $table->string('customer_name')->nullable()->after('user_id');
            $table->string('customer_phone')->nullable()->after('customer_name');

            $table->index(['tenant_id', 'channel']);
        });

        // A walk-in POS sale has no user; app checkout still always sets one.
        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'channel']);
            $table->dropColumn(['channel', 'customer_name', 'customer_phone']);
        });
    }
};
