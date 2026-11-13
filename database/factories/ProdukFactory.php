<?php

namespace Database\Factories;

use App\Models\Produk;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Produk> */
class ProdukFactory extends Factory
{
    protected $model = Produk::class;

    public function definition(): array
    {
        return [
            'kode_sku' => fake()->unique()->bothify('YOKE-########'),
            'nama_produk' => fake()->words(2, true),
            'harga' => 100000,
            'karton_per_kontainer' => 2000,
            'kategori' => null,
        ];
    }
}
