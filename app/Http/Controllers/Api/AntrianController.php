<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Antrian;
use App\Models\Pendaftaran;
use App\Models\UnitPemeriksaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AntrianController extends Controller
{
    /**
     * GET /api/antrian
     * Tampilkan antrian hari ini per unit (untuk display monitor di ruangan)
     * Bisa diakses tanpa auth (untuk display publik)
     */
    public function index(Request $request): JsonResponse
    {
        $tanggal = $request->tanggal ?? today()->toDateString();
        $unitId  = $request->unit_id;

        $antrian = Antrian::with(['pendaftaran.pasien', 'unit'])
            ->when($unitId, fn($q) => $q->where('unit_id', $unitId))
            ->where('tanggal', $tanggal)
            ->orderBy('nomor_antrian')
            ->get()
            ->groupBy('unit_id');

        return response()->json([
            'success'  => true,
            'tanggal'  => $tanggal,
            'data'     => $antrian,
        ]);
    }

    /**
     * GET /api/antrian/unit/{unitId}/display
     * Khusus untuk display monitor antrian di ruang tunggu unit
     * Format ringkas untuk ditampilkan di layar
     */
    public function display(int $unitId): JsonResponse
    {
        $tanggal = today()->toDateString();

        $unit = UnitPemeriksaan::findOrFail($unitId);

        $sedangDilayani = Antrian::with('pendaftaran.pasien')
            ->where('unit_id', $unitId)
            ->where('tanggal', $tanggal)
            ->where('status', 'dipanggil')
            ->first();

        $menunggu = Antrian::with('pendaftaran.pasien')
            ->where('unit_id', $unitId)
            ->where('tanggal', $tanggal)
            ->where('status', 'menunggu')
            ->orderBy('nomor_antrian')
            ->take(5)
            ->get();

        $totalHariIni = Antrian::where('unit_id', $unitId)
            ->where('tanggal', $tanggal)
            ->count();

        $sudahSelesai = Antrian::where('unit_id', $unitId)
            ->where('tanggal', $tanggal)
            ->whereIn('status', ['dilayani', 'selesai'])
            ->count();

        return response()->json([
            'success'   => true,
            'unit'      => $unit->nama_unit,
            'tanggal'   => $tanggal,
            'dipanggil' => $sedangDilayani ? [
                'kode'         => $sedangDilayani->kode_antrian,
                'nomor'        => $sedangDilayani->nomor_antrian,
                'nama_pasien'  => $sedangDilayani->pendaftaran->pasien->nama_lengkap,
                'waktu_panggil'=> $sedangDilayani->waktu_panggil,
            ] : null,
            'antrian_menunggu' => $menunggu->map(fn($a) => [
                'kode'  => $a->kode_antrian,
                'nomor' => $a->nomor_antrian,
            ]),
            'statistik' => [
                'total'     => $totalHariIni,
                'selesai'   => $sudahSelesai,
                'menunggu'  => $totalHariIni - $sudahSelesai,
            ],
        ]);
    }

    /**
     * POST /api/antrian/{id}/panggil
     * Perawat: panggil nomor antrian berikutnya
     * CATATAN: Pemanggilan dilakukan di UNIT masing-masing (bukan di pendaftaran)
     */
    public function panggil(int $id): JsonResponse
    {
        $antrian = Antrian::with(['pendaftaran.pasien', 'unit'])->findOrFail($id);

        if ($antrian->status !== 'menunggu') {
            return response()->json([
                'success' => false,
                'message' => "Antrian sudah dalam status: {$antrian->status}",
            ], 422);
        }

        // Reset antrian sebelumnya yang masih 'dipanggil' → 'tidak_hadir'
        Antrian::where('unit_id', $antrian->unit_id)
            ->where('tanggal', $antrian->tanggal)
            ->where('status', 'dipanggil')
            ->update(['status' => 'tidak_hadir']);

        // Panggil antrian ini
        $antrian->update([
            'status'         => 'dipanggil',
            'waktu_panggil'  => now(),
            'dipanggil_oleh' => auth()->id(),
        ]);

        // Update status pendaftaran
        $antrian->pendaftaran->update(['status' => 'dipanggil']);

        return response()->json([
            'success' => true,
            'message' => "Antrian {$antrian->kode_antrian} dipanggil.",
            'data'    => [
                'kode_antrian' => $antrian->kode_antrian,
                'nomor_antrian'=> $antrian->nomor_antrian,
                'unit'         => $antrian->unit->nama_unit,
                'nama_pasien'  => $antrian->pendaftaran->pasien->nama_lengkap,
                'waktu_panggil'=> now(),
            ],
        ]);
    }

    /**
     * POST /api/antrian/unit/{unitId}/panggil-berikutnya
     * Perawat: panggil otomatis nomor antrian terkecil yang masih menunggu
     */
    public function panggilBerikutnya(int $unitId): JsonResponse
    {
        $tanggal = today()->toDateString();

        $antrian = Antrian::with(['pendaftaran.pasien', 'unit'])
            ->where('unit_id', $unitId)
            ->where('tanggal', $tanggal)
            ->where('status', 'menunggu')
            ->orderBy('nomor_antrian')
            ->first();

        if (!$antrian) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada antrian yang menunggu.',
            ], 404);
        }

        return $this->panggil($antrian->id);
    }

    /**
     * PUT /api/antrian/{id}/selesai
     * Tandai antrian selesai dilayani (biasanya dipanggil kelompok 2)
     */
    public function selesai(int $id): JsonResponse
    {
        $antrian = Antrian::findOrFail($id);
        $antrian->update(['status' => 'selesai']);

        return response()->json([
            'success' => true,
            'message' => "Antrian {$antrian->kode_antrian} selesai.",
        ]);
    }

    /**
     * GET /api/antrian/saya
     * Pasien: lihat status antrian sendiri hari ini
     */
    public function saya(): JsonResponse
    {
        $user   = auth()->user();
        $pasien = $user->pasien;

        if (!$pasien) {
            return response()->json([
                'success' => false,
                'message' => 'Data pasien tidak ditemukan.',
            ], 404);
        }

        $antrian = Antrian::with(['unit', 'pendaftaran'])
            ->whereHas('pendaftaran', fn($q) => $q->where('pasien_id', $pasien->id))
            ->where('tanggal', today()->toDateString())
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $antrian,
        ]);
    }
}
