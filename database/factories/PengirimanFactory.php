<?php

namespace Database\Factories;

use App\Models\Pengiriman;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Pengiriman> */
class PengirimanFactory extends Factory
{
    protected $model = Pengiriman::class;

    public function definition(): array
    {
        return [
            'id_po' => PurchaseOrder::factory(),
            'nomor_kontainer' => fake()->unique()->bothify('YOKE-########'),
            'nama_vessel' => null,
            'eta_awal' => '2026-11-01',
            'eta_terkini' => '2026-11-01',
            'status' => 'draf',
        ];
    }
}
