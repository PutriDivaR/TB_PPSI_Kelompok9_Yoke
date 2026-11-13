<?php

namespace Database\Factories;

use App\Models\DetailPengiriman;
use App\Models\DetailPo;
use App\Models\Pengiriman;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DetailPengiriman> */
class DetailPengirimanFactory extends Factory
{
    protected $model = DetailPengiriman::class;

    public function definition(): array
    {
        return [
            'id_pengiriman' => Pengiriman::factory(),
            'id_detail_po' => DetailPo::factory(),
            'jumlah_dikirim' => 1,
        ];
    }
}
