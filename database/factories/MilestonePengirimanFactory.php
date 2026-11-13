<?php

namespace Database\Factories;

use App\Models\MilestonePengiriman;
use App\Models\Pengiriman;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MilestonePengiriman> */
class MilestonePengirimanFactory extends Factory
{
    protected $model = MilestonePengiriman::class;

    public function definition(): array
    {
        return [
            'id_pengiriman' => Pengiriman::factory(),
            'urutan' => 1,
            'nama_tahap' => fake()->words(2, true),
            'tanggal_pembaruan' => '2026-11-13 10:00:00',
            'keterangan' => null,
        ];
    }
}
