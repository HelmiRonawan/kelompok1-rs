<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitPemeriksaan extends Model
{
    protected $table = 'unit_pemeriksaan';

    protected $fillable = ['kode_unit', 'nama_unit', 'deskripsi', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function antrian()
    {
        return $this->hasMany(Antrian::class, 'unit_id');
    }

    public function pendaftaran()
    {
        return $this->hasMany(Pendaftaran::class, 'unit_id');
    }
}
