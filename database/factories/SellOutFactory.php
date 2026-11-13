<?php

namespace Database\Factories;

use App\Models\Distributor;
use App\Models\Produk;
use App\Models\SellOut;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SellOut> */
class SellOutFactory extends Factory
{
    protected $model = SellOut::class;

    public function definition(): array
    {
        return [
            'id_distributor' => Distributor::factory(),
            'id_produk' => Produk::factory(),
            'kanal' => 'Retail',
            'jumlah' => 1,
            'tanggal' => '2026-11-01',
        ];
    }
}
