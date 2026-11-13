<?php

namespace App\Models;

use Database\Factories\DetailKlaimFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetailKlaim extends YokeModel
{
    /** @use HasFactory<DetailKlaimFactory> */
    use HasFactory;

    protected $table = 'detail_klaim';

    protected $primaryKey = 'id_detail_klaim';

    protected $fillable = ['id_klaim', 'id_detail_penerimaan', 'jenis_kerusakan', 'jumlah', 'penanggung_jawab', 'status_verifikasi'];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
        ];
    }

    public function klaim(): BelongsTo
    {
        return $this->belongsTo(Klaim::class, 'id_klaim', 'id_klaim');
    }

    public function detailPenerimaan(): BelongsTo
    {
        return $this->belongsTo(DetailPenerimaan::class, 'id_detail_penerimaan', 'id_detail_penerimaan');
    }

    public function fotoBuktiRecords(): HasMany
    {
        return $this->hasMany(FotoBukti::class, 'id_detail_klaim', 'id_detail_klaim');
    }
}
