<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UnitPemeriksaan;
use Illuminate\Http\JsonResponse;

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
}