<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AntrianController;
use App\Http\Controllers\Api\PasienController;
use App\Http\Controllers\Api\PendaftaranController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// ── Auth Public ───────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('register',             [AuthController::class, 'register']);
    Route::post('verify-email',         [AuthController::class, 'verifyEmail']);
    Route::post('resend-verification',  [AuthController::class, 'resendVerification']);
    Route::post('login',                [AuthController::class, 'login']);
    Route::post('verify-token',         [AuthController::class, 'verifyToken']); // untuk kelompok 2,3,4
    Route::post('forgot-password',      [AuthController::class, 'forgotPassword']);
    Route::post('check-reset-token',    [AuthController::class, 'checkResetToken']);
    Route::post('reset-password',       [AuthController::class, 'resetPassword']);
});

// ── Auth Protected ────────────────────────────────────────────────────────
Route::prefix('auth')->middleware('auth:api')->group(function () {
    Route::post('logout',  [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me',       [AuthController::class, 'me']);
});

// ── Unit (Public) ──────────────────────────────────
Route::get('units',      [UnitController::class, 'index']);
Route::get('units/{id}', [UnitController::class, 'show']);

// ── Display Antrian (Public — monitor ruang tunggu) ───────────────────────
Route::get('antrian/unit/{unitId}/display', [AntrianController::class, 'display']);

// ── Protected Routes ──────────────────────────────────────────────────────
Route::middleware('auth:api')->group(function () {

    // Unit (Superadmin)
    Route::middleware('role:superadmin')->group(function () {
        Route::post('units',      [UnitController::class, 'store']);
        Route::put('units/{id}',  [UnitController::class, 'update']);
    });

    // User Management (Superadmin)
    Route::apiResource('users', UserController::class)
        ->middleware('role:superadmin');

    // Pasien
    Route::prefix('pasien')->group(function () {
        Route::middleware('role:superadmin,admin_perawat')->group(function () {
            Route::get('/',             [PasienController::class, 'index']);
            Route::get('cek-nik/{nik}', [PasienController::class, 'cekNik']);
            Route::put('{id}',          [PasienController::class, 'update']);
        });
        Route::middleware('role:superadmin,admin_perawat,pasien')->group(function () {
            Route::get('{id}', [PasienController::class, 'show']);
        });
    });

    // Pendaftaran
    Route::prefix('pendaftaran')->group(function () {
        // Admin perawat: list & daftar langsung
        Route::middleware('role:superadmin,admin_perawat')->group(function () {
            Route::get('/',           [PendaftaranController::class, 'index']);
            Route::post('/langsung',  [PendaftaranController::class, 'langsung']); // ← pasien datang langsung
        });

        // Pasien: daftar online
        Route::post('/online', [PendaftaranController::class, 'online'])
            ->middleware('role:pasien');

        // Semua role: lihat detail & riwayat
        Route::middleware('role:superadmin,admin_perawat,pasien')->group(function () {
            Route::get('{id}',                      [PendaftaranController::class, 'show']);
            Route::get('pasien/{pasienId}/riwayat', [PendaftaranController::class, 'riwayatPasien']);
        });
    });

    // Antrian
    Route::prefix('antrian')->group(function () {
        Route::get('/', [AntrianController::class, 'index'])
            ->middleware('role:superadmin,admin_perawat');

        Route::middleware('role:superadmin,admin_perawat,perawat,dokter,admin_kasir,kasir,admin_apotik,apoteker')->group(function () {
            Route::post('{id}/panggil',                     [AntrianController::class, 'panggil']);
            Route::post('unit/{unitId}/panggil-berikutnya', [AntrianController::class, 'panggilBerikutnya']);
            Route::put('{id}/status',                       [AntrianController::class, 'updateStatus']);
            Route::get('by-pendaftaran/{pendaftaranId}',    [AntrianController::class, 'byPendaftaran']);
            Route::get('unit/{unitId}',                     [AntrianController::class, 'listByUnit']);
        });

        Route::get('saya', [AntrianController::class, 'saya'])
            ->middleware('role:pasien');
    });

    // Public — untuk display monitor & kelompok lain
    Route::get('antrian/unit/{unitId}',         [AntrianController::class, 'listByUnit']);
    Route::get('antrian/unit/{unitId}/display', [AntrianController::class, 'display']);
});
