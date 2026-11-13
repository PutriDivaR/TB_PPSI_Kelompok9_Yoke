<?php

namespace App\Models;

use Database\Factories\BeritaAcaraKerusakanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeritaAcaraKerusakan extends YokeModel
{
    /** @use HasFactory<BeritaAcaraKerusakanFactory> */
    use HasFactory;

    protected $table = 'berita_acara_kerusakan';

    protected $primaryKey = 'id_bak';

    protected $fillable = ['id_klaim', 'nomor_bak', 'id_pengesah', 'tanggal_pengesahan', 'tanda_tangan_digital', 'status'];

    protected function casts(): array
    {
        return [
            'tanggal_pengesahan' => 'datetime',
        ];
    }

    public function klaim(): BelongsTo
    {
        return $this->belongsTo(Klaim::class, 'id_klaim', 'id_klaim');
    }

    public function pengesah(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengesah', 'id_pengguna');
    }
}
