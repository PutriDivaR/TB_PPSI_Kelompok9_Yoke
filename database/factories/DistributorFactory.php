<?php

namespace Database\Factories;

use App\Models\Distributor;
use App\Models\Wilayah;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Distributor> */
class DistributorFactory extends Factory
{
    protected $model = Distributor::class;

    public function definition(): array
    {
        return [
            'nama' => fake()->words(2, true),
            'id_wilayah' => Wilayah::factory(),
            'kontak' => fake()->words(2, true),
            'alamat_gudang' => null,
            'nomor_mou' => null,
            'status_aktif' => true,
        ];
    }
}
