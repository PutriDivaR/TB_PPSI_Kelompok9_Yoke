<?php

namespace Database\Factories;

use App\Models\TahunFiskal;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TahunFiskal> */
class TahunFiskalFactory extends Factory
{
    protected $model = TahunFiskal::class;

    public function definition(): array
    {
        return [
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2027-09-30',
            'status' => null,
        ];
    }
}
