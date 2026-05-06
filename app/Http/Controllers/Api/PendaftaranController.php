<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Antrian;
use App\Models\Pasien;
use App\Models\Pendaftaran;
use App\Models\UnitPemeriksaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PendaftaranController extends Controller
{
    /**
     * GET /api/pendaftaran
     * Admin perawat: list pendaftaran hari ini
     */
    public function index(Request $request): JsonResponse
    {
        $tanggal = $request->tanggal ?? today()->toDateString();

        $pendaftaran = Pendaftaran::with(['pasien', 'unit', 'antrian'])
            ->where('tanggal_kunjungan', $tanggal)
            ->when($request->unit_id, fn($q) => $q->where('unit_id', $request->unit_id))
            ->when($request->status,  fn($q) => $q->where('status', $request->status))
            ->orderBy('created_at')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $pendaftaran]);
    }

    /**
     * POST /api/pendaftaran/langsung
     *
     * Admin perawat: daftarkan pasien yang datang langsung.
     * Sistem otomatis deteksi pasien baru atau lama by NIK.
     *
     * Body:
     *   nik          : wajib
     *   nama_lengkap : wajib
     *   tanggal_lahir: wajib
     *   unit_id      : wajib
     *   (data tambahan jika pasien baru — opsional)
     *   jenis_kelamin: opsional
     *   alamat       : opsional
     *   no_telepon   : opsional
     */
    public function langsung(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nik'          => 'required|string|size:16',
            'nama_lengkap' => 'required|string|max:100',
            'tanggal_lahir'=> 'required|date',
            'unit_id'      => 'required|integer|exists:unit_pemeriksaan,id',
            'jenis_kelamin'=> 'nullable|in:L,P',
            'alamat'       => 'nullable|string',
            'no_telepon'   => 'nullable|string|max:20',
        ]);

        return $this->prosesAntrian(
            nik          : $validated['nik'],
            namaLengkap  : $validated['nama_lengkap'],
            tanggalLahir : $validated['tanggal_lahir'],
            unitId       : $validated['unit_id'],
            dataTambahan : $validated,
            userId       : null  // tidak ada user_id karena didaftarkan perawat
        );
    }

    /**
     * POST /api/pendaftaran/online
     *
     * Pasien: daftar antrian secara online.
     * Sistem otomatis deteksi pasien baru atau lama by NIK.
     *
     * Pasien BARU (belum punya data di DB):
     *   Body: { nik, nama_lengkap, tanggal_lahir, jenis_kelamin, alamat, no_telepon, unit_id }
     *
     * Pasien LAMA (NIK sudah ada di DB):
     *   Body: { nik, nama_lengkap, nomor_rm, unit_id }
     *   (sistem verifikasi NIK + nama + nomor_rm)
     */
    public function online(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nik'          => 'required|string|size:16',
            'nama_lengkap' => 'required|string|max:100',
            'unit_id'      => 'required|integer|exists:unit_pemeriksaan,id',
            // Pasien lama
            'nomor_rm'     => 'nullable|string',
            // Pasien baru (opsional jika lama)
            'tanggal_lahir'=> 'nullable|date',
            'jenis_kelamin'=> 'nullable|in:L,P',
            'alamat'       => 'nullable|string',
            'no_telepon'   => 'nullable|string|max:20',
        ]);

        $user = auth()->user();

        // Cek apakah pasien lama (NIK sudah ada)
        $pasienLama = Pasien::where('nik', $validated['nik'])->first();

        if ($pasienLama) {
            // ── PASIEN LAMA ────────────────────────────────────────────
            // Verifikasi: NIK + nama harus cocok
            if (strtolower(trim($pasienLama->nama_lengkap)) !== strtolower(trim($validated['nama_lengkap']))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak cocok. Periksa NIK dan nama lengkap.',
                ], 422);
            }

            // Jika ada nomor_rm, verifikasi juga
            if (!empty($validated['nomor_rm']) && $pasienLama->nomor_rm !== $validated['nomor_rm']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nomor rekam medis tidak sesuai.',
                ], 422);
            }

            // Link ke akun user jika belum
            if (!$pasienLama->user_id && $user) {
                $pasienLama->update(['user_id' => $user->id]);
            }

            return $this->prosesAntrian(
                nik         : $validated['nik'],
                namaLengkap : $pasienLama->nama_lengkap,
                tanggalLahir: $pasienLama->tanggal_lahir?->toDateString() ?? now()->toDateString(),
                unitId      : $validated['unit_id'],
                dataTambahan: $validated,
                userId      : $user?->id,
                pasienExist : $pasienLama
            );
        }

        // ── PASIEN BARU ────────────────────────────────────────────────
        // Validasi tambahan untuk pasien baru
        $request->validate([
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:L,P',
        ]);

        return $this->prosesAntrian(
            nik         : $validated['nik'],
            namaLengkap : $validated['nama_lengkap'],
            tanggalLahir: $validated['tanggal_lahir'],
            unitId      : $validated['unit_id'],
            dataTambahan: $validated,
            userId      : $user?->id
        );
    }

    /**
     * Core logic: buat/cari pasien → buat pendaftaran → buat antrian
     */
    private function prosesAntrian(
        string  $nik,
        string  $namaLengkap,
        string  $tanggalLahir,
        int     $unitId,
        array   $dataTambahan,
        ?int    $userId,
        ?Pasien $pasienExist = null
    ): JsonResponse {

        $unit    = UnitPemeriksaan::findOrFail($unitId);
        $tanggal = today()->toDateString();

        if (!$unit->is_active) {
            return response()->json([
                'success' => false,
                'message' => "Unit {$unit->nama_unit} sedang tidak aktif.",
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Buat atau ambil data pasien
            $pasien = $pasienExist ?? Pasien::firstOrCreate(
                ['nik' => $nik],
                [
                    'user_id'       => $userId,
                    'nomor_rm'      => Pasien::generateNomorRM(),
                    'nama_lengkap'  => $namaLengkap,
                    'tanggal_lahir' => $tanggalLahir,
                    'jenis_kelamin' => $dataTambahan['jenis_kelamin'] ?? null,
                    'alamat'        => $dataTambahan['alamat'] ?? null,
                    'no_telepon'    => $dataTambahan['no_telepon'] ?? null,
                ]
            );

            // Cegah double daftar hari ini di unit yang sama
            $sudahDaftar = Pendaftaran::where('pasien_id', $pasien->id)
                ->where('unit_id', $unitId)
                ->where('tanggal_kunjungan', $tanggal)
                ->exists();

            if ($sudahDaftar) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Pasien sudah terdaftar di unit ini hari ini.',
                ], 422);
            }

            // Buat pendaftaran
            $pendaftaran = Pendaftaran::create([
                'nomor_pendaftaran'  => Pendaftaran::generateNomor($tanggal),
                'pasien_id'          => $pasien->id,
                'unit_id'            => $unitId,
                'tanggal_kunjungan'  => $tanggal,
                'status'             => 'terdaftar',
            ]);

            // Buat antrian
            $nomor = Antrian::nomorBerikutnya($unitId, $tanggal);
            $kode  = Antrian::generateKode($unit, $nomor);

            Antrian::create([
                'pendaftaran_id' => $pendaftaran->id,
                'unit_id'        => $unitId,
                'tanggal'        => $tanggal,
                'nomor_antrian'  => $nomor,
                'kode_antrian'   => $kode,
                'status'         => 'menunggu',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pendaftaran berhasil.',
                'data'    => [
                    // Tiket antrian
                    'tiket' => [
                        'nomor_antrian'     => $nomor,
                        'kode_antrian'      => $kode,
                        'unit'              => $unit->nama_unit,
                        'tanggal'           => $tanggal,
                        'nomor_pendaftaran' => $pendaftaran->nomor_pendaftaran,
                    ],
                    'pasien' => [
                        'nomor_rm'     => $pasien->nomor_rm,
                        'nama_lengkap' => $pasien->nama_lengkap,
                        'nik'          => $pasien->nik,
                    ],
                ],
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Pendaftaran gagal.',
                'error'   => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * GET /api/pendaftaran/{id}
     */
    public function show(int $id): JsonResponse
    {
        $pendaftaran = Pendaftaran::with(['pasien', 'unit', 'antrian'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $pendaftaran]);
    }

    /**
     * PUT /api/pendaftaran/{id}/status
     * Dipakai kelompok lain untuk update status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:terdaftar,dipanggil,sedang_periksa,selesai_periksa,selesai',
        ]);

        $pendaftaran = Pendaftaran::findOrFail($id);
        $pendaftaran->update(['status' => $validated['status']]);

        return response()->json(['success' => true, 'message' => 'Status diupdate.', 'data' => $pendaftaran->fresh()]);
    }

    /**
     * GET /api/pendaftaran/pasien/{pasienId}/riwayat
     */
    public function riwayatPasien(int $pasienId): JsonResponse
    {
        $pendaftaran = Pendaftaran::with(['unit', 'antrian'])
            ->where('pasien_id', $pasienId)
            ->orderByDesc('tanggal_kunjungan')
            ->paginate(10);

        return response()->json(['success' => true, 'data' => $pendaftaran]);
    }
}
