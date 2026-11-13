<?php

namespace Database\Factories;

use App\Models\DetailForecast;
use App\Models\Forecast;
use App\Models\Produk;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DetailForecast> */
class DetailForecastFactory extends Factory
{
    protected $model = DetailForecast::class;

    public function definition(): array
    {
        return [
            'id_forecast' => Forecast::factory(),
            'id_produk' => Produk::factory(),
            'jumlah_kontainer' => 1,
            'jumlah_disetujui' => null,
        ];
    }
}
