<?php

namespace App\Models;

use Database\Factories\PengirimanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pengiriman extends YokeModel
{
    /** @use HasFactory<PengirimanFactory> */
    use HasFactory;

    protected $table = 'pengiriman';

    protected $primaryKey = 'id_pengiriman';

    protected $fillable = ['id_po', 'nomor_kontainer', 'nama_vessel', 'eta_awal', 'eta_terkini', 'status'];

    protected function casts(): array
    {
        return [
            'eta_awal' => 'date',
            'eta_terkini' => 'date',
        ];
    }

    public function po(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'id_po', 'id_po');
    }

    public function milestonePengirimanRecords(): HasMany
    {
        return $this->hasMany(MilestonePengiriman::class, 'id_pengiriman', 'id_pengiriman');
    }

    public function penerimaanBarangRecord(): HasOne
    {
        return $this->hasOne(PenerimaanBarang::class, 'id_pengiriman', 'id_pengiriman');
    }

    public function detailPengirimanRecords(): HasMany
    {
        return $this->hasMany(DetailPengiriman::class, 'id_pengiriman', 'id_pengiriman');
    }
}
