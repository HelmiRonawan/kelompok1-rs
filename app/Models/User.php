<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'username',
        'email',
        'password',
        'nama_lengkap',
        'is_active',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── JWT Interface ──────────────────────────────────────────
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'roles' => $this->roles->pluck('nama_role')->toArray(),
            'nama'  => $this->nama_lengkap,
        ];
    }

    // ── Relasi ────────────────────────────────────────────────
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function pasien()
    {
        return $this->hasOne(Pasien::class);
    }

    // ── Helper Cek Role ───────────────────────────────────────
    public function hasRole(string $role): bool
    {
        return $this->roles->contains('nama_role', $role);
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->whereIn('nama_role', $roles)->isNotEmpty();
    }
}
