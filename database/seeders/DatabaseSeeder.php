<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\UnitPemeriksaan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Roles ──────────────────────────────────────────────────────────
        $roles = [
            ['nama_role' => 'superadmin',    'deskripsi' => 'Administrator sistem'],
            ['nama_role' => 'admin_perawat', 'deskripsi' => 'Pendaftaran & antrian langsung'],
            ['nama_role' => 'pasien',        'deskripsi' => 'Daftar online & lihat antrian'],
            ['nama_role' => 'perawat',       'deskripsi' => 'Pemanggilan & assessment'],
            ['nama_role' => 'dokter',        'deskripsi' => 'Pemeriksaan & E-Resep'],
            ['nama_role' => 'kasir',         'deskripsi' => 'Pembayaran & antrian kasir'],
            ['nama_role' => 'admin_kasir',   'deskripsi' => 'Kelola harga'],
            ['nama_role' => 'apoteker',      'deskripsi' => 'Penyerahan obat'],
            ['nama_role' => 'admin_apotik',  'deskripsi' => 'Kelola stok & harga obat'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['nama_role' => $role['nama_role']], $role);
        }

        // ── Unit Pemeriksaan ───────────────────────────────────────────────
        $units = [
            ['kode_unit' => 'A',   'nama_unit' => 'Mata'],
            ['kode_unit' => 'B',   'nama_unit' => 'Gigi'],
            ['kode_unit' => 'C', 'nama_unit' => 'Penyakit Dalam'],
            ['kode_unit' => 'D',   'nama_unit' => 'Jantung'],
        ];

        foreach ($units as $unit) {
            UnitPemeriksaan::firstOrCreate(['kode_unit' => $unit['kode_unit']], $unit);
        }

        // ── User Default ───────────────────────────────────────────────────
        $users = [
            // 1
            ['email' => 'freejpgtopng1@gmail.com',      'password' => 'admin12345',  'roles' => ['superadmin']],
            ['email' => 'derinayu@viamedika.id',     'password' => 'adperawat123',  'roles' => ['admin_perawat']],
            // 2
            ['email' => 'mijie@viamedika.id',        'password' => 'perawat123',  'roles' => ['perawat']],
            ['email' => 'janny@viamedika.id',         'password' => 'dokter123',   'roles' => ['dokter']],
            // 3
            ['email' => 'kasir01@viamedika.id',          'password' => 'kasir123',    'roles' => ['kasir']],
            ['email' => 'adminkasir@viamedika.id',       'password' => 'adkasir123',    'roles' => ['admin_kasir']],
            // 4
            ['email' => 'apoteker01@viamedika.id',       'password' => 'apotik123',   'roles' => ['apoteker']],
            ['email' => 'adminapotik@viamedika.id',      'password' => 'adapotik123',   'roles' => ['admin_apotik']],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'password'          => Hash::make($data['password']), 
                    'is_active'         => true,
                    'email_verified_at' => now(),
                ]
            );
            $roleIds = Role::whereIn('nama_role', $data['roles'])->pluck('id');
            $user->roles()->syncWithoutDetaching($roleIds);
        }

        echo "\nSeeder selesai!\n";
        echo "─────────────────────────────────────────────────────\n";
        echo "LOGIN PAKAI EMAIL:\n";
        echo "  1. freejpgtopng1@gmail.com      → admin12345  \n";
        echo "  2. derinayu@viamedika.id    → adperawat123  \n";
        echo "  3. mijie@viamedika.id       → perawat123  \n";
        echo "  4. janny@viamedika.id        → dokter123   \n";
        echo "  5. kasir01@viamedika.id         → kasir123    \n";
        echo "  6. adminkasir@viamedika.id      → adkasir123    \n";
        echo "  7. apoteker01@viamedika.id      → apotik123   \n";
        echo "  8. adminapotik@viamedika.id     → adapotik123   \n";
        echo "─────────────────────────────────────────────────────\n";
    }
}
