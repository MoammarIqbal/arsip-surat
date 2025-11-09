<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    // POST /api/login
    public function login(Request $request)
    {
        $cred = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $cred['email'])->first();

        if (!$user || !Hash::check($cred['password'], $user->password)) {
            return response()->json(['message' => 'email atau pasword salah'], 401);
        }

        // Buat Personal Access Token (Sanctum)
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Berhasil Login',
            'token' => $token,
            'user' => $user,
        ], 200);
    }

    // GET /api/profile
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    // POST /api/logout
    public function logout(Request $request)
    {
        // Hapus token yang sedang dipakai
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
