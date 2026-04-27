<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UnitPemeriksaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * GET /api/units
     */
    public function index(): JsonResponse
    {
        $units = UnitPemeriksaan::where('is_active', true)
            ->get(['id', 'kode_unit', 'nama_unit']);

        return response()->json([
            'success' => true,
            'data'    => $units,
        ]);
    }

    /**
     * GET /api/units/{id}
     */
    public function show(int $id): JsonResponse
    {
        $unit = UnitPemeriksaan::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $unit,
        ]);
    }

    /**
    * POST /api/units
    * Superadmin: tambah unit baru
    */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode_unit' => 'required|string|max:10|unique:unit_pemeriksaan,kode_unit',
            'nama_unit' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
        ]);

        $unit = UnitPemeriksaan::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Unit berhasil ditambahkan.',
            'data'    => $unit,
        ], 201);
    }

    /**
    * PUT /api/units/{id}
    * Superadmin: update unit (nama, aktif/nonaktif)
    */
    public function update(Request $request, int $id): JsonResponse
    {
        $unit = UnitPemeriksaan::findOrFail($id);

        $validated = $request->validate([
            'nama_unit' => 'sometimes|string|max:100',
            'deskripsi' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $unit->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Unit berhasil diupdate.',
            'data'    => $unit->fresh(),
        ]);
    }
}