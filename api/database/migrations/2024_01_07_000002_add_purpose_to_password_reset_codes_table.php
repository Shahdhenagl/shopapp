<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('password_reset_codes', function (Blueprint $table): void {
            // The same one-time-code table now serves two flows keyed by email:
            // password reset (§4 /auth/password/*) and the sign-up email
            // verification OTP (§4 /auth/register/verify). The purpose scopes
            // every issue/verify/consume so the two never collide.
            $table->string('purpose')->default('password_reset')->after('email');
            $table->index(['email', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::table('password_reset_codes', function (Blueprint $table): void {
            $table->dropIndex(['email', 'purpose']);
            $table->dropColumn('purpose');
        });
    }
};
