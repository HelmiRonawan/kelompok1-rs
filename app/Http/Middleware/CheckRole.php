<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penggunaan di route:
 *   ->middleware('role:superadmin')
 *   ->middleware('role:perawat,superadmin')   // any of these roles
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Akses ditolak. Role tidak sesuai.',
            'required_roles' => $roles,
            'your_roles'     => $user->roles->pluck('nama_role'),
        ], 403);
    }
}
