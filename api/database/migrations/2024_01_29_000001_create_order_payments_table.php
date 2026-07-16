<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per tender: a sale may be collected across several methods
        // (e.g. 200 cash + 150 wallet). orders.payment_method keeps a summary
        // ('split' when there is more than one) so existing reads still work.
        Schema::create('order_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->string('method'); // cash | instapay | wallet | creditCard | deferred
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
