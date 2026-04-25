<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel blacklist token (untuk logout / revoke)
        Schema::create('token_blacklist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('jti', 64)->unique(); // JWT ID (jti claim)
            $table->timestamp('expired_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_blacklist');
    }
};
