<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');          // hashed token
            $table->string('username')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('expired_at')->nullable(); // expired 15 menit
            $table->boolean('used')->default(false);     // token hanya bisa dipakai sekali
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
