<?php

namespace App\Models;

use Database\Factories\DetailPengirimanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetailPengiriman extends YokeModel
{
    /** @use HasFactory<DetailPengirimanFactory> */
    use HasFactory;

    protected $table = 'detail_pengiriman';

    protected $primaryKey = 'id_detail_pengiriman';

    protected $fillable = ['id_pengiriman', 'id_detail_po', 'jumlah_dikirim'];

    protected function casts(): array
    {
        return [
            'jumlah_dikirim' => 'integer',
        ];
    }

    public function pengiriman(): BelongsTo
    {
        return $this->belongsTo(Pengiriman::class, 'id_pengiriman', 'id_pengiriman');
    }

    public function detailPo(): BelongsTo
    {
        return $this->belongsTo(DetailPo::class, 'id_detail_po', 'id_detail_po');
    }

    public function sellInRecords(): HasMany
    {
        return $this->hasMany(SellIn::class, 'id_detail_pengiriman', 'id_detail_pengiriman');
    }

    public function detailPenerimaanRecords(): HasMany
    {
        return $this->hasMany(DetailPenerimaan::class, 'id_detail_pengiriman', 'id_detail_pengiriman');
    }
}
