<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pasien extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pasien';

    protected $fillable = [
        'user_id',
        'nomor_rm',
        'nomor_kartu',
        'nik',
        'nama_lengkap',
        'jenis_kelamin',
        'tanggal_lahir',
        'tempat_lahir',
        'alamat',
        'no_telepon',
        'golongan_darah',
        'jenis_pasien',
        'no_bpjs',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    // ── Relasi ────────────────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pendaftaran()
    {
        return $this->hasMany(Pendaftaran::class);
    }

    // ── Helper: Generate Nomor RM ─────────────────────────────
    public static function generateNomorRM(): string
    {
        $last = static::withTrashed()->latest('id')->first();
        $nextNum = $last ? ($last->id + 1) : 1;
        return 'RM-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
    }
}
