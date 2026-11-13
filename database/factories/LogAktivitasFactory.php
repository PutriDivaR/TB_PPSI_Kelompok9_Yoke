<?php

namespace Database\Factories;

use App\Models\LogAktivitas;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LogAktivitas> */
class LogAktivitasFactory extends Factory
{
    protected $model = LogAktivitas::class;

    public function definition(): array
    {
        return [
            'id_pengguna' => Pengguna::factory(),
            'modul' => fake()->words(2, true),
            'aksi' => fake()->words(2, true),
            'waktu' => '2026-11-13 10:00:00',
        ];
    }
}
