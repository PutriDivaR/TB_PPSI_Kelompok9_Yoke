<?php

namespace Database\Factories;

use App\Models\DetailKlaim;
use App\Models\DetailPenerimaan;
use App\Models\Klaim;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DetailKlaim> */
class DetailKlaimFactory extends Factory
{
    protected $model = DetailKlaim::class;

    public function definition(): array
    {
        return [
            'id_klaim' => Klaim::factory(),
            'id_detail_penerimaan' => DetailPenerimaan::factory(),
            'jenis_kerusakan' => fake()->words(2, true),
            'jumlah' => 1,
            'penanggung_jawab' => fake()->words(2, true),
            'status_verifikasi' => fake()->words(2, true),
        ];
    }
}
