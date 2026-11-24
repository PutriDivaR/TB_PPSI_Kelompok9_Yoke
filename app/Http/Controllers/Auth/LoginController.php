<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * Tampilkan form login.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect($this->redirectByRole(Auth::user()));
        }

        return view('auth.login');
    }

    /**
     * Proses autentikasi login.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:1',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $pengguna = Pengguna::with('peran')
            ->where('email', $request->email)
            ->first();

        // Hindari user enumeration: gunakan pesan umum untuk email maupun kata sandi yang salah
        if (! $pengguna || ! Hash::check($request->password, $pengguna->kata_sandi)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email atau kata sandi salah.']);
        }

        if (! $pengguna->status_aktif) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.']);
        }

        Auth::login($pengguna, $request->boolean('remember'));
        $request->session()->regenerate();

        // Catat log aktivitas login
        LogAktivitas::create([
            'id_pengguna' => $pengguna->id_pengguna,
            'modul' => 'Autentikasi',
            'aksi' => 'Login',
            'waktu' => now(),
        ]);

        // Set mock_role session agar sidebar kompatibel
        $roleName = strtolower($pengguna->peran->nama_peran ?? 'distributor');
        $roleMap = [
            'admin' => 'admin',
            'distributor' => 'distributor',
            'logistik pusat' => 'logistik',
            'sam' => 'sam',
        ];
        session(['mock_role' => $roleMap[$roleName] ?? 'distributor']);

        return redirect($this->redirectByRole($pengguna));
    }

    /**
     * Proses logout pengguna.
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            LogAktivitas::create([
                'id_pengguna' => $user->id_pengguna,
                'modul' => 'Autentikasi',
                'aksi' => 'Logout',
                'waktu' => now(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda berhasil keluar dari sistem.');
    }

    /**
     * Tentukan halaman tujuan berdasarkan peran pengguna.
     */
    protected function redirectByRole(Pengguna $user): string
    {
        $user->loadMissing('peran');
        $roleName = strtolower($user->peran->nama_peran ?? '');

        return match ($roleName) {
            'admin' => route('admin.ringkasan'),
            'distributor' => route('e-po.distributor.hitung-forecast'),
            'logistik pusat' => route('simulation.logistik'),
            'sam' => route('klaim.sam.antrean'),
            default => route('e-po.distributor.hitung-forecast'),
        };
    }
}
