<?php

namespace App\Models;

use Database\Factories\MilestonePengirimanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MilestonePengiriman extends YokeModel
{
    /** @use HasFactory<MilestonePengirimanFactory> */
    use HasFactory;

    protected $table = 'milestone_pengiriman';

    protected $primaryKey = 'id_milestone';

    protected $fillable = ['id_pengiriman', 'urutan', 'nama_tahap', 'tanggal_pembaruan', 'keterangan'];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'tanggal_pembaruan' => 'datetime',
        ];
    }

    public function pengiriman(): BelongsTo
    {
        return $this->belongsTo(Pengiriman::class, 'id_pengiriman', 'id_pengiriman');
    }
}
