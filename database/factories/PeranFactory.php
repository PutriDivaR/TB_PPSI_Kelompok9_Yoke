<?php

namespace Database\Factories;

use App\Models\Peran;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Peran> */
class PeranFactory extends Factory
{
    protected $model = Peran::class;

    public function definition(): array
    {
        return [
            'nama_peran' => 'Distributor',
            'deskripsi' => null,
        ];
    }
}
