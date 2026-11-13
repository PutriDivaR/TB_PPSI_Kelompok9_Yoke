<?php

namespace App\Models;

use Database\Factories\ProdukFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produk extends YokeModel
{
    /** @use HasFactory<ProdukFactory> */
    use HasFactory;

    protected $table = 'produk';

    protected $primaryKey = 'id_produk';

    protected $fillable = ['kode_sku', 'nama_produk', 'harga', 'karton_per_kontainer', 'kategori'];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
            'karton_per_kontainer' => 'integer',
        ];
    }

    public function detailForecastRecords(): HasMany
    {
        return $this->hasMany(DetailForecast::class, 'id_produk', 'id_produk');
    }

    public function detailPoRecords(): HasMany
    {
        return $this->hasMany(DetailPo::class, 'id_produk', 'id_produk');
    }

    public function sellOutRecords(): HasMany
    {
        return $this->hasMany(SellOut::class, 'id_produk', 'id_produk');
    }

    public function stokGudangRecords(): HasMany
    {
        return $this->hasMany(StokGudang::class, 'id_produk', 'id_produk');
    }
}
