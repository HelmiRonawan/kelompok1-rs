<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ForgotPasswordMail;
use App\Mail\VerifikasiEmailMail;
use App\Models\Pasien;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    // ── REGISTER PASIEN MANDIRI ────────────────────────────────────────────

    /**
     * POST /api/auth/register
     *
     * Pasien daftar mandiri untuk keperluan pendaftaran online.
     * Setelah register, email verifikasi dikirim ke Gmail.
     * Akun TIDAK aktif sampai email diverifikasi.
     *
     * Body:
     *   nik               : string (16 digit, wajib)
     *   nama_lengkap      : string (wajib)
     *   email             : string (wajib, untuk verifikasi)
     *   password          : string (min 8, wajib)
     *   password_confirmation : string (wajib)
     *   tanggal_lahir     : date (wajib)
     *   jenis_kelamin     : L|P (wajib)
     *   tempat_lahir      : string (opsional)
     *   alamat            : string (opsional)
     *   no_telepon        : string (opsional)
     *   jenis_pasien      : umum|bpjs (wajib)
     *   no_bpjs           : string (wajib jika bpjs)
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nik'                  => 'required|string|size:16|unique:pasien,nik',
            'nama_lengkap'         => 'required|string|max:100',
            'email'                => 'required|email|unique:users,email',
            'password'             => 'required|string|min:8|confirmed',
            'password_confirmation'=> 'required|string',
            'tanggal_lahir'        => 'required|date',
            'jenis_kelamin'        => 'required|in:L,P',
            'tempat_lahir'         => 'nullable|string|max:100',
            'alamat'               => 'nullable|string',
            'no_telepon'           => 'nullable|string|max:20',
            'jenis_pasien'         => 'required|in:umum,bpjs',
            'no_bpjs'              => 'required_if:jenis_pasien,bpjs|nullable|string|max:20',
        ]);

        DB::beginTransaction();
        try {
            // Generate token verifikasi email
            $verifikasiToken = Str::random(64);

            // Buat akun user — is_active FALSE sampai email diverifikasi
            $user = User::create([
                'username'                 => $validated['nik'], // username = NIK
                'email'                    => $validated['email'],
                'password'                 => Hash::make($validated['password']),
                'nama_lengkap'             => $validated['nama_lengkap'],
                'is_active'                => false, // ← belum aktif
                'email_verification_token' => Hash::make($verifikasiToken),
                'email_verified_at'        => null,
            ]);

            // Assign role pasien
            $pasienRole = Role::where('nama_role', 'pasien')->first();
            if ($pasienRole) {
                $user->roles()->attach($pasienRole->id);
            }

            // Buat data pasien
            $pasien = Pasien::create([
                'user_id'       => $user->id,
                'nomor_rm'      => Pasien::generateNomorRM(),
                'nomor_kartu'   => 'RS-' . str_pad($user->id, 8, '0', STR_PAD_LEFT),
                'nik'           => $validated['nik'],
                'nama_lengkap'  => $validated['nama_lengkap'],
                'jenis_kelamin' => $validated['jenis_kelamin'],
                'tanggal_lahir' => $validated['tanggal_lahir'],
                'tempat_lahir'  => $validated['tempat_lahir'] ?? null,
                'alamat'        => $validated['alamat'] ?? null,
                'no_telepon'    => $validated['no_telepon'] ?? null,
                'jenis_pasien'  => $validated['jenis_pasien'],
                'no_bpjs'       => $validated['no_bpjs'] ?? null,
            ]);

            // Kirim email verifikasi
            Mail::to($user->email)->send(new VerifikasiEmailMail($user, $verifikasiToken));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registrasi berhasil! Cek email kamu untuk verifikasi akun.',
                'data'    => [
                    'username'  => $user->username,
                    'email'     => $user->email,
                    'nomor_rm'  => $pasien->nomor_rm,
                ],
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Registrasi gagal. Coba lagi.',
                'error'   => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    // ── VERIFIKASI EMAIL ───────────────────────────────────────────────────

    /**
     * GET /api/auth/verify-email?token=xxx&email=yyy
     *
     * Dipanggil saat pasien klik link di email.
     * Mengaktifkan akun user.
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak ditemukan.',
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'success' => true,
                'message' => 'Email sudah diverifikasi sebelumnya. Silakan login.',
            ]);
        }

        if (!$user->email_verification_token || !Hash::check($request->token, $user->email_verification_token)) {
            return response()->json([
                'success' => false,
                'message' => 'Token verifikasi tidak valid.',
            ], 422);
        }

        // Aktifkan akun
        $user->update([
            'is_active'                => true,
            'email_verified_at'        => now(),
            'email_verification_token' => null, // hapus token setelah dipakai
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email berhasil diverifikasi! Akun kamu sudah aktif. Silakan login.',
        ]);
    }

    /**
     * POST /api/auth/resend-verification
     * Kirim ulang email verifikasi jika belum diterima
     *
     * Body: { "email": "..." }
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Selalu response sukses (tidak bocorkan info)
        if (!$user || $user->email_verified_at) {
            return response()->json([
                'success' => true,
                'message' => 'Jika email belum diverifikasi, link baru akan dikirim.',
            ]);
        }

        // Generate token baru
        $verifikasiToken = Str::random(64);
        $user->update([
            'email_verification_token' => Hash::make($verifikasiToken),
        ]);

        try {
            Mail::to($user->email)->send(new VerifikasiEmailMail($user, $verifikasiToken));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim email. Coba beberapa saat lagi.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Link verifikasi baru telah dikirim ke email kamu.',
        ]);
    }

    // ── LOGIN ──────────────────────────────────────────────────────────────

    /**
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username'    => 'required|string',
            'password'    => 'required|string',
            'remember_me' => 'sometimes|boolean',
        ]);

        $user = User::with('roles')->where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Username atau password salah.',
            ], 401);
        }

        // Cek apakah email sudah diverifikasi (khusus pasien yang daftar mandiri)
        if (!$user->email_verified_at && $user->hasRole('pasien')) {
            return response()->json([
                'success' => false,
                'message' => 'Email belum diverifikasi. Cek inbox Gmail kamu atau minta kirim ulang.',
                'action'  => 'resend_verification', // hint untuk frontend
            ], 403);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak aktif. Hubungi admin.',
            ], 403);
        }

        $rememberMe = $request->boolean('remember_me', false);
        $ttlMenit   = $rememberMe ? (60 * 24 * 7) : 60;

        $token = JWTAuth::customClaims([
            'remember_me' => $rememberMe,
            'exp'         => now()->addMinutes($ttlMenit)->timestamp,
        ])->fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data'    => [
                'token'       => $token,
                'token_type'  => 'Bearer',
                'expires_in'  => $ttlMenit * 60,
                'remember_me' => $rememberMe,
                'user'        => [
                    'id'          => $user->id,
                    'username'    => $user->username,
                    'nama_lengkap'=> $user->nama_lengkap,
                    'email'       => $user->email,
                    'roles'       => $user->roles->pluck('nama_role'),
                ],
            ],
        ]);
    }

    // ── LOGOUT ─────────────────────────────────────────────────────────────

    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['success' => true, 'message' => 'Logout berhasil.']);
    }

    // ── REFRESH ────────────────────────────────────────────────────────────

    public function refresh(): JsonResponse
    {
        try {
            $oldToken   = JWTAuth::getToken();
            $payload    = JWTAuth::getPayload($oldToken);
            $rememberMe = $payload->get('remember_me', false);
            $newToken   = JWTAuth::refresh($oldToken);

            return response()->json([
                'success' => true,
                'data'    => [
                    'token'       => $newToken,
                    'token_type'  => 'Bearer',
                    'expires_in'  => ($rememberMe ? 60 * 24 * 7 : 60) * 60,
                    'remember_me' => $rememberMe,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Token tidak valid.'], 401);
        }
    }

    // ── ME ─────────────────────────────────────────────────────────────────

    public function me(): JsonResponse
    {
        $jwt     = JWTAuth::parseToken();
        $user    = $jwt->authenticate()->load('roles');
        $payload = $jwt->getPayload();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'              => $user->id,
                'username'        => $user->username,
                'nama_lengkap'    => $user->nama_lengkap,
                'email'           => $user->email,
                'email_verified'  => !is_null($user->email_verified_at),
                'roles'           => $user->roles->pluck('nama_role'),
                'token_info'      => [
                    'remember_me' => $payload->get('remember_me', false),
                    'expired_at'  => date('Y-m-d H:i:s', $payload->get('exp')),
                ],
            ],
        ]);
    }

    // ── VERIFY TOKEN (untuk kelompok 2, 3, 4) ─────────────────────────────

    public function verifyToken(): JsonResponse
    {
        try {
            $payload = JWTAuth::parseToken()->getPayload();
            return response()->json([
                'success' => true,
                'valid'   => true,
                'data'    => [
                    'user_id'    => $payload->get('sub'),
                    'nama'       => $payload->get('nama'),
                    'roles'      => $payload->get('roles'),
                    'expired_at' => date('Y-m-d H:i:s', $payload->get('exp')),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'valid' => false, 'message' => 'Token tidak valid.'], 401);
        }
    }

    // ── FORGOT PASSWORD ────────────────────────────────────────────────────

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['success' => true, 'message' => 'Jika email terdaftar, link reset password akan dikirim.']);
        }

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        $plainToken = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email'      => $user->email,
            'username'   => $user->username,
            'token'      => Hash::make($plainToken),
            'created_at' => now(),
            'expired_at' => now()->addMinutes(15),
            'used'       => false,
        ]);

        try {
            Mail::to($user->email)->send(new ForgotPasswordMail($user, $plainToken));
        } catch (\Exception $e) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            return response()->json(['success' => false, 'message' => 'Gagal mengirim email.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Link reset password telah dikirim. Berlaku 15 menit.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'                 => 'required|string',
            'email'                 => 'required|email',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('used', false)
            ->first();

        if (!$record || now()->isAfter($record->expired_at) || !Hash::check($request->token, $record->token)) {
            return response()->json(['success' => false, 'message' => 'Token tidak valid atau sudah expired.'], 422);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $user->update(['password' => Hash::make($request->password)]);
        DB::table('password_reset_tokens')->where('email', $request->email)->update(['used' => true]);

        return response()->json(['success' => true, 'message' => 'Password berhasil direset. Silakan login.']);
    }

    public function checkResetToken(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string', 'email' => 'required|email']);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('used', false)
            ->first();

        if (!$record || now()->isAfter($record->expired_at) || !Hash::check($request->token, $record->token)) {
            return response()->json(['success' => false, 'valid' => false, 'message' => 'Token tidak valid atau expired.'], 422);
        }

        return response()->json(['success' => true, 'valid' => true, 'expired_at' => $record->expired_at]);
    }
}
