<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\LogAktivitas;
use App\Models\Pengguna;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function csrf(): JsonResponse
    {
        return response()->json(['csrf_token' => csrf_token()]);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email:rfc', 'max:254'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $user = Pengguna::query()->where('email', $credentials['email'])->first();

        if (! $user || ! $user->status_aktif || ! Hash::check($credentials['password'], $user->kata_sandi)) {
            return response()->json(['message' => 'Email atau kata sandi tidak valid.'], 401);
        }

        Auth::login($user);
        $request->session()->regenerate();
        LogAktivitas::query()->create([
            'id_pengguna' => $user->id_pengguna,
            'modul' => 'Autentikasi',
            'aksi' => 'Login',
            'waktu' => now(),
        ]);

        return response()->json([
            'data' => (new UserResource($user->load(['peran', 'distributor'])))->resolve(),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var Pengguna $user */
        $user = $request->user();

        return response()->json([
            'data' => (new UserResource($user->load(['peran', 'distributor'])))->resolve(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        LogAktivitas::query()->create([
            'id_pengguna' => $user->id_pengguna,
            'modul' => 'Autentikasi',
            'aksi' => 'Logout',
            'waktu' => now(),
        ]);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Sesi berhasil diakhiri.']);
    }
}
