<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 13 unit: Mata, Gigi, Penyakit Dalam, Jantung, Bedah, KIA, Anak,
        //          Rehab Medik, Saraf, Paru, Kulit, THT, Jiwa
        Schema::create('unit_pemeriksaan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_unit', 10)->unique();
            $table->string('nama_unit');
            $table->string('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_pemeriksaan');
    }
};
