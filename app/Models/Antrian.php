<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Antrian extends Model
{
    protected $table = 'antrian';

    protected $fillable = [
        'pendaftaran_id',
        'unit_id',
        'tanggal',
        'nomor_antrian',
        'kode_antrian',
        'status',
        'waktu_panggil',
        'dipanggil_oleh',
    ];

    protected $casts = [
        'tanggal'       => 'date',
        'waktu_panggil' => 'datetime',
    ];

    // ── Relasi ────────────────────────────────────────────────
    public function pendaftaran()
    {
        return $this->belongsTo(Pendaftaran::class);
    }

    public function unit()
    {
        return $this->belongsTo(UnitPemeriksaan::class, 'unit_id');
    }

    public function pemanggil()
    {
        return $this->belongsTo(User::class, 'dipanggil_oleh');
    }

    // ── Helper: Nomor Antrian Berikutnya ──────────────────────
    public static function nomorBerikutnya(int $unitId, string $tanggal): int
    {
        $last = static::where('unit_id', $unitId)
            ->where('tanggal', $tanggal)
            ->max('nomor_antrian');
        return ($last ?? 0) + 1;
    }

    public static function generateKode(UnitPemeriksaan $unit, int $nomor): string
    {
        return strtoupper(substr($unit->kode_unit, 0, 4)) . '-' . str_pad($nomor, 3, '0', STR_PAD_LEFT);
    }
}
