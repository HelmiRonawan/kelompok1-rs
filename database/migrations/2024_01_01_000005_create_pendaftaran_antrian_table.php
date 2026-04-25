<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel pendaftaran kunjungan
        Schema::create('pendaftaran', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pendaftaran')->unique(); // e.g. PEND-20240101-0001
            $table->foreignId('pasien_id')->constrained('pasien')->onDelete('cascade');
            $table->foreignId('unit_id')->constrained('unit_pemeriksaan')->onDelete('cascade');
            $table->foreignId('didaftarkan_oleh')->constrained('users')->onDelete('cascade'); // perawat/pasien sendiri
            $table->date('tanggal_kunjungan');
            $table->enum('jenis_pendaftaran', ['langsung', 'online'])->default('langsung');
            $table->enum('status', [
                'terdaftar',      // baru daftar
                'dipanggil',      // antrian dipanggil ke unit
                'sedang_periksa', // sedang di dokter
                'selesai_periksa',// selesai dari dokter → ke kasir
                'selesai'         // seluruh proses selesai
            ])->default('terdaftar');
            $table->text('keluhan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabel antrian per unit per hari
        Schema::create('antrian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftaran_id')->constrained('pendaftaran')->onDelete('cascade');
            $table->foreignId('unit_id')->constrained('unit_pemeriksaan')->onDelete('cascade');
            $table->date('tanggal');
            $table->integer('nomor_antrian');
            $table->string('kode_antrian', 20); // e.g. MATA-001
            $table->enum('status', [
                'menunggu',
                'dipanggil',
                'dilayani',
                'selesai',
                'tidak_hadir'
            ])->default('menunggu');
            $table->timestamp('waktu_panggil')->nullable();
            $table->foreignId('dipanggil_oleh')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Satu pendaftaran = satu antrian per unit
            $table->unique(['pendaftaran_id', 'unit_id']);
            // Nomor antrian unik per unit per hari
            $table->unique(['unit_id', 'tanggal', 'nomor_antrian']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('antrian');
        Schema::dropIfExists('pendaftaran');
    }
};
