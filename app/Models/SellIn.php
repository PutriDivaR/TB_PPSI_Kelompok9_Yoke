<?php

namespace App\Models;

use Database\Factories\SellInFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellIn extends YokeModel
{
    /** @use HasFactory<SellInFactory> */
    use HasFactory;

    protected $table = 'sell_in';

    protected $primaryKey = 'id_sell_in';

    protected $fillable = ['id_detail_pengiriman', 'nomor_invoice', 'nomor_do', 'jumlah', 'tanggal'];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'tanggal' => 'date',
        ];
    }

    public function detailPengiriman(): BelongsTo
    {
        return $this->belongsTo(DetailPengiriman::class, 'id_detail_pengiriman', 'id_detail_pengiriman');
    }
}
