<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pendaftaran — hapus: didaftarkan_oleh, jenis_pendaftaran, keluhan
        Schema::create('pendaftaran', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pendaftaran')->unique();
            $table->foreignId('pasien_id')->constrained('pasien')->onDelete('cascade');
            $table->foreignId('unit_id')->constrained('unit_pemeriksaan')->onDelete('cascade');
            $table->date('tanggal_kunjungan');
            $table->timestamps();
            $table->softDeletes();
        });

        // Antrian — hapus: dipanggil_oleh
        Schema::create('antrian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftaran_id')->constrained('pendaftaran')->onDelete('cascade');
            $table->foreignId('unit_id')->constrained('unit_pemeriksaan')->onDelete('cascade');
            $table->date('tanggal');
            $table->integer('nomor_antrian');
            $table->string('kode_antrian', 20);
            $table->enum('status', [
                'menunggu',
                'dipanggil',
                'pemeriksaan_awal',
                'sedang_diperiksa',
                'selesai_pemeriksaan',
                'lunas',
                'obat_diserahkan',
                'tidak_hadir',
            ])->default('menunggu');
            $table->timestamp('waktu_panggil')->nullable();
            $table->timestamps();

            $table->unique(['pendaftaran_id', 'unit_id']);
            $table->unique(['unit_id', 'tanggal', 'nomor_antrian']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('antrian');
        Schema::dropIfExists('pendaftaran');
    }
};
