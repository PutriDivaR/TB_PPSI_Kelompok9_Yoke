<?php

namespace App\Models;

use Database\Factories\DetailPenerimaanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetailPenerimaan extends YokeModel
{
    /** @use HasFactory<DetailPenerimaanFactory> */
    use HasFactory;

    protected $table = 'detail_penerimaan';

    protected $primaryKey = 'id_detail_penerimaan';

    protected $fillable = ['id_penerimaan', 'id_detail_pengiriman', 'jumlah_diterima', 'jumlah_baik', 'kondisi'];

    protected function casts(): array
    {
        return [
            'jumlah_diterima' => 'integer',
            'jumlah_baik' => 'integer',
        ];
    }

    public function penerimaan(): BelongsTo
    {
        return $this->belongsTo(PenerimaanBarang::class, 'id_penerimaan', 'id_penerimaan');
    }

    public function detailPengiriman(): BelongsTo
    {
        return $this->belongsTo(DetailPengiriman::class, 'id_detail_pengiriman', 'id_detail_pengiriman');
    }

    public function detailKlaimRecords(): HasMany
    {
        return $this->hasMany(DetailKlaim::class, 'id_detail_penerimaan', 'id_detail_penerimaan');
    }
}
