<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pasien;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PasienController extends Controller
{
    /**
     * GET /api/pasien
     * Perawat: cari pasien (by NIK, nomor RM, nama)
     */
    public function index(Request $request): JsonResponse
    {
        $pasien = Pasien::query()
            ->when($request->search, fn($q) =>
                $q->where('nama_lengkap', 'like', "%{$request->search}%")
                  ->orWhere('nik', $request->search)
                  ->orWhere('nomor_rm', $request->search)
            )
            ->with('user')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $pasien,
        ]);
    }

    /**
     * GET /api/pasien/{id}
     */
    public function show(int $id): JsonResponse
    {
        $pasien = Pasien::with(['user', 'pendaftaran.unit', 'pendaftaran.antrian'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $pasien,
        ]);
    }

    /**
     * GET /api/pasien/cek-nik/{nik}
     * Cek apakah pasien sudah terdaftar sebelum daftar ulang
     */
    public function cekNik(string $nik): JsonResponse
    {
        $pasien = Pasien::where('nik', $nik)->first();

        if ($pasien) {
            return response()->json([
                'success'   => true,
                'terdaftar' => true,
                'data'      => $pasien,
            ]);
        }

        return response()->json([
            'success'   => true,
            'terdaftar' => false,
            'data'      => null,
        ]);
    }

    /**
     * POST /api/pasien
     * Perawat: daftarkan pasien baru (langsung datang)
     * Juga otomatis buat akun user dengan role 'pasien'
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nik'          => 'required|string|size:16|unique:pasien,nik',
            'nama_lengkap' => 'required|string|max:100',
            'jenis_kelamin'=> 'required|in:L,P',
            'tanggal_lahir'=> 'required|date',
            'tempat_lahir' => 'nullable|string|max:100',
            'alamat'       => 'nullable|string',
            'no_telepon'   => 'nullable|string|max:20',
        ]);

        DB::beginTransaction();
        try {
            // Buat akun user otomatis (username = NIK, password = tgl lahir YYYYMMDD)
            $defaultPassword = str_replace('-', '', $validated['tanggal_lahir']);
            $user = User::create([
                'username'    => $validated['nik'],
                'password'    => Hash::make($defaultPassword),
                'nama_lengkap'=> $validated['nama_lengkap'],
            ]);

            $pasienRole = Role::where('nama_role', 'pasien')->first();
            if ($pasienRole) {
                $user->roles()->attach($pasienRole->id);
            }

            // Buat data pasien
            $pasien = Pasien::create(array_merge($validated, [
                'user_id'    => $user->id,
                'nomor_rm'   => Pasien::generateNomorRM(),
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pasien berhasil didaftarkan.',
                'data'    => $pasien->load('user'),
                'info'    => [
                    'username' => $user->username,
                    'password_default' => $defaultPassword,
                    'note'    => 'Password default = tanggal lahir (YYYYMMDD). Minta pasien ganti setelah login.',
                ],
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendaftarkan pasien.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/pasien/{id}
     * Perawat/Pasien: update data pasien
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $pasien = Pasien::findOrFail($id);

        $validated = $request->validate([
            'nama_lengkap' => 'sometimes|string|max:100',
            'alamat'       => 'sometimes|string',
            'no_telepon'   => 'sometimes|string|max:20',
        ]);

        $pasien->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data pasien berhasil diupdate.',
            'data'    => $pasien->fresh(),
        ]);
    }
}
