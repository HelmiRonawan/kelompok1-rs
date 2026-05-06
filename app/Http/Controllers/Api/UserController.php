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
    public function index(Request $request): JsonResponse
    {
        $users = User::with('roles')
            ->when($request->search, fn($q) =>
                $q->where('email', 'like', "%{$request->search}%")
            )
            ->when($request->role, fn($q) =>
                $q->whereHas('roles', fn($r) => $r->where('nama_role', $request->role))
            )
            ->paginate(15);

        return response()->json(['success' => true, 'data' => $users]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:8',
            'roles'       => 'required|array|min:1',
            'roles.*'     => 'string|exists:roles,nama_role',
        ]);

        $user = User::create([
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'is_active'         => true,
            'email_verified_at' => now(), // staff tidak perlu verifikasi email
        ]);

        $roleIds = Role::whereIn('nama_role', $validated['roles'])->pluck('id');
        $user->roles()->sync($roleIds);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dibuat.',
            'data'    => $user->load('roles'),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => User::with('roles')->findOrFail($id)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'email'       => "nullable|email|unique:users,email,{$id}",
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

        return response()->json(['success' => true, 'message' => 'User diupdate.', 'data' => $user->fresh()->load('roles')]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Tidak bisa hapus akun sendiri.'], 422);
        }

        $user->delete();
        return response()->json(['success' => true, 'message' => 'User dihapus.']);
    }
}
