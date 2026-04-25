<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_verification_token', 64)->nullable(); // ← 64 bukan 6
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('otp_expired_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_verification_token',
                'email_verified_at',
                'otp_expired_at',
            ]);
        });
    }
};
