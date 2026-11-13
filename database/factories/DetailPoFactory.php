<?php

namespace Database\Factories;

use App\Models\DetailPo;
use App\Models\Produk;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DetailPo> */
class DetailPoFactory extends Factory
{
    protected $model = DetailPo::class;

    public function definition(): array
    {
        return [
            'id_po' => PurchaseOrder::factory(),
            'id_produk' => Produk::factory(),
            'jumlah_kontainer' => 1,
        ];
    }
}
