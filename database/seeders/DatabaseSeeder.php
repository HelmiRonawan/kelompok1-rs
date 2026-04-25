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
        // ── Roles Semua Kelompok ───────────────────────────────────────────
        $roles = [
            // Kelompok 1 — Auth & Pendaftaran
            ['nama_role' => 'superadmin',   'deskripsi' => 'Administrator sistem, bisa buat user baru'],
            ['nama_role' => 'admin_perawat','deskripsi' => 'Menerima pendaftaran dan memanggil antrian'],
            ['nama_role' => 'pasien',       'deskripsi' => 'Daftar online dan lihat status antrian'],

            // Kelompok 2 — Pemeriksaan
            ['nama_role' => 'perawat',      'deskripsi' => 'Pemanggilan antrian & assessment awal pasien'],
            ['nama_role' => 'dokter',       'deskripsi' => 'Pemeriksaan, diagnosa, dan E-Resep'],

            // Kelompok 3 — Kasir
            ['nama_role' => 'kasir',        'deskripsi' => 'Pemanggilan antrian & penerima pembayaran'],
            ['nama_role' => 'admin_kasir',  'deskripsi' => 'Menentukan harga jasa dan obat'],

            // Kelompok 4 — Apotik
            ['nama_role' => 'apoteker',     'deskripsi' => 'Pemanggilan antrian & penyerahan obat'],
            ['nama_role' => 'admin_apotik', 'deskripsi' => 'Kelola stok, harga beli/jual obat'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['nama_role' => $role['nama_role']], $role);
        }

        // ── Unit Pemeriksaan (13 unit sesuai diagram) ──────────────────────
        $units = [
            ['kode_unit' => 'MATA',   'nama_unit' => 'Mata'],
            ['kode_unit' => 'GIGI',   'nama_unit' => 'Gigi'],
            ['kode_unit' => 'PDALAM', 'nama_unit' => 'Penyakit Dalam'],
            ['kode_unit' => 'JNTG',   'nama_unit' => 'Jantung'],
            ['kode_unit' => 'BEDAH',  'nama_unit' => 'Bedah'],
            ['kode_unit' => 'KIA',    'nama_unit' => 'KIA'],
            ['kode_unit' => 'ANAK',   'nama_unit' => 'Anak'],
            ['kode_unit' => 'REHAB',  'nama_unit' => 'Rehab Medik'],
            ['kode_unit' => 'SARAF',  'nama_unit' => 'Saraf'],
            ['kode_unit' => 'PARU',   'nama_unit' => 'Paru'],
            ['kode_unit' => 'KULIT',  'nama_unit' => 'Kulit'],
            ['kode_unit' => 'THT',    'nama_unit' => 'THT'],
            ['kode_unit' => 'JIWA',   'nama_unit' => 'Jiwa'],
        ];

        foreach ($units as $unit) {
            UnitPemeriksaan::firstOrCreate(['kode_unit' => $unit['kode_unit']], $unit);
        }

        // ── User Default per Kelompok ──────────────────────────────────────

        $defaultUsers = [
            // Kelompok 1
            [
                'username'    => 'superadmin',
                'email'       => 'ahmadhelmi2804@gmail.com',
                'password'    => 'admin12345',
                'nama_lengkap'=> 'Super Administrator',
                'roles'       => ['superadmin'],
            ],
            [
                'username'    => 'adminperawat01',
                'email'       => 'adminperawat01@rs.id',
                'password'    => 'perawat123',
                'nama_lengkap'=> 'Admin Perawat Satu',
                'roles'       => ['admin_perawat'],
            ],

            // Kelompok 2
            [
                'username'    => 'perawat01',
                'email'       => 'perawat01@rs.id',
                'password'    => 'perawat123',
                'nama_lengkap'=> 'Perawat Satu',
                'roles'       => ['perawat'],
            ],
            [
                'username'    => 'dokter01',
                'email'       => 'dokter01@rs.id',
                'password'    => 'dokter123',
                'nama_lengkap'=> 'Dr. Budi Santoso',
                'roles'       => ['dokter'],
            ],

            // Kelompok 3
            [
                'username'    => 'kasir01',
                'email'       => 'kasir01@rs.id',
                'password'    => 'kasir123',
                'nama_lengkap'=> 'Kasir Satu',
                'roles'       => ['kasir'],
            ],
            [
                'username'    => 'adminkasir01',
                'email'       => 'adminkasir01@rs.id',
                'password'    => 'kasir123',
                'nama_lengkap'=> 'Admin Kasir Satu',
                'roles'       => ['admin_kasir'],
            ],

            // Kelompok 4
            [
                'username'    => 'apoteker01',
                'email'       => 'apoteker01@rs.id',
                'password'    => 'apotik123',
                'nama_lengkap'=> 'Apoteker Satu',
                'roles'       => ['apoteker'],
            ],
            [
                'username'    => 'adminapotik01',
                'email'       => 'adminapotik01@rs.id',
                'password'    => 'apotik123',
                'nama_lengkap'=> 'Admin Apotik Satu',
                'roles'       => ['admin_apotik'],
            ],
        ];

        foreach ($defaultUsers as $data) {
            $user = User::firstOrCreate(
                ['username' => $data['username']],
                [
                    'email'             => $data['email'],
                    'password'          => Hash::make($data['password']),
                    'nama_lengkap'      => $data['nama_lengkap'],
                    'is_active'         => true,
                    'email_verified_at' => now(), // staff tidak perlu verifikasi email
                ]
            );

            $roleIds = Role::whereIn('nama_role', $data['roles'])->pluck('id');
            $user->roles()->syncWithoutDetaching($roleIds);
        }

        echo "\n✅ Seeder selesai!\n";
        echo "─────────────────────────────────────────\n";
        echo "KELOMPOK 1:\n";
        echo "  superadmin    → admin12345\n";
        echo "  adminperawat01→ perawat123\n";
        echo "KELOMPOK 2:\n";
        echo "  perawat01     → perawat123\n";
        echo "  dokter01      → dokter123\n";
        echo "KELOMPOK 3:\n";
        echo "  kasir01       → kasir123\n";
        echo "  adminkasir01  → kasir123\n";
        echo "KELOMPOK 4:\n";
        echo "  apoteker01    → apotik123\n";
        echo "  adminapotik01 → apotik123\n";
        echo "─────────────────────────────────────────\n";
    }
}
