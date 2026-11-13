<?php

namespace App\Models;

use Database\Factories\SellOutFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellOut extends YokeModel
{
    /** @use HasFactory<SellOutFactory> */
    use HasFactory;

    protected $table = 'sell_out';

    protected $primaryKey = 'id_sell_out';

    protected $fillable = ['id_distributor', 'id_produk', 'kanal', 'jumlah', 'tanggal'];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'tanggal' => 'date',
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
