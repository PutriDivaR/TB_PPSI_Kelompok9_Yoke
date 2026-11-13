<?php

namespace Database\Factories;

use App\Models\Distributor;
use App\Models\Forecast;
use App\Models\TahunFiskal;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Forecast> */
class ForecastFactory extends Factory
{
    protected $model = Forecast::class;

    public function definition(): array
    {
        return [
            'id_distributor' => Distributor::factory(),
            'id_tahun_fiskal' => TahunFiskal::factory(),
            'periode' => '2026-11-01',
            'tanggal_pengajuan' => '2026-11-13 10:00:00',
            'status' => 'draf',
            'tanggal_disetujui' => null,
            'id_penyetuju' => null,
        ];
    }
}
