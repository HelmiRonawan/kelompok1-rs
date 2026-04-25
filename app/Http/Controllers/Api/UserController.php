<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * GET /api/users
     * Superadmin: list semua user
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::with('roles')
            ->when($request->search, fn($q) =>
                $q->where('nama_lengkap', 'like', "%{$request->search}%")
                  ->orWhere('username', 'like', "%{$request->search}%")
            )
            ->when($request->role, fn($q) =>
                $q->whereHas('roles', fn($r) => $r->where('nama_role', $request->role))
            )
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $users,
        ]);
    }

    /**
     * POST /api/users
     * Superadmin: buat user baru (perawat/admin)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username'    => 'required|string|unique:users,username|max:50',
            'email'       => 'nullable|email|unique:users,email',
            'password'    => 'required|string|min:8',
            'nama_lengkap'=> 'required|string|max:100',
            'roles'       => 'required|array|min:1',
            'roles.*'     => 'string|exists:roles,nama_role',
        ]);

        $user = User::create([
            'username'    => $validated['username'],
            'email'       => $validated['email'] ?? null,
            'password'    => Hash::make($validated['password']),
            'nama_lengkap'=> $validated['nama_lengkap'],
        ]);

        // Assign roles
        $roleIds = Role::whereIn('nama_role', $validated['roles'])->pluck('id');
        $user->roles()->sync($roleIds);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dibuat.',
            'data'    => $user->load('roles'),
        ], 201);
    }

    /**
     * GET /api/users/{id}
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with('roles')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $user,
        ]);
    }

    /**
     * PUT /api/users/{id}
     * Superadmin: update user
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'email'       => "nullable|email|unique:users,email,{$id}",
            'nama_lengkap'=> 'sometimes|string|max:100',
            'password'    => 'sometimes|string|min:8',
            'is_active'   => 'sometimes|boolean',
            'roles'       => 'sometimes|array|min:1',
            'roles.*'     => 'string|exists:roles,nama_role',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update(collect($validated)->except('roles')->toArray());

        if (isset($validated['roles'])) {
            $roleIds = Role::whereIn('nama_role', $validated['roles'])->pluck('id');
            $user->roles()->sync($roleIds);
        }

        return response()->json([
            'success' => true,
            'message' => 'User berhasil diupdate.',
            'data'    => $user->fresh()->load('roles'),
        ]);
    }

    /**
     * DELETE /api/users/{id}
     * Superadmin: hapus user (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa menghapus akun sendiri.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dihapus.',
        ]);
    }
}
