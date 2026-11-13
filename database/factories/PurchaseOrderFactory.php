<?php

namespace Database\Factories;

use App\Models\Forecast;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PurchaseOrder> */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'nomor_po' => fake()->unique()->bothify('YOKE-########'),
            'id_forecast' => Forecast::factory(),
            'tanggal_pengajuan' => '2026-11-13 10:00:00',
            'status' => 'draf',
            'tanggal_disetujui' => null,
            'id_penyetuju' => null,
        ];
    }
}
