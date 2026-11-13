<?php

namespace Database\Factories;

use App\Models\DetailPenerimaan;
use App\Models\DetailPengiriman;
use App\Models\PenerimaanBarang;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DetailPenerimaan> */
class DetailPenerimaanFactory extends Factory
{
    protected $model = DetailPenerimaan::class;

    public function definition(): array
    {
        return [
            'id_penerimaan' => PenerimaanBarang::factory(),
            'id_detail_pengiriman' => DetailPengiriman::factory(),
            'jumlah_diterima' => 1,
            'jumlah_baik' => 1,
            'kondisi' => fake()->words(2, true),
        ];
    }
}
