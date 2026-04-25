<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel master role
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nama_role')->unique(); // superadmin, perawat, pasien
            $table->string('deskripsi')->nullable();
            $table->timestamps();
        });

        // Pivot: satu user bisa punya banyak role
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
    }
};
