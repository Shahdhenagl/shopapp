<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A dashboard-created category legitimately has no shipped l10n `label_key`
 * (that's for app-bundled keys) and may lean on an image instead of an
 * `icon_key`. Both were NOT NULL, so creating such a category 500'd. Relax them
 * to nullable to match how the admin editor actually produces categories.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->string('label_key')->nullable()->change();
            $table->string('icon_key')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->string('label_key')->nullable(false)->change();
            $table->string('icon_key')->nullable(false)->change();
        });
    }
};
