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
        $unit    = UnitPemeriksaan::findOrFail($unitId);

        $sedangDipanggil = Antrian::with('pendaftaran.pasien')
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

        // ← Fix: sesuaikan status yang dianggap selesai
        $sudahSelesai = Antrian::where('unit_id', $unitId)
            ->where('tanggal', $tanggal)
            ->whereIn('status', [
                'selesai_pemeriksaan',
                'lunas',
                'obat_diserahkan',  // ← status final
            ])
            ->count();

        return response()->json([
            'success' => true,
            'unit'    => $unit->nama_unit,
            'tanggal' => $tanggal,
            'dipanggil' => $sedangDipanggil ? [
                'kode'         => $sedangDipanggil->kode_antrian,
                'nomor'        => $sedangDipanggil->nomor_antrian,
                'nama_pasien'  => $sedangDipanggil->pendaftaran->pasien->nama_lengkap,
                'waktu_panggil'=> $sedangDipanggil->waktu_panggil,
            ] : null,
            'antrian_menunggu' => $menunggu->map(fn($a) => [
                'kode'  => $a->kode_antrian,
                'nomor' => $a->nomor_antrian,
            ]),
            'statistik' => [
                'total'    => $totalHariIni,
                'selesai'  => $sudahSelesai,
                'menunggu' => $totalHariIni - $sudahSelesai,
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

        // Panggil antrian ini
        $antrian->update([
            'status'         => 'dipanggil',
            'waktu_panggil'  => now()
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
    * PUT /api/antrian/{id}/status
    * Dipakai kelompok 2, 3, 4 untuk update status
    */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:menunggu,dipanggil,pemeriksaan_awal,sedang_diperiksa,selesai_pemeriksaan,lunas,obat_diserahkan,tidak_hadir',
        ]);

        $antrian = Antrian::with(['pendaftaran.pasien', 'unit'])->findOrFail($id);

        // Catat waktu panggil jika status dipanggil
        $data = ['status' => $validated['status']];
        if ($validated['status'] === 'dipanggil') {
            $data['waktu_panggil'] = now();
        }

        $antrian->update($data);

        return response()->json([
            'success' => true,
            'message' => "Status antrian diupdate ke {$validated['status']}.",
            'data'    => $antrian->fresh()->load('pendaftaran.pasien', 'unit'),
        ]);
    }

    /**
    * GET /api/antrian/by-pendaftaran/{pendaftaranId}
    * Kelompok lain ambil data antrian by pendaftaran_id
    */
    public function byPendaftaran(int $pendaftaranId): JsonResponse
    {
        $antrian = Antrian::with(['pendaftaran.pasien', 'unit'])
            ->where('pendaftaran_id', $pendaftaranId)
            ->firstOrFail();
    
        return response()->json([
            'success' => true,
            'data'    => $antrian,
        ]);
    }

    /**
    * GET /api/antrian/unit/{unitId}
     * Kelompok 2,3,4 tampilkan list antrian di unit mereka hari ini
    */
    public function listByUnit(int $unitId): JsonResponse
    {
        $tanggal = today()->toDateString();
    
        $antrian = Antrian::with(['pendaftaran.pasien'])
            ->where('unit_id', $unitId)
            ->where('tanggal', $tanggal)
            ->orderBy('nomor_antrian')
            ->get();
    
        // Kelompokkan by status
        return response()->json([
            'success' => true,
            'unit'    => UnitPemeriksaan::find($unitId)?->nama_unit,
            'tanggal' => $tanggal,
            'data'    => [
                'menunggu'            => $antrian->where('status', 'menunggu')->values(),
                'pemeriksaan_awal'    => $antrian->where('status', 'pemeriksaan_awal')->values(),
                'sedang_diperiksa'    => $antrian->where('status', 'sedang_diperiksa')->values(),
                'selesai_pemeriksaan' => $antrian->where('status', 'selesai_pemeriksaan')->values(),
                'lunas'               => $antrian->where('status', 'lunas')->values(),
                'obat_diserahkan'     => $antrian->where('status', 'obat_diserahkan')->values(),
            ],
            'statistik' => [
                'total'    => $antrian->count(),
                'menunggu' => $antrian->where('status', 'menunggu')->count(),
                'selesai'  => $antrian->where('status', 'obat_diserahkan')->count(),
            ],
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
