<?php

// app/Http/Controllers/UserManagementController.php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserManagementController extends Controller
{
    // GET /api/users?search=&role=&per_page=
    public function index(Request $request) {
        $q = User::query()->latest('id');

        if ($s = trim($request->query('search', ''))) {
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%$s%")
                  ->orWhere('email', 'like', "%$s%");
            });
        }

        if ($role = $request->query('role')) {
            $q->where('role', $role);
        }

        $perPage = max(1, min((int)$request->query('per_page', 15), 100));
        return $q->paginate($perPage);
    }

    // GET /api/users/{id}
    public function show($id) {
        return User::findOrFail($id);
    }

    // POST /api/users
    public function store(Request $request) {
        $data = $request->validate([
            'name'     => ['required','string','max:100'],
            'email'    => ['required','email', Rule::unique('users','email')],
            'password' => ['required','string','min:6'],
            'role'     => ['required', Rule::in(['admin','user'])],
        ]);

        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        return response()->json($user, 201);
    }

    // PUT /api/users/{id}
    public function update(Request $request, $id) {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name'     => ['sometimes','required','string','max:100'],
            'email'    => ['sometimes','required','email', Rule::unique('users','email')->ignore($user->id)],
            'password' => ['sometimes','nullable','string','min:6'],
            'role'     => ['sometimes','required', Rule::in(['admin','user'])],
        ]);

        // proteksi: minimal harus ada 1 admin
        if (array_key_exists('role', $data) && $user->role === 'admin' && $data['role'] !== 'admin') {
            $adminCount = User::where('role','admin')->count();
            if ($adminCount <= 1) {
                return response()->json(['message' => 'Tidak bisa menurunkan role admin terakhir.'], 422);
            }
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->fill($data)->save();
        return response()->json($user);
    }

    // DELETE /api/users/{id}
    public function destroy(Request $request, $id) {
        $user = User::findOrFail($id);

        // tidak boleh hapus dirinya sendiri
        if ($request->user()->id == $user->id) {
            return response()->json(['message' => 'Tidak bisa menghapus akun sendiri.'], 422);
        }

        // tidak boleh hapus admin terakhir
        if ($user->role === 'admin') {
            $adminCount = User::where('role','admin')->count();
            if ($adminCount <= 1) {
                return response()->json(['message' => 'Tidak bisa menghapus admin terakhir.'], 422);
            }
        }

        // hapus semua token user tersebut (opsional)
        DB::table('personal_access_tokens')->where('tokenable_id', $user->id)->delete();

        $user->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
