<?php

namespace App\Models;

use Database\Factories\KlaimFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Klaim extends YokeModel
{
    /** @use HasFactory<KlaimFactory> */
    use HasFactory;

    protected $table = 'klaim';

    protected $primaryKey = 'id_klaim';

    protected $fillable = ['id_penerimaan', 'tanggal_pengajuan', 'status', 'tanggal_penyelesaian'];

    protected function casts(): array
    {
        return [
            'tanggal_pengajuan' => 'datetime',
            'tanggal_penyelesaian' => 'datetime',
        ];
    }

    public function penerimaan(): BelongsTo
    {
        return $this->belongsTo(PenerimaanBarang::class, 'id_penerimaan', 'id_penerimaan');
    }

    public function beritaAcaraKerusakanRecord(): HasOne
    {
        return $this->hasOne(BeritaAcaraKerusakan::class, 'id_klaim', 'id_klaim');
    }

    public function detailKlaimRecords(): HasMany
    {
        return $this->hasMany(DetailKlaim::class, 'id_klaim', 'id_klaim');
    }
}
