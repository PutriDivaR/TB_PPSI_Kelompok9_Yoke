<?php

namespace App\Console\Commands;

use App\Models\Pengguna;
use App\Models\Peran;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateInitialAdmin extends Command
{
    protected $signature = 'yoke:admin-create';

    protected $description = 'Buat akun Admin pertama tanpa kredensial bawaan';

    public function handle(): int
    {
        if (Pengguna::query()->whereHas('peran', fn ($query) => $query->where('nama_peran', 'Admin'))->exists()) {
            $this->components->error('Akun Admin sudah ada. Buat akun berikutnya melalui API Admin yang terlindungi.');

            return self::FAILURE;
        }

        $role = Peran::query()->where('nama_peran', 'Admin')->first();
        if (! $role) {
            $this->components->error('Peran Admin belum tersedia. Jalankan seeder peran terlebih dahulu.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Nama Admin'));
        $email = mb_strtolower(trim((string) $this->ask('Email Admin')));
        if ($name === '' || mb_strlen($name) > 150 || ! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) {
            $this->components->error('Nama atau email tidak valid.');

            return self::FAILURE;
        }
        if (Pengguna::query()->where('email', $email)->exists()) {
            $this->components->error('Email tersebut sudah digunakan.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Kata sandi Admin (minimal 10 karakter)');
        $confirmation = (string) $this->secret('Ulangi kata sandi');
        if (mb_strlen($password) < 10 || ! hash_equals($password, $confirmation)) {
            $this->components->error('Kata sandi harus minimal 10 karakter dan kedua isian harus sama.');

            return self::FAILURE;
        }

        Pengguna::query()->create([
            'nama' => $name,
            'email' => $email,
            'kata_sandi' => Hash::make($password),
            'id_peran' => $role->id_peran,
            'status_aktif' => true,
            'id_distributor' => null,
        ]);

        $this->components->info('Akun Admin pertama berhasil dibuat.');

        return self::SUCCESS;
    }
}
