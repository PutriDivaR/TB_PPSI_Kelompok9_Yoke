<?php

namespace Database\Factories;

use App\Models\BeritaAcaraKerusakan;
use App\Models\Klaim;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BeritaAcaraKerusakan> */
class BeritaAcaraKerusakanFactory extends Factory
{
    protected $model = BeritaAcaraKerusakan::class;

    public function definition(): array
    {
        return [
            'id_klaim' => Klaim::factory(),
            'nomor_bak' => fake()->unique()->bothify('YOKE-########'),
            'id_pengesah' => Pengguna::factory(),
            'tanggal_pengesahan' => '2026-11-13 10:00:00',
            'tanda_tangan_digital' => fake()->words(2, true),
            'status' => 'draf',
        ];
    }
}
