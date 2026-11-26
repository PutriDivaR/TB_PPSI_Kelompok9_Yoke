<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Memperbarui profil pengguna saat ini (Nama/Username, Foto Profil, dan Kata Sandi).
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login')->with('error', 'Silakan masuk terlebih dahulu untuk mengedit profil.');
        }

        $request->validate([
            'nama' => 'required|string|max:150',
            'kata_sandi' => 'nullable|string|min:4|confirmed',
            'foto_profil' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'nama.required' => 'Nama lengkap / username wajib diisi.',
            'kata_sandi.min' => 'Kata sandi baru minimal 4 karakter.',
            'kata_sandi.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'foto_profil.image' => 'Berkas foto profil harus berupa gambar.',
            'foto_profil.mimes' => 'Format foto profil harus JPG, PNG, JPEG, atau WEBP.',
            'foto_profil.max' => 'Ukuran foto profil maksimal 2MB.',
        ]);

        // 1. Perbarui Nama / Username
        $user->nama = $request->nama;

        // 2. Perbarui Kata Sandi jika diisi
        if ($request->filled('kata_sandi')) {
            $user->kata_sandi = Hash::make($request->kata_sandi);
        }

        // 3. Perbarui Foto Profil jika ada berkas diunggah
        if ($request->hasFile('foto_profil')) {
            $uploadDir = public_path('uploads/avatars');
            if (! file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $file = $request->file('foto_profil');
            $fileName = $user->id_pengguna . '.png';
            $file->move($uploadDir, $fileName);
        }

        $user->save();

        // 4. Catat Log Aktivitas
        LogAktivitas::create([
            'id_pengguna' => $user->id_pengguna,
            'modul' => 'Profil Pengguna',
            'aksi' => 'Ubah',
            'waktu' => now(),
        ]);

        return redirect()->back()->with('success', 'Profil Anda berhasil diperbarui!');
    }
}
