<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ForgotPasswordMail;
use App\Mail\VerifikasiEmailMail;
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
    // ── REGISTER ───────────────────────────────────────────────────────────

    /**
     * POST /api/auth/register
     *
     * Register hanya butuh email.
     * Data lengkap pasien diisi saat pendaftaran antrian.
     *
     * Body: { email }
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'email'                 => 'required|email|unique:users,email'
        ]);

        DB::beginTransaction();
        try {
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $user = User::create([
                'email'                    => $request->email,
                'password'                 => Hash::make(Str::random(32)),
                'is_active'                => false,
                'email_verification_token' => hash('sha256', $otp),
                'email_verified_at'        => null,
                'otp_expired_at'           => now()->addMinutes(10),
            ]);

            // Assign role pasien
            $pasienRole = Role::where('nama_role', 'pasien')->first();
            if ($pasienRole) {
                $user->roles()->attach($pasienRole->id);
            }

            Mail::to($user->email)->send(new VerifikasiEmailMail($user, $otp));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registrasi berhasil! Cek email untuk kode OTP verifikasi.',
                'data'    => ['email' => $user->email],
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Registrasi gagal.',
                'error'   => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    // ── VERIFIKASI EMAIL ───────────────────────────────────────────────────

    /**
     * POST /api/auth/verify-email
     * 
     * Verifikasi email sekaligus set password baru.
     * OTP hanya berlaku 10 menit sejak registrasi atau request ulang.
     * 
     * Body: { email, otp, password, password_confirmation }
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
            'otp'   => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Akun tidak ditemukan.'], 404);
        }

        if ($user->email_verified_at) {
            return response()->json(['success' => true, 'message' => 'Email sudah diverifikasi. Silakan login.']);
        }

        if (!$user->otp_expired_at || now()->isAfter($user->otp_expired_at)) {
            return response()->json(['success' => false, 'message' => 'OTP sudah expired. Minta kirim ulang.'], 422);
        }

        if (!$user->email_verification_token || $user->email_verification_token !== hash('sha256', $request->otp)) {
            return response()->json(['success' => false, 'message' => 'OTP tidak valid.'], 422);
        }

        $user->update([
            'password'                 => Hash::make($request->password),
            'is_active'                => true,
            'email_verified_at'        => now(),
            'email_verification_token' => null,
            'otp_expired_at'           => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Email berhasil diverifikasi! Silakan login.']);
    }

    // ── RESEND VERIFICATION ────────────────────────────────────────────────

    /**
     * POST /api/auth/resend-verification
     * Body: { email }
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->email_verified_at) {
            return response()->json(['success' => true, 'message' => 'Jika email belum diverifikasi, OTP baru akan dikirim.']);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update([
            'email_verification_token' => hash('sha256', $otp),
            'otp_expired_at'           => now()->addMinutes(10),
        ]);

        try {
            Mail::to($user->email)->send(new VerifikasiEmailMail($user, $otp));
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengirim email.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'OTP baru telah dikirim ke email.']);
    }

    // ── LOGIN ──────────────────────────────────────────────────────────────

    /**
     * POST /api/auth/login
     *
     * Login pakai EMAIL (bukan username).
     * Body: { email, password, remember_me? }
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'       => 'required|email',
            'password'    => 'required|string',
            'remember_me' => 'sometimes|boolean',
        ]);

        $user = User::with('roles')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Email atau password salah.'], 401);
        }

        // Cek verifikasi email (khusus pasien)
        if (!$user->email_verified_at && $user->hasRole('pasien')) {
            return response()->json([
                'success' => false,
                'message' => 'Email belum diverifikasi. Cek Gmail atau minta kirim ulang OTP.',
                'action'  => 'resend_verification',
            ], 403);
        }

        if (!$user->is_active) {
            return response()->json(['success' => false, 'message' => 'Akun tidak aktif. Hubungi admin.'], 403);
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
                    'email'       => $user->email,
                    'roles'       => $user->roles->pluck('nama_role'),
                    'has_data_pasien' => $user->pasien()->exists(), // hint ke frontend
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
                'email'           => $user->email,
                'email_verified'  => !is_null($user->email_verified_at),
                'roles'           => $user->roles->pluck('nama_role'),
                'has_data_pasien' => $user->pasien()->exists(),
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
            return response()->json(['success' => true, 'message' => 'Jika email terdaftar, OTP akan dikirim.']);
        }

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->insert([
            'email'      => $user->email,
            'token'      => hash('sha256', $otp),
            'created_at' => now(),
            'expired_at' => now()->addMinutes(10),
            'used'       => false,
        ]);

        try {
            Mail::to($user->email)->send(new ForgotPasswordMail($user, $otp));
        } catch (\Exception $e) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            return response()->json(['success' => false, 'message' => 'Gagal mengirim email.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'OTP reset password telah dikirim. Berlaku 10 menit.']);
    }

    public function checkResetToken(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email', 'otp' => 'required|string|size:6']);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('used', false)
            ->first();

        if (!$record || now()->isAfter($record->expired_at) || hash('sha256', $request->otp) !== $record->token) {
            return response()->json(['success' => false, 'valid' => false, 'message' => 'OTP tidak valid atau expired.'], 422);
        }

        return response()->json(['success' => true, 'valid' => true, 'expired_at' => $record->expired_at]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email'                 => 'required|email',
            'otp'                   => 'required|string|size:6',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('used', false)
            ->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'OTP tidak valid.'], 422);
        }

        if (now()->isAfter($record->expired_at)) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['success' => false, 'message' => 'OTP expired. Silakan request ulang.'], 422);
        }

        if (hash('sha256', $request->otp) !== $record->token) {
            return response()->json(['success' => false, 'message' => 'OTP tidak valid.'], 422);
        }

        User::where('email', $request->email)->firstOrFail()
            ->update(['password' => Hash::make($request->password)]);

        DB::table('password_reset_tokens')->where('email', $request->email)->update(['used' => true]);

        return response()->json(['success' => true, 'message' => 'Password berhasil direset. Silakan login.']);
    }
}
