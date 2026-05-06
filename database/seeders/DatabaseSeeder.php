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
            ['kode_unit' => 'E',  'nama_unit' => 'Bedah'],
            ['kode_unit' => 'F',    'nama_unit' => 'KIA'],
            ['kode_unit' => 'G',   'nama_unit' => 'Anak'],
            ['kode_unit' => 'H',  'nama_unit' => 'Rehab Medik'],
            ['kode_unit' => 'I',  'nama_unit' => 'Saraf'],
            ['kode_unit' => 'J',   'nama_unit' => 'Paru'],
            ['kode_unit' => 'K',  'nama_unit' => 'Kulit'],
            ['kode_unit' => 'L',    'nama_unit' => 'THT'],
            ['kode_unit' => 'M',   'nama_unit' => 'Jiwa'],
        ];

        foreach ($units as $unit) {
            UnitPemeriksaan::firstOrCreate(['kode_unit' => $unit['kode_unit']], $unit);
        }

        // ── User Default ───────────────────────────────────────────────────
        $users = [
            // Kelompok 1
            ['email' => 'freejpgtopng1@gmail.com',      'password' => 'admin12345',  'nama' => 'Super Administrator',  'roles' => ['superadmin']],
            ['email' => 'adminperawat@rs.id',     'password' => 'perawat123',  'nama' => 'Admin Perawat',        'roles' => ['admin_perawat']],
            // Kelompok 2
            ['email' => 'perawat01@rs.id',        'password' => 'perawat123',  'nama' => 'Perawat Satu',         'roles' => ['perawat']],
            ['email' => 'dokter01@rs.id',         'password' => 'dokter123',   'nama' => 'Dr. Budi Santoso',     'roles' => ['dokter']],
            // Kelompok 3
            ['email' => 'kasir01@rs.id',          'password' => 'kasir123',    'nama' => 'Kasir Satu',           'roles' => ['kasir']],
            ['email' => 'adminkasir@rs.id',       'password' => 'kasir123',    'nama' => 'Admin Kasir',          'roles' => ['admin_kasir']],
            // Kelompok 4
            ['email' => 'apoteker01@rs.id',       'password' => 'apotik123',   'nama' => 'Apoteker Satu',        'roles' => ['apoteker']],
            ['email' => 'adminapotik@rs.id',      'password' => 'apotik123',   'nama' => 'Admin Apotik',         'roles' => ['admin_apotik']],
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
        echo "  freejpgtopng1@gmail.com      → admin12345  (superadmin)\n";
        echo "  adminperawat@rs.id    → perawat123  (admin_perawat)\n";
        echo "  perawat01@rs.id       → perawat123  (perawat - kelompok 2)\n";
        echo "  dokter01@rs.id        → dokter123   (dokter - kelompok 2)\n";
        echo "  kasir01@rs.id         → kasir123    (kasir - kelompok 3)\n";
        echo "  adminkasir@rs.id      → kasir123    (admin_kasir - kelompok 3)\n";
        echo "  apoteker01@rs.id      → apotik123   (apoteker - kelompok 4)\n";
        echo "  adminapotik@rs.id     → apotik123   (admin_apotik - kelompok 4)\n";
        echo "─────────────────────────────────────────────────────\n";
    }
}
