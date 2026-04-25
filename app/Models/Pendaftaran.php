<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pendaftaran extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pendaftaran';

    protected $fillable = [
        'nomor_pendaftaran',
        'pasien_id',
        'unit_id',
        'didaftarkan_oleh',
        'tanggal_kunjungan',
        'jenis_pendaftaran',
        'status',
        'keluhan',
    ];

    protected $casts = [
        'tanggal_kunjungan' => 'date',
    ];

    // ── Relasi ────────────────────────────────────────────────
    public function pasien()
    {
        return $this->belongsTo(Pasien::class);
    }

    public function unit()
    {
        return $this->belongsTo(UnitPemeriksaan::class, 'unit_id');
    }

    public function pendaftar()
    {
        return $this->belongsTo(User::class, 'didaftarkan_oleh');
    }

    public function antrian()
    {
        return $this->hasOne(Antrian::class);
    }

    // ── Helper: Generate Nomor Pendaftaran ────────────────────
    public static function generateNomor(string $tanggal): string
    {
        $prefix = 'PEND-' . str_replace('-', '', $tanggal) . '-';
        $count = static::where('nomor_pendaftaran', 'like', $prefix . '%')->count();
        return $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
