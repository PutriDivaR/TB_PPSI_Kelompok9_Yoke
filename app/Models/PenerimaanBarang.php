<?php

namespace App\Models;

use Database\Factories\PenerimaanBarangFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PenerimaanBarang extends YokeModel
{
    /** @use HasFactory<PenerimaanBarangFactory> */
    use HasFactory;

    protected $table = 'penerimaan_barang';

    protected $primaryKey = 'id_penerimaan';

    protected $fillable = ['id_pengiriman', 'tanggal_terima'];

    protected function casts(): array
    {
        return [
            'tanggal_terima' => 'datetime',
        ];
    }

    public function pengiriman(): BelongsTo
    {
        return $this->belongsTo(Pengiriman::class, 'id_pengiriman', 'id_pengiriman');
    }

    public function klaimRecordAsPenerimaan(): HasOne
    {
        return $this->hasOne(Klaim::class, 'id_penerimaan', 'id_penerimaan');
    }

    public function detailPenerimaanRecordsAsPenerimaan(): HasMany
    {
        return $this->hasMany(DetailPenerimaan::class, 'id_penerimaan', 'id_penerimaan');
    }
}
