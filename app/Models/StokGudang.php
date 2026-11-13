<?php

namespace App\Models;

use Database\Factories\StokGudangFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StokGudang extends YokeModel
{
    /** @use HasFactory<StokGudangFactory> */
    use HasFactory;

    protected $table = 'stok_gudang';

    protected $primaryKey = 'id_stok';

    protected $fillable = ['id_distributor', 'id_produk', 'periode', 'stok_fisik', 'stok_layak_jual'];

    protected function casts(): array
    {
        return [
            'periode' => 'date',
            'stok_fisik' => 'integer',
            'stok_layak_jual' => 'integer',
        ];
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class, 'id_distributor', 'id_distributor');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }
}
