<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Social sign-in (Facebook / Google). A social account carries a provider + the
 * provider's stable user id and has no password, so password becomes nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('provider')->nullable()->after('password');      // facebook | google
            $table->string('provider_id')->nullable()->after('provider');   // the provider's user id
            $table->string('password')->nullable()->change();               // social users have none

            // A provider identity is unique within a tenant.
            $table->unique(['tenant_id', 'provider', 'provider_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['tenant_id', 'provider', 'provider_id']);
            $table->dropColumn(['provider', 'provider_id']);
            // Leave password nullable on rollback — pre-existing rows keep theirs.
        });
    }
};
