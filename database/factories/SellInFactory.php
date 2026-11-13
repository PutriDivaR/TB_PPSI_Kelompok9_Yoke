<?php

namespace Database\Factories;

use App\Models\DetailPengiriman;
use App\Models\SellIn;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SellIn> */
class SellInFactory extends Factory
{
    protected $model = SellIn::class;

    public function definition(): array
    {
        return [
            'id_detail_pengiriman' => DetailPengiriman::factory(),
            'nomor_invoice' => null,
            'nomor_do' => null,
            'jumlah' => 1,
            'tanggal' => '2026-11-01',
        ];
    }
}
