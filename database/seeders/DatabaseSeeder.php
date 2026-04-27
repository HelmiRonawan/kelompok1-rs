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
        // ── Roles ──────────────────────────────────
        $roles = [
            // Auth & Pendaftaran
            ['nama_role' => 'superadmin',   'deskripsi' => 'Administrator sistem, bisa buat user baru'],
            ['nama_role' => 'admin_perawat','deskripsi' => 'Menerima pendaftaran dan memanggil antrian'],
            ['nama_role' => 'pasien',       'deskripsi' => 'Daftar online dan lihat status antrian'],

            // Pemeriksaan
            ['nama_role' => 'perawat',      'deskripsi' => 'Pemanggilan antrian & assessment awal pasien'],
            ['nama_role' => 'dokter',       'deskripsi' => 'Pemeriksaan, diagnosa, dan E-Resep'],

            // Kasir
            ['nama_role' => 'kasir',        'deskripsi' => 'Pemanggilan antrian & penerima pembayaran'],
            ['nama_role' => 'admin_kasir',  'deskripsi' => 'Menentukan harga jasa dan obat'],

            // Apotik
            ['nama_role' => 'apoteker',     'deskripsi' => 'Pemanggilan antrian & penyerahan obat'],
            ['nama_role' => 'admin_apotik', 'deskripsi' => 'Kelola stok, harga beli/jual obat'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['nama_role' => $role['nama_role']], $role);
        }

        // ── Unit Pemeriksaan ───────────────────────
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

        // ── User Default ───────────────────────────

        $defaultUsers = [
            // 1
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

            // 2
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

            // 3
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

            // 4
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

        echo "\nSeeder selesai!\n";
        echo "─────────────────────────────────────────\n";
        echo "1:\n";
        echo "  superadmin    → admin12345\n";
        echo "  adminperawat01→ perawat123\n";
        echo "2:\n";
        echo "  perawat01     → perawat123\n";
        echo "  dokter01      → dokter123\n";
        echo "3:\n";
        echo "  kasir01       → kasir123\n";
        echo "  adminkasir01  → kasir123\n";
        echo "4:\n";
        echo "  apoteker01    → apotik123\n";
        echo "  adminapotik01 → apotik123\n";
        echo "─────────────────────────────────────────\n";
    }
}
