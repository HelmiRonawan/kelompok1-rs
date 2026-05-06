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
        'email',
        'password',
        'is_active',
        'email_verification_token',
        'email_verified_at',
        'otp_expired_at',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'is_active'         => 'boolean',
        'email_verified_at' => 'datetime',
        'otp_expired_at'    => 'datetime',
    ];

    // ── JWT ───────────────────────────────────────────────────────────────
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'roles' => $this->roles->pluck('nama_role')->toArray(),
            'email'  => $this->email,
        ];
    }

    // ── Relasi ────────────────────────────────────────────────────────────
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function pasien()
    {
        return $this->hasOne(Pasien::class);
    }

    // ── Helper ────────────────────────────────────────────────────────────
    public function hasRole(string $role): bool
    {
        return $this->roles->contains('nama_role', $role);
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->whereIn('nama_role', $roles)->isNotEmpty();
    }
}
