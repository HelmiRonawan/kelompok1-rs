<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AntrianController;
use App\Http\Controllers\Api\PasienController;
use App\Http\Controllers\Api\PendaftaranController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Kelompok 1: Auth & Pendaftaran — API Routes (v3: + register + verifikasi email)
|--------------------------------------------------------------------------
*/

// ── Auth Public (tidak perlu token) ───────────────────────────────────────
Route::prefix('auth')->group(function () {

    // Login & Token
    Route::post('login',         [AuthController::class, 'login']);
    Route::post('verify-token',  [AuthController::class, 'verifyToken']); // untuk kelompok 2,3,4

    // Register Pasien Mandiri
    Route::post('register',      [AuthController::class, 'register']);

    // Verifikasi Email
    Route::post('verify-email',           [AuthController::class, 'verifyEmail']);
    Route::post('resend-verification',   [AuthController::class, 'resendVerification']);

    // Forgot Password
    Route::post('forgot-password',  [AuthController::class, 'forgotPassword']);
    Route::post('reset-password',   [AuthController::class, 'resetPassword']);
    Route::get('check-reset-token', [AuthController::class, 'checkResetToken']);
});

// ── Auth Protected (perlu token) ───────────────────────────────────────────
Route::prefix('auth')->middleware('auth:api')->group(function () {
    Route::post('logout',  [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me',       [AuthController::class, 'me']);
});

// ── Display Antrian (Public — monitor di ruang tunggu) ────────────────────
Route::get('antrian/unit/{unitId}/display', [AntrianController::class, 'display']);

// ── Protected Routes ───────────────────────────────────────────────────────
Route::middleware('auth:api')->group(function () {

    // User Management (Superadmin only)
    Route::apiResource('users', UserController::class)
        ->middleware('role:superadmin');

    // Pasien
    Route::prefix('pasien')->group(function () {
        Route::middleware('role:superadmin,perawat')->group(function () {
            Route::get('/',             [PasienController::class, 'index']);
            Route::post('/',            [PasienController::class, 'store']);
            Route::put('{id}',          [PasienController::class, 'update']);
            Route::get('cek-nik/{nik}', [PasienController::class, 'cekNik']);
        });
        Route::middleware('role:superadmin,perawat,pasien')->group(function () {
            Route::get('{id}',          [PasienController::class, 'show']);
        });
    });

    // Pendaftaran
    Route::prefix('pendaftaran')->group(function () {
        Route::middleware('role:superadmin,perawat')->group(function () {
            Route::get('/',           [PendaftaranController::class, 'index']);
            Route::post('/',          [PendaftaranController::class, 'store']);
            Route::put('{id}/status', [PendaftaranController::class, 'updateStatus']);
        });
        Route::middleware('role:superadmin,perawat,pasien')->group(function () {
            Route::get('{id}',                          [PendaftaranController::class, 'show']);
            Route::get('pasien/{pasienId}/riwayat',     [PendaftaranController::class, 'riwayatPasien']);
        });
        // Pasien daftar online (pilih unit + keluhan)
        Route::post('online', [PendaftaranController::class, 'store'])
            ->middleware('role:pasien');
    });

    // Antrian
    Route::prefix('antrian')->group(function () {
        Route::get('/', [AntrianController::class, 'index'])
            ->middleware('role:superadmin,perawat');
        Route::middleware('role:superadmin,perawat')->group(function () {
            Route::post('{id}/panggil',                     [AntrianController::class, 'panggil']);
            Route::post('unit/{unitId}/panggil-berikutnya', [AntrianController::class, 'panggilBerikutnya']);
            Route::put('{id}/selesai',                      [AntrianController::class, 'selesai']);
        });
        // Pasien lihat antrian sendiri
        Route::get('saya', [AntrianController::class, 'saya'])
            ->middleware('role:pasien');
    });
});
