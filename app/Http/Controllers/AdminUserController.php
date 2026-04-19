<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::with('role')->orderBy('created_at', 'desc')->get();
        $roles = Role::orderBy('name')->get();
        return view('admin.users', compact('users', 'roles'));
    }

    public function apiList(Request $request)
    {
        $query = User::with('role');
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->role_id) {
            $query->where('role_id', $request->role_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        $users = $query->orderBy('created_at', 'desc')->get()->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role_id' => $u->role_id,
                'role_name' => $u->role?->name ?? '—',
                'portal_role' => $u->portal_role,
                'is_admin' => $u->is_admin,
                'status' => $u->status ?? 'active',
                'last_login_at' => $u->last_login_at?->diffForHumans() ?? 'Never',
                'created_at' => $u->created_at?->format('d M Y'),
            ];
        });
        return response()->json(['data' => $users]);
    }

    public function apiStore(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'password'     => ['required', Password::min(8)],
            'role_id'      => 'nullable|exists:roles,id',
            'portal_role'  => 'required|in:admin,user',
            'status'       => 'required|in:active,suspended',
        ]);

        $user = User::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'password'     => Hash::make($validated['password']),
            'role_id'      => $validated['role_id'] ?? null,
            'portal_role'  => $validated['portal_role'],
            'is_admin'     => $validated['portal_role'] === 'admin',
            'status'       => $validated['status'],
            'password_reset_required' => true,
        ]);

        return response()->json(['message' => 'User created successfully', 'data' => ['id' => $user->id, 'name' => $user->name]], 201);
    }

    public function apiUpdate(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email,' . $id,
            'role_id'     => 'nullable|exists:roles,id',
            'portal_role' => 'required|in:admin,user',
            'status'      => 'required|in:active,suspended',
            'password'    => ['nullable', Password::min(8)],
        ]);

        $user->name        = $validated['name'];
        $user->email       = $validated['email'];
        $user->role_id     = $validated['role_id'] ?? null;
        $user->portal_role = $validated['portal_role'];
        $user->is_admin    = $validated['portal_role'] === 'admin';
        $user->status      = $validated['status'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
            $user->password_reset_required = false;
        }

        $user->save();

        return response()->json(['message' => 'User updated successfully']);
    }

    public function apiDestroy($id)
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'You cannot delete your own account'], 403);
        }
        $user->delete();
        return response()->json(['message' => 'User deleted']);
    }

    public function apiRoles()
    {
        $roles = Role::orderBy('name')->get(['id', 'code', 'name', 'description']);
        return response()->json(['data' => $roles]);
    }
}
