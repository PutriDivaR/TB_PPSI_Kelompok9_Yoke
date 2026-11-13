<?php

namespace Database\Factories;

use App\Models\PenerimaanBarang;
use App\Models\Pengiriman;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PenerimaanBarang> */
class PenerimaanBarangFactory extends Factory
{
    protected $model = PenerimaanBarang::class;

    public function definition(): array
    {
        return [
            'id_pengiriman' => Pengiriman::factory(),
            'tanggal_terima' => '2026-11-13 10:00:00',
        ];
    }
}
