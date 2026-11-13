<?php

namespace Database\Factories;

use App\Models\DetailKlaim;
use App\Models\FotoBukti;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FotoBukti> */
class FotoBuktiFactory extends Factory
{
    protected $model = FotoBukti::class;

    public function definition(): array
    {
        return [
            'id_detail_klaim' => DetailKlaim::factory(),
            'path_berkas' => fake()->words(2, true),
            'tanggal_unggah' => '2026-11-13 10:00:00',
        ];
    }
}
