<?php

namespace App\Models;

use Database\Factories\DetailPoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetailPo extends YokeModel
{
    /** @use HasFactory<DetailPoFactory> */
    use HasFactory;

    protected $table = 'detail_po';

    protected $primaryKey = 'id_detail_po';

    protected $fillable = ['id_po', 'id_produk', 'jumlah_kontainer'];

    protected function casts(): array
    {
        return [
            'jumlah_kontainer' => 'integer',
        ];
    }

    public function po(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'id_po', 'id_po');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }

    public function detailPengirimanRecords(): HasMany
    {
        return $this->hasMany(DetailPengiriman::class, 'id_detail_po', 'id_detail_po');
    }
}
