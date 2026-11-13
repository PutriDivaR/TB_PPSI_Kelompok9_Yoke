<?php

namespace App\Models;

use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends YokeModel
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory;

    protected $table = 'purchase_order';

    protected $primaryKey = 'id_po';

    protected $fillable = ['nomor_po', 'id_forecast', 'tanggal_pengajuan', 'status', 'tanggal_disetujui', 'id_penyetuju'];

    protected function casts(): array
    {
        return [
            'tanggal_pengajuan' => 'datetime',
            'tanggal_disetujui' => 'datetime',
        ];
    }

    public function forecast(): BelongsTo
    {
        return $this->belongsTo(Forecast::class, 'id_forecast', 'id_forecast');
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_penyetuju', 'id_pengguna');
    }

    public function detailPoRecordsAsPo(): HasMany
    {
        return $this->hasMany(DetailPo::class, 'id_po', 'id_po');
    }

    public function pengirimanRecordsAsPo(): HasMany
    {
        return $this->hasMany(Pengiriman::class, 'id_po', 'id_po');
    }
}
