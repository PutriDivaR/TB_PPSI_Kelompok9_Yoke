<?php

namespace Database\Factories;

use App\Models\Pengguna;
use App\Models\Peran;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<Pengguna> */
class PenggunaFactory extends Factory
{
    protected $model = Pengguna::class;

    public function definition(): array
    {
        return [
            'nama' => fake()->words(2, true),
            'email' => fake()->unique()->safeEmail(),
            'kata_sandi' => Hash::make('password'),
            'id_peran' => Peran::factory(),
            'status_aktif' => true,
            'id_distributor' => null,
        ];
    }
}
