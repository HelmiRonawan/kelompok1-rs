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
     * Perawat: list pendaftaran hari ini
     */
    public function index(Request $request): JsonResponse
    {
        $tanggal = $request->tanggal ?? today()->toDateString();

        $pendaftaran = Pendaftaran::with(['pasien', 'unit', 'antrian', 'pendaftar'])
            ->where('tanggal_kunjungan', $tanggal)
            ->when($request->unit_id, fn($q) => $q->where('unit_id', $request->unit_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $pendaftaran,
        ]);
    }

    /**
     * POST /api/pendaftaran
     * Perawat: daftarkan kunjungan pasien (langsung)
     * Pasien: daftar online (jenis_pendaftaran = online)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pasien_id'   => 'required|integer|exists:pasien,id',
            'unit_id'     => 'required|integer|exists:unit_pemeriksaan,id',
            'keluhan'     => 'nullable|string|max:500',
            'jenis_pendaftaran' => 'sometimes|in:langsung,online',
        ]);

        $user = auth()->user();
        $tanggal = today()->toDateString();

        // Cegah double daftar hari ini di unit yang sama
        $sudahDaftar = Pendaftaran::where('pasien_id', $validated['pasien_id'])
            ->where('unit_id', $validated['unit_id'])
            ->where('tanggal_kunjungan', $tanggal)
            ->exists();

        if ($sudahDaftar) {
            return response()->json([
                'success' => false,
                'message' => 'Pasien sudah terdaftar di unit ini hari ini.',
            ], 422);
        }

        $unit = UnitPemeriksaan::findOrFail($validated['unit_id']);

        if (!$unit->is_active) {
            return response()->json([
                'success' => false,
                'message' => "Unit {$unit->nama_unit} sedang tidak aktif.",
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Buat pendaftaran
            $pendaftaran = Pendaftaran::create([
                'nomor_pendaftaran'  => Pendaftaran::generateNomor($tanggal),
                'pasien_id'         => $validated['pasien_id'],
                'unit_id'           => $validated['unit_id'],
                'didaftarkan_oleh'  => $user->id,
                'tanggal_kunjungan' => $tanggal,
                'jenis_pendaftaran' => $validated['jenis_pendaftaran'] ?? 'langsung',
                'keluhan'           => $validated['keluhan'] ?? null,
                'status'            => 'terdaftar',
            ]);

            // Buat nomor antrian otomatis
            $nomor      = Antrian::nomorBerikutnya($unit->id, $tanggal);
            $kode       = Antrian::generateKode($unit, $nomor);

            Antrian::create([
                'pendaftaran_id' => $pendaftaran->id,
                'unit_id'        => $unit->id,
                'tanggal'        => $tanggal,
                'nomor_antrian'  => $nomor,
                'kode_antrian'   => $kode,
                'status'         => 'menunggu',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pendaftaran berhasil.',
                'data'    => $pendaftaran->load(['pasien', 'unit', 'antrian']),
                'antrian' => [
                    'nomor'  => $nomor,
                    'kode'   => $kode,
                    'unit'   => $unit->nama_unit,
                ],
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendaftarkan.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/pendaftaran/{id}
     */
    public function show(int $id): JsonResponse
    {
        $pendaftaran = Pendaftaran::with(['pasien', 'unit', 'antrian', 'pendaftar'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $pendaftaran,
        ]);
    }

    /**
     * PUT /api/pendaftaran/{id}/status
     * Update status pendaftaran (dipakai kelompok lain juga via API internal)
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:terdaftar,dipanggil,sedang_periksa,selesai_periksa,selesai',
        ]);

        $pendaftaran = Pendaftaran::findOrFail($id);
        $pendaftaran->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Status pendaftaran diupdate.',
            'data'    => $pendaftaran->fresh(),
        ]);
    }

    /**
     * GET /api/pendaftaran/pasien/{pasienId}
     * Riwayat kunjungan pasien tertentu
     */
    public function riwayatPasien(int $pasienId): JsonResponse
    {
        $pendaftaran = Pendaftaran::with(['unit', 'antrian'])
            ->where('pasien_id', $pasienId)
            ->orderByDesc('tanggal_kunjungan')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $pendaftaran,
        ]);
    }
}
