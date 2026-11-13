<?php

namespace Database\Factories;

use App\Models\Distributor;
use App\Models\Produk;
use App\Models\StokGudang;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StokGudang> */
class StokGudangFactory extends Factory
{
    protected $model = StokGudang::class;

    public function definition(): array
    {
        return [
            'id_distributor' => Distributor::factory(),
            'id_produk' => Produk::factory(),
            'periode' => '2026-11-01',
            'stok_fisik' => 1,
            'stok_layak_jual' => 1,
        ];
    }
}
