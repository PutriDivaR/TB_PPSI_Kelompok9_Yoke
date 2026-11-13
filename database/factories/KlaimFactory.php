<?php

namespace Database\Factories;

use App\Models\Klaim;
use App\Models\PenerimaanBarang;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Klaim> */
class KlaimFactory extends Factory
{
    protected $model = Klaim::class;

    public function definition(): array
    {
        return [
            'id_penerimaan' => PenerimaanBarang::factory(),
            'tanggal_pengajuan' => '2026-11-13 10:00:00',
            'status' => 'draf',
            'tanggal_penyelesaian' => null,
        ];
    }
}
