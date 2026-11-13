<?php

namespace App\Models;

use Database\Factories\FotoBuktiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FotoBukti extends YokeModel
{
    /** @use HasFactory<FotoBuktiFactory> */
    use HasFactory;

    protected $table = 'foto_bukti';

    protected $primaryKey = 'id_foto';

    protected $fillable = ['id_detail_klaim', 'path_berkas', 'tanggal_unggah'];

    protected function casts(): array
    {
        return [
            'tanggal_unggah' => 'datetime',
        ];
    }

    public function detailKlaim(): BelongsTo
    {
        return $this->belongsTo(DetailKlaim::class, 'id_detail_klaim', 'id_detail_klaim');
    }
}
